<?php
/**
 * analytics.php — Returns mood analytics for the logged-in user
 * GET ?range=7|30|90   (days)
 * Returns JSON with mood history, distribution, streaks, liked songs
 */
header("Content-Type: application/json");
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["error" => "Not logged in"]);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$range  = in_array((int)($_GET['range'] ?? 7), [7, 30, 90]) ? (int)$_GET['range'] : 7;

// ── 1. Timeline: one entry per day (last N days) ──────────────────────────────
$timeline = [];
$stmt = $conn->prepare("
    SELECT DATE(detected_at) as day, mood, COUNT(*) as cnt
    FROM mood_history
    WHERE user_id = ? AND detected_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
    GROUP BY day, mood
    ORDER BY day ASC
");
$stmt->bind_param("ii", $userId, $range);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $timeline[] = $row;
}
$stmt->close();

// ── 2. Mood distribution ──────────────────────────────────────────────────────
$distribution = [];
$stmt2 = $conn->prepare("
    SELECT mood, COUNT(*) as total
    FROM mood_history
    WHERE user_id = ? AND detected_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
    GROUP BY mood
    ORDER BY total DESC
");
$stmt2->bind_param("ii", $userId, $range);
$stmt2->execute();
$res2 = $stmt2->get_result();
while ($row = $res2->fetch_assoc()) {
    $distribution[] = $row;
}
$stmt2->close();

// ── 3. Method breakdown ───────────────────────────────────────────────────────
$methods = [];
$stmt3 = $conn->prepare("
    SELECT method, COUNT(*) as total
    FROM mood_history
    WHERE user_id = ? AND detected_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
    GROUP BY method ORDER BY total DESC
");
$stmt3->bind_param("ii", $userId, $range);
$stmt3->execute();
$res3 = $stmt3->get_result();
while ($row = $res3->fetch_assoc()) {
    $methods[] = $row;
}
$stmt3->close();

// ── 4. Recent history (last 20 entries) ───────────────────────────────────────
$recent = [];
$stmt4 = $conn->prepare("
    SELECT mood, method, note, detected_at
    FROM mood_history
    WHERE user_id = ?
    ORDER BY detected_at DESC
    LIMIT 20
");
$stmt4->bind_param("i", $userId);
$stmt4->execute();
$res4 = $stmt4->get_result();
while ($row = $res4->fetch_assoc()) {
    $recent[] = $row;
}
$stmt4->close();

// ── 5. Stats / streaks ────────────────────────────────────────────────────────
$stats = ["current_streak" => 0, "best_streak" => 0, "total_logs" => 0];
$stmt5 = $conn->prepare("SELECT current_streak, best_streak, total_logs FROM user_stats WHERE user_id = ?");
$stmt5->bind_param("i", $userId);
$stmt5->execute();
$res5 = $stmt5->get_result();
if ($res5->num_rows > 0) {
    $stats = $res5->fetch_assoc();
}
$stmt5->close();

// ── 6. Liked songs ────────────────────────────────────────────────────────────
$liked = [];
$stmt6 = $conn->prepare("
    SELECT track_name, artist, spotify_url, album_art, mood, liked_at
    FROM liked_songs WHERE user_id = ? ORDER BY liked_at DESC LIMIT 50
");
$stmt6->bind_param("i", $userId);
$stmt6->execute();
$res6 = $stmt6->get_result();
while ($row = $res6->fetch_assoc()) {
    $liked[] = $row;
}
$stmt6->close();

// ── 7. Top mood this week / most common ──────────────────────────────────────
$topMood = count($distribution) ? $distribution[0]['mood'] : 'neutral';

// ── 8. Mood positivity score (weighted avg) ───────────────────────────────────
$positivity = [
    'happy'=>10,'hopeful'=>8,'energetic'=>8,'confident'=>8,'romantic'=>7,
    'chill'=>6,'focus'=>6,'neutral'=>5,'surprised'=>5,'nostalgic'=>5,
    'tired'=>3,'anxious'=>3,'lonely'=>3,'melancholy'=>3,
    'sad'=>2,'angry'=>2,'fearful'=>2,'disgusted'=>1,
];
$posTotal = 0;
$posCount = 0;
foreach ($distribution as $d) {
    $w = $positivity[$d['mood']] ?? 5;
    $posTotal += $w * $d['total'];
    $posCount += $d['total'];
}
$posScore = $posCount > 0 ? round($posTotal / $posCount, 1) : 5.0;

echo json_encode([
    "range"        => $range,
    "timeline"     => $timeline,
    "distribution" => $distribution,
    "methods"      => $methods,
    "recent"       => $recent,
    "stats"        => $stats,
    "liked_songs"  => $liked,
    "top_mood"     => $topMood,
    "pos_score"    => $posScore,
    "max_score"    => 10,
]);
