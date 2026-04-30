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
<title>EMORA — Mood Analytics</title>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,100..900;1,9..144,100..900&family=Cabinet+Grotesk:wght@300;400;500;700;800&family=Fira+Code:wght@300;400&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
<style>
:root {
  --void:#03010a; --surface:rgba(255,255,255,0.035); --rim:rgba(255,255,255,0.08);
  --rim2:rgba(255,255,255,0.04); --neo1:#7b2fff; --neo2:#ff2fa0; --neo3:#00e5ff;
  --neo4:#39ff7a; --text:#f0ecff; --muted:rgba(240,236,255,0.42); --ink:rgba(240,236,255,0.7);
  --ease:cubic-bezier(.22,1,.36,1);
}
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box}
body{min-height:100vh;background:var(--void);font-family:'Cabinet Grotesk',sans-serif;color:var(--text);overflow-x:hidden}

.orb{position:fixed;border-radius:50%;filter:blur(130px);pointer-events:none;z-index:0;animation:orbDrift 20s ease-in-out infinite alternate}
.orb1{width:700px;height:700px;background:#7b2fff;opacity:.1;top:-200px;right:-200px}
.orb2{width:400px;height:400px;background:#ff2fa0;opacity:.06;bottom:-100px;left:-100px;animation-delay:-10s}
@keyframes orbDrift{0%{transform:translate(0,0)}100%{transform:translate(40px,30px)}}

.app{position:relative;z-index:1;max-width:1200px;margin:0 auto;padding:0 32px 100px}

/* ── Header ── */
header{display:flex;align-items:center;justify-content:space-between;padding:28px 0;border-bottom:1px solid var(--rim2);margin-bottom:48px}
.logo{display:flex;align-items:center;gap:12px;text-decoration:none;color:inherit}
.logo-text{font-family:'Fraunces',serif;font-size:20px;font-weight:800}
.header-right{display:flex;align-items:center;gap:10px}
.nav-link{padding:8px 16px;border:1px solid var(--rim);border-radius:9px;color:var(--muted);text-decoration:none;font-size:13px;transition:all .2s}
.nav-link:hover{color:var(--text);border-color:rgba(123,47,255,.5)}
.nav-link.active{border-color:var(--neo1);color:var(--text);background:rgba(123,47,255,.1)}

/* ── Range Tabs ── */
.range-tabs{display:flex;gap:8px;margin-bottom:28px}
.tab{padding:7px 18px;border:1px solid var(--rim);border-radius:8px;background:transparent;color:var(--muted);font-family:'Cabinet Grotesk',sans-serif;font-size:13px;cursor:pointer;transition:all .2s}
.tab.active,.tab:hover{border-color:var(--neo1);color:var(--text);background:rgba(123,47,255,.15)}

/* ── Stats Row ── */
.stat-row{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:16px}
.stat-card{background:var(--surface);border:1px solid var(--rim);border-radius:18px;padding:22px 24px}
.stat-accent{font-size:30px;font-weight:800;font-family:'Fraunces',serif;background:linear-gradient(135deg,var(--neo1),var(--neo2));-webkit-background-clip:text;-webkit-text-fill-color:transparent;line-height:1.1;margin-bottom:6px}
.stat-label{font-size:11px;color:var(--muted);font-family:'Fira Code',monospace;text-transform:uppercase;letter-spacing:.08em}

/* ── Positivity Bar (full width) ── */
.pos-card{background:var(--surface);border:1px solid var(--rim);border-radius:18px;padding:20px 28px;margin-bottom:28px;display:flex;align-items:center;gap:20px}
.pos-label{font-size:12px;color:var(--muted);font-family:'Fira Code',monospace;text-transform:uppercase;white-space:nowrap;min-width:100px}
.pos-track{flex:1;height:8px;background:rgba(255,255,255,.08);border-radius:8px;overflow:hidden}
.pos-fill{height:100%;background:linear-gradient(90deg,var(--neo1),var(--neo3),var(--neo4));border-radius:8px;transition:width 1.2s var(--ease);width:0%}
.pos-score{font-size:15px;font-weight:700;font-family:'Fraunces',serif;color:var(--neo4);white-space:nowrap}

/* ── Charts Grid ── */
.grid-2{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px}
.chart-card{background:var(--surface);border:1px solid var(--rim);border-radius:20px;padding:28px}
.chart-title{font-size:13px;font-family:'Fira Code',monospace;color:var(--muted);text-transform:uppercase;letter-spacing:.08em;margin-bottom:20px}

/* ── Timeline list ── */
.tl-list{display:flex;flex-direction:column;gap:8px;max-height:380px;overflow-y:auto;padding-right:4px}
.tl-list::-webkit-scrollbar{width:4px}
.tl-list::-webkit-scrollbar-track{background:transparent}
.tl-list::-webkit-scrollbar-thumb{background:var(--rim);border-radius:4px}
.tl-item{display:flex;align-items:center;gap:14px;padding:12px 14px;background:rgba(255,255,255,.03);border:1px solid var(--rim2);border-radius:12px;transition:background .2s}
.tl-item:hover{background:rgba(255,255,255,.06)}
.tl-emoji{font-size:20px;width:32px;text-align:center;flex-shrink:0}
.tl-mood{font-weight:600;text-transform:capitalize;font-size:14px}
.tl-date{font-size:11px;color:var(--muted);font-family:'Fira Code',monospace;margin-top:2px}
.tl-method{font-size:10px;color:var(--neo1);font-family:'Fira Code',monospace;margin-left:auto;flex-shrink:0}

/* ── Liked Songs ── */
.liked-list{display:flex;flex-direction:column;gap:8px;max-height:380px;overflow-y:auto;padding-right:4px}
.liked-list::-webkit-scrollbar{width:4px}
.liked-list::-webkit-scrollbar-track{background:transparent}
.liked-list::-webkit-scrollbar-thumb{background:var(--rim);border-radius:4px}
.liked-item{display:flex;align-items:center;gap:12px;padding:10px 14px;background:rgba(255,255,255,.03);border:1px solid var(--rim2);border-radius:12px;text-decoration:none;color:inherit;transition:background .2s}
.liked-item:hover{background:rgba(123,47,255,.12);border-color:rgba(123,47,255,.3)}
.liked-art{width:38px;height:38px;border-radius:8px;object-fit:cover;background:rgba(255,255,255,.08);flex-shrink:0}
.liked-art-placeholder{width:38px;height:38px;border-radius:8px;background:linear-gradient(135deg,var(--neo1),var(--neo2));display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0}
.liked-track{font-weight:600;font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.liked-artist{font-size:11px;color:var(--muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.liked-mood-tag{margin-left:auto;font-size:10px;font-family:'Fira Code',monospace;color:var(--neo3);flex-shrink:0}
.empty-state{text-align:center;color:var(--muted);font-size:13px;padding:40px 20px}

/* ── Skeleton ── */
.skel{background:rgba(255,255,255,0.05);border-radius:12px;animation:pulse 1.5s infinite}
@keyframes pulse{0%{opacity:.5}50%{opacity:.8}100%{opacity:.5}}
</style>
</head>
<body>
<div class="orb orb1"></div>
<div class="orb orb2"></div>

<div class="app">
  <header>
    <a class="logo" href="dashboard.php">
      <span class="logo-text">EMORA</span>
    </a>
    <div class="header-right">
      <a href="dashboard.php" class="nav-link">🎵 Dashboard</a>
      <a href="history.php" class="nav-link active">📊 Analytics</a>
      <a href="logout.php" class="nav-link">Sign Out</a>
    </div>
  </header>

  <!-- Range Tabs -->
  <div class="range-tabs">
    <button class="tab active" onclick="switchRange(7,this)">7 Days</button>
    <button class="tab" onclick="switchRange(30,this)">30 Days</button>
    <button class="tab" onclick="switchRange(90,this)">90 Days</button>
  </div>

  <!-- Stat Cards -->
  <div class="stat-row" id="statRow">
    <div class="skel" style="height:88px"></div>
    <div class="skel" style="height:88px"></div>
    <div class="skel" style="height:88px"></div>
    <div class="skel" style="height:88px"></div>
  </div>

  <!-- Positivity Bar -->
  <div class="pos-card" id="posCard">
    <div class="pos-label">Positivity</div>
    <div class="pos-track"><div class="pos-fill" id="posFill"></div></div>
    <div class="pos-score" id="posScore">—</div>
  </div>

  <!-- Charts Row -->
  <div class="grid-2">
    <div class="chart-card">
      <div class="chart-title">📈 Mood Timeline</div>
      <canvas id="lineChart" height="200"></canvas>
    </div>
    <div class="chart-card">
      <div class="chart-title">🎯 Mood Distribution</div>
      <canvas id="donutChart" height="200"></canvas>
    </div>
  </div>

  <!-- History + Liked Songs Row -->
  <div class="grid-2">
    <div class="chart-card">
      <div class="chart-title">🕐 Recent History</div>
      <div class="tl-list" id="timelineList">
        <div class="skel" style="height:48px"></div>
        <div class="skel" style="height:48px"></div>
        <div class="skel" style="height:48px"></div>
      </div>
    </div>
    <div class="chart-card">
      <div class="chart-title">❤️ Liked Songs</div>
      <div class="liked-list" id="likedList">
        <div class="skel" style="height:58px"></div>
        <div class="skel" style="height:58px"></div>
        <div class="skel" style="height:58px"></div>
      </div>
    </div>
  </div>
</div>

<script>
// ── Constants ─────────────────────────────────────────────────────────────────
const EMOJIS = {
  happy:'😄', sad:'😢', neutral:'😐', angry:'😤', energetic:'⚡',
  romantic:'💖', chill:'😌', focus:'🎯', anxious:'😰', nostalgic:'🌅',
  lonely:'🌧️', confident:'💪', tired:'😴', hopeful:'🌟', surprised:'😲',
  fearful:'😨', disgusted:'🤢', melancholy:'🌫️'
};

const MOOD_COLORS = {
  happy:'#fbbf24', sad:'#60a5fa', angry:'#f87171', neutral:'#94a3b8',
  energetic:'#f97316', romantic:'#f472b6', chill:'#34d399', focus:'#818cf8',
  anxious:'#a78bfa', nostalgic:'#fb923c', lonely:'#7dd3fc', confident:'#4ade80',
  tired:'#9ca3af', hopeful:'#fde68a', surprised:'#f0abfc', fearful:'#c084fc',
  disgusted:'#86efac', melancholy:'#93c5fd'
};

// Mood → numeric for line chart
const MOOD_VAL = {
  happy:10, hopeful:8, energetic:8, confident:8, romantic:7, surprised:7,
  chill:6, focus:6, neutral:5, nostalgic:5, tired:3, anxious:3,
  lonely:3, melancholy:3, sad:2, angry:2, fearful:2, disgusted:1
};

let charts = {};

// ── Range switcher ────────────────────────────────────────────────────────────
function switchRange(range, el) {
  document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
  el.classList.add('active');
  loadAnalytics(range);
}

// ── Main loader ───────────────────────────────────────────────────────────────
async function loadAnalytics(range = 7) {
  try {
    const res  = await fetch(`analytics.php?range=${range}`);
    const data = await res.json();
    if (data.error) { console.error(data.error); return; }
    renderStats(data);
    renderLineChart(data);
    renderDonutChart(data);
    renderTimeline(data);
    renderLiked(data);
  } catch (e) {
    console.error("Failed to load analytics", e);
  }
}

// ── Stats ─────────────────────────────────────────────────────────────────────
function renderStats(data) {
  const s        = data.stats   || {};
  const topMood  = data.top_mood || 'neutral';
  const posScore = data.pos_score ?? 5;
  const pct      = Math.round((posScore / 10) * 100);
  const emoji    = EMOJIS[topMood] || '🎵';

  document.getElementById('statRow').innerHTML = `
    <div class="stat-card">
      <div class="stat-accent">${s.current_streak ?? 0} 🔥</div>
      <div class="stat-label">Day Streak</div>
    </div>
    <div class="stat-card">
      <div class="stat-accent">${s.best_streak ?? 0}</div>
      <div class="stat-label">Best Streak</div>
    </div>
    <div class="stat-card">
      <div class="stat-accent">${s.total_logs ?? 0}</div>
      <div class="stat-label">Total Logs</div>
    </div>
    <div class="stat-card">
      <div class="stat-accent" style="text-transform:capitalize">${emoji} ${topMood}</div>
      <div class="stat-label">Top Mood</div>
    </div>`;

  // Positivity bar
  document.getElementById('posScore').textContent = `${posScore}/10`;
  setTimeout(() => {
    document.getElementById('posFill').style.width = pct + '%';
  }, 100);
}

// ── Line Chart ────────────────────────────────────────────────────────────────
function renderLineChart(data) {
  // Destroy old chart if exists
  if (charts.line) { charts.line.destroy(); }

  const timeline = data.timeline || [];

  if (timeline.length === 0) {
    document.getElementById('lineChart').parentElement.innerHTML +=
      '<div class="empty-state">No timeline data yet.<br>Log some moods to see your trend!</div>';
    return;
  }

  // Build day → avg mood value map
  const dayMap = {};
  const countMap = {};
  timeline.forEach(row => {
    const val = MOOD_VAL[row.mood] ?? 5;
    if (!dayMap[row.day]) { dayMap[row.day] = 0; countMap[row.day] = 0; }
    dayMap[row.day]   += val * row.cnt;
    countMap[row.day] += parseInt(row.cnt);
  });

  const labels = Object.keys(dayMap).sort();
  const values = labels.map(d => (dayMap[d] / countMap[d]).toFixed(1));
  const bgColors = values.map(v => {
    const n = parseFloat(v);
    if (n >= 7) return 'rgba(57,255,122,0.15)';
    if (n >= 4) return 'rgba(0,229,255,0.10)';
    return 'rgba(255,47,160,0.15)';
  });

  const ctx = document.getElementById('lineChart').getContext('2d');
  charts.line = new Chart(ctx, {
    type: 'line',
    data: {
      labels: labels.map(d => {
        const dt = new Date(d);
        return dt.toLocaleDateString('en-GB',{day:'numeric',month:'short'});
      }),
      datasets: [{
        label: 'Mood Score',
        data: values,
        fill: true,
        backgroundColor: 'rgba(123,47,255,0.12)',
        borderColor: '#7b2fff',
        borderWidth: 2.5,
        pointBackgroundColor: values.map(v => {
          const n = parseFloat(v);
          if (n >= 7) return '#39ff7a';
          if (n >= 4) return '#00e5ff';
          return '#ff2fa0';
        }),
        pointRadius: 5,
        pointHoverRadius: 7,
        tension: 0.4,
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: 'rgba(3,1,10,0.9)',
          titleColor: '#f0ecff',
          bodyColor: 'rgba(240,236,255,0.7)',
          borderColor: 'rgba(123,47,255,0.4)',
          borderWidth: 1,
          callbacks: {
            label: ctx => {
              const v = parseFloat(ctx.parsed.y);
              const label = v>=8?'Great 😄':v>=6?'Good 🙂':v>=4?'Okay 😐':v>=2?'Low 😢':'Very Low 😞';
              return ` ${ctx.parsed.y} — ${label}`;
            }
          }
        }
      },
      scales: {
        x: {
          grid: { color: 'rgba(255,255,255,0.04)' },
          ticks: { color: 'rgba(240,236,255,0.4)', font: { family: 'Fira Code', size: 10 } }
        },
        y: {
          min: 0, max: 10,
          grid: { color: 'rgba(255,255,255,0.04)' },
          ticks: { color: 'rgba(240,236,255,0.4)', font: { family: 'Fira Code', size: 10 }, stepSize: 2 }
        }
      }
    }
  });
}

// ── Donut Chart ───────────────────────────────────────────────────────────────
function renderDonutChart(data) {
  if (charts.donut) { charts.donut.destroy(); }

  const dist = data.distribution || [];

  if (dist.length === 0) {
    document.getElementById('donutChart').parentElement.innerHTML +=
      '<div class="empty-state">No mood data yet.</div>';
    return;
  }

  const ctx = document.getElementById('donutChart').getContext('2d');
  charts.donut = new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: dist.map(d => `${EMOJIS[d.mood]||'🎵'} ${d.mood}`),
      datasets: [{
        data: dist.map(d => d.total),
        backgroundColor: dist.map(d => MOOD_COLORS[d.mood] || '#888'),
        borderColor: '#03010a',
        borderWidth: 3,
        hoverOffset: 8,
      }]
    },
    options: {
      responsive: true,
      cutout: '62%',
      plugins: {
        legend: {
          position: 'bottom',
          labels: {
            color: 'rgba(240,236,255,0.6)',
            font: { family: 'Cabinet Grotesk', size: 12 },
            padding: 12,
            boxWidth: 12,
            boxHeight: 12,
          }
        },
        tooltip: {
          backgroundColor: 'rgba(3,1,10,0.9)',
          titleColor: '#f0ecff',
          bodyColor: 'rgba(240,236,255,0.7)',
          borderColor: 'rgba(123,47,255,0.4)',
          borderWidth: 1,
          callbacks: {
            label: ctx => {
              const total = ctx.dataset.data.reduce((a,b)=>a+b,0);
              const pct   = Math.round(ctx.parsed / total * 100);
              return ` ${ctx.parsed} logs (${pct}%)`;
            }
          }
        }
      }
    }
  });
}

// ── Timeline ──────────────────────────────────────────────────────────────────
function renderTimeline(data) {
  const container = document.getElementById('timelineList');
  const recent    = data.recent || [];

  if (recent.length === 0) {
    container.innerHTML = '<div class="empty-state">No history yet.<br>Start logging your moods!</div>';
    return;
  }

  container.innerHTML = recent.map(r => {
    const emoji = EMOJIS[r.mood] || '🎵';
    const date  = new Date(r.detected_at).toLocaleDateString('en-GB',{
      day:'numeric', month:'short', hour:'2-digit', minute:'2-digit'
    });
    const method = r.method ? r.method.replace('_',' ') : '';
    return `
      <div class="tl-item">
        <div class="tl-emoji">${emoji}</div>
        <div>
          <div class="tl-mood">${r.mood}</div>
          <div class="tl-date">${date}</div>
        </div>
        ${method ? `<div class="tl-method">${method}</div>` : ''}
      </div>`;
  }).join('');
}

// ── Liked Songs ───────────────────────────────────────────────────────────────
function renderLiked(data) {
  const container = document.getElementById('likedList');
  const liked     = data.liked_songs || [];

  if (liked.length === 0) {
    container.innerHTML = '<div class="empty-state">No liked songs yet.<br>Heart a track on the dashboard!</div>';
    return;
  }

  container.innerHTML = liked.map(s => {
    const art = s.album_art
      ? `<img class="liked-art" src="${s.album_art}" alt="" onerror="this.style.display='none'">`
      : `<div class="liked-art-placeholder">🎵</div>`;
    const moodTag = s.mood ? `<div class="liked-mood-tag">${EMOJIS[s.mood]||''} ${s.mood}</div>` : '';
    return `
      <a class="liked-item" href="${s.spotify_url}" target="_blank" rel="noopener">
        ${art}
        <div style="min-width:0">
          <div class="liked-track">${s.track_name}</div>
          <div class="liked-artist">${s.artist}</div>
        </div>
        ${moodTag}
      </a>`;
  }).join('');
}

// ── Init ──────────────────────────────────────────────────────────────────────
loadAnalytics(7);
</script>
</body>
</html>