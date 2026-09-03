<?php
// ========================================
// FutureWay - migrate.php
// ตัวรัน migration SQL บน Railway (ที่เข้า MySQL ผ่าน mysql.railway.internal
// ได้จากใน container เท่านั้น เลยรันจาก phpMyAdmin ในเครื่องไม่ได้)
//
// วิธีใช้:
//   1) ตั้ง environment variable บน Railway:  MIGRATE_TOKEN = <ข้อความลับที่เดายาก>
//   2) deploy แล้วเปิด:  https://<app>.up.railway.app/php/migrate.php?token=<MIGRATE_TOKEN>
//   3) รันเสร็จแล้ว ลบไฟล์นี้ออกจาก repo (หรือลบ MIGRATE_TOKEN ทิ้ง) เพื่อความปลอดภัย
//
// รันซ้ำได้ปลอดภัย: ไฟล์ไหนรันไปแล้วจะถูกข้าม (ดูตาราง schema_migrations)
// และคำสั่งที่ "ทำไปแล้ว" (ตาราง/คอลัมน์มีอยู่แล้ว) จะถูกนับเป็น skipped ไม่ใช่ error
// ========================================

header('Content-Type: application/json; charset=utf-8');

// ---- ตรวจ token ----
$expected = getenv('MIGRATE_TOKEN');
$given    = $_GET['token'] ?? '';

if (!$expected) {
    http_response_code(503);
    echo json_encode([
        'success' => false,
        'error'   => 'ยังไม่ได้ตั้ง environment variable MIGRATE_TOKEN บน Railway'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
if (!is_string($given) || !hash_equals($expected, $given)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'token ไม่ถูกต้อง'], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once __DIR__ . '/db_config.php';

// error code ของ MySQL ที่แปลว่า "สิ่งนี้มีอยู่แล้ว" -> ถือว่าผ่าน ไม่ใช่ error
const ALREADY_APPLIED = [
    1050, // table already exists
    1060, // duplicate column name
    1061, // duplicate key name
    1022, // duplicate key (FK)
    1826, // duplicate foreign key constraint name
    1091, // can't DROP; check that it exists
    1062, // duplicate entry (เช่น INSERT ที่มี PK/unique key ซ้ำกับแถวที่มีอยู่แล้ว)
    1068, // multiple primary key defined (ตารางมี PRIMARY KEY อยู่แล้ว)
];

/**
 * แยกไฟล์ SQL เป็นคำสั่งย่อยทีละคำสั่ง
 * - ตัดคอมเมนต์ `-- ...` ออก (แต่ไม่ตัดถ้า -- อยู่ในเครื่องหมายคำพูด)
 * - แยกด้วย ; ที่อยู่นอกเครื่องหมายคำพูดเท่านั้น
 */
function splitSqlStatements(string $sql): array {
    $statements = [];
    $current    = '';
    $quote      = null;   // ' หรือ " หรือ ` ที่กำลังเปิดค้างอยู่
    $len        = strlen($sql);

    for ($i = 0; $i < $len; $i++) {
        $ch = $sql[$i];

        if ($quote !== null) {
            $current .= $ch;
            if ($ch === '\\' && $i + 1 < $len) {   // escape ในสตริง
                $current .= $sql[++$i];
            } elseif ($ch === $quote) {
                $quote = null;
            }
            continue;
        }

        // คอมเมนต์ -- จนจบบรรทัด
        if ($ch === '-' && $i + 1 < $len && $sql[$i + 1] === '-') {
            while ($i < $len && $sql[$i] !== "\n") { $i++; }
            $current .= "\n";
            continue;
        }

        if ($ch === "'" || $ch === '"' || $ch === '`') {
            $quote = $ch;
            $current .= $ch;
            continue;
        }

        if ($ch === ';') {
            if (trim($current) !== '') { $statements[] = trim($current); }
            $current = '';
            continue;
        }

        $current .= $ch;
    }

    if (trim($current) !== '') { $statements[] = trim($current); }
    return $statements;
}

try {
    $conn = getDbConnection();

    // ตารางบันทึกว่า migration ไฟล์ไหนรันไปแล้ว
    $conn->query("
        CREATE TABLE IF NOT EXISTS `schema_migrations` (
            `filename`   varchar(190) NOT NULL,
            `applied_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`filename`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $applied = [];
    if ($res = $conn->query("SELECT filename FROM schema_migrations")) {
        while ($row = $res->fetch_assoc()) { $applied[$row['filename']] = true; }
        $res->free();
    }

    $sqlDir = dirname(__DIR__) . '/sql';
    $files  = glob($sqlDir . '/*.sql') ?: [];
    sort($files);   // รันตามลำดับเลขหน้าไฟล์: 001_, 002_, ...

    $report = [];

    foreach ($files as $path) {
        $name = basename($path);

        if (isset($applied[$name])) {
            $report[] = ['file' => $name, 'status' => 'already_applied'];
            continue;
        }

        $statements = splitSqlStatements(file_get_contents($path));
        $ok = $skipped = 0;
        $errors = [];

        foreach ($statements as $stmt) {
            if ($conn->query($stmt)) {
                $ok++;
            } elseif (in_array($conn->errno, ALREADY_APPLIED, true)) {
                $skipped++;   // มีอยู่แล้ว ถือว่าผ่าน
            } else {
                $errors[] = [
                    'errno'     => $conn->errno,
                    'error'     => $conn->error,
                    'statement' => substr(preg_replace('/\s+/', ' ', $stmt), 0, 200),
                ];
            }
        }

        if (!$errors) {
            $mark = $conn->prepare("INSERT IGNORE INTO schema_migrations (filename) VALUES (?)");
            $mark->bind_param('s', $name);
            $mark->execute();
            $mark->close();
        }

        $report[] = [
            'file'       => $name,
            'status'     => $errors ? 'failed' : 'applied',
            'executed'   => $ok,
            'skipped'    => $skipped,
            'errors'     => $errors,
        ];
    }

    $conn->close();

    $failed = array_filter($report, fn($r) => ($r['status'] ?? '') === 'failed');
    echo json_encode([
        'success'    => empty($failed),
        'migrations' => $report,
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
