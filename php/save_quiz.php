<?php
// ========================================
// FutureWay - save_quiz.php (แก้ไข: รับประกันว่า output เป็น JSON เสมอ)
// ========================================

ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

session_start();

header('Content-Type: application/json; charset=utf-8');

// ========================================
// ครอบทุกอย่างด้วย try-catch เพื่อรับประกันว่า
// ไม่ว่าจะพังตรงไหน จะได้ JSON กลับไปเสมอ ไม่ใช่ response ว่างๆ
// ========================================
try {

    // เช็คว่ามี output แปลกปลอมก่อนหน้านี้หรือไม่ (BOM, whitespace, warning)
    $earlyOutput = ob_get_clean();
    if (!empty(trim($earlyOutput))) {
        throw new Exception('PHP output before JSON: ' . $earlyOutput);
    }

    // เช็ค login
    if (!isset($_SESSION['username']) && !isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'กรุณาเข้าสู่ระบบก่อน']);
        exit;
    }

    // รับ JSON จาก quiz.html
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || !isset($input['grades']) || !isset($input['answers']) || !is_array($input['answers'])) {
        echo json_encode(['success' => false, 'error' => 'ข้อมูลไม่ครบ']);
        exit;
    }

    $grades  = $input['grades'];
    $answers = $input['answers'];

    // ตรวจสอบว่ามี key ของเกรดครบ และมีคำตอบอย่างน้อย 1 ข้อ
    $requiredSubjects = ['math', 'sci', 'eng', 'thai', 'social', 'art'];
    foreach ($requiredSubjects as $subj) {
        if (!isset($grades[$subj])) {
            echo json_encode(['success' => false, 'error' => "ขาดเกรดวิชา: $subj"]);
            exit;
        }
    }
    if (count($answers) === 0) {
        echo json_encode(['success' => false, 'error' => 'ไม่พบคำตอบแบบทดสอบ']);
        exit;
    }
    foreach ($answers as $a) {
        if (!isset($a['question_id']) || !isset($a['selected'])) {
            echo json_encode(['success' => false, 'error' => 'รูปแบบคำตอบไม่ถูกต้อง']);
            exit;
        }
    }

    // ========================================
    // เชื่อมต่อ Database
    // ========================================
    require_once __DIR__ . '/db_config.php';
    $conn = getDbConnection(); // ถ้า connect ไม่ได้ exception จะถูกจับโดย catch (Throwable $e) ท้ายไฟล์

    // ดึง user_id
    if (isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
    } else {
        $uname = $_SESSION['username'];
        $stmtU = $conn->prepare("SELECT id FROM users WHERE username = ?");
        if (!$stmtU) {
            throw new Exception('Prepare (SELECT user) ล้มเหลว: ' . $conn->error);
        }
        $stmtU->bind_param('s', $uname);
        $stmtU->execute();
        $rowU = $stmtU->get_result()->fetch_assoc();
        $stmtU->close();
        if (!$rowU) {
            echo json_encode(['success' => false, 'error' => 'ไม่พบข้อมูลผู้ใช้']);
            exit;
        }
        $userId = $rowU['id'];
        $_SESSION['user_id'] = $userId;
    }

    // ========================================
    // Step 1: เรียก Python Decision Tree ก่อน
    // (mbti ยังไม่รู้ค่า จนกว่า python จะคำนวณจาก answers ให้)
    // ========================================
    $pythonInput = json_encode([
        'grades'  => $grades,
        'answers' => $answers
    ]);

    require_once __DIR__ . '/python_config.php';
    $pythonPath = getPythonPath();
    $scriptPath = dirname(__DIR__) . '/decision_tree.py';

    if (!file_exists($scriptPath)) {
        throw new Exception("ไม่พบไฟล์ Python script ที่: $scriptPath");
    }

    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $cmd = '"' . $pythonPath . '" "' . $scriptPath . '"';
    $process = proc_open($cmd, $descriptors, $pipes);

    if (!is_resource($process)) {
        throw new Exception('ไม่สามารถเรียก Python ได้');
    }

    fwrite($pipes[0], $pythonInput);
    fclose($pipes[0]);

    $pythonOutput = stream_get_contents($pipes[1]);
    $pythonStderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    proc_close($process);

    $pyResult = json_decode($pythonOutput, true);

    if ($pyResult === null && trim($pythonOutput) !== '') {
        // stdout มีข้อมูล แต่ parse JSON ไม่ได้ -> python พังกลางทาง print ไม่ครบ
        $errMsg = 'Python ส่งค่ากลับมาไม่ใช่ JSON ที่ถูกต้อง (' . json_last_error_msg() . ')';
    } elseif (!$pyResult) {
        // stdout ว่างเปล่าจริงๆ -> python ไม่ถูกเรียก หรือ crash ตั้งแต่ต้นไฟล์
        $errMsg = 'ไม่ได้รับ output จาก Python เลย (ตรวจสอบ python path และว่าลง mysql-connector-python แล้วหรือยัง)';
    } elseif (isset($pyResult['error'])) {
        // python รันได้ แต่ error ระหว่างทาง (เช่น DB connect ไม่ได้)
        $errMsg = 'Python error: ' . $pyResult['error'];
    } elseif (empty($pyResult['top3'])) {
        // python รันสำเร็จ เชื่อม DB ได้ปกติ แต่ query ตาราง branches ไม่เจอแถวที่ is_active = 1
        $errMsg = 'ไม่พบข้อมูลสาขาในระบบ (ตาราง branches อาจว่าง หรือไม่มีแถวที่ is_active = 1)';
    } elseif (!isset($pyResult['mbti']) || !is_string($pyResult['mbti']) || strlen($pyResult['mbti']) !== 4) {
        // python รันได้แต่คำนวณ mbti จาก answers ไม่สำเร็จ (เช่น question_id ไม่ตรงกับ DB เลยสักข้อ)
        $errMsg = 'ไม่สามารถคำนวณผล MBTI จากคำตอบที่ส่งมาได้';
    } else {
        $errMsg = null;
    }

    if ($errMsg !== null) {
        echo json_encode([
            'success' => false,
            'error'   => $errMsg,
            'debug'   => [
                'stdout'      => $pythonOutput,
                'stderr'      => $pythonStderr,
                'python_path' => $pythonPath,
                'script_path' => $scriptPath,
            ]
        ]);
        exit;
    }

    // ========================================
    // Step 2: บันทึกผลลัพธ์ทั้งหมดลง DB ในครั้งเดียว
    // (เกรด + mbti ที่ python คำนวณได้ + คณะที่แนะนำอันดับ 1)
    // ========================================
    $mbti = $pyResult['mbti'];

    // แยก string offset ออกมาเป็นตัวแปรก่อน เพราะ PHP 8+ ห้าม reference string offset ตรงๆ ใน bind_param
    $mbtiEI = $mbti[0];
    $mbtiSN = $mbti[1];
    $mbtiTF = $mbti[2];
    $mbtiJP = $mbti[3];

    $top1       = $pyResult['top3'][0];
    $branchId   = $top1['id']    ?? null;
    $branchName = $top1['name']  ?? null;
    $score      = $top1['score'] ?? null;

    $stmt = $conn->prepare("
        INSERT INTO quiz_results 
            (user_id, grade_math, grade_sci, grade_eng, grade_thai, grade_social, grade_art,
             mbti_type, mbti_e_i, mbti_s_n, mbti_t_f, mbti_j_p,
             branch_id, branch_name, score)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    if (!$stmt) {
        throw new Exception('Prepare (INSERT quiz_results) ล้มเหลว: ' . $conn->error);
    }

    $stmt->bind_param(
        'iddddddsssssisd',
        $userId,
        $grades['math'], $grades['sci'], $grades['eng'],
        $grades['thai'], $grades['social'], $grades['art'],
        $mbti,
        $mbtiEI, $mbtiSN, $mbtiTF, $mbtiJP,
        $branchId, $branchName, $score
    );

    if (!$stmt->execute()) {
        throw new Exception('บันทึกข้อมูลไม่สำเร็จ: ' . $stmt->error);
    }

    $resultId = $stmt->insert_id;
    $stmt->close();
    $conn->close();

    // ========================================
    // Step 3: ส่ง result_id และ mbti กลับให้ quiz.html
    // ========================================
    echo json_encode([
        'success'   => true,
        'result_id' => $resultId,
        'mbti'      => $mbti
    ]);

} catch (Throwable $e) {
    // ดักทุก error/exception ที่หลุดมา -> ยังไงก็ได้ JSON กลับไปแน่นอน
    error_log('save_quiz.php error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error'   => 'เกิดข้อผิดพลาดที่ server: ' . $e->getMessage()
    ]);
}
