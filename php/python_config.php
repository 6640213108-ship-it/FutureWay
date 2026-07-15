<?php
// ========================================
// FutureWay - python_config.php
// หา path ของ python แบบ portable
// รองรับทั้ง Windows (dev เครื่องตัวเอง) และ Linux (Railway/Docker)
// ========================================

function getPythonPath(): string {
    $projectRoot = dirname(__DIR__); // โฟลเดอร์โปรเจกต์ (เหนือ /php ขึ้นไป 1 ชั้น)
    $isWindows   = stripos(PHP_OS_FAMILY, 'Windows') !== false;

    // ลำดับที่ 1: venv ที่อยู่ในโปรเจกต์เอง (แนะนำ — portable ที่สุด)
    $venvPython = $isWindows
        ? $projectRoot . '\\venv\\Scripts\\python.exe'
        : $projectRoot . '/venv/bin/python3';
    if (file_exists($venvPython)) {
        return $venvPython;
    }

    if ($isWindows) {
        // ลำดับที่ 2 (Windows): python ที่อยู่ใน PATH ของระบบ
        $output = [];
        exec('where python 2>NUL', $output);
        foreach ($output as $path) {
            // ข้าม WindowsApps stub (ตัวปลอมของ Microsoft Store)
            if (stripos($path, 'WindowsApps') === false && file_exists(trim($path))) {
                return trim($path);
            }
        }
    } else {
        // ลำดับที่ 2 (Linux/Docker): python3 ที่อยู่ใน PATH ของระบบ
        // ใน Dockerfile ต้อง apt-get install -y python3 python3-pip ไว้ก่อน
        $output = [];
        exec('command -v python3 2>/dev/null', $output);
        if (!empty($output[0]) && file_exists(trim($output[0]))) {
            return trim($output[0]);
        }

        // เผื่อบางระบบมีแค่ python (ไม่มี python3)
        $output = [];
        exec('command -v python 2>/dev/null', $output);
        if (!empty($output[0]) && file_exists(trim($output[0]))) {
            return trim($output[0]);
        }

        // fallback: path มาตรฐานที่ apt install ส่วนใหญ่ลงไว้
        foreach (['/usr/bin/python3', '/usr/local/bin/python3'] as $fallback) {
            if (file_exists($fallback)) {
                return $fallback;
            }
        }
    }

    // ไม่เจอเลย -> โยน exception ให้ error message ชัดเจน แทนที่จะ proc_open แล้วได้ output ว่างเปล่า
    if ($isWindows) {
        throw new Exception(
            'ไม่พบ Python บนเครื่องนี้ กรุณาสร้าง venv ก่อน: ' .
            'cd ' . $projectRoot . ' && python -m venv venv && venv\\Scripts\\pip install mysql-connector-python'
        );
    } else {
        throw new Exception(
            'ไม่พบ Python บน server นี้ ตรวจสอบว่า Dockerfile ได้ RUN apt-get install -y python3 python3-pip ' .
            'และ pip install mysql-connector-python ไว้แล้วหรือยัง'
        );
    }
}
