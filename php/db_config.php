<?php
// ========================================
// FutureWay - db_config.php
// ค่าเชื่อมต่อฐานข้อมูล + ฟังก์ชันเปิด connection (ที่เดียวของทั้งโปรเจกต์)
//
// อ่านจาก environment variable เท่านั้น — Railway จะ inject ให้อัตโนมัติเมื่อ
// เชื่อม service MySQL ไว้ในโปรเจกต์เดียวกัน ส่วนเครื่อง dev ใช้ไฟล์ .env
// (ดูตัวอย่างใน .env.example) ห้าม hardcode รหัสผ่านจริงไว้ในไฟล์นี้เด็ดขาด
// เพราะไฟล์นี้อยู่ใน git repo สาธารณะ
//
// decision_tree.py อ่านตัวแปรชุดเดียวกันนี้ (get_db_config) — แก้ชื่อตัวแปรที่ไหน
// ต้องแก้ทั้งสองที่
// ========================================

define('DB_HOST', getenv('MYSQLHOST') ?: '127.0.0.1');
define('DB_USER', getenv('MYSQLUSER') ?: 'root');
define('DB_PASS', getenv('MYSQLPASSWORD') ?: '');
define('DB_NAME', getenv('MYSQLDATABASE') ?: 'futureway');
define('DB_PORT', (int)(getenv('MYSQLPORT') ?: 3306));

/**
 * เปิดการเชื่อมต่อฐานข้อมูลใหม่ 1 connection (charset utf8mb4, time zone ไทย)
 *
 * @throws Exception ถ้ายังไม่ได้ตั้งรหัสผ่าน หรือเชื่อมต่อไม่สำเร็จ
 */
function getDbConnection(): mysqli {
    if (DB_PASS === '') {
        throw new Exception('MYSQLPASSWORD environment variable is not set');
    }

    // ตรวจ connect_error เองแทนการโยน exception จาก mysqli
    mysqli_report(MYSQLI_REPORT_OFF);

    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    if ($conn->connect_error) {
        throw new Exception('DB connection failed: ' . $conn->connect_error);
    }

    $conn->set_charset('utf8mb4');

    // เซิร์ฟเวอร์ MySQL บน Railway เดินเวลาเป็น UTC ทำให้ค่าในคอลัมน์ TIMESTAMP
    // (เช่น created_at ของผลแบบทดสอบ) ช้ากว่าเวลาไทย 7 ชม. ตั้งที่จุดเดียวตรงนี้
    $conn->query("SET time_zone = '+07:00'");

    return $conn;
}
