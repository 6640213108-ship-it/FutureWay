<?php
// ========================================
// FutureWay - google_login.php
// รับ access token จากปุ่ม "เข้าสู่ระบบด้วย Google" (js/google-login.js)
// แล้วตรวจสอบกับเซิร์ฟเวอร์ Google โดยตรง — ไม่เชื่อข้อมูลโปรไฟล์ที่
// ฝั่งเบราว์เซอร์ส่งมาเอง เพราะใครก็ปลอมส่งมาได้
//
// ขั้นตอน:
//   1. ตรวจ token กับ Google (tokeninfo) ว่าออกให้แอปเราจริง (aud ตรงกับ Client ID)
//   2. ดึงโปรไฟล์ (userinfo) เอา email + ชื่อ
//   3. หา user จาก email — ถ้ายังไม่มีให้สมัครให้อัตโนมัติ
//   4. set session แบบเดียวกับ login.php แล้วตอบ JSON ให้หน้าเว็บ redirect เอง
// ========================================

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/db_config.php';
require_once __DIR__ . '/google_config.php';

function gFail(int $status, string $message): void {
    http_response_code($status);
    echo json_encode(['success' => false, 'error' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    gFail(405, 'ต้องเรียกด้วยวิธี POST เท่านั้น');
}

$input = json_decode(file_get_contents('php://input'), true);
$accessToken = is_array($input) ? trim($input['access_token'] ?? '') : '';

if ($accessToken === '') {
    gFail(400, 'ไม่พบ access token');
}
if (GOOGLE_CLIENT_ID === 'YOUR_GOOGLE_CLIENT_ID.apps.googleusercontent.com') {
    gFail(500, 'ยังไม่ได้ตั้งค่า Google Client ID (ดูวิธีใน php/google_config.php)');
}

/**
 * GET แล้ว decode JSON — ใช้ curl ถ้ามี ไม่งั้น fallback เป็น file_get_contents
 */
function googleApiGet(string $url, array $headers = []): ?array {
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 10,
        ]);
        $body = curl_exec($ch);
        curl_close($ch);
    } else {
        $ctx = stream_context_create(['http' => [
            'header'        => implode("\r\n", $headers),
            'timeout'       => 10,
            'ignore_errors' => true,
        ]]);
        $body = @file_get_contents($url, false, $ctx);
    }

    if (!$body) {
        return null;
    }
    $data = json_decode($body, true);
    return is_array($data) ? $data : null;
}

// ---- 1) ตรวจว่า token นี้ Google ออกให้ "แอปเรา" จริง ----
$tokenInfo = googleApiGet(
    'https://oauth2.googleapis.com/tokeninfo?access_token=' . urlencode($accessToken)
);
if (!$tokenInfo || isset($tokenInfo['error']) || empty($tokenInfo['aud'])) {
    gFail(401, 'Token ไม่ถูกต้องหรือหมดอายุ กรุณาลองใหม่');
}
if ($tokenInfo['aud'] !== GOOGLE_CLIENT_ID) {
    // token ของแอปอื่น — ปฏิเสธ ไม่งั้นเว็บอื่นเอา token ผู้ใช้ตัวเองมาสวมรอยได้
    gFail(401, 'Token นี้ไม่ได้ออกให้แอปพลิเคชันนี้');
}

// ---- 2) ดึงโปรไฟล์จาก Google ----
$profile = googleApiGet(
    'https://www.googleapis.com/oauth2/v3/userinfo',
    ['Authorization: Bearer ' . $accessToken]
);
if (!$profile || empty($profile['email'])) {
    gFail(401, 'ดึงข้อมูลบัญชี Google ไม่สำเร็จ กรุณาลองใหม่');
}
if (empty($profile['email_verified'])) {
    gFail(401, 'อีเมลของบัญชี Google นี้ยังไม่ได้รับการยืนยัน');
}

$email     = mb_strtolower(trim($profile['email']));
$firstname = trim($profile['given_name']  ?? '');
$lastname  = trim($profile['family_name'] ?? '');
if ($firstname === '') {
    // บางบัญชีไม่แยกชื่อ-นามสกุล ใช้ชื่อเต็มหรือส่วนหน้าอีเมลแทน
    $firstname = trim($profile['name'] ?? '') ?: explode('@', $email)[0];
}

// ---- 3) หา user จาก email ถ้าไม่มีก็สมัครให้อัตโนมัติ ----
try {
    $conn = getDbConnection();
} catch (Exception $e) {
    error_log('google_login.php: ' . $e->getMessage());
    gFail(500, 'เชื่อมต่อฐานข้อมูลไม่ได้');
}

$stmt = $conn->prepare('SELECT id, username FROM users WHERE email = ?');
$stmt->bind_param('s', $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    // สร้าง username จากส่วนหน้าอีเมล ถ้าชนกับของคนอื่นให้เติมเลขต่อท้าย
    $base = preg_replace('/[^a-zA-Z0-9_.]/', '', explode('@', $email)[0]);
    $base = substr($base ?: 'google_user', 0, 40);

    $username = $base;
    for ($i = 1; ; $i++) {
        $stmt = $conn->prepare('SELECT id FROM users WHERE username = ?');
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $taken = $stmt->get_result()->num_rows > 0;
        $stmt->close();
        if (!$taken) {
            break;
        }
        $username = $base . $i;
    }

    // บัญชี Google ไม่มีรหัสผ่านของระบบเรา — ใส่รหัสสุ่มยาว ๆ ที่เดาไม่ได้ไว้แทน
    // (ผู้ใช้ไปตั้งรหัสจริงทีหลังได้ที่หน้าเปลี่ยนรหัสผ่าน)
    $randomPassword = password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT);
    $gender = 'ไม่ระบุ';

    $stmt = $conn->prepare(
        'INSERT INTO users (username, firstname, lastname, gender, email, password)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->bind_param('ssssss', $username, $firstname, $lastname, $gender, $email, $randomPassword);
    if (!$stmt->execute()) {
        $err = $stmt->error;
        $stmt->close();
        error_log('google_login.php insert: ' . $err);
        gFail(500, 'สร้างบัญชีใหม่ไม่สำเร็จ กรุณาลองใหม่');
    }
    $user = ['id' => $stmt->insert_id, 'username' => $username];
    $stmt->close();
}

// ---- 4) set session แบบเดียวกับ login.php ----
session_regenerate_id(true);
$_SESSION['user_id']  = (int)$user['id'];
$_SESSION['username'] = $user['username'];

$conn->close();

echo json_encode(['success' => true, 'username' => $user['username']], JSON_UNESCAPED_UNICODE);
