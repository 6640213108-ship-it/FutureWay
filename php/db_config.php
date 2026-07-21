<?php
// ========================================
// FutureWay - db_config.php
// ไฟล์เดียวสำหรับตั้งค่าและเชื่อมต่อ Database
// ให้ทุกไฟล์ require ตัวนี้แทนการ hardcode ค่าเชื่อมต่อซ้ำๆ
// ========================================

// อ่านจาก environment variable ก่อน (Railway จะ inject ให้อัตโนมัติ
// ถ้า service เชื่อมกันในโปรเจกต์เดียวกัน) ถ้าไม่มีให้ fallback
// ไปใช้ค่า internal host ที่ใช้งานอยู่ตอนนี้
define('DB_HOST', getenv('MYSQLHOST') ?: 'mysql.railway.internal');
define('DB_USER', getenv('MYSQLUSER') ?: 'root');
define('DB_PASS', getenv('MYSQLPASSWORD') ?: 'OLdaGruletpcPRSKSZkUOUrKaUWmDjri');
define('DB_NAME', getenv('MYSQLDATABASE') ?: 'railway');
define('DB_PORT', (int)(getenv('MYSQLPORT') ?: 3306));

/**
 * เปิดการเชื่อมต่อ Database ใหม่ 1 connection
 * ใช้ mysqli_report(MYSQLI_REPORT_OFF) เพื่อไม่ให้ throw exception ตอน connect ไม่ได้
 * (ให้ตรวจสอบ $conn->connect_error เองแทน เหมือนที่ไฟล์เดิมทำอยู่)
 *
 * @return mysqli
 * @throws Exception ถ้าเชื่อมต่อไม่สำเร็จ
 */
function getDbConnection(): mysqli {
    mysqli_report(MYSQLI_REPORT_OFF);

    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

    if ($conn->connect_error) {
        throw new Exception('DB connection failed: ' . $conn->connect_error);
    }

    $conn->set_charset('utf8mb4');
    return $conn;
}
