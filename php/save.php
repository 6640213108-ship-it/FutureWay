<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$conn = new mysqli("localhost", "root", "root", "project1");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$username  = isset($_POST['user'])       ? trim($_POST['user'])       : '';
$firstname = isset($_POST['fristname'])  ? trim($_POST['fristname'])  : '';
$lastname  = isset($_POST['lastneme'])   ? trim($_POST['lastneme'])   : '';
$gender    = isset($_POST['gender'])     ? trim($_POST['gender'])     : '';
$email     = isset($_POST['email'])      ? trim($_POST['email'])      : '';
$password1 = isset($_POST['password1'])  ? $_POST['password1']        : '';
$password2 = isset($_POST['password2'])  ? $_POST['password2']        : '';

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
