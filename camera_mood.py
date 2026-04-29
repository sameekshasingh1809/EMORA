"""
camera_mood.py — Camera-based mood detection for MoodTune / EMORA
Uses DeepFace over a 5-second capture window with majority-vote strategy,
exactly as described in the Phase 2 project report.

Requirements: pip install deepface opencv-python tf-keras
"""

import cv2
import sys
import time
from collections import Counter

# Mapping from DeepFace emotion labels → MoodTune mood labels
EMOTION_TO_MOOD = {
    "happy":    "happy",
    "sad":      "sad",
    "angry":    "angry",
    "fear":     "anxious",
    "surprise": "surprised",
    "disgust":  "disgusted",
    "neutral":  "chill",
}

VALID_MOODS = [
    "happy", "sad", "angry", "energetic", "romantic", "chill", "anxious",
    "nostalgic", "lonely", "confident", "tired", "hopeful", "surprised",
    "neutral", "fearful", "disgusted", "melancholy", "focus"
]


def detect_with_deepface(frame, enforce_detection=False):
    """Run DeepFace emotion analysis on a single frame. Returns dominant emotion string or None."""
    try:
        from deepface import DeepFace
        results = DeepFace.analyze(
            frame,
            actions=["emotion"],
            enforce_detection=enforce_detection,
            silent=True,
        )
        # results can be a list (multiple faces) or a single dict
        if isinstance(results, list):
            results = results[0]
        dominant = results.get("dominant_emotion", "").lower()
        return dominant if dominant else None
    except Exception:
        return None


def get_mood_deepface(capture_seconds=5):
    """
    Capture frames for `capture_seconds`, run DeepFace on each,
    and return the majority-voted mood label.
    """
    cap = cv2.VideoCapture(0, cv2.CAP_DSHOW)   # CAP_DSHOW for Windows; harmless on Linux/Mac
    if not cap.isOpened():
        cap = cv2.VideoCapture(0)               # fallback without backend flag
    if not cap.isOpened():
        return "neutral"

    # Let the camera warm up
    for _ in range(5):
        cap.read()

    votes = []
    start = time.time()

    while time.time() - start < capture_seconds:
        ret, frame = cap.read()
        if not ret:
            break

        emotion = detect_with_deepface(frame)
        if emotion:
            mood = EMOTION_TO_MOOD.get(emotion, "neutral")
            votes.append(mood)

        # ~3 fps is plenty; avoids hammering CPU
        time.sleep(0.33)

    cap.release()

    if not votes:
        return "neutral"

    # Majority vote
    winner, _ = Counter(votes).most_common(1)[0]
    return winner if winner in VALID_MOODS else "neutral"


def get_mood_opencv_fallback():
    """
    Pure-OpenCV fallback (no DeepFace/TensorFlow).
    Uses Haar Cascade + brightness heuristic over 5 seconds.
    Less accurate but always works.
    """
    face_cascade = cv2.CascadeClassifier(
        cv2.data.haarcascades + "haarcascade_frontalface_default.xml"
    )

    cap = cv2.VideoCapture(0, cv2.CAP_DSHOW)
    if not cap.isOpened():
        cap = cv2.VideoCapture(0)
    if not cap.isOpened():
        return "neutral"

    # Warm-up
    for _ in range(5):
        cap.read()

    brightness_samples = []
    start = time.time()

    while time.time() - start < 5:
        ret, frame = cap.read()
        if not ret:
            break
        gray = cv2.cvtColor(frame, cv2.COLOR_BGR2GRAY)
        faces = face_cascade.detectMultiScale(gray, 1.3, 5)
        if len(faces) > 0:
            x, y, w, h = faces[0]
            face_roi = gray[y:y + h, x:x + w]
            brightness_samples.append(face_roi.mean())
        time.sleep(0.33)

    cap.release()

    if not brightness_samples:
        return "neutral"

    avg = sum(brightness_samples) / len(brightness_samples)
    if avg > 150:
        return "happy"
    if avg < 85:
        return "sad"
    return "chill"


def main():
    # Try DeepFace first; fall back to OpenCV-only if unavailable
    try:
        import deepface  # noqa: F401  — just checking availability
        mood = get_mood_deepface(capture_seconds=5)
    except ImportError:
        mood = get_mood_opencv_fallback()

    print(mood)


if __name__ == "__main__":
    main()