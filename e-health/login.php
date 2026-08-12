<?php
// ============================================================
// login.php — Logika PHP di atas sebelum output HTML
// ============================================================
session_start();

if (isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

$login_error = '';

if (isset($_POST['login'])) {
    require_once 'koneksi.php';

    $username      = trim($_POST['username']);
    $password      = trim($_POST['password']);
    $username_safe = mysqli_real_escape_string($conn, $username);

    $result = mysqli_query($conn, "SELECT * FROM users WHERE username = '$username_safe' LIMIT 1");

    if ($result && mysqli_num_rows($result) === 1) {
        $user  = mysqli_fetch_assoc($result);
        $valid = password_verify($password, $user['password']) || ($password === $user['password']);

        if ($valid) {
            $_SESSION['user']    = $user['username'];
            $_SESSION['role']    = $user['role'] ?? 'admin_rs';
            $_SESSION['user_id'] = $user['id'];
            header("Location: index.php");
            exit;
        } else {
            $login_error = 'Password yang kamu masukkan salah.';
        }
    } else {
        $login_error = 'Username tidak ditemukan di sistem.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | PELITA E-Health</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
        --teal:       #0B7B6E;
        --teal-mid:   #0E9E8D;
        --teal-light: #CCEFEB;
        --teal-pale:  #EBF8F6;
        --navy:       #0D1B2A;
        --navy-mid:   #1A2E42;
        --slate:      #4A6278;
        --muted:      #8A9BAD;
        --surface:    #F4F8F7;
        --white:      #FFFFFF;
    }

    html { scroll-behavior: smooth; }
    body { font-family: 'DM Sans', sans-serif; background: var(--surface); color: var(--navy); overflow-x: hidden; }

    /* ══════════════════════════════════
       SECTION 1 — HERO + FORM
    ══════════════════════════════════ */
    .hero-section {
        min-height: 100vh;
        display: grid;
        grid-template-columns: 1fr 1fr;
        position: relative;
        overflow: hidden;
    }

    /* ── Sisi Kiri ── */
    .hero-left {
        background: var(--navy);
        display: flex;
        flex-direction: column;
        justify-content: center;
        padding: 60px 64px;
        position: relative;
        overflow: hidden;
    }

    /* Grid dekoratif */
    .hero-left::before {
        content: '';
        position: absolute; inset: 0;
        background-image:
            linear-gradient(rgba(14,158,141,.09) 1px, transparent 1px),
            linear-gradient(90deg, rgba(14,158,141,.09) 1px, transparent 1px);
        background-size: 44px 44px;
        mask-image: radial-gradient(ellipse 80% 80% at 30% 50%, black 30%, transparent 100%);
    }

    /* Orb cahaya */
    .hero-left::after {
        content: '';
        position: absolute;
        top: -100px; left: -100px;
        width: 500px; height: 500px;
        background: radial-gradient(circle, rgba(14,158,141,.18) 0%, transparent 70%);
        pointer-events: none;
    }

    .hero-left-content { position: relative; z-index: 2; }

    .brand-badge {
        display: inline-flex; align-items: center; gap: 10px;
        background: rgba(14,158,141,.12);
        border: 1px solid rgba(14,158,141,.3);
        border-radius: 50px; padding: 8px 18px;
        font-size: 12px; font-weight: 600;
        color: #5DCFC2; letter-spacing: .5px;
        margin-bottom: 32px;
    }

    .brand-dot {
        width: 7px; height: 7px; border-radius: 50%;
        background: #0AD4BC;
        animation: blink 2s infinite;
    }

    @keyframes blink { 0%,100%{opacity:1} 50%{opacity:.3} }

    .hero-title {
        font-family: 'Syne', sans-serif;
        font-weight: 800;
        font-size: clamp(36px, 3.5vw, 58px);
        color: white;
        line-height: 1.08;
        margin-bottom: 20px;
    }

    .hero-title span {
        background: linear-gradient(90deg, #0AD4BC, #5DCFC2);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .hero-desc {
        font-size: 15px;
        color: rgba(255,255,255,.5);
        line-height: 1.75;
        font-weight: 300;
        max-width: 420px;
        margin-bottom: 36px;
    }

    /* Chip status koneksi */
    .node-chips { display: flex; flex-direction: column; gap: 10px; }

    .node-chip {
        display: flex; align-items: center; gap: 12px;
        background: rgba(255,255,255,.04);
        border: 1px solid rgba(255,255,255,.08);
        border-radius: 12px; padding: 12px 16px;
        transition: border-color .3s;
    }
    .node-chip:hover { border-color: rgba(14,158,141,.35); }

    .chip-icon {
        width: 36px; height: 36px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 14px; color: white; flex-shrink: 0;
    }

    .chip-text { flex: 1; }
    .chip-name { font-size: 13px; font-weight: 600; color: rgba(255,255,255,.85); }
    .chip-role { font-size: 11px; color: rgba(255,255,255,.35); margin-top: 2px; }

    .chip-status {
        display: flex; align-items: center; gap: 5px;
        font-size: 11px; font-weight: 600; color: #22C55E;
    }
    .chip-status .dot {
        width: 6px; height: 6px; border-radius: 50%;
        background: #22C55E;
        animation: blink 1.8s infinite;
    }
    .chip-status.idle { color: #F59E0B; }
    .chip-status.idle .dot { background: #F59E0B; }

    /* ── Sisi Kanan (Form) ── */
    .hero-right {
        background: white;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        padding: 60px 48px;
        position: relative;
    }

    .form-box {
        width: 100%; max-width: 420px;
        animation: fadeUp .8s ease both;
    }

    @keyframes fadeUp { from{opacity:0;transform:translateY(20px)} to{opacity:1;transform:translateY(0)} }

    .form-logo {
        width: 52px; height: 52px; border-radius: 14px;
        background: linear-gradient(135deg, var(--teal-mid), #0AD4BC);
        display: flex; align-items: center; justify-content: center;
        font-size: 22px; color: white;
        margin-bottom: 24px;
        box-shadow: 0 8px 24px rgba(14,158,141,.35);
    }

    .form-heading { font-family: 'Syne', sans-serif; font-weight: 800; font-size: 28px; color: var(--navy); margin-bottom: 6px; }
    .form-sub { font-size: 14px; color: var(--muted); margin-bottom: 32px; font-weight: 300; }

    /* Error alert */
    .err-box {
        background: #FEF2F2; border: 1px solid #FECACA;
        border-radius: 12px; padding: 13px 16px;
        display: flex; align-items: center; gap: 10px;
        font-size: 13.5px; color: #991B1B; margin-bottom: 20px;
    }
    .err-box i { flex-shrink: 0; }

    /* Input */
    .field-group { margin-bottom: 16px; }
    .field-label { font-size: 12.5px; font-weight: 600; color: var(--slate); margin-bottom: 7px; display: block; }

    .input-wrap { position: relative; }
    .input-wrap i.icon-left {
        position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
        color: var(--muted); font-size: 14px; pointer-events: none;
    }
    .input-wrap input {
        width: 100%; border: 1.5px solid #E5E7EB;
        border-radius: 12px; padding: 12px 14px 12px 40px;
        font-size: 14px; font-family: 'DM Sans', sans-serif;
        color: var(--navy); transition: border-color .2s, box-shadow .2s;
        background: white;
    }
    .input-wrap input:focus { border-color: var(--teal-mid); box-shadow: 0 0 0 3px rgba(14,158,141,.12); outline: none; }

    .pw-toggle {
        position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
        background: none; border: none; color: var(--muted);
        cursor: pointer; font-size: 14px; padding: 0; transition: color .2s;
    }
    .pw-toggle:hover { color: var(--slate); }

    .form-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 22px; }
    .check-label { display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--slate); cursor: pointer; }
    .check-label input { accent-color: var(--teal); }
    .forgot-link { font-size: 13px; color: var(--teal); text-decoration: none; font-weight: 500; }
    .forgot-link:hover { color: var(--teal-mid); }

    .btn-login {
        width: 100%; background: linear-gradient(135deg, var(--teal-mid), #0AD4BC);
        color: white; border: none; border-radius: 12px;
        padding: 14px; font-size: 15px; font-weight: 600;
        font-family: 'DM Sans', sans-serif;
        cursor: pointer; transition: all .3s;
        display: flex; align-items: center; justify-content: center; gap: 10px;
        box-shadow: 0 6px 24px rgba(14,158,141,.35);
        margin-bottom: 20px;
    }
    .btn-login:hover { transform: translateY(-2px); box-shadow: 0 10px 32px rgba(14,158,141,.5); }
    .btn-login:active { transform: translateY(0); }

    .register-link { text-align: center; font-size: 13px; color: var(--muted); }
    .register-link a { color: var(--teal); font-weight: 600; text-decoration: none; }
    .register-link a:hover { color: var(--teal-mid); }

    .form-divider { display: flex; align-items: center; gap: 12px; margin: 20px 0; }
    .form-divider::before, .form-divider::after { content: ''; flex:1; height:1px; background:#F3F4F6; }
    .form-divider span { font-size: 12px; color: #D1D5DB; white-space: nowrap; }

    /* scroll arrow */
    .scroll-hint {
        position: absolute; bottom: 28px; left: 50%;
        transform: translateX(-50%);
        display: flex; flex-direction: column; align-items: center; gap: 6px;
        color: var(--muted); font-size: 12px; cursor: pointer;
        text-decoration: none; transition: color .2s;
    }
    .scroll-hint:hover { color: var(--teal); }
    .scroll-hint i { animation: bounce 1.8s infinite; }
    @keyframes bounce { 0%,100%{transform:translateY(0)} 50%{transform:translateY(6px)} }

    /* ══════════════════════════════════
       SECTION 2 — FITUR MODUL
    ══════════════════════════════════ */
    .features-section { padding: 100px 60px; max-width: 1160px; margin: 0 auto; }

    .section-label { font-size: 11px; font-weight: 700; letter-spacing: 3px; text-transform: uppercase; color: var(--teal); margin-bottom: 14px; }
    .section-title { font-family: 'Syne', sans-serif; font-weight: 800; font-size: clamp(28px,3vw,42px); color: var(--navy); line-height: 1.2; margin-bottom: 12px; max-width: 520px; }
    .section-desc { font-size: 15px; color: var(--slate); line-height: 1.75; font-weight: 300; max-width: 500px; margin-bottom: 56px; }

    .features-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; }

    .feat-card {
        background: white; border-radius: 18px;
        padding: 28px 26px; border: 1px solid rgba(0,0,0,.06);
        transition: all .35s; position: relative; overflow: hidden;
    }
    .feat-card::after {
        content: ''; position: absolute; bottom:0; left:0; right:0;
        height: 3px; background: linear-gradient(90deg, var(--c1), var(--c2));
        transform: scaleX(0); transform-origin: left; transition: transform .4s;
    }
    .feat-card:hover { transform: translateY(-5px); box-shadow: 0 16px 40px rgba(0,0,0,.08); }
    .feat-card:hover::after { transform: scaleX(1); }

    .feat-card.c1 { --c1:#0B7B6E; --c2:#0AD4BC; }
    .feat-card.c2 { --c1:#1A56DB; --c2:#3F83F8; }
    .feat-card.c3 { --c1:#C62828; --c2:#EF5350; }
    .feat-card.c4 { --c1:#E65100; --c2:#FB8C00; }

    .feat-icon {
        width: 48px; height: 48px; border-radius: 13px;
        display: flex; align-items: center; justify-content: center;
        font-size: 20px; color: white; margin-bottom: 18px;
    }
    .c1 .feat-icon { background: linear-gradient(135deg,#0B7B6E,#0AD4BC); }
    .c2 .feat-icon { background: linear-gradient(135deg,#1A56DB,#3F83F8); }
    .c3 .feat-icon { background: linear-gradient(135deg,#C62828,#EF5350); }
    .c4 .feat-icon { background: linear-gradient(135deg,#E65100,#FB8C00); }

    .feat-name { font-family: 'Syne', sans-serif; font-weight: 700; font-size: 16px; color: var(--navy); margin-bottom: 8px; }
    .feat-desc { font-size: 13px; color: var(--slate); line-height: 1.65; font-weight: 300; }

    /* ══════════════════════════════════
       SECTION 3 — ALUR API (gelap)
    ══════════════════════════════════ */
    .flow-section { background: var(--navy); padding: 100px 60px; }
    .flow-inner { max-width: 1000px; margin: 0 auto; }
    .flow-section .section-label { color: #5DCFC2; }
    .flow-section .section-title { color: white; }
    .flow-section .section-desc { color: rgba(255,255,255,.45); }

    .flow-steps { display: flex; align-items: flex-start; gap: 0; margin-top: 56px; flex-wrap: wrap; justify-content: center; }

    .flow-step { flex: 1; min-width: 140px; max-width: 180px; text-align: center; position: relative; padding: 0 12px; }

    .flow-step-icon {
        width: 64px; height: 64px; border-radius: 18px; margin: 0 auto 16px;
        display: flex; align-items: center; justify-content: center;
        font-size: 26px; color: white; position: relative; z-index: 1;
    }

    .flow-step-icon.s1 { background: linear-gradient(135deg,#0B7B6E,#0AD4BC); }
    .flow-step-icon.s2 { background: linear-gradient(135deg,#1A56DB,#3F83F8); }
    .flow-step-icon.s3 { background: linear-gradient(135deg,#C62828,#EF5350); }
    .flow-step-icon.s4 { background: linear-gradient(135deg,#7C3AED,#A78BFA); }

    .flow-step-num {
        font-family: 'Syne', sans-serif; font-size: 11px; font-weight: 800;
        letter-spacing: 2px; color: rgba(255,255,255,.3);
        text-transform: uppercase; margin-bottom: 8px;
    }
    .flow-step-name { font-size: 13px; font-weight: 600; color: rgba(255,255,255,.85); margin-bottom: 5px; }
    .flow-step-desc { font-size: 11.5px; color: rgba(255,255,255,.4); line-height: 1.55; }

    .flow-arrow {
        display: flex; align-items: flex-start; padding-top: 28px; flex-shrink: 0;
    }
    .flow-arrow i { color: rgba(14,158,141,.5); font-size: 18px; }

    /* ══════════════════════════════════
       SECTION 4 — STATS
    ══════════════════════════════════ */
    .stats-section { background: var(--teal-pale); padding: 80px 60px; }
    .stats-inner { max-width: 1000px; margin: 0 auto; display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 24px; }

    .stat-item { text-align: center; }
    .stat-num {
        font-family: 'Syne', sans-serif; font-weight: 800;
        font-size: clamp(36px,4vw,56px); color: var(--teal); line-height: 1;
        margin-bottom: 8px;
    }
    .stat-label { font-size: 13px; color: var(--slate); font-weight: 400; line-height: 1.5; }

    /* ══════════════════════════════════
       FOOTER
    ══════════════════════════════════ */
    .login-footer { background: var(--navy-mid, #1A2E42); padding: 32px 60px; text-align: center; }
    .login-footer p { font-size: 12.5px; color: rgba(255,255,255,.3); }
    .login-footer strong { color: rgba(255,255,255,.55); font-weight: 500; }

    /* ══════════════════════════════════
       RESPONSIVE
    ══════════════════════════════════ */
    @media (max-width: 900px) {
        .hero-section { grid-template-columns: 1fr; }
        .hero-left { padding: 60px 32px 48px; min-height: 50vh; justify-content: flex-end; }
        .hero-right { padding: 48px 28px 60px; }
        .features-section, .flow-section, .stats-section, .login-footer { padding: 64px 28px; }
        .flow-steps { gap: 20px; }
        .flow-arrow { display: none; }
    }
    </style>
</head>
<body>

<!-- ══════════════════════════════════════════════
     SECTION 1 — HERO + LOGIN FORM
══════════════════════════════════════════════ -->
<section class="hero-section" id="top">

    <!-- KIRI: Branding -->
    <div class="hero-left">
        <div class="hero-left-content">

            <div class="brand-badge">
                <div class="brand-dot"></div>
                Node Kesehatan · SIGAP E-Government
            </div>

            <h1 class="hero-title">
                PELITA<br>
                <span>E-Health</span><br>
                System
            </h1>

            <p class="hero-desc">
                Platform rekam medis digital terintegrasi yang terhubung langsung ke Sistem Induk Kependudukan, Node Kepolisian, dan Node Bantuan Sosial melalui API real-time.
            </p>

            <!-- Node chips -->
            <div class="node-chips">
                <div class="node-chip">
                    <div class="chip-icon" style="background:linear-gradient(135deg,#0B7B6E,#0AD4BC)">
                        <i class="fa-solid fa-landmark-dome"></i>
                    </div>
                    <div class="chip-text">
                        <div class="chip-name">Node Kependudukan</div>
                        <div class="chip-role">Sistem Induk · Data Provider Utama</div>
                    </div>
                    <div class="chip-status"><span class="dot"></span>Online</div>
                </div>
                <div class="node-chip">
                    <div class="chip-icon" style="background:linear-gradient(135deg,#1A56DB,#3F83F8)">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                    <div class="chip-text">
                        <div class="chip-name">Node Kepolisian</div>
                        <div class="chip-role">Konsumen Data SKCK & Surat Sehat</div>
                    </div>
                    <div class="chip-status"><span class="dot"></span>Online</div>
                </div>
                <div class="node-chip">
                    <div class="chip-icon" style="background:linear-gradient(135deg,#E65100,#FB8C00)">
                        <i class="fa-solid fa-hand-holding-heart"></i>
                    </div>
                    <div class="chip-text">
                        <div class="chip-name">Node Bantuan Sosial</div>
                        <div class="chip-role">Konsumen Status Kesehatan Warga</div>
                    </div>
                    <div class="chip-status idle"><span class="dot"></span>Sinkronisasi</div>
                </div>
            </div>

        </div>

        <a class="scroll-hint" href="#fitur">
            <span>Scroll untuk selengkapnya</span>
            <i class="fa-solid fa-chevron-down"></i>
        </a>
    </div>

    <!-- KANAN: Form Login -->
    <div class="hero-right">
        <div class="form-box">

            <div class="form-logo"><i class="fa-solid fa-heart-pulse"></i></div>

            <div class="form-heading">Selamat Datang</div>
            <div class="form-sub">Masuk ke dashboard petugas PELITA E-Health</div>

            <?php if ($login_error): ?>
            <div class="err-box">
                <i class="fa-solid fa-circle-exclamation"></i>
                <?= htmlspecialchars($login_error) ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="">

                <div class="field-group">
                    <label class="field-label">Username</label>
                    <div class="input-wrap">
                        <i class="fa-solid fa-user icon-left"></i>
                        <input type="text" name="username" placeholder="Masukkan username"
                               required autocomplete="off"
                               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                    </div>
                </div>

                <div class="field-group">
                    <label class="field-label">Password</label>
                    <div class="input-wrap">
                        <i class="fa-solid fa-lock icon-left"></i>
                        <input type="password" name="password" id="pwInput"
                               placeholder="Masukkan password" required>
                        <button type="button" class="pw-toggle" onclick="togglePw()">
                            <i class="fa-solid fa-eye" id="pwIcon"></i>
                        </button>
                    </div>
                </div>

                <div class="form-row">
                    <label class="check-label">
                        <input type="checkbox" name="remember"> Ingat Saya
                    </label>
                    <a href="#" class="forgot-link">Lupa Password?</a>
                </div>

                <button type="submit" name="login" class="btn-login">
                    <i class="fa-solid fa-right-to-bracket"></i>
                    Login ke Dashboard
                </button>

                <div class="form-divider"><span>atau</span></div>

                <div class="register-link">
                    Belum punya akun? <a href="register_akun.php">Buat Akun Admin</a>
                </div>

            </form>
        </div>
    </div>

</section>


<!-- ══════════════════════════════════════════════
     SECTION 2 — FITUR MODUL
══════════════════════════════════════════════ -->
<section id="fitur">
<div class="features-section">
    <div class="section-label">Fitur Sistem</div>
    <div class="section-title">Empat Modul Utama<br>dalam Satu Platform</div>
    <div class="section-desc">Setiap modul dirancang untuk mempercepat pelayanan kesehatan dan mempermudah integrasi data antar-sistem pemerintahan.</div>

    <div class="features-grid">
        <div class="feat-card c1">
            <div class="feat-icon"><i class="fa-solid fa-user-plus"></i></div>
            <div class="feat-name">Pendaftaran Pasien</div>
            <div class="feat-desc">Registrasi pasien baru dengan validasi NIK real-time dari Node Kependudukan. Tidak perlu input ulang data yang sudah ada.</div>
        </div>
        <div class="feat-card c2">
            <div class="feat-icon"><i class="fa-solid fa-stethoscope"></i></div>
            <div class="feat-name">Rekam Medis Digital</div>
            <div class="feat-desc">Input pemeriksaan, golongan darah, status penyakit, dan tes narkoba. Data siap ditarik otomatis oleh Node Kepolisian untuk SKCK.</div>
        </div>
        <div class="feat-card c3">
            <div class="feat-icon"><i class="fa-solid fa-bed-pulse"></i></div>
            <div class="feat-name">Laporan Kematian</div>
            <div class="feat-desc">Pelaporan kematian pasien langsung terkirim ke Sistem Induk Kependudukan melalui API secara otomatis dan terverifikasi.</div>
        </div>
        <div class="feat-card c4">
            <div class="feat-icon"><i class="fa-solid fa-rotate"></i></div>
            <div class="feat-name">Log Aktivitas API</div>
            <div class="feat-desc">Monitor seluruh request masuk dan keluar antar-node secara real-time. Transparansi penuh alur data lintas sistem.</div>
        </div>
    </div>
</div>
</section>


<!-- ══════════════════════════════════════════════
     SECTION 3 — ALUR KERJA API
══════════════════════════════════════════════ -->
<section class="flow-section" id="alur">
    <div class="flow-inner">
        <div class="section-label">Cara Kerja</div>
        <div class="section-title">Dari Input ke Integrasi<br>dalam Hitungan Detik</div>
        <div class="section-desc">Contoh alur: petugas input rekam medis → data tersimpan → Node Kepolisian menarik data SKCK secara otomatis tanpa input manual.</div>

        <div class="flow-steps">

            <div class="flow-step">
                <div class="flow-step-num">01</div>
                <div class="flow-step-icon s1"><i class="fa-solid fa-user-plus"></i></div>
                <div class="flow-step-name">Petugas Input</div>
                <div class="flow-step-desc">Data pasien & rekam medis diinput melalui dashboard PELITA</div>
            </div>

            <div class="flow-arrow"><i class="fa-solid fa-chevron-right"></i></div>

            <div class="flow-step">
                <div class="flow-step-num">02</div>
                <div class="flow-step-icon s2"><i class="fa-solid fa-database"></i></div>
                <div class="flow-step-name">Tersimpan di DB</div>
                <div class="flow-step-desc">Data tervalidasi dan tersimpan aman di database Node Kesehatan</div>
            </div>

            <div class="flow-arrow"><i class="fa-solid fa-chevron-right"></i></div>

            <div class="flow-step">
                <div class="flow-step-num">03</div>
                <div class="flow-step-icon s3"><i class="fa-solid fa-plug-circle-check"></i></div>
                <div class="flow-step-name">API Tersedia</div>
                <div class="flow-step-desc">Endpoint API aktif dan siap merespons request dari node lain</div>
            </div>

            <div class="flow-arrow"><i class="fa-solid fa-chevron-right"></i></div>

            <div class="flow-step">
                <div class="flow-step-num">04</div>
                <div class="flow-step-icon s4"><i class="fa-solid fa-shield-halved"></i></div>
                <div class="flow-step-name">Node Tarik Data</div>
                <div class="flow-step-desc">Kepolisian & Bansos menarik data otomatis — tanpa input ulang</div>
            </div>

        </div>
    </div>
</section>


<!-- ══════════════════════════════════════════════
     SECTION 4 — STATISTIK
══════════════════════════════════════════════ -->
<section class="stats-section" id="stats">
    <div class="stats-inner">
        <div class="stat-item">
            <div class="stat-num">5</div>
            <div class="stat-label">Node Sistem<br>Terintegrasi</div>
        </div>
        <div class="stat-item">
            <div class="stat-num">API</div>
            <div class="stat-label">Komunikasi<br>Real-Time</div>
        </div>
        <div class="stat-item">
            <div class="stat-num">4</div>
            <div class="stat-label">Modul Aktif<br>dalam Sistem</div>
        </div>
        <div class="stat-item">
            <div class="stat-num">24/7</div>
            <div class="stat-label">Sinkronisasi<br>Otomatis</div>
        </div>
    </div>
</section>


<!-- ══════════════════════════════════════════════
     FOOTER
══════════════════════════════════════════════ -->
<footer class="login-footer">
    <p>
        <strong>PELITA E-Health</strong> · Node Sistem Kesehatan &nbsp;·&nbsp;
        Bagian dari <strong>SIGAP E-Government</strong> &nbsp;·&nbsp;
        Tugas Komputasi Paralel &amp; Terdistribusi
    </p>
</footer>


<script>
// Show / hide password
function togglePw() {
    const inp = document.getElementById('pwInput');
    const ico = document.getElementById('pwIcon');
    if (inp.type === 'password') {
        inp.type = 'text';
        ico.className = 'fa-solid fa-eye-slash';
    } else {
        inp.type = 'password';
        ico.className = 'fa-solid fa-eye';
    }
}

// Smooth scroll untuk anchor dalam halaman
document.querySelectorAll('a[href^="#"]').forEach(a => {
    a.addEventListener('click', e => {
        const t = document.querySelector(a.getAttribute('href'));
        if (t) { e.preventDefault(); t.scrollIntoView({ behavior: 'smooth' }); }
    });
});
</script>

</body>
</html>