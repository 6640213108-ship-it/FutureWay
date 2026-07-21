<?php
// ========================================
// FutureWay - get_questions.php
// ดึงคำถาม MBTI จากตาราง mbti_questions
// ========================================

session_start();
header('Content-Type: application/json; charset=utf-8');
mysqli_report(MYSQLI_REPORT_OFF);

try {
    $conn = new mysqli('mysql.railway.internal', 'root', 'OLdaGruletpcPRSKSZkUOUrKaUWmDjri', 'railway', 3306);
    if ($conn->connect_error) {
        echo json_encode(['success' => false, 'error' => 'DB: ' . $conn->connect_error]);
        exit;
    }
    $conn->set_charset('utf8mb4');

    $sql = "SELECT id, question_no, dimension, dimension_title,
                   question_text, option_a_text, option_a_value,
                   option_b_text, option_b_value
            FROM mbti_questions
            ORDER BY question_no ASC";

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