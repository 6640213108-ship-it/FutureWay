<?php
// ========================================
// FutureWay - register.php
// สมัครสมาชิกใหม่ (ใช้โดย register.html)
//
// รับ POST + body เป็น JSON:
//   {"phone":"0812345678", "firstname":"...", "lastname":"...",
//    "email":"...", "gender":"ชาย|หญิง|อื่นๆ", "password":"...", "confirm_password":"..."}
//
// เบอร์มือถือถูกเก็บในคอลัมน์ username เพื่อใช้เป็นชื่อผู้ใช้ตอนเข้าสู่ระบบ
// ========================================

require_once __DIR__ . '/bootstrap.php';

$input = requireJsonPost();

$phone     = preg_replace('/[^0-9]/', '', (string)($input['phone'] ?? ''));
$firstname = trim((string)($input['firstname'] ?? ''));
$lastname  = trim((string)($input['lastname']  ?? ''));
$email     = mb_strtolower(trim((string)($input['email'] ?? '')));
$gender    = trim((string)($input['gender']    ?? ''));
$password  = (string)($input['password']         ?? '');
$confirm   = (string)($input['confirm_password'] ?? '');

// ---- ตรวจความถูกต้อง ----
if (!preg_match('/^0[0-9]{9}$/', $phone)) {
    jsonFail(400, 'กรุณากรอกเบอร์มือถือ 10 หลัก เช่น 0812345678');
}
if ($firstname === '' || $lastname === '') {
    jsonFail(400, 'กรุณากรอกชื่อและนามสกุล');
}
if (mb_strlen($firstname) > 100 || mb_strlen($lastname) > 100) {
    jsonFail(400, 'ชื่อหรือนามสกุลยาวเกินไป (ไม่เกิน 100 ตัวอักษร)');
}
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) {
    jsonFail(400, 'รูปแบบอีเมลไม่ถูกต้อง');
}
if (!in_array($gender, ['ชาย', 'หญิง', 'อื่นๆ'], true)) {
    jsonFail(400, 'กรุณาเลือกเพศ');
}
if (mb_strlen($password) < 6) {
    jsonFail(400, 'รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร');
}
if (strlen($password) > 72) {
    // bcrypt อ่านแค่ 72 byte แรก ที่เกินจะถูกตัดทิ้งเงียบ ๆ
    jsonFail(400, 'รหัสผ่านยาวเกินไป (ไม่เกิน 72 ตัวอักษร)');
}
if ($password !== $confirm) {
    jsonFail(400, 'รหัสผ่านทั้งสองช่องไม่ตรงกัน');
}

$conn = connectOrFailJson();

// ---- ห้ามซ้ำทั้งเบอร์และอีเมล ----
$stmt = $conn->prepare('SELECT id FROM users WHERE username = ? OR email = ?');
if (!$stmt) {
    jsonServerError('register', $conn->error);
}
$stmt->bind_param('ss', $phone, $email);
$stmt->execute();
$taken = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($taken) {
    jsonFail(409, 'เบอร์มือถือหรืออีเมลนี้มีผู้ใช้งานแล้ว');
}

// ---- บันทึก ----
$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $conn->prepare(
    'INSERT INTO users (username, firstname, lastname, gender, email, password) VALUES (?, ?, ?, ?, ?, ?)'
);
if (!$stmt) {
    jsonServerError('register', $conn->error);
}
$stmt->bind_param('ssssss', $phone, $firstname, $lastname, $gender, $email, $hash);

if (!$stmt->execute()) {
    $err = $stmt->error;
    $stmt->close();
    jsonServerError('register', $err, 'สมัครสมาชิกไม่สำเร็จ กรุณาลองใหม่');
}
$stmt->close();
$conn->close();

jsonOk(['message' => 'สมัครสมาชิกเรียบร้อยแล้ว']);
