<?php
// ========================================
// FutureWay - get_settings.php
// อ่านการตั้งค่าการแจ้งเตือน + ความเป็นส่วนตัว ของผู้ใช้ที่ล็อกอินอยู่
// ใช้โดย notifications.html และ privacy.html
//
// ตอบกลับ:
//   {"success":true,
//    "settings":{"notify_result":true, ... ,"allow_stats":true},
//    "stats":{"rounds":5,"last_quiz":"2026-07-15 13:38:38","joined":"..."}}
// ========================================

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/user_session.php';

$conn   = connectOrFailJson();
$user   = requireUserJson($conn);
$userId = (int)$user['id'];

$settings = getUserSettings($conn, $userId);

// จำนวนรอบที่ทำแบบทดสอบ + รอบล่าสุด (โชว์ในหน้าความเป็นส่วนตัว)
$rounds   = 0;
$lastQuiz = null;
$stmt = $conn->prepare('SELECT COUNT(*) AS c, MAX(created_at) AS last_at FROM quiz_results WHERE user_id = ?');
if ($stmt) {
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    if ($row = $stmt->get_result()->fetch_assoc()) {
        $rounds   = (int)$row['c'];
        $lastQuiz = $row['last_at'];
    }
    $stmt->close();
}
$conn->close();

jsonOk([
    'settings' => $settings,
    'stats'    => [
        'rounds'    => $rounds,
        'last_quiz' => $lastQuiz,
        'joined'    => $user['created_at'] ?? null,
    ],
]);
