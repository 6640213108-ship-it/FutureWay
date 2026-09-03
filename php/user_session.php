<?php
// ========================================
// FutureWay - user_session.php
// ของกลางของ endpoint ฝั่ง "ผู้ใช้ทั่วไป": อ่านแถวผู้ใช้ที่ล็อกอินอยู่
// และอ่าน/เขียนตาราง user_settings
//
// ต้อง require bootstrap.php มาก่อน (session + helper ตอบ JSON)
// โครงสร้างตารางอยู่ใน sql/ เท่านั้น (ไม่มีการสร้าง/แก้ตารางจากในนี้)
// ========================================

/**
 * คืนข้อมูลผู้ใช้ที่ล็อกอินอยู่ทั้งแถว ถ้าไม่ได้ล็อกอินจะตอบ 401 แล้วจบ
 *
 * session ปกติมีทั้ง user_id และ username แต่ session ที่ค้างมาจากเวอร์ชันเก่า
 * อาจมีแค่ username จึง fallback ไปหาจาก username ให้ด้วย
 */
function requireUserJson(mysqli $conn): array {
    $userId   = currentUserId();
    $username = $_SESSION['username'] ?? '';

    if ($userId <= 0 && $username === '') {
        jsonFail(401, 'กรุณาเข้าสู่ระบบก่อน');
    }

    $sql  = $userId > 0 ? 'SELECT * FROM users WHERE id = ?' : 'SELECT * FROM users WHERE username = ?';
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        jsonServerError('user_session', $conn->error);
    }
    if ($userId > 0) {
        $stmt->bind_param('i', $userId);
    } else {
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
 * ค่าเริ่มต้นของการตั้งค่า — ใช้กับผู้ใช้ที่ยังไม่เคยกดบันทึกในหน้าตั้งค่า
 * key ในนี้คือ key ทั้งหมดที่ระบบยอมรับ (อย่างอื่นที่ส่งมาจะถูกทิ้ง)
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
 * อ่านการตั้งค่าของผู้ใช้ 1 คน (ยังไม่มีแถว = ได้ค่าเริ่มต้น)
 * คืนเป็น bool ทุก key เพื่อให้ฝั่งหน้าเว็บผูกกับ checkbox ได้ตรง ๆ
 */
function getUserSettings(mysqli $conn, int $userId): array {
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

    $cols         = array_keys($values);
    $colList      = '`user_id`, `' . implode('`, `', $cols) . '`';
    $placeholders = implode(', ', array_fill(0, count($cols) + 1, '?'));
    $updateList   = implode(', ', array_map(fn($c) => "`$c` = VALUES(`$c`)", $cols));

    $stmt = $conn->prepare("INSERT INTO `user_settings` ($colList) VALUES ($placeholders)
                            ON DUPLICATE KEY UPDATE $updateList");
    if (!$stmt) {
        jsonServerError('saveUserSettings', $conn->error, 'บันทึกการตั้งค่าไม่สำเร็จ');
    }

    $params = array_merge([$userId], array_values($values));
    $stmt->bind_param(str_repeat('i', count($params)), ...$params);

    if (!$stmt->execute()) {
        $err = $stmt->error;
        $stmt->close();
        jsonServerError('saveUserSettings', $err, 'บันทึกการตั้งค่าไม่สำเร็จ');
    }
    $stmt->close();

    return array_map(fn($v) => (bool)$v, $values);
}
