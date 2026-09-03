<?php
// ========================================
// FutureWay - delete_my_data.php
// ลบข้อมูลของ "ตัวเอง" (ใช้โดยหน้า privacy.html)
//
// รับเฉพาะ POST + body เป็น JSON:
//   {"scope":"history", "password":"..."}   ลบประวัติผลแบบทดสอบทุกรอบ
//   {"scope":"account", "password":"..."}   ลบบัญชีทิ้งทั้งหมด (ย้อนกลับไม่ได้)
//
// ต้องยืนยันด้วยรหัสผ่านทั้งสองแบบ และแตะได้เฉพาะ user_id ของ session ตัวเอง
// ========================================

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/user_session.php';

$input  = requireJsonPost();
$conn   = connectOrFailJson();
$user   = requireUserJson($conn);
$userId = (int)$user['id'];

$scope    = (string)($input['scope'] ?? '');
$password = (string)($input['password'] ?? '');

if (!in_array($scope, ['history', 'account'], true)) {
    jsonFail(400, 'ไม่รู้จักประเภทข้อมูลที่จะลบ');
}
if ($password === '') {
    jsonFail(400, 'กรุณากรอกรหัสผ่านเพื่อยืนยัน');
}
if (!password_verify($password, $user['password'])) {
    usleep(300000);
    jsonFail(401, 'รหัสผ่านไม่ถูกต้อง');
}

if ($scope === 'history') {
    $stmt = $conn->prepare('DELETE FROM quiz_results WHERE user_id = ?');
    if (!$stmt) {
        jsonServerError('delete_my_data', $conn->error, 'ลบข้อมูลไม่สำเร็จ');
    }
    $stmt->bind_param('i', $userId);
    if (!$stmt->execute()) {
        $err = $stmt->error;
        $stmt->close();
        jsonServerError('delete_my_data', $err, 'ลบข้อมูลไม่สำเร็จ');
    }
    $deleted = $stmt->affected_rows;
    $stmt->close();
    $conn->close();

    error_log(sprintf('delete_my_data: user "%s" (id=%d) deleted %d quiz results', $user['username'], $userId, $deleted));

    jsonOk([
        'scope'   => 'history',
        'deleted' => $deleted,
        'message' => "ลบประวัติผลลัพธ์แล้ว $deleted รอบ",
    ]);
}

// ---- ลบบัญชี ----
// ลบตารางลูกก่อนให้ชัวร์ (เผื่อ DB ไม่มี FK CASCADE) แล้วค่อยลบตัวผู้ใช้ ทั้งหมดในธุรกรรมเดียว
$conn->begin_transaction();
try {
    foreach (['DELETE FROM quiz_results WHERE user_id = ?', 'DELETE FROM user_settings WHERE user_id = ?'] as $sql) {
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $stmt->close();
        }
    }

    $stmt = $conn->prepare('DELETE FROM users WHERE id = ?');
    if (!$stmt) {
        throw new Exception($conn->error);
    }
    $stmt->bind_param('i', $userId);
    if (!$stmt->execute()) {
        throw new Exception($stmt->error);
    }
    $stmt->close();

    $conn->commit();
} catch (Throwable $e) {
    $conn->rollback();
    $conn->close();
    jsonServerError('delete_my_data account', $e->getMessage(), 'ลบบัญชีไม่สำเร็จ');
}
$conn->close();

error_log(sprintf('delete_my_data: account "%s" (id=%d) deleted by owner', $user['username'], $userId));

// บัญชีไม่มีแล้ว session ที่ค้างอยู่ต้องหายไปด้วย
$_SESSION = [];
session_destroy();

jsonOk(['scope' => 'account', 'message' => 'ลบบัญชีเรียบร้อยแล้ว']);
