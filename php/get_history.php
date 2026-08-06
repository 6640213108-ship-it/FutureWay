<?php
// ========================================
// FutureWay - get_history.php
// ดึงประวัติผลลัพธ์ของผู้ใช้ที่ login อยู่ (ใหม่ล่าสุดก่อน)
// ใช้โดย History_Results.html
//
// query string:
//   limit = จำนวนรอบที่ต้องการ (ค่าเริ่มต้น 3 = 3 รอบล่าสุด, สูงสุด 50)
// ========================================

session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'กรุณาเข้าสู่ระบบก่อน'], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once __DIR__ . '/db_config.php';

try {
    $conn = getDbConnection();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'DB connection failed: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
}

$limit  = isset($_GET['limit']) ? max(1, min(50, (int)$_GET['limit'])) : 3;
$userId = $_SESSION['user_id'];

// นับจำนวนรอบทั้งหมดที่เคยทำ เพื่อบอกผู้ใช้ว่ากำลังดูแค่ส่วนหนึ่ง
$totalRounds = 0;
$stmtT = $conn->prepare("SELECT COUNT(*) AS c FROM quiz_results WHERE user_id = ?");
if ($stmtT) {
    $stmtT->bind_param('i', $userId);
    $stmtT->execute();
    $totalRounds = (int)($stmtT->get_result()->fetch_assoc()['c'] ?? 0);
    $stmtT->close();
}

$stmt = $conn->prepare("
    SELECT qr.id, qr.mbti_type, qr.avg_grade, qr.created_at,
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
    echo json_encode(['success' => false, 'error' => 'Prepare ล้มเหลว: ' . $conn->error], JSON_UNESCAPED_UNICODE);
    exit;
}
$stmt->bind_param('ii', $userId, $limit);
$stmt->execute();
$result = $stmt->get_result();

$history = [];
$ids     = [];
while ($row = $result->fetch_assoc()) {
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

    $id    = (int)$row['id'];
    $ids[] = $id;

    $history[] = [
        'id'          => $id,
        'mbti_type'   => $row['mbti_type'],
        'avg_grade'   => $avg,
        'grades'      => $grades,
        'branch_name' => $row['branch_name'],
        'faculty'     => $row['faculty'],
        'description' => $row['description'],
        'score'       => $row['score'] !== null ? (float)$row['score'] : null,
        'created_at'  => $row['created_at'],
        'top3'        => [],   // เติมด้านล่าง
    ];
}
$stmt->close();

// ดึงสาขาที่แนะนำครบ 3 อันดับของทุกรอบในครั้งเดียว
// (ถ้าตารางยังไม่ถูกสร้าง prepare จะคืน false -> ข้ามไป หน้าเว็บยังแสดงอันดับ 1 ได้)
if ($ids) {
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmtB = $conn->prepare("
        SELECT result_id, rank_no, branch_name, faculty, description, score
        FROM quiz_result_branches
        WHERE result_id IN ($placeholders)
        ORDER BY result_id, rank_no
    ");
    if ($stmtB) {
        $stmtB->bind_param(str_repeat('i', count($ids)), ...$ids);
        $stmtB->execute();
        $resB = $stmtB->get_result();

        $byResult = [];
        while ($b = $resB->fetch_assoc()) {
            $byResult[(int)$b['result_id']][] = [
                'rank'        => (int)$b['rank_no'],
                'name'        => $b['branch_name'],
                'faculty'     => $b['faculty'],
                'description' => $b['description'],
                'score'       => (float)$b['score'],
            ];
        }
        $stmtB->close();

        foreach ($history as &$h) {
            $h['top3'] = $byResult[$h['id']] ?? [];
        }
        unset($h);
    }
}

$conn->close();

echo json_encode([
    'success'      => true,
    'history'      => $history,
    'limit'        => $limit,
    'total_rounds' => $totalRounds,
], JSON_UNESCAPED_UNICODE);
