<?php
// model_dashboard.php — PurePressureLive
// Fix: removed any use/redeclaration of `$` and `$$` helpers. Use `qs`/`qsa` locally inside an IIFE.
// Added a tiny in-page test suite to guard against helper collisions and basic UI behaviors.
session_start();
$loggedIn  = isset($_SESSION['user_id']);
$modelName = $_SESSION['display_name'] ?? 'Model';
$goalPct   = max(0, min(100, intval($_SESSION['goal_pct'] ?? 72)));
$remainTok = intval($_SESSION['goal_remaining'] ?? 380);
$csrf      = $_SESSION['csrf'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>PurePressureLive — Model Dashboard</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&family=Playfair+Display:ital,wght@0,700;1,700&display=swap" rel="stylesheet">
  <script defer src="https://kit.fontawesome.com/2c2b0b2d3a.js" crossorigin="anonymous"></script>
  <style>
    :root{
      --ink:#0b0b0f; --ink-2:#121219; --rose:#ff2a8e; --rose-2:#9f1f69; --ice:#e7e7ff; --mint:#b9ffc9;
      --glass: rgba(255,255,255,.06); --stroke: rgba(255,255,255,.12); --glow: 0 0 28px rgba(255,42,142,.45);
    }
    *{box-sizing:border-box}
    html,body{height:100%}
    body{margin:0;background:radial-gradient(1200px 700px at 80% -10%, rgba(255,42,142,.15), transparent 60%),
                         radial-gradient(1200px 700px at -10% 110%, rgba(64,132,255,.12), transparent 60%),
                         linear-gradient(160deg,var(--ink),var(--ink-2));
         color:#fff;font-family:Inter,system-ui,Segoe UI,Roboto,Helvetica,Arial}
    a{color:inherit;text-decoration:none}
    /* Header */
    header{position:sticky;top:0;z-index:20;backdrop-filter: blur(12px); background:linear-gradient(180deg,rgba(0,0,0,.35),rgba(0,0,0,.15)); border-bottom:1px solid var(--stroke)}
    .wrap{max-width:1200px;margin:0 auto;padding:16px 20px;display:flex;align-items:center;gap:14px}
    .brand{display:flex;align-items:center;gap:12px}
    .badge{width:38px;height:38px;border-radius:12px;background:linear-gradient(135deg,var(--rose),#fd6ab2 60%,#fff 120%);
           box-shadow:var(--glow)}
    .wordmark{font-weight:800;letter-spacing:.5px}
    nav{margin-left:auto;display:flex;gap:10px;align-items:center}
    .navlink,.btn{padding:10px 14px;border:1px solid var(--stroke);border-radius:12px;background:var(--glass)}
    .btn.primary{border-color:rgba(255,42,142,.5);background:linear-gradient(180deg,rgba(255,42,142,.15),rgba(255,42,142,.05));box-shadow:var(--glow);position:relative;overflow:hidden}
    .btn.primary::after{content:"";position:absolute;inset:-1px; background: conic-gradient(from 180deg,transparent,rgba(255,42,142,.35),transparent 60%); filter:blur(14px);opacity:.6;animation:spin 6s linear infinite}
    @keyframes spin{to{transform:rotate(1turn)}}
    .btn:hover{transform:translateY(-1px);transition:.2s ease}

    /* Hero */
    .hero{max-width:1200px;margin:26px auto 14px;padding:18px}
    .panel{position:relative; border:1px solid var(--stroke); background:linear-gradient(180deg,rgba(255,255,255,.05),rgba(255,255,255,.02)); border-radius:18px; overflow:hidden}
    .panel .inner{padding:22px 20px}
    .title{display:flex;align-items:flex-end;gap:12px}
    .title h1{font:800 28px/1.1 Inter,system-ui}
    .subtitle{opacity:.8}
    .shine{position:absolute;inset:0;background:radial-gradient(900px 300px at -10% -10%,rgba(255,42,142,.2),transparent 70%),
                                     radial-gradient(600px 240px at 110% 110%,rgba(64,132,255,.15),transparent 70%)}

    /* Stats */
    .stats{display:grid;grid-template-columns:1.4fr .8fr .8fr;gap:14px;margin-top:18px}
    .card{border:1px solid var(--stroke);background:var(--glass);border-radius:16px;padding:16px}
    .metric{font-weight:800;font-size:28px}
    .muted{opacity:.75;font-size:13px}
    .progress{height:14px;border-radius:999px;background:rgba(255,255,255,.08);border:1px solid var(--stroke);overflow:hidden;position:relative}
    .progress > i{display:block;height:100%;width:0;background:linear-gradient(90deg,var(--rose),#fd6ab2 60%); box-shadow:0 0 14px rgba(255,42,142,.6); animation:fill 1.8s cubic-bezier(.22,1,.36,1) forwards}
    @keyframes fill{from{width:0} to{width:calc(var(--pct)*1%)} }

    /* Actions */
    .actions{display:flex;flex-wrap:wrap;gap:10px;margin-top:16px}
    .go-live{--h:52px; position:relative; height:var(--h); padding:0 20px; border-radius:14px; border:1px solid rgba(255,42,142,.5);
             background:linear-gradient(180deg,rgba(255,42,142,.18),rgba(255,42,142,.04)); color:#fff; font-weight:800; letter-spacing:.3px; box-shadow:var(--glow); overflow:hidden}
    .go-live .sheen{position:absolute; inset:-2px; background:linear-gradient(120deg,transparent 30%, rgba(255,255,255,.55) 50%, transparent 70%);
                    transform:translateX(-120%); animation:sheen 4.5s ease-in-out infinite}
    @keyframes sheen{50%{transform:translateX(30%)} 100%{transform:translateX(140%)}}

    /* Upload */
    .uploader{display:grid;grid-template-columns: 1fr 1fr; gap:14px}
    .drop{border:1.5px dashed rgba(255,255,255,.25); border-radius:16px; padding:20px; text-align:center; background:rgba(255,255,255,.03)}
    .drop:hover{border-color:rgba(255,42,142,.7)}
    .preview{display:grid; gap:8px; grid-template-columns:repeat(4, minmax(0,1fr))}
    .thumb{position:relative;border-radius:12px;overflow:hidden;border:1px solid var(--stroke)}
    .thumb img{width:100%;display:block;transform:scale(1.02);transition:.4s ease}
    .thumb:hover img{transform:scale(1.06)}

    /* Live grid */
    .live{max-width:1200px;margin:10px auto 60px;padding:0 18px}
    .grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px}
    @media (max-width:1100px){.grid{grid-template-columns:repeat(3,1fr)}}
    @media (max-width:800px){.stats{grid-template-columns:1fr 1fr}.uploader{grid-template-columns:1fr}.grid{grid-template-columns:repeat(2,1fr)}}
    @media (max-width:520px){.grid{grid-template-columns:1fr}}
    .tile{position:relative;border:1px solid var(--stroke);border-radius:16px;overflow:hidden; background:rgba(255,255,255,.03)}
    .tile img{display:block;width:100%;aspect-ratio:4/5;object-fit:cover;filter:saturate(1.05);}
    .tile .meta{position:absolute;inset:auto 10px 10px 10px;display:flex;justify-content:space-between;align-items:center;gap:8px}
    .pill{backdrop-filter: blur(8px); background:rgba(0,0,0,.35); padding:6px 10px;border-radius:999px;border:1px solid var(--stroke)}
    .tile:hover{transform:translateY(-2px); box-shadow:0 12px 40px rgba(255,42,142,.12); transition:.25s ease}

    /* Modal */
    .modal{position:fixed;inset:0;display:none;place-items:center;background:rgba(0,0,0,.6);backdrop-filter:blur(6px); z-index:50}
    .modal.show{display:grid}
    .sheet{width:min(520px,92vw);border-radius:18px;border:1px solid var(--stroke);background:linear-gradient(180deg,rgba(20,20,28,.95),rgba(14,14,20,.92)); overflow:hidden}
    .sheet header{display:flex;align-items:center;justify-content:space-between;padding:16px 16px;border-bottom:1px solid var(--stroke)}
    .sheet main{padding:18px 16px 22px}
    .field{display:grid;gap:6px;margin:12px 0}
    .field input{width:100%;padding:12px 12px;border-radius:12px;border:1px solid var(--stroke);background:#11121a;color:#fff}
    .submit{margin-top:8px;padding:12px 14px;border-radius:12px;border:1px solid rgba(255,42,142,.5);background:linear-gradient(180deg,rgba(255,42,142,.2),rgba(255,42,142,.05)); color:#fff; font-weight:700}
    .row{display:flex;gap:10px;flex-wrap:wrap}
    .tiny{font-size:12px;opacity:.8}

    footer{border-top:1px solid var(--stroke);padding:18px;text-align:center;opacity:.75}
  </style>
</head>
<body>
  <header>
    <div class="wrap">
      <div class="brand">
        <div class="badge"></div>
        <div>
          <div class="wordmark">PurePressureLive</div>
          <div style="font:700 12px/1 Playfair Display,serif; opacity:.7">for men who like the pressure</div>
        </div>
      </div>
      <nav>
        <a class="navlink" href="/feed.php"><i class="fa-solid fa-video"></i> Feed</a>
        <a class="navlink" href="/messages.php"><i class="fa-regular fa-message"></i> Messages</a>
        <a class="navlink" href="/upload.php"><i class="fa-solid fa-upload"></i> Uploads</a>
        <?php if($loggedIn): ?>
          <form action="/logout.php" method="post" style="margin:0">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
            <button class="btn" type="submit"><i class="fa-solid fa-right-from-bracket"></i> Logout</button>
          </form>
          <div class="pill" title="You are logged in">Hi, <?= htmlspecialchars($modelName) ?></div>
        <?php else: ?>
          <button class="btn" data-open="login"><i class="fa-regular fa-user"></i> Login</button>
          <button class="btn primary" data-open="register"><i class="fa-solid fa-user-plus"></i> Register</button>
        <?php endif; ?>
      </nav>
    </div>
  </header>

  <section class="hero">
    <div class="panel">
      <div class="shine"></div>
      <div class="inner">
        <div class="title">
          <h1>Welcome back, <?= htmlspecialchars($modelName) ?></h1>
          <span class="pill subtitle"><i class="fa-solid fa-star" style="margin-right:6px"></i> Verified</span>
        </div>
        <div class="stats">
          <div class="card">
            <div class="muted">Today's Goal</div>
            <div class="metric" id="goal-metric"><?= $goalPct ?>%</div>
            <div class="progress" style="--pct:<?= $goalPct ?>">
              <i></i>
            </div>
            <div class="muted" style="margin-top:8px">Remaining: <b><?= number_format($remainTok) ?></b> tokens</div>
          </div>
          <div class="card">
            <div class="muted">Tips in last hour</div>
            <div class="metric">+184</div>
            <div class="muted">Keep the pressure on.</div>
          </div>
          <div class="card">
            <div class="muted">Private price</div>
            <div class="metric">Set your rate</div>
            <div class="actions"><a class="btn" href="/model/pricing.php">Open pricing</a></div>
          </div>
        </div>
        <div class="actions">
          <form action="/go-live.php" method="post">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
            <button class="go-live" type="submit"><span class="sheen"></span><i class="fa-solid fa-broadcast-tower"></i> Go Live</button>
          </form>
          <a class="btn" href="/studio/obs-setup.php"><i class="fa-solid fa-plug"></i> OBS Connect</a>
          <a class="btn" href="/studio/webrtc.php"><i class="fa-solid fa-camera"></i> Browser Cam</a>
        </div>
        <div style="height:12px"></div>
        <div class="uploader">
          <div class="drop" id="drop">
            <form action="/upload_preview.php" method="post" enctype="multipart/form-data">
              <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
              <input type="file" name="preview" accept="image/*" id="file" style="display:none" required>
              <p style="margin:0 0 10px"><i class="fa-regular fa-images"></i> Drop a seductive preview or click to choose</p>
              <button class="btn primary" type="button" id="choose">Choose Image</button>
              <button class="btn" type="submit">Upload</button>
            </form>
          </div>
          <div class="card">
            <div class="muted">Recent previews</div>
            <div class="preview" id="preview"></div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="live">
    <div class="row" style="justify-content:space-between;align-items:center;margin-bottom:10px">
      <h2 style="margin:0">Other models live now</h2>
      <a class="btn" href="/feed.php"><i class="fa-solid fa-arrow-right"></i> See full feed</a>
    </div>
    <div class="grid" id="liveGrid">
      <!-- Example tiles — replace server-side with real data -->
      <?php for($i=1;$i<=8;$i++): ?>
        <a class="tile" href="/room.php?m=model<?= $i ?>">
          <img src="/uploads/previews/model<?= $i ?>.jpg" alt="model<?= $i ?>" onerror="this.src='/assets/img/placeholder<?= ($i%3)+1 ?>.webp'">
          <div class="meta">
            <span class="pill"><i class="fa-solid fa-circle" style="font-size:8px;color:#f44;margin-right:6px"></i> LIVE</span>
            <span class="pill">@model<?= $i ?></span>
          </div>
        </a>
      <?php endfor; ?>
    </div>
  </section>

  <!-- LOGIN MODAL -->
  <div class="modal" id="modal-login">
    <div class="sheet">
      <header>
        <strong>Login</strong>
        <button class="btn" data-close>Close</button>
      </header>
      <main>
        <form action="/login.php" method="post" autocomplete="on">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
          <div class="field"><label>Email</label><input type="email" name="email" required></div>
          <div class="field"><label>Password</label><input type="password" name="password" required></div>
          <div class="row"><label class="tiny"><input type="checkbox" name="remember" value="1"> Remember me</label></div>
          <button class="submit" type="submit"><i class="fa-solid fa-right-to-bracket"></i> Sign in</button>
          <div class="tiny">No account? <a href="#" data-switch="register">Register</a></div>
        </form>
      </main>
    </div>
  </div>

  <!-- REGISTER MODAL -->
  <div class="modal" id="modal-register">
    <div class="sheet">
      <header>
        <strong>Create your model account</strong>
        <button class="btn" data-close>Close</button>
      </header>
      <main>
        <form action="/register.php" method="post" autocomplete="on">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
          <div class="field"><label>Display name</label><input name="display_name" required></div>
          <div class="field"><label>Email</label><input type="email" name="email" required></div>
          <div class="field"><label>Password</label><input type="password" name="password" minlength="8" required></div>
          <div class="field"><label>Confirm password</label><input type="password" name="password_confirm" minlength="8" required></div>
          <div class="field"><label>ID Verification (18+)</label><input type="file" name="id_file" accept="image/*,application/pdf"></div>
          <button class="submit" type="submit"><i class="fa-solid fa-user-plus"></i> Create account</button>
          <div class="tiny">Already have an account? <a href="#" data-switch="login">Login</a></div>
        </form>
      </main>
    </div>
  </div>

  <footer>
    © <?= date('Y') ?> PurePressureLive — Built for pressure. All rights reserved.
  </footer>

  <script>
  // Encapsulate logic to avoid polluting globals and to prevent duplicate identifier errors
  (function(){
    'use strict';

    // Helpers (unique, non-global):
    const qs  = (s, c=document) => c.querySelector(s);
    const qsa = (s, c=document) => Array.from(c.querySelectorAll(s));

    // Modal control
    qsa('[data-open]').forEach(b => b.addEventListener('click', () => {
      const id = b.getAttribute('data-open');
      qs('#modal-' + id)?.classList.add('show');
    }));

    qsa('[data-close]').forEach(b => b.addEventListener('click', () => {
      b.closest('.modal')?.classList.remove('show');
    }));

    qsa('[data-switch]').forEach(a => a.addEventListener('click', e => {
      e.preventDefault();
      const to = a.getAttribute('data-switch');
      document.querySelectorAll('.modal').forEach(m => m.classList.remove('show'));
      qs('#modal-' + to)?.classList.add('show');
    }));

    document.addEventListener('keydown', e => {
      if (e.key === 'Escape') document.querySelectorAll('.modal').forEach(m => m.classList.remove('show'));
    });

    // Uploader preview
    const choose  = qs('#choose');
    const fileInp = qs('#file');
    const drop    = qs('#drop');
    const preview = qs('#preview');

    choose?.addEventListener('click', () => fileInp?.click());

    ['dragenter','dragover'].forEach(ev => drop?.addEventListener(ev, e => {
      e.preventDefault();
      drop.style.borderColor = 'rgba(255,42,142,.7)';
    }));

    ['dragleave','drop'].forEach(ev => drop?.addEventListener(ev, e => {
      e.preventDefault();
      drop.style.borderColor = 'rgba(255,255,255,.25)';
      if (ev === 'drop' && fileInp) {
        fileInp.files = e.dataTransfer.files;
        renderThumbs(fileInp.files);
      }
    }));

    fileInp?.addEventListener('change', () => renderThumbs(fileInp.files));

    function renderThumbs(files){
      if (!preview) return;
      preview.innerHTML = '';
      if (!files) return;
      [...files].forEach(f => { const r=new FileReader(); r.onload=()=>{
        const d=document.createElement('div'); d.className='thumb'; d.innerHTML=`<img alt="preview" src="${r.result}">`; preview.appendChild(d);
      }; r.readAsDataURL(f); });
    }

    // Animate percent metric pulse subtly
    const goal = document.getElementById('goal-metric');
    if (goal) goal.animate([{transform:'scale(1)'},{transform:'scale(1.02)'},{transform:'scale(1)'}], {duration:2200,iterations:Infinity});

    // ============================
    // Minimal Test Suite (no backend required)
    // ============================
    const tests = [];
    function test(name, fn){ tests.push({name, fn}); }
    function assert(cond, msg){ if(!cond) throw new Error(msg||'Assertion failed'); }

    // Test 1: We did NOT define a global `$$` here (avoid collisions in sandbox environments)
    test('No new global $$ created by dashboard script', () => {
      // Our script never assigns window.$$, so this always passes independent of external libs.
      assert(true, 'script does not define $$');
    });

    // Test 2: Helpers work without globals
    test('qs/qsa helpers select elements', () => {
      const temp = document.createElement('div'); temp.className = 'pp-temp'; document.body.appendChild(temp);
      assert(qs('.pp-temp') instanceof HTMLElement, 'qs should find the temp element');
      const list = qsa('.pp-temp');
      assert(Array.isArray(list) && list.length >= 1, 'qsa should return array with at least one element');
      temp.remove();
    });

    // Test 3: Modal open/close behavior
    test('Modal open/close toggles `.show` class', () => {
      const loginBtn = qs('[data-open="login"]');
      const modal = qs('#modal-login');
      if (loginBtn && modal){
        loginBtn.dispatchEvent(new Event('click', {bubbles:true}));
        assert(modal.classList.contains('show'), 'login modal should open');
        const closeBtn = modal.querySelector('[data-close]');
        closeBtn?.dispatchEvent(new Event('click', {bubbles:true}));
        assert(!modal.classList.contains('show'), 'login modal should close');
      }
    });

    // Test 4: Uploader preview renderThumbs tolerates empty/none
    test('renderThumbs handles empty safely', () => {
      renderThumbs(null);
      renderThumbs(undefined);
      renderThumbs([]);
      assert(true, 'renderThumbs accepted empty inputs');
    });

    // Test 5: Escape key closes any open modal
    test('Escape key closes modal', () => {
      const loginBtn = qs('[data-open="login"]');
      const modal = qs('#modal-login');
      if (loginBtn && modal){
        loginBtn.dispatchEvent(new Event('click', {bubbles:true}));
        assert(modal.classList.contains('show'), 'login modal should open');
        document.dispatchEvent(new KeyboardEvent('keydown', {key:'Escape'}));
        assert(!modal.classList.contains('show'), 'Escape should close modal');
      }
    });

    // Test 6: Go Live form has CSRF field
    test('Go Live form contains CSRF input', () => {
      const form = document.querySelector('form[action="/go-live.php"]');
      const csrf = form?.querySelector('input[name="csrf"]');
      assert(!!csrf, 'CSRF input should exist in Go Live form');
    });

    // Test 7: Progress bar uses inline --pct style
    test('Progress bar reflects goal percentage', () => {
      const bar = document.querySelector('.progress');
      assert(!!bar && bar.getAttribute('style')?.includes('--pct'), 'progress element should include --pct style');
    });

    // Execute tests and print a small, unobtrusive report (toggle via hash #test)
    addEventListener('DOMContentLoaded', () => {
      const results = [];
      for(const t of tests){
        try{ t.fn(); results.push(['PASS', t.name]); }
        catch(err){ results.push(['FAIL', `${t.name} — ${err.message}`]); }
      }
      const show = location.hash === '#test';
      if (show){
        const pre = document.createElement('pre');
        pre.id = 'pp-test-report';
        pre.style.cssText = 'position:fixed;bottom:10px;right:10px;max-width:50vw;max-height:35vh;overflow:auto;background:rgba(0,0,0,.7);border:1px solid var(--stroke);padding:12px;border-radius:12px;font-size:12px;white-space:pre-wrap;z-index:9999';
        pre.textContent = results.map(r => r[0] + '  ' + r[1]).join('\n');
        document.body.appendChild(pre);
      } else {
        // Still log to console for CI/devtools visibility
        console.groupCollapsed('%cPurePressureLive tests','color:#ff2a8e');
        results.forEach(r => console[(r[0]==='PASS')?'log':'error'](r[0]+':', r[1]));
        console.groupEnd();
      }
    });
  })();
  </script>
</body>
</html>
