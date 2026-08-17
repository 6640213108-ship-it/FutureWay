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

    // FIELD() บังคับลำดับหมวดตามเอกสารต้นทาง EI -> SN -> TF -> JP
    // (ORDER BY category เฉยๆ จะเรียงตามตัวอักษร ได้ JP มาก่อน SN/TF)
    // ตามด้วย question_no ที่ migration 007 ตั้งให้ต่อเนื่อง 1-7 ในแต่ละหมวด
    $sql = "SELECT id, category, question_no,
                   question_text, option_a_text, option_a_trait,
                   option_b_text, option_b_trait
            FROM mbti_questions
            ORDER BY FIELD(category, 'EI', 'SN', 'TF', 'JP'), question_no ASC, id ASC";

    $result = $conn->query($sql);
    if (!$result) {
        echo json_encode(['success' => false, 'error' => 'Query error: ' . $conn->error]);
        exit;
    }

    // $conn->query() คืนค่าทุกคอลัมน์เป็น string -> ต้อง cast id/question_no
    // เป็น int เอง ไม่งั้น JS จะส่ง question_id เป็น "1" (string) กลับมา
    // แล้วฝั่ง Python จับคู่กับคำถามไม่ได้ (คะแนน MBTI เป็น 0 ทุกมิติ)
    $questions = [];
    while ($row = $result->fetch_assoc()) {
        $row['id']          = (int)$row['id'];
        $row['question_no'] = (int)$row['question_no'];
        $row['category']    = strtoupper(trim($row['category']));
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