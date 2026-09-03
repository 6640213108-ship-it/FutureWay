<?php
// ========================================
// FutureWay - get_google_client_id.php
// ให้หน้าเว็บดึง Google Client ID ไปใช้ตอนเปิด popup เข้าสู่ระบบ
// ตั้งค่าที่เดียวใน google_config.php ไม่ต้องแก้ HTML ทุกหน้า
// ========================================

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/google_config.php';

jsonOk(['client_id' => GOOGLE_CLIENT_ID]);
