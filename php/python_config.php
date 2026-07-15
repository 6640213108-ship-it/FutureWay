<?php
// ========================================
// FutureWay - python_config.php
// หา path ของ python แบบ portable (ไม่ผูกกับ user คนใดคนหนึ่ง)
// ยกโปรเจกต์ไปเครื่องอื่นได้เลย แค่สร้าง venv ตามขั้นตอนใน README
// ========================================

function getPythonPath(): string {
    $projectRoot = dirname(__DIR__); // โฟลเดอร์โปรเจกต์ (เหนือ /php ขึ้นไป 1 ชั้น)

    // ลำดับที่ 1: venv ที่อยู่ในโปรเจกต์เอง (แนะนำ — portable ที่สุด)
    $venvPython = $projectRoot . '\\venv\\Scripts\\python.exe';
    if (file_exists($venvPython)) {
        return $venvPython;
    }

    // ลำดับที่ 2: python ที่อยู่ใน PATH ของระบบ (system-wide, ไม่ใช่ user-specific)
    // ใช้ได้ถ้า Python ถูกติดตั้งแบบ "Install for all users" ตอน setup
    $output = [];
    exec('where python 2>NUL', $output);
    foreach ($output as $path) {
        // ข้าม WindowsApps stub (ตัวปลอมของ Microsoft Store)
        if (stripos($path, 'WindowsApps') === false && file_exists(trim($path))) {
            return trim($path);
        }
    }

    // ไม่เจอเลย -> โยน exception ให้ error message ชัดเจน แทนที่จะ proc_open แล้วได้ output ว่างเปล่า
    throw new Exception(
        'ไม่พบ Python บนเครื่องนี้ กรุณาสร้าง venv ก่อน: ' .
        'cd ' . $projectRoot . ' && python -m venv venv && venv\\Scripts\\pip install mysql-connector-python'
    );
}
