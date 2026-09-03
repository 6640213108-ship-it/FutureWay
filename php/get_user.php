<?php
// ========================================
// FutureWay - get_user.php
// ข้อมูลของผู้ใช้ที่ล็อกอินอยู่ สำหรับหน้าเว็บฝั่ง client
// ใช้โดย js/common.js (loadCurrentUser) ในทุกหน้าที่ต้องล็อกอิน
//
// ตอบกลับเมื่อล็อกอินแล้ว:
//   success, fullname, firstname, lastname, username, email, gender,
//   phone, address, joined, is_admin, settings
// ยังไม่ล็อกอิน: 401 {"success":false}
// ========================================

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/user_session.php';
require_once __DIR__ . '/admin_auth.php';

$conn = connectOrFailJson();
$user = requireUserJson($conn);

$firstname = (string)($user['firstname'] ?? '');
$lastname  = (string)($user['lastname']  ?? '');
$fullname  = trim($firstname . ' ' . $lastname) ?: $user['username'];

$settings = getUserSettings($conn, (int)$user['id']);
$conn->close();

jsonOk([
    'fullname'  => $fullname,
    'firstname' => $firstname,
    'lastname'  => $lastname,
    'username'  => $user['username'],
    'email'     => $user['email']      ?? '',
    'gender'    => $user['gender']     ?? '',
    'phone'     => $user['phone']      ?? '',
    'address'   => $user['address']    ?? '',
    'joined'    => $user['created_at'] ?? null,
    'is_admin'  => isAdmin(),
    'settings'  => $settings,
]);
