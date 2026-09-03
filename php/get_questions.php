<?php
// ========================================
// FutureWay - get_questions.php
// ดึงคำถาม MBTI จากตาราง mbti_questions (ใช้โดย quiz.html)
// ========================================

require_once __DIR__ . '/bootstrap.php';

$conn = connectOrFailJson();

// FIELD() บังคับลำดับหมวดตามเอกสารต้นทาง EI -> SN -> TF -> JP
// (ORDER BY category เฉย ๆ จะเรียงตามตัวอักษร ได้ JP มาก่อน SN/TF)
$result = $conn->query("
    SELECT id, category, question_no,
           question_text, option_a_text, option_a_trait,
           option_b_text, option_b_trait
    FROM mbti_questions
    ORDER BY FIELD(category, 'EI', 'SN', 'TF', 'JP'), question_no ASC, id ASC
");
if (!$result) {
    jsonServerError('get_questions', $conn->error);
}

// query() คืนทุกคอลัมน์เป็น string -> cast id/question_no เป็น int
// ไม่งั้น JS จะส่ง question_id เป็น "1" กลับมา แล้ว Python จับคู่ไม่ได้
$questions = [];
while ($row = $result->fetch_assoc()) {
    $row['id']          = (int)$row['id'];
    $row['question_no'] = (int)$row['question_no'];
    $row['category']    = strtoupper(trim($row['category']));
    $questions[]        = $row;
}
$result->free();
$conn->close();

if (!$questions) {
    jsonFail(404, 'ไม่พบคำถามในระบบ');
}

jsonOk(['questions' => $questions]);
