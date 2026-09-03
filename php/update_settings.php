<?php
// ========================================
// FutureWay - update_settings.php
// บันทึกการตั้งค่าการแจ้งเตือน / ความเป็นส่วนตัว
// ใช้โดย notifications.html และ privacy.html
//
// รับเฉพาะ POST + body เป็น JSON เช่น {"notify_result":true, "notify_news":false}
// key ไหนไม่ส่งมาก็คงค่าเดิมไว้ ทำให้หน้าเว็บส่งมาแค่ปุ่มที่เพิ่งกดสลับก็พอ
// ========================================

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/user_session.php';

$input = requireJsonPost();
$conn  = connectOrFailJson();
$user  = requireUserJson($conn);

$known = array_intersect_key($input, defaultUserSettings());
if (!$known) {
    jsonFail(400, 'ไม่มีการตั้งค่าที่รู้จักส่งมาเลย');
}

$settings = saveUserSettings($conn, (int)$user['id'], $known);
$conn->close();

jsonOk([
    'message'  => 'บันทึกการตั้งค่าแล้ว',
    'settings' => $settings,
]);
