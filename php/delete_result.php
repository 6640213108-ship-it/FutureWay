<?php
// ========================================
// FutureWay - delete_result.php
// ลบผลการทำแบบทดสอบ 1 รอบ (สำหรับผู้ดูแลระบบเท่านั้น)
//
// รับเฉพาะ POST + body เป็น JSON: {"result_id": 123}
//
// ทำไมต้องเป็น POST + JSON:
//   ถ้าเปิดให้ลบผ่าน GET ใครก็ตามที่หลอกให้แอดมินคลิกลิงก์ (หรือแค่ฝังรูป
//   <img src="...delete_result.php?id=1">) จะสั่งลบข้อมูลได้ทันที
//   ส่วนการบังคับ Content-Type: application/json กันฟอร์มจากเว็บอื่นยิงข้ามโดเมนมา
//   เพราะฟอร์ม HTML ธรรมดาตั้ง header นี้ไม่ได้ ต้องผ่าน CORS preflight ก่อน
//
// ตาราง quiz_result_branches / quiz_answers ผูก FK ON DELETE CASCADE ไว้
// ลบแถวใน quiz_results แถวเดียว ข้อมูลลูกจึงหายตามเองทั้งหมด
// ========================================

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/admin_config.php';
requireAdminJson();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error'   => 'ต้องเรียกด้วยวิธี POST เท่านั้น'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (stripos($contentType, 'application/json') === false) {
    http_response_code(415);
    echo json_encode([
        'success' => false,
        'error'   => 'ต้องส่งข้อมูลเป็น JSON'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once __DIR__ . '/db_config.php';

try {
    $input    = json_decode(file_get_contents('php://input'), true);
    $resultId = (int)($input['result_id'] ?? 0);

    if ($resultId <= 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error'   => 'ไม่พบ result_id ที่ต้องการลบ'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $conn = getDbConnection();

    // ดึงข้อมูลก่อนลบ เพื่อเอาไปเขียน log และส่งกลับให้หน้าเว็บยืนยันว่าลบอันไหนไป
    $stmtS = $conn->prepare("
        SELECT qr.id, qr.mbti_type, qr.created_at, u.username
        FROM quiz_results qr
        JOIN users u ON u.id = qr.user_id
        WHERE qr.id = ?
    ");
    if (!$stmtS) {
        throw new Exception('Prepare (SELECT) ล้มเหลว: ' . $conn->error);
    }
    $stmtS->bind_param('i', $resultId);
    $stmtS->execute();
    $target = $stmtS->get_result()->fetch_assoc();
    $stmtS->close();

    if (!$target) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error'   => 'ไม่พบผลลัพธ์ที่ต้องการลบ (อาจถูกลบไปแล้ว)'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM quiz_results WHERE id = ?");
    if (!$stmt) {
        throw new Exception('Prepare (DELETE) ล้มเหลว: ' . $conn->error);
    }
    $stmt->bind_param('i', $resultId);

    if (!$stmt->execute()) {
        throw new Exception('ลบข้อมูลไม่สำเร็จ: ' . $stmt->error);
    }

    $deleted = $stmt->affected_rows;
    $stmt->close();
    $conn->close();

    // เก็บร่องรอยไว้ใน log ของ Railway ว่าแอดมินคนไหนลบผลของใครไป
    error_log(sprintf(
        'delete_result.php: admin "%s" deleted result_id=%d (user=%s, mbti=%s, created_at=%s)',
        $_SESSION['username'] ?? '?',
        $resultId,
        $target['username'],
        $target['mbti_type'],
        $target['created_at']
    ));

    echo json_encode([
        'success'   => true,
        'deleted'   => $deleted,
        'result_id' => $resultId,
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    error_log('delete_result.php error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error'   => 'เกิดข้อผิดพลาดที่ server: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
