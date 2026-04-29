<?php
header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);
$text = isset($data['text']) ? strtolower(trim($data['text'])) : '';

if ($text === '') {
    echo json_encode(["error" => "No input"]);
    exit();
}

$keywords = [
    "happy" => [
        "happy","joy","joyful","glad","great","wonderful","fantastic","excited","elated","amazing","awesome",
        "love","good","nice","smile","laugh","fun","best","blessed","grateful","cheerful","pleased","thrilled",
        "delighted","content","positive","upbeat","bright","enjoy","enjoyed","ecstatic","giddy","overjoyed",
        "on top of the world","cloud nine","jumping for joy","over the moon","so happy",
        "feeling good","feeling great","feeling amazing","feel great","feel amazing","good vibes","lit",
        "stoked","living my best","best day","love today"
    ],
    "sad" => [
        "sad","unhappy","depressed","depression","down","blue","miserable","heartbroken","grief","sorrow",
        "cry","crying","tears","lost","empty","hopeless","devastated","alone","miss","broken","pain","hurt",
        "upset","terrible","awful","worse","worst","gloomy","low","dark","drained","numb","helpless",
        "falling apart","no point","worthless","pitiful","sobbing","weeping",
        "heavy heart","heavy chest","feel like crying","want to cry","can't stop crying",
        "throat tight","chest heavy","feel hollow","feel empty inside","nothing matters","what's the point",
        "don't want to get up","can't get out of bed","no motivation","don't care anymore",
        "feel like giving up","feel defeated","feel broken","feel crushed","feel shattered",
        "missing someone","feel lost"
    ],
    "angry" => [
        "angry","mad","furious","rage","frustrated","annoyed","irritated","hate","resentful","bitter",
        "outraged","pissed","livid","fed up","infuriated","enraged","seething","boiling","explosive","hostile",
        "so angry","really mad","drives me crazy","makes me mad","can't stand","so frustrated","losing it",
        "about to explode","want to scream","ticked off","agitated","wound up","heated","steaming","raging","fuming"
    ],
    "energetic" => [
        "energetic","pumped","hyped","motivated","active","energized","fired up","ready","productive","alive",
        "wired","electric","amped","raring","unstoppable","let's go","on fire","killing it","buzzing","thriving",
        "revved up","full of energy","full of life","can't sit still","raring to go","bring it on",
        "ready to crush","feeling powerful","so much energy","charged up","supercharged"
    ],
    "romantic" => [
        "romantic","in love","crush","affection","tender","longing","dreamy","passionate","intimate",
        "miss you","adore","smitten","valentine","date night","falling for","butterflies","heart flutters",
        "love you","thinking of you","head over heels","falling in love","so in love",
        "can't stop thinking about","makes my heart race","makes my heart skip",
        "feel connected","feel close","wanting to be with","can't wait to see"
    ],
    "chill" => [
        "chill","relaxed","calm","peaceful","serene","mellow","laid back","easy","tranquil","quiet","cozy",
        "comfortable","zen","breezy","at ease","unwinding","chilling","taking it easy","no stress","vibing",
        "going with the flow","just chilling","doing nothing","lazy day","rest day","taking a break",
        "slow day","lounging","kicking back","cooling down","winding down","feel at peace",
        "no worries","stress free","carefree","easygoing","chillin","relaxing","decompressing"
    ],
    "anxious" => [
        "anxious","nervous","worried","worry","stress","stressed","tense","overwhelmed","panic","panicking",
        "fear","scared","uneasy","restless","apprehensive","jittery","on edge","freaking out",
        "racing thoughts","can't breathe","heart pounding","shaking","trembling",
        "sweating","sweaty","stomach in knots","knot in my stomach","butterflies in stomach",
        "feel nauseous","queasy","dizzy","lightheaded","heart racing",
        "chest tight","chest tightness","shortness of breath","can't focus","mind racing","can't sleep",
        "can't relax","can't calm down","feel out of control","overthinking","overanalyzing",
        "spiraling","spiralling","doom","dread","dreading",
        "so nervous","really anxious","really stressed","super stressed","too much","too overwhelmed"
    ],
    "nostalgic" => [
        "nostalgic","memories","old times","childhood","throwback","remember","past","gone","used to",
        "reminisce","back then","miss the old","good old days","those days","back in the day","miss being",
        "young again","old songs","classic","remember when","brings back memories","takes me back",
        "those were the days","simpler times","years ago","when i was young","when i was a kid",
        "wish i could go back","feel like the old days"
    ],
    "lonely" => [
        "lonely","isolated","abandoned","forgotten","no one","by myself","left out","invisible",
        "disconnected","alienated","no friends","all alone","nobody cares","no one understands",
        "feel like a ghost","shut out","on my own","sitting alone","eating alone",
        "going home alone","no one to talk to","no one to call","feel distant",
        "nobody checks on me","nobody texts me","left on read","feel unwanted","feel unheard",
        "feel unseen","feel ignored","feel excluded","feel rejected","nobody around"
    ],
    "confident" => [
        "confident","strong","powerful","brave","bold","fearless","unstoppable","determined","proud",
        "capable","invincible","certain","got this","i can do this","ready for anything","believe in myself",
        "on top","killing it","boss","owning it","crushing it","nailing it","on my game","in my element",
        "feel unstoppable","feel powerful","feel capable","feel ready","nothing can stop me","i got this",
        "feeling myself","at my best","peak performance"
    ],
    "tired" => [
        "tired","exhausted","sleepy","drained","fatigued","worn out","weary","bored","dull","sluggish",
        "lethargic","burnout","burn out","no energy","can't wake up","dragging","wiped out","dead tired",
        "sleep deprived","can't keep eyes open","hectic",
        "my day was exhausted","my day was exhausting","day was exhausting","so exhausted","really tired",
        "extremely tired","so drained","completely drained","totally wiped","running on empty",
        "running low","barely awake","need sleep","need rest","need a nap","need to sleep",
        "could sleep forever","could sleep all day","falling asleep","eyes heavy","heavy eyes",
        "body aches","body is tired","physically tired","mentally tired","mentally exhausted",
        "brain dead","brain fog","foggy","can barely move","can't move","just want to sleep",
        "just want to rest","low energy","low battery","hit a wall",
        "long day","rough day","tough day","hard day","exhausting day","tiring day","what a day",
        "day wiped me out","work wiped me out"
    ],
    "hopeful" => [
        "hope","hopeful","optimistic","looking forward","better days","wishing","anticipate",
        "excited about","believe","things will get better","brighter tomorrow","new beginning","faith",
        "light at the end","turning around","things are looking up","feeling hopeful","feeling positive",
        "feeling optimistic","can't wait","excited for","looking ahead","bright future","good things coming",
        "i'll be okay","it'll be okay","going to be okay","everything will be fine",
        "new start","fresh start","new chapter","turning point","breakthrough"
    ],
    "focus" => [
        "focus","focused","concentrate","concentrating","studying","study","work","working","productive",
        "grind","deep work","deadline","flow state","in the zone","need to concentrate","trying to work",
        "power through","get things done","heads down","need to focus","trying to focus","have to study",
        "have to work","working hard","studying hard","grinding","locked in","tunnel vision","dialed in",
        "need to finish","trying to finish","crunch time","last minute","all nighter","all-nighter",
        "pulling an all nighter","exam","test tomorrow","due tomorrow","assignment due"
    ],
    "melancholy" => [
        "melancholy","bittersweet","wistful","pensive","reflective","longing","yearning","mournful",
        "somber","heavy hearted","contemplative","quiet sadness","soft sadness","feeling low but okay",
        "blue but okay","not sad but not happy","somewhere in between","mixed feelings",
        "don't know how i feel","hard to explain","feeling off","feel off","something's off",
        "hard to describe","can't explain it","bittersweet feeling","poetic sadness","thoughtful",
        "staring into space","zoning out","introspective","in my head","deep in thought"
    ],
    "disgusted" => [
        "disgusted","disgusting","revolting","sick to my stomach","nauseated","repulsed","gross",
        "ugh","yuck","vomit","vomiting","feel like vomiting","want to vomit","want to throw up",
        "throwing up","threw up","feel like throwing up","about to throw up","going to throw up",
        "sick","feeling sick","so sick","grossed out","turns my stomach","makes me sick","makes me gag",
        "gag","gagging","stomach turning","stomach churning","revolted","feel revolted",
        "can't stomach it","feel ill","feeling ill","nausea","queasy feeling"
    ],
    "fearful" => [
        "afraid","terrified","scared stiff","dread","frightened","petrified","horror","nightmare",
        "can't stop shaking","frozen with fear","so scared","really scared","extremely scared",
        "fear of","scared of","afraid of","terrified of","nightmare about","haunted","paranoid",
        "feel threatened","feel unsafe","feel in danger","something bad will happen",
        "ominous","foreboding","impending doom","dread feeling","feeling of dread","so afraid"
    ],
    "surprised" => [
        "surprised","shocked","wow","unbelievable","can't believe","mind blown","astonished",
        "blown away","didn't expect","what just happened","no way","oh my god","oh wow","jaw dropped",
        "stunned","taken aback","out of nowhere","did not see that coming","didn't see that coming",
        "so unexpected","hard to believe","in disbelief","disbelief","floored","speechless"
    ],
    "neutral" => [
        "okay","fine","alright","meh","so-so","normal","nothing special","average","indifferent",
        "just okay","not much","whatever","nothing really","neither","same old","same as always",
        "nothing new","pretty much the same","all good","doing okay","doing fine","doing alright",
        "just existing","just here","just chillin"
    ],
];

$scores = array_fill_keys(array_keys($keywords), 0);

foreach ($keywords as $mood => $words) {
    foreach ($words as $word) {
        if (strpos($word, ' ') !== false) {
            if (strpos($text, $word) !== false) $scores[$mood] += 2;
        } else {
            if (preg_match('/\b' . preg_quote($word, '/') . '\b/', $text)) $scores[$mood]++;
        }
    }
}

arsort($scores);
$best = array_key_first($scores);

// Stemming-style regex fallback if nothing matched
if ($scores[$best] === 0) {
    $fallbackPatterns = [
        '/\b(exhaust|tir(ed|ing)|wip(ed|ing)|fatigue|drain)\w*/i' => 'tired',
        '/\b(vomit|nauseat|throw.?up|puke|gag)\w*/i'             => 'disgusted',
        '/\b(stress|anxiet|worr|panic|overwhelm)\w*/i'           => 'anxious',
        '/\b(depress|unhapp|grief|mourn|sorrow)\w*/i'            => 'sad',
        '/\b(happi|joyful|excit|elat|cheerful)\w*/i'             => 'happy',
        '/\b(angr|furi|irrit|rage|livid)\w*/i'                   => 'angry',
        '/\b(calm|peace|relax|chill|serenity)\w*/i'              => 'chill',
        '/\b(confident|fearless|brave|bold|proud)\w*/i'          => 'confident',
        '/\b(hope|optimis|believ|faith)\w*/i'                    => 'hopeful',
        '/\b(nostalgic|reminiscen|memori)\w*/i'                  => 'nostalgic',
        '/\b(lonely|isolat|abandon)\w*/i'                        => 'lonely',
    ];
    foreach ($fallbackPatterns as $pattern => $mood) {
        if (preg_match($pattern, $text)) {
            $best = $mood;
            break;
        }
    }
    // If still no match, neutral
    if ($scores[array_key_first($scores)] === 0 && !isset($mood)) $best = "neutral";
}

echo json_encode(["mood" => $best]);