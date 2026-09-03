<?php
// ========================================
// FutureWay - export_my_data.php
// ดาวน์โหลดข้อมูลทั้งหมดของผู้ใช้ที่ล็อกอินอยู่เป็นไฟล์ JSON
// ใช้โดยปุ่ม "ดาวน์โหลดข้อมูลของฉัน" ในหน้า privacy.html
//
// รวม: ข้อมูลบัญชี (ไม่รวมรหัสผ่าน), การตั้งค่า, ผลการทำแบบทดสอบทุกรอบ
//       พร้อมสาขาที่แนะนำและคำตอบรายข้อของแต่ละรอบ
// ถ้ามีปัญหา jsonFail จะตอบ JSON ธรรมดา (ไม่ใช่ไฟล์ดาวน์โหลด)
// ========================================

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/user_session.php';

$conn   = connectOrFailJson();
$user   = requireUserJson($conn);
$userId = (int)$user['id'];

$profile = [
    'username'   => $user['username'],
    'firstname'  => $user['firstname']  ?? '',
    'lastname'   => $user['lastname']   ?? '',
    'gender'     => $user['gender']     ?? '',
    'email'      => $user['email']      ?? '',
    'phone'      => $user['phone']      ?? '',
    'address'    => $user['address']    ?? '',
    'created_at' => $user['created_at'] ?? null,
];

$settings = getUserSettings($conn, $userId);

// ---- ผลการทำแบบทดสอบทุกรอบ ----
$results = [];
$ids     = [];
$stmt = $conn->prepare('SELECT * FROM quiz_results WHERE user_id = ? ORDER BY created_at DESC, id DESC');
if ($stmt) {
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        unset($row['user_id']);
        $id              = (int)$row['id'];
        $ids[]           = $id;
        $row['branches'] = [];
        $row['answers']  = [];
        $results[$id]    = $row;
    }
    $stmt->close();
}

// ---- ข้อมูลลูกของแต่ละรอบ ดึงทีเดียวด้วย IN (...) ----
if ($ids) {
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types        = str_repeat('i', count($ids));

    $children = [
        'branches' => "SELECT * FROM quiz_result_branches WHERE result_id IN ($placeholders) ORDER BY result_id, category_rank, rank_no",
        'answers'  => "SELECT * FROM quiz_answers WHERE result_id IN ($placeholders) ORDER BY result_id, id",
    ];
    foreach ($children as $key => $sql) {
        $q = $conn->prepare($sql);
        if (!$q) {
            continue;
        }
        $q->bind_param($types, ...$ids);
        $q->execute();
        $r = $q->get_result();
        while ($row = $r->fetch_assoc()) {
            $rid = (int)$row['result_id'];
            if (isset($results[$rid])) {
                $results[$rid][$key][] = $row;
            }
        }
        $q->close();
    }
}

$conn->close();

$payload = [
    'exported_by' => 'FutureWay',
    'exported_at' => date('c'),
    'profile'     => $profile,
    'settings'    => $settings,
    'quiz_rounds' => array_values($results),
];

// ตั้งชื่อไฟล์จาก username (กรองอักขระที่ใช้ตั้งชื่อไฟล์ไม่ได้ออกก่อน)
$safeName = preg_replace('/[^A-Za-z0-9_\-]/', '', $user['username']) ?: 'user';
$filename = 'futureway-' . $safeName . '-' . date('Ymd') . '.json';

header('Content-Disposition: attachment; filename="' . $filename . '"');
echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
