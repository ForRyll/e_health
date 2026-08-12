<?php
// ============================================================
// register_akun.php — Buat Akun Admin Baru
// ============================================================
session_start();

if (isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

require_once 'koneksi.php';

$alert      = '';
$alert_type = '';
$old_user   = '';

if (isset($_POST['register'])) {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $konfirm  = trim($_POST['konfirm']  ?? '');
    $role     = trim($_POST['role']     ?? 'admin_rs');
    $old_user = $username;

    // ── Validasi ──
    $errors = [];
    if (strlen($username) < 4)          $errors[] = 'Username minimal 4 karakter.';
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username))
                                         $errors[] = 'Username hanya boleh huruf, angka, dan underscore.';
    if (strlen($password) < 6)          $errors[] = 'Password minimal 6 karakter.';
    if ($password !== $konfirm)         $errors[] = 'Konfirmasi password tidak cocok.';
    if (!in_array($role, ['admin_rs','petugas'])) $errors[] = 'Role tidak valid.';

    // Cek duplikat username
    if (empty($errors)) {
        $u_s = mysqli_real_escape_string($conn, $username);
        $cek = mysqli_query($conn, "SELECT id FROM users WHERE username = '$u_s' LIMIT 1");
        if ($cek && mysqli_num_rows($cek) > 0) {
            $errors[] = "Username <strong>$username</strong> sudah digunakan. Pilih username lain.";
        }
    }

    if (empty($errors)) {
        $u_s    = mysqli_real_escape_string($conn, $username);
        $pw_hash= password_hash($password, PASSWORD_DEFAULT);
        $pw_s   = mysqli_real_escape_string($conn, $pw_hash);
        $role_s = mysqli_real_escape_string($conn, $role);

        $sql = "INSERT INTO users (username, password, role) VALUES ('$u_s', '$pw_s', '$role_s')";

        if (mysqli_query($conn, $sql)) {
            $alert      = "Akun <strong>$username</strong> berhasil dibuat! Silakan login.";
            $alert_type = 'success';
            $old_user   = '';
        } else {
            $alert      = 'Gagal membuat akun: ' . mysqli_error($conn);
            $alert_type = 'danger';
        }
    } else {
        $alert      = implode('<br>', $errors);
        $alert_type = 'danger';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Akun | PELITA E-Health</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
        --teal:      #0B7B6E;
        --teal-mid:  #0E9E8D;
        --navy:      #0D1B2A;
        --slate:     #4A6278;
        --muted:     #8A9BAD;
        --surface:   #F4F8F7;
    }

    body {
        font-family: 'DM Sans', sans-serif;
        background: var(--surface);
        min-height: 100vh;
        display: grid;
        grid-template-columns: 1fr 1fr;
    }

    /* ── KIRI ── */
    .left-panel {
        background: var(--navy);
        display: flex;
        flex-direction: column;
        justify-content: center;
        padding: 60px 64px;
        position: relative;
        overflow: hidden;
    }

    .left-panel::before {
        content: '';
        position: absolute; inset: 0;
        background-image:
            linear-gradient(rgba(14,158,141,.09) 1px, transparent 1px),
            linear-gradient(90deg, rgba(14,158,141,.09) 1px, transparent 1px);
        background-size: 44px 44px;
        mask-image: radial-gradient(ellipse 80% 80% at 30% 50%, black 30%, transparent 100%);
    }
    .left-panel::after {
        content: '';
        position: absolute;
        top: -100px; left: -100px;
        width: 500px; height: 500px;
        background: radial-gradient(circle, rgba(14,158,141,.18) 0%, transparent 70%);
        pointer-events: none;
    }

    .left-content { position: relative; z-index: 2; }

    .brand-badge {
        display: inline-flex; align-items: center; gap: 10px;
        background: rgba(14,158,141,.12);
        border: 1px solid rgba(14,158,141,.3);
        border-radius: 50px; padding: 8px 18px;
        font-size: 12px; font-weight: 600;
        color: #5DCFC2; letter-spacing: .5px;
        margin-bottom: 32px;
    }
    .brand-dot { width:7px; height:7px; border-radius:50%; background:#0AD4BC; animation:blink 2s infinite; }
    @keyframes blink { 0%,100%{opacity:1} 50%{opacity:.3} }

    .left-title {
        font-family: 'Syne', sans-serif; font-weight: 800;
        font-size: clamp(32px, 3vw, 50px);
        color: white; line-height: 1.1; margin-bottom: 18px;
    }
    .left-title span { background: linear-gradient(90deg,#0AD4BC,#5DCFC2); -webkit-background-clip:text; -webkit-text-fill-color:transparent; }

    .left-desc { font-size: 14px; color: rgba(255,255,255,.45); line-height: 1.75; font-weight: 300; margin-bottom: 36px; max-width: 380px; }

    /* Step list */
    .step-list { display: flex; flex-direction: column; gap: 14px; }
    .step-item { display:flex; align-items:flex-start; gap:14px; }
    .step-num {
        width: 30px; height: 30px; border-radius: 8px; flex-shrink: 0;
        background: rgba(14,158,141,.15); border: 1px solid rgba(14,158,141,.3);
        display: flex; align-items: center; justify-content: center;
        font-size: 12px; font-weight: 700; color: #5DCFC2;
    }
    .step-text { font-size: 13px; color: rgba(255,255,255,.6); line-height: 1.55; padding-top: 5px; }
    .step-text strong { color: rgba(255,255,255,.85); font-weight: 600; }

    /* ── KANAN ── */
    .right-panel {
        background: white;
        display: flex; flex-direction: column;
        justify-content: center; align-items: center;
        padding: 60px 48px;
    }

    .form-box { width: 100%; max-width: 420px; animation: fadeUp .8s ease both; }
    @keyframes fadeUp { from{opacity:0;transform:translateY(20px)} to{opacity:1;transform:translateY(0)} }

    .form-logo {
        width: 52px; height: 52px; border-radius: 14px;
        background: linear-gradient(135deg, var(--teal-mid), #0AD4BC);
        display: flex; align-items: center; justify-content: center;
        font-size: 22px; color: white; margin-bottom: 22px;
        box-shadow: 0 8px 24px rgba(14,158,141,.35);
    }

    .form-heading { font-family:'Syne',sans-serif; font-weight:800; font-size:26px; color:var(--navy); margin-bottom:5px; }
    .form-sub { font-size:13.5px; color:var(--muted); margin-bottom:28px; font-weight:300; }

    /* Alert */
    .f-alert {
        border-radius: 12px; padding: 13px 16px; font-size: 13.5px;
        border: none; display: flex; align-items: flex-start; gap: 10px; margin-bottom: 20px;
    }
    .f-alert i { flex-shrink:0; margin-top:2px; }
    .f-alert.success { background:#d1fae5; color:#065f46; }
    .f-alert.danger  { background:#fee2e2; color:#991b1b; }

    /* Fields */
    .field-group { margin-bottom: 15px; }
    .field-label { font-size:12.5px; font-weight:600; color:var(--slate); margin-bottom:6px; display:block; }

    .input-wrap { position: relative; }
    .input-wrap .icon-left { position:absolute; left:13px; top:50%; transform:translateY(-50%); color:var(--muted); font-size:13px; pointer-events:none; }
    .input-wrap input, .input-wrap select {
        width:100%; border:1.5px solid #E5E7EB; border-radius:11px;
        padding:11px 13px 11px 38px; font-size:13.5px;
        font-family:'DM Sans',sans-serif; color:var(--navy);
        transition:border-color .2s, box-shadow .2s; background:white;
        appearance: none;
    }
    .input-wrap input:focus, .input-wrap select:focus {
        border-color:var(--teal-mid); box-shadow:0 0 0 3px rgba(14,158,141,.12); outline:none;
    }

    /* pw toggle */
    .pw-toggle { position:absolute; right:12px; top:50%; transform:translateY(-50%); background:none; border:none; color:var(--muted); cursor:pointer; font-size:13px; padding:0; transition:color .2s; }
    .pw-toggle:hover { color:var(--slate); }

    /* pw strength */
    .pw-bars { display:flex; gap:4px; margin-top:7px; }
    .pw-bar { flex:1; height:3px; border-radius:4px; background:#F3F4F6; transition:background .3s; }
    .pw-bar.lemah  { background:#ef4444; }
    .pw-bar.sedang { background:#f59e0b; }
    .pw-bar.kuat   { background:#22c55e; }
    .pw-hint { font-size:.7rem; color:#9ca3af; margin-top:5px; }

    /* Role selector */
    .role-group { display:flex; gap:10px; }
    .role-card {
        flex:1; border:1.5px solid #E5E7EB; border-radius:11px;
        padding:11px 14px; cursor:pointer; transition:all .2s;
        display:flex; align-items:center; gap:10px;
        font-size:13px; font-weight:500; color:#6b7280; user-select:none;
    }
    .role-card input { display:none; }
    .role-card i { font-size:1rem; }
    .role-card:has(input:checked) { border-color:var(--teal-mid); background:rgba(14,158,141,.06); color:var(--teal); }

    /* Submit */
    .btn-register {
        width:100%; background:linear-gradient(135deg,var(--teal-mid),#0AD4BC);
        color:white; border:none; border-radius:11px; padding:13px;
        font-size:14.5px; font-weight:600; font-family:'DM Sans',sans-serif;
        cursor:pointer; transition:all .3s;
        display:flex; align-items:center; justify-content:center; gap:9px;
        box-shadow:0 6px 24px rgba(14,158,141,.3); margin-top:4px;
    }
    .btn-register:hover { transform:translateY(-2px); box-shadow:0 10px 30px rgba(14,158,141,.45); }

    .divider { display:flex; align-items:center; gap:12px; margin:18px 0; }
    .divider::before, .divider::after { content:''; flex:1; height:1px; background:#F3F4F6; }
    .divider span { font-size:12px; color:#D1D5DB; }

    .login-link { text-align:center; font-size:13px; color:var(--muted); }
    .login-link a { color:var(--teal); font-weight:600; text-decoration:none; }
    .login-link a:hover { color:var(--teal-mid); }

    /* Responsive */
    @media (max-width: 860px) {
        body { grid-template-columns: 1fr; }
        .left-panel { padding: 48px 28px 40px; min-height: auto; }
        .right-panel { padding: 44px 24px 56px; }
    }
    </style>
</head>
<body>

<!-- ── KIRI: Branding ── -->
<div class="left-panel">
    <div class="left-content">
        <div class="brand-badge">
            <div class="brand-dot"></div>
            PELITA E-Health · Manajemen Akun
        </div>

        <h1 class="left-title">
            Daftarkan<br>Akun <span>Admin</span><br>Baru
        </h1>

        <p class="left-desc">
            Akun yang dibuat akan memiliki akses ke seluruh modul PELITA E-Health sesuai role yang dipilih.
        </p>

        <div class="step-list">
            <div class="step-item">
                <div class="step-num">1</div>
                <div class="step-text"><strong>Isi username unik</strong> — minimal 4 karakter, hanya huruf, angka, dan underscore</div>
            </div>
            <div class="step-item">
                <div class="step-num">2</div>
                <div class="step-text"><strong>Buat password kuat</strong> — minimal 6 karakter, disimpan terenkripsi</div>
            </div>
            <div class="step-item">
                <div class="step-num">3</div>
                <div class="step-text"><strong>Pilih role</strong> — Admin RS punya akses penuh, Petugas akses terbatas</div>
            </div>
        </div>
    </div>
</div>

<!-- ── KANAN: Form ── -->
<div class="right-panel">
    <div class="form-box">

        <div class="form-logo"><i class="fa-solid fa-user-shield"></i></div>
        <div class="form-heading">Buat Akun Baru</div>
        <div class="form-sub">Isi data di bawah untuk mendaftarkan akun petugas</div>

        <?php if ($alert): ?>
        <div class="f-alert <?= $alert_type ?>">
            <i class="fa-solid <?= $alert_type==='success'?'fa-circle-check':'fa-triangle-exclamation' ?>"></i>
            <div>
                <?= $alert ?>
                <?php if($alert_type==='success'): ?>
                &nbsp;<a href="login.php" style="font-weight:700;color:#065f46;">Login sekarang →</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <form method="POST" action="" novalidate>

            <!-- Username -->
            <div class="field-group">
                <label class="field-label">Username *</label>
                <div class="input-wrap">
                    <i class="fa-solid fa-user icon-left"></i>
                    <input type="text" name="username" placeholder="Cth: dr_budi, perawat01"
                           maxlength="30" autocomplete="off"
                           value="<?= htmlspecialchars($old_user) ?>" required
                           oninput="cekUsername(this.value)">
                </div>
                <div class="pw-hint" id="unameHint"></div>
            </div>

            <!-- Password -->
            <div class="field-group">
                <label class="field-label">Password *</label>
                <div class="input-wrap">
                    <i class="fa-solid fa-lock icon-left"></i>
                    <input type="password" name="password" id="pwInput"
                           placeholder="Minimal 6 karakter" required
                           oninput="cekKekuatan(this.value)">
                    <button type="button" class="pw-toggle" onclick="togglePw('pwInput','pwIcon1')">
                        <i class="fa-solid fa-eye" id="pwIcon1"></i>
                    </button>
                </div>
                <div class="pw-bars">
                    <div class="pw-bar" id="bar1"></div>
                    <div class="pw-bar" id="bar2"></div>
                    <div class="pw-bar" id="bar3"></div>
                </div>
                <div class="pw-hint" id="pwLabel">Masukkan password</div>
            </div>

            <!-- Konfirmasi Password -->
            <div class="field-group">
                <label class="field-label">Konfirmasi Password *</label>
                <div class="input-wrap">
                    <i class="fa-solid fa-lock-open icon-left"></i>
                    <input type="password" name="konfirm" id="konfirmInput"
                           placeholder="Ulangi password" required
                           oninput="cekCocok()">
                    <button type="button" class="pw-toggle" onclick="togglePw('konfirmInput','pwIcon2')">
                        <i class="fa-solid fa-eye" id="pwIcon2"></i>
                    </button>
                </div>
                <div class="pw-hint" id="cocokLabel"></div>
            </div>

            <!-- Role -->
            <div class="field-group">
                <label class="field-label">Role Akun *</label>
                <div class="role-group">
                    <label class="role-card">
                        <input type="radio" name="role" value="admin_rs" checked>
                        <i class="fa-solid fa-user-tie" style="color:#0B7B6E"></i>
                        Admin RS
                    </label>
                    <label class="role-card">
                        <input type="radio" name="role" value="petugas">
                        <i class="fa-solid fa-user-nurse" style="color:#3b82f6"></i>
                        Petugas
                    </label>
                </div>
            </div>

            <button type="submit" name="register" class="btn-register">
                <i class="fa-solid fa-user-plus"></i> Buat Akun
            </button>

        </form>

        <div class="divider"><span>atau</span></div>
        <div class="login-link">Sudah punya akun? <a href="login.php">Login di sini</a></div>

    </div>
</div>

<script>
function togglePw(inputId, iconId) {
    const el  = document.getElementById(inputId);
    const ico = document.getElementById(iconId);
    el.type = el.type === 'password' ? 'text' : 'password';
    ico.className = el.type === 'password' ? 'fa-solid fa-eye' : 'fa-solid fa-eye-slash';
}

function cekUsername(val) {
    const hint = document.getElementById('unameHint');
    if (!val) { hint.textContent = ''; return; }
    if (val.length < 4) {
        hint.textContent = '✗ Minimal 4 karakter';
        hint.style.color = '#ef4444';
    } else if (!/^[a-zA-Z0-9_]+$/.test(val)) {
        hint.textContent = '✗ Hanya huruf, angka, dan underscore';
        hint.style.color = '#ef4444';
    } else {
        hint.textContent = '✓ Username valid';
        hint.style.color = '#22c55e';
    }
}

function cekKekuatan(val) {
    const b1=document.getElementById('bar1'),
          b2=document.getElementById('bar2'),
          b3=document.getElementById('bar3'),
          lbl=document.getElementById('pwLabel');
    b1.className=b2.className=b3.className='pw-bar';
    if (!val) { lbl.textContent='Masukkan password'; lbl.style.color='#9ca3af'; return; }
    let s = 0;
    if (val.length >= 6)  s++;
    if (val.length >= 10) s++;
    if (/[A-Z]/.test(val) && /[0-9]/.test(val)) s++;
    if (s===1) {
        b1.classList.add('lemah');
        lbl.textContent='Lemah'; lbl.style.color='#ef4444';
    } else if (s===2) {
        b1.classList.add('sedang'); b2.classList.add('sedang');
        lbl.textContent='Sedang'; lbl.style.color='#f59e0b';
    } else {
        b1.classList.add('kuat'); b2.classList.add('kuat'); b3.classList.add('kuat');
        lbl.textContent='Kuat ✓'; lbl.style.color='#22c55e';
    }
    cekCocok();
}

function cekCocok() {
    const baru   = document.getElementById('pwInput').value;
    const ulang  = document.getElementById('konfirmInput').value;
    const lbl    = document.getElementById('cocokLabel');
    if (!ulang) { lbl.textContent=''; return; }
    if (baru === ulang) {
        lbl.textContent='✓ Password cocok'; lbl.style.color='#22c55e';
    } else {
        lbl.textContent='✗ Password tidak cocok'; lbl.style.color='#ef4444';
    }
}
</script>
</body>
</html>