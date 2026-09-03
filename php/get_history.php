<?php
// ========================================
// FutureWay - get_history.php
// ดึงประวัติผลลัพธ์ของผู้ใช้ที่ล็อกอินอยู่ (ใหม่ล่าสุดก่อน) ใช้โดย history.html
//
// query string:
//   limit = จำนวนรอบที่ต้องการ (ค่าเริ่มต้น 3, สูงสุด 50)
// ========================================

require_once __DIR__ . '/bootstrap.php';

$userId = requireLoginJson();
$limit  = isset($_GET['limit']) ? max(1, min(50, (int)$_GET['limit'])) : 3;

$conn = connectOrFailJson();

// นับจำนวนรอบทั้งหมดที่เคยทำ เพื่อบอกผู้ใช้ว่ากำลังดูแค่ส่วนหนึ่ง
$totalRounds = 0;
$stmtT = $conn->prepare('SELECT COUNT(*) AS c FROM quiz_results WHERE user_id = ?');
if ($stmtT) {
    $stmtT->bind_param('i', $userId);
    $stmtT->execute();
    $totalRounds = (int)($stmtT->get_result()->fetch_assoc()['c'] ?? 0);
    $stmtT->close();
}

$stmt = $conn->prepare("
    SELECT qr.id, qr.input_mode, qr.mbti_type, qr.avg_grade, qr.created_at,
           qr.grade_math, qr.grade_sci, qr.grade_eng,
           qr.grade_thai, qr.grade_social, qr.grade_art,
           qr.branch_id, qr.branch_name, qr.score,
           b.faculty, b.description
    FROM quiz_results qr
    LEFT JOIN branches b ON qr.branch_id = b.id
    WHERE qr.user_id = ?
    ORDER BY qr.created_at DESC, qr.id DESC
    LIMIT ?
");
if (!$stmt) {
    jsonServerError('get_history', $conn->error);
}
$stmt->bind_param('ii', $userId, $limit);
$stmt->execute();
$result = $stmt->get_result();

$history = [];
$ids     = [];
while ($row = $result->fetch_assoc()) {
    $inputMode = $row['input_mode'] ?? 'grade';

    // โหมด interest ไม่มีเกรดเลย (grade_* เป็น NULL ทั้งชุด) -> grades/avg เป็น null
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

    $id    = (int)$row['id'];
    $ids[] = $id;

    $history[] = [
        'id'             => $id,
        'input_mode'     => $inputMode,
        'mbti_type'      => $row['mbti_type'],
        'avg_grade'      => $avg,
        'grades'         => $grades,
        'branch_name'    => $row['branch_name'],
        'faculty'        => $row['faculty'],
        'description'    => $row['description'],
        'score'          => $row['score'] !== null ? (float)$row['score'] : null,
        'created_at'     => $row['created_at'],
        'top_categories' => [],   // เติมด้านล่าง
    ];
}
$stmt->close();

// ดึงสาขาที่แนะนำ (จัดเป็นหมวดหมู่/คณะ) ของทุกรอบในครั้งเดียว
if ($ids) {
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmtB = $conn->prepare("
        SELECT result_id, category_rank, rank_no, branch_name, faculty, description, score
        FROM quiz_result_branches
        WHERE result_id IN ($placeholders)
        ORDER BY result_id, category_rank, rank_no
    ");
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
                'name'        => $b['branch_name'],
                'faculty'     => $b['faculty'],
                'description' => $b['description'],
                'score'       => (float)$b['score'],
            ];
        }
        $stmtB->close();

        foreach ($history as &$h) {
            $cats = $byResult[$h['id']] ?? [];
            ksort($cats);
            $h['top_categories'] = array_values($cats);
        }
        unset($h);
    }
}

$conn->close();

jsonOk([
    'history'      => $history,
    'limit'        => $limit,
    'total_rounds' => $totalRounds,
]);
