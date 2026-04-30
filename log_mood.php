<?php
/**
 * log_mood.php — Save detected mood to database
 * POST: { "mood": "happy", "method": "text", "note": "optional" }
 * Returns JSON: { "success": true, "streak": 3 }
 */
header("Content-Type: application/json");
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "error" => "Not logged in"]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "error" => "POST required"]);
    exit;
}

$body   = json_decode(file_get_contents("php://input"), true);
$userId = (int)$_SESSION['user_id'];

$allowed = ["happy","sad","angry","energetic","romantic","chill","anxious",
            "nostalgic","lonely","confident","tired","hopeful","surprised",
            "neutral","fearful","disgusted","melancholy","focus"];
$methods = ["text","voice","camera","chip"];

$mood   = in_array($body['mood']   ?? '', $allowed) ? $body['mood']   : 'neutral';
$method = in_array($body['method'] ?? '', $methods) ? $body['method'] : 'text';
$note   = trim(substr($body['note'] ?? '', 0, 500));

// Insert mood log
$stmt = $conn->prepare("INSERT INTO mood_history (user_id, mood, method, note) VALUES (?, ?, ?, ?)");
$stmt->bind_param("isss", $userId, $mood, $method, $note);
$stmt->execute();
$stmt->close();

// ── Update streak and stats ────────────────────────────────────────────────
$today = date('Y-m-d');

// Check if stats row exists
$s = $conn->prepare("SELECT current_streak, best_streak, total_logs, last_log_date FROM user_stats WHERE user_id = ?");
$s->bind_param("i", $userId);
$s->execute();
$res  = $s->get_result();
$s->close();

if ($res->num_rows === 0) {
    // First ever log — create stats row
    $ins = $conn->prepare("INSERT INTO user_stats (user_id, current_streak, best_streak, total_logs, last_log_date) VALUES (?,1,1,1,?)");
    $ins->bind_param("is", $userId, $today);
    $ins->execute();
    $ins->close();
    $streak = 1;
} else {
    $stats = $res->fetch_assoc();
    $last  = $stats['last_log_date'];
    $cur   = (int)$stats['current_streak'];
    $best  = (int)$stats['best_streak'];
    $total = (int)$stats['total_logs'];

    $yesterday = date('Y-m-d', strtotime('-1 day'));

    if ($last === $today) {
        // Already logged today — just increment total, keep streak
        $streak = $cur;
    } elseif ($last === $yesterday) {
        // Consecutive day — extend streak
        $cur++;
        $best   = max($best, $cur);
        $total++;
        $streak = $cur;
        $upd = $conn->prepare("UPDATE user_stats SET current_streak=?, best_streak=?, total_logs=?, last_log_date=? WHERE user_id=?");
        $upd->bind_param("iiisi", $cur, $best, $total, $today, $userId);
        $upd->execute();
        $upd->close();
    } else {
        // Streak broken — reset
        $cur    = 1;
        $total++;
        $streak = 1;
        $upd = $conn->prepare("UPDATE user_stats SET current_streak=1, total_logs=?, last_log_date=? WHERE user_id=?");
        $upd->bind_param("isi", $total, $today, $userId);
        $upd->execute();
        $upd->close();
    }
}

echo json_encode(["success" => true, "streak" => $streak ?? 1]);
