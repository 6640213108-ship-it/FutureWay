<?php
// ========================================
// FutureWay - get_history.php
// ดึงประวัติผลลัพธ์ทั้งหมดของผู้ใช้ที่ login อยู่ (ใหม่ล่าสุดก่อน)
// ใช้โดย History_Results.html
// ========================================

session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'กรุณาเข้าสู่ระบบก่อน']);
    exit;
}

require_once __DIR__ . '/db_config.php';

try {
    $conn = getDbConnection();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'DB connection failed: ' . $e->getMessage()]);
    exit;
}

$stmt = $conn->prepare("
    SELECT qr.id, qr.mbti_type, qr.branch_name, qr.score, qr.created_at,
           b.faculty, b.description
    FROM quiz_results qr
    LEFT JOIN branches b ON qr.branch_id = b.id
    WHERE qr.user_id = ?
    ORDER BY qr.created_at DESC
");
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

$history = [];
while ($row = $result->fetch_assoc()) {
    $history[] = $row;
}
$stmt->close();
$conn->close();

echo json_encode(['success' => true, 'history' => $history]);
?>
