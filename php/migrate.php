<?php
// ========================================
// FutureWay - migrate.php
// ตัวรัน migration SQL บน Railway (ที่เข้า MySQL ผ่าน mysql.railway.internal
// ได้จากใน container เท่านั้น เลยรันจาก phpMyAdmin ในเครื่องไม่ได้)
//
// วิธีใช้:
//   ในเครื่อง / ใน container:   php php/migrate.php
//   ผ่านเว็บ (Railway):         ตั้ง MIGRATE_TOKEN เป็นข้อความลับที่เดายาก แล้ว
//       curl -X POST -H "Authorization: Bearer $MIGRATE_TOKEN" https://<app>/php/migrate.php
//   (รับเฉพาะ POST + token ใน header เพื่อไม่ให้ token ไปโผล่ใน access log)
//
// รันซ้ำได้ปลอดภัย: ไฟล์ไหนรันไปแล้วจะถูกข้าม (ดูตาราง schema_migrations)
// และคำสั่งที่ "ทำไปแล้ว" (ตาราง/คอลัมน์มีอยู่แล้ว) จะถูกนับเป็น skipped ไม่ใช่ error
// รันเฉพาะไฟล์ชื่อ NNN_*.sql ในโฟลเดอร์ sql/ เรียงตามเลขหน้าไฟล์
// ========================================

require_once __DIR__ . '/bootstrap.php';

if (PHP_SAPI !== 'cli') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonFail(405, 'ต้องเรียกด้วยวิธี POST เท่านั้น');
    }

    $expected = getenv('MIGRATE_TOKEN');
    if (!$expected) {
        jsonFail(503, 'ยังไม่ได้ตั้ง environment variable MIGRATE_TOKEN');
    }

    $auth  = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    $given = preg_match('/^Bearer\s+(.+)$/i', $auth, $m) ? trim($m[1]) : '';
    if ($given === '' || !hash_equals($expected, $given)) {
        jsonFail(403, 'token ไม่ถูกต้อง');
    }
}

// error code ของ MySQL ที่แปลว่า "สิ่งนี้มีอยู่แล้ว" -> ถือว่าผ่าน ไม่ใช่ error
const ALREADY_APPLIED = [
    1050, // table already exists
    1060, // duplicate column name
    1061, // duplicate key name
    1022, // duplicate key (FK)
    1826, // duplicate foreign key constraint name
    1091, // can't DROP; check that it exists
    1068, // multiple primary key defined (ตารางมี PRIMARY KEY อยู่แล้ว — เป็นไปได้แค่แบบเดียวเสมอ)
    // ไม่ใส่ 1062 (duplicate entry) ไว้ที่นี่ — ความหมายกำกวมเกินไป: INSERT ที่ชน
    // PK เดิมตรงๆ ปลอดภัยที่จะข้าม แต่ ALTER ... ADD UNIQUE KEY ที่ชน 1062 แปลว่า
    // "ข้อมูลที่มีอยู่จริงละเมิดกฎที่กำลังจะเพิ่ม" ซึ่งเป็นปัญหาจริงที่ต้องเห็น
    // ไม่ควรถูกกลืนเงียบๆ (ดู sql/010_dedupe_branches.sql)
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
    $files  = array_values(array_filter(
        glob($sqlDir . '/*.sql') ?: [],
        fn($f) => preg_match('/^\d{3}_.+\.sql$/', basename($f)) === 1
    ));
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
    jsonServerError('migrate', $e->getMessage(), 'รัน migration ไม่สำเร็จ (ดูรายละเอียดใน log)');
}
