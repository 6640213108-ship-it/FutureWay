<?php
// ========================================
// FutureWay - get_google_client_id.php
// ให้หน้าเว็บ (index.html / login.html) ดึง Client ID ไปใช้ตอนเปิด popup
// จะได้ตั้งค่า Client ID แค่ที่เดียวใน google_config.php ไม่ต้องแก้ HTML ทุกหน้า
// ========================================

require_once __DIR__ . '/google_config.php';

header('Content-Type: application/json; charset=utf-8');
echo json_encode(['client_id' => GOOGLE_CLIENT_ID]);
