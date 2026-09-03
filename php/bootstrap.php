<?php
// ========================================
// FutureWay - bootstrap.php
// จุดเริ่มต้นร่วมของทุก endpoint ใน php/ — require ไฟล์นี้เป็นบรรทัดแรกเสมอ
//
// ทำให้ครบในที่เดียว:
//   1) ตั้งค่า error: ไม่แสดง error ให้ผู้ใช้เห็น (log แทน) — ป้องกันข้อมูลภายในรั่ว
//      และป้องกัน warning หลุดมาปนกับ JSON/ไฟล์ดาวน์โหลด
//   2) time zone ฝั่ง PHP เป็นเวลาไทย
//   3) เริ่ม session
//   4) ตั้ง Content-Type เป็น JSON (endpoint ที่ส่งไฟล์จะ header_remove เอง)
//   5) โหลด helper ตอบ JSON + การเชื่อมต่อฐานข้อมูล
// ========================================

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');

date_default_timezone_set('Asia/Bangkok');

require_once __DIR__ . '/db_config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

/**
 * ตอบ error เป็น JSON แล้วจบการทำงานทันที
 */
function jsonFail(int $status, string $message): void {
    http_response_code($status);
    echo json_encode(['success' => false, 'error' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * ตอบสำเร็จเป็น JSON แล้วจบการทำงาน
 */
function jsonOk(array $data = []): void {
    echo json_encode(['success' => true] + $data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * บันทึกรายละเอียด error ลง log แล้วตอบผู้ใช้ด้วยข้อความกลาง ๆ
 * รายละเอียดจริง (ข้อความจาก MySQL ฯลฯ) ไม่ควรถึงมือผู้ใช้
 */
function jsonServerError(string $context, string $detail, string $userMessage = 'เกิดข้อผิดพลาดที่เซิร์ฟเวอร์ กรุณาลองใหม่'): void {
    error_log($context . ': ' . $detail);
    jsonFail(500, $userMessage);
}

/**
 * บังคับว่าต้องเรียกด้วย POST + body เป็น JSON แล้วคืนค่าที่ decode แล้ว
 *
 * ที่ต้องบังคับ Content-Type: application/json เพราะฟอร์ม HTML จากเว็บอื่น
 * ตั้ง header นี้ไม่ได้ (ต้องผ่าน CORS preflight ก่อน) จึงกันการยิงข้ามโดเมน
 * มาแก้ข้อมูลของคนที่ล็อกอินค้างไว้ได้
 */
function requireJsonPost(): array {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonFail(405, 'ต้องเรียกด้วยวิธี POST เท่านั้น');
    }
    // บาง server ไม่ตั้ง $_SERVER['CONTENT_TYPE'] ให้ มีแต่ HTTP_CONTENT_TYPE
    $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
    if (stripos($contentType, 'application/json') === false) {
        jsonFail(415, 'ต้องส่งข้อมูลเป็น JSON');
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        jsonFail(400, 'รูปแบบข้อมูลที่ส่งมาไม่ถูกต้อง');
    }
    return $input;
}

/**
 * เปิด connection พร้อมตอบ JSON ให้เองถ้าต่อ DB ไม่ได้
 */
function connectOrFailJson(): mysqli {
    try {
        return getDbConnection();
    } catch (Exception $e) {
        jsonServerError('db', $e->getMessage(), 'เชื่อมต่อฐานข้อมูลไม่ได้');
    }
}

/**
 * user_id ของคนที่ล็อกอินอยู่ (0 = ยังไม่ได้ล็อกอิน)
 */
function currentUserId(): int {
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
}

/**
 * บังคับว่าต้องล็อกอินก่อน ไม่งั้นตอบ 401 แล้วจบ — คืน user_id
 */
function requireLoginJson(): int {
    $userId = currentUserId();
    if ($userId <= 0) {
        jsonFail(401, 'กรุณาเข้าสู่ระบบก่อน');
    }
    return $userId;
}
