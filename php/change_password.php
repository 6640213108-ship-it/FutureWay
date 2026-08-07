<?php
// ========================================
// FutureWay - change_password.php
// เปลี่ยนรหัสผ่านของผู้ใช้ที่ล็อกอินอยู่ (ใช้โดย change_password.html)
//
// รับเฉพาะ POST + body เป็น JSON:
//   {"current_password":"...", "new_password":"...", "confirm_password":"..."}
//
// ต้องกรอกรหัสผ่านเดิมเสมอ ถึงจะล็อกอินค้างอยู่ก็ตาม
// กันกรณีเครื่องถูกเปิดทิ้งไว้แล้วมีคนมาเปลี่ยนรหัสยึดบัญชีไป
// ========================================

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/user_session.php';

$input = requireJsonPost();
$conn  = connectOrFailJson();
$user  = requireUserJson($conn);

$current = (string)($input['current_password'] ?? '');
$new     = (string)($input['new_password']     ?? '');
$confirm = (string)($input['confirm_password'] ?? '');

if ($current === '' || $new === '' || $confirm === '') {
    jsonFail(400, 'กรุณากรอกข้อมูลให้ครบทุกช่อง');
}
if ($new !== $confirm) {
    jsonFail(400, 'รหัสผ่านใหม่กับการยืนยันไม่ตรงกัน');
}
if (mb_strlen($new) < 6) {
    jsonFail(400, 'รหัสผ่านใหม่ต้องมีอย่างน้อย 6 ตัวอักษร');
}
if (strlen($new) > 72) {
    // bcrypt (PASSWORD_DEFAULT) อ่านแค่ 72 byte แรก ที่เกินจะถูกตัดทิ้งเงียบ ๆ
    // ตัดจบตั้งแต่ตรงนี้ดีกว่าปล่อยให้ผู้ใช้เข้าใจผิดว่ารหัสยาวกว่านั้นมีผล
    jsonFail(400, 'รหัสผ่านใหม่ยาวเกินไป (ไม่เกิน 72 ตัวอักษร)');
}
if ($new === $current) {
    jsonFail(400, 'รหัสผ่านใหม่ต้องไม่ซ้ำกับรหัสผ่านเดิม');
}

if (!password_verify($current, $user['password'])) {
    // หน่วงนิดหนึ่งกันคนไล่เดารหัสเดิมรัว ๆ จากหน้าที่ล็อกอินค้างไว้
    usleep(300000);
    jsonFail(401, 'รหัสผ่านเดิมไม่ถูกต้อง');
}

$hash   = password_hash($new, PASSWORD_DEFAULT);
$userId = (int)$user['id'];

$stmt = $conn->prepare('UPDATE users SET password = ? WHERE id = ?');
if (!$stmt) {
    jsonFail(500, 'เปลี่ยนรหัสผ่านไม่สำเร็จ: ' . $conn->error);
}
$stmt->bind_param('si', $hash, $userId);

if (!$stmt->execute()) {
    $err = $stmt->error;
    $stmt->close();
    jsonFail(500, 'เปลี่ยนรหัสผ่านไม่สำเร็จ: ' . $err);
}
$stmt->close();

// ออก session id ใหม่หลังเปลี่ยนรหัส (ข้อมูลใน session ยังอยู่ครบ ไม่ต้องล็อกอินใหม่)
// ถ้ามีใครขโมย session id เดิมไป อันนั้นจะใช้ไม่ได้อีกต่อไป
session_regenerate_id(true);

error_log(sprintf('change_password.php: user "%s" (id=%d) changed password', $user['username'], $userId));

jsonOk(['message' => 'เปลี่ยนรหัสผ่านเรียบร้อยแล้ว']);
