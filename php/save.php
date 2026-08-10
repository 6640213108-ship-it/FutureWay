<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/db_config.php';

try {
    $conn = getDbConnection();
} catch (Exception $e) {
    die("Connection failed: " . $e->getMessage());
}

$username  = isset($_POST['user'])       ? trim($_POST['user'])       : '';
$firstname = isset($_POST['fristname'])  ? trim($_POST['fristname'])  : '';
$lastname  = isset($_POST['lastneme'])   ? trim($_POST['lastneme'])   : '';
$gender    = isset($_POST['gender'])     ? trim($_POST['gender'])     : '';
$email     = isset($_POST['email'])      ? trim($_POST['email'])      : '';
$password1 = isset($_POST['password1'])  ? $_POST['password1']        : '';
$password2 = isset($_POST['password2'])  ? $_POST['password2']        : '';

// ช่อง "ชื่อผู้ใช้" เปลี่ยนเป็นเบอร์มือถือแล้ว (เก็บลงคอลัมน์ username เดิม
// เพื่อให้ login.php ใช้เบอร์เข้าสู่ระบบได้โดยไม่ต้องแก้ฐานข้อมูล)
// ลบอักขระคั่น เช่น ขีด/เว้นวรรค ออกก่อน แล้วตรวจว่าเป็นเบอร์ 10 หลักขึ้นต้นด้วย 0
$username = preg_replace('/[^0-9]/', '', $username);
if (!preg_match('/^0[0-9]{9}$/', $username)) {
    header("Location: ../register.html?status=invalid_phone");
    exit();
}

// ตรวจสอบรหัสผ่านตรงกัน
if ($password1 !== $password2) {
    header("Location: ../register.html?status=password_mismatch");
    exit();
}

// ตรวจสอบข้อมูลซ้ำ
$check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
$check_stmt->bind_param("ss", $username, $email);
$check_stmt->execute();
$check_stmt->store_result();

if ($check_stmt->num_rows > 0) {
    header("Location: ../register.html?status=duplicate");
    $check_stmt->close();
    $conn->close();
    exit();
}
$check_stmt->close();

// เข้ารหัสผ่านและบันทึก
$hashedPassword = password_hash($password1, PASSWORD_DEFAULT);

$stmt = $conn->prepare(
    "INSERT INTO users (username, firstname, lastname, gender, email, password) VALUES (?, ?, ?, ?, ?, ?)"
);
if ($stmt === false) {
    die("เกิดข้อผิดพลาดเกี่ยวกับ Database: " . $conn->error);
}

$stmt->bind_param("ssssss", $username, $firstname, $lastname, $gender, $email, $hashedPassword);

if ($stmt->execute()) {
    header("Location: ../register.html?status=success");
    exit();
} else {
    echo "เกิดข้อผิดพลาดในการบันทึก: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
