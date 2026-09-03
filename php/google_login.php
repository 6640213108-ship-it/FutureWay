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

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/google_config.php';

$input       = requireJsonPost();
$accessToken = trim((string)($input['access_token'] ?? ''));

if ($accessToken === '') {
    jsonFail(400, 'ไม่พบ access token');
}
if (GOOGLE_CLIENT_ID === GOOGLE_CLIENT_ID_PLACEHOLDER) {
    jsonFail(500, 'ระบบยังไม่ได้ตั้งค่า Google Client ID');
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
$tokenInfo = googleApiGet('https://oauth2.googleapis.com/tokeninfo?access_token=' . urlencode($accessToken));
if (!$tokenInfo || isset($tokenInfo['error']) || empty($tokenInfo['aud'])) {
    jsonFail(401, 'Token ไม่ถูกต้องหรือหมดอายุ กรุณาลองใหม่');
}
if ($tokenInfo['aud'] !== GOOGLE_CLIENT_ID) {
    // token ของแอปอื่น — ปฏิเสธ ไม่งั้นเว็บอื่นเอา token ผู้ใช้ตัวเองมาสวมรอยได้
    jsonFail(401, 'Token นี้ไม่ได้ออกให้แอปพลิเคชันนี้');
}

// ---- 2) ดึงโปรไฟล์จาก Google ----
$profile = googleApiGet('https://www.googleapis.com/oauth2/v3/userinfo', ['Authorization: Bearer ' . $accessToken]);
if (!$profile || empty($profile['email'])) {
    jsonFail(401, 'ดึงข้อมูลบัญชี Google ไม่สำเร็จ กรุณาลองใหม่');
}
if (empty($profile['email_verified'])) {
    jsonFail(401, 'อีเมลของบัญชี Google นี้ยังไม่ได้รับการยืนยัน');
}

$email     = mb_strtolower(trim($profile['email']));
$firstname = trim($profile['given_name']  ?? '');
$lastname  = trim($profile['family_name'] ?? '');
if ($firstname === '') {
    // บางบัญชีไม่แยกชื่อ-นามสกุล ใช้ชื่อเต็มหรือส่วนหน้าอีเมลแทน
    $firstname = trim($profile['name'] ?? '') ?: explode('@', $email)[0];
}

// ---- 3) หา user จาก email ถ้าไม่มีก็สมัครให้อัตโนมัติ ----
$conn = connectOrFailJson();

$stmt = $conn->prepare('SELECT id, username, gender FROM users WHERE email = ?');
if (!$stmt) {
    jsonServerError('google_login', $conn->error);
}
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
    $gender         = 'ไม่ระบุ';

    $stmt = $conn->prepare(
        'INSERT INTO users (username, firstname, lastname, gender, email, password) VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->bind_param('ssssss', $username, $firstname, $lastname, $gender, $email, $randomPassword);
    if (!$stmt->execute()) {
        $err = $stmt->error;
        $stmt->close();
        jsonServerError('google_login insert', $err, 'สร้างบัญชีใหม่ไม่สำเร็จ กรุณาลองใหม่');
    }
    $user = ['id' => $stmt->insert_id, 'username' => $username, 'gender' => $gender];
    $stmt->close();
}

$conn->close();

// ---- 4) set session แบบเดียวกับ login.php ----
session_regenerate_id(true);
$_SESSION['user_id']  = (int)$user['id'];
$_SESSION['username'] = $user['username'];

// need_gender = ยังไม่เคยเลือกเพศ (บัญชีที่เพิ่งสร้างจาก Google จะเป็น 'ไม่ระบุ')
$gender = (string)($user['gender'] ?? '');
jsonOk([
    'username'    => $user['username'],
    'need_gender' => $gender === 'ไม่ระบุ' || $gender === '',
]);
