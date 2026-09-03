<?php
// ========================================
// FutureWay - set_gender.php
// บันทึกเพศของผู้ใช้ที่ล็อกอินอยู่ — ใช้โดยป็อปอัปเลือกเพศ
// หลังเข้าสู่ระบบด้วย Google ครั้งแรก (js/google-login.js)
//
// รับ POST + body เป็น JSON: {"gender":"ชาย|หญิง|อื่นๆ"}
// ========================================

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/user_session.php';

$input = requireJsonPost();
$conn  = connectOrFailJson();
$user  = requireUserJson($conn);

// ค่าชุดเดียวกับ register.html / update_profile.php เพื่อให้สถิติแยกเพศไม่แตกเป็นคำใหม่
$gender = trim((string)($input['gender'] ?? ''));
if (!in_array($gender, ['ชาย', 'หญิง', 'อื่นๆ'], true)) {
    jsonFail(400, 'เพศต้องเป็น ชาย, หญิง หรือ อื่นๆ');
}

$userId = (int)$user['id'];
$stmt   = $conn->prepare('UPDATE users SET gender = ? WHERE id = ?');
if (!$stmt) {
    jsonServerError('set_gender', $conn->error, 'บันทึกไม่สำเร็จ');
}
$stmt->bind_param('si', $gender, $userId);
if (!$stmt->execute()) {
    $err = $stmt->error;
    $stmt->close();
    jsonServerError('set_gender', $err, 'บันทึกไม่สำเร็จ');
}
$stmt->close();
$conn->close();

jsonOk(['gender' => $gender]);
