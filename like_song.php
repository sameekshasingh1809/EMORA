<?php
/**
 * like_song.php — Like or unlike a song
 * POST: { "action": "like"|"unlike", "track_name": "...", "artist": "...", "spotify_url": "...", "album_art": "...", "mood": "..." }
 * Returns JSON: { "success": true, "liked": true }
 */
header("Content-Type: application/json");
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "error" => "Not logged in"]);
    exit;
}

$body      = json_decode(file_get_contents("php://input"), true);
$userId    = (int)$_SESSION['user_id'];
$action    = $body['action']     ?? 'like';
$trackName = trim($body['track_name']  ?? '');
$artist    = trim($body['artist']      ?? '');
$spotUrl   = trim($body['spotify_url'] ?? '');
$albumArt  = trim($body['album_art']   ?? '');
$mood      = trim($body['mood']        ?? '');

if (!$trackName) {
    echo json_encode(["success" => false, "error" => "Missing track name"]);
    exit;
}

if ($action === 'unlike') {
    $stmt = $conn->prepare("DELETE FROM liked_songs WHERE user_id=? AND track_name=? AND artist=?");
    $stmt->bind_param("iss", $userId, $trackName, $artist);
    $stmt->execute();
    $stmt->close();
    echo json_encode(["success" => true, "liked" => false]);
} else {
    // INSERT IGNORE so duplicate doesn't error
    $stmt = $conn->prepare("INSERT IGNORE INTO liked_songs (user_id, track_name, artist, spotify_url, album_art, mood) VALUES (?,?,?,?,?,?)");
    $stmt->bind_param("isssss", $userId, $trackName, $artist, $spotUrl, $albumArt, $mood);
    $stmt->execute();
    $stmt->close();
    echo json_encode(["success" => true, "liked" => true]);
}
