<?php
session_start();
session_unset();
session_destroy();

// คำสั่งนี้คือตัวที่จะพาผู้ใช้กลับไปหน้า Login 
// (สมมติว่าไฟล์ login.html อยู่ข้างนอกโฟลเดอร์ php)
header("Location: ../login.html");
exit();
?>