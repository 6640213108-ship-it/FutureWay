<?php
// ========================================
// FutureWay - get_result.php
// ดึงผลลัพธ์จาก DB ส่งให้ result.html
// ========================================

session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'กรุณาเข้าสู่ระบบก่อน']);
    exit;
}

$resultId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$resultId) {
    echo json_encode(['success' => false, 'error' => 'ไม่พบ result_id']);
    exit;
}

$conn = new mysqli('tokaido.proxy.rlwy.net', 'root', 'OLdaGruletpcPRSKSZkUOUrKaUWmDjri', 'railway', 57745);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'DB connection failed']);
    exit;
}
$conn->set_charset('utf8mb4');

// ดึงข้อมูล quiz_result
$stmt = $conn->prepare("
    SELECT qr.*, b.faculty, b.description
    FROM quiz_results qr
    LEFT JOIN branches b ON qr.branch_id = b.id
    WHERE qr.id = ? AND qr.user_id = ?
");
$stmt->bind_param('ii', $resultId, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$result) {
    echo json_encode(['success' => false, 'error' => 'ไม่พบข้อมูล']);
    exit;
}

// ดึง top 3 สาขาโดยรัน Python อีกครั้ง (เพื่อแสดงผลครบ)
$grades = [
    'math'   => $result['grade_math'],
    'sci'    => $result['grade_sci'],
    'eng'    => $result['grade_eng'],
    'thai'   => $result['grade_thai'],
    'social' => $result['grade_social'],
    'art'    => $result['grade_art'],
];
$mbti = $result['mbti_type'];

$pythonInput = json_encode(['grades' => $grades, 'mbti' => $mbti]);
require_once __DIR__ . '/python_config.php';
try {
    $pythonPath = getPythonPath();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}
$scriptPath  = dirname(__DIR__) . '/decision_tree.py';

$descriptors = [
    0 => ['pipe', 'r'],
    1 => ['pipe', 'w'],
    2 => ['pipe', 'w'],
];

$process = proc_open('"' . $pythonPath . '" "' . $scriptPath . '"', $descriptors, $pipes);
fwrite($pipes[0], $pythonInput);
fclose($pipes[0]);
$pythonOutput = stream_get_contents($pipes[1]);
fclose($pipes[1]);
fclose($pipes[2]);
proc_close($process);

$pyResult = json_decode($pythonOutput, true);

$conn->close();

echo json_encode([
    'success'    => true,
    'mbti'       => $mbti,
    'avg_grade'  => round(array_sum($grades) / count($grades), 2),
    'grades'     => $grades,
    'top3'       => $pyResult['top3'] ?? [],
    'created_at' => $result['created_at'],
]);
?>