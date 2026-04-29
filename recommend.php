<?php
/**
 * recommend.php — Mood-based Spotify song recommendations
 * GET ?mood=happy
 * Returns JSON: { success, mood, tracks: [...], method }
 *
 * Flow:
 *  1. Get Spotify OAuth2 token (Client Credentials)
 *  2. Search tracks with mood-specific query
 *  3. Return 10 shuffled tracks with full Spotify URLs
 *  4. Fallback to curated list if Spotify is unreachable
 *
 * ⚠️  UPDATE $client_id and $client_secret with YOUR Spotify app credentials:
 *     https://developer.spotify.com/dashboard
 */
header("Content-Type: application/json");

// ── Spotify Credentials ──────────────────────────────────────────────────────
$client_id     = "c4d14c5753144432b3f25541168a1c33";  // ← Replace with your Client ID
$client_secret = "e1dd878601684cafaa7904cc45baa2b9";  // ← Replace with your Client Secret

// ── Validate mood ────────────────────────────────────────────────────────────
$mood = strtolower(trim($_GET['mood'] ?? 'neutral'));
$allowed = ["happy","sad","angry","energetic","romantic","chill","anxious",
            "nostalgic","lonely","confident","tired","hopeful","surprised",
            "neutral","fearful","disgusted","melancholy","focus"];
if (!in_array($mood, $allowed)) $mood = "neutral";

// ── cURL helper ───────────────────────────────────────────────────────────────
function curlGet(string $url, array $headers): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT      => 'MoodTune/1.0',
    ]);
    $raw  = curl_exec($ch);
    $err  = curl_error($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$raw, $err, $code];
}

function curlPost(string $url, array $headers, array $fields): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($fields),
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_USERAGENT      => 'MoodTune/1.0',
    ]);
    $raw  = curl_exec($ch);
    $err  = curl_error($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$raw, $err, $code];
}

// ── Mood → Spotify search queries ────────────────────────────────────────────
// Multiple queries per mood; a random one is picked each call for variety
$moodQueries = [
    "happy"     => ["happy feel good pop hits","upbeat cheerful music","fun sunny day songs","joyful dance pop","positive vibes songs 2024"],
    "sad"       => ["sad emotional ballads","heartbreak songs acoustic","melancholy indie songs","tearful slow ballads","sad love songs"],
    "angry"     => ["angry rock metal","rage against machine style","aggressive punk rock","intense hard rock","metal angry songs"],
    "energetic" => ["workout pump up songs","high energy edm banger","gym motivation music","upbeat running songs","energetic dance hits"],
    "romantic"  => ["romantic love songs ballads","soft romantic music","love songs acoustic","sweet romantic playlist","valentine love songs"],
    "chill"     => ["lofi hip hop chill","ambient chill relaxing","mellow indie chill","peaceful calm music","chillout downtempo"],
    "anxious"   => ["calming anxiety relief music","soothing piano meditation","peaceful stress relief","ambient calm music","gentle healing music"],
    "nostalgic" => ["90s nostalgic hits","80s classic throwback","retro oldies feel","nostalgic childhood songs","best of 90s pop"],
    "lonely"    => ["lonely sad songs","alone acoustic heartfelt","solitude indie folk","alone at night songs","loneliness music"],
    "confident" => ["confident empowering anthems","motivational hip hop","powerful boss songs","self empowerment music","confidence boost playlist"],
    "tired"     => ["tired sleepy lofi","dreamy slow songs","sleep relax music","lo-fi tired night","late night slow songs"],
    "hopeful"   => ["hopeful uplifting songs","inspirational morning music","optimistic pop songs","positive future anthems","uplifting indie"],
    "focus"     => ["deep focus study music","concentration instrumental","coding lofi beats","study music instrumental","focus work ambient"],
    "melancholy"=> ["melancholy indie folk","bittersweet acoustic songs","wistful slow music","pensive indie sad","somber beautiful music"],
    "surprised" => ["euphoric exciting pop","wow amazing upbeat songs","surprising upbeat music"],
    "fearful"   => ["dark atmospheric music","tense dramatic cinematic","suspense dark ambient"],
    "disgusted" => ["punk rock edgy songs","alternative dark indie","gritty raw music"],
    "neutral"   => ["top hits 2024 2025","trending popular songs","billboard hot 100","chart toppers today","popular music now"],
];

// ── Step 1: Get Spotify Access Token ─────────────────────────────────────────
$spotifyError = null;

[$tokenRaw, $tokenErr, $tokenHttp] = curlPost(
    "https://accounts.spotify.com/api/token",
    ["Authorization: Basic " . base64_encode("$client_id:$client_secret"),
     "Content-Type: application/x-www-form-urlencoded"],
    ["grant_type" => "client_credentials"]
);

$tokenData = json_decode($tokenRaw, true);

if ($tokenErr || !isset($tokenData['access_token'])) {
    if ($tokenErr)              $spotifyError = "curl_error: $tokenErr";
    elseif ($tokenHttp === 401) $spotifyError = "Invalid Spotify credentials. Visit developer.spotify.com/dashboard to get valid keys.";
    elseif ($tokenHttp === 400) $spotifyError = "Bad request to Spotify token endpoint.";
    else                        $spotifyError = "Token error HTTP $tokenHttp: " . ($tokenData['error_description'] ?? $tokenData['error'] ?? 'unknown');
    goto use_fallback;
}

$token = $tokenData['access_token'];

// ── Step 2: Search Tracks on Spotify ─────────────────────────────────────────
$queries  = $moodQueries[$mood] ?? $moodQueries['neutral'];
$queryStr = $queries[array_rand($queries)];
$offset   = rand(0, 30); // Randomise for variety

[$searchRaw, $searchErr, $searchHttp] = curlGet(
    "https://api.spotify.com/v1/search?" . http_build_query([
        'q'      => $queryStr,
        'type'   => 'track',
        'limit'  => 20,
        'offset' => $offset,
        'market' => 'IN', // India market — change if needed
    ]),
    ["Authorization: Bearer $token"]
);

$searchData = json_decode($searchRaw, true);

// Retry at offset 0 if no results at random offset
if (empty($searchData['tracks']['items'])) {
    [$searchRaw, , ] = curlGet(
        "https://api.spotify.com/v1/search?" . http_build_query([
            'q' => $queryStr, 'type' => 'track', 'limit' => 20, 'offset' => 0, 'market' => 'IN',
        ]),
        ["Authorization: Bearer $token"]
    );
    $searchData = json_decode($searchRaw, true);
}

// Try a different query if still empty
if (empty($searchData['tracks']['items'])) {
    $altQuery = $queries[0]; // Use first (most reliable) query
    [$searchRaw, , ] = curlGet(
        "https://api.spotify.com/v1/search?" . http_build_query([
            'q' => $altQuery, 'type' => 'track', 'limit' => 20, 'offset' => 0,
        ]),
        ["Authorization: Bearer $token"]
    );
    $searchData = json_decode($searchRaw, true);
}

if (!empty($searchData['tracks']['items'])) {
    $tracks = array_values(array_filter(
        $searchData['tracks']['items'],
        fn($t) => !empty($t['name']) && !empty($t['external_urls']['spotify'])
    ));
    shuffle($tracks);
    $tracks = array_slice($tracks, 0, 10);

    $result = array_map(fn($t) => [
        "name"          => $t['name'],
        "artists"       => [["name" => $t['artists'][0]['name'] ?? 'Unknown']],
        "album"         => ["images" => $t['album']['images'] ?? []],
        "external_urls" => ["spotify" => $t['external_urls']['spotify']],
        "preview_url"   => $t['preview_url'] ?? null,
    ], $tracks);

    echo json_encode(["success" => true, "mood" => $mood, "method" => "spotify", "tracks" => $result]);
    exit;
}

$spotifyError = $searchErr ?: "No tracks found (HTTP $searchHttp)";

// ── Curated Fallback ──────────────────────────────────────────────────────────
// Used when Spotify is unreachable or credentials are expired.
// Each entry: [name, artist, direct_spotify_track_url]
use_fallback:

$fallback = [
    "happy" => [
        ["Happy", "Pharrell Williams", "https://open.spotify.com/track/60nZcImufyMA1MKQY3dcCH"],
        ["Can't Stop the Feeling!", "Justin Timberlake", "https://open.spotify.com/track/1WkMMavIMc4JZ8cfMmxHkI"],
        ["Uptown Funk", "Mark Ronson ft. Bruno Mars", "https://open.spotify.com/track/32OlwWuMpZ6b0aN2RZOeMS"],
        ["Good as Hell", "Lizzo", "https://open.spotify.com/track/3Yh9lSMD9CijudH9xa4Bq7"],
        ["Shake It Off", "Taylor Swift", "https://open.spotify.com/track/0cqRj7pUJDkTCEsJkx8snD"],
        ["Walking on Sunshine", "Katrina & The Waves", "https://open.spotify.com/track/05wIrZSwuaVWhcv5FfqeH0"],
        ["September", "Earth, Wind & Fire", "https://open.spotify.com/track/2grjqo0Frpf2okIBiifQKs"],
        ["Dancing Queen", "ABBA", "https://open.spotify.com/track/0GjEhVFGZW8afUYGChu3Rr"],
        ["Levitating", "Dua Lipa", "https://open.spotify.com/track/463CkQjx2Zk1yXoBuierM9"],
        ["Best Day Of My Life", "American Authors", "https://open.spotify.com/track/6bkwJBHN5M4MIwGlL3l4yY"],
    ],
    "sad" => [
        ["Someone Like You", "Adele", "https://open.spotify.com/track/4kflIUSP30ltMRQCg4SFRG"],
        ["The Night We Met", "Lord Huron", "https://open.spotify.com/track/0HMi2VJFVqkZlTASKkzJSE"],
        ["Fix You", "Coldplay", "https://open.spotify.com/track/7LVHVU3tWfcxj5aiPFEW4Q"],
        ["Skinny Love", "Bon Iver", "https://open.spotify.com/track/5dMGEelyAoSmNjwBMreMpj"],
        ["Liability", "Lorde", "https://open.spotify.com/track/5N3hjp1WNCSoS0wd8UqBBe"],
        ["All I Want", "Kodaline", "https://open.spotify.com/track/2vD5TDTJ7gSzNXNXfBJNmI"],
        ["Let Her Go", "Passenger", "https://open.spotify.com/track/6zFUNYV7wnMbPqkEBsekNX"],
        ["Breathe Me", "Sia", "https://open.spotify.com/track/6SsIdN9pVBFU6yBdRBQDhO"],
        ["Hurt", "Johnny Cash", "https://open.spotify.com/track/28cngnQkQ6larUs9HCNxm9"],
        ["Motion Sickness", "Phoebe Bridgers", "https://open.spotify.com/track/4Eln3SaXKClN4aLkUEeEJ0"],
    ],
    "angry" => [
        ["Break Stuff", "Limp Bizkit", "https://open.spotify.com/track/4Gp3rHYILBXQqvF6m4cHlR"],
        ["Killing In The Name", "Rage Against The Machine", "https://open.spotify.com/track/59WN2psjkt1tyaxjspN8fp"],
        ["Given Up", "Linkin Park", "https://open.spotify.com/track/7eQJUEDmACvLRCgJ2yxDxR"],
        ["Enter Sandman", "Metallica", "https://open.spotify.com/track/6as7Or03XNvo0JyEbGnmGV"],
        ["Bodies", "Drowning Pool", "https://open.spotify.com/track/3oA1fjJMKVjY7y1RDFmMpY"],
        ["Du Hast", "Rammstein", "https://open.spotify.com/track/6Ei9WLObWw5OBb0ykxNzxX"],
        ["Chop Suey!", "System of a Down", "https://open.spotify.com/track/2CHvBhKGpFMIZUAInCuSPY"],
        ["Last Resort", "Papa Roach", "https://open.spotify.com/track/79cdsBZQLsi5xQANvkJoDA"],
        ["Down With The Sickness", "Disturbed", "https://open.spotify.com/track/5IqGZpGXPMUqCZhVODqRLD"],
        ["Bulls on Parade", "Rage Against The Machine", "https://open.spotify.com/track/1HRpTBqiPpsMUx9kJhQ8M0"],
    ],
    "energetic" => [
        ["Titanium", "David Guetta ft. Sia", "https://open.spotify.com/track/0gplL1WMoJ6iYaIlTKUBsA"],
        ["Lose Yourself", "Eminem", "https://open.spotify.com/track/7w9bgPAmPTtrzt15v7sefu"],
        ["Eye of the Tiger", "Survivor", "https://open.spotify.com/track/2KH16WveTQWT6KOG9Rg6e2"],
        ["Stronger", "Kanye West", "https://open.spotify.com/track/6YllAFNXLKhSVOoKAifA3G"],
        ["Run The World (Girls)", "Beyoncé", "https://open.spotify.com/track/4P1tqKTHW7GalYQ7nAFNPu"],
        ["Radioactive", "Imagine Dragons", "https://open.spotify.com/track/69yfbpvmkzOibQYyzA9meD"],
        ["Till I Collapse", "Eminem", "https://open.spotify.com/track/1w9O7cMJvl6oAkVBpHfBlN"],
        ["Thunder", "Imagine Dragons", "https://open.spotify.com/track/1zB4vmk8tFRmM9UULNzbLB"],
        ["Turn Down for What", "DJ Snake & Lil Jon", "https://open.spotify.com/track/3Rq3sASLDGzlbd06r7ELBT"],
        ["Power", "Kanye West", "https://open.spotify.com/track/2gZUPNdnz5Y45eiGxpHGSc"],
    ],
    "romantic" => [
        ["Perfect", "Ed Sheeran", "https://open.spotify.com/track/0tgVpDi06FyKpA1z0VMD4v"],
        ["All of Me", "John Legend", "https://open.spotify.com/track/3U4isOIWM3VvDubwSI3y7a"],
        ["Make You Feel My Love", "Adele", "https://open.spotify.com/track/7FnaUVFKjqIGQ7VUuPNQaY"],
        ["A Thousand Years", "Christina Perri", "https://open.spotify.com/track/6lanRgr6wXibZr8KgzXxBl"],
        ["Lover", "Taylor Swift", "https://open.spotify.com/track/1dGr1c8CrMLDpV6mPbImSI"],
        ["Can't Help Falling In Love", "Elvis Presley", "https://open.spotify.com/track/44AyOl4qVkzS48vBsbNXaC"],
        ["Your Song", "Elton John", "https://open.spotify.com/track/7bTd3sOxQJm5P78UPgmFWa"],
        ["Thinking Out Loud", "Ed Sheeran", "https://open.spotify.com/track/34gCuhDGsG4bRPIf9bb02f"],
        ["At Last", "Etta James", "https://open.spotify.com/track/5vPGKMiPRETjWMbeBRXaMV"],
        ["Die For You", "The Weeknd", "https://open.spotify.com/track/4AogqAMFomQfGVMOJT3U0K"],
    ],
    "chill" => [
        ["Sunset Lover", "Petit Biscuit", "https://open.spotify.com/track/6jFCdMuXOdEJJWIlWZABxG"],
        ["Redbone", "Childish Gambino", "https://open.spotify.com/track/6MMWLjIFGMEAiGQ9YNqD6L"],
        ["Sunday Morning", "Maroon 5", "https://open.spotify.com/track/2DQ2TMPccBkKYTqBRJsNMx"],
        ["Electric Feel", "MGMT", "https://open.spotify.com/track/3FtYbEfBqAlGO46NUDtSAt"],
        ["Golden Hour", "JVKE", "https://open.spotify.com/track/5odlY52u43F5BjByhxg7wg"],
        ["Bloom", "The Paper Kites", "https://open.spotify.com/track/1FkxGMHIXYqNdF7RSNK4T9"],
        ["coffee", "beabadoobee", "https://open.spotify.com/track/6cf4iSs6UFpNwT3KJHP8jZ"],
        ["Feels", "Calvin Harris", "https://open.spotify.com/track/5bcTCxgc7xVfSaMV3RoA7n"],
        ["Waves", "Mr. Probz", "https://open.spotify.com/track/0mnRBFJjVqhEyQbf0RSCXM"],
        ["Yellow", "Coldplay", "https://open.spotify.com/track/3AJwUDP919kvQ9QcozQPxg"],
    ],
    "anxious" => [
        ["Weightless", "Marconi Union", "https://open.spotify.com/track/6hWq7plVsPfGbK9WO4RtU3"],
        ["Experience", "Ludovico Einaudi", "https://open.spotify.com/track/1BncfTJAOPVhGSHiHBHLHy"],
        ["River Flows in You", "Yiruma", "https://open.spotify.com/track/6HFoLTLbkiEHOhHB4ZZMJI"],
        ["Clair de Lune", "Debussy", "https://open.spotify.com/track/2bCg25EOLG5bCyJVHf1gv7"],
        ["Breathe (2 AM)", "Anna Nalick", "https://open.spotify.com/track/3z50CPJzSVFsOt3EDNfrk1"],
        ["Mad World", "Gary Jules", "https://open.spotify.com/track/3JOVTQ5h8HyvI8pZT3gfEG"],
        ["The Sound of Silence", "Simon & Garfunkel", "https://open.spotify.com/track/4H7wGPFzMBFBLBxHU8rCUv"],
        ["Let It Be", "The Beatles", "https://open.spotify.com/track/7iN1s7xHE4ifF5povM6A48"],
        ["Spiegel im Spiegel", "Arvo Pärt", "https://open.spotify.com/track/4BwEJOJSbJfAG9mMGzq1sJ"],
        ["Nuvole Bianche", "Ludovico Einaudi", "https://open.spotify.com/track/5d2VH2mNAkLMXJ3R6zJQnJ"],
    ],
    "nostalgic" => [
        ["Summer of '69", "Bryan Adams", "https://open.spotify.com/track/0MkaHQWNLG9sOBq3JdBFfL"],
        ["Don't You (Forget About Me)", "Simple Minds", "https://open.spotify.com/track/4F3zRGJON1WB2KrOyDsS0N"],
        ["Africa", "Toto", "https://open.spotify.com/track/2374M0fQpWi3dLnB54qaLX"],
        ["Come On Eileen", "Dexys Midnight Runners", "https://open.spotify.com/track/0vFabeTqtOtJdSQABFXXJh"],
        ["1979", "The Smashing Pumpkins", "https://open.spotify.com/track/0dREmcFg0tWvvCFstiqFJi"],
        ["Fast Car", "Tracy Chapman", "https://open.spotify.com/track/3UcSBfKRqBXKJFoY7cBQV4"],
        ["Zombie", "The Cranberries", "https://open.spotify.com/track/6nzxy2wXs6tLgzEtqOkEi2"],
        ["Smells Like Teen Spirit", "Nirvana", "https://open.spotify.com/track/5ghIJDpPoe3CfHMGu71E6T"],
        ["Everybody Hurts", "R.E.M.", "https://open.spotify.com/track/6PypGyiu0Y2lCDBN1XZEnP"],
        ["Mr. Jones", "Counting Crows", "https://open.spotify.com/track/6KQ6uxRrQSUC7mN9UqsQ2K"],
    ],
    "lonely" => [
        ["Eleanor Rigby", "The Beatles", "https://open.spotify.com/track/3T4tUhGYeRNVUGevb0wThu"],
        ["Iris", "Goo Goo Dolls", "https://open.spotify.com/track/6pRGHfbEFPmKdx0yMjmr1K"],
        ["How to Save a Life", "The Fray", "https://open.spotify.com/track/1bRu32dWOY9mFObRGKOVkJ"],
        ["People Are Strange", "The Doors", "https://open.spotify.com/track/4cXhsil3rAgfMNm0lj43ML"],
        ["Lonely", "Akon", "https://open.spotify.com/track/2LMkwUfqC6S6s6qDVlEuzV"],
        ["Unwell", "Matchbox Twenty", "https://open.spotify.com/track/4DJa0kzrF3VUMKoGVQXV4Q"],
        ["Black", "Pearl Jam", "https://open.spotify.com/track/4XHjENLCEiMDzsFZgRHQl9"],
        ["I Am a Rock", "Simon & Garfunkel", "https://open.spotify.com/track/75JFxkI2RXiU7L9VmCAV6V"],
        ["Only the Lonely", "Roy Orbison", "https://open.spotify.com/track/4m4pNkwSAJ3nYkfqL3nkXs"],
        ["Mr. Lonely", "Bobby Vinton", "https://open.spotify.com/track/5qDkNl1FJLHfO7p53JQfFV"],
    ],
    "confident" => [
        ["Roar", "Katy Perry", "https://open.spotify.com/track/6HDKN9kyByAsxFM9Ob5qGW"],
        ["Fighter", "Christina Aguilera", "https://open.spotify.com/track/3PZLmCX3Pf7hfqcLRJ9gUC"],
        ["HUMBLE.", "Kendrick Lamar", "https://open.spotify.com/track/7KXjTSCq5nL1LoYtL7XAwS"],
        ["Stronger (What Doesn't Kill You)", "Kelly Clarkson", "https://open.spotify.com/track/1TfqLAPs4K3s2rJMoCokcX"],
        ["We Will Rock You", "Queen", "https://open.spotify.com/track/4pbJqGIASGPr0ZpGpnWkDn"],
        ["Can't Hold Us", "Macklemore & Ryan Lewis", "https://open.spotify.com/track/0SFkeBQjR1OfRjWJolkX5M"],
        ["Started From the Bottom", "Drake", "https://open.spotify.com/track/3hFCpKGqzCFjnqjBMl8D9d"],
        ["Run the World (Girls)", "Beyoncé", "https://open.spotify.com/track/4P1tqKTHW7GalYQ7nAFNPu"],
        ["Power", "Kanye West", "https://open.spotify.com/track/2gZUPNdnz5Y45eiGxpHGSc"],
        ["Fight Song", "Rachel Platten", "https://open.spotify.com/track/1E9VPkVa2yhtRWOdGSWkCm"],
    ],
    "tired" => [
        ["Breathe", "Pink Floyd", "https://open.spotify.com/track/4cW5n5hcPvnFbaqv5CBLUC"],
        ["Asleep", "The Smiths", "https://open.spotify.com/track/2RxK6pElsaGrE3f8zrIj0l"],
        ["Dream a Little Dream", "Ella Fitzgerald", "https://open.spotify.com/track/5rXYIvVOyUbMqhEHuGcSzp"],
        ["Holocene", "Bon Iver", "https://open.spotify.com/track/40VWLOkdQQ4CrH7dKHTzLH"],
        ["Slow Dancing in a Burning Room", "John Mayer", "https://open.spotify.com/track/36b2aPmBSMDVJHj82BQKBI"],
        ["Sleepyhead", "Passion Pit", "https://open.spotify.com/track/2eSBUYpQLlrBRWAHDSMUiy"],
        ["Night Owl", "Galimatias", "https://open.spotify.com/track/1ySoHjqb0lEDhFjEFAJqiA"],
        ["4AM", "Kaskade", "https://open.spotify.com/track/6JRo09i9gp6VHRkm1m2iMV"],
        ["Skinny Love", "Bon Iver", "https://open.spotify.com/track/5dMGEelyAoSmNjwBMreMpj"],
        ["The Sound of Silence", "Disturbed", "https://open.spotify.com/track/04oOxCQkRFGO3E0Zo2Bmpg"],
    ],
    "hopeful" => [
        ["Here Comes the Sun", "The Beatles", "https://open.spotify.com/track/6dGnYIeXmHdcikdzNNDMm2"],
        ["Don't Stop Me Now", "Queen", "https://open.spotify.com/track/5T8EDUDqKcs6OSOwEsfqG7"],
        ["Beautiful Day", "U2", "https://open.spotify.com/track/1bEf00VE7TA7kXaMrRFp8m"],
        ["Good Life", "OneRepublic", "https://open.spotify.com/track/6XGoLvCmBp58QLgkpNXiMV"],
        ["Rise Up", "Andra Day", "https://open.spotify.com/track/74VOlZXxnOGQkCEZG9Qlqr"],
        ["Hall of Fame", "The Script", "https://open.spotify.com/track/5h07dBKEpAaXUJoZXpnvbq"],
        ["A Sky Full of Stars", "Coldplay", "https://open.spotify.com/track/0BCiDoPW4jL9bSqLBBT28A"],
        ["Brave", "Sara Bareilles", "https://open.spotify.com/track/4RvWPyQ5RL0ao9LPZeSouE"],
        ["Keep Your Head Up", "Ben Howard", "https://open.spotify.com/track/7FGWJg4CxaRGJIEiuqS0TK"],
        ["Count on Me", "Bruno Mars", "https://open.spotify.com/track/4pOkjKMKJ3iBk7HBGolhsS"],
    ],
    "focus" => [
        ["Experience", "Ludovico Einaudi", "https://open.spotify.com/track/1BncfTJAOPVhGSHiHBHLHy"],
        ["Divenire", "Ludovico Einaudi", "https://open.spotify.com/track/7nGYZJiUQXm2ICFNJDQMM5"],
        ["Nuvole Bianche", "Ludovico Einaudi", "https://open.spotify.com/track/5d2VH2mNAkLMXJ3R6zJQnJ"],
        ["Time", "Hans Zimmer", "https://open.spotify.com/track/6ZFbXIJkuI1dVNWvzJzown"],
        ["Comptine d'un autre été", "Yann Tiersen", "https://open.spotify.com/track/2DPYF5oEJPpAFqcmAh78OI"],
        ["Journey", "Hans Zimmer", "https://open.spotify.com/track/5IHnKGDu1VSmqQ4Rn0DMBA"],
        ["Intro", "The xx", "https://open.spotify.com/track/2rcOqNQPDeKYKJPNOfZJlH"],
        ["Midnight City", "M83", "https://open.spotify.com/track/3PGRPmlLKOCBb7jUPeIsp1"],
        ["Interstellar Theme", "Hans Zimmer", "https://open.spotify.com/track/5ItGHWDTOQCrLK8UkzXMUf"],
        ["River Flows in You", "Yiruma", "https://open.spotify.com/track/6HFoLTLbkiEHOhHB4ZZMJI"],
    ],
    "melancholy" => [
        ["Fade Into You", "Mazzy Star", "https://open.spotify.com/track/3J7nTBVvHU1zNmvLYA7Ikq"],
        ["4th of July", "Sufjan Stevens", "https://open.spotify.com/track/2GCmKOQIDm3dxqVnFSQR4M"],
        ["Re: Stacks", "Bon Iver", "https://open.spotify.com/track/2WZzqWt8GCvjFJrpjJrQiJ"],
        ["Flightless Bird", "Iron and Wine", "https://open.spotify.com/track/6rMDvOdYJwJDZY4iqnRCOo"],
        ["Naked as We Came", "Iron and Wine", "https://open.spotify.com/track/4LaBbJzX9lxSPQkSk9F0J1"],
        ["Blue Ridge Mountains", "Fleet Foxes", "https://open.spotify.com/track/5PbdFBVWBaKP2GJpxP8mbS"],
        ["White Winter Hymnal", "Fleet Foxes", "https://open.spotify.com/track/4V9PDMiZWS7QkwEYp4aYPF"],
        ["Motion Picture Soundtrack", "Radiohead", "https://open.spotify.com/track/21lMPuEDGz3tBGnKJlxoEG"],
        ["Death With Dignity", "Sufjan Stevens", "https://open.spotify.com/track/0jYhU8V8wHRjKoG3s7KKgv"],
        ["Casimir Pulaski Day", "Sufjan Stevens", "https://open.spotify.com/track/4EKMWCPRlmI7cZ3CU4XPe3"],
    ],
    "surprised" => [
        ["Blinding Lights", "The Weeknd", "https://open.spotify.com/track/0VjIjW4GlUZAMYd2vXMi3b"],
        ["Levitating", "Dua Lipa", "https://open.spotify.com/track/463CkQjx2Zk1yXoBuierM9"],
        ["Anti-Hero", "Taylor Swift", "https://open.spotify.com/track/0V3wPSX9ygBnCm8psDIegu"],
        ["good 4 u", "Olivia Rodrigo", "https://open.spotify.com/track/4ZtFanR9U6ndgddUvNcjcG"],
        ["As It Was", "Harry Styles", "https://open.spotify.com/track/4Dvkj6JhhA12EX05fT7y2e"],
        ["Stay", "The Kid LAROI & Justin Bieber", "https://open.spotify.com/track/5HCyWlXZPP0y6Gqq8TgA20"],
        ["Dynamite", "BTS", "https://open.spotify.com/track/0t1kP63rueHleOhQkYSXFY"],
        ["Watermelon Sugar", "Harry Styles", "https://open.spotify.com/track/6UelLqGlWMcVH1E5c4Hboy"],
        ["Peaches", "Justin Bieber", "https://open.spotify.com/track/4iJyoBOLtHqaWYs3wyiets"],
        ["MONTERO", "Lil Nas X", "https://open.spotify.com/track/7gJGvKHOBBe1V3eMV7eI8V"],
    ],
    "fearful" => [
        ["Running Up That Hill", "Kate Bush", "https://open.spotify.com/track/75FEaRjZTKLhTrFGsfMUXR"],
        ["Mad World", "Gary Jules", "https://open.spotify.com/track/3JOVTQ5h8HyvI8pZT3gfEG"],
        ["Everybody Hurts", "R.E.M.", "https://open.spotify.com/track/6PypGyiu0Y2lCDBN1XZEnP"],
        ["Creep", "Radiohead", "https://open.spotify.com/track/70LcF31zb1H0PyJoS1Sx1r"],
        ["Black", "Pearl Jam", "https://open.spotify.com/track/4XHjENLCEiMDzsFZgRHQl9"],
        ["The Sound of Silence", "Disturbed", "https://open.spotify.com/track/04oOxCQkRFGO3E0Zo2Bmpg"],
        ["Breathe (2 AM)", "Anna Nalick", "https://open.spotify.com/track/3z50CPJzSVFsOt3EDNfrk1"],
        ["Asleep", "The Smiths", "https://open.spotify.com/track/2RxK6pElsaGrE3f8zrIj0l"],
        ["Motion Picture Soundtrack", "Radiohead", "https://open.spotify.com/track/21lMPuEDGz3tBGnKJlxoEG"],
        ["When It's All Over", "RAIGN", "https://open.spotify.com/search/When+Its+All+Over+RAIGN"],
    ],
    "disgusted" => [
        ["Killing In The Name", "Rage Against The Machine", "https://open.spotify.com/track/59WN2psjkt1tyaxjspN8fp"],
        ["Chop Suey!", "System of a Down", "https://open.spotify.com/track/2CHvBhKGpFMIZUAInCuSPY"],
        ["Last Resort", "Papa Roach", "https://open.spotify.com/track/79cdsBZQLsi5xQANvkJoDA"],
        ["Break Stuff", "Limp Bizkit", "https://open.spotify.com/track/4Gp3rHYILBXQqvF6m4cHlR"],
        ["Du Hast", "Rammstein", "https://open.spotify.com/track/6Ei9WLObWw5OBb0ykxNzxX"],
        ["Basket Case", "Green Day", "https://open.spotify.com/track/7MJQ9Nfxzh8LPZ9e9u68Fq"],
        ["American Idiot", "Green Day", "https://open.spotify.com/track/0JMhSBCCCfFHCRnV8QF0jz"],
        ["Bullet with Butterfly Wings", "Smashing Pumpkins", "https://open.spotify.com/track/5VkqILJxvNnXp2WUNIiACb"],
        ["Down With The Sickness", "Disturbed", "https://open.spotify.com/track/5IqGZpGXPMUqCZhVODqRLD"],
        ["Numb", "Linkin Park", "https://open.spotify.com/track/3igfgOKcSrfBkCBLMrCJyB"],
    ],
    "neutral" => [
        ["Blinding Lights", "The Weeknd", "https://open.spotify.com/track/0VjIjW4GlUZAMYd2vXMi3b"],
        ["Shape of You", "Ed Sheeran", "https://open.spotify.com/track/7qiZfU4dY1lWllzX7mPBI3"],
        ["As It Was", "Harry Styles", "https://open.spotify.com/track/4Dvkj6JhhA12EX05fT7y2e"],
        ["Bad Guy", "Billie Eilish", "https://open.spotify.com/track/2Fxmhks0bxGSBdJ92vM42m"],
        ["Watermelon Sugar", "Harry Styles", "https://open.spotify.com/track/6UelLqGlWMcVH1E5c4Hboy"],
        ["Drivers License", "Olivia Rodrigo", "https://open.spotify.com/track/5wANPM4fQCJwkGd4rN57mH"],
        ["Stay", "The Kid LAROI & Justin Bieber", "https://open.spotify.com/track/5HCyWlXZPP0y6Gqq8TgA20"],
        ["Anti-Hero", "Taylor Swift", "https://open.spotify.com/track/0V3wPSX9ygBnCm8psDIegu"],
        ["good 4 u", "Olivia Rodrigo", "https://open.spotify.com/track/4ZtFanR9U6ndgddUvNcjcG"],
        ["Peaches", "Justin Bieber", "https://open.spotify.com/track/4iJyoBOLtHqaWYs3wyiets"],
    ],
];

$fallbackTracks = $fallback[$mood] ?? $fallback["neutral"];
shuffle($fallbackTracks);

$result = array_map(fn($t) => [
    "name"          => $t[0],
    "artists"       => [["name" => $t[1]]],
    "album"         => ["images" => []],
    "external_urls" => ["spotify" => $t[2]],
    "preview_url"   => null,
], $fallbackTracks);

echo json_encode([
    "success"     => true,
    "mood"        => $mood,
    "method"      => "hard_fallback",
    "spotify_err" => $spotifyError ?? "not_attempted",
    "tracks"      => $result,
]);