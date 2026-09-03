<?php
// ========================================
// FutureWay - decision_tree_runner.php
// เรียก decision_tree.py ผ่าน proc_open — ที่เดียวสำหรับทุก endpoint
// (save_quiz.php ใช้ตอนบันทึกผล, get_result.php ใช้เป็น fallback ของผลเก่า)
//
// ส่ง input เป็น JSON ทาง stdin (ไม่ใช่ argv) จึงไม่มีช่องให้ inject คำสั่ง
// Python ตอบ JSON ทาง stdout; ถ้าพังจะตอบ {"error": ...} พร้อม exit code 1
// ========================================

require_once __DIR__ . '/python_locator.php';

/**
 * รัน decision tree แล้วคืนผลลัพธ์ที่ decode แล้ว
 *
 * @param array $input  เช่น ['grades' => [...], 'answers' => [...]]
 * @return array        ผลจาก Python (มี key mbti, top_categories, ...)
 * @throws RuntimeException ข้อความปลอดภัยสำหรับแสดงผู้ใช้ (รายละเอียดถูก log ไว้แล้ว)
 */
function runDecisionTree(array $input): array {
    $scriptPath = dirname(__DIR__) . '/decision_tree.py';
    if (!file_exists($scriptPath)) {
        error_log('decision_tree_runner: script not found at ' . $scriptPath);
        throw new RuntimeException('ระบบวิเคราะห์ยังไม่พร้อมใช้งาน');
    }

    try {
        $pythonPath = getPythonPath();
    } catch (RuntimeException $e) {
        error_log('decision_tree_runner: ' . $e->getMessage());
        throw new RuntimeException('ระบบวิเคราะห์ยังไม่พร้อมใช้งาน');
    }

    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $process = proc_open('"' . $pythonPath . '" "' . $scriptPath . '"', $descriptors, $pipes);
    if (!is_resource($process)) {
        error_log('decision_tree_runner: proc_open failed for ' . $pythonPath);
        throw new RuntimeException('ไม่สามารถเรียกระบบวิเคราะห์ได้');
    }

    fwrite($pipes[0], json_encode($input, JSON_UNESCAPED_UNICODE));
    fclose($pipes[0]);

    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exitCode = proc_close($process);

    $result = json_decode($stdout, true);

    if (!is_array($result)) {
        error_log(sprintf(
            'decision_tree_runner: invalid output (exit=%d) stdout=%s stderr=%s',
            $exitCode, substr($stdout, 0, 500), substr($stderr, 0, 500)
        ));
        throw new RuntimeException('ระบบวิเคราะห์ตอบกลับไม่ถูกต้อง กรุณาลองใหม่');
    }

    if ($exitCode !== 0 || isset($result['error'])) {
        error_log(sprintf(
            'decision_tree_runner: python error (exit=%d): %s %s',
            $exitCode, $result['error'] ?? '', substr($stderr, 0, 500)
        ));
        throw new RuntimeException('วิเคราะห์ผลไม่สำเร็จ กรุณาลองใหม่');
    }

    return $result;
}
