<?php
// ========================================
// FutureWay - get_riasec_questions.php
// ดึงคำถามความสนใจ/งานอดิเรก (RIASEC) จากตาราง riasec_questions
// ใช้เมื่อผู้ใช้เลือกโหมด "ไม่ทราบเกรด" ใน quiz.html
// ========================================

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/db_config.php';

try {
    $conn = getDbConnection();

    $sql = "SELECT id, letter, question_no, text
            FROM riasec_questions
            ORDER BY FIELD(letter, 'R', 'I', 'A', 'S', 'E', 'C'), question_no ASC, id ASC";

    $result = $conn->query($sql);
    if (!$result) {
        echo json_encode(['success' => false, 'error' => 'Query error: ' . $conn->error]);
        exit;
    }

    $questions = [];
    while ($row = $result->fetch_assoc()) {
        $row['id']          = (int)$row['id'];
        $row['question_no'] = (int)$row['question_no'];
        $row['letter']      = strtoupper(trim($row['letter']));
        $questions[] = $row;
    }
    $conn->close();

    if (empty($questions)) {
        echo json_encode(['success' => false, 'error' => 'ไม่พบคำถามในระบบ']);
        exit;
    }

    echo json_encode(['success' => true, 'questions' => $questions], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    error_log('get_riasec_questions.php error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'เกิดข้อผิดพลาดที่ server: ' . $e->getMessage()]);
}
