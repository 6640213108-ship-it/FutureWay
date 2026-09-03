<?php
// ========================================
// FutureWay - export_admin_results.php
// ส่งออกผลการทำแบบทดสอบทั้งหมดเป็นไฟล์ Excel (.xlsx) — สำหรับผู้ดูแลระบบเท่านั้น
//
// query string:
//   scope = all       (ค่าเริ่มต้น) ส่งออกทุกแถวในระบบ ไม่สนตัวกรอง
//         = filtered  ส่งออกเฉพาะที่ตรงกับตัวกรองบนหน้าเว็บ
//   q, gender, mbti   ตัวกรอง — ใช้เมื่อ scope=filtered (ชุดเดียวกับ get_admin_results.php)
//
// ไฟล์ที่ได้มี 2 ชีต
//   1) ผลการทำแบบทดสอบ — 1 แถวต่อ 1 รอบ พร้อมเกรดรายวิชาและหมวดหมู่ (คณะ) ที่แนะนำสูงสุด 3 หมวด
//   2) สรุปสถิติ        — ยอดรวม + แยกตามเพศ / MBTI / สาขา
//
// ไม่แบ่งหน้าเหมือน get_admin_results.php เพราะจุดประสงค์คือเอาข้อมูลไปทำต่อ
// ต้องได้ครบทุกแถวในไฟล์เดียว
// ========================================

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/admin_auth.php';
require_once __DIR__ . '/admin_filter.php';
require_once __DIR__ . '/xlsx_writer.php';

// ตรวจสิทธิ์ก่อนทำอะไรทั้งสิ้น — ไม่ผ่านจะตอบ JSON 401/403 แล้วจบ
requireAdminJson();

// ไฟล์นี้ส่งข้อมูล binary ออกไป — buffer ไว้ก่อน ถ้ามีอะไรหลุดออกมาก่อนตัวไฟล์
// (notice/warning) จะถูกทิ้งด้วย ob_clean ไม่ให้ปนเข้าไปจน Excel เปิดไม่ขึ้น
ob_start();

/** ชื่อหัวคอลัมน์ของเกรดแต่ละวิชา */
const GRADE_LABELS = [
    'grade_math'   => 'คณิตศาสตร์',
    'grade_sci'    => 'วิทยาศาสตร์',
    'grade_eng'    => 'ภาษาอังกฤษ',
    'grade_thai'   => 'ภาษาไทย',
    'grade_social' => 'สังคมศึกษา',
    'grade_art'    => 'ศิลปะ',
];

$conn = connectOrFailJson();

try {
    $scope = ($_GET['scope'] ?? 'all') === 'filtered' ? 'filtered' : 'all';

    // scope=all แปลว่าไม่กรองอะไรเลย ต่อให้หน้าเว็บส่ง q/gender/mbti ติดมาก็ไม่สน
    $filter   = $scope === 'filtered' ? buildAdminFilter($_GET) : ['sql' => '', 'params' => [], 'types' => '', 'applied' => []];
    $whereSql = $filter['sql'];

    // ---- ดึงผลทุกแถว ----
    $sql = "
        SELECT qr.id, qr.user_id, qr.created_at, qr.input_mode,
               qr.mbti_type, qr.avg_grade,
               qr.grade_math, qr.grade_sci, qr.grade_eng,
               qr.grade_thai, qr.grade_social, qr.grade_art,
               qr.branch_name, qr.score,
               u.username, u.firstname, u.lastname, u.gender, u.email
        FROM quiz_results qr
        JOIN users u ON u.id = qr.user_id
        $whereSql
        ORDER BY qr.created_at DESC, qr.id DESC
    ";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Prepare (export) ล้มเหลว: ' . $conn->error);
    }
    if ($filter['types'] !== '') {
        $stmt->bind_param($filter['types'], ...$filter['params']);
    }
    $stmt->execute();
    $res = $stmt->get_result();

    $rows = [];
    $ids  = [];
    while ($row = $res->fetch_assoc()) {
        $id        = (int)$row['id'];
        $ids[]     = $id;
        $rows[$id] = $row;
    }
    $stmt->close();

    // ---- สาขาที่แนะนำ จัดเป็นหมวดหมู่ (คณะ) สูงสุด 3 หมวด ดึงทีเดียวทุกแถว ไม่ยิงทีละรอบ ----
    $categoriesByResult = [];
    if ($ids) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmtB = $conn->prepare("
            SELECT result_id, category_rank, rank_no, branch_name, faculty, score
            FROM quiz_result_branches
            WHERE result_id IN ($placeholders)
            ORDER BY result_id, category_rank, rank_no
        ");
        if ($stmtB) {
            $stmtB->bind_param(str_repeat('i', count($ids)), ...$ids);
            $stmtB->execute();
            $resB = $stmtB->get_result();
            while ($b = $resB->fetch_assoc()) {
                $rid     = (int)$b['result_id'];
                $catRank = (int)($b['category_rank'] ?? 1);

                if (!isset($categoriesByResult[$rid][$catRank])) {
                    $categoriesByResult[$rid][$catRank] = [
                        'faculty' => $b['faculty'],
                        'names'   => [],
                    ];
                }
                $categoriesByResult[$rid][$catRank]['names'][] = $b['branch_name'] . ' (' . (float)$b['score'] . '%)';
            }
            $stmtB->close();
        }
        // ตาราง quiz_result_branches ยังไม่มีในบาง DB (ก่อน migration 002)
        // prepare ไม่ผ่านก็ปล่อยว่างไว้ แล้วไปใช้ branch_name อันดับ 1 ที่อยู่ใน quiz_results แทน
    }

    // ---- ชีตที่ 1: ผลการทำแบบทดสอบ ----
    $header = array_merge(
        ['ลำดับ', 'วันที่ทำ', 'ชื่อผู้ใช้', 'ชื่อ-นามสกุล', 'เพศ', 'อีเมล', 'ผล MBTI', 'ประเภทข้อมูล', 'เกรดเฉลี่ย'],
        array_values(GRADE_LABELS),
        ['หมวดหมู่ 1 (คณะ)', 'สาขาแนะนำในหมวดหมู่ 1',
         'หมวดหมู่ 2 (คณะ)', 'สาขาแนะนำในหมวดหมู่ 2',
         'หมวดหมู่ 3 (คณะ)', 'สาขาแนะนำในหมวดหมู่ 3']
    );

    $sheet1 = [$header];
    $no     = 0;

    foreach ($rows as $r) {
        $no++;
        $inputMode  = $r['input_mode'] ?? 'grade';
        $isInterest = $inputMode === 'interest' || $r['grade_math'] === null;

        // โหมด interest ไม่มีเกรดเลย -> ปล่อยช่องว่างไว้ ไม่ใส่ 0.00 ที่ทำให้เข้าใจผิดว่าสอบตกทุกวิชา
        $grades = [];
        $sum    = 0;
        foreach (array_keys(GRADE_LABELS) as $col) {
            $g        = $isInterest ? '' : (float)$r[$col];
            $grades[] = $g;
            $sum     += $isInterest ? 0 : $g;
        }

        if ($isInterest) {
            $avg = '';
        } elseif ($r['avg_grade'] !== null) {
            $avg = (float)$r['avg_grade'];
        } else {
            // แถวเก่าก่อน migration 002 ไม่มี avg_grade เก็บไว้ -> คำนวณให้ตอน export
            $avg = round($sum / count(GRADE_LABELS), 2);
        }

        $categoryCells = [];
        $cats = $categoriesByResult[(int)$r['id']] ?? [];
        ksort($cats);
        $cats = array_values($cats);

        for ($catIdx = 0; $catIdx < 3; $catIdx++) {
            $cat = $cats[$catIdx] ?? null;

            if ($cat) {
                $categoryCells[] = (string)($cat['faculty'] ?? '');
                $categoryCells[] = implode('; ', $cat['names']);
            } elseif ($catIdx === 0 && $r['branch_name'] !== null) {
                // ผลรอบเก่าที่เก็บไว้แค่อันดับ 1 เดี่ยวๆ (ก่อน migration 009)
                $categoryCells[] = '';
                $categoryCells[] = (string)$r['branch_name'] . ($r['score'] !== null ? ' (' . (float)$r['score'] . '%)' : '');
            } else {
                $categoryCells[] = '';
                $categoryCells[] = '';
            }
        }

        $sheet1[] = array_merge(
            [
                $no,
                // รูปแบบ YYYY-MM-DD HH:MM เรียงลำดับใน Excel ได้ถูกต้องแม้เป็นข้อความ
                substr((string)$r['created_at'], 0, 16),
                (string)$r['username'],
                trim($r['firstname'] . ' ' . $r['lastname']),
                (string)$r['gender'],
                (string)$r['email'],
                (string)$r['mbti_type'],
                $isInterest ? 'ความสนใจ/งานอดิเรก' : 'เกรด',
                $avg,
            ],
            $grades,
            $categoryCells
        );
    }

    if (count($sheet1) === 1) {
        $sheet1[] = ['ไม่มีข้อมูลที่ตรงกับเงื่อนไข'];
    }

    // ---- ชีตที่ 2: สรุปสถิติ ----
    // สถิติคิดจากทั้งระบบเสมอ (ไม่ขึ้นกับตัวกรอง) เหมือนการ์ดสถิติบนหน้าเว็บ
    $sheet2 = [
        ['รายการ', 'ค่า'],
        ['วันที่ส่งออก', date('Y-m-d H:i')],
        ['ส่งออกโดย', (string)($_SESSION['username'] ?? '')],
        ['ขอบเขตข้อมูล', $scope === 'all' ? 'ทั้งหมดในระบบ' : 'เฉพาะที่ตรงกับตัวกรอง'],
        ['จำนวนแถวในไฟล์นี้', $no],
    ];

    foreach ($filter['applied'] as $label => $value) {
        $sheet2[] = ['ตัวกรอง: ' . $label, $value];
    }

    $sheet2[] = ['', ''];

    if ($r1 = $conn->query('SELECT COUNT(*) AS attempts, COUNT(DISTINCT user_id) AS users FROM quiz_results')) {
        $s = $r1->fetch_assoc();
        $sheet2[] = ['จำนวนครั้งที่ทำแบบทดสอบทั้งระบบ', (int)$s['attempts']];
        $sheet2[] = ['จำนวนผู้ใช้ที่เคยทำ', (int)$s['users']];
        $r1->free();
    }
    if ($rU = $conn->query('SELECT COUNT(*) AS c FROM users')) {
        $sheet2[] = ['จำนวนผู้ใช้ทั้งหมดในระบบ', (int)$rU->fetch_assoc()['c']];
        $rU->free();
    }

    // กลุ่มสถิติที่เหลือ: หัวข้อ + รายการย่อย
    $groups = [
        'แยกตามเพศ' => '
            SELECT u.gender AS k, COUNT(*) AS c
            FROM quiz_results qr JOIN users u ON u.id = qr.user_id
            GROUP BY u.gender ORDER BY c DESC',
        'แยกตามผล MBTI' => '
            SELECT mbti_type AS k, COUNT(*) AS c
            FROM quiz_results GROUP BY mbti_type ORDER BY c DESC',
        'สาขาที่ถูกแนะนำ (อันดับ 1)' => '
            SELECT branch_name AS k, COUNT(*) AS c
            FROM quiz_results WHERE branch_name IS NOT NULL
            GROUP BY branch_name ORDER BY c DESC',
    ];

    foreach ($groups as $title => $query) {
        $sheet2[] = ['', ''];
        $sheet2[] = [$title, 'จำนวนครั้ง'];
        if ($r = $conn->query($query)) {
            while ($row = $r->fetch_assoc()) {
                $sheet2[] = [(string)($row['k'] === '' || $row['k'] === null ? 'ไม่ระบุ' : $row['k']), (int)$row['c']];
            }
            $r->free();
        }
    }

    $conn->close();

    $xlsx = buildXlsx([
        [
            'name'   => 'ผลการทำแบบทดสอบ',
            'rows'   => $sheet1,
            'widths' => [7, 17, 14, 24, 8, 26, 10, 16, 11,
                         12, 12, 12, 11, 12, 9,
                         22, 42, 22, 42, 22, 42],
        ],
        [
            'name'   => 'สรุปสถิติ',
            'rows'   => $sheet2,
            'widths' => [34, 18],
        ],
    ]);

    $filename = 'FutureWay-ผลแบบทดสอบ-' . date('Ymd-Hi') . '.xlsx';

    // ทิ้งอะไรก็ตามที่หลุดออกมาก่อนหน้า ให้เหลือแต่ตัวไฟล์ล้วน ๆ
    ob_clean();

    // ทิ้ง header JSON ที่ตั้งไว้ตอนต้นไฟล์ แล้วส่ง header ของไฟล์ Excel แทน
    header_remove('Content-Type');
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    // ใส่ทั้ง filename (ASCII สำรอง) และ filename* (UTF-8) เพราะชื่อไฟล์มีภาษาไทย
    header('Content-Disposition: attachment; filename="FutureWay-quiz-results-' . date('Ymd-Hi') . '.xlsx"; '
         . "filename*=UTF-8''" . rawurlencode($filename));
    header('Content-Length: ' . strlen($xlsx));
    header('Cache-Control: no-store');

    echo $xlsx;

} catch (Throwable $e) {
    ob_clean();   // เคลียร์ output ที่ค้างอยู่ ให้เหลือแต่ JSON ที่หน้าเว็บอ่านได้
    jsonServerError('export_admin_results', $e->getMessage(), 'สร้างไฟล์ Excel ไม่สำเร็จ');
}
