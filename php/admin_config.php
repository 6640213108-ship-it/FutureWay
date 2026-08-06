<?php
// ========================================
// FutureWay - admin_config.php
// กำหนดว่าใครเป็นผู้ดูแลระบบ
//
// ตั้ง environment variable บน Railway:
//     ADMIN_USERS = topza,hee      (username คั่นด้วยลูกน้ำ ไม่ต้องเว้นวรรคก็ได้)
//
// ใช้ env แทนการเพิ่มคอลัมน์ is_admin ในตาราง users เพราะไม่ต้องรัน migration
// เพิ่ม/ถอนสิทธิ์แอดมินทำได้จากหน้า Variables ของ Railway ทันที ไม่ต้อง deploy ใหม่
// ถ้าภายหลังอยากย้ายไปเก็บใน DB ก็แก้แค่ฟังก์ชัน isAdmin() ตัวเดียว
// ========================================

/**
 * รายชื่อ username ที่เป็นแอดมิน
 */
function getAdminUsernames(): array {
    $raw = getenv('ADMIN_USERS');
    if (!$raw) {
        return [];
    }
    return array_values(array_filter(array_map('trim', explode(',', $raw)), fn($u) => $u !== ''));
}

/**
 * ผู้ใช้ที่ login อยู่ตอนนี้เป็นแอดมินหรือไม่
 * (ต้องเรียก session_start() มาก่อน)
 */
function isAdmin(): bool {
    if (empty($_SESSION['username'])) {
        return false;
    }
    foreach (getAdminUsernames() as $admin) {
        if (strcasecmp($admin, $_SESSION['username']) === 0) {
            return true;
        }
    }
    return false;
}

/**
 * ด่านตรวจสำหรับ endpoint ที่คืน JSON — ไม่ผ่านคือจบเลย
 * แยก 401 (ยังไม่ login) กับ 403 (login แล้วแต่ไม่ใช่แอดมิน) เพื่อให้หน้าเว็บ
 * พาไปหน้า login หรือขึ้นข้อความ "ไม่มีสิทธิ์" ได้ถูกกรณี
 */
function requireAdminJson(): void {
    if (empty($_SESSION['user_id']) && empty($_SESSION['username'])) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error'   => 'กรุณาเข้าสู่ระบบก่อน'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (!isAdmin()) {
        // แยกสองกรณีให้ชัด ไม่งั้นตอนตั้งค่าพลาดจะไล่หาสาเหตุไม่เจอ
        // (บอกแค่ username ของตัวเอง ไม่เผยรายชื่อแอดมินคนอื่น)
        $me = $_SESSION['username'] ?? '';
        $msg = getAdminUsernames()
            ? "บัญชี \"$me\" ไม่ได้อยู่ในรายชื่อผู้ดูแลระบบ (ตรวจค่า ADMIN_USERS บน Railway)"
            : "ยังไม่ได้ตั้ง environment variable ADMIN_USERS บน Railway — เพิ่มค่าเป็น \"$me\" แล้วรอ deploy ใหม่";

        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error'   => $msg
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
