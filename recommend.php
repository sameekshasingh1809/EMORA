<?php
/**
 * recommend.php — Mood-based Spotify song recommendations
 * GET ?mood=happy
 * Returns JSON: { success, mood, tracks: [...], method }
 *
 * Flow:
 *  1. Get Spotify OAuth2 token (Client Credentials)
 *  2. Search for tracks using mood-based genre/keyword queries
 *     — Recommendations API was DEPRECATED by Spotify in Nov 2024, use Search instead
 *  3. Return 10 shuffled tracks with real Spotify URLs
 *  4. Fallback to curated list if Spotify is unreachable
 */
header("Content-Type: application/json");

// ── Spotify Credentials ──────────────────────────────────────────────────────
$client_id     = "c4d14c5753144432b3f25541168a1c33";
$client_secret = "e1dd878601684cafaa7904cc45baa2b9";

// ── Validate mood ────────────────────────────────────────────────────────────
$mood = strtolower(trim($_GET['mood'] ?? 'neutral'));
$allowed = ["happy","sad","angry","energetic","romantic","chill","anxious",
            "nostalgic","lonely","confident","tired","hopeful","surprised",
            "neutral","fearful","disgusted","melancholy","focus"];
if (!in_array($mood, $allowed)) $mood = "neutral";

// ── cURL helpers ──────────────────────────────────────────────────────────────
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

// ── Build a guaranteed-working Spotify search URL ────────────────────────────
function spotifySearchUrl(string $song, string $artist): string {
    return "https://open.spotify.com/search/" . rawurlencode($song . " " . $artist);
}

// ── Mood → Multiple Search Queries ───────────────────────────────────────────
// We run 3 different search queries per mood and pool the results,
// then shuffle and return 10. This ensures variety on each refresh.
// NOTE: /v1/recommendations was deprecated by Spotify on Nov 27, 2024.
//       We now use /v1/search which works with Client Credentials.
$moodQueries = [
    "happy"      => ["genre:pop mood:happy", "feel good dance pop", "upbeat happy hits"],
    "sad"        => ["sad heartbreak acoustic", "genre:indie emotional sad", "breakup songs melancholy"],
    "angry"      => ["genre:metal heavy aggressive", "genre:rock angry intense", "genre:punk hard fast"],
    "energetic"  => ["genre:edm high energy workout", "genre:hip-hop pump up", "genre:rock energetic fast"],
    "romantic"   => ["genre:pop romantic love songs", "genre:soul love ballad", "genre:r-n-b romantic smooth"],
    "chill"      => ["genre:chill lo-fi relaxed", "genre:indie chill vibes", "genre:pop mellow easy"],
    "anxious"    => ["genre:classical calm peaceful piano", "ambient relaxing meditation", "genre:acoustic gentle soft"],
    "nostalgic"  => ["genre:rock 80s classic hits", "genre:pop 90s throwback", "genre:alternative 2000s nostalgia"],
    "lonely"     => ["genre:indie lonely solitude", "genre:folk alone quiet", "genre:alternative sad introspective"],
    "confident"  => ["genre:hip-hop confident swagger", "genre:pop empowerment bold", "genre:r-n-b confidence strong"],
    "tired"      => ["genre:ambient sleepy slow", "genre:classical soft quiet night", "genre:acoustic gentle lullaby"],
    "hopeful"    => ["genre:pop uplifting hopeful", "genre:indie optimistic bright", "genre:folk hopeful positive"],
    "focus"      => ["genre:classical focus study", "genre:ambient instrumental focus", "genre:piano concentration work"],
    "melancholy" => ["genre:indie melancholy bittersweet", "genre:folk sad quiet", "genre:alternative introspective"],
    "surprised"  => ["genre:pop unexpected surprise upbeat", "genre:edm exciting energy", "genre:pop viral trending"],
    "fearful"    => ["genre:ambient dark tense", "genre:classical dramatic intense", "genre:indie dark moody"],
    "disgusted"  => ["genre:punk angry protest", "genre:metal aggressive heavy", "genre:rock rebellious"],
    "neutral"    => ["genre:pop top hits", "genre:indie popular", "genre:rock mainstream"],
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
    elseif ($tokenHttp === 401) $spotifyError = "Invalid Spotify credentials (401).";
    elseif ($tokenHttp === 400) $spotifyError = "Bad request to Spotify token endpoint (400).";
    else                        $spotifyError = "Token error HTTP $tokenHttp: " . ($tokenData['error_description'] ?? $tokenData['error'] ?? 'unknown');
    goto use_fallback;
}

$token   = $tokenData['access_token'];
$queries = $moodQueries[$mood] ?? $moodQueries['neutral'];

// ── Step 2: Search API — run multiple queries for variety ─────────────────────
// Use a random offset (0–20) so repeated requests return different results
$allTracks = [];

foreach ($queries as $q) {
    $offset = rand(0, 20); // randomise offset for variety on refresh
    $url = "https://api.spotify.com/v1/search?" . http_build_query([
        "q"      => $q,
        "type"   => "track",
        "limit"  => 15,
        "offset" => $offset,
    ]);

    [$searchRaw, $searchErr, $searchHttp] = curlGet($url, ["Authorization: Bearer $token"]);

    if ($searchErr || $searchHttp !== 200) {
        // record first error but keep trying other queries
        if (!$spotifyError) {
            $decoded = json_decode($searchRaw, true);
            $spotifyError = $searchErr ?: "Search API HTTP $searchHttp: " . ($decoded['error']['message'] ?? 'unknown');
        }
        continue;
    }

    $searchData = json_decode($searchRaw, true);
    $items      = $searchData['tracks']['items'] ?? [];
    $allTracks  = array_merge($allTracks, $items);
}

if (empty($allTracks)) {
    if (!$spotifyError) $spotifyError = "Search returned no tracks for mood: $mood";
    goto use_fallback;
}

// Deduplicate by track ID, filter out tracks without Spotify URLs
$seen   = [];
$unique = [];
foreach ($allTracks as $t) {
    if (!empty($t['id']) && !isset($seen[$t['id']]) && !empty($t['external_urls']['spotify'])) {
        $seen[$t['id']] = true;
        $unique[] = $t;
    }
}

if (empty($unique)) {
    $spotifyError = "No unique tracks found after dedup for mood: $mood";
    goto use_fallback;
}

// Shuffle and slice to 10
shuffle($unique);
$unique = array_slice($unique, 0, 10);

$result = array_map(fn($t) => [
    "name"          => $t['name'],
    "artists"       => [["name" => $t['artists'][0]['name'] ?? 'Unknown']],
    "album"         => ["images" => $t['album']['images'] ?? []],
    "external_urls" => ["spotify" => $t['external_urls']['spotify']],
    "preview_url"   => $t['preview_url'] ?? null,
], $unique);

echo json_encode(["success" => true, "mood" => $mood, "method" => "spotify_search", "tracks" => $result]);
exit;

// ── Curated Fallback ──────────────────────────────────────────────────────────
use_fallback:

$fallback = [
    "happy" => [
        ["Happy",                    "Pharrell Williams",      spotifySearchUrl("Happy",                    "Pharrell Williams")],
        ["Uptown Funk",              "Mark Ronson",            spotifySearchUrl("Uptown Funk",              "Mark Ronson Bruno Mars")],
        ["Can't Stop the Feeling!", "Justin Timberlake",      spotifySearchUrl("Can't Stop the Feeling",   "Justin Timberlake")],
        ["Shake It Off",             "Taylor Swift",           spotifySearchUrl("Shake It Off",             "Taylor Swift")],
        ["Levitating",               "Dua Lipa",               spotifySearchUrl("Levitating",               "Dua Lipa")],
        ["September",                "Earth, Wind & Fire",     spotifySearchUrl("September",                "Earth Wind Fire")],
        ["Good as Hell",             "Lizzo",                  spotifySearchUrl("Good as Hell",             "Lizzo")],
        ["Blinding Lights",          "The Weeknd",             spotifySearchUrl("Blinding Lights",          "The Weeknd")],
        ["Sunflower",                "Post Malone",            spotifySearchUrl("Sunflower",                "Post Malone Swae Lee")],
        ["Walking on Sunshine",      "Katrina & The Waves",    spotifySearchUrl("Walking on Sunshine",      "Katrina and the Waves")],
        ["Dynamite",                 "BTS",                    spotifySearchUrl("Dynamite",                 "BTS")],
        ["Watermelon Sugar",         "Harry Styles",           spotifySearchUrl("Watermelon Sugar",         "Harry Styles")],
        ["As It Was",                "Harry Styles",           spotifySearchUrl("As It Was",                "Harry Styles")],
        ["Flowers",                  "Miley Cyrus",            spotifySearchUrl("Flowers",                  "Miley Cyrus")],
        ["Starboy",                  "The Weeknd",             spotifySearchUrl("Starboy",                  "The Weeknd")],
    ],
    "sad" => [
        ["Someone Like You",         "Adele",                  spotifySearchUrl("Someone Like You",         "Adele")],
        ["The Night We Met",         "Lord Huron",             spotifySearchUrl("The Night We Met",         "Lord Huron")],
        ["Fix You",                  "Coldplay",               spotifySearchUrl("Fix You",                  "Coldplay")],
        ["All I Want",               "Kodaline",               spotifySearchUrl("All I Want",               "Kodaline")],
        ["Let Her Go",               "Passenger",              spotifySearchUrl("Let Her Go",               "Passenger")],
        ["Skinny Love",              "Bon Iver",               spotifySearchUrl("Skinny Love",              "Bon Iver")],
        ["Chasing Cars",             "Snow Patrol",            spotifySearchUrl("Chasing Cars",             "Snow Patrol")],
        ["The Scientist",            "Coldplay",               spotifySearchUrl("The Scientist",            "Coldplay")],
        ["Liability",                "Lorde",                  spotifySearchUrl("Liability",                "Lorde")],
        ["drivers license",          "Olivia Rodrigo",         spotifySearchUrl("drivers license",          "Olivia Rodrigo")],
        ["Happier",                  "Olivia Rodrigo",         spotifySearchUrl("Happier",                  "Olivia Rodrigo")],
        ["Motion Sickness",          "Phoebe Bridgers",        spotifySearchUrl("Motion Sickness",          "Phoebe Bridgers")],
        ["Ghost",                    "Justin Bieber",          spotifySearchUrl("Ghost",                    "Justin Bieber")],
        ["Before He Cheats",         "Carrie Underwood",       spotifySearchUrl("Before He Cheats",         "Carrie Underwood")],
        ["When the Party's Over",    "Billie Eilish",          spotifySearchUrl("When the Party's Over",    "Billie Eilish")],
    ],
    "angry" => [
        ["Killing In The Name",      "Rage Against The Machine", spotifySearchUrl("Killing In The Name",   "Rage Against The Machine")],
        ["Numb",                     "Linkin Park",              spotifySearchUrl("Numb",                  "Linkin Park")],
        ["Given Up",                 "Linkin Park",              spotifySearchUrl("Given Up",              "Linkin Park")],
        ["Enter Sandman",            "Metallica",                spotifySearchUrl("Enter Sandman",         "Metallica")],
        ["Chop Suey!",               "System of a Down",         spotifySearchUrl("Chop Suey",            "System of a Down")],
        ["Last Resort",              "Papa Roach",               spotifySearchUrl("Last Resort",           "Papa Roach")],
        ["Break Stuff",              "Limp Bizkit",              spotifySearchUrl("Break Stuff",           "Limp Bizkit")],
        ["Bulls on Parade",          "Rage Against The Machine", spotifySearchUrl("Bulls on Parade",       "Rage Against The Machine")],
        ["Down With The Sickness",   "Disturbed",                spotifySearchUrl("Down With The Sickness","Disturbed")],
        ["Du Hast",                  "Rammstein",                spotifySearchUrl("Du Hast",               "Rammstein")],
        ["Crawling",                 "Linkin Park",              spotifySearchUrl("Crawling",              "Linkin Park")],
        ["In The End",               "Linkin Park",              spotifySearchUrl("In The End",            "Linkin Park")],
        ["Wake Up",                  "Rage Against The Machine", spotifySearchUrl("Wake Up",               "Rage Against The Machine")],
        ["Master of Puppets",        "Metallica",                spotifySearchUrl("Master of Puppets",     "Metallica")],
        ["Bodies",                   "Drowning Pool",            spotifySearchUrl("Bodies",                "Drowning Pool")],
    ],
    "energetic" => [
        ["Lose Yourself",            "Eminem",                 spotifySearchUrl("Lose Yourself",            "Eminem")],
        ["Eye of the Tiger",         "Survivor",               spotifySearchUrl("Eye of the Tiger",         "Survivor")],
        ["Radioactive",              "Imagine Dragons",        spotifySearchUrl("Radioactive",              "Imagine Dragons")],
        ["Titanium",                 "David Guetta ft. Sia",   spotifySearchUrl("Titanium",                 "David Guetta Sia")],
        ["Turn Down for What",       "DJ Snake & Lil Jon",     spotifySearchUrl("Turn Down for What",       "DJ Snake Lil Jon")],
        ["Stronger",                 "Kanye West",             spotifySearchUrl("Stronger",                 "Kanye West")],
        ["Thunderstruck",            "AC/DC",                  spotifySearchUrl("Thunderstruck",            "ACDC")],
        ["Till I Collapse",          "Eminem",                 spotifySearchUrl("Till I Collapse",          "Eminem")],
        ["Run The World (Girls)",    "Beyoncé",                spotifySearchUrl("Run The World Girls",      "Beyonce")],
        ["Jump Around",              "House of Pain",          spotifySearchUrl("Jump Around",              "House of Pain")],
        ["Power",                    "Kanye West",             spotifySearchUrl("Power",                    "Kanye West")],
        ["SICKO MODE",               "Travis Scott",           spotifySearchUrl("SICKO MODE",               "Travis Scott")],
        ["God's Plan",               "Drake",                  spotifySearchUrl("God's Plan",               "Drake")],
        ["Rockstar",                 "Post Malone",            spotifySearchUrl("Rockstar",                 "Post Malone")],
        ["Humble",                   "Kendrick Lamar",         spotifySearchUrl("Humble",                   "Kendrick Lamar")],
    ],
    "romantic" => [
        ["Perfect",                  "Ed Sheeran",             spotifySearchUrl("Perfect",                  "Ed Sheeran")],
        ["All of Me",                "John Legend",            spotifySearchUrl("All of Me",                "John Legend")],
        ["A Thousand Years",         "Christina Perri",        spotifySearchUrl("A Thousand Years",         "Christina Perri")],
        ["Thinking Out Loud",        "Ed Sheeran",             spotifySearchUrl("Thinking Out Loud",        "Ed Sheeran")],
        ["Can't Help Falling In Love","Elvis Presley",         spotifySearchUrl("Can't Help Falling In Love","Elvis Presley")],
        ["Lover",                    "Taylor Swift",           spotifySearchUrl("Lover",                    "Taylor Swift")],
        ["Make You Feel My Love",    "Adele",                  spotifySearchUrl("Make You Feel My Love",    "Adele")],
        ["Just the Way You Are",     "Bruno Mars",             spotifySearchUrl("Just the Way You Are",     "Bruno Mars")],
        ["Crazy in Love",            "Beyoncé",                spotifySearchUrl("Crazy in Love",            "Beyonce")],
        ["Everything",               "Michael Bublé",          spotifySearchUrl("Everything",               "Michael Buble")],
        ["Marry You",                "Bruno Mars",             spotifySearchUrl("Marry You",                "Bruno Mars")],
        ["Die For You",              "The Weeknd",             spotifySearchUrl("Die For You",              "The Weeknd")],
        ["Enchanted",                "Taylor Swift",           spotifySearchUrl("Enchanted",                "Taylor Swift")],
        ["At Last",                  "Etta James",             spotifySearchUrl("At Last",                  "Etta James")],
        ["La Vie En Rose",           "Édith Piaf",             spotifySearchUrl("La Vie En Rose",           "Edith Piaf")],
    ],
    "chill" => [
        ["Redbone",                  "Childish Gambino",       spotifySearchUrl("Redbone",                  "Childish Gambino")],
        ["Sunday Morning",           "Maroon 5",               spotifySearchUrl("Sunday Morning",           "Maroon 5")],
        ["Golden Hour",              "JVKE",                   spotifySearchUrl("Golden Hour",              "JVKE")],
        ["Electric Feel",            "MGMT",                   spotifySearchUrl("Electric Feel",            "MGMT")],
        ["Yellow",                   "Coldplay",               spotifySearchUrl("Yellow",                   "Coldplay")],
        ["coffee",                   "beabadoobee",            spotifySearchUrl("coffee",                   "beabadoobee")],
        ["Banana Pancakes",          "Jack Johnson",           spotifySearchUrl("Banana Pancakes",          "Jack Johnson")],
        ["I'm Yours",                "Jason Mraz",             spotifySearchUrl("I'm Yours",                "Jason Mraz")],
        ["Bloom",                    "The Paper Kites",        spotifySearchUrl("Bloom",                    "The Paper Kites")],
        ["Sunset Lover",             "Petit Biscuit",          spotifySearchUrl("Sunset Lover",             "Petit Biscuit")],
        ["Stick Season",             "Noah Kahan",             spotifySearchUrl("Stick Season",             "Noah Kahan")],
        ["Featherstone",             "The Paper Kites",        spotifySearchUrl("Featherstone",             "The Paper Kites")],
        ["Skinny Love",              "Bon Iver",               spotifySearchUrl("Skinny Love",              "Bon Iver")],
        ["Chlorine",                 "Twenty One Pilots",      spotifySearchUrl("Chlorine",                 "Twenty One Pilots")],
        ["Do I Wanna Know?",         "Arctic Monkeys",         spotifySearchUrl("Do I Wanna Know",          "Arctic Monkeys")],
    ],
    "anxious" => [
        ["Weightless",               "Marconi Union",          spotifySearchUrl("Weightless",               "Marconi Union")],
        ["Experience",               "Ludovico Einaudi",       spotifySearchUrl("Experience",               "Ludovico Einaudi")],
        ["River Flows in You",       "Yiruma",                 spotifySearchUrl("River Flows in You",       "Yiruma")],
        ["Breathe (2 AM)",           "Anna Nalick",            spotifySearchUrl("Breathe 2 AM",             "Anna Nalick")],
        ["Let It Be",                "The Beatles",            spotifySearchUrl("Let It Be",                "The Beatles")],
        ["Nuvole Bianche",           "Ludovico Einaudi",       spotifySearchUrl("Nuvole Bianche",           "Ludovico Einaudi")],
        ["The Sound of Silence",     "Simon & Garfunkel",      spotifySearchUrl("The Sound of Silence",     "Simon and Garfunkel")],
        ["Holocene",                 "Bon Iver",               spotifySearchUrl("Holocene",                 "Bon Iver")],
        ["Clair de Lune",            "Claude Debussy",         spotifySearchUrl("Clair de Lune",            "Debussy")],
        ["In My Room",               "The Beach Boys",         spotifySearchUrl("In My Room",               "The Beach Boys")],
        ["Saturn",                   "Stevie Wonder",          spotifySearchUrl("Saturn",                   "Stevie Wonder")],
        ["The Night Will Always Win","Manchester Orchestra",   spotifySearchUrl("The Night Will Always Win","Manchester Orchestra")],
        ["Liability",                "Lorde",                  spotifySearchUrl("Liability",                "Lorde")],
        ["Smother",                  "Daughter",               spotifySearchUrl("Smother",                  "Daughter")],
        ["Youth",                    "Daughter",               spotifySearchUrl("Youth",                    "Daughter")],
    ],
    "nostalgic" => [
        ["Africa",                   "Toto",                   spotifySearchUrl("Africa",                   "Toto")],
        ["Summer of '69",            "Bryan Adams",            spotifySearchUrl("Summer of 69",             "Bryan Adams")],
        ["Don't You (Forget About Me)","Simple Minds",         spotifySearchUrl("Don't You Forget About Me","Simple Minds")],
        ["Fast Car",                 "Tracy Chapman",          spotifySearchUrl("Fast Car",                 "Tracy Chapman")],
        ["Zombie",                   "The Cranberries",        spotifySearchUrl("Zombie",                   "The Cranberries")],
        ["Smells Like Teen Spirit",  "Nirvana",                spotifySearchUrl("Smells Like Teen Spirit",  "Nirvana")],
        ["Wonderwall",               "Oasis",                  spotifySearchUrl("Wonderwall",               "Oasis")],
        ["Mr. Jones",                "Counting Crows",         spotifySearchUrl("Mr Jones",                 "Counting Crows")],
        ["Semi-Charmed Life",        "Third Eye Blind",        spotifySearchUrl("Semi-Charmed Life",        "Third Eye Blind")],
        ["Come As You Are",          "Nirvana",                spotifySearchUrl("Come As You Are",          "Nirvana")],
        ["Bohemian Rhapsody",        "Queen",                  spotifySearchUrl("Bohemian Rhapsody",        "Queen")],
        ["Hotel California",         "Eagles",                 spotifySearchUrl("Hotel California",         "Eagles")],
        ["Sweet Child O' Mine",      "Guns N' Roses",          spotifySearchUrl("Sweet Child O Mine",       "Guns N Roses")],
        ["Losing My Religion",       "R.E.M.",                 spotifySearchUrl("Losing My Religion",       "REM")],
        ["With or Without You",      "U2",                     spotifySearchUrl("With or Without You",      "U2")],
    ],
    "lonely" => [
        ["Iris",                     "Goo Goo Dolls",          spotifySearchUrl("Iris",                     "Goo Goo Dolls")],
        ["Eleanor Rigby",            "The Beatles",            spotifySearchUrl("Eleanor Rigby",            "The Beatles")],
        ["How to Save a Life",       "The Fray",               spotifySearchUrl("How to Save a Life",       "The Fray")],
        ["Lonely",                   "Akon",                   spotifySearchUrl("Lonely",                   "Akon")],
        ["Creep",                    "Radiohead",              spotifySearchUrl("Creep",                    "Radiohead")],
        ["Unwell",                   "Matchbox Twenty",        spotifySearchUrl("Unwell",                   "Matchbox Twenty")],
        ["Mad World",                "Gary Jules",             spotifySearchUrl("Mad World",                "Gary Jules")],
        ["The Sound of Silence",     "Simon & Garfunkel",      spotifySearchUrl("The Sound of Silence",     "Simon and Garfunkel")],
        ["Black",                    "Pearl Jam",              spotifySearchUrl("Black",                    "Pearl Jam")],
        ["I Am a Rock",              "Simon & Garfunkel",      spotifySearchUrl("I Am a Rock",              "Simon and Garfunkel")],
        ["Motion Sickness",          "Phoebe Bridgers",        spotifySearchUrl("Motion Sickness",          "Phoebe Bridgers")],
        ["Sober",                    "Childish Gambino",       spotifySearchUrl("Sober",                    "Childish Gambino")],
        ["Me and My Shadow",         "Frank Sinatra",          spotifySearchUrl("Me and My Shadow",         "Frank Sinatra")],
        ["Mr. Lonely",               "Bobby Vinton",           spotifySearchUrl("Mr Lonely",                "Bobby Vinton")],
        ["Alone Again",              "Gilbert O'Sullivan",     spotifySearchUrl("Alone Again",              "Gilbert O'Sullivan")],
    ],
    "confident" => [
        ["Roar",                     "Katy Perry",             spotifySearchUrl("Roar",                     "Katy Perry")],
        ["HUMBLE.",                  "Kendrick Lamar",         spotifySearchUrl("HUMBLE",                   "Kendrick Lamar")],
        ["Stronger (What Doesn't Kill You)","Kelly Clarkson",  spotifySearchUrl("Stronger",                 "Kelly Clarkson")],
        ["We Will Rock You",         "Queen",                  spotifySearchUrl("We Will Rock You",         "Queen")],
        ["Can't Hold Us",            "Macklemore",             spotifySearchUrl("Can't Hold Us",            "Macklemore Ryan Lewis")],
        ["Fight Song",               "Rachel Platten",         spotifySearchUrl("Fight Song",               "Rachel Platten")],
        ["Stronger",                 "Kanye West",             spotifySearchUrl("Stronger",                 "Kanye West")],
        ["Till I Collapse",          "Eminem",                 spotifySearchUrl("Till I Collapse",          "Eminem")],
        ["Run the World (Girls)",    "Beyoncé",                spotifySearchUrl("Run the World Girls",      "Beyonce")],
        ["Started From the Bottom",  "Drake",                  spotifySearchUrl("Started From the Bottom",  "Drake")],
        ["Power",                    "Kanye West",             spotifySearchUrl("Power",                    "Kanye West")],
        ["Baddest",                  "K/DA",                   spotifySearchUrl("Baddest",                  "KDA")],
        ["Formation",                "Beyoncé",                spotifySearchUrl("Formation",                "Beyonce")],
        ["Not Afraid",               "Eminem",                 spotifySearchUrl("Not Afraid",               "Eminem")],
        ["Work B**ch",               "Britney Spears",         spotifySearchUrl("Work Bitch",               "Britney Spears")],
    ],
    "tired" => [
        ["Breathe",                  "Pink Floyd",             spotifySearchUrl("Breathe",                  "Pink Floyd")],
        ["Asleep",                   "The Smiths",             spotifySearchUrl("Asleep",                   "The Smiths")],
        ["Holocene",                 "Bon Iver",               spotifySearchUrl("Holocene",                 "Bon Iver")],
        ["Slow Dancing in a Burning Room","John Mayer",        spotifySearchUrl("Slow Dancing in a Burning Room","John Mayer")],
        ["Dream a Little Dream",     "Ella Fitzgerald",        spotifySearchUrl("Dream a Little Dream",     "Ella Fitzgerald")],
        ["Skinny Love",              "Bon Iver",               spotifySearchUrl("Skinny Love",              "Bon Iver")],
        ["Be Still",                 "The Killers",            spotifySearchUrl("Be Still",                 "The Killers")],
        ["Such Great Heights",       "The Postal Service",     spotifySearchUrl("Such Great Heights",       "The Postal Service")],
        ["Lua",                      "Bright Eyes",            spotifySearchUrl("Lua",                      "Bright Eyes")],
        ["Naked as We Came",         "Iron and Wine",          spotifySearchUrl("Naked as We Came",         "Iron and Wine")],
        ["Black Swan",               "Thom Yorke",             spotifySearchUrl("Black Swan",               "Thom Yorke")],
        ["Hide and Seek",            "Imogen Heap",            spotifySearchUrl("Hide and Seek",            "Imogen Heap")],
        ["Motion Picture Soundtrack","Radiohead",              spotifySearchUrl("Motion Picture Soundtrack","Radiohead")],
        ["Lullaby",                  "Sia",                    spotifySearchUrl("Lullaby",                  "Sia")],
        ["Breathe Me",               "Sia",                    spotifySearchUrl("Breathe Me",               "Sia")],
    ],
    "hopeful" => [
        ["Here Comes the Sun",       "The Beatles",            spotifySearchUrl("Here Comes the Sun",       "The Beatles")],
        ["Beautiful Day",            "U2",                     spotifySearchUrl("Beautiful Day",            "U2")],
        ["Rise Up",                  "Andra Day",              spotifySearchUrl("Rise Up",                  "Andra Day")],
        ["Good Life",                "OneRepublic",            spotifySearchUrl("Good Life",                "OneRepublic")],
        ["Don't Stop Me Now",        "Queen",                  spotifySearchUrl("Don't Stop Me Now",        "Queen")],
        ["A Sky Full of Stars",      "Coldplay",               spotifySearchUrl("A Sky Full of Stars",      "Coldplay")],
        ["Brave",                    "Sara Bareilles",         spotifySearchUrl("Brave",                    "Sara Bareilles")],
        ["Hall of Fame",             "The Script",             spotifySearchUrl("Hall of Fame",             "The Script")],
        ["Dog Days Are Over",        "Florence + The Machine", spotifySearchUrl("Dog Days Are Over",        "Florence and The Machine")],
        ["Count on Me",              "Bruno Mars",             spotifySearchUrl("Count on Me",              "Bruno Mars")],
        ["Better Days",              "OneRepublic",            spotifySearchUrl("Better Days",              "OneRepublic")],
        ["The Middle",               "Jimmy Eat World",        spotifySearchUrl("The Middle",               "Jimmy Eat World")],
        ["Eye of the Tiger",         "Survivor",               spotifySearchUrl("Eye of the Tiger",         "Survivor")],
        ["Unwritten",                "Natasha Bedingfield",    spotifySearchUrl("Unwritten",                "Natasha Bedingfield")],
        ["I Gotta Feeling",          "The Black Eyed Peas",    spotifySearchUrl("I Gotta Feeling",          "The Black Eyed Peas")],
    ],
    "focus" => [
        ["Experience",               "Ludovico Einaudi",       spotifySearchUrl("Experience",               "Ludovico Einaudi")],
        ["Time",                     "Hans Zimmer",            spotifySearchUrl("Time",                     "Hans Zimmer Inception")],
        ["River Flows in You",       "Yiruma",                 spotifySearchUrl("River Flows in You",       "Yiruma")],
        ["Nuvole Bianche",           "Ludovico Einaudi",       spotifySearchUrl("Nuvole Bianche",           "Ludovico Einaudi")],
        ["Divenire",                 "Ludovico Einaudi",       spotifySearchUrl("Divenire",                 "Ludovico Einaudi")],
        ["Intro",                    "The xx",                 spotifySearchUrl("Intro",                    "The xx")],
        ["Midnight City",            "M83",                    spotifySearchUrl("Midnight City",            "M83")],
        ["Clair de Lune",            "Claude Debussy",         spotifySearchUrl("Clair de Lune",            "Debussy")],
        ["On the Nature of Daylight","Max Richter",            spotifySearchUrl("On the Nature of Daylight","Max Richter")],
        ["Gymnopédie No.1",          "Erik Satie",             spotifySearchUrl("Gymnopedie No 1",          "Erik Satie")],
        ["Comptine d'un autre été",  "Yann Tiersen",           spotifySearchUrl("Comptine d un autre ete",  "Yann Tiersen")],
        ["An Ending (Ascent)",       "Brian Eno",              spotifySearchUrl("An Ending Ascent",         "Brian Eno")],
        ["Lost in Thought",          "Hans Zimmer",            spotifySearchUrl("Lost in Thought",          "Hans Zimmer")],
        ["Cornfield Chase",          "Hans Zimmer",            spotifySearchUrl("Cornfield Chase",          "Hans Zimmer")],
        ["First Steps",              "Hans Zimmer",            spotifySearchUrl("First Steps",              "Hans Zimmer Interstellar")],
    ],
    "melancholy" => [
        ["Fade Into You",            "Mazzy Star",             spotifySearchUrl("Fade Into You",            "Mazzy Star")],
        ["Re: Stacks",               "Bon Iver",               spotifySearchUrl("Re Stacks",                "Bon Iver")],
        ["Flightless Bird",          "Iron and Wine",          spotifySearchUrl("Flightless Bird",          "Iron and Wine")],
        ["White Winter Hymnal",      "Fleet Foxes",            spotifySearchUrl("White Winter Hymnal",      "Fleet Foxes")],
        ["Motion Picture Soundtrack","Radiohead",              spotifySearchUrl("Motion Picture Soundtrack","Radiohead")],
        ["Blue Ridge Mountains",     "Fleet Foxes",            spotifySearchUrl("Blue Ridge Mountains",     "Fleet Foxes")],
        ["4th of July",              "Sufjan Stevens",         spotifySearchUrl("4th of July",              "Sufjan Stevens")],
        ["Naked as We Came",         "Iron and Wine",          spotifySearchUrl("Naked as We Came",         "Iron and Wine")],
        ["Skinny Love",              "Bon Iver",               spotifySearchUrl("Skinny Love",              "Bon Iver")],
        ["Holocene",                 "Bon Iver",               spotifySearchUrl("Holocene",                 "Bon Iver")],
        ["Death With Dignity",       "Sufjan Stevens",         spotifySearchUrl("Death With Dignity",       "Sufjan Stevens")],
        ["Poison & Wine",            "The Civil Wars",         spotifySearchUrl("Poison and Wine",          "The Civil Wars")],
        ["From the Morning",         "Nick Drake",             spotifySearchUrl("From the Morning",         "Nick Drake")],
        ["Pink Moon",                "Nick Drake",             spotifySearchUrl("Pink Moon",                "Nick Drake")],
        ["Breathe Me",               "Sia",                    spotifySearchUrl("Breathe Me",               "Sia")],
    ],
    "surprised" => [
        ["Blinding Lights",          "The Weeknd",             spotifySearchUrl("Blinding Lights",          "The Weeknd")],
        ["Levitating",               "Dua Lipa",               spotifySearchUrl("Levitating",               "Dua Lipa")],
        ["Anti-Hero",                "Taylor Swift",           spotifySearchUrl("Anti-Hero",                "Taylor Swift")],
        ["good 4 u",                 "Olivia Rodrigo",         spotifySearchUrl("good 4 u",                 "Olivia Rodrigo")],
        ["As It Was",                "Harry Styles",           spotifySearchUrl("As It Was",                "Harry Styles")],
        ["Dynamite",                 "BTS",                    spotifySearchUrl("Dynamite",                 "BTS")],
        ["Watermelon Sugar",         "Harry Styles",           spotifySearchUrl("Watermelon Sugar",         "Harry Styles")],
        ["Stay",                     "The Kid LAROI",          spotifySearchUrl("Stay",                     "The Kid LAROI Justin Bieber")],
        ["abcdefu",                  "GAYLE",                  spotifySearchUrl("abcdefu",                  "GAYLE")],
        ["Heat Waves",               "Glass Animals",          spotifySearchUrl("Heat Waves",               "Glass Animals")],
        ["Physical",                 "Dua Lipa",               spotifySearchUrl("Physical",                 "Dua Lipa")],
        ["Bad Guy",                  "Billie Eilish",          spotifySearchUrl("Bad Guy",                  "Billie Eilish")],
        ["Flowers",                  "Miley Cyrus",            spotifySearchUrl("Flowers",                  "Miley Cyrus")],
        ["Shivers",                  "Ed Sheeran",             spotifySearchUrl("Shivers",                  "Ed Sheeran")],
        ["Montero",                  "Lil Nas X",              spotifySearchUrl("Montero Call Me By Your Name","Lil Nas X")],
    ],
    "fearful" => [
        ["Running Up That Hill",     "Kate Bush",              spotifySearchUrl("Running Up That Hill",     "Kate Bush")],
        ["Mad World",                "Gary Jules",             spotifySearchUrl("Mad World",                "Gary Jules")],
        ["Creep",                    "Radiohead",              spotifySearchUrl("Creep",                    "Radiohead")],
        ["Everybody Hurts",          "R.E.M.",                 spotifySearchUrl("Everybody Hurts",          "REM")],
        ["Black",                    "Pearl Jam",              spotifySearchUrl("Black",                    "Pearl Jam")],
        ["The Sound of Silence",     "Disturbed",              spotifySearchUrl("The Sound of Silence",     "Disturbed")],
        ["Breathe (2 AM)",           "Anna Nalick",            spotifySearchUrl("Breathe 2 AM",             "Anna Nalick")],
        ["Asleep",                   "The Smiths",             spotifySearchUrl("Asleep",                   "The Smiths")],
        ["Motion Picture Soundtrack","Radiohead",              spotifySearchUrl("Motion Picture Soundtrack","Radiohead")],
        ["Holocene",                 "Bon Iver",               spotifySearchUrl("Holocene",                 "Bon Iver")],
        ["Exit Music (For a Film)",  "Radiohead",              spotifySearchUrl("Exit Music For a Film",    "Radiohead")],
        ["I'm So Tired...",          "Lauv & Troye Sivan",     spotifySearchUrl("I'm So Tired",             "Lauv Troye Sivan")],
        ["Smother",                  "Daughter",               spotifySearchUrl("Smother",                  "Daughter")],
        ["Youth",                    "Daughter",               spotifySearchUrl("Youth",                    "Daughter")],
        ["Shallows",                 "Daughter",               spotifySearchUrl("Shallows",                 "Daughter")],
    ],
    "disgusted" => [
        ["Killing In The Name",      "Rage Against The Machine", spotifySearchUrl("Killing In The Name",   "Rage Against The Machine")],
        ["Chop Suey!",               "System of a Down",         spotifySearchUrl("Chop Suey",            "System of a Down")],
        ["Numb",                     "Linkin Park",              spotifySearchUrl("Numb",                  "Linkin Park")],
        ["American Idiot",           "Green Day",                spotifySearchUrl("American Idiot",        "Green Day")],
        ["Basket Case",              "Green Day",                spotifySearchUrl("Basket Case",           "Green Day")],
        ["Break Stuff",              "Limp Bizkit",              spotifySearchUrl("Break Stuff",           "Limp Bizkit")],
        ["Bullet with Butterfly Wings","Smashing Pumpkins",      spotifySearchUrl("Bullet with Butterfly Wings","Smashing Pumpkins")],
        ["Down With The Sickness",   "Disturbed",                spotifySearchUrl("Down With The Sickness","Disturbed")],
        ["Du Hast",                  "Rammstein",                spotifySearchUrl("Du Hast",               "Rammstein")],
        ["Bulls on Parade",          "Rage Against The Machine", spotifySearchUrl("Bulls on Parade",       "Rage Against The Machine")],
        ["Welcome to the Black Parade","My Chemical Romance",    spotifySearchUrl("Welcome to the Black Parade","My Chemical Romance")],
        ["Helena",                   "My Chemical Romance",      spotifySearchUrl("Helena",                "My Chemical Romance")],
        ["I'm Not Okay",             "My Chemical Romance",      spotifySearchUrl("I'm Not Okay",          "My Chemical Romance")],
        ["Sugar We're Goin Down",    "Fall Out Boy",             spotifySearchUrl("Sugar We're Goin Down", "Fall Out Boy")],
        ["Dance Dance",              "Fall Out Boy",             spotifySearchUrl("Dance Dance",           "Fall Out Boy")],
    ],
    "neutral" => [
        ["Blinding Lights",          "The Weeknd",             spotifySearchUrl("Blinding Lights",          "The Weeknd")],
        ["Shape of You",             "Ed Sheeran",             spotifySearchUrl("Shape of You",             "Ed Sheeran")],
        ["As It Was",                "Harry Styles",           spotifySearchUrl("As It Was",                "Harry Styles")],
        ["Bad Guy",                  "Billie Eilish",          spotifySearchUrl("Bad Guy",                  "Billie Eilish")],
        ["Watermelon Sugar",         "Harry Styles",           spotifySearchUrl("Watermelon Sugar",         "Harry Styles")],
        ["Anti-Hero",                "Taylor Swift",           spotifySearchUrl("Anti-Hero",                "Taylor Swift")],
        ["good 4 u",                 "Olivia Rodrigo",         spotifySearchUrl("good 4 u",                 "Olivia Rodrigo")],
        ["Stay",                     "The Kid LAROI",          spotifySearchUrl("Stay",                     "The Kid LAROI Justin Bieber")],
        ["Heat Waves",               "Glass Animals",          spotifySearchUrl("Heat Waves",               "Glass Animals")],
        ["Levitating",               "Dua Lipa",               spotifySearchUrl("Levitating",               "Dua Lipa")],
        ["Flowers",                  "Miley Cyrus",            spotifySearchUrl("Flowers",                  "Miley Cyrus")],
        ["Shivers",                  "Ed Sheeran",             spotifySearchUrl("Shivers",                  "Ed Sheeran")],
        ["Golden Hour",              "JVKE",                   spotifySearchUrl("Golden Hour",              "JVKE")],
        ["Calm Down",                "Rema & Selena Gomez",    spotifySearchUrl("Calm Down",                "Rema Selena Gomez")],
        ["Rich Flex",                "Drake & 21 Savage",      spotifySearchUrl("Rich Flex",                "Drake 21 Savage")],
    ],
];

// Expand fallback list, shuffle, return 10
$fallbackTracks = $fallback[$mood] ?? $fallback["neutral"];

// Use microsecond seed for better randomness on rapid refreshes
mt_srand((int)(microtime(true) * 1000));
shuffle($fallbackTracks);

$result = array_map(fn($t) => [
    "name"          => $t[0],
    "artists"       => [["name" => $t[1]]],
    "album"         => ["images" => []],
    "external_urls" => ["spotify" => $t[2]],
    "preview_url"   => null,
], array_slice($fallbackTracks, 0, 10));

echo json_encode([
    "success"     => true,
    "mood"        => $mood,
    "method"      => "hard_fallback",
    "spotify_err" => $spotifyError ?? "not_attempted",
    "tracks"      => $result,
]);
