<?php
// ========================================
// FutureWay - logout.php
// ออกจากระบบ — ล้าง session แล้วตอบ JSON ให้หน้าเว็บพาไปหน้า login เอง
// รับเฉพาะ POST กันการหลอกให้คลิกลิงก์แล้วหลุดออกจากระบบ
// ========================================

require_once __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonFail(405, 'ต้องเรียกด้วยวิธี POST เท่านั้น');
}

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();

jsonOk();
