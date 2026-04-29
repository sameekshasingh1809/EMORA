<?php
/**
 * albumart.php  — Fetch album artwork via iTunes Search API (free, no API key)
 * GET ?artist=Ed+Sheeran&track=Shape+of+You
 * Returns JSON: { "image": "https://...jpg" } or { "image": null }
 */
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

$artist = trim($_GET['artist'] ?? '');
$track  = trim($_GET['track']  ?? '');

if (!$artist && !$track) {
    echo json_encode(["image" => null]);
    exit;
}

// Build iTunes search query
$query = urlencode(($track ? $track . ' ' : '') . $artist);
$url   = "https://itunes.apple.com/search?term={$query}&media=music&entity=song&limit=1";

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_TIMEOUT        => 8,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
    CURLOPT_HTTPHEADER     => ['Accept: application/json'],
]);
$raw  = curl_exec($ch);
$err  = curl_error($ch);
curl_close($ch);

if ($err || !$raw) {
    echo json_encode(["image" => null, "error" => $err]);
    exit;
}

$data = json_decode($raw, true);

if (!empty($data['results'][0]['artworkUrl100'])) {
    // Replace 100x100 thumb with 500x500 high-res
    $img = str_replace('100x100bb', '500x500bb', $data['results'][0]['artworkUrl100']);
    echo json_encode(["image" => $img]);
} else {
    echo json_encode(["image" => null]);
}