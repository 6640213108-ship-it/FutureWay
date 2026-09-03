<?php
// ========================================
// FutureWay - google_config.php
// Google OAuth Client ID (ใช้ร่วมกันโดย google_login.php และ get_google_client_id.php)
//
// ตั้ง environment variable GOOGLE_CLIENT_ID (ดู .env.example)
// วิธีสร้าง: https://console.cloud.google.com -> APIs & Services -> Credentials
//   -> Create OAuth client ID -> Web application
//   -> Authorized JavaScript origins ใส่โดเมนเว็บ เช่น https://<app>.up.railway.app และ http://localhost
// ========================================

const GOOGLE_CLIENT_ID_PLACEHOLDER = 'YOUR_GOOGLE_CLIENT_ID.apps.googleusercontent.com';

define('GOOGLE_CLIENT_ID', getenv('GOOGLE_CLIENT_ID') ?: GOOGLE_CLIENT_ID_PLACEHOLDER);
