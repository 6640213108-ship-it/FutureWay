<?php
// ========================================
// FutureWay - get_user.php
// ข้อมูลของผู้ใช้ที่ล็อกอินอยู่ สำหรับหน้าเว็บฝั่ง client
// ใช้โดย main.html, profile.html, admin.html, edit_profile.html
//
// ตอบกลับ:
//   success, fullname, firstname, lastname, username, email, gender,
//   phone, address, is_admin, settings (การตั้งค่าการแจ้งเตือน/ความเป็นส่วนตัว)
// ========================================

// เริ่ม session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/db_config.php';

try {
    $conn = getDbConnection();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'fullname' => '', 'reason' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once __DIR__ . '/admin_config.php';
require_once __DIR__ . '/user_session.php';

// ค่าเริ่มต้น
$response = [
    "success"  => false,
    "fullname" => "",
    "is_admin" => isAdmin(),   // ให้หน้าเว็บรู้ว่าควรโชว์เมนูผู้ดูแลระบบไหม
    "settings" => array_map(fn($v) => (bool)$v, defaultUserSettings()),
    "reason"   => "ไม่ได้ล็อกอิน"
];

// เช็คจาก $_SESSION['username'] ตามที่ไฟล์ login.php ของคุณทำไว้
if (isset($_SESSION['username'])) {
    $user = $_SESSION['username'];

    // ค้นหาข้อมูลจาก username
    $sql = "SELECT * FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("s", $user);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $response["success"] = true;

            // ตรวจสอบว่าในฐานข้อมูลมีคอลัมน์ firstname / lastname หรือไม่
            $fname = isset($row['firstname']) ? $row['firstname'] : '';
            $lname = isset($row['lastname']) ? $row['lastname'] : '';

            if ($fname != '' || $lname != '') {
                // ถ้ามี ก็เอามาต่อกัน
                $response["fullname"] = trim($fname . " " . $lname);
            } else {
                // ถ้าไม่มีคอลัมน์นี้ ให้เอา username มาโชว์แทนชั่วคราว จะได้รู้ว่าล็อกอินผ่าน
                $response["fullname"] = $row['username'];
            }

            // ฟิลด์ที่หน้า edit_profile.html ต้องใช้เติมลงในฟอร์ม
            // phone / address จะยังไม่มีถ้ายังไม่ได้เพิ่มคอลัมน์ (migration 003)
            $response["username"]  = $row['username'];
            $response["firstname"] = $fname;
            $response["lastname"]  = $lname;
            $response["email"]     = $row['email']   ?? '';
            $response["gender"]    = $row['gender']  ?? '';
            $response["phone"]     = $row['phone']   ?? '';
            $response["address"]   = $row['address'] ?? '';
            $response["joined"]    = $row['created_at'] ?? null;

            // sync user_id ให้ session เก่าที่ยังไม่มี (login.php รุ่นก่อนเก็บแค่ username)
            $_SESSION['user_id'] = (int)$row['id'];

            // การตั้งค่าของผู้ใช้ — profile.html ใช้ show_email ตัดสินใจว่าจะโชว์อีเมลไหม
            $response["settings"] = getUserSettings($conn, (int)$row['id']);

            $response["reason"] = "ดึงข้อมูลสำเร็จ";
        } else {
            $response["reason"] = "หาชื่อผู้ใช้นี้ไม่พบในฐานข้อมูล";
        }
        $stmt->close();
    } else {
        $response["reason"] = "คำสั่ง SQL ผิดพลาด: " . $conn->error;
    }
} else {
    $response["reason"] = "ไม่พบข้อมูล Session (โปรดล็อกอินใหม่)";
}

$conn->close();
echo json_encode($response, JSON_UNESCAPED_UNICODE);
