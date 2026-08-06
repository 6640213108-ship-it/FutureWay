<?php
// ========================================
// FutureWay - chat.php
// Proxy เรียก Claude API จากฝั่ง server แทนการยิงตรงจาก browser
// (กัน API key รั่วใน dev tools / ฝั่ง client)
//
// ก่อนใช้งาน: ตั้ง environment variable ชื่อ ANTHROPIC_API_KEY
// บน Railway/hosting ของคุณ (ห้าม hardcode key ในไฟล์นี้)
// ========================================

session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'กรุณาเข้าสู่ระบบก่อน']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$apiKey = getenv('ANTHROPIC_API_KEY');
if (!$apiKey) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'ยังไม่ได้ตั้งค่า ANTHROPIC_API_KEY บน server']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$messages = $input['messages'] ?? null;

if (!is_array($messages) || count($messages) === 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ข้อมูล messages ไม่ถูกต้อง']);
    exit;
}

// จำกัดความยาว history กันส่ง payload ใหญ่เกินไป
if (count($messages) > 30) {
    $messages = array_slice($messages, -30);
}

$systemPrompt = 'คุณคือผู้ช่วยแนะนำสาขาการเรียนและมหาวิทยาลัยในประเทศไทย ชื่อ "FutureWay AI" ' .
    'ตอบเป็นภาษาไทยเสมอ ตอบกระชับ ชัดเจน เป็นมิตร ' .
    'ช่วยแนะนำสาขาวิชา มหาวิทยาลัย อาชีพ และค่าใช้จ่ายที่เกี่ยวข้อง ' .
    'ถ้าผู้ใช้ถามเรื่องอื่นที่ไม่เกี่ยวข้อง ให้แนะนำกลับมาที่หัวข้อการศึกษาอย่างสุภาพ';

$payload = json_encode([
    'model'      => 'claude-sonnet-4-6',
    'max_tokens' => 1000,
    'system'     => $systemPrompt,
    'messages'   => $messages,
]);

$ch = curl_init('https://api.anthropic.com/v1/messages');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'x-api-key: ' . $apiKey,
        'anthropic-version: 2023-06-01',
    ],
    CURLOPT_TIMEOUT => 30,
]);

$response  = curl_exec($ch);
$curlError = curl_error($ch);
$httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response === false) {
    http_response_code(502);
    echo json_encode(['success' => false, 'error' => 'เรียก Claude API ไม่สำเร็จ: ' . $curlError]);
    exit;
}

$data = json_decode($response, true);

if ($httpCode !== 200 || !isset($data['content'][0]['text'])) {
    http_response_code(502);
    echo json_encode([
        'success' => false,
        'error'   => 'Claude API ตอบกลับผิดพลาด',
        'debug'   => $data,
    ]);
    exit;
}

echo json_encode(['success' => true, 'text' => $data['content'][0]['text']]);
?>
