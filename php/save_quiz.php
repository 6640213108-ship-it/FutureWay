<?php
// ========================================
// FutureWay - save_quiz.php
// รับคำตอบแบบทดสอบจาก quiz.html -> ให้ Python คำนวณ MBTI + สาขาแนะนำ
// -> บันทึกผลทั้งหมดลงฐานข้อมูลในธุรกรรมเดียว -> ตอบ result_id
//
// รับ POST + body เป็น JSON:
//   โหมดกรอกเกรด:   {"grades": {"math":3.5, ...}, "answers": [{"question_id":1,"selected":"A"}, ...]}
//   โหมดไม่ทราบเกรด: {"interests": [id, ...],       "answers": [...]}
// ========================================

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/decision_tree_runner.php';

$userId = requireLoginJson();
$input  = requireJsonPost();

// ---- ตรวจ input ----
$answers = $input['answers'] ?? null;
if (!is_array($answers) || !$answers) {
    jsonFail(400, 'ไม่พบคำตอบแบบทดสอบ');
}
foreach ($answers as $a) {
    if (!is_array($a) || !isset($a['question_id'], $a['selected'])) {
        jsonFail(400, 'รูปแบบคำตอบไม่ถูกต้อง');
    }
}

$grades    = null;
$interests = null;
if (isset($input['grades']) && is_array($input['grades'])) {
    $inputMode = 'grade';
    $grades    = [];
    foreach (['math', 'sci', 'eng', 'thai', 'social', 'art'] as $subject) {
        if (!isset($input['grades'][$subject]) || !is_numeric($input['grades'][$subject])) {
            jsonFail(400, "ขาดเกรดวิชา: $subject");
        }
        $value = (float)$input['grades'][$subject];
        if ($value < 0 || $value > 4) {
            jsonFail(400, 'เกรดต้องอยู่ระหว่าง 0.00 ถึง 4.00');
        }
        $grades[$subject] = $value;
    }
} elseif (isset($input['interests']) && is_array($input['interests'])) {
    $inputMode = 'interest';
    $interests = array_values(array_map('intval', $input['interests']));
    if (!$interests) {
        jsonFail(400, 'กรุณาเลือกความชอบ/งานอดิเรกอย่างน้อย 1 ข้อ');
    }
} else {
    jsonFail(400, 'ต้องส่ง grades หรือ interests อย่างใดอย่างหนึ่ง');
}

// ---- Step 1: ให้ Python คำนวณ MBTI จากคำตอบ + จัดอันดับสาขา ----
try {
    $pyResult = runDecisionTree($inputMode === 'grade'
        ? ['grades' => $grades, 'answers' => $answers]
        : ['interests' => $interests, 'answers' => $answers]);
} catch (RuntimeException $e) {
    jsonFail(500, $e->getMessage());
}

$mbti = $pyResult['mbti'] ?? null;
if (!is_string($mbti) || strlen($mbti) !== 4) {
    error_log('save_quiz: python returned invalid mbti: ' . json_encode($pyResult['mbti'] ?? null));
    jsonFail(500, 'ไม่สามารถคำนวณผล MBTI จากคำตอบที่ส่งมาได้');
}
if (empty($pyResult['top_categories'][0]['branches'])) {
    error_log('save_quiz: python returned no branches (table branches empty?)');
    jsonFail(500, 'ไม่พบข้อมูลสาขาในระบบ');
}

// ---- Step 2: บันทึกผลลง DB ทั้ง 3 ตารางในธุรกรรมเดียว ----
$conn = connectOrFailJson();

$top1       = $pyResult['top_categories'][0]['branches'][0];
$branchId   = $top1['id']    ?? null;
$branchName = $top1['name']  ?? null;
$score      = $top1['score'] ?? null;

$avgGrade     = $pyResult['avg_grade'] ?? null;
$mbtiDetail   = isset($pyResult['mbti_detail'])
    ? json_encode($pyResult['mbti_detail'], JSON_UNESCAPED_UNICODE) : null;
$riasecScores = isset($pyResult['riasec_detail']['scores'])
    ? json_encode($pyResult['riasec_detail']['scores'], JSON_UNESCAPED_UNICODE) : null;
$answersTotal = count($answers);

// PHP 8+ ห้าม reference string offset ใน bind_param -> แยกเป็นตัวแปรก่อน
$mbtiEI = $mbti[0];
$mbtiSN = $mbti[1];
$mbtiTF = $mbti[2];
$mbtiJP = $mbti[3];

// โหมดไม่ทราบเกรด: คอลัมน์ grade_* เป็น NULL
$gMath   = $grades['math']   ?? null;
$gSci    = $grades['sci']    ?? null;
$gEng    = $grades['eng']    ?? null;
$gThai   = $grades['thai']   ?? null;
$gSocial = $grades['social'] ?? null;
$gArt    = $grades['art']    ?? null;

$conn->begin_transaction();
try {
    $stmt = $conn->prepare("
        INSERT INTO quiz_results
            (user_id, input_mode, grade_math, grade_sci, grade_eng, grade_thai, grade_social, grade_art,
             avg_grade, mbti_type, mbti_e_i, mbti_s_n, mbti_t_f, mbti_j_p,
             mbti_detail, riasec_scores, answers_total, branch_id, branch_name, score)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    if (!$stmt) {
        throw new Exception('prepare quiz_results: ' . $conn->error);
    }
    $stmt->bind_param(
        'isdddddddsssssssiisd',
        $userId, $inputMode,
        $gMath, $gSci, $gEng, $gThai, $gSocial, $gArt,
        $avgGrade, $mbti, $mbtiEI, $mbtiSN, $mbtiTF, $mbtiJP,
        $mbtiDetail, $riasecScores, $answersTotal,
        $branchId, $branchName, $score
    );
    if (!$stmt->execute()) {
        throw new Exception('insert quiz_results: ' . $stmt->error);
    }
    $resultId = $stmt->insert_id;
    $stmt->close();

    // snapshot สาขาที่แนะนำของรอบนี้ (สูงสุด 3 คณะ x 5 สาขา) เพื่อให้ประวัติเก่า
    // ไม่เปลี่ยนตามข้อมูลตาราง branches ที่แก้ทีหลัง
    $stmtB = $conn->prepare("
        INSERT INTO quiz_result_branches
            (result_id, category_rank, rank_no, branch_id, branch_name, faculty, description, score, note)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    if (!$stmtB) {
        throw new Exception('prepare quiz_result_branches: ' . $conn->error);
    }
    foreach ($pyResult['top_categories'] as $ci => $category) {
        $categoryRank = $ci + 1;
        foreach ($category['branches'] as $bi => $b) {
            $rankNo = $bi + 1;
            $bId    = $b['id']          ?? null;
            $bName  = $b['name']        ?? '';
            $bFac   = $b['faculty']     ?? ($category['faculty'] ?? null);
            $bDesc  = $b['description'] ?? null;
            $bScore = $b['score']       ?? 0;
            $bNote  = $b['note']        ?? null;

            $stmtB->bind_param('iiiisssds', $resultId, $categoryRank, $rankNo, $bId, $bName, $bFac, $bDesc, $bScore, $bNote);
            if (!$stmtB->execute()) {
                throw new Exception('insert quiz_result_branches: ' . $stmtB->error);
            }
        }
    }
    $stmtB->close();

    // คำตอบรายข้อ พร้อมมิติ (EI/SN/TF/JP) และตัวอักษรที่ได้จากตัวเลือกนั้น
    $qMeta = [];
    if ($qRes = $conn->query('SELECT id, question_no, category, option_a_trait, option_b_trait FROM mbti_questions')) {
        while ($qRow = $qRes->fetch_assoc()) {
            $qMeta[(string)$qRow['id']] = $qRow;
        }
        $qRes->free();
    }

    $stmtA = $conn->prepare("
        INSERT INTO quiz_answers (result_id, question_id, question_no, category, selected, trait)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    if (!$stmtA) {
        throw new Exception('prepare quiz_answers: ' . $conn->error);
    }
    foreach ($answers as $a) {
        $qId  = (int)$a['question_id'];
        $sel  = strtoupper(substr(trim((string)$a['selected']), 0, 1));
        $meta = $qMeta[(string)$qId] ?? null;

        $qNo   = $meta ? (int)$meta['question_no'] : null;
        $cat   = $meta ? strtoupper(trim((string)$meta['category'])) : null;
        $trait = null;
        if ($meta) {
            $rawTrait = ($sel === 'A') ? $meta['option_a_trait'] : $meta['option_b_trait'];
            $trait    = strtoupper(substr(trim((string)$rawTrait), 0, 1)) ?: null;
        }

        $stmtA->bind_param('iiisss', $resultId, $qId, $qNo, $cat, $sel, $trait);
        if (!$stmtA->execute()) {
            throw new Exception('insert quiz_answers: ' . $stmtA->error);
        }
    }
    $stmtA->close();

    $conn->commit();
} catch (Throwable $e) {
    $conn->rollback();
    $conn->close();
    jsonServerError('save_quiz', $e->getMessage(), 'บันทึกผลไม่สำเร็จ กรุณาลองใหม่');
}

$conn->close();

jsonOk([
    'result_id' => $resultId,
    'mbti'      => $mbti,
]);
