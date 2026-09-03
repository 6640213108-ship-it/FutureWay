<?php
// ========================================
// FutureWay - get_admin_results.php
// ดึงผลการทำแบบทดสอบของผู้ใช้ทุกคน สำหรับหน้าผู้ดูแลระบบ (admin.html)
//
// query string ที่รับได้:
//   q       = ค้นหาจากชื่อ / นามสกุล / username / email
//   gender  = กรองตามเพศ
//   mbti    = กรองตามผล MBTI
//   page    = หน้าที่เท่าไร (เริ่มที่ 1)
// ========================================

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/admin_auth.php';
require_once __DIR__ . '/admin_filter.php';

requireAdminJson();

const PER_PAGE = 20;

$conn = connectOrFailJson();

try {

    $page   = max(1, (int)($_GET['page'] ?? 1));
    $offset = ($page - 1) * PER_PAGE;

    // ---- เงื่อนไขกรอง (ใช้ตัวเดียวกับฝั่ง export เพื่อให้ผลตรงกันเสมอ) ----
    $filter   = buildAdminFilter($_GET);
    $whereSql = $filter['sql'];
    $params   = $filter['params'];
    $types    = $filter['types'];

    // ---- นับจำนวนทั้งหมด (ตามเงื่อนไขที่กรอง) ----
    $stmtC = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM quiz_results qr
        JOIN users u ON u.id = qr.user_id
        $whereSql
    ");
    if (!$stmtC) {
        throw new Exception('Prepare (count) ล้มเหลว: ' . $conn->error);
    }
    if ($types !== '') {
        $stmtC->bind_param($types, ...$params);
    }
    $stmtC->execute();
    $total = (int)($stmtC->get_result()->fetch_assoc()['total'] ?? 0);
    $stmtC->close();

    // ---- ดึงรายการของหน้านี้ ----
    $stmt = $conn->prepare("
        SELECT qr.id, qr.user_id, qr.created_at, qr.input_mode,
               qr.mbti_type, qr.avg_grade, qr.riasec_scores,
               qr.grade_math, qr.grade_sci, qr.grade_eng,
               qr.grade_thai, qr.grade_social, qr.grade_art,
               qr.branch_name, qr.score,
               u.username, u.firstname, u.lastname, u.gender, u.email
        FROM quiz_results qr
        JOIN users u ON u.id = qr.user_id
        $whereSql
        ORDER BY qr.created_at DESC, qr.id DESC
        LIMIT ? OFFSET ?
    ");
    if (!$stmt) {
        throw new Exception('Prepare (list) ล้มเหลว: ' . $conn->error);
    }

    $listParams = $params;
    $listTypes  = $types . 'ii';
    $perPage    = PER_PAGE;
    array_push($listParams, $perPage, $offset);
    $stmt->bind_param($listTypes, ...$listParams);
    $stmt->execute();
    $res = $stmt->get_result();

    $rows = [];
    $ids  = [];
    while ($row = $res->fetch_assoc()) {
        $inputMode = $row['input_mode'] ?? 'grade';

        // โหมด interest ไม่มีเกรดเลย -> grades/avg เป็น null (ไม่ใช่ 0.00 ที่ทำให้เข้าใจผิด)
        $grades = null;
        $avg    = null;
        if ($inputMode !== 'interest' && $row['grade_math'] !== null) {
            $grades = [
                'math'   => (float)$row['grade_math'],
                'sci'    => (float)$row['grade_sci'],
                'eng'    => (float)$row['grade_eng'],
                'thai'   => (float)$row['grade_thai'],
                'social' => (float)$row['grade_social'],
                'art'    => (float)$row['grade_art'],
            ];
            // แถวเก่าก่อน migration 002 ยังไม่มี avg_grade -> คำนวณให้ตอนแสดงผล
            $avg = $row['avg_grade'] !== null
                ? (float)$row['avg_grade']
                : round(array_sum($grades) / count($grades), 2);
        }

        $id     = (int)$row['id'];
        $ids[]  = $id;
        $rows[] = [
            'result_id'  => $id,
            'user_id'    => (int)$row['user_id'],
            'username'   => $row['username'],
            'fullname'   => trim($row['firstname'] . ' ' . $row['lastname']),
            'gender'     => $row['gender'],
            'email'      => $row['email'],
            'mbti'       => $row['mbti_type'],
            'input_mode'    => $inputMode,
            'avg_grade'     => $avg,
            'grades'        => $grades,
            'riasec_scores' => isset($row['riasec_scores']) ? json_decode($row['riasec_scores'], true) : null,
            'top_branch' => $row['branch_name'],
            'top_score'  => $row['score'] !== null ? (float)$row['score'] : null,
            'created_at' => $row['created_at'],
            'top_categories' => [],   // เติมด้านล่าง
        ];
    }
    $stmt->close();

    // ---- ดึงสาขาที่แนะนำ (จัดเป็นหมวดหมู่/คณะ) ของทุกแถวในหน้านี้ ด้วย query เดียว ----
    // (ไม่ยิงทีละแถว เพราะหน้าเดียวมีได้ถึง 20 แถว)
    if ($ids) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmtB = $conn->prepare("
            SELECT result_id, category_rank, rank_no, branch_name, faculty, score
            FROM quiz_result_branches
            WHERE result_id IN ($placeholders)
            ORDER BY result_id, category_rank, rank_no
        ");
        // ถ้าตารางยังไม่ถูกสร้าง prepare จะคืน false -> ข้ามไป หน้าเว็บยังใช้ได้
        if ($stmtB) {
            $stmtB->bind_param(str_repeat('i', count($ids)), ...$ids);
            $stmtB->execute();
            $resB = $stmtB->get_result();

            $byResult = [];
            while ($b = $resB->fetch_assoc()) {
                $rid     = (int)$b['result_id'];
                $catRank = (int)($b['category_rank'] ?? 1);

                if (!isset($byResult[$rid][$catRank])) {
                    $byResult[$rid][$catRank] = [
                        'faculty'    => $b['faculty'],
                        'best_score' => (float)$b['score'],
                        'branches'   => [],
                    ];
                }
                $byResult[$rid][$catRank]['branches'][] = [
                    'name'    => $b['branch_name'],
                    'faculty' => $b['faculty'],
                    'score'   => (float)$b['score'],
                ];
            }
            $stmtB->close();

            foreach ($rows as &$r) {
                $cats = $byResult[$r['result_id']] ?? [];
                ksort($cats);
                $r['top_categories'] = array_values($cats);
            }
            unset($r);
        }
    }

    // ---- สถิติรวม (ของทั้งระบบ ไม่ขึ้นกับตัวกรอง) ----
    $stats = [
        'total_attempts' => 0,
        'total_users'    => 0,
        'by_gender'      => [],
        'top_mbti'       => [],
        'top_branch'     => [],
    ];

    if ($r1 = $conn->query("SELECT COUNT(*) AS attempts, COUNT(DISTINCT user_id) AS users FROM quiz_results")) {
        $s = $r1->fetch_assoc();
        $stats['total_attempts'] = (int)$s['attempts'];
        $stats['total_users']    = (int)$s['users'];
        $r1->free();
    }

    if ($r2 = $conn->query("
        SELECT u.gender, COUNT(*) AS c
        FROM quiz_results qr JOIN users u ON u.id = qr.user_id
        GROUP BY u.gender ORDER BY c DESC
    ")) {
        while ($row = $r2->fetch_assoc()) {
            $stats['by_gender'][] = ['gender' => $row['gender'], 'count' => (int)$row['c']];
        }
        $r2->free();
    }

    if ($r3 = $conn->query("
        SELECT mbti_type, COUNT(*) AS c
        FROM quiz_results GROUP BY mbti_type ORDER BY c DESC LIMIT 5
    ")) {
        while ($row = $r3->fetch_assoc()) {
            $stats['top_mbti'][] = ['mbti' => $row['mbti_type'], 'count' => (int)$row['c']];
        }
        $r3->free();
    }

    if ($r4 = $conn->query("
        SELECT branch_name, COUNT(*) AS c
        FROM quiz_results WHERE branch_name IS NOT NULL
        GROUP BY branch_name ORDER BY c DESC LIMIT 5
    ")) {
        while ($row = $r4->fetch_assoc()) {
            $stats['top_branch'][] = ['name' => $row['branch_name'], 'count' => (int)$row['c']];
        }
        $r4->free();
    }

    // ---- ตัวเลือกสำหรับ dropdown ตัวกรอง ----
    $filters = ['genders' => [], 'mbti' => []];
    if ($r5 = $conn->query("SELECT DISTINCT gender FROM users WHERE gender <> '' ORDER BY gender")) {
        while ($row = $r5->fetch_assoc()) { $filters['genders'][] = $row['gender']; }
        $r5->free();
    }
    if ($r6 = $conn->query("SELECT DISTINCT mbti_type FROM quiz_results ORDER BY mbti_type")) {
        while ($row = $r6->fetch_assoc()) { $filters['mbti'][] = $row['mbti_type']; }
        $r6->free();
    }

    $conn->close();

    jsonOk([
        'results'     => $rows,
        'stats'       => $stats,
        'filters'     => $filters,
        'page'        => $page,
        'per_page'    => PER_PAGE,
        'total'       => $total,
        'total_pages' => (int)ceil($total / PER_PAGE),
    ]);

} catch (Throwable $e) {
    jsonServerError('get_admin_results', $e->getMessage());
}
