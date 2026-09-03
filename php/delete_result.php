<?php
// ========================================
// FutureWay - delete_result.php
// ลบผลการทำแบบทดสอบ 1 รอบ (สำหรับผู้ดูแลระบบเท่านั้น)
//
// รับเฉพาะ POST + body เป็น JSON: {"result_id": 123}
// (GET ถูกปฏิเสธ — ไม่งั้นแค่ฝัง <img src="...?id=1"> ก็สั่งลบได้)
//
// quiz_result_branches / quiz_answers ผูก FK ON DELETE CASCADE ไว้
// ลบแถวใน quiz_results แถวเดียว ข้อมูลลูกหายตามเองทั้งหมด
// ========================================

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/admin_auth.php';

requireAdminJson();
$input = requireJsonPost();

$resultId = (int)($input['result_id'] ?? 0);
if ($resultId <= 0) {
    jsonFail(400, 'ไม่พบ result_id ที่ต้องการลบ');
}

$conn = connectOrFailJson();

// ดึงข้อมูลก่อนลบ เพื่อเขียน log ว่าแอดมินคนไหนลบผลของใครไป
$stmtS = $conn->prepare("
    SELECT qr.id, qr.mbti_type, qr.created_at, u.username
    FROM quiz_results qr
    JOIN users u ON u.id = qr.user_id
    WHERE qr.id = ?
");
if (!$stmtS) {
    jsonServerError('delete_result', $conn->error);
}
$stmtS->bind_param('i', $resultId);
$stmtS->execute();
$target = $stmtS->get_result()->fetch_assoc();
$stmtS->close();

if (!$target) {
    jsonFail(404, 'ไม่พบผลลัพธ์ที่ต้องการลบ (อาจถูกลบไปแล้ว)');
}

$stmt = $conn->prepare('DELETE FROM quiz_results WHERE id = ?');
if (!$stmt) {
    jsonServerError('delete_result', $conn->error);
}
$stmt->bind_param('i', $resultId);
if (!$stmt->execute()) {
    $err = $stmt->error;
    $stmt->close();
    jsonServerError('delete_result', $err, 'ลบข้อมูลไม่สำเร็จ');
}
$deleted = $stmt->affected_rows;
$stmt->close();
$conn->close();

error_log(sprintf(
    'delete_result: admin "%s" deleted result_id=%d (user=%s, mbti=%s, created_at=%s)',
    $_SESSION['username'] ?? '?', $resultId, $target['username'], $target['mbti_type'], $target['created_at']
));

jsonOk(['deleted' => $deleted, 'result_id' => $resultId]);
