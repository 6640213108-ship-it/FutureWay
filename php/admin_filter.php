<?php
// ========================================
// FutureWay - admin_filter.php
// สร้างเงื่อนไข WHERE ของหน้าผู้ดูแลระบบ ใช้ร่วมกันระหว่าง
//   - get_admin_results.php  (ตารางบนหน้าเว็บ)
//   - export_admin_results.php (ไฟล์ Excel)
//
// แยกมาไว้ที่เดียวเพราะถ้าต่างคนต่างเขียนเงื่อนไขเอง วันหนึ่งที่แก้ตัวกรอง
// ของหน้าเว็บแล้วลืมแก้ฝั่ง export ไฟล์ที่ได้จะไม่ตรงกับที่แอดมินเห็นบนจอ
// ซึ่งเป็นบั๊กที่จับได้ยากมาก เพราะทั้งสองฝั่ง "ทำงานได้" ทั้งคู่
// ========================================

/**
 * แปลง query string เป็นชิ้นส่วนของ SQL
 *
 * @param array $get  ปกติคือ $_GET
 * @return array{sql:string, params:array, types:string, applied:array}
 *                    applied = ตัวกรองที่ใช้จริง (ไว้เขียนกำกับในไฟล์ Excel)
 */
function buildAdminFilter(array $get): array {
    $q      = trim((string)($get['q']      ?? ''));
    $gender = trim((string)($get['gender'] ?? ''));
    $mbti   = strtoupper(trim((string)($get['mbti'] ?? '')));

    $where   = [];
    $params  = [];
    $types   = '';
    $applied = [];

    if ($q !== '') {
        $like    = '%' . $q . '%';
        $where[] = '(u.firstname LIKE ? OR u.lastname LIKE ? OR u.username LIKE ? OR u.email LIKE ?)';
        array_push($params, $like, $like, $like, $like);
        $types           .= 'ssss';
        $applied['คำค้นหา'] = $q;
    }
    if ($gender !== '') {
        $where[]        = 'u.gender = ?';
        $params[]       = $gender;
        $types         .= 's';
        $applied['เพศ'] = $gender;
    }
    if ($mbti !== '') {
        $where[]         = 'qr.mbti_type = ?';
        $params[]        = $mbti;
        $types          .= 's';
        $applied['MBTI'] = $mbti;
    }

    return [
        'sql'     => $where ? ('WHERE ' . implode(' AND ', $where)) : '',
        'params'  => $params,
        'types'   => $types,
        'applied' => $applied,
    ];
}
