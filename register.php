<?php
include "db.php";
$message="";$success=false;
if($_SERVER["REQUEST_METHOD"]=="POST"){
  $name=$_POST['name'];$email=$_POST['email'];$password=password_hash($_POST['password'],PASSWORD_DEFAULT);
  $check=$conn->prepare("SELECT id FROM users WHERE email=?");$check->bind_param("s",$email);$check->execute();$result=$check->get_result();
  if($result->num_rows>0){$message="This email is already registered.";}
  else{$stmt=$conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");$stmt->bind_param("sss",$name,$email,$password);if($stmt->execute()){$success=true;$message="Account created! You can now sign in."}else{$message="Something went wrong. Please try again."}}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>EMORA — Create Account</title>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,100..900;1,9..144,100..900&family=Cabinet+Grotesk:wght@300;400;500;700;800&family=Fira+Code:wght@300;400&display=swap" rel="stylesheet">
<style>
:root{--void:#03010a;--neo1:#7b2fff;--neo2:#ff2fa0;--neo3:#00e5ff;--neo4:#39ff7a;--text:#f0ecff;--muted:rgba(240,236,255,0.38);--rim:rgba(255,255,255,0.07)}
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box}
html,body{min-height:100%;background:var(--void);font-family:'Cabinet Grotesk',sans-serif;color:var(--text)}
#bg{position:fixed;inset:0;z-index:0}
body::after{content:'';position:fixed;inset:0;background-image:url("data:image/svg+xml,%3Csvg viewBox='0 0 512 512' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.85' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.045'/%3E%3C/svg%3E");pointer-events:none;z-index:1;mix-blend-mode:overlay}
.page{position:relative;z-index:2;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:40px 20px}
.card{width:100%;max-width:460px;animation:rise .7s cubic-bezier(.16,1,.3,1) both}
@keyframes rise{from{opacity:0;transform:translateY(30px)}to{opacity:1;transform:translateY(0)}}
.back-link{display:flex;align-items:center;gap:8px;font-family:'Fira Code',monospace;font-size:11px;letter-spacing:.12em;text-transform:uppercase;color:var(--muted);text-decoration:none;margin-bottom:36px;transition:color .2s}
.back-link:hover{color:var(--neo3)}
.back-link::before{content:'←'}
.top-brand{display:flex;align-items:center;gap:10px;margin-bottom:12px}
.brand-dot{width:32px;height:32px;border-radius:9px;border:1.5px solid var(--neo1);display:flex;align-items:center;justify-content:center;position:relative}
.brand-dot::before{content:'';position:absolute;inset:3px;border-radius:6px;background:linear-gradient(135deg,var(--neo1),var(--neo2));opacity:.3}
.brand-dot svg{width:16px;height:16px;fill:var(--neo1);position:relative;z-index:1}
.brand-name{font-family:'Fraunces',serif;font-size:20px;font-weight:800;background:linear-gradient(90deg,var(--text),rgba(240,236,255,.6));-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.form-title{font-family:'Fraunces',serif;font-size:34px;font-weight:300;letter-spacing:-.02em;margin-bottom:6px}
.form-title em{font-style:italic;background:linear-gradient(90deg,var(--neo1),var(--neo3));-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.form-sub{font-size:14px;color:var(--muted);margin-bottom:36px;line-height:1.5}
/* Glass form box */
.form-box{background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.07);border-radius:20px;padding:36px;backdrop-filter:blur(16px);box-shadow:0 40px 100px rgba(0,0,0,.6),inset 0 1px 0 rgba(255,255,255,.06)}
.field-group{margin-bottom:20px}
.field-label{display:block;font-family:'Fira Code',monospace;font-size:10px;letter-spacing:.18em;text-transform:uppercase;color:var(--muted);margin-bottom:9px}
.field-input{width:100%;padding:14px 18px;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.09);border-radius:12px;color:var(--text);font-family:'Cabinet Grotesk',sans-serif;font-size:15px;outline:none;transition:all .28s}
.field-input::placeholder{color:rgba(240,236,255,.2)}
.field-input:focus{border-color:var(--neo1);background:rgba(123,47,255,.06);box-shadow:0 0 0 3px rgba(123,47,255,.12)}
.strength-track{height:3px;border-radius:3px;background:rgba(255,255,255,.08);overflow:hidden;margin-top:7px}
.strength-fill{height:100%;width:0%;border-radius:3px;transition:width .35s,background .35s}
.msg{display:flex;align-items:flex-start;gap:10px;padding:13px 16px;border-radius:11px;font-size:13px;line-height:1.5;margin-bottom:20px;animation:shake .4s ease}
.msg.error{background:rgba(255,47,160,.08);border:1px solid rgba(255,47,160,.25);color:#ff85c8}
.msg.success{background:rgba(57,255,122,.08);border:1px solid rgba(57,255,122,.25);color:#7fffaa}
@keyframes shake{0%,100%{transform:translateX(0)}20%{transform:translateX(-5px)}60%{transform:translateX(5px)}}
.btn-create{width:100%;padding:15px;background:linear-gradient(135deg,var(--neo1),var(--neo2));border:none;border-radius:12px;color:#fff;font-family:'Cabinet Grotesk',sans-serif;font-size:15px;font-weight:700;cursor:pointer;transition:all .3s;margin-top:6px;position:relative;overflow:hidden}
.btn-create::before{content:'';position:absolute;inset:0;background:linear-gradient(135deg,rgba(255,255,255,.15),transparent);opacity:0;transition:opacity .3s}
.btn-create:hover{transform:translateY(-2px);box-shadow:0 12px 40px rgba(123,47,255,.5)}
.btn-create:hover::before{opacity:1}
.login-link{text-align:center;font-size:13px;color:var(--muted);margin-top:22px}
.login-link a{color:var(--neo3);text-decoration:none;font-weight:700;transition:color .2s}
.login-link a:hover{color:#fff;text-shadow:0 0 12px var(--neo3)}
/* Floating orbs mini */
.orb{position:fixed;border-radius:50%;filter:blur(120px);pointer-events:none;z-index:0;animation:drift 15s ease-in-out infinite alternate}
.orb1{width:500px;height:500px;background:#7b2fff;opacity:.18;top:-100px;right:-150px}
.orb2{width:400px;height:400px;background:#ff2fa0;opacity:.14;bottom:-80px;left:-100px;animation-delay:-6s}
.orb3{width:300px;height:300px;background:#00e5ff;opacity:.1;top:40%;left:30%;animation-delay:-10s}
@keyframes drift{0%{transform:translate(0,0) scale(1)}100%{transform:translate(35px,25px) scale(1.07)}}
</style>
</head>
<body>
<div class="orb orb1"></div>
<div class="orb orb2"></div>
<div class="orb orb3"></div>
<div class="page">
  <div class="card">
    <a class="back-link" href="index.php"> Back to sign in</a>
    <div class="top-brand">
      <div class="brand-dot"><svg viewBox="0 0 24 24"><path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z"/></svg></div>
      <span class="brand-name">EMORA</span>
    </div>
    <div class="form-title">Join the <em>experience.</em></div>
    <div class="form-sub">Create your free account and let music find you</div>
    <div class="form-box">
      <?php if($message): ?>
        <div class="msg <?php echo $success?'success':'error'; ?>">
          <?php echo $success?'✓':'⚠'; ?> <?php echo htmlspecialchars($message); ?>
          <?php if($success): ?> <a href="index.php" style="color:inherit;font-weight:700;margin-left:4px">Sign in →</a><?php endif; ?>
        </div>
      <?php endif; ?>
      <form method="POST" autocomplete="on">
        <div class="field-group"><label class="field-label">Your Name</label><input class="field-input" type="text" name="name" placeholder="Alex Rivera" required autocomplete="name"></div>
        <div class="field-group"><label class="field-label">Email</label><input class="field-input" type="email" name="email" placeholder="you@example.com" required autocomplete="email"></div>
        <div class="field-group">
          <label class="field-label">Password</label>
          <input class="field-input" type="password" name="password" id="pwd" placeholder="Create a strong password" required oninput="strength(this.value)">
          <div class="strength-track"><div class="strength-fill" id="sf"></div></div>
        </div>
        <button type="submit" class="btn-create">Create Account →</button>
      </form>
    </div>
    <div class="login-link">Already have an account? <a href="index.php">Sign in</a></div>
  </div>
</div>
<script>
function strength(v){
  const f=document.getElementById('sf');let s=0;
  if(v.length>=8)s++;if(/[A-Z]/.test(v))s++;if(/[0-9]/.test(v))s++;if(/[^A-Za-z0-9]/.test(v))s++;
  const w=['0%','25%','50%','75%','100%'],c=['transparent','#ef4444','#f97316','#eab308','#39ff7a'];
  f.style.width=w[s];f.style.background=c[s];
}
</script>
</body>
</html>