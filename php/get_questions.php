<?php
// ========================================
// FutureWay - get_questions.php
// ดึงคำถาม MBTI จากตาราง mbti_questions
// ========================================

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/db_config.php';

try {
    $conn = getDbConnection();

    $sql = "SELECT id, category, question_no,
                   question_text, option_a_text, option_a_trait,
                   option_b_text, option_b_trait
            FROM mbti_questions
            ORDER BY category, question_no ASC";

    $result = $conn->query($sql);
    if (!$result) {
        echo json_encode(['success' => false, 'error' => 'Query error: ' . $conn->error]);
        exit;
    }

    $questions = [];
    while ($row = $result->fetch_assoc()) {
        $questions[] = $row;
    }
    $conn->close();

    if (empty($questions)) {
        echo json_encode(['success' => false, 'error' => 'ไม่พบคำถามในระบบ']);
        exit;
    }

    echo json_encode(['success' => true, 'questions' => $questions]);

} catch (Throwable $e) {
    error_log('get_questions.php error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'เกิดข้อผิดพลาดที่ server: ' . $e->getMessage()]);
}
