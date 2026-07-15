<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../login.html");
    exit();
}
$conn = new mysqli('tokaido.proxy.rlwy.net', 'root', 'mysql -h tokaido.proxy.rlwy.net -u root -p OLdaGruletpcPRSKSZkUOUrKaUWmDjri --port 57745 --protocol=TCP railway', 'railway', 57745);

if ($conn->connect_error) {
    die("เชื่อมต่อ DB ไม่ได้: " . $conn->connect_error);
}

$username = isset($_POST['user']) ? trim($_POST['user']) : '';
$password = isset($_POST['password']) ? trim($_POST['password']) : '';

if (empty($username) || empty($password)) {
    header("Location: ../login.html?status=empty");
    exit();
}

$sql  = "SELECT * FROM users WHERE username = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("SQL Error: " . $conn->error);
}

$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();

    if (password_verify($password, $row['password'])) {
        // ✅ set ทั้ง username และ user_id
        $_SESSION['username'] = $row['username'];
        $_SESSION['user_id']  = $row['id'];        // ← เพิ่มบรรทัดนี้
        header("Location: ../login.html?status=success");
        exit();
    } else {
        header("Location: ../login.html?status=wrong_password");
        exit();
    }
} else {
    header("Location: ../login.html?status=user_not_found");
    exit();
}

$conn->close();
?>
