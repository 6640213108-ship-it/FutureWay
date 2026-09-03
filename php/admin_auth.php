<?php
// ========================================
// FutureWay - admin_auth.php
// ตรวจสิทธิ์ผู้ดูแลระบบ
//
// รายชื่อแอดมินมาจาก environment variable:
//     ADMIN_USERS = alice,bob      (username คั่นด้วยลูกน้ำ)
//
// ใช้ env แทนคอลัมน์ is_admin ในตาราง users เพราะเพิ่ม/ถอนสิทธิ์ได้จากหน้า
// Variables ของ Railway ทันทีโดยไม่ต้องรัน migration ถ้าภายหลังอยากย้ายไป
// เก็บใน DB ก็แก้แค่ isAdmin() ตัวเดียว
//
// ต้อง require bootstrap.php มาก่อน (session + helper ตอบ JSON)
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
 * ผู้ใช้ที่ล็อกอินอยู่ตอนนี้เป็นแอดมินหรือไม่
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
 * ด่านตรวจสำหรับ endpoint ของแอดมิน — ไม่ผ่านคือจบเลย
 * แยก 401 (ยังไม่ล็อกอิน) กับ 403 (ล็อกอินแล้วแต่ไม่ใช่แอดมิน) เพื่อให้หน้าเว็บ
 * พาไปหน้า login หรือขึ้นข้อความ "ไม่มีสิทธิ์" ได้ถูกกรณี
 */
function requireAdminJson(): void {
    if (currentUserId() <= 0 && empty($_SESSION['username'])) {
        jsonFail(401, 'กรุณาเข้าสู่ระบบก่อน');
    }

    if (!isAdmin()) {
        // บอกแค่ username ของตัวเอง ไม่เผยรายชื่อแอดมินคนอื่น
        $me  = $_SESSION['username'] ?? '';
        $msg = getAdminUsernames()
            ? "บัญชี \"$me\" ไม่ได้อยู่ในรายชื่อผู้ดูแลระบบ"
            : 'ระบบยังไม่ได้กำหนดผู้ดูแลระบบ (ADMIN_USERS)';
        jsonFail(403, $msg);
    }
}
