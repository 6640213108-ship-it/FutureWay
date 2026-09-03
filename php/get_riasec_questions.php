<?php
// ========================================
// FutureWay - get_riasec_questions.php
// ดึงคำถามความสนใจ/งานอดิเรก (RIASEC) จากตาราง riasec_questions
// ใช้เมื่อผู้ใช้เลือกโหมด "ไม่ทราบเกรด" ใน quiz.html
// ========================================

require_once __DIR__ . '/bootstrap.php';

$conn = connectOrFailJson();

$result = $conn->query("
    SELECT id, letter, question_no, text
    FROM riasec_questions
    ORDER BY FIELD(letter, 'R', 'I', 'A', 'S', 'E', 'C'), question_no ASC, id ASC
");
if (!$result) {
    jsonServerError('get_riasec_questions', $conn->error);
}

$questions = [];
while ($row = $result->fetch_assoc()) {
    $row['id']          = (int)$row['id'];
    $row['question_no'] = (int)$row['question_no'];
    $row['letter']      = strtoupper(trim($row['letter']));
    $questions[]        = $row;
}
$result->free();
$conn->close();

if (!$questions) {
    jsonFail(404, 'ไม่พบคำถามในระบบ');
}

jsonOk(['questions' => $questions]);
