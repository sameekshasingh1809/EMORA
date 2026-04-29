<?php
session_start();
if (isset($_SESSION['user'])) { header("Location: dashboard.php"); exit(); }
$loginError = $_SESSION['login_error'] ?? '';
unset($_SESSION['login_error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>EMORA — Sign In</title>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,100..900;1,9..144,100..900&family=Cabinet+Grotesk:wght@300;400;500;700;800&family=Fira+Code:wght@300;400&display=swap" rel="stylesheet">
<style>
:root{--void:#03010a;--neo1:#7b2fff;--neo2:#ff2fa0;--neo3:#00e5ff;--neo4:#39ff7a;--glass:rgba(255,255,255,0.03);--rim:rgba(255,255,255,0.07);--text:#f0ecff;--muted:rgba(240,236,255,0.38);--ink:rgba(240,236,255,0.65)}
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box}
html,body{height:100%;background:var(--void);font-family:'Cabinet Grotesk',sans-serif;color:var(--text);overflow:hidden}
#bg{position:fixed;inset:0;z-index:0}
body::after{content:'';position:fixed;inset:0;background-image:url("data:image/svg+xml,%3Csvg viewBox='0 0 512 512' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.85' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.045'/%3E%3C/svg%3E");pointer-events:none;z-index:1;mix-blend-mode:overlay}
.stage{position:relative;z-index:2;display:grid;grid-template-columns:1fr 1fr;height:100vh}
.brand-side{display:flex;flex-direction:column;justify-content:center;padding:60px 64px;position:relative;overflow:hidden}
.brand-side::after{content:'';position:absolute;top:0;right:0;width:1px;height:100%;background:linear-gradient(180deg,transparent,var(--neo1) 30%,var(--neo2) 70%,transparent);opacity:0.4}
.wordmark{display:flex;align-items:center;gap:14px;margin-bottom:64px}
.wordmark-glyph{width:44px;height:44px;border:1.5px solid var(--neo1);border-radius:12px;display:flex;align-items:center;justify-content:center;position:relative;animation:glyphPulse 4s ease-in-out infinite}
.wordmark-glyph::before{content:'';position:absolute;inset:3px;border-radius:8px;background:linear-gradient(135deg,var(--neo1),var(--neo2));opacity:0.25}
.wordmark-glyph svg{width:22px;height:22px;fill:var(--neo1);position:relative;z-index:1}
@keyframes glyphPulse{0%,100%{box-shadow:0 0 0 0 rgba(123,47,255,0);border-color:var(--neo1)}50%{box-shadow:0 0 20px 4px rgba(123,47,255,0.35);border-color:var(--neo3)}}
.wordmark-name{font-family:'Fraunces',serif;font-size:26px;font-weight:800;letter-spacing:-0.02em;background:linear-gradient(90deg,var(--text),rgba(240,236,255,0.6));-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.hero-eyebrow{font-family:'Fira Code',monospace;font-size:11px;color:var(--neo3);letter-spacing:0.2em;text-transform:uppercase;margin-bottom:22px;display:flex;align-items:center;gap:10px}
.hero-eyebrow::before{content:'';width:32px;height:1px;background:var(--neo3)}
.hero-headline{font-family:'Fraunces',serif;font-size:clamp(44px,4.5vw,64px);font-weight:300;line-height:1.05;letter-spacing:-0.03em;margin-bottom:22px}
.hero-headline .hl{font-style:italic;background:linear-gradient(90deg,var(--neo1),var(--neo2));-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.hero-sub{font-size:15px;color:var(--ink);line-height:1.65;max-width:360px}
.freq-viz{display:flex;align-items:flex-end;gap:4px;height:60px;margin-top:48px}
.freq-bar{width:3px;border-radius:2px;animation:freqAnim var(--dur,1.2s) ease-in-out infinite alternate}
@keyframes freqAnim{from{height:var(--min,4px);opacity:0.3}to{height:var(--max,40px);opacity:1}}
.mood-ticker{display:flex;gap:8px;flex-wrap:wrap;margin-top:32px}
.mood-pill{padding:5px 14px;border-radius:50px;border:1px solid var(--rim);font-size:12px;color:var(--muted);background:var(--glass);backdrop-filter:blur(8px);white-space:nowrap;animation:pillFloat var(--d,3s) ease-in-out infinite alternate}
@keyframes pillFloat{from{transform:translateY(0);opacity:0.4}to{transform:translateY(-5px);opacity:0.85}}
.auth-side{display:flex;align-items:center;justify-content:center;padding:60px 64px;position:relative}
.auth-card{width:100%;max-width:400px;animation:authReveal 0.8s cubic-bezier(0.16,1,0.3,1) both}
@keyframes authReveal{from{opacity:0;transform:translateY(28px) scale(0.97)}to{opacity:1;transform:translateY(0) scale(1)}}
.auth-title{font-family:'Fraunces',serif;font-size:32px;font-weight:400;letter-spacing:-0.02em;margin-bottom:6px}
.auth-sub{font-size:14px;color:var(--muted);margin-bottom:40px;line-height:1.5}
.field-group{margin-bottom:20px;position:relative}
.field-label{display:block;font-family:'Fira Code',monospace;font-size:10px;letter-spacing:0.18em;text-transform:uppercase;color:var(--muted);margin-bottom:9px}
.field-input{width:100%;padding:14px 18px;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.09);border-radius:12px;color:var(--text);font-family:'Cabinet Grotesk',sans-serif;font-size:15px;outline:none;transition:all 0.28s}
.field-input::placeholder{color:rgba(240,236,255,0.2)}
.field-input:focus{border-color:var(--neo1);background:rgba(123,47,255,0.06);box-shadow:0 0 0 3px rgba(123,47,255,0.12),0 0 30px rgba(123,47,255,0.08)}
.error-strip{display:flex;align-items:center;gap:10px;padding:12px 16px;background:rgba(255,47,160,0.08);border:1px solid rgba(255,47,160,0.25);border-radius:10px;font-size:13px;color:#ff85c8;margin-bottom:22px;animation:shake 0.4s ease}
@keyframes shake{0%,100%{transform:translateX(0)}20%{transform:translateX(-6px)}60%{transform:translateX(6px)}}
.error-strip::before{content:'⚠';font-size:15px}
.btn-signin{width:100%;padding:15px;background:linear-gradient(135deg,var(--neo1) 0%,var(--neo2) 100%);border:none;border-radius:12px;color:#fff;font-family:'Cabinet Grotesk',sans-serif;font-size:15px;font-weight:700;letter-spacing:0.02em;cursor:pointer;position:relative;overflow:hidden;transition:all 0.3s;margin-top:6px}
.btn-signin::before{content:'';position:absolute;inset:0;background:linear-gradient(135deg,rgba(255,255,255,0.15),transparent);opacity:0;transition:opacity 0.3s}
.btn-signin:hover{transform:translateY(-2px);box-shadow:0 12px 40px rgba(123,47,255,0.5)}
.btn-signin:hover::before{opacity:1}
.divider{display:flex;align-items:center;gap:14px;margin:26px 0;font-size:11px;letter-spacing:0.1em;color:var(--muted);text-transform:uppercase}
.divider::before,.divider::after{content:'';flex:1;height:1px;background:linear-gradient(90deg,transparent,rgba(255,255,255,0.1),transparent)}
.register-prompt{text-align:center;font-size:13px;color:var(--muted)}
.register-prompt a{color:var(--neo3);text-decoration:none;font-weight:700;transition:color 0.2s}
.register-prompt a:hover{color:#fff;text-shadow:0 0 12px var(--neo3)}
.corner{position:absolute;width:20px;height:20px;pointer-events:none}
.corner-tl{top:24px;left:24px;border-top:1.5px solid var(--neo1);border-left:1.5px solid var(--neo1);border-radius:3px 0 0 0}
.corner-tr{top:24px;right:24px;border-top:1.5px solid var(--neo1);border-right:1.5px solid var(--neo1);border-radius:0 3px 0 0}
.corner-bl{bottom:24px;left:24px;border-bottom:1.5px solid var(--neo2);border-left:1.5px solid var(--neo2);border-radius:0 0 0 3px}
.corner-br{bottom:24px;right:24px;border-bottom:1.5px solid var(--neo2);border-right:1.5px solid var(--neo2);border-radius:0 0 3px 0}
@media(max-width:700px){.stage{grid-template-columns:1fr}.brand-side{display:none}.auth-side{padding:40px 28px}}
</style>
</head>
<body>
<canvas id="bg"></canvas>
<div class="stage">
  <div class="brand-side">
    <div class="corner corner-tl"></div>
    <div class="corner corner-bl"></div>
    <div class="wordmark">
      <div class="wordmark-glyph"><svg viewBox="0 0 24 24"><path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z"/></svg></div>
      <span class="wordmark-name">EMORA</span>
    </div>
    <div class="hero-eyebrow">Emotion-Aware Music</div>
    <h1 class="hero-headline">Your feelings deserve<br>the <span class="hl">right soundtrack.</span></h1>
    <p class="hero-sub">EMORA reads your mood through text, voice, or face — then curates the perfect playlist from millions of tracks.</p>
    <div class="freq-viz" id="freqViz"></div>
    <div class="mood-ticker">
      <?php
      $moods=['😄 happy','😢 sad','⚡ energetic','😌 chill','💖 romantic','🎯 focus','🌙 nostalgic','💪 confident','😰 anxious','🌅 hopeful'];
      $d=[0,0.4,0.8,1.2,0.2,0.6,1.0,0.3,0.7,0.5];
      foreach($moods as $i=>$m) echo "<div class='mood-pill' style='--d:".( 2.5+$d[$i])."s;animation-delay:".$d[$i]."s'>$m</div>";
      ?>
    </div>
  </div>
  <div class="auth-side">
    <div class="corner corner-tr"></div>
    <div class="corner corner-br"></div>
    <div class="auth-card">
      <div class="auth-title">Welcome back</div>
      <div class="auth-sub">Sign in to discover music tuned to your emotions</div>
      <?php if($loginError): ?><div class="error-strip"><?php echo htmlspecialchars($loginError); ?></div><?php endif; ?>
      <form method="POST" action="login_process.php" autocomplete="on">
        <div class="field-group"><label class="field-label">Email</label><input class="field-input" type="email" name="email" placeholder="you@example.com" required autocomplete="email"></div>
        <div class="field-group"><label class="field-label">Password</label><input class="field-input" type="password" name="password" placeholder="••••••••••" required autocomplete="current-password"></div>
        <button type="submit" class="btn-signin">Sign In →</button>
      </form>
      <div class="divider">or</div>
      <div class="register-prompt">New here? <a href="register.php">Create your account</a></div>
    </div>
  </div>
</div>
<script>
const canvas=document.getElementById('bg'),ctx=canvas.getContext('2d');
function resize(){canvas.width=window.innerWidth;canvas.height=window.innerHeight}
resize();window.addEventListener('resize',resize);
const orbs=[{x:.25,y:.3,r:.38,c:'#7b2fff',vx:.00015,vy:.0001},{x:.75,y:.6,r:.32,c:'#ff2fa0',vx:-.0001,vy:.00012},{x:.5,y:.8,r:.25,c:'#00e5ff',vx:.00012,vy:-.00008},{x:.1,y:.7,r:.2,c:'#39ff7a',vx:.00008,vy:.00015}];
let t=0;
function draw(){
  t++;const W=canvas.width,H=canvas.height;ctx.clearRect(0,0,W,H);
  const bg=ctx.createLinearGradient(0,0,W,H);bg.addColorStop(0,'#03010a');bg.addColorStop(1,'#07020f');ctx.fillStyle=bg;ctx.fillRect(0,0,W,H);
  for(const o of orbs){
    o.x+=o.vx*Math.sin(t*.008+o.r);o.y+=o.vy*Math.cos(t*.006+o.r);
    if(o.x<.05||o.x>.95)o.vx*=-1;if(o.y<.05||o.y>.95)o.vy*=-1;
    const gx=o.x*W,gy=o.y*H,gr=Math.min(W,H)*o.r;
    const rad=ctx.createRadialGradient(gx,gy,0,gx,gy,gr);
    rad.addColorStop(0,o.c+'55');rad.addColorStop(.5,o.c+'18');rad.addColorStop(1,'transparent');
    ctx.fillStyle=rad;ctx.beginPath();ctx.arc(gx,gy,gr,0,Math.PI*2);ctx.fill();
  }
  requestAnimationFrame(draw);
}
draw();
const viz=document.getElementById('freqViz');
for(let i=0;i<36;i++){
  const el=document.createElement('div');el.className='freq-bar';
  const mn=3+Math.random()*8,mx=16+Math.random()*44,dur=.7+Math.random()*1.1;
  const hue=270+(i/36)*100;
  el.style.cssText=`--min:${mn}px;--max:${mx}px;--dur:${dur}s;animation-delay:${Math.random()*1.5}s;height:${mn}px;background:linear-gradient(180deg,hsl(${hue},90%,65%),hsl(${hue+30},90%,55%))`;
  viz.appendChild(el);
}
</script>
</body>
</html>