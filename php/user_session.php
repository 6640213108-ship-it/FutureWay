<?php
// ========================================
// FutureWay - user_session.php
// ของกลางที่ endpoint ฝั่ง "ผู้ใช้ทั่วไป" ใช้ร่วมกัน
// (update_profile.php, change_password.php, get_settings.php,
//  update_settings.php, export_my_data.php, delete_my_data.php)
//
// รวมไว้ที่เดียวเพราะทุกไฟล์ต้องทำเรื่องเดิมซ้ำ ๆ คือ
//   1) หา user_id ของคนที่ล็อกอินอยู่ (session เก่าบางอันมีแค่ username)
//   2) อ่าน/เขียนตาราง user_settings ที่อาจยังไม่ถูก migrate
//   3) ตอบ JSON ภาษาไทยแบบเดียวกันเวลา error
// ========================================

require_once __DIR__ . '/db_config.php';

/**
 * ตอบ error เป็น JSON แล้วจบการทำงานทันที
 */
function jsonFail(int $status, string $message): void {
    http_response_code($status);
    echo json_encode(['success' => false, 'error' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * ตอบสำเร็จเป็น JSON แล้วจบการทำงาน
 */
function jsonOk(array $data = []): void {
    echo json_encode(['success' => true] + $data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * บังคับว่าต้องเรียกด้วย POST + body เป็น JSON แล้วคืนค่าที่ decode แล้ว
 *
 * ที่ต้องบังคับ Content-Type: application/json เพราะฟอร์ม HTML จากเว็บอื่น
 * ตั้ง header นี้ไม่ได้ (ต้องผ่าน CORS preflight ก่อน) จึงกันการยิงข้ามโดเมน
 * มาแก้โปรไฟล์/รหัสผ่านของคนที่ล็อกอินค้างไว้ได้ — เหตุผลเดียวกับ delete_result.php
 */
function requireJsonPost(): array {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonFail(405, 'ต้องเรียกด้วยวิธี POST เท่านั้น');
    }
    // บาง server ไม่ตั้ง $_SERVER['CONTENT_TYPE'] ให้ มีแต่ HTTP_CONTENT_TYPE
    $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
    if (stripos($contentType, 'application/json') === false) {
        jsonFail(415, 'ต้องส่งข้อมูลเป็น JSON');
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        jsonFail(400, 'รูปแบบข้อมูลที่ส่งมาไม่ถูกต้อง');
    }
    return $input;
}

/**
 * เปิด connection พร้อมตอบ JSON ให้เองถ้าต่อ DB ไม่ได้
 */
function connectOrFailJson(): mysqli {
    try {
        return getDbConnection();
    } catch (Exception $e) {
        error_log('user_session.php: ' . $e->getMessage());
        jsonFail(500, 'เชื่อมต่อฐานข้อมูลไม่ได้');
    }
}

/**
 * คืนข้อมูลผู้ใช้ที่ล็อกอินอยู่ทั้งแถว ถ้าไม่ได้ล็อกอินจะตอบ 401 แล้วจบ
 *
 * login.php ปัจจุบัน set ทั้ง user_id และ username แต่ session ที่ค้างมาจาก
 * เวอร์ชันก่อนหน้ามีแค่ username เลยต้อง fallback ไปหาจาก username ให้ด้วย
 * ไม่งั้นคนที่ยังไม่ได้ล็อกเอาต์จะโดนเด้งออกทั้งที่ยังล็อกอินอยู่
 */
function requireUserJson(mysqli $conn): array {
    $userId   = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
    $username = $_SESSION['username'] ?? '';

    if ($userId <= 0 && $username === '') {
        jsonFail(401, 'กรุณาเข้าสู่ระบบก่อน');
    }

    if ($userId > 0) {
        $stmt = $conn->prepare('SELECT * FROM users WHERE id = ?');
        if (!$stmt) {
            jsonFail(500, 'อ่านข้อมูลผู้ใช้ไม่สำเร็จ: ' . $conn->error);
        }
        $stmt->bind_param('i', $userId);
    } else {
        $stmt = $conn->prepare('SELECT * FROM users WHERE username = ?');
        if (!$stmt) {
            jsonFail(500, 'อ่านข้อมูลผู้ใช้ไม่สำเร็จ: ' . $conn->error);
        }
        $stmt->bind_param('s', $username);
    }
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        jsonFail(401, 'ไม่พบบัญชีผู้ใช้นี้ (โปรดเข้าสู่ระบบใหม่)');
    }

    // sync session ให้ครบ เผื่อ session เก่าที่มีแค่ username
    $_SESSION['user_id']  = (int)$row['id'];
    $_SESSION['username'] = $row['username'];

    return $row;
}

/**
 * รายชื่อคอลัมน์ที่มีจริงในตาราง (cache ไว้ต่อ 1 request)
 *
 * ใช้เช็คก่อนแตะคอลัมน์ phone / address เพราะถ้ายังไม่ได้รัน migration 003
 * คอลัมน์พวกนี้จะไม่มี แล้ว UPDATE จะพังทั้งคำสั่ง ทำให้แก้ชื่อไม่ได้ไปด้วย
 */
function tableColumns(mysqli $conn, string $table, bool $refresh = false): array {
    static $cache = [];
    if (!$refresh && isset($cache[$table])) {
        return $cache[$table];
    }

    $cols = [];
    // ชื่อตารางมาจากโค้ดเราเองเท่านั้น ไม่ได้รับจากผู้ใช้ แต่กรองไว้กันพลาด
    $safe = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    if ($res = $conn->query("SHOW COLUMNS FROM `$safe`")) {
        while ($r = $res->fetch_assoc()) {
            $cols[] = $r['Field'];
        }
        $res->free();
    }

    return $cache[$table] = $cols;
}

/**
 * เพิ่มคอลัมน์ phone / address ให้ตาราง users ถ้ายังไม่มี
 *
 * ทำจาก PHP ด้วย เพราะ sql/003 ต้องรันผ่าน migrate.php บน Railway เท่านั้น
 * (เข้า MySQL จากเครื่องตัวเองไม่ได้) และถ้าเอา SQL ไปวางรันมือทีเดียวสองบรรทัด
 * ตัวรัน SQL บางตัวจะฟ้อง syntax error เพราะมันยิงได้ทีละคำสั่ง
 *
 * ยิงทีละ ALTER แยกกัน ถ้าคอลัมน์มีอยู่แล้วก็แค่ข้าม ไม่ทำให้อีกคอลัมน์พังตาม
 */
function ensureUserProfileColumns(mysqli $conn): void {
    static $done = false;
    if ($done) {
        return;
    }

    $cols = tableColumns($conn, 'users');
    $want = [
        'phone'   => "ALTER TABLE `users` ADD COLUMN `phone` varchar(20) DEFAULT NULL",
        'address' => "ALTER TABLE `users` ADD COLUMN `address` varchar(255) DEFAULT NULL",
    ];

    $added = false;
    foreach ($want as $col => $sql) {
        if (!in_array($col, $cols, true)) {
            if ($conn->query($sql)) {
                $added = true;
            } else {
                error_log("user_session.php: เพิ่มคอลัมน์ $col ไม่สำเร็จ: " . $conn->error);
            }
        }
    }

    if ($added) {
        tableColumns($conn, 'users', true);   // อ่านรายชื่อคอลัมน์ใหม่ ทับ cache เดิม
    }

    $done = true;
}

/**
 * ค่าเริ่มต้นของการตั้งค่า — ใช้กับผู้ใช้ที่ยังไม่เคยกดบันทึกในหน้าตั้งค่า
 * key ที่อยู่ในนี้คือ key ทั้งหมดที่ระบบยอมรับ (อย่างอื่นที่ส่งมาจะถูกทิ้ง)
 */
function defaultUserSettings(): array {
    return [
        'notify_result'   => 1,
        'notify_news'     => 1,
        'notify_reminder' => 0,
        'notify_email'    => 1,
        'notify_push'     => 0,
        'show_email'      => 1,
        'allow_stats'     => 1,
    ];
}

/**
 * สร้างตาราง user_settings ถ้ายังไม่มี
 *
 * ทำให้หน้าตั้งค่าใช้ได้ทันทีแม้ยังไม่ได้รัน migrate.php บน Railway
 * (คำสั่งตรงกับ sql/003_user_settings.sql — แก้ที่ไหนต้องแก้ทั้งสองที่)
 */
function ensureUserSettingsTable(mysqli $conn): void {
    static $done = false;
    if ($done) {
        return;
    }
    $conn->query("
        CREATE TABLE IF NOT EXISTS `user_settings` (
            `user_id`         int(11)    NOT NULL,
            `notify_result`   tinyint(1) NOT NULL DEFAULT 1,
            `notify_news`     tinyint(1) NOT NULL DEFAULT 1,
            `notify_reminder` tinyint(1) NOT NULL DEFAULT 0,
            `notify_email`    tinyint(1) NOT NULL DEFAULT 1,
            `notify_push`     tinyint(1) NOT NULL DEFAULT 0,
            `show_email`      tinyint(1) NOT NULL DEFAULT 1,
            `allow_stats`     tinyint(1) NOT NULL DEFAULT 1,
            `updated_at`      timestamp  NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    $done = true;
}

/**
 * อ่านการตั้งค่าของผู้ใช้ 1 คน (ยังไม่มีแถว = ได้ค่าเริ่มต้น)
 * คืนเป็น bool ทุก key เพื่อให้ฝั่งหน้าเว็บเอาไปผูกกับ checkbox ได้ตรง ๆ
 */
function getUserSettings(mysqli $conn, int $userId): array {
    ensureUserSettingsTable($conn);

    $settings = defaultUserSettings();

    $stmt = $conn->prepare('SELECT * FROM user_settings WHERE user_id = ?');
    if ($stmt) {
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        if ($row = $stmt->get_result()->fetch_assoc()) {
            foreach ($settings as $key => $_) {
                if (isset($row[$key])) {
                    $settings[$key] = (int)$row[$key];
                }
            }
        }
        $stmt->close();
    }

    return array_map(fn($v) => (bool)$v, $settings);
}

/**
 * บันทึกการตั้งค่า — รับเฉพาะ key ที่รู้จัก, key ที่ไม่ได้ส่งมาจะคงค่าเดิมไว้
 * ใช้ INSERT ... ON DUPLICATE KEY UPDATE เพราะผู้ใช้ส่วนใหญ่ยังไม่มีแถวในตารางนี้
 */
function saveUserSettings(mysqli $conn, int $userId, array $input): array {
    $current = getUserSettings($conn, $userId);

    $values = [];
    foreach (defaultUserSettings() as $key => $_) {
        $values[$key] = array_key_exists($key, $input)
            ? (int)filter_var($input[$key], FILTER_VALIDATE_BOOLEAN)
            : (int)$current[$key];
    }

    $cols        = array_keys($values);
    $colList     = '`user_id`, `' . implode('`, `', $cols) . '`';
    $placeholders = implode(', ', array_fill(0, count($cols) + 1, '?'));
    $updateList  = implode(', ', array_map(fn($c) => "`$c` = VALUES(`$c`)", $cols));

    $sql  = "INSERT INTO `user_settings` ($colList) VALUES ($placeholders)
             ON DUPLICATE KEY UPDATE $updateList";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        jsonFail(500, 'บันทึกการตั้งค่าไม่สำเร็จ: ' . $conn->error);
    }

    $params = array_merge([$userId], array_values($values));
    $stmt->bind_param(str_repeat('i', count($params)), ...$params);

    if (!$stmt->execute()) {
        $err = $stmt->error;
        $stmt->close();
        jsonFail(500, 'บันทึกการตั้งค่าไม่สำเร็จ: ' . $err);
    }
    $stmt->close();

    return array_map(fn($v) => (bool)$v, $values);
}
