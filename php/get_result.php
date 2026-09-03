<?php
// ========================================
// FutureWay - get_result.php
// ดึงผลลัพธ์ 1 รอบของผู้ใช้ที่ล็อกอินอยู่ ส่งให้ result.html
//
// query string: id = result_id
// ========================================

require_once __DIR__ . '/bootstrap.php';

$userId   = requireLoginJson();
$resultId = (int)($_GET['id'] ?? 0);
if ($resultId <= 0) {
    jsonFail(400, 'ไม่พบ result_id');
}

$conn = connectOrFailJson();

$stmt = $conn->prepare("
    SELECT qr.*, b.faculty, b.description
    FROM quiz_results qr
    LEFT JOIN branches b ON qr.branch_id = b.id
    WHERE qr.id = ? AND qr.user_id = ?
");
if (!$stmt) {
    jsonServerError('get_result', $conn->error);
}
$stmt->bind_param('ii', $resultId, $userId);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$result) {
    jsonFail(404, 'ไม่พบข้อมูลผลลัพธ์');
}

// โหมดไม่ทราบเกรด (input_mode = 'interest'): grade_* เป็น NULL ทั้งชุด
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
$mbti         = $result['mbti_type'];

// ---- สาขาทั้งหมดที่เข้ากับ MBTI นี้ จัดกลุ่มตามคณะ (ดึงสดจากตาราง branches) ----
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

// ---- สาขาที่แนะนำ (จัดเป็นหมวดหมู่/คณะ) จาก snapshot ตอนทำแบบทดสอบรอบนั้น ----
// ผลที่เห็นย้อนหลังตรงกับตอนทำจริงเสมอ ถึงจะแก้ข้อมูล branches ทีหลัง
$categoriesById = [];
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

// ---- Fallback: ผลเก่าที่บันทึกก่อนมี snapshot -> คำนวณใหม่ด้วย Python ----
if (!$topCategories) {
    require_once __DIR__ . '/decision_tree_runner.php';
    try {
        $pyResult      = runDecisionTree(['grades' => $grades, 'mbti' => $mbti]);
        $topCategories = $pyResult['top_categories'] ?? [];
    } catch (RuntimeException $e) {
        // แสดงส่วนอื่นของผลลัพธ์ต่อได้ แค่ไม่มีรายการสาขาแนะนำ
        $topCategories = [];
    }
}

// ---- คำตอบรายข้อของรอบนี้ ----
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
if (isset($result['avg_grade'])) {
    $avgGrade = (float)$result['avg_grade'];
} elseif ($grades !== null) {
    $avgGrade = round(array_sum($grades) / count($grades), 2);
} else {
    $avgGrade = null;
}

jsonOk([
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
]);
