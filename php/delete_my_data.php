<?php
// ========================================
// FutureWay - delete_my_data.php
// ลบข้อมูลของ "ตัวเอง" (ใช้โดยหน้า privacy.html)
//
// รับเฉพาะ POST + body เป็น JSON:
//   {"scope":"history", "password":"..."}   ลบประวัติผลแบบทดสอบทุกรอบ
//   {"scope":"account", "password":"..."}   ลบบัญชีทิ้งทั้งหมด (ย้อนกลับไม่ได้)
//
// ต้องยืนยันด้วยรหัสผ่านทั้งสองแบบ เพราะเป็นการลบที่กู้คืนไม่ได้
// และแตะได้เฉพาะ user_id ของ session ตัวเอง (ต่างจาก delete_result.php ที่เป็นของแอดมิน)
//
// quiz_result_branches / quiz_answers ผูก FK ON DELETE CASCADE ไว้กับ quiz_results
// และ quiz_results / user_settings ผูก CASCADE ไว้กับ users -> ลบต้นทางพอ
// ========================================

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/user_session.php';

$input = requireJsonPost();
$conn  = connectOrFailJson();
$user  = requireUserJson($conn);
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
        jsonFail(500, 'ลบข้อมูลไม่สำเร็จ: ' . $conn->error);
    }
    $stmt->bind_param('i', $userId);
    if (!$stmt->execute()) {
        $err = $stmt->error;
        $stmt->close();
        jsonFail(500, 'ลบข้อมูลไม่สำเร็จ: ' . $err);
    }
    $deleted = $stmt->affected_rows;
    $stmt->close();
    $conn->close();

    error_log(sprintf('delete_my_data.php: user "%s" (id=%d) deleted %d quiz results',
        $user['username'], $userId, $deleted));

    jsonOk([
        'scope'   => 'history',
        'deleted' => $deleted,
        'message' => "ลบประวัติผลลัพธ์แล้ว $deleted รอบ",
    ]);
}

// ---- ลบบัญชี ----
// ถ้า DB นี้ไม่มี FK CASCADE (เช่นตารางถูกสร้างแบบ MyISAM) การลบ users แถวเดียว
// จะทิ้งผลแบบทดสอบไว้เป็นขยะ เลยลบลูกก่อนให้ชัวร์ แล้วค่อยลบตัวผู้ใช้
$conn->begin_transaction();
try {
    foreach (['DELETE FROM quiz_results WHERE user_id = ?',
              'DELETE FROM user_settings WHERE user_id = ?'] as $sql) {
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param('i', $userId);
            $stmt->execute();      // ตารางอาจยังไม่มี -> prepare ไม่ผ่าน ก็ข้ามไป
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
    error_log('delete_my_data.php error: ' . $e->getMessage());
    jsonFail(500, 'ลบบัญชีไม่สำเร็จ: ' . $e->getMessage());
}

$conn->close();

error_log(sprintf('delete_my_data.php: account "%s" (id=%d) deleted by owner', $user['username'], $userId));

// บัญชีไม่มีแล้ว session ที่ค้างอยู่ต้องหายไปด้วย ไม่งั้นหน้าอื่นจะพยายามโหลดข้อมูลของ id ที่ถูกลบ
$_SESSION = [];
session_destroy();

jsonOk([
    'scope'   => 'account',
    'message' => 'ลบบัญชีเรียบร้อยแล้ว',
]);
