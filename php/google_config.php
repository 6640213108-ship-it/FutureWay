<?php
// ========================================
// FutureWay - google_config.php
// เก็บ Google OAuth Client ID ที่เดียว ให้ทั้ง google_login.php
// และ get_google_client_id.php ใช้ร่วมกัน
//
// วิธีตั้งค่า (เลือกอย่างใดอย่างหนึ่ง):
//   1. ตั้ง environment variable ชื่อ GOOGLE_CLIENT_ID บน Railway (แนะนำ)
//   2. หรือแก้ค่า fallback ข้างล่างเป็น Client ID ของตัวเอง
//
// วิธีสร้าง Client ID: https://console.cloud.google.com
//   → APIs & Services → Credentials → Create OAuth client ID → Web application
//   → Authorized JavaScript origins ใส่โดเมนเว็บ เช่น
//     https://xxxx.up.railway.app และ http://localhost (ไว้ทดสอบในเครื่อง)
// ========================================

define('GOOGLE_CLIENT_ID', getenv('GOOGLE_CLIENT_ID')
    ?: 'YOUR_GOOGLE_CLIENT_ID.apps.googleusercontent.com');
