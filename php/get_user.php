<?php
// เริ่ม session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json; charset=utf-8');

$conn = new mysqli('tokaido.proxy.rlwy.net', 'root', 'OLdaGruletpcPRSKSZkUOUrKaUWmDjri', 'railway', 57745);
$conn->set_charset("utf8");

// ค่าเริ่มต้น
$response = [
    "success" => false, 
    "fullname" => "",
    "reason" => "ไม่ได้ล็อกอิน"
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
echo json_encode($response);
?>