<?php
// ========================================
// FutureWay - update_profile.php
// บันทึกข้อมูลโปรไฟล์ของผู้ใช้ที่ล็อกอินอยู่ (ใช้โดย edit_profile.html)
//
// รับเฉพาะ POST + body เป็น JSON:
//   {"firstname":"...", "lastname":"...", "email":"...",
//    "gender":"ชาย|หญิง|ไม่ระบุ", "phone":"...", "address":"..."}
//
// แก้ได้เฉพาะบัญชีตัวเอง — user_id มาจาก session เท่านั้น ไม่รับจาก client
// ========================================

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/user_session.php';

$input = requireJsonPost();
$conn  = connectOrFailJson();
$user  = requireUserJson($conn);
$userId = (int)$user['id'];

ensureUserProfileColumns($conn);
$userCols = tableColumns($conn, 'users');

// ---- อ่านค่าที่ส่งมา ----
$firstname = trim((string)($input['firstname'] ?? ''));
$lastname  = trim((string)($input['lastname']  ?? ''));
$email     = trim((string)($input['email']     ?? ''));
$gender    = trim((string)($input['gender']    ?? ''));
$phone     = trim((string)($input['phone']     ?? ''));
$address   = trim((string)($input['address']   ?? ''));

// ---- ตรวจความถูกต้อง ----
// นับความยาวด้วย mb_strlen เพราะชื่อไทย 1 ตัวอักษรกินหลาย byte
// ถ้านับด้วย strlen จะตัดชื่อคนไทยทิ้งทั้งที่ยังไม่เกินขนาดคอลัมน์
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

// ใช้ค่าชุดเดียวกับ select ในหน้า register.html เพื่อให้สถิติแยกเพศในหน้าแอดมิน
// ไม่แตกเป็นคำใหม่ที่ความหมายเดียวกัน
$allowedGender = ['ชาย', 'หญิง', 'อื่นๆ'];
if ($gender === '') {
    $gender = 'อื่นๆ';
}
if (!in_array($gender, $allowedGender, true)) {
    jsonFail(400, 'เพศต้องเป็น ชาย, หญิง หรือ อื่นๆ');
}

if ($phone !== '') {
    // ยอมให้มี - เว้นวรรค วงเล็บ และ + นำหน้า แล้วค่อยดูว่าเหลือตัวเลขกี่หลัก
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

// ---- อีเมลต้องไม่ซ้ำกับคนอื่น (คอลัมน์ email เป็น UNIQUE KEY) ----
// เช็คก่อนเพื่อให้ได้ข้อความไทยที่อ่านรู้เรื่อง แทนที่จะปล่อยให้ MySQL ฟ้อง error 1062
$stmt = $conn->prepare('SELECT id FROM users WHERE email = ? AND id <> ?');
if (!$stmt) {
    jsonFail(500, 'ตรวจสอบอีเมลไม่สำเร็จ: ' . $conn->error);
}
$stmt->bind_param('si', $email, $userId);
$stmt->execute();
$taken = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($taken) {
    jsonFail(409, 'อีเมลนี้ถูกใช้โดยบัญชีอื่นแล้ว');
}

// ---- ประกอบคำสั่ง UPDATE เฉพาะคอลัมน์ที่มีจริง ----
// phone / address จะไม่มีถ้ายังเพิ่มคอลัมน์ไม่สำเร็จ (เช่นสิทธิ์ ALTER ไม่พอ)
// กรณีนั้นยังต้องแก้ชื่อ/อีเมลได้ตามปกติ ไม่ใช่พังทั้งหน้า
$fields = [
    'firstname' => $firstname,
    'lastname'  => $lastname,
    'email'     => $email,
    'gender'    => $gender,
];
if (in_array('phone', $userCols, true)) {
    $fields['phone'] = $phone;
}
if (in_array('address', $userCols, true)) {
    $fields['address'] = $address;
}

$setSql = implode(', ', array_map(fn($c) => "`$c` = ?", array_keys($fields)));
$stmt   = $conn->prepare("UPDATE users SET $setSql WHERE id = ?");
if (!$stmt) {
    jsonFail(500, 'บันทึกไม่สำเร็จ: ' . $conn->error);
}

$params = array_values($fields);
$params[] = $userId;
$stmt->bind_param(str_repeat('s', count($fields)) . 'i', ...$params);

if (!$stmt->execute()) {
    $err = $stmt->error;
    $stmt->close();
    jsonFail(500, 'บันทึกไม่สำเร็จ: ' . $err);
}
$stmt->close();

$skipped = array_values(array_diff(['phone', 'address'], array_keys($fields)));

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
    // บอกหน้าเว็บตรง ๆ ถ้ามีช่องที่บันทึกไม่ได้ จะได้ไม่เข้าใจผิดว่าเซฟแล้ว
    'not_saved' => $skipped,
]);
