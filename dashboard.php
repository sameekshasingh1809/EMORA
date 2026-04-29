<?php
session_start();
if (!isset($_SESSION['user'])) { header("Location: index.php"); exit(); }
$username = $_SESSION['user'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>EMORA — Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,100..900;1,9..144,100..900&family=Cabinet+Grotesk:wght@300;400;500;700;800&family=Fira+Code:wght@300;400&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
<style>
/* ── DESIGN TOKENS ── */
:root {
  --void:    #03010a;
  --surface: rgba(255,255,255,0.035);
  --rim:     rgba(255,255,255,0.08);
  --rim2:    rgba(255,255,255,0.04);
  --neo1:    #7b2fff;
  --neo2:    #ff2fa0;
  --neo3:    #00e5ff;
  --neo4:    #39ff7a;
  --text:    #f0ecff;
  --muted:   rgba(240,236,255,0.42);
  --ink:     rgba(240,236,255,0.7);
  --ease:    cubic-bezier(.22,1,.36,1);
}

*,*::before,*::after { margin:0; padding:0; box-sizing:border-box; }
html { scroll-behavior: smooth; }

body {
  min-height: 100vh;
  background: var(--void);
  font-family: 'Cabinet Grotesk', sans-serif;
  color: var(--text);
  overflow-x: hidden;
}

/* ── Grain ── */
body::after {
  content: '';
  position: fixed; inset: 0;
  background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 512 512' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.8' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.04'/%3E%3C/svg%3E");
  pointer-events: none; z-index: 0; mix-blend-mode: overlay;
}

/* ── Ambient orbs ── */
.orb {
  position: fixed; border-radius: 50%;
  filter: blur(130px); pointer-events: none; z-index: 0;
  animation: orbDrift 20s ease-in-out infinite alternate;
}
.orb1 { width: 700px; height: 700px; background: #7b2fff; opacity: .12; top: -200px; right: -200px; }
.orb2 { width: 500px; height: 500px; background: #ff2fa0; opacity: .09; bottom: -100px; left: -150px; animation-delay: -8s; }
.orb3 { width: 350px; height: 350px; background: #00e5ff; opacity: .07; top: 45%; left: 40%; animation-delay: -14s; }
@keyframes orbDrift { 0%{transform:translate(0,0) scale(1)} 100%{transform:translate(40px,30px) scale(1.06)} }

/* ── App container ── */
.app {
  position: relative; z-index: 1;
  max-width: 1140px; margin: 0 auto;
  padding: 0 32px 100px;
}

/* ══ HEADER ══ */
header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 28px 0 26px;
  border-bottom: 1px solid var(--rim2);
  margin-bottom: 56px;
  animation: slideDown .6s var(--ease) both;
}
@keyframes slideDown { from{opacity:0;transform:translateY(-18px)} to{opacity:1;transform:translateY(0)} }

.logo { display: flex; align-items: center; gap: 12px; text-decoration: none; color: inherit; }
.logo-mark {
  width: 38px; height: 38px;
  border: 1.5px solid var(--neo1); border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  position: relative; animation: logoPulse 5s ease-in-out infinite;
}
.logo-mark::before { content:''; position:absolute; inset:3px; border-radius:7px; background:linear-gradient(135deg,var(--neo1),var(--neo2)); opacity:.25; }
.logo-mark svg { width:18px; height:18px; fill:var(--neo1); position:relative; z-index:1; }
@keyframes logoPulse {
  0%,100%{box-shadow:0 0 0 0 rgba(123,47,255,0)} 
  50%{box-shadow:0 0 18px 3px rgba(123,47,255,0.4)}
}
.logo-text {
  font-family: 'Fraunces', serif; font-size: 20px; font-weight: 800;
  background: linear-gradient(90deg,var(--text),rgba(240,236,255,.65));
  -webkit-background-clip: text; -webkit-text-fill-color: transparent;
}

.header-right { display: flex; align-items: center; gap: 12px; }

.user-pill {
  display: flex; align-items: center; gap: 9px;
  padding: 6px 14px 6px 7px;
  background: var(--surface); border: 1px solid var(--rim);
  border-radius: 50px; backdrop-filter: blur(12px);
}
.user-avatar {
  width: 28px; height: 28px; border-radius: 50%;
  background: linear-gradient(135deg,var(--neo1),var(--neo2));
  display: flex; align-items: center; justify-content: center;
  font-size: 11px; font-weight: 800; letter-spacing: .05em;
}
.user-name { font-size: 13px; font-weight: 600; color: var(--ink); }

.btn-logout {
  padding: 8px 18px;
  border: 1px solid var(--rim); border-radius: 9px;
  background: transparent; color: var(--muted);
  font-family: 'Cabinet Grotesk', sans-serif; font-size: 13px; font-weight: 500;
  cursor: pointer; text-decoration: none; transition: all .2s;
}
.btn-logout:hover { border-color: var(--neo2); color: var(--neo2); background: rgba(255,47,160,.06); }

/* ══ HERO ══ */
.hero { margin-bottom: 52px; animation: fadeUp .7s var(--ease) .1s both; }
@keyframes fadeUp { from{opacity:0;transform:translateY(24px)} to{opacity:1;transform:translateY(0)} }

.hero-eyebrow {
  font-family: 'Fira Code', monospace; font-size: 11px;
  color: var(--neo3); letter-spacing: .2em; text-transform: uppercase;
  margin-bottom: 18px; display: flex; align-items: center; gap: 10px;
}
.hero-eyebrow::before { content:''; width:28px; height:1px; background:var(--neo3); }

.hero-title {
  font-family: 'Fraunces', serif;
  font-size: clamp(38px,5vw,62px);
  font-weight: 300; line-height: 1.08; letter-spacing: -.03em;
  margin-bottom: 14px;
}
.hero-title em { font-style: italic; background:linear-gradient(90deg,var(--neo1),var(--neo2)); -webkit-background-clip:text; -webkit-text-fill-color:transparent; }

.hero-sub { font-size: 15px; color: var(--muted); line-height: 1.65; }

/* ══ SECTION TAG ══ */
.section-tag {
  display: inline-flex; align-items: center; gap: 8px;
  font-family: 'Fira Code', monospace; font-size: 10px;
  letter-spacing: .18em; text-transform: uppercase; color: var(--muted);
  margin-bottom: 18px; font-weight: 400;
}
.section-tag::before { content:''; width:22px; height:1px; background:var(--muted); }

/* ══ DETECT CARD ══ */
.detect-card {
  background: var(--surface); border: 1px solid var(--rim);
  border-radius: 24px; padding: 36px;
  backdrop-filter: blur(20px);
  box-shadow: 0 8px 60px rgba(0,0,0,.5), inset 0 1px 0 rgba(255,255,255,.06);
  animation: fadeUp .7s var(--ease) .2s both;
}
.detect-card-title {
  font-family: 'Fraunces', serif; font-size: 24px; font-weight: 400;
  margin-bottom: 5px; letter-spacing: -.02em;
}
.detect-card-sub { font-size: 13px; color: var(--muted); margin-bottom: 28px; }

/* ── Method Tabs ── */
.method-tabs {
  display: flex; gap: 4px;
  background: rgba(255,255,255,.04); border: 1px solid var(--rim);
  border-radius: 14px; padding: 4px; margin-bottom: 28px;
}
.mtab {
  flex: 1; padding: 10px 8px;
  background: transparent; border: none; border-radius: 11px;
  font-family: 'Cabinet Grotesk', sans-serif; font-size: 13px; font-weight: 700;
  color: var(--muted); cursor: pointer; transition: all .22s var(--ease);
  display: flex; align-items: center; justify-content: center; gap: 6px;
  white-space: nowrap;
}
.mtab.active { background: rgba(123,47,255,.25); color: var(--text); box-shadow: 0 0 0 1px rgba(123,47,255,.4); }
.mtab:hover:not(.active) { color: var(--text); background: rgba(255,255,255,.06); }

/* ── Tab panels ── */
.tab-panel { display: none; }
.tab-panel.active { display: block; animation: fadeIn .3s ease; }
@keyframes fadeIn { from{opacity:0} to{opacity:1} }

/* ── Text Panel ── */
.text-row { display: flex; gap: 10px; }
.mood-input {
  flex: 1; padding: 14px 20px;
  background: rgba(255,255,255,.05); border: 1px solid var(--rim);
  border-radius: 12px; color: var(--text);
  font-family: 'Cabinet Grotesk', sans-serif; font-size: 15px;
  outline: none; transition: all .25s;
}
.mood-input:focus { border-color: var(--neo1); background: rgba(123,47,255,.06); box-shadow: 0 0 0 3px rgba(123,47,255,.12); }
.mood-input::placeholder { color: var(--muted); }

.btn-primary {
  padding: 14px 26px; background: linear-gradient(135deg,var(--neo1),var(--neo2));
  border: none; border-radius: 12px; color: #fff;
  font-family: 'Cabinet Grotesk', sans-serif; font-size: 13px; font-weight: 700;
  cursor: pointer; letter-spacing: .03em; transition: all .25s var(--ease);
  display: flex; align-items: center; gap: 8px; white-space: nowrap; position: relative; overflow: hidden;
}
.btn-primary:hover { transform: translateY(-2px); box-shadow: 0 10px 32px rgba(123,47,255,.5); }
.btn-primary:disabled { opacity: .5; cursor: not-allowed; transform: none; }

.input-hint { margin-top: 10px; font-size: 12px; color: var(--muted); font-family: 'Fira Code', monospace; }

/* ── Spinner ── */
.spinner {
  display: none; width: 16px; height: 16px;
  border: 2px solid rgba(255,255,255,.3); border-top-color: #fff;
  border-radius: 50%; animation: spin .7s linear infinite;
}
@keyframes spin { to{transform:rotate(360deg)} }

/* ── Voice Panel ── */
.voice-panel { display:flex; flex-direction:column; align-items:center; gap:0; padding:12px 0; }
.voice-desc { font-size:14px; color:var(--muted); text-align:center; max-width:420px; line-height:1.65; margin-bottom:28px; }

.voice-orb-container { position:relative; display:flex; align-items:center; justify-content:center; width:120px; height:120px; margin-bottom:20px; }
.ripple-ring {
  position:absolute; border-radius:50%; border:1.5px solid var(--neo1);
  animation:ripple 2s ease-out infinite;
}
.ripple-ring.hidden { display:none; }
.ripple-ring:nth-child(1){width:120px;height:120px;animation-delay:0s}
.ripple-ring:nth-child(2){width:150px;height:150px;animation-delay:.5s}
.ripple-ring:nth-child(3){width:180px;height:180px;animation-delay:1s}
@keyframes ripple { 0%{opacity:.6;transform:scale(.8)} 100%{opacity:0;transform:scale(1.3)} }

.voice-orb {
  width:80px; height:80px; border-radius:50%;
  background:linear-gradient(135deg,var(--neo1),var(--neo2));
  border:none; cursor:pointer; font-size:30px;
  display:flex; align-items:center; justify-content:center;
  transition:all .3s var(--ease); position:relative; z-index:1;
  box-shadow:0 0 0 0 rgba(123,47,255,.4);
}
.voice-orb:hover { transform:scale(1.08); }
.voice-orb.active { animation:orbGlow 1s ease-in-out infinite alternate; }
@keyframes orbGlow { from{box-shadow:0 0 20px rgba(123,47,255,.5)} to{box-shadow:0 0 40px rgba(255,47,160,.7)} }

.waveform { display:flex; align-items:center; gap:3px; height:36px; margin-bottom:14px; }
.wbar { width:4px; height:8px; border-radius:2px; background:linear-gradient(180deg,var(--neo1),var(--neo2)); opacity:.3; transition:all .15s; }
.waveform.active .wbar { animation:wave .6s ease-in-out infinite alternate; opacity:1; }
.wbar:nth-child(1){animation-delay:.0s}.wbar:nth-child(2){animation-delay:.08s}.wbar:nth-child(3){animation-delay:.16s}
.wbar:nth-child(4){animation-delay:.24s}.wbar:nth-child(5){animation-delay:.32s}.wbar:nth-child(6){animation-delay:.40s}
.wbar:nth-child(7){animation-delay:.48s}.wbar:nth-child(8){animation-delay:.56s}.wbar:nth-child(9){animation-delay:.64s}
@keyframes wave { from{height:4px} to{height:32px} }

.voice-status { font-size:13px; color:var(--muted); text-align:center; min-height:22px; margin-bottom:12px; }
.voice-status.listening { color:var(--neo2); }
.voice-status.success { color:var(--neo4); }
.voice-status.error { color:#ff6b9d; }

.voice-transcript-box {
  display:none; width:100%; max-width:500px;
  background:rgba(255,255,255,.04); border:1px solid var(--rim);
  border-radius:12px; padding:14px 18px; font-size:14px;
  color:var(--ink); line-height:1.6; min-height:50px;
  margin-bottom:14px; text-align:left;
}
.voice-transcript-box.visible { display:block; }
.voice-transcript-box .interim { opacity:.5; font-style:italic; }

.btn-analyse-voice {
  display:none; padding:11px 24px;
  background:linear-gradient(135deg,var(--neo1),var(--neo2));
  border:none; border-radius:12px; color:#fff;
  font-family:'Cabinet Grotesk',sans-serif; font-size:13px; font-weight:700;
  cursor:pointer; transition:all .25s;
}
.btn-analyse-voice.visible { display:inline-flex; }
.btn-analyse-voice:hover { transform:translateY(-1px); box-shadow:0 8px 26px rgba(123,47,255,.4); }

.voice-privacy { font-family:'Fira Code',monospace; font-size:10px; color:var(--muted); text-align:center; margin-top:14px; letter-spacing:.04em; }

/* ── Camera Panel ── */
.camera-panel-inner { display:flex; flex-direction:column; align-items:center; gap:18px; padding:24px 0; }
.camera-desc { font-size:14px; color:var(--muted); text-align:center; max-width:420px; line-height:1.65; }
.btn-camera {
  padding:13px 32px;
  border:1.5px solid var(--neo3); border-radius:12px;
  background:rgba(0,229,255,.08); color:var(--neo3);
  font-family:'Cabinet Grotesk',sans-serif; font-size:14px; font-weight:700;
  cursor:pointer; transition:all .25s; letter-spacing:.03em;
}
.btn-camera:hover { background:rgba(0,229,255,.15); box-shadow:0 0 24px rgba(0,229,255,.2); transform:translateY(-1px); }

/* ── Quick Pick Chips ── */
.chips-grid { display:flex; flex-wrap:wrap; gap:10px; padding:8px 0; }
.chip {
  padding:9px 20px; border-radius:50px;
  border:1px solid var(--rim); background:var(--surface);
  font-size:13px; font-weight:600; color:var(--ink);
  cursor:pointer; transition:all .22s var(--ease);
  backdrop-filter:blur(10px);
}
.chip:hover { border-color:var(--neo1); color:var(--text); background:rgba(123,47,255,.12); transform:translateY(-2px); box-shadow:0 6px 20px rgba(123,47,255,.2); }

/* ── Mood Result Badge ── */
#moodResult {
  display:none; margin-top:28px;
  align-items:center; gap:18px;
  padding:20px 24px;
  background:linear-gradient(135deg,rgba(123,47,255,.12),rgba(255,47,160,.08));
  border:1px solid rgba(123,47,255,.3); border-radius:16px;
  animation:fadeUp .4s var(--ease);
}
.mood-emoji { font-size:40px; filter:drop-shadow(0 0 14px rgba(123,47,255,.6)); }
.mood-info { flex:1; }
.mood-label { font-family:'Fira Code',monospace; font-size:10px; letter-spacing:.16em; text-transform:uppercase; color:var(--muted); margin-bottom:3px; }
.mood-name { font-family:'Fraunces',serif; font-size:26px; font-weight:600; letter-spacing:-.02em; }
.mood-via { font-size:12px; color:var(--muted); margin-top:2px; }

.btn-get-songs {
  padding:13px 26px;
  background:linear-gradient(135deg,var(--neo1),var(--neo2));
  border:none; border-radius:12px; color:#fff;
  font-family:'Cabinet Grotesk',sans-serif; font-size:13px; font-weight:700;
  cursor:pointer; transition:all .25s var(--ease); white-space:nowrap;
}
.btn-get-songs:hover { transform:translateY(-2px); box-shadow:0 10px 30px rgba(123,47,255,.5); }

/* ── History ── */
.history-wrap { display:none; margin-top:20px; }
.history-label { font-family:'Fira Code',monospace; font-size:10px; letter-spacing:.14em; text-transform:uppercase; color:var(--muted); margin-bottom:10px; }
.history-chips { display:flex; gap:8px; flex-wrap:wrap; }
.hchip {
  padding:6px 14px; border-radius:50px;
  border:1px solid var(--rim); background:var(--surface);
  font-size:12px; color:var(--muted); cursor:pointer; transition:all .2s;
}
.hchip:hover { color:var(--text); border-color:var(--neo2); background:rgba(255,47,160,.08); }

/* ══ SONGS SECTION ══ */
.songs-header {
  display:flex; align-items:baseline; gap:16px;
  margin: 56px 0 24px;
}
.songs-title {
  font-family:'Fraunces',serif;
  font-size:clamp(28px,4vw,44px);
  font-weight:300; letter-spacing:-.03em;
}
.songs-title em { font-style:italic; background:linear-gradient(90deg,var(--neo1),var(--neo2)); -webkit-background-clip:text; -webkit-text-fill-color:transparent; }
.songs-mood-tag {
  font-family:'Fira Code',monospace; font-size:11px; letter-spacing:.12em;
  text-transform:uppercase; color:var(--muted);
  padding:4px 12px; border:1px solid var(--rim); border-radius:50px;
}

.songs-grid {
  display:grid;
  grid-template-columns:repeat(auto-fill,minmax(185px,1fr));
  gap:16px;
}

.song-card {
  background:var(--surface); border:1px solid var(--rim);
  border-radius:16px; overflow:hidden;
  transition:all .3s var(--ease); cursor:pointer;
  animation:fadeUp .5s var(--ease) both; backdrop-filter:blur(12px);
}
.song-card:hover {
  transform:translateY(-8px) scale(1.02);
  box-shadow:0 24px 60px rgba(0,0,0,.6), 0 0 0 1px rgba(123,47,255,.3);
  border-color:rgba(123,47,255,.3);
}

.song-art-frame { width:100%; aspect-ratio:1; position:relative; overflow:hidden; background:linear-gradient(145deg,#120820,#200a30); }
.song-art-bg { position:absolute; inset:0; display:flex; align-items:center; justify-content:center; font-size:48px; opacity:.3; }
.song-art { position:absolute; inset:0; width:100%; height:100%; object-fit:cover; opacity:0; transition:opacity .5s; }
.song-art.loaded { opacity:1; }

.song-body { padding:14px; }
.song-name { font-size:13px; font-weight:700; color:var(--text); margin-bottom:4px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.song-artist { font-size:11px; color:var(--muted); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; margin-bottom:10px; font-family:'Fira Code',monospace; }
.song-audio { width:100%; height:26px; margin-bottom:8px; accent-color:var(--neo1); }
.no-preview { font-family:'Fira Code',monospace; font-size:10px; color:var(--muted); text-align:center; padding:4px 0 10px; }
.spotify-link {
  display:flex; align-items:center; justify-content:center; gap:6px;
  padding:8px 10px; background:#1DB954; border-radius:9px;
  color:#000; font-size:11px; font-weight:800; letter-spacing:.03em;
  text-decoration:none; transition:all .2s;
}
.spotify-link:hover { filter:brightness(1.12); transform:scale(1.02); }
.spotify-link svg { width:12px; height:12px; fill:#000; }

/* Skeleton */
.skel { height:270px; border-radius:16px; background:rgba(255,255,255,.04); animation:shimmer 1.4s ease-in-out infinite; }
@keyframes shimmer { 0%,100%{opacity:.4} 50%{opacity:.7} }

.fallback-note { grid-column:1/-1; padding:12px 18px; background:rgba(0,229,255,.06); border:1px solid rgba(0,229,255,.2); border-radius:12px; font-size:13px; color:var(--neo3); }
.error-notice { grid-column:1/-1; padding:18px 24px; background:rgba(255,47,160,.06); border:1px solid rgba(255,47,160,.2); border-radius:12px; font-size:13px; color:#ff85c8; line-height:1.8; }

/* ══ CAMERA MODAL ══ */
#cameraModal {
  display:none; position:fixed; inset:0; z-index:1000;
  background:rgba(3,1,10,.85); backdrop-filter:blur(16px);
  align-items:center; justify-content:center;
}
#cameraModal.open { display:flex; }

.cam-box {
  background:rgba(255,255,255,.04); border:1px solid var(--rim);
  border-radius:24px; padding:36px; text-align:center;
  max-width:520px; width:92%;
  box-shadow:0 60px 120px rgba(0,0,0,.8), inset 0 1px 0 rgba(255,255,255,.06);
  backdrop-filter:blur(20px);
  animation:fadeUp .4s var(--ease);
}
.cam-title { font-family:'Fraunces',serif; font-size:22px; font-weight:400; margin-bottom:6px; }
.cam-sub { font-size:13px; color:var(--muted); margin-bottom:16px; }
.cam-badge { display:inline-block; padding:4px 14px; border:1px solid rgba(57,255,122,.3); border-radius:50px; font-family:'Fira Code',monospace; font-size:10px; color:var(--neo4); margin-bottom:18px; letter-spacing:.06em; }
#camPreview { width:100%; border-radius:12px; margin-bottom:14px; background:#000; display:none; transform:scaleX(-1); border:1px solid var(--rim); }
#camStatus { font-size:13px; color:var(--muted); min-height:24px; margin-bottom:14px; line-height:1.5; }
#countdown { display:none; font-family:'Fraunces',serif; font-size:72px; font-weight:600; background:linear-gradient(135deg,var(--neo1),var(--neo2)); -webkit-background-clip:text; -webkit-text-fill-color:transparent; margin:8px 0; animation:countBeat 1s ease-in-out infinite; }
@keyframes countBeat { 0%,100%{transform:scale(1)} 50%{transform:scale(1.15)} }
.cam-btns { display:flex; gap:10px; justify-content:center; flex-wrap:wrap; }
#snapBtn { padding:12px 28px; background:linear-gradient(135deg,var(--neo1),var(--neo2)); color:#fff; border:none; border-radius:12px; font-family:'Cabinet Grotesk',sans-serif; font-size:13px; font-weight:700; cursor:pointer; display:none; transition:all .25s; }
#snapBtn:hover { transform:translateY(-1px); box-shadow:0 8px 24px rgba(123,47,255,.5); }
#snapBtn:disabled { opacity:.5; cursor:not-allowed; transform:none; }
.btn-close-cam { padding:12px 20px; background:transparent; border:1px solid var(--rim); border-radius:12px; font-family:'Cabinet Grotesk',sans-serif; font-size:13px; color:var(--muted); cursor:pointer; transition:all .2s; }
.btn-close-cam:hover { border-color:var(--neo2); color:var(--neo2); }

/* ══ TOAST ══ */
#toast {
  position:fixed; bottom:32px; left:50%;
  transform:translateX(-50%) translateY(16px);
  background:rgba(255,255,255,.08); color:var(--text);
  border:1px solid var(--rim); backdrop-filter:blur(20px);
  padding:11px 24px; border-radius:50px;
  font-size:13px; font-weight:600;
  opacity:0; pointer-events:none;
  transition:all .3s var(--ease); z-index:2000;
  white-space:nowrap; letter-spacing:.02em;
  box-shadow:0 8px 32px rgba(0,0,0,.5);
}
#toast.show { opacity:1; transform:translateX(-50%) translateY(0); }

/* ══ RESPONSIVE ══ */
@media(max-width:640px) {
  .app { padding:0 18px 60px; }
  .hero-title { font-size:36px; }
  .text-row { flex-direction:column; }
  .songs-grid { grid-template-columns:repeat(auto-fill,minmax(158px,1fr)); gap:12px; }
  .method-tabs { flex-wrap:wrap; }
  .mtab { flex:0 0 calc(50% - 2px); font-size:12px; }
  .detect-card { padding:24px 18px; }
}
</style>
</head>
<body>

<div class="orb orb1"></div>
<div class="orb orb2"></div>
<div class="orb orb3"></div>

<div id="toast"></div>

<!-- Camera Modal -->
<div id="cameraModal">
  <div class="cam-box">
    <div class="cam-title">Face Mood Detection</div>
    <div class="cam-sub">Your expression is analysed entirely in your browser</div>
    <div class="cam-badge">✓ face-api.js — 100% local processing</div>
    <video id="camPreview" autoplay playsinline muted></video>
    <canvas id="camCanvas" width="480" height="360" style="display:none"></canvas>
    <div id="camStatus">Initialising…</div>
    <div id="countdown"></div>
    <div class="cam-btns">
      <button id="snapBtn" onclick="startCountdown()">📸 Capture &amp; Analyse</button>
      <button class="btn-close-cam" onclick="closeCamera()">✕ Cancel</button>
    </div>
  </div>
</div>

<div class="app">

  <!-- Header -->
  <header>
    <a class="logo" href="dashboard.php">
      <div class="logo-mark"><svg viewBox="0 0 24 24"><path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z"/></svg></div>
      <span class="logo-text">EMORA</span>
    </a>
    <div class="header-right">
      <div class="user-pill">
        <div class="user-avatar"><?php echo strtoupper(substr($username,0,1)); ?></div>
        <span class="user-name"><?php echo htmlspecialchars($username); ?></span>
      </div>
      <a href="logout.php" class="btn-logout">Sign Out</a>
    </div>
  </header>

  <!-- Hero -->
  <div class="hero">
    <div class="hero-eyebrow">Your Personal Soundtrack</div>
    <h1 class="hero-title">
      Hello, <em><?php echo htmlspecialchars($username); ?></em><br>
      How are you <em>feeling?</em>
    </h1>
    <p class="hero-sub">Tell us your mood — we'll find music that fits perfectly.</p>
  </div>

  <!-- Mood Detection -->
  <div class="section-tag">Mood Detection</div>
  <div class="detect-card">
    <div class="detect-card-title">Describe your mood</div>
    <div class="detect-card-sub">Choose a detection method — type, speak, use your camera, or pick directly</div>

    <div class="method-tabs">
      <button class="mtab active" onclick="switchTab('text')"   id="tab-text">✏️ Type</button>
      <button class="mtab"        onclick="switchTab('voice')"  id="tab-voice">🎙️ Voice</button>
      <button class="mtab"        onclick="switchTab('camera')" id="tab-camera">📷 Camera</button>
      <button class="mtab"        onclick="switchTab('chips')"  id="tab-chips">😄 Pick</button>
    </div>

    <!-- TEXT -->
    <div class="tab-panel active" id="panel-text">
      <div class="text-row">
        <input class="mood-input" type="text" id="moodText"
          placeholder="e.g. I feel restless and a little nostalgic…"
          onkeydown="if(event.key==='Enter') detectMood()">
        <button class="btn-primary" id="detectBtn" onclick="detectMood()">
          <span id="btnLabel">Detect ✨</span>
          <div class="spinner" id="spinner"></div>
        </button>
      </div>
      <div class="input-hint">// be descriptive — more detail = better match</div>
    </div>

    <!-- VOICE -->
    <div class="tab-panel" id="panel-voice">
      <div class="voice-panel" id="voicePanelInner">
        <p class="voice-desc">Click the microphone, then speak naturally about how you're feeling. We'll transcribe and analyse your words in real time.</p>
        <div class="voice-orb-container">
          <div class="ripple-ring hidden" id="ring1"></div>
          <div class="ripple-ring hidden" id="ring2"></div>
          <div class="ripple-ring hidden" id="ring3"></div>
          <button class="voice-orb" id="voiceOrb" onclick="toggleVoice()" title="Start / stop recording">
            <span id="micIcon">🎙️</span>
          </button>
        </div>
        <div class="waveform" id="waveform">
          <div class="wbar"></div><div class="wbar"></div><div class="wbar"></div>
          <div class="wbar"></div><div class="wbar"></div><div class="wbar"></div>
          <div class="wbar"></div><div class="wbar"></div><div class="wbar"></div>
        </div>
        <div class="voice-status" id="voiceStatus">Tap the microphone to start speaking</div>
        <div class="voice-transcript-box" id="voiceTranscript"></div>
        <button class="btn-analyse-voice" id="analyseVoiceBtn" onclick="analyseCurrentTranscript()">🧠 Analyse My Mood</button>
        <div class="voice-privacy">🔒 No audio is sent to any server — speech is transcribed by your browser</div>
      </div>
    </div>

    <!-- CAMERA -->
    <div class="tab-panel" id="panel-camera">
      <div class="camera-panel-inner">
        <p class="camera-desc">We analyse your facial expression using face-api.js, which runs entirely in your browser — no images leave your device.</p>
        <button class="btn-camera" onclick="openCamera()">📷 Open Camera</button>
        <div class="voice-privacy">🔒 All face processing happens locally on your device</div>
      </div>
    </div>

    <!-- CHIPS -->
    <div class="tab-panel" id="panel-chips">
      <div class="chips-grid">
        <div class="chip" onclick="useMood('happy')">😄 Happy</div>
        <div class="chip" onclick="useMood('sad')">😢 Sad</div>
        <div class="chip" onclick="useMood('energetic')">⚡ Energetic</div>
        <div class="chip" onclick="useMood('chill')">😌 Chill</div>
        <div class="chip" onclick="useMood('romantic')">💖 Romantic</div>
        <div class="chip" onclick="useMood('angry')">😤 Angry</div>
        <div class="chip" onclick="useMood('focus')">🎯 Focus</div>
        <div class="chip" onclick="useMood('anxious')">😰 Anxious</div>
        <div class="chip" onclick="useMood('nostalgic')">🌙 Nostalgic</div>
        <div class="chip" onclick="useMood('lonely')">🌧️ Lonely</div>
        <div class="chip" onclick="useMood('confident')">💪 Confident</div>
        <div class="chip" onclick="useMood('tired')">😴 Tired</div>
        <div class="chip" onclick="useMood('hopeful')">🌅 Hopeful</div>
        <div class="chip" onclick="useMood('melancholy')">🌫️ Melancholy</div>
        <div class="chip" onclick="useMood('neutral')">😐 Neutral</div>
      </div>
    </div>

    <!-- Mood Result -->
    <div id="moodResult">
      <div class="mood-emoji" id="moodEmoji">🎵</div>
      <div class="mood-info">
        <div class="mood-label">Detected mood</div>
        <div class="mood-name" id="moodName">—</div>
        <div class="mood-via" id="moodVia"></div>
      </div>
      <button class="btn-get-songs" onclick="loadSongs(currentMood)">🎧 Get Songs</button>
    </div>

    <!-- History -->
    <div class="history-wrap" id="historyWrap">
      <div class="history-label">Recent moods</div>
      <div class="history-chips" id="historyChips"></div>
    </div>
  </div>

  <!-- Songs -->
  <div id="songsSection"></div>

</div>

</div><!-- /app -->

<script>
/* ══════════════════════════════════════════════
   CONSTANTS
══════════════════════════════════════════════ */
const EMOJIS = {
  happy:'😄', sad:'😢', neutral:'😐', angry:'😤', energetic:'⚡',
  romantic:'💖', chill:'😌', anxious:'😰', nostalgic:'🌙', lonely:'🌧️',
  confident:'💪', tired:'😴', hopeful:'🌅', focus:'🎯', melancholy:'🌫️',
  surprised:'😲', fearful:'😨', disgusted:'🤢'
};

let currentMood = '';
let moodHistory = JSON.parse(localStorage.getItem('moodTuneHistory') || '[]');

/* ══════════════════════════════════════════════
   UTILITIES
══════════════════════════════════════════════ */
const esc = s => String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');

function toast(msg, ms=2800) {
  const el = document.getElementById('toast');
  el.textContent = msg;
  el.classList.add('show');
  setTimeout(() => el.classList.remove('show'), ms);
}

function showMoodBadge(mood, via='') {
  currentMood = mood;
  document.getElementById('moodEmoji').textContent = EMOJIS[mood] || '🎵';
  document.getElementById('moodName').textContent  = mood.charAt(0).toUpperCase() + mood.slice(1);
  document.getElementById('moodVia').textContent   = via ? `via ${via}` : '';
  document.getElementById('moodResult').style.display = 'flex';

  moodHistory = [mood, ...moodHistory.filter(m=>m!==mood)].slice(0,6);
  localStorage.setItem('moodTuneHistory', JSON.stringify(moodHistory));
  renderHistory();
}

function renderHistory() {
  if (!moodHistory.length) return;
  document.getElementById('historyWrap').style.display = 'block';
  document.getElementById('historyChips').innerHTML =
    moodHistory.map(m =>
      `<div class="hchip" onclick="useMood('${m}')">${EMOJIS[m]||'🎵'} ${m}</div>`
    ).join('');
}
renderHistory();

function useMood(mood) {
  showMoodBadge(mood, 'quick pick');
  loadSongs(mood);
}

/* ══════════════════════════════════════════════
   TAB SWITCHING
══════════════════════════════════════════════ */
function switchTab(name) {
  document.querySelectorAll('.mtab').forEach(b => b.classList.remove('active'));
  document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
  document.getElementById('tab-' + name).classList.add('active');
  document.getElementById('panel-' + name).classList.add('active');
  if (name !== 'voice' && isRecording) stopVoice();
}

/* ══════════════════════════════════════════════
   TEXT DETECTION
══════════════════════════════════════════════ */
function setLoading(on) {
  document.getElementById('detectBtn').disabled       = on;
  document.getElementById('btnLabel').style.display   = on ? 'none' : 'inline';
  document.getElementById('spinner').style.display    = on ? 'inline-block' : 'none';
}

async function detectMood() {
  const text = document.getElementById('moodText').value.trim();
  if (!text) { toast('✏️ Please describe how you feel'); return; }
  setLoading(true);
  try {
    const res  = await fetch('mood.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({text}) });
    const data = await res.json();
    const mood = data.mood || clientKeyword(text);
    showMoodBadge(mood, 'text analysis');
    await loadSongs(mood);
  } catch {
    const mood = clientKeyword(text);
    showMoodBadge(mood, 'text analysis');
    await loadSongs(mood);
  }
  setLoading(false);
}

function clientKeyword(text) {
  text = text.toLowerCase();
  const kw = {
    happy:     ['happy','joy','joyful','glad','great','wonderful','fantastic','excited','elated','amazing','awesome','smile','laugh','blessed','cheerful','thrilled','delighted','ecstatic','overjoyed','over the moon','feeling good','feel great','love today','best day'],
    sad:       ['sad','unhappy','depressed','down','blue','miserable','heartbroken','cry','crying','tears','empty','hopeless','hurt','terrible','awful','sobbing','weeping','feel broken','feel crushed','heavy heart','want to cry','feel like crying','nothing matters','feel defeated'],
    angry:     ['angry','mad','furious','rage','frustrated','annoyed','irritated','hate','bitter','outraged','pissed','livid','fed up','seething','fuming','ticked off','so angry','drives me crazy','want to scream'],
    energetic: ['energetic','pumped','hyped','motivated','active','energized','fired up','alive','wired','buzzing','charged up','full of energy','raring to go','unstoppable'],
    romantic:  ['romantic','in love','crush','affection','tender','longing','dreamy','passionate','adore','smitten','butterflies','heart flutters','falling for','head over heels'],
    chill:     ['chill','relaxed','calm','peaceful','serene','mellow','laid back','tranquil','cozy','comfortable','zen','vibing','no worries','winding down','decompressing','just chilling'],
    anxious:   ['anxious','nervous','worried','stress','stressed','tense','overwhelmed','panic','scared','uneasy','jittery','on edge','freaking out','racing thoughts','heart pounding','shaking','sweating','stomach in knots','nauseous','dizzy','lightheaded','overthinking','spiraling','dread','dreading','so nervous','really anxious','too much'],
    nostalgic: ['nostalgic','memories','old times','childhood','throwback','remember','past','used to','back in the day','good old days','remember when','brings back memories','takes me back','simpler times'],
    tired:     ['tired','exhausted','sleepy','drained','fatigued','worn out','weary','bored','sluggish','lethargic','burnout','wiped out','dead tired','no energy','running on empty','brain fog','heavy eyes','body aches','mentally exhausted','long day','rough day','exhausting day','tiring day','need sleep','need rest','my day was exhausted','day was exhausting','really tired','so drained','completely drained'],
    focus:     ['focus','focused','concentrate','studying','study','work','working','productive','grind','deadline','in the zone','all nighter','exam','test tomorrow','due tomorrow','locked in'],
    lonely:    ['lonely','alone','isolated','abandoned','forgotten','no one','by myself','left out','invisible','no friends','all alone','nobody cares','feel unwanted','feel ignored','nobody around'],
    confident: ['confident','strong','powerful','brave','bold','fearless','unstoppable','determined','proud','capable','got this','i can do this','crushing it','nailing it','feeling myself'],
    hopeful:   ['hope','hopeful','optimistic','looking forward','better days','things will get better','brighter tomorrow','new beginning','fresh start','new chapter','i ll be okay','going to be okay'],
    melancholy:['melancholy','bittersweet','wistful','pensive','reflective','yearning','somber','heavy hearted','mixed feelings','feel off','something s off','staring into space','zoning out','in my head'],
    disgusted: ['disgusted','vomit','vomiting','feel like vomiting','want to vomit','want to throw up','throwing up','feel like throwing up','sick to my stomach','nauseated','repulsed','gross','revolting','makes me sick','stomach churning','gagging'],
    fearful:   ['afraid','terrified','dread','frightened','petrified','horror','nightmare','so scared','paranoid','feel unsafe','impending doom'],
    surprised: ['surprised','shocked','unbelievable','can t believe','mind blown','astonished','blown away','didn t expect','stunned','jaw dropped','speechless'],
  };
  // Stem-based regex fallback patterns
  const stemFallback = [
    [/\b(exhaust|tir(ed|ing)|wip(ed|ing)|fatigue|drain)\w*/i, 'tired'],
    [/\b(vomit|nauseat|throw.?up|puke|gag)\w*/i,             'disgusted'],
    [/\b(stress|anxiet|worr|panic|overwhelm)\w*/i,           'anxious'],
    [/\b(depress|unhapp|grief|mourn|sorrow)\w*/i,            'sad'],
    [/\b(happi|joyful|excit|elat|cheerful)\w*/i,             'happy'],
    [/\b(angr|furi|irrit|rage|livid)\w*/i,                   'angry'],
    [/\b(calm|peace|relax|chill|serenity)\w*/i,              'chill'],
    [/\b(confident|fearless|brave|bold|proud)\w*/i,          'confident'],
    [/\b(hope|optimis|believ|faith)\w*/i,                    'hopeful'],
  ];
  let best = 'neutral', top = 0;
  for (const [m, words] of Object.entries(kw)) {
    const score = words.filter(w => text.includes(w)).length;
    if (score > top) { top = score; best = m; }
  }
  if (top === 0) {
    for (const [pattern, mood] of stemFallback) {
      if (pattern.test(text)) { best = mood; break; }
    }
  }
  return best;
}

/* ══════════════════════════════════════════════
   VOICE DETECTION — fully rebuilt
══════════════════════════════════════════════ */
let recognition   = null;
let isRecording   = false;
let voiceTimeout  = null;
let finalText     = '';   // accumulates confirmed final segments
let interimText   = '';   // live interim

function setVoiceUI(state) {
  // state: 'idle' | 'listening' | 'analysing' | 'done' | 'error'
  const orb       = document.getElementById('voiceOrb');
  const mic       = document.getElementById('micIcon');
  const status    = document.getElementById('voiceStatus');
  const waveform  = document.getElementById('waveform');
  const rings     = [document.getElementById('ring1'), document.getElementById('ring2'), document.getElementById('ring3')];
  const analyseBtn = document.getElementById('analyseVoiceBtn');

  // Clear all state classes
  status.classList.remove('listening','success','error');
  orb.classList.remove('active');
  waveform.classList.remove('active');
  rings.forEach(r => r.classList.add('hidden'));

  if (state === 'idle') {
    mic.textContent     = '🎙️';
    status.textContent  = 'Tap the microphone to start speaking';
  } else if (state === 'listening') {
    orb.classList.add('active');
    mic.textContent     = '⏹️';
    waveform.classList.add('active');
    rings.forEach(r => r.classList.remove('hidden'));
    status.classList.add('listening');
    status.textContent  = '🔴 Listening… speak naturally about how you feel';
    analyseBtn.classList.remove('visible');
  } else if (state === 'analysing') {
    mic.textContent     = '🎙️';
    status.textContent  = '🧠 Analysing your mood from speech…';
    analyseBtn.classList.remove('visible');
  } else if (state === 'done') {
    mic.textContent     = '🎙️';
    status.classList.add('success');
  } else if (state === 'error') {
    mic.textContent     = '🎙️';
    status.classList.add('error');
  }
}

function showTranscript(final, interim) {
  const box = document.getElementById('voiceTranscript');
  const analyseBtn = document.getElementById('analyseVoiceBtn');

  if (!final && !interim) {
    box.classList.remove('visible');
    return;
  }

  box.classList.add('visible');
  box.innerHTML = (final ? esc(final) : '')
    + (interim ? `<span class="interim"> ${esc(interim)}</span>` : '');

  // Show "Analyse" button if we have enough text
  if ((final + interim).trim().length > 4) {
    analyseBtn.classList.add('visible');
  }
}

function initRecognition() {
  const SR = window.SpeechRecognition || window.webkitSpeechRecognition;
  if (!SR) return null;

  const r = new SR();
  r.continuous      = true;
  r.interimResults  = true;
  r.lang            = 'en-US';
  r.maxAlternatives = 1;

  r.onstart = () => {
    isRecording = true;
    finalText   = '';
    interimText = '';
    setVoiceUI('listening');
    showTranscript('','');
    // Auto-stop after 20 seconds
    voiceTimeout = setTimeout(() => stopVoice(), 20000);
  };

  r.onresult = (event) => {
    let interim = '';
    for (let i = event.resultIndex; i < event.results.length; i++) {
      const chunk = event.results[i][0].transcript;
      if (event.results[i].isFinal) {
        finalText += (finalText ? ' ' : '') + chunk.trim();
      } else {
        interim = chunk;
      }
    }
    interimText = interim;
    showTranscript(finalText, interimText);
  };

  r.onerror = (e) => {
    clearTimeout(voiceTimeout);
    isRecording = false;
    if (e.error === 'not-allowed') {
      setVoiceUI('error');
      document.getElementById('voiceStatus').innerHTML =
        '❌ Microphone access denied. Allow mic permission in your browser settings.';
    } else if (e.error === 'no-speech') {
      setVoiceUI('error');
      document.getElementById('voiceStatus').textContent =
        '🤫 No speech detected — try speaking louder or closer.';
    } else if (e.error === 'aborted') {
      setVoiceUI('idle');
    } else {
      setVoiceUI('error');
      document.getElementById('voiceStatus').textContent = `❌ Error: ${e.error} — please try again.`;
    }
  };

  r.onend = () => {
    clearTimeout(voiceTimeout);
    isRecording = false;
    const captured = (finalText + ' ' + interimText).trim();
    if (captured.length > 3) {
      analyseVoiceText(captured);
    } else {
      setVoiceUI('idle');
    }
  };

  return r;
}

function toggleVoice() {
  if (isRecording) stopVoice();
  else startVoice();
}

function startVoice() {
  const SR = window.SpeechRecognition || window.webkitSpeechRecognition;
  if (!SR) {
    setVoiceUI('error');
    document.getElementById('voiceStatus').innerHTML =
      '❌ Voice not supported in this browser.<br><small>Please use Chrome, Edge, or Safari.</small>';
    return;
  }
  recognition = initRecognition();
  if (recognition) recognition.start();
}

function stopVoice() {
  clearTimeout(voiceTimeout);
  if (recognition) {
    recognition.onend = null; // prevent double-trigger
    recognition.stop();
    recognition = null;
  }
  isRecording = false;
  const captured = (finalText + ' ' + interimText).trim();
  if (captured.length > 3) {
    analyseVoiceText(captured);
  } else {
    setVoiceUI('idle');
  }
}

/* Called by the "Analyse My Mood" button */
function analyseCurrentTranscript() {
  const box = document.getElementById('voiceTranscript');
  const text = (finalText + ' ' + interimText).trim()
             || box.textContent.trim();
  if (isRecording) stopVoice();
  if (text.length > 2) analyseVoiceText(text);
}

async function analyseVoiceText(text) {
  setVoiceUI('analysing');

  let mood = 'neutral';
  try {
    const res = await fetch('voice_mood.php', {
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body:JSON.stringify({ transcript: text })
    });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    const data = await res.json();
    if (data.error) throw new Error(data.error);
    mood = data.mood || clientKeyword(text);
  } catch(err) {
    console.warn('voice_mood.php failed, using client fallback:', err.message);
    mood = clientKeyword(text);
  }

  setVoiceUI('done');
  document.getElementById('voiceStatus').textContent =
    `✅ Detected: ${mood.charAt(0).toUpperCase() + mood.slice(1)}`;

  setTimeout(() => {
    showMoodBadge(mood, 'voice analysis');
    loadSongs(mood);
  }, 700);
}

/* Browser support warning on load */
if (!window.SpeechRecognition && !window.webkitSpeechRecognition) {
  window.addEventListener('DOMContentLoaded', () => {
    const s = document.getElementById('voiceStatus');
    if (s) {
      s.classList.add('error');
      s.textContent = '⚠️ Voice not supported here — please use Chrome, Edge, or Safari.';
    }
  });
}

/* ══════════════════════════════════════════════
   CAMERA — face-api.js
══════════════════════════════════════════════ */
const MODEL_URL = '/webdev/weights';
let camStream = null, countTimer = null, faceLoaded = false;

function moodFromExpressions(e) {
  if (e.happy > 0.5)                             return 'happy';
  if (e.happy > 0.25 && e.surprised > 0.1)       return 'energetic';
  if (e.sad > 0.4)                               return 'sad';
  if (e.sad > 0.2 && e.fearful > 0.1)            return 'anxious';
  if (e.sad > 0.15 && e.neutral > 0.5)            return 'melancholy';
  if (e.angry > 0.3)                             return 'angry';
  if (e.angry > 0.15 || e.fearful > 0.2)         return 'anxious';
  if (e.surprised > 0.3)                         return 'surprised';
  if (e.neutral > 0.85)                          return 'chill';
  if (e.neutral > 0.7 && e.sad > 0.05)            return 'tired';
  if (e.neutral > 0.7 && e.angry > 0.05)          return 'focus';
  if (e.neutral > 0.6 && e.happy > 0.1)           return 'hopeful';
  return 'neutral';
}

async function loadFaceModels() {
  if (faceLoaded) return;
  document.getElementById('camStatus').textContent = '⏳ Loading face-api models…';
  await Promise.all([
    faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
    faceapi.nets.faceExpressionNet.loadFromUri(MODEL_URL),
  ]);
  faceLoaded = true;
}

async function openCamera() {
  document.getElementById('cameraModal').classList.add('open');
  const snap = document.getElementById('snapBtn');
  const vid  = document.getElementById('camPreview');
  snap.style.display = 'none'; snap.disabled = true;
  document.getElementById('countdown').style.display = 'none';
  document.getElementById('camStatus').textContent = '⏳ Loading models & camera…';
  try {
    const [stream] = await Promise.all([
      navigator.mediaDevices.getUserMedia({ video:{width:{ideal:640},height:{ideal:480},facingMode:'user'}, audio:false }),
      loadFaceModels()
    ]);
    camStream = stream;
    vid.srcObject = stream; vid.style.display = 'block';
    await vid.play();
    await new Promise(r => setTimeout(r, 1000));
    document.getElementById('camStatus').textContent = '✅ Ready! Make your expression, then click Capture.';
    snap.style.display = 'inline-block'; snap.disabled = false;
  } catch(err) {
    const map = { NotAllowedError:'❌ Camera permission denied — click the camera icon in your address bar.', NotFoundError:'❌ No camera found on this device.' };
    document.getElementById('camStatus').textContent = map[err.name] || `❌ ${err.message}`;
  }
}

function closeCamera() {
  if (countTimer) { clearInterval(countTimer); countTimer = null; }
  if (camStream)  { camStream.getTracks().forEach(t=>t.stop()); camStream = null; }
  document.getElementById('cameraModal').classList.remove('open');
  const vid = document.getElementById('camPreview');
  vid.style.display = 'none'; vid.srcObject = null;
  document.getElementById('snapBtn').style.display = 'none';
  document.getElementById('countdown').style.display = 'none';
  document.getElementById('camStatus').textContent = 'Initialising…';
}

function startCountdown() {
  const snap = document.getElementById('snapBtn');
  const cd   = document.getElementById('countdown');
  snap.disabled = true; cd.style.display = 'block';
  let n = 3; cd.textContent = n;
  document.getElementById('camStatus').textContent = '🕐 Hold still…';
  countTimer = setInterval(() => {
    n--;
    if (n > 0) { cd.textContent = n; }
    else { clearInterval(countTimer); countTimer = null; cd.style.display = 'none'; captureAndAnalyse(); }
  }, 1000);
}

async function captureAndAnalyse() {
  const status = document.getElementById('camStatus');
  const snap   = document.getElementById('snapBtn');
  const vid    = document.getElementById('camPreview');
  status.textContent = '🔍 Analysing expression…';
  try {
    const samples = [];
    for (let i = 0; i < 5; i++) {
      const r = await faceapi.detectSingleFace(vid, new faceapi.TinyFaceDetectorOptions({inputSize:320,scoreThreshold:0.3})).withFaceExpressions();
      if (r) samples.push(r.expressions);
      await new Promise(r=>setTimeout(r,280));
    }
    if (!samples.length) {
      status.innerHTML = '😕 No face detected — ensure good lighting and centre your face, then try again.';
      snap.disabled = false; snap.style.display = 'inline-block'; return;
    }
    const keys = ['happy','sad','angry','fearful','disgusted','surprised','neutral'];
    const avg  = {};
    for (const k of keys) avg[k] = samples.reduce((s,e)=>s+(e[k]||0),0)/samples.length;
    const mood = moodFromExpressions(avg);
    const top  = keys.sort((a,b)=>avg[b]-avg[a])[0];
    status.innerHTML = `✅ Detected: <strong style="color:var(--accent);text-transform:capitalize">${mood}</strong> <small style="opacity:.6">(${top} ${Math.round(avg[top]*100)}%)</small>`;
    setTimeout(() => { closeCamera(); showMoodBadge(mood,'camera analysis'); loadSongs(mood); }, 900);
  } catch(e) {
    status.textContent = `⚠️ Analysis failed: ${e.message}`;
    snap.disabled = false; snap.style.display = 'inline-block';
  }
}

/* ══════════════════════════════════════════════
   SONGS
══════════════════════════════════════════════ */
async function loadSongs(mood) {
  const sec = document.getElementById('songsSection');
  sec.innerHTML = `
    <div class="songs-header">
      <div class="songs-title">Songs for <em>${mood}</em></div>
      <span class="songs-mood-tag">${EMOJIS[mood]||'🎵'} ${mood}</span>
    </div>
    <div class="songs-grid" id="songsGrid">
      ${Array(10).fill('<div class="skel"></div>').join('')}
    </div>`;
  sec.scrollIntoView({behavior:'smooth',block:'start'});

  try {
    const res  = await fetch(`recommend.php?mood=${encodeURIComponent(mood)}&_=${Date.now()}`);
    const ct   = res.headers.get('content-type')||'';
    if (!res.ok || !ct.includes('application/json')) throw new Error(`Server error ${res.status}`);
    const data = await res.json();
    const grid = document.getElementById('songsGrid');

    if (!data.success || !Array.isArray(data.tracks) || !data.tracks.length) {
      grid.innerHTML = `<div class="error-notice">😔 Couldn't load songs right now.${data.spotify_err?`<br><small>${esc(data.spotify_err)}</small>`:''}</div>`;
      return;
    }
    grid.innerHTML = data.method==='hard_fallback'
      ? `<div class="fallback-note">🎵 Curated picks — click <strong>Open in Spotify</strong> to listen</div>`
      : '';

    data.tracks.forEach((t, i) => {
      const name   = esc(t.name||'Unknown');
      const artist = esc(t.artists?.[0]?.name||'Unknown Artist');
      const spotUrl= t.external_urls?.spotify||'#';
      const prev   = t.preview_url||null;
      const img    = t.album?.images?.[0]?.url||null;
      grid.innerHTML += `
        <div class="song-card" style="animation-delay:${i*0.05}s">
          <div class="song-art-frame">
            <div class="song-art-bg">🎵</div>
            <img class="song-art" id="art-${i}" alt="${name}" loading="lazy"${img?` src="${esc(img)}"`:''}>
          </div>
          <div class="song-body">
            <div class="song-name" title="${name}">${name}</div>
            <div class="song-artist" title="${artist}">${artist}</div>
            ${prev
              ? `<audio class="song-audio" controls preload="none" src="${esc(prev)}" onclick="event.stopPropagation()"></audio>`
              : `<div class="no-preview">No preview</div>`}
            <a class="spotify-link" href="${esc(spotUrl)}" target="_blank" rel="noopener" onclick="event.stopPropagation()">
              <svg viewBox="0 0 24 24"><path d="M12 0C5.4 0 0 5.4 0 12s5.4 12 12 12 12-5.4 12-12S18.66 0 12 0zm5.521 17.34c-.24.359-.66.48-1.021.24-2.82-1.74-6.36-2.101-10.561-1.141-.418.122-.779-.179-.899-.539-.12-.421.18-.78.54-.9 4.56-1.021 8.52-.6 11.64 1.32.42.18.479.659.301 1.02zm1.44-3.3c-.301.42-.841.6-1.262.3-3.239-1.98-8.159-2.58-11.939-1.38-.479.12-1.02-.12-1.14-.6-.12-.48.12-1.021.6-1.141C9.6 9.9 15 10.561 18.72 12.84c.361.181.54.78.241 1.2zm.12-3.36C15.24 8.4 8.82 8.16 5.16 9.301c-.6.179-1.2-.181-1.38-.721-.18-.601.18-1.2.72-1.381 4.26-1.26 11.28-1.02 15.721 1.621.539.3.719 1.02.419 1.56-.299.421-1.02.599-1.559.3z"/></svg>
              Open in Spotify
            </a>
          </div>
        </div>`;
    });

    data.tracks.forEach((t, i) => {
      const el  = document.getElementById(`art-${i}`);
      if (!el) return;
      const src = t.album?.images?.[0]?.url;
      if (src) {
        el.onload  = () => el.classList.add('loaded');
        el.onerror = () => fetchItunesArt(i, t.name, t.artists?.[0]?.name);
        if (el.complete && el.naturalWidth > 0) el.classList.add('loaded');
      } else {
        fetchItunesArt(i, t.name, t.artists?.[0]?.name);
      }
    });

  } catch(e) {
    document.getElementById('songsSection').innerHTML =
      `<div class="error-notice">⚠ Could not load recommendations: ${esc(e.message)}</div>`;
  }
}

async function fetchItunesArt(idx, name, artist) {
  try {
    const q = encodeURIComponent(`${name||''} ${artist||''}`);
    const r = await fetch(`https://itunes.apple.com/search?term=${q}&media=music&entity=song&limit=1`);
    if (r.ok) {
      const d = await r.json();
      const u = d?.results?.[0]?.artworkUrl100;
      if (u) {
        const el = document.getElementById(`art-${idx}`);
        if (el) { el.src = u.replace('100x100bb','500x500bb'); el.onload = ()=>el.classList.add('loaded'); }
      }
    }
  } catch {}
}
</script>
</body>
</html>