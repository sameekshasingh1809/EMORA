<?php
/**
 * camera.php — Server-side mood detection via DeepFace/OpenCV
 * Auto-detects Python on Windows, Linux, or macOS.
 * Falls back gracefully so the browser can use Claude Vision instead.
 */
header("Content-Type: application/json");

$allowed = ["happy","sad","angry","energetic","romantic","chill","anxious",
            "nostalgic","lonely","confident","tired","hopeful","surprised",
            "neutral","fearful","disgusted","melancholy","focus"];

// ── Find Python ───────────────────────────────────────────────────────────────
function findPython(): string {
    $candidates = [];
    if (PHP_OS_FAMILY === 'Windows') {
        $candidates = ['python', 'python3', 'py'];
        // Scan AppData for any Python 3.x install
        $localAppData = getenv('LOCALAPPDATA') ?: '';
        if ($localAppData) {
            foreach (glob($localAppData . '\\Programs\\Python\\Python3*\\python.exe') ?: [] as $p) {
                $candidates[] = $p;
            }
        }
        // Common fixed paths
        foreach (['311','310','312','39','38'] as $v) {
            $candidates[] = "C:\\Python$v\\python.exe";
            $candidates[] = "C:\\Program Files\\Python$v\\python.exe";
        }
    } else {
        $candidates = ['python3', 'python', '/usr/bin/python3', '/usr/local/bin/python3'];
    }

    foreach ($candidates as $cmd) {
        $quoted = (PHP_OS_FAMILY === 'Windows' && strpbrk($cmd, ' \\') !== false)
                  ? '"' . $cmd . '"'
                  : escapeshellcmd($cmd);
        $out = procStdout($quoted . ' --version 2>&1');
        if (preg_match('/Python 3\.\d/', $out)) {
            return $cmd;
        }
    }
    return '';
}

// ── Check DeepFace ────────────────────────────────────────────────────────────
function checkDeepFace(string $python): bool {
    $code = implode(';', [
        'import os,sys',
        'os.environ["TF_CPP_MIN_LOG_LEVEL"]="3"',
        'os.environ["TF_ENABLE_ONEDNN_OPTS"]="0"',
        'import warnings;warnings.filterwarnings("ignore")',
        'from deepface import DeepFace',
        'import cv2',
        'print("OK")',
    ]);
    $out = procStdout(quotePy($python) . ' -c ' . escapeshellarg($code));
    return trim($out) === 'OK';
}

// ── Run camera_mood.py ────────────────────────────────────────────────────────
function runCameraMood(string $python): string {
    $script = __DIR__ . DIRECTORY_SEPARATOR . 'camera_mood.py';
    if (!file_exists($script)) return '';
    return procStdout(quotePy($python) . ' ' . escapeshellarg($script));
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function quotePy(string $python): string {
    if (PHP_OS_FAMILY === 'Windows' && strpbrk($python, ' \\') !== false) {
        return '"' . $python . '"';
    }
    return escapeshellcmd($python);
}

function procStdout(string $cmd): string {
    $desc = [0 => ['pipe','r'], 1 => ['pipe','w'], 2 => ['pipe','w']];
    $proc = proc_open($cmd, $desc, $pipes);
    if (!is_resource($proc)) return '';
    fclose($pipes[0]);
    $out = stream_get_contents($pipes[1]); fclose($pipes[1]);
                                            fclose($pipes[2]);
    proc_close($proc);
    return trim($out);
}

// ── Main ──────────────────────────────────────────────────────────────────────
$python = findPython();

if ($python === '') {
    echo json_encode([
        "mood"  => "neutral",
        "mode"  => "browser_fallback",
        "error" => "Python 3 not found — install Python 3 and add it to PATH, then reload."
    ]);
    exit;
}

// Try DeepFace first; camera_mood.py also handles OpenCV-only fallback internally
$deepfaceOk = checkDeepFace($python);

$mood = runCameraMood($python);
$mood = strtolower(preg_replace('/[^a-z]/', '', $mood));

if ($mood && in_array($mood, $allowed)) {
    echo json_encode([
        "mood" => $mood,
        "mode" => $deepfaceOk ? "deepface" : "opencv_fallback",
    ]);
} else {
    echo json_encode([
        "mood"  => "neutral",
        "mode"  => "browser_fallback",
        "error" => "camera_mood.py returned no valid mood. "
                 . ($deepfaceOk ? "" : "DeepFace unavailable — run: pip install deepface opencv-python tf-keras"),
    ]);
}