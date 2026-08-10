<?php
// ========================================
// FutureWay - set_gender.php
// บันทึกเพศของผู้ใช้ที่ล็อกอินอยู่ — ใช้โดยป็อปอัปเลือกเพศ
// หลังเข้าสู่ระบบด้วย Google ครั้งแรก (js/google-login.js)
//
// รับ POST + body เป็น JSON: {"gender":"ชาย|หญิง|อื่นๆ"}
// แก้ได้เฉพาะบัญชีตัวเอง — user_id มาจาก session เท่านั้น
// ========================================

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/user_session.php';

$input = requireJsonPost();
$conn  = connectOrFailJson();
$user  = requireUserJson($conn);

// ใช้ค่าชุดเดียวกับ select ในหน้า register.html / update_profile.php
// เพื่อให้สถิติแยกเพศในหน้าแอดมินไม่แตกเป็นคำใหม่
$gender  = trim((string)($input['gender'] ?? ''));
$allowed = ['ชาย', 'หญิง', 'อื่นๆ'];
if (!in_array($gender, $allowed, true)) {
    jsonFail(400, 'เพศต้องเป็น ชาย, หญิง หรือ อื่นๆ');
}

$userId = (int)$user['id'];
$stmt   = $conn->prepare('UPDATE users SET gender = ? WHERE id = ?');
if (!$stmt) {
    jsonFail(500, 'บันทึกไม่สำเร็จ: ' . $conn->error);
}
$stmt->bind_param('si', $gender, $userId);
if (!$stmt->execute()) {
    $err = $stmt->error;
    $stmt->close();
    jsonFail(500, 'บันทึกไม่สำเร็จ: ' . $err);
}
$stmt->close();

jsonOk(['gender' => $gender]);
