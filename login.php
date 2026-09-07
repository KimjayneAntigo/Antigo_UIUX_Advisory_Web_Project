<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Antigo — Log In</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
  :root{
    --navy:#13224B;--violet:#6C5BB5;--blue:#4C6CCB;--white:#FFFFFF;
    --light-blue:#DDEBFF;--light-gray:#F4F6F8;--dark-gray:#4b4b4b;
    --bg-alt:#F4F6F8;--text:#13224B;--text-soft:#4b4b4b;--text-faint:#8890AA;
    --border:rgba(19,34,75,.1);
    --grad:linear-gradient(100deg,var(--navy) 0%,var(--violet) 55%,var(--blue) 100%);
    --grad-soft:linear-gradient(135deg,var(--blue),var(--violet));
    --shadow-md:0 20px 40px -20px rgba(19,34,75,.25);
    --radius-md:18px;--radius-lg:28px;
  }
  *{margin:0;padding:0;box-sizing:border-box;}
  body{font-family:'Poppins',sans-serif;color:var(--text);}
  a{color:inherit;text-decoration:none;}
  .screen{display:grid;grid-template-columns:1fr 1fr;min-height:100vh;}

  .brand-panel{
    background:var(--grad);position:relative;overflow:hidden;
    display:flex;flex-direction:column;justify-content:space-between;
    padding:56px;color:#fff;
  }
  .brand-panel .blob{position:absolute;border-radius:50%;filter:blur(70px);opacity:.35;}
  .brand-panel .b1{width:420px;height:420px;background:#fff;top:-160px;left:-140px;}
  .brand-panel .b2{width:340px;height:340px;background:#4C6CCB;bottom:-120px;right:-100px;}
  .bp-top{position:relative;z-index:2;display:flex;align-items:center;gap:12px;}
  .bp-top .mark{width:38px;height:38px;}
  .bp-top .word{font-weight:800;font-size:16px;letter-spacing:.1em;}
  .bp-top .sub{font-size:8px;letter-spacing:.18em;opacity:.75;text-transform:uppercase;font-weight:600;}
  .bp-mid{position:relative;z-index:2;max-width:420px;}
  .bp-mid h1{font-size:34px;line-height:1.25;font-weight:700;margin-bottom:16px;}
  .bp-mid p{font-size:14.5px;color:rgba(255,255,255,.8);line-height:1.7;}
  .bp-quote{position:relative;z-index:2;border-top:1px solid rgba(255,255,255,.2);padding-top:22px;font-size:13px;color:rgba(255,255,255,.75);}

  .form-panel{display:flex;align-items:center;justify-content:center;padding:40px;background:#fff;}
  .form-card{width:100%;max-width:380px;}
  .back-home{font-size:12.5px;color:var(--text-faint);display:inline-flex;align-items:center;gap:6px;margin-bottom:34px;}
  .form-card h2{font-size:26px;margin-bottom:8px;}
  .form-card>p.lead{font-size:13.5px;color:var(--text-soft);margin-bottom:28px;}

  .role-toggle{display:flex;background:var(--bg-alt);border-radius:999px;padding:4px;margin-bottom:28px;}
  .role-toggle button{flex:1;border:none;background:transparent;padding:10px;border-radius:999px;font-size:13px;font-weight:600;color:var(--text-faint);cursor:pointer;font-family:inherit;transition:all .2s ease;}
  .role-toggle button.active{background:#fff;color:var(--navy);box-shadow:0 4px 12px -4px rgba(19,34,75,.2);}

  .field{margin-bottom:18px;}
  .field label{display:block;font-size:12.5px;font-weight:600;color:var(--text-soft);margin-bottom:8px;}
  .field input{
    width:100%;padding:13px 16px;border-radius:12px;border:1px solid var(--border);
    background:var(--bg-alt);font-family:inherit;font-size:14px;color:var(--text);
  }
  .field input:focus{outline:none;border-color:var(--violet);background:#fff;}
  .row-between{display:flex;justify-content:space-between;align-items:center;margin-bottom:26px;font-size:12.5px;}
  .remember{display:flex;align-items:center;gap:8px;color:var(--text-soft);}
  .row-between a{color:var(--violet);font-weight:600;}

  .btn{display:inline-flex;align-items:center;justify-content:center;gap:9px;padding:14px 28px;border-radius:999px;font-weight:600;font-size:14.5px;cursor:pointer;border:1.5px solid transparent;width:100%;font-family:inherit;}
  .btn-primary{background:var(--grad);color:#fff;box-shadow:0 14px 26px -12px rgba(76,108,203,.55);}

  .divider{display:flex;align-items:center;gap:14px;margin:26px 0;color:var(--text-faint);font-size:12px;}
  .divider::before,.divider::after{content:"";flex:1;height:1px;background:var(--border);}

  .signup-note{text-align:center;font-size:13px;color:var(--text-soft);}
  .signup-note a{color:var(--violet);font-weight:600;}

  @media(max-width:860px){.screen{grid-template-columns:1fr;}.brand-panel{display:none;}}
</style>
</head>
<body>
<div class="screen">
  <div class="brand-panel">
    <div class="blob b1"></div><div class="blob b2"></div>
    <div class="bp-top">
       <div class="w-9 h-9 rounded-xl flex items-center justify-center font-extrabold text-white text-sm shadow-md" style="background:var(--grad);">
                    AJ
                </div>
                  <div class="flex flex-col leading-tight">
                    <span class="font-extrabold tracking-wide uppercase text-sm text-[#13224B]">Antigo</span>
                    <span class="text-[9px] text-[#6C5BB5] font-bold tracking-[0.22em] uppercase">UI/UX Advisory</span>
                </div>
      <!-- <div><div class="word">ANTIGO</div><div class="sub">UI / UX Advisory</div></div> -->
    </div>
    <div class="bp-mid">
      <h1>Designing experience. Driving impact.</h1>
      <p>Log in to track your project's progress, share files, and message your designer — all in one place.</p>
    </div>
    <div class="bp-quote">"The client portal made the whole process feel effortless — I always knew where things stood." — Maria Santos, Pesolink</div>
  </div>

  <div class="form-panel">
    <div class="form-card">
      <a href="00-home.html" class="back-home">← Back to website</a>
      <div class="role-toggle">
        <button class="active" onclick="setRole(this,'client')">Client Login</button>
        <button onclick="setRole(this,'admin')">Admin Login</button>
      </div>
      <h2 id="heading">Welcome back</h2>
      <p class="lead" id="subheading">Log in to view your project status, files, and messages.</p>
<!-- placeholder="you@example.com" -->
 <!-- placeholder="••••••••" -->
      <form onsubmit="event.preventDefault();">
        <div class="field"><label for="email">Email address</label><input id="email" type="email" ></div>
        <div class="field"><label for="password">Password</label><input id="password" type="password" ></div>
        <div class="row-between">
          <label class="remember"><input type="checkbox"> Remember me</label>
          <a href="#">Forgot password?</a>
        </div>
        <button type="submit" class="btn btn-primary">Sign In</button>
      </form>

      <div class="divider">or</div>
      <p class="signup-note" id="footNote">New client? <a href="03-inquiry.html">Get started with an inquiry</a></p>
    </div>
  </div>
</div>
<script>
  function setRole(btn, role){
    document.querySelectorAll('.role-toggle button').forEach(b=>b.classList.remove('active'));
    btn.classList.add('active');
    const heading=document.getElementById('heading');
    const sub=document.getElementById('subheading');
    const foot=document.getElementById('footNote');
    if(role==='admin'){
      heading.textContent='Admin sign in';
      sub.textContent='Access the studio dashboard to manage inquiries, bookings, and active projects.';
      foot.innerHTML='Studio access only · <a href="#">Contact support</a>';
    } else {
      heading.textContent='Welcome back';
      sub.textContent="Log in to view your project status, files, and messages.";
      foot.innerHTML='New client? <a href="03-inquiry.html">Get started with an inquiry</a>';
    }
  }
</script>
</body>
</html>
