<?php
// ========================================
// FutureWay - get_result.php
// ดึงผลลัพธ์จาก DB ส่งให้ result.html
// ========================================

session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'กรุณาเข้าสู่ระบบก่อน']);
    exit;
}

$resultId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$resultId) {
    echo json_encode(['success' => false, 'error' => 'ไม่พบ result_id']);
    exit;
}

require_once __DIR__ . '/db_config.php';

try {
    $conn = getDbConnection();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'DB connection failed: ' . $e->getMessage()]);
    exit;
}

// ดึงข้อมูล quiz_result
$stmt = $conn->prepare("
    SELECT qr.*, b.faculty, b.description
    FROM quiz_results qr
    LEFT JOIN branches b ON qr.branch_id = b.id
    WHERE qr.id = ? AND qr.user_id = ?
");
$stmt->bind_param('ii', $resultId, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$result) {
    echo json_encode(['success' => false, 'error' => 'ไม่พบข้อมูล']);
    exit;
}

// โหมดไม่ทราบเกรด (input_mode = 'interest'): grade_* เป็น NULL ทั้งชุด ไม่ต้องสร้าง $grades
$inputMode = $result['input_mode'] ?? 'grade';
$grades    = null;
if ($inputMode !== 'interest' && $result['grade_math'] !== null) {
    $grades = [
        'math'   => $result['grade_math'],
        'sci'    => $result['grade_sci'],
        'eng'    => $result['grade_eng'],
        'thai'   => $result['grade_thai'],
        'social' => $result['grade_social'],
        'art'    => $result['grade_art'],
    ];
}
$riasecScores = isset($result['riasec_scores']) ? json_decode($result['riasec_scores'], true) : null;
$mbti = $result['mbti_type'];

// ========================================
// สาขาทั้งหมดที่เข้ากับ MBTI นี้ (ไม่จำกัดแค่ top 3 คณะ) จัดกลุ่มตามคณะ
// ใช้แสดงในหน้าผลลัพธ์แบบ "การ์ดข้อมูล MBTI" — ดึงสดจากตาราง branches ปัจจุบัน
// เสมอ (ไม่ใช่ snapshot) เพราะเป็นข้อมูลอ้างอิงทั่วไปของ MBTI แบบนี้ ไม่ผูกกับ
// รอบทำแบบทดสอบใดรอบหนึ่งโดยเฉพาะ
// ========================================
$mbtiBranchesByFaculty = [];
$mbtiBranchesTotal     = 0;
if ($resAll = $conn->query("SELECT name, faculty, mbti_match FROM branches WHERE is_active = 1 ORDER BY faculty, name")) {
    while ($row = $resAll->fetch_assoc()) {
        $match = json_decode($row['mbti_match'], true);
        if (!is_array($match) || !in_array($mbti, $match, true)) {
            continue;
        }
        $fac = $row['faculty'] ?: 'ไม่ระบุคณะ';
        $mbtiBranchesByFaculty[$fac][] = $row['name'];
        $mbtiBranchesTotal++;
    }
    $resAll->free();
}

// ========================================
// อ่านสาขาที่แนะนำ (จัดเป็นหมวดหมู่/คณะ) จาก snapshot ที่บันทึกไว้ตอนทำแบบทดสอบรอบนั้น
// -> ผลที่ผู้ใช้เห็นย้อนหลังตรงกับตอนทำจริงเสมอ ถึงจะแก้ข้อมูล branches ทีหลัง
//    และไม่ต้องรัน python ซ้ำทุกครั้งที่เปิดหน้านี้
// ========================================
$categoriesById = [];   // category_rank => ['faculty' => ..., 'best_score' => ..., 'branches' => [...]]
$stmtB = $conn->prepare("
    SELECT category_rank, rank_no, branch_id, branch_name, faculty, description, score, note
    FROM quiz_result_branches
    WHERE result_id = ?
    ORDER BY category_rank ASC, rank_no ASC
");
if ($stmtB) {
    $stmtB->bind_param('i', $resultId);
    $stmtB->execute();
    $resB = $stmtB->get_result();
    while ($row = $resB->fetch_assoc()) {
        $catRank = (int)($row['category_rank'] ?? 1);

        if (!isset($categoriesById[$catRank])) {
            $categoriesById[$catRank] = [
                'faculty'    => $row['faculty'],
                'best_score' => (float)$row['score'],
                'branches'   => [],
            ];
        }

        $categoriesById[$catRank]['branches'][] = [
            'id'          => $row['branch_id'] !== null ? (int)$row['branch_id'] : null,
            'name'        => $row['branch_name'],
            'faculty'     => $row['faculty'],
            'description' => $row['description'],
            'score'       => (float)$row['score'],
            'note'        => $row['note'],
        ];
    }
    $stmtB->close();
}
ksort($categoriesById);
$topCategories = array_values($categoriesById);

// ========================================
// Fallback: ผลลัพธ์เก่าที่บันทึกไว้ก่อน migration 002/009 ยังไม่มี snapshot
// (หรือยังเป็นรูปแบบ flat แบบเก่า) จึงต้องคำนวณใหม่ด้วย python เหมือนเดิม
// ========================================
if (!$topCategories) {
    $pythonInput = json_encode(['grades' => $grades, 'mbti' => $mbti]);
    require_once __DIR__ . '/python_config.php';
    try {
        $pythonPath = getPythonPath();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
    $scriptPath  = dirname(__DIR__) . '/decision_tree.py';

    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $process = proc_open('"' . $pythonPath . '" "' . $scriptPath . '"', $descriptors, $pipes);
    fwrite($pipes[0], $pythonInput);
    fclose($pipes[0]);
    $pythonOutput = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    proc_close($process);

    $pyResult      = json_decode($pythonOutput, true);
    $topCategories = $pyResult['top_categories'] ?? [];
}

// ดึงคำตอบรายข้อของรอบนี้ (ถ้ามีบันทึกไว้)
$answers = [];
$stmtA   = $conn->prepare("
    SELECT question_id, question_no, category, selected, trait
    FROM quiz_answers
    WHERE result_id = ?
    ORDER BY id ASC
");
if ($stmtA) {
    $stmtA->bind_param('i', $resultId);
    $stmtA->execute();
    $resA = $stmtA->get_result();
    while ($row = $resA->fetch_assoc()) {
        $answers[] = $row;
    }
    $stmtA->close();
}

$conn->close();

// avg_grade ใช้ค่าที่บันทึกไว้ก่อน ถ้าเป็นแถวเก่าที่ยังไม่มีค่อยคำนวณเอง
// (โหมด interest ไม่มีเกรดเลย -> ไม่มี avg_grade ให้คำนวณ เป็น null เสมอ)
if (isset($result['avg_grade']) && $result['avg_grade'] !== null) {
    $avgGrade = (float)$result['avg_grade'];
} elseif ($grades !== null) {
    $avgGrade = round(array_sum($grades) / count($grades), 2);
} else {
    $avgGrade = null;
}

echo json_encode([
    'success'             => true,
    'result_id'           => $resultId,
    'mbti'                => $mbti,
    'mbti_detail'         => isset($result['mbti_detail']) ? json_decode($result['mbti_detail'], true) : null,
    'input_mode'          => $inputMode,
    'avg_grade'           => $avgGrade,
    'grades'              => $grades,
    'riasec_scores'       => $riasecScores,
    'top_categories'      => $topCategories,
    'mbti_branches'       => $mbtiBranchesByFaculty,
    'mbti_branches_total' => $mbtiBranchesTotal,
    'answers'             => $answers,
    'created_at'          => $result['created_at'],
], JSON_UNESCAPED_UNICODE);
?>
