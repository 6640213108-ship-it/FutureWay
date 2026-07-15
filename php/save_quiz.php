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

// ปิด mysqli exception mode -> ให้เช็ค error เองผ่าน connect_error/errno แทน
// (ถ้าไม่ปิด PHP 8.1+ จะ throw exception ตอน connect ไม่ได้ ทำให้ script ตายแบบไม่มี output)
mysqli_report(MYSQLI_REPORT_OFF);

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
    if (!$input || !isset($input['grades']) || !isset($input['mbti'])) {
        echo json_encode(['success' => false, 'error' => 'ข้อมูลไม่ครบ']);
        exit;
    }

    $grades = $input['grades'];
    $mbti   = $input['mbti'];

    // ตรวจสอบว่ามี key ของเกรดครบ และ mbti มีความยาว 4 ตัวอักษร
    $requiredSubjects = ['math', 'sci', 'eng', 'thai', 'social', 'art'];
    foreach ($requiredSubjects as $subj) {
        if (!isset($grades[$subj])) {
            echo json_encode(['success' => false, 'error' => "ขาดเกรดวิชา: $subj"]);
            exit;
        }
    }
    if (!is_string($mbti) || strlen($mbti) !== 4) {
        echo json_encode(['success' => false, 'error' => 'รูปแบบ MBTI ไม่ถูกต้อง']);
        exit;
    }

    // ========================================
    // เชื่อมต่อ Database
    // ========================================
    // MySQL รันที่ port 3306 (default ของ XAMPP/Laragon/MySQL ทั่วไป)
 $conn = new mysqli('tokaido.proxy.rlwy.net', 'root', 'mysql -h tokaido.proxy.rlwy.net -u root -p OLdaGruletpcPRSKSZkUOUrKaUWmDjri --port 57745 --protocol=TCP railway', 'railway', 57745);
    if ($conn->connect_error) {
        echo json_encode(['success' => false, 'error' => 'DB: ' . $conn->connect_error]);
        exit;
    }
    $conn->set_charset('utf8mb4');

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
    // Step 1: บันทึกเกรด + MBTI ลง DB ก่อน
    // ========================================
    $stmt = $conn->prepare("
        INSERT INTO quiz_results 
            (user_id, grade_math, grade_sci, grade_eng, grade_thai, grade_social, grade_art,
             mbti_type, mbti_e_i, mbti_s_n, mbti_t_f, mbti_j_p)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    if (!$stmt) {
        throw new Exception('Prepare (INSERT quiz_results) ล้มเหลว: ' . $conn->error);
    }

    // แยก string offset ออกมาเป็นตัวแปรก่อน เพราะ PHP 8+ ห้าม reference string offset ตรงๆ ใน bind_param
    $mbtiEI = $mbti[0];
    $mbtiSN = $mbti[1];
    $mbtiTF = $mbti[2];
    $mbtiJP = $mbti[3];

    $stmt->bind_param(
        'iddddddsssss',
        $userId,
        $grades['math'], $grades['sci'], $grades['eng'],
        $grades['thai'], $grades['social'], $grades['art'],
        $mbti,
        $mbtiEI, $mbtiSN, $mbtiTF, $mbtiJP
    );

    if (!$stmt->execute()) {
        throw new Exception('บันทึกข้อมูลไม่สำเร็จ: ' . $stmt->error);
    }

    $resultId = $stmt->insert_id;
    $stmt->close();

    // ========================================
    // Step 2: เรียก Python Decision Tree
    // ========================================
    $pythonInput = json_encode([
        'grades' => $grades,
        'mbti'   => $mbti
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
        // (เช่น import mysql.connector ไม่ได้ เพราะยังไม่ได้ pip install mysql-connector-python
        //  ให้ python ตัวที่ตรงกับ $pythonPath)
        $errMsg = 'ไม่ได้รับ output จาก Python เลย (ตรวจสอบ python path และว่าลง mysql-connector-python แล้วหรือยัง)';
    } elseif (isset($pyResult['error'])) {
        // python รันได้ แต่ error ระหว่างทาง (เช่น DB connect ไม่ได้)
        $errMsg = 'Python error: ' . $pyResult['error'];
    } elseif (empty($pyResult['top3'])) {
        // python รันสำเร็จ เชื่อม DB ได้ปกติ แต่ query ตาราง branches ไม่เจอแถวที่ is_active = 1
        $errMsg = 'ไม่พบข้อมูลสาขาในระบบ (ตาราง branches อาจว่าง หรือไม่มีแถวที่ is_active = 1)';
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
    // Step 3: อัปเดตผลลัพธ์ลง DB
    // ========================================
    $top1       = $pyResult['top3'][0];
    $branchId   = $top1['id']    ?? null;
    $branchName = $top1['name']  ?? null;
    $score      = $top1['score'] ?? null;

    $stmt2 = $conn->prepare("
        UPDATE quiz_results 
        SET branch_id = ?, branch_name = ?, score = ?
        WHERE id = ?
    ");
    if (!$stmt2) {
        throw new Exception('Prepare (UPDATE quiz_results) ล้มเหลว: ' . $conn->error);
    }
    $stmt2->bind_param('isdi', $branchId, $branchName, $score, $resultId);
    if (!$stmt2->execute()) {
        throw new Exception('อัปเดตผลลัพธ์ไม่สำเร็จ: ' . $stmt2->error);
    }
    $stmt2->close();
    $conn->close();

    // ========================================
    // Step 4: ส่ง result_id กลับให้ quiz.html
    // ========================================
    echo json_encode([
        'success'   => true,
        'result_id' => $resultId
    ]);

} catch (Throwable $e) {
    // ดักทุก error/exception ที่หลุดมา -> ยังไงก็ได้ JSON กลับไปแน่นอน
    error_log('save_quiz.php error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error'   => 'เกิดข้อผิดพลาดที่ server: ' . $e->getMessage()
    ]);
}
