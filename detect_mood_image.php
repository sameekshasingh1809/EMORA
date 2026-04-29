<?php
/**
 * detect_mood_image.php — Mood detection from browser-captured image
 * Uses Claude Vision API (Anthropic) - no Python/DeepFace needed
 * POST: { "image": "data:image/jpeg;base64,..." }
 * Returns JSON: { "mood": "happy", "mode": "claude_vision" }
 */
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(["error" => "POST required"]); exit; }

$VALID_MOODS = ["happy","sad","angry","energetic","romantic","chill","anxious",
                "nostalgic","lonely","confident","tired","hopeful","surprised",
                "neutral","fearful","disgusted","melancholy","focus"];

// ── Parse image ───────────────────────────────────────────────────────────────
$body  = json_decode(file_get_contents("php://input"), true);
$image = $body['image'] ?? '';

if (!$image || !preg_match('/^data:(image\/(?:jpeg|png|webp));base64,(.+)$/', $image, $m)) {
    echo json_encode(["error" => "Invalid or missing image", "mood" => "neutral"]); exit;
}
$mediaType  = $m[1]; // e.g. "image/jpeg"
$base64Data = $m[2];

// Validate base64
$decoded = base64_decode($base64Data, true);
if (!$decoded || strlen($decoded) < 500) {
    echo json_encode(["error" => "Image too small or corrupt", "mood" => "neutral"]); exit;
}

// ── Call Claude Vision API ────────────────────────────────────────────────────
$apiKey = ""; // Leave empty — handled by proxy in claude.ai environment
// If running locally, set your Anthropic API key here:
// $apiKey = "sk-ant-...";

$prompt = "Look at this photo of a person's face. Analyze their facial expression and body language carefully.
Determine their current emotional mood from this list ONLY:
happy, sad, angry, energetic, romantic, chill, anxious, nostalgic, lonely, confident, tired, hopeful, surprised, neutral, fearful, disgusted, melancholy, focus

Rules:
- Reply with ONLY the single mood word from the list above, nothing else
- If you cannot clearly see a face, reply: neutral
- Pick the mood that best matches what you observe
- Consider: eye openness, mouth shape, brow position, overall expression";

$requestBody = json_encode([
    "model"      => "claude-opus-4-20250514",
    "max_tokens" => 20,
    "messages"   => [
        [
            "role"    => "user",
            "content" => [
                [
                    "type"   => "image",
                    "source" => [
                        "type"       => "base64",
                        "media_type" => $mediaType,
                        "data"       => $base64Data,
                    ]
                ],
                [
                    "type" => "text",
                    "text" => $prompt
                ]
            ]
        ]
    ]
]);

$headers = [
    "Content-Type: application/json",
    "anthropic-version: 2023-06-01",
    "x-api-key: " . $apiKey,
];

$ch = curl_init("https://api.anthropic.com/v1/messages");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $requestBody,
    CURLOPT_HTTPHEADER     => $headers,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_TIMEOUT        => 30,
]);

$raw = curl_exec($ch);
$err = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($err || !$raw) {
    // Fallback to OpenCV/DeepFace if Claude fails
    echo json_encode(["mood" => "neutral", "mode" => "error", "error" => "API call failed: $err"]);
    exit;
}

$response = json_decode($raw, true);

if (isset($response['content'][0]['text'])) {
    $rawMood = strtolower(trim($response['content'][0]['text']));
    // Extract just the mood word (in case model returns extra text)
    $rawMood = preg_replace('/[^a-z]/', '', $rawMood);
    $mood = in_array($rawMood, $VALID_MOODS) ? $rawMood : 'neutral';
    echo json_encode(["mood" => $mood, "mode" => "claude_vision"]);
} else {
    $errMsg = $response['error']['message'] ?? 'Unknown API error';
    echo json_encode(["mood" => "neutral", "mode" => "api_error", "error" => $errMsg]);
}