<?php
// ========================================
// FutureWay - login.php
// เข้าสู่ระบบด้วยชื่อผู้ใช้ (เบอร์มือถือ) + รหัสผ่าน
//
// รับ POST + body เป็น JSON: {"username":"...", "password":"..."}
// ตอบ: {"success":true, "username":"..."} หรือ 401 พร้อมข้อความ
// ========================================

require_once __DIR__ . '/bootstrap.php';

$input    = requireJsonPost();
$username = trim((string)($input['username'] ?? ''));
$password = (string)($input['password'] ?? '');

if ($username === '' || $password === '') {
    jsonFail(400, 'กรุณากรอกชื่อผู้ใช้และรหัสผ่าน');
}

$conn = connectOrFailJson();

$stmt = $conn->prepare('SELECT id, username, password FROM users WHERE username = ?');
if (!$stmt) {
    jsonServerError('login', $conn->error);
}
$stmt->bind_param('s', $username);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();

// ใช้ข้อความเดียวกันทั้งกรณีไม่พบผู้ใช้และรหัสผิด — ไม่ให้เดาได้ว่ามีบัญชีนี้อยู่จริง
if (!$row || !password_verify($password, $row['password'])) {
    usleep(300000);   // หน่วงเล็กน้อยกันไล่เดารหัสรัว ๆ
    jsonFail(401, 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง');
}

session_regenerate_id(true);
$_SESSION['user_id']  = (int)$row['id'];
$_SESSION['username'] = $row['username'];

jsonOk(['username' => $row['username']]);
