<?php
// ========================================
// FutureWay - update_profile.php
// บันทึกข้อมูลโปรไฟล์ของผู้ใช้ที่ล็อกอินอยู่ (ใช้โดย edit_profile.html)
//
// รับเฉพาะ POST + body เป็น JSON:
//   {"firstname":"...", "lastname":"...", "email":"...",
//    "gender":"ชาย|หญิง|อื่นๆ", "phone":"...", "address":"..."}
//
// แก้ได้เฉพาะบัญชีตัวเอง — user_id มาจาก session เท่านั้น ไม่รับจาก client
// ========================================

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/user_session.php';

$input  = requireJsonPost();
$conn   = connectOrFailJson();
$user   = requireUserJson($conn);
$userId = (int)$user['id'];

// ---- อ่านค่าที่ส่งมา ----
$firstname = trim((string)($input['firstname'] ?? ''));
$lastname  = trim((string)($input['lastname']  ?? ''));
$email     = mb_strtolower(trim((string)($input['email'] ?? '')));
$gender    = trim((string)($input['gender']    ?? ''));
$phone     = trim((string)($input['phone']     ?? ''));
$address   = trim((string)($input['address']   ?? ''));

// ---- ตรวจความถูกต้อง (นับความยาวด้วย mb_strlen เพราะชื่อไทย 1 ตัวอักษรกินหลาย byte) ----
if ($firstname === '' || $lastname === '') {
    jsonFail(400, 'กรุณากรอกชื่อและนามสกุล');
}
if (mb_strlen($firstname) > 100 || mb_strlen($lastname) > 100) {
    jsonFail(400, 'ชื่อหรือนามสกุลยาวเกินไป (ไม่เกิน 100 ตัวอักษร)');
}
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonFail(400, 'รูปแบบอีเมลไม่ถูกต้อง');
}
if (mb_strlen($email) > 150) {
    jsonFail(400, 'อีเมลยาวเกินไป (ไม่เกิน 150 ตัวอักษร)');
}

if ($gender === '') {
    $gender = 'อื่นๆ';
}
if (!in_array($gender, ['ชาย', 'หญิง', 'อื่นๆ'], true)) {
    jsonFail(400, 'เพศต้องเป็น ชาย, หญิง หรือ อื่นๆ');
}

if ($phone !== '') {
    if (!preg_match('/^[0-9()+\-\s]{6,20}$/', $phone)) {
        jsonFail(400, 'เบอร์โทรศัพท์ต้องเป็นตัวเลข (ใส่ - หรือเว้นวรรคได้) ความยาว 6-20 ตัว');
    }
    if (strlen(preg_replace('/\D/', '', $phone)) < 9) {
        jsonFail(400, 'เบอร์โทรศัพท์ต้องมีตัวเลขอย่างน้อย 9 หลัก');
    }
}
if (mb_strlen($address) > 255) {
    jsonFail(400, 'ที่อยู่ยาวเกินไป (ไม่เกิน 255 ตัวอักษร)');
}

// ---- อีเมลต้องไม่ซ้ำกับคนอื่น (เช็คก่อนเพื่อให้ได้ข้อความไทยแทน error 1062) ----
$stmt = $conn->prepare('SELECT id FROM users WHERE email = ? AND id <> ?');
if (!$stmt) {
    jsonServerError('update_profile', $conn->error);
}
$stmt->bind_param('si', $email, $userId);
$stmt->execute();
$taken = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($taken) {
    jsonFail(409, 'อีเมลนี้ถูกใช้โดยบัญชีอื่นแล้ว');
}

// ---- บันทึก ----
$stmt = $conn->prepare('UPDATE users SET firstname = ?, lastname = ?, email = ?, gender = ?, phone = ?, address = ? WHERE id = ?');
if (!$stmt) {
    jsonServerError('update_profile', $conn->error, 'บันทึกไม่สำเร็จ');
}
$stmt->bind_param('ssssssi', $firstname, $lastname, $email, $gender, $phone, $address, $userId);
if (!$stmt->execute()) {
    $err = $stmt->error;
    $stmt->close();
    jsonServerError('update_profile', $err, 'บันทึกไม่สำเร็จ');
}
$stmt->close();
$conn->close();

jsonOk([
    'message' => 'บันทึกข้อมูลเรียบร้อยแล้ว',
    'user'    => [
        'username'  => $user['username'],
        'firstname' => $firstname,
        'lastname'  => $lastname,
        'fullname'  => trim("$firstname $lastname"),
        'email'     => $email,
        'gender'    => $gender,
        'phone'     => $phone,
        'address'   => $address,
    ],
]);
