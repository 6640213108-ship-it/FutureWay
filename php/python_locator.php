<?php
// ========================================
// FutureWay - python_locator.php
// หา path ของ Python แบบ portable
// รองรับทั้ง Windows (เครื่อง dev) และ Linux (Railway/Docker)
//
// ลำดับการค้นหา:
//   1) PYTHON_BIN (environment variable) — กำหนดเองได้ตรง ๆ
//   2) venv ในโฟลเดอร์โปรเจกต์
//   3) python3 / python ใน PATH ของระบบ
//   4) path มาตรฐานที่ apt ติดตั้งให้
// ========================================

function getPythonPath(): string {
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

    $projectRoot = dirname(__DIR__);
    $isWindows   = stripos(PHP_OS_FAMILY, 'Windows') !== false;

    $candidates = [];

    $fromEnv = getenv('PYTHON_BIN');
    if ($fromEnv) {
        $candidates[] = $fromEnv;
    }

    $candidates[] = $isWindows
        ? $projectRoot . '\\venv\\Scripts\\python.exe'
        : $projectRoot . '/venv/bin/python3';

    foreach ($candidates as $path) {
        if (file_exists($path)) {
            return $cached = $path;
        }
    }

    if ($isWindows) {
        $output = [];
        exec('where python 2>NUL', $output);
        foreach ($output as $path) {
            // ข้าม stub ของ Microsoft Store ที่ไม่ใช่ Python จริง
            if (stripos($path, 'WindowsApps') === false && file_exists(trim($path))) {
                return $cached = trim($path);
            }
        }
    } else {
        foreach (['python3', 'python'] as $bin) {
            $output = [];
            exec('command -v ' . $bin . ' 2>/dev/null', $output);
            if (!empty($output[0]) && file_exists(trim($output[0]))) {
                return $cached = trim($output[0]);
            }
        }
        foreach (['/usr/bin/python3', '/usr/local/bin/python3'] as $fallback) {
            if (file_exists($fallback)) {
                return $cached = $fallback;
            }
        }
    }

    throw new RuntimeException(
        'Python interpreter not found. Set PYTHON_BIN or create a venv in ' . $projectRoot
    );
}
