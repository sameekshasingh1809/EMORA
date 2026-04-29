"""
mood_detector.py  —  Text-based mood detection for MoodTune
Usage: python mood_detector.py "I feel really happy today"

Returns a single mood word to stdout (happy, sad, angry, energetic, etc.)
Tries transformers (zero-shot) first; falls back to keyword matching.
"""

import sys
import re

# ── Keyword fallback (always works, no extra libraries needed) ────────────────
MOOD_KEYWORDS = {
    "happy":     ["happy", "joyful", "glad", "delighted", "cheerful", "content", "great", "wonderful", "fantastic", "excited", "elated", "ecstatic", "pleased", "thrilled"],
    "sad":       ["sad", "unhappy", "depressed", "down", "blue", "miserable", "heartbroken", "grief", "sorrow", "melancholy", "tearful", "cry", "crying", "lost", "empty"],
    "angry":     ["angry", "mad", "furious", "rage", "frustrated", "annoyed", "irritated", "upset", "hate", "resentful", "bitter", "outraged"],
    "energetic": ["energetic", "pumped", "hyped", "motivated", "active", "energized", "fired up", "ready", "productive", "focused", "alive"],
    "romantic":  ["romantic", "love", "in love", "crush", "affection", "tender", "longing", "dreamy", "passionate", "intimate", "miss you"],
    "chill":     ["chill", "relaxed", "calm", "peaceful", "serene", "mellow", "laid back", "easy", "tranquil", "quiet", "cozy", "comfortable"],
    "anxious":   ["anxious", "nervous", "worried", "stress", "stressed", "tense", "overwhelmed", "panic", "fear", "scared", "uneasy", "restless"],
    "nostalgic": ["nostalgic", "nostalgic", "miss", "memories", "old times", "childhood", "throwback", "remember", "past", "gone", "used to"],
    "lonely":    ["lonely", "alone", "isolated", "abandoned", "forgotten", "no one", "by myself", "left out"],
    "confident": ["confident", "strong", "powerful", "brave", "bold", "fearless", "unstoppable", "determined", "proud"],
    "tired":     ["tired", "exhausted", "sleepy", "drained", "fatigued", "worn out", "weary", "bored", "dull"],
    "hopeful":   ["hope", "hopeful", "optimistic", "looking forward", "excited about", "better days", "positive"],
}

def keyword_mood(text: str) -> str:
    text_lower = text.lower()
    scores = {mood: 0 for mood in MOOD_KEYWORDS}
    for mood, words in MOOD_KEYWORDS.items():
        for word in words:
            if re.search(r'\b' + re.escape(word) + r'\b', text_lower):
                scores[mood] += 1
    best = max(scores, key=scores.get)
    return best if scores[best] > 0 else "happy"

def transformers_mood(text: str) -> str:
    """Use zero-shot classification if transformers library is installed."""
    from transformers import pipeline
    candidate_labels = list(MOOD_KEYWORDS.keys())
    classifier = pipeline("zero-shot-classification",
                          model="typeform/distilbert-base-uncased-mnli")
    result = classifier(text, candidate_labels)
    return result["labels"][0]

def detect_mood(text: str) -> str:
    try:
        return transformers_mood(text)
    except Exception:
        # transformers not installed or model not available — use keyword fallback
        return keyword_mood(text)

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print("happy")
        sys.exit(0)

    user_text = " ".join(sys.argv[1:])
    mood = detect_mood(user_text)
    print(mood)