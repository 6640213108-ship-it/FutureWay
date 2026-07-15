<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../login.html");
    exit();
}

try {
    $conn = new PDO(
        "mysql:host=tokaido.proxy.rlwy.net;port=57745;dbname=railway",
        "root",
        "OLdaGruletpcPRSKSZkUOUrKaUWmDjri"
    );

    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

$username = isset($_POST['user']) ? trim($_POST['user']) : '';
$password = isset($_POST['password']) ? trim($_POST['password']) : '';

if (empty($username) || empty($password)) {
    header("Location: ../login.html?status=empty");
    exit();
}

$sql = "SELECT * FROM users WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$username]);

$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row) {

    if (password_verify($password, $row['password'])) {

        $_SESSION['username'] = $row['username'];
        $_SESSION['user_id'] = $row['id'];

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
?>