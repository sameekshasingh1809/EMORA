<?php
/**
 * voice_mood.php — Voice transcript mood detection (Pure PHP, NO external API)
 *
 * Multi-layer NLP approach:
 *   1. Contraction expansion  ("don't" → "do not")
 *   2. Negation handling      ("not happy" → flips to sad)
 *   3. Intensifier weighting  ("extremely sad" → higher score)
 *   4. Phrase-level matching  (multi-word idioms score higher)
 *
 * POST: { "transcript": "I feel really anxious and stressed about everything" }
 * Returns JSON: { "mood": "anxious", "score": 7.2, "mode": "php_nlp" }
 */
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST')    { echo json_encode(["error" => "POST required"]); exit; }

$body       = json_decode(file_get_contents("php://input"), true);
$transcript = trim($body['transcript'] ?? '');

if ($transcript === '') {
    echo json_encode(["error" => "No transcript provided", "mood" => "neutral"]);
    exit;
}

// ─── Negation words ────────────────────────────────────────────────────────────
$NEGATIONS = [
    "not","no","never","do not","does not","did not","will not","would not",
    "cannot","is not","are not","was not","were not","have not","has not",
    "could not","should not","hardly","barely","nothing","far from",
    "anything but","lack","lacking","without","free from"
];

// ─── Intensifiers ─────────────────────────────────────────────────────────────
$INTENSIFIERS = [
    "very"=>1.8,"really"=>1.8,"extremely"=>2.2,"incredibly"=>2.2,
    "so"=>1.5,"super"=>1.6,"absolutely"=>2.0,"totally"=>1.7,
    "completely"=>2.0,"utterly"=>2.0,"deeply"=>1.9,"truly"=>1.7,
    "quite"=>1.4,"pretty"=>1.3,"rather"=>1.3,"somewhat"=>0.8,
    "a bit"=>0.7,"a little"=>0.7,"kind of"=>0.7,"sort of"=>0.7,
    "slightly"=>0.6,"overwhelmingly"=>2.4,"insanely"=>2.3,
    "terribly"=>1.9,"awfully"=>1.9,"beyond"=>2.0,"crazy"=>1.9,
];

// ─── Mood keyword dictionary ───────────────────────────────────────────────────
$MOOD_KEYWORDS = [

  "happy" => [
    "on top of the world"=>3,"cloud nine"=>3,"over the moon"=>3,
    "jumping for joy"=>3,"couldn't be happier"=>3,"best day"=>3,
    "feel amazing"=>3,"feel wonderful"=>3,"feel fantastic"=>3,
    "feel happy"=>3,"feeling happy"=>3,"feeling great"=>2.5,
    "feel good"=>2.5,"good mood"=>2.5,"feel alive"=>2,"feel positive"=>2,
    "happy"=>2.5,"happiness"=>2.5,"joy"=>2.5,"joyful"=>2.5,"joyous"=>2.5,
    "glad"=>1.5,"gleeful"=>2,"elated"=>2.5,"ecstatic"=>3,"delighted"=>2,
    "pleased"=>1.5,"content"=>1.5,"cheerful"=>2,"thrilled"=>2.5,
    "blissful"=>2.5,"euphoric"=>3,"overjoyed"=>3,"wonderful"=>1.5,
    "fantastic"=>1.5,"amazing"=>1.5,"awesome"=>1.5,"great"=>1.0,
    "excellent"=>1.5,"brilliant"=>1.5,"smile"=>1.5,"smiling"=>1.5,
    "laugh"=>1.5,"laughing"=>1.5,"fun"=>1.0,"enjoy"=>1.5,"enjoying"=>1.5,
    "grateful"=>2,"thankful"=>2,"blessed"=>2,"lucky"=>1.5,"giddy"=>2,
    "carefree"=>2,"pumped"=>1.0,
  ],

  "sad" => [
    "falling apart"=>3,"breaking down"=>3,"can not stop crying"=>3,
    "feel empty"=>3,"feel hollow"=>3,"feel worthless"=>3,
    "feel hopeless"=>3,"what is the point"=>3,"nothing matters"=>3,
    "feel broken"=>3,"heart is broken"=>3,"feel lost"=>2.5,
    "feel alone"=>2.5,"feel terrible"=>2.5,"feel down"=>2.5,
    "feel sad"=>3,"feeling sad"=>3,"feel depressed"=>3,
    "feeling depressed"=>3,"feel awful"=>2.5,"feel bad"=>2,
    "feeling bad"=>2,"feeling low"=>2.5,"feel low"=>2.5,
    "miss them"=>2,"miss you"=>2,
    "sad"=>2.5,"sadness"=>2.5,"unhappy"=>2,"upset"=>1.5,
    "depressed"=>3,"depression"=>3,"miserable"=>2.5,"misery"=>2.5,
    "heartbroken"=>3,"heartbreak"=>3,"grief"=>3,"grieving"=>3,
    "sorrow"=>2.5,"sorrowful"=>2.5,"mournful"=>2.5,"cry"=>2,
    "crying"=>2,"tears"=>2,"teary"=>2,"weeping"=>2.5,"sobbing"=>3,
    "devastated"=>3,"crushed"=>2.5,"helpless"=>2,"hopeless"=>2.5,
    "worthless"=>2.5,"empty"=>2,"hollow"=>2,"numb"=>2,"drained"=>1.5,
    "hurt"=>1.5,"pain"=>1.5,"ache"=>1.5,"aching"=>1.5,"broken"=>2,
    "shattered"=>2.5,"lost"=>1.5,"terrible"=>1.5,"awful"=>1.5,
    "gloomy"=>2,"dark"=>1,"bleak"=>2,"dreary"=>1.5,
    "despondent"=>2.5,"dejected"=>2,"downcast"=>2,"sullen"=>1.5,
    "blue"=>1.5,"down"=>1,"low"=>1,
  ],

  "angry" => [
    "pissed off"=>3,"fed up"=>2.5,"had enough"=>2.5,
    "so angry"=>3,"really angry"=>3,"losing my mind"=>2.5,
    "losing it"=>2,"losing my temper"=>2.5,"drives me crazy"=>2,
    "makes me mad"=>2.5,"makes me angry"=>2.5,"sick of"=>2,
    "sick and tired"=>2.5,"so frustrated"=>2.5,"really frustrated"=>2.5,
    "feel furious"=>3,"feeling furious"=>3,"boiling over"=>3,
    "see red"=>3,"losing patience"=>2,"out of patience"=>2,
    "angry"=>2.5,"anger"=>2.5,"furious"=>3,"fury"=>3,"mad"=>2,
    "rage"=>3,"raging"=>3,"enraged"=>3,"livid"=>3,"outraged"=>3,
    "irate"=>2.5,"frustrated"=>2,"frustration"=>2,
    "irritated"=>2,"irritation"=>2,"annoyed"=>1.5,"hate"=>2.5,
    "hating"=>2.5,"despise"=>2.5,"detest"=>2.5,"loathe"=>3,
    "resent"=>2,"resentful"=>2,"bitter"=>2,"hostile"=>2.5,
    "seething"=>3,"infuriated"=>3,"wrathful"=>3,"wrath"=>3,
    "explosive"=>2.5,"volatile"=>2,
  ],

  "energetic" => [
    "ready to go"=>2.5,"fired up"=>2.5,"pumped up"=>2.5,
    "full of energy"=>3,"so much energy"=>3,"can not sit still"=>2.5,
    "raring to go"=>2.5,"on fire"=>2.5,"killing it"=>2,
    "on top of my game"=>2.5,"let us go"=>2,"ready for anything"=>2.5,
    "wired up"=>2,"wide awake"=>2,"buzzing with"=>2.5,
    "feel unstoppable"=>3,"feel invincible"=>3,
    "energetic"=>3,"energy"=>2,"energized"=>2.5,"hyped"=>2.5,
    "pumped"=>2,"amped"=>2,"wired"=>2,"active"=>1.5,"alive"=>1.5,
    "vibrant"=>2,"motivated"=>2.5,"motivation"=>2,"driven"=>2,
    "dynamic"=>1.5,"electric"=>2,"charged"=>2,"buzzing"=>2,
    "thriving"=>2,"unstoppable"=>2.5,"invincible"=>2.5,
  ],

  "romantic" => [
    "in love"=>3,"falling in love"=>3,"head over heels"=>3,
    "butterflies in my stomach"=>3,"miss you so much"=>3,
    "thinking of you"=>2.5,"you mean everything"=>3,
    "heart flutters"=>2.5,"heart skips"=>2.5,"deeply in love"=>3,
    "madly in love"=>3,"date night"=>2,"love of my life"=>3,
    "feel romantic"=>3,"feeling romantic"=>3,
    "romantic"=>3,"romance"=>2.5,"love"=>1.5,"loving"=>1.5,
    "adore"=>2.5,"adoration"=>2.5,"affection"=>2.5,"affectionate"=>2,
    "tender"=>2,"intimate"=>2,"passion"=>2,"passionate"=>2.5,
    "longing"=>1.5,"smitten"=>2.5,"infatuated"=>2.5,"crush"=>2,
    "dreamy"=>2,"swooning"=>2.5,"devoted"=>2,"cherish"=>2.5,
    "darling"=>1.5,"beloved"=>2,
  ],

  "chill" => [
    "taking it easy"=>2.5,"going with the flow"=>2.5,
    "no worries"=>2,"no stress"=>2,"no pressure"=>2,
    "just vibing"=>2.5,"feel relaxed"=>2.5,"feeling relaxed"=>2.5,
    "feel calm"=>2.5,"feeling calm"=>2.5,"at peace"=>3,
    "feel peaceful"=>2.5,"feeling peaceful"=>2.5,"laid back"=>2.5,
    "chilling out"=>2.5,"kicking back"=>2,"winding down"=>2,
    "cozy night"=>2,"feel content"=>2,"feeling content"=>2,
    "chill"=>2.5,"chilled"=>2,"chilling"=>2,"relaxed"=>2.5,
    "relax"=>2,"relaxing"=>2,"calm"=>2.5,"calming"=>2,
    "peaceful"=>2.5,"peace"=>2,"serene"=>2.5,"serenity"=>2.5,
    "tranquil"=>2.5,"mellow"=>2,"easygoing"=>2,"comfortable"=>1.5,
    "cozy"=>2,"content"=>1.5,"satisfied"=>1.5,"zen"=>2.5,
    "breezy"=>1.5,"carefree"=>2,"unwinding"=>2,"grounded"=>1.5,
  ],

  "anxious" => [
    "racing thoughts"=>3,"can not breathe"=>3,"heart pounding"=>3,
    "heart racing"=>3,"can not relax"=>2.5,"can not calm down"=>3,
    "freaking out"=>3,"totally stressed"=>2.5,"really stressed"=>2.5,
    "so stressed"=>3,"feeling stressed"=>2.5,"feel stressed"=>2.5,
    "feel anxious"=>3,"feeling anxious"=>3,"panic attack"=>3,
    "constant worry"=>3,"can not stop worrying"=>3,"worrying about"=>2,
    "nervous about"=>2,"scared about"=>2,"on edge"=>2.5,
    "worked up"=>2,"tied in knots"=>2.5,"knot in my stomach"=>3,
    "shaking with"=>2.5,"trembling with"=>2.5,
    "anxious"=>3,"anxiety"=>3,"nervous"=>2.5,"worried"=>2,
    "worry"=>2,"worrying"=>2,"stressed"=>2.5,"stress"=>2,
    "tense"=>2,"tension"=>2,"jittery"=>2.5,"overwhelmed"=>2.5,
    "overwhelm"=>2,"panic"=>3,"panicking"=>3,"dread"=>2.5,
    "dreading"=>2.5,"apprehensive"=>2.5,"uneasy"=>2,"unsettled"=>2,
    "restless"=>2,"shaking"=>2,"trembling"=>2,"scared"=>2,
    "afraid"=>2,"frightened"=>2,"insecure"=>2,"uncertain"=>1.5,
  ],

  "nostalgic" => [
    "back in the day"=>3,"good old days"=>3,"those were the days"=>3,
    "miss the old"=>3,"brings back memories"=>3,"old memories"=>2.5,
    "childhood memories"=>3,"used to be"=>2,"remember when"=>2.5,
    "back when"=>2.5,"reminds me of"=>2.5,"takes me back"=>3,
    "miss being young"=>3,"feels like yesterday"=>2.5,
    "simpler times"=>2.5,"old times"=>2.5,"old days"=>2.5,
    "nostalgic"=>3,"nostalgia"=>3,"memories"=>2,"remember"=>1.5,
    "reminisce"=>2.5,"reminiscing"=>2.5,"throwback"=>2.5,
    "childhood"=>2.5,"younger"=>2,"youth"=>2,"past"=>1.5,
    "vintage"=>1.5,"retro"=>1.5,"classic"=>1.5,"bittersweet"=>2,
  ],

  "lonely" => [
    "all alone"=>3,"by myself"=>2.5,"on my own"=>2,
    "no one around"=>3,"no one cares"=>3,"nobody cares"=>3,
    "no one understands"=>3,"feel isolated"=>3,"feeling isolated"=>3,
    "feel lonely"=>3,"feeling lonely"=>3,"feel left out"=>3,
    "left out"=>2.5,"shut out"=>2.5,"pushed away"=>2.5,
    "feel invisible"=>3,"feel forgotten"=>3,"feel disconnected"=>3,
    "nobody to talk to"=>3,"no one to talk to"=>3,
    "lonely"=>3,"loneliness"=>3,"alone"=>2,"isolated"=>3,
    "isolation"=>3,"solitary"=>2.5,"abandoned"=>3,"forsaken"=>3,
    "rejected"=>2.5,"forgotten"=>2.5,"invisible"=>2.5,"ignored"=>2,
    "excluded"=>2.5,"alienated"=>3,"disconnected"=>2.5,"withdrawn"=>2,
    "friendless"=>3,"unloved"=>3,"unwanted"=>3,
  ],

  "confident" => [
    "got this"=>2.5,"i can do this"=>3,"i will do this"=>3,
    "ready for anything"=>2.5,"bring it on"=>2.5,"believe in myself"=>3,
    "trust myself"=>2.5,"owning it"=>2.5,"on top"=>2,"boss mode"=>2.5,
    "feeling confident"=>3,"feel confident"=>3,"feeling strong"=>2.5,
    "feel strong"=>2.5,"feeling powerful"=>2.5,"feel powerful"=>2.5,
    "know my worth"=>3,"nothing can stop me"=>3,
    "confident"=>3,"confidence"=>3,"assured"=>2.5,"bold"=>2.5,
    "brave"=>2.5,"bravery"=>2.5,"courage"=>2.5,"courageous"=>2.5,
    "fearless"=>3,"determined"=>2.5,"proud"=>2.5,"pride"=>2.5,
    "capable"=>2,"strong"=>2,"strength"=>2,"powerful"=>2,
    "invincible"=>3,"unstoppable"=>3,"decisive"=>2,"assertive"=>2,
    "poised"=>2,"self-assured"=>3,
  ],

  "tired" => [
    "dead tired"=>3,"completely exhausted"=>3,"totally drained"=>3,
    "so tired"=>2.5,"really tired"=>2.5,"feel tired"=>2.5,
    "feeling tired"=>2.5,"feel exhausted"=>3,"feeling exhausted"=>3,
    "feel drained"=>2.5,"feeling drained"=>2.5,
    "can not keep my eyes open"=>3,"can not stay awake"=>3,
    "falling asleep"=>2.5,"need sleep"=>2.5,"need rest"=>2.5,
    "running on empty"=>3,"nothing left"=>2.5,"worn out"=>2.5,
    "wiped out"=>3,"burned out"=>3,"burnt out"=>3,
    "sleep deprived"=>2.5,"barely slept"=>2.5,"no energy"=>3,
    "zero energy"=>3,"low energy"=>2.5,
    "tired"=>2.5,"exhausted"=>3,"exhaustion"=>3,"sleepy"=>2,
    "drowsy"=>2,"fatigued"=>2.5,"fatigue"=>2.5,"drained"=>2,
    "weary"=>2,"sluggish"=>2,"lethargic"=>2,"bored"=>1.5,
    "dragging"=>1.5,"groggy"=>2,"listless"=>2,"spent"=>2,"depleted"=>2,
  ],

  "hopeful" => [
    "things will get better"=>3,"better days ahead"=>3,"brighter future"=>3,
    "looking forward to"=>2.5,"new beginning"=>3,"fresh start"=>2.5,
    "light at the end"=>3,"things are looking up"=>3,
    "feel hopeful"=>3,"feeling hopeful"=>3,"feel optimistic"=>2.5,
    "feeling optimistic"=>2.5,"getting better"=>2,"things will improve"=>2.5,
    "hopeful"=>3,"hope"=>2,"hoping"=>2,"optimistic"=>3,
    "optimism"=>3,"positive"=>2,"inspired"=>2.5,"faith"=>2,
    "believe"=>2,"believing"=>2,"anticipate"=>2,"eager"=>2,
    "promising"=>2,"encouraging"=>2,"uplifting"=>2.5,
  ],

  "focus" => [
    "in the zone"=>3,"flow state"=>3,"deep focus"=>3,"heads down"=>2.5,
    "getting things done"=>2.5,"power through"=>2.5,
    "need to concentrate"=>3,"trying to concentrate"=>2.5,
    "need to focus"=>3,"trying to focus"=>2.5,"stay focused"=>2.5,
    "study mode"=>2.5,"work mode"=>2.5,"deadline today"=>3,
    "deadline tomorrow"=>3,"have to study"=>3,"have to work"=>2.5,
    "must finish"=>2.5,"lots to do"=>2,"so much to do"=>2.5,
    "exam tomorrow"=>3,"exam today"=>3,"test tomorrow"=>3,
    "focus"=>2.5,"focused"=>3,"focusing"=>2.5,"concentrate"=>2.5,
    "concentrating"=>2.5,"study"=>2,"studying"=>2.5,"work"=>1.5,
    "working"=>1.5,"productive"=>2,"deadline"=>2.5,"assignment"=>2,
    "project"=>1.5,"exam"=>2.5,"grind"=>2,"grinding"=>2,
    "hustle"=>2,"discipline"=>2,"diligent"=>2,"attentive"=>2,
  ],

  "melancholy" => [
    "quietly sad"=>3,"soft sadness"=>3,"gentle sadness"=>3,
    "bittersweet feeling"=>3,"something missing"=>2.5,
    "feel reflective"=>2.5,"feeling reflective"=>2.5,
    "feel pensive"=>2.5,"feeling pensive"=>2.5,"heavy heart"=>3,
    "heavy hearted"=>3,"weighed down"=>2.5,"blue but okay"=>3,
    "sad but okay"=>3,"wistful feeling"=>3,"longing for"=>2.5,
    "lost in thought"=>2.5,"quiet sadness"=>3,
    "melancholy"=>3,"melancholic"=>3,"wistful"=>3,"pensive"=>2.5,
    "reflective"=>2.5,"contemplative"=>2.5,"introspective"=>2.5,
    "mournful"=>2.5,"somber"=>2.5,"brooding"=>2.5,"yearning"=>2,
    "longing"=>2,"resigned"=>2,"wistfulness"=>3,
  ],

  "surprised" => [
    "can not believe it"=>3,"did not expect"=>2.5,"totally unexpected"=>3,
    "mind blown"=>3,"blew my mind"=>3,"out of nowhere"=>2.5,
    "never saw it coming"=>3,"what just happened"=>3,
    "completely shocked"=>3,"absolutely shocked"=>3,
    "feel surprised"=>3,"feeling surprised"=>3,
    "surprised"=>3,"surprise"=>2.5,"shocked"=>3,"shock"=>2.5,
    "stunned"=>3,"astonished"=>3,"astounded"=>3,"speechless"=>2.5,
    "disbelief"=>2.5,"flabbergasted"=>3,"dumbfounded"=>3,
    "bewildered"=>2,"wow"=>2,"whoa"=>2,"unbelievable"=>2.5,
  ],

  "fearful" => [
    "scared to death"=>3,"scared stiff"=>3,"frozen with fear"=>3,
    "paralyzed with fear"=>3,"terrified of"=>3,"really scared"=>2.5,
    "so scared"=>3,"absolutely terrified"=>3,"too scared to"=>3,
    "afraid to"=>2,"fear of"=>2,"can not face it"=>2.5,
    "fearful"=>3,"fear"=>2.5,"afraid"=>2.5,"terrified"=>3,
    "terror"=>3,"scared"=>2.5,"petrified"=>3,"horror"=>2.5,
    "horrified"=>3,"dreading"=>2.5,"fright"=>2.5,"frightened"=>2.5,
    "spooked"=>2,"nightmares"=>2.5,"phobia"=>2.5,
  ],

  "disgusted" => [
    "sick to my stomach"=>3,"makes me sick"=>3,"feels disgusting"=>3,
    "so disgusting"=>3,"so gross"=>3,"absolutely revolting"=>3,
    "can not stand it"=>2.5,"grossed out"=>2.5,
    "feel disgusted"=>3,"feeling disgusted"=>3,"nauseated by"=>2.5,
    "repulsed by"=>2.5,
    "disgusted"=>3,"disgust"=>3,"disgusting"=>3,"revolting"=>3,
    "repulsed"=>3,"repulsive"=>3,"gross"=>2.5,"nauseated"=>2.5,
    "nauseous"=>2.5,"sickened"=>2.5,"appalled"=>2.5,"yuck"=>2.5,
    "ugh"=>2,"vile"=>3,"putrid"=>3,
  ],

  "neutral" => [
    "nothing special"=>2,"just okay"=>2,"pretty average"=>2,
    "neither good nor bad"=>3,"same as usual"=>2,"nothing really"=>2,
    "do not feel much"=>2.5,"no strong feelings"=>3,
    "feel okay"=>1.5,"feeling okay"=>1.5,"feel fine"=>1.5,
    "feeling fine"=>1.5,"feel alright"=>1.5,
    "okay"=>1.5,"fine"=>1.5,"alright"=>1.5,"neutral"=>2,
    "meh"=>2,"average"=>1.5,"indifferent"=>2,
    "whatever"=>1.5,"normal"=>1.5,"ordinary"=>1.5,
  ],
];

// ═══════════════════════════════════════════════════════════════════════════════
// NLP ENGINE
// ═══════════════════════════════════════════════════════════════════════════════

function normalise(string $text): string {
    $text = mb_strtolower($text);
    // Normalise apostrophes
    $text = str_replace(["'","'","'","`","'"], "'", $text);
    // Expand contractions
    $contractions = [
        "can't"=>"cannot","won't"=>"will not","don't"=>"do not",
        "doesn't"=>"does not","didn't"=>"did not","isn't"=>"is not",
        "aren't"=>"are not","wasn't"=>"was not","weren't"=>"were not",
        "wouldn't"=>"would not","couldn't"=>"could not","shouldn't"=>"should not",
        "haven't"=>"have not","hasn't"=>"has not","hadn't"=>"had not",
        "i'm"=>"i am","they're"=>"they are","we're"=>"we are",
        "it's"=>"it is","that's"=>"that is","there's"=>"there is",
        "what's"=>"what is","he's"=>"he is","she's"=>"she is",
        "i've"=>"i have","i'll"=>"i will","i'd"=>"i would",
        "you're"=>"you are","you've"=>"you have","you'll"=>"you will",
    ];
    $text = strtr($text, $contractions);
    $text = preg_replace('/[^a-z\s]/', ' ', $text);
    return preg_replace('/\s+/', ' ', trim($text));
}

function detectMood(
    string $rawText,
    array $MOOD_KEYWORDS,
    array $NEGATIONS,
    array $INTENSIFIERS
): array {

    $text  = normalise($rawText);
    $words = explode(' ', $text);
    $n     = count($words);

    $scores  = [];
    $matched = [];
    foreach ($MOOD_KEYWORDS as $mood => $_) $scores[$mood] = 0.0;

    // Opposite mood map for negation cross-boost
    $opposite = [
        'happy'=>'sad',     'sad'=>'hopeful',    'angry'=>'chill',
        'energetic'=>'tired','confident'=>'anxious','chill'=>'anxious',
        'hopeful'=>'sad',   'tired'=>'energetic',
    ];

    foreach ($MOOD_KEYWORDS as $mood => $keywords) {
        // Sort longest phrases first
        uksort($keywords, fn($a,$b) => substr_count($b,' ') - substr_count($a,' '));

        foreach ($keywords as $rawKw => $baseScore) {
            $kw    = normalise($rawKw);
            $kwLen = substr_count($kw,' ') + 1;

            if (strpos($text, $kw) === false) continue;

            for ($i = 0; $i <= $n - $kwLen; $i++) {
                $slice = implode(' ', array_slice($words, $i, $kwLen));
                if ($slice !== $kw) continue;

                // Negation check (3-word window before keyword)
                $negated = false;
                for ($b = max(0, $i - 3); $b < $i && !$negated; $b++) {
                    if (in_array($words[$b], $NEGATIONS)) { $negated = true; break; }
                    if ($b+1 < $i && in_array($words[$b].' '.$words[$b+1], $NEGATIONS)) {
                        $negated = true; break;
                    }
                }

                // Intensifier check (2-word window before keyword)
                $mult = 1.0;
                if (!$negated) {
                    for ($b = max(0, $i - 2); $b < $i; $b++) {
                        if (isset($INTENSIFIERS[$words[$b]])) {
                            $mult = max($mult, $INTENSIFIERS[$words[$b]]);
                        }
                        if ($b+1 < $i) {
                            $pair = $words[$b].' '.$words[$b+1];
                            if (isset($INTENSIFIERS[$pair])) {
                                $mult = max($mult, $INTENSIFIERS[$pair]);
                            }
                        }
                    }
                }

                $effective = $baseScore * $mult;

                if ($negated) {
                    $scores[$mood] -= $effective * 0.4;
                    if (isset($opposite[$mood])) {
                        $scores[$opposite[$mood]] += $effective * 0.5;
                    }
                } else {
                    $scores[$mood] += $effective;
                    $matched[] = "$mood: '$rawKw' ×$mult";
                }
            }
        }
    }

    // Clamp to ≥ 0
    foreach ($scores as $k => $v) $scores[$k] = round(max(0.0, $v), 2);

    arsort($scores);
    $topMood  = array_key_first($scores);
    $topScore = $scores[$topMood];

    if ($topScore < 0.5) $topMood = 'neutral';

    return [
        'mood'    => $topMood,
        'score'   => $topScore,
        'top5'    => array_slice($scores, 0, 5, true),
        'matched' => array_slice($matched, 0, 8),
        'mode'    => 'php_nlp',
    ];
}

// ── Run ────────────────────────────────────────────────────────────────────────
$result = detectMood($transcript, $MOOD_KEYWORDS, $NEGATIONS, $INTENSIFIERS);

echo json_encode([
    "transcript" => $transcript,
    "mood"       => $result['mood'],
    "score"      => $result['score'],
    "top5"       => $result['top5'],
    "matched"    => $result['matched'],
    "mode"       => $result['mode'],
]);