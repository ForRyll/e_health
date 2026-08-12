<?php
// ============================================================
// profil.php — Profil Akun & Ganti Password
// Dipanggil via include dari index.php (session & $conn aktif)
// ============================================================

require_once 'koneksi.php';

$alert      = '';
$alert_type = '';

// Ambil data user dari DB
$user_id = $_SESSION['user_id'] ?? 0;
$user_db = null;
if ($user_id) {
    $res = mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id LIMIT 1");
    if ($res && mysqli_num_rows($res) === 1) {
        $user_db = mysqli_fetch_assoc($res);
    }
}

// ── Ganti Password ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ganti_password'])) {
    $pw_lama  = trim($_POST['pw_lama']    ?? '');
    $pw_baru  = trim($_POST['pw_baru']    ?? '');
    $pw_ulang = trim($_POST['pw_ulang']   ?? '');

    $errors = [];
    if (empty($pw_lama))              $errors[] = 'Password lama wajib diisi.';
    if (strlen($pw_baru) < 6)         $errors[] = 'Password baru minimal 6 karakter.';
    if ($pw_baru !== $pw_ulang)       $errors[] = 'Konfirmasi password tidak cocok.';

    if (empty($errors) && $user_db) {
        // Verifikasi password lama (support plain text lama atau hashed)
        $valid = password_verify($pw_lama, $user_db['password']) || ($pw_lama === $user_db['password']);
        if (!$valid) {
            $errors[] = 'Password lama yang kamu masukkan salah.';
        }
    }

    if (empty($errors)) {
        $pw_hash = password_hash($pw_baru, PASSWORD_DEFAULT);
        $pw_s    = mysqli_real_escape_string($conn, $pw_hash);
        if (mysqli_query($conn, "UPDATE users SET password = '$pw_s' WHERE id = $user_id")) {
            // Update di DB → reload user_db
            $user_db['password'] = $pw_hash;
            $alert      = 'Password berhasil diperbarui!';
            $alert_type = 'success';
        } else {
            $alert      = 'Gagal memperbarui password: ' . mysqli_error($conn);
            $alert_type = 'danger';
        }
    } else {
        $alert      = implode('<br>', $errors);
        $alert_type = 'danger';
    }
}

// Statistik akun
$stat_pasien = $stat_rekam = $stat_lapor = 0;
$q = mysqli_query($conn, "SELECT COUNT(*) c FROM pasien"); if($q) $stat_pasien = mysqli_fetch_assoc($q)['c'];
$q = mysqli_query($conn, "SELECT COUNT(*) c FROM rekam_medis"); if($q) $stat_rekam = mysqli_fetch_assoc($q)['c'];
$q = mysqli_query($conn, "SELECT COUNT(*) c FROM laporan_kematian"); if($q) $stat_lapor = mysqli_fetch_assoc($q)['c'];

$tgl_akun  = $user_db ? date('d M Y', strtotime($user_db['created_at'] ?? 'now')) : '—';
$nama_user = $_SESSION['user'] ?? 'Admin';
$role      = $_SESSION['role'] ?? 'admin_rs';
$role_label= $role === 'admin_rs' ? 'Admin Rumah Sakit' : ucfirst(str_replace('_',' ',$role));
?>

<style>
.pf-wrapper { max-width: 800px; }

/* Alert */
.pf-alert {
    border-radius:12px; padding:14px 18px; font-size:.875rem;
    border:none; display:flex; align-items:flex-start; gap:12px; margin-bottom:20px;
}
.pf-alert i { flex-shrink:0; margin-top:2px; }
.pf-alert.success { background:#d1fae5; color:#065f46; }
.pf-alert.danger  { background:#fee2e2; color:#991b1b; }

/* Avatar card */
.pf-hero {
    background: linear-gradient(135deg, #006666, #008080 55%, #20b2aa);
    border-radius:20px; padding:32px 36px;
    display:flex; align-items:center; gap:28px;
    color:white; margin-bottom:20px; position:relative; overflow:hidden;
}
.pf-hero::before {
    content:''; position:absolute; right:-60px; top:-60px;
    width:220px; height:220px; border-radius:50%;
    background:rgba(255,255,255,.07);
}
.pf-hero::after {
    content:''; position:absolute; right:80px; bottom:-80px;
    width:280px; height:280px; border-radius:50%;
    background:rgba(255,255,255,.05);
}
.pf-avatar {
    width:90px; height:90px; border-radius:22px;
    border:3px solid rgba(255,255,255,.3);
    flex-shrink:0; position:relative; z-index:1;
}
.pf-hero-info { position:relative; z-index:1; }
.pf-name  { font-size:1.5rem; font-weight:800; margin:0 0 4px; }
.pf-role  { font-size:.82rem; opacity:.8; margin:0 0 14px; }
.pf-badge {
    display:inline-flex; align-items:center; gap:7px;
    background:rgba(255,255,255,.15); border:1px solid rgba(255,255,255,.25);
    border-radius:20px; padding:5px 14px; font-size:.78rem; font-weight:600;
}

/* Stat mini */
.pf-stats { display:flex; gap:14px; margin-bottom:20px; flex-wrap:wrap; }
.pf-stat {
    flex:1; min-width:140px; background:white; border-radius:14px;
    padding:16px 20px; box-shadow:0 2px 12px rgba(0,0,0,.06);
    display:flex; align-items:center; gap:14px;
}
.pf-stat-icon {
    width:42px; height:42px; border-radius:12px;
    display:flex; align-items:center; justify-content:center;
    font-size:1.1rem; color:white; flex-shrink:0;
}
.pf-stat-num   { font-size:1.5rem; font-weight:800; color:#1f2937; line-height:1; }
.pf-stat-label { font-size:.72rem; color:#9ca3af; margin-top:3px; }

/* Cards */
.pf-card { background:white; border-radius:18px; box-shadow:0 2px 16px rgba(0,0,0,.06); overflow:hidden; margin-bottom:20px; }
.pf-card-header { padding:18px 24px; border-bottom:1px solid #f3f4f6; display:flex; align-items:center; gap:10px; }
.pf-card-header h6 { font-size:.9rem; font-weight:700; color:#1f2937; margin:0; }
.pf-card-body { padding:22px 24px; }

/* Info rows */
.info-row { display:flex; justify-content:space-between; padding:10px 0; border-bottom:1px solid #f9fafb; font-size:.85rem; }
.info-row:last-child { border-bottom:none; }
.info-label { color:#6b7280; font-weight:500; }
.info-value { color:#1f2937; font-weight:600; }

/* Form */
.form-label { font-size:.78rem; font-weight:600; color:#374151; margin-bottom:5px; display:block; }
.form-control {
    border-radius:10px; border:1.5px solid #e5e7eb; font-size:.875rem;
    padding:10px 14px; width:100%; color:#1f2937;
    transition:border-color .2s, box-shadow .2s; background:white;
}
.form-control:focus { border-color:#008080; box-shadow:0 0 0 3px rgba(0,128,128,.1); outline:none; }

/* Password wrapper */
.pw-wrap { position:relative; }
.pw-wrap input { padding-right:44px; }
.pw-toggle {
    position:absolute; right:12px; top:50%; transform:translateY(-50%);
    background:none; border:none; color:#9ca3af; cursor:pointer; font-size:.9rem;
    padding:0; line-height:1; transition:color .2s;
}
.pw-toggle:hover { color:#374151; }

/* Kekuatan password */
.pw-strength { display:flex; gap:4px; margin-top:8px; }
.pw-bar { flex:1; height:3px; border-radius:4px; background:#f3f4f6; transition:background .3s; }
.pw-bar.lemah  { background:#ef4444; }
.pw-bar.sedang { background:#f59e0b; }
.pw-bar.kuat   { background:#22c55e; }
.pw-strength-label { font-size:.72rem; color:#9ca3af; margin-top:5px; }

/* Tombol */
.btn-save {
    background:linear-gradient(135deg,#006666,#008080);
    color:white; border:none; border-radius:12px; padding:11px 28px;
    font-size:.875rem; font-weight:600; cursor:pointer; transition:all .25s;
    display:inline-flex; align-items:center; gap:8px;
}
.btn-save:hover { background:linear-gradient(135deg,#004d4d,#006666); transform:translateY(-1px); box-shadow:0 6px 20px rgba(0,128,128,.3); }

/* Field section */
.field-section {
    font-size:.68rem; font-weight:700; text-transform:uppercase;
    letter-spacing:1.2px; color:#9ca3af; margin:20px 0 12px;
    display:flex; align-items:center; gap:8px;
}
.field-section::after { content:''; flex:1; height:1px; background:#f3f4f6; }
.field-section:first-child { margin-top:0; }
</style>

<div class="pf-wrapper">

    <?php if ($alert): ?>
    <div class="pf-alert <?= $alert_type ?>">
        <i class="fa-solid <?= $alert_type==='success'?'fa-circle-check':'fa-triangle-exclamation' ?>"></i>
        <div><?= $alert ?></div>
    </div>
    <?php endif; ?>

    <!-- ── HERO ── -->
    <div class="pf-hero">
        <img src="https://ui-avatars.com/api/?name=<?= urlencode($nama_user) ?>&background=ffffff&color=008080&size=180&bold=true&rounded=true"
             class="pf-avatar" alt="Avatar">
        <div class="pf-hero-info">
            <div class="pf-name"><?= htmlspecialchars($nama_user) ?></div>
            <div class="pf-role"><?= htmlspecialchars($role_label) ?> · PELITA E-Health</div>
            <div class="pf-badge">
                <i class="fa-solid fa-circle-check"></i> Akun Aktif
                &nbsp;·&nbsp;
                <i class="fa-solid fa-calendar-check"></i> Bergabung <?= $tgl_akun ?>
            </div>
        </div>
    </div>

    <!-- ── STATISTIK KONTRIBUSI ── -->
    <div class="pf-stats">
        <div class="pf-stat">
            <div class="pf-stat-icon" style="background:linear-gradient(135deg,#1d4ed8,#3b82f6)">
                <i class="fa-solid fa-users"></i>
            </div>
            <div>
                <div class="pf-stat-num"><?= $stat_pasien ?></div>
                <div class="pf-stat-label">Total Pasien</div>
            </div>
        </div>
        <div class="pf-stat">
            <div class="pf-stat-icon" style="background:linear-gradient(135deg,#006666,#20b2aa)">
                <i class="fa-solid fa-file-medical"></i>
            </div>
            <div>
                <div class="pf-stat-num"><?= $stat_rekam ?></div>
                <div class="pf-stat-label">Rekam Medis</div>
            </div>
        </div>
        <div class="pf-stat">
            <div class="pf-stat-icon" style="background:linear-gradient(135deg,#7f1d1d,#ef4444)">
                <i class="fa-solid fa-bed-pulse"></i>
            </div>
            <div>
                <div class="pf-stat-num"><?= $stat_lapor ?></div>
                <div class="pf-stat-label">Laporan Kematian</div>
            </div>
        </div>
    </div>

    <!-- ── INFO AKUN ── -->
    <div class="pf-card">
        <div class="pf-card-header">
            <i class="fa-solid fa-id-card" style="color:#008080"></i>
            <h6>Informasi Akun</h6>
        </div>
        <div class="pf-card-body">
            <div class="info-row">
                <span class="info-label">Username</span>
                <span class="info-value" style="font-family:monospace"><?= htmlspecialchars($nama_user) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Role / Jabatan</span>
                <span class="info-value">
                    <span style="background:#e0f2fe;color:#0369a1;padding:3px 12px;border-radius:20px;font-size:.75rem;font-weight:700;">
                        <?= htmlspecialchars($role_label) ?>
                    </span>
                </span>
            </div>
            <div class="info-row">
                <span class="info-label">Sistem</span>
                <span class="info-value">PELITA E-Health · Node Kesehatan</span>
            </div>
            <div class="info-row">
                <span class="info-label">Status Akun</span>
                <span class="info-value">
                    <span style="background:#d1fae5;color:#065f46;padding:3px 12px;border-radius:20px;font-size:.75rem;font-weight:700;">
                        <i class="fa-solid fa-circle-check me-1"></i>Aktif
                    </span>
                </span>
            </div>
            <div class="info-row">
                <span class="info-label">Terakhir Login</span>
                <span class="info-value"><?= date('d M Y, H:i') ?> WIB</span>
            </div>
        </div>
    </div>

    <!-- ── GANTI PASSWORD ── -->
    <div class="pf-card">
        <div class="pf-card-header">
            <i class="fa-solid fa-key" style="color:#008080"></i>
            <h6>Ganti Password</h6>
        </div>
        <div class="pf-card-body">
            <form method="POST" action="" id="formPassword">

                <div class="field-section"><i class="fa-solid fa-lock" style="color:#008080"></i> Verifikasi</div>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Password Lama *</label>
                        <div class="pw-wrap">
                            <input type="password" class="form-control" name="pw_lama"
                                   id="pwLama" placeholder="Masukkan password saat ini" required>
                            <button type="button" class="pw-toggle" onclick="togglePw('pwLama',this)">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="field-section"><i class="fa-solid fa-lock-open" style="color:#008080"></i> Password Baru</div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Password Baru *</label>
                        <div class="pw-wrap">
                            <input type="password" class="form-control" name="pw_baru"
                                   id="pwBaru" placeholder="Minimal 6 karakter"
                                   required oninput="cekKekuatan(this.value)">
                            <button type="button" class="pw-toggle" onclick="togglePw('pwBaru',this)">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                        <!-- Indikator kekuatan -->
                        <div class="pw-strength mt-2">
                            <div class="pw-bar" id="bar1"></div>
                            <div class="pw-bar" id="bar2"></div>
                            <div class="pw-bar" id="bar3"></div>
                        </div>
                        <div class="pw-strength-label" id="pwLabel">Masukkan password baru</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Konfirmasi Password Baru *</label>
                        <div class="pw-wrap">
                            <input type="password" class="form-control" name="pw_ulang"
                                   id="pwUlang" placeholder="Ulangi password baru"
                                   required oninput="cekCocok()">
                            <button type="button" class="pw-toggle" onclick="togglePw('pwUlang',this)">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                        <div class="pw-strength-label" id="cocokLabel" style="margin-top:13px;"></div>
                    </div>
                </div>

                <div style="margin-top:24px;">
                    <button type="submit" name="ganti_password" class="btn-save">
                        <i class="fa-solid fa-floppy-disk"></i> Simpan Password Baru
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
// Toggle show/hide password
function togglePw(id, btn) {
    const el  = document.getElementById(id);
    const ico = btn.querySelector('i');
    if (el.type === 'password') {
        el.type = 'text';
        ico.className = 'fa-solid fa-eye-slash';
    } else {
        el.type = 'password';
        ico.className = 'fa-solid fa-eye';
    }
}

// Indikator kekuatan password
function cekKekuatan(val) {
    const b1=document.getElementById('bar1'),
          b2=document.getElementById('bar2'),
          b3=document.getElementById('bar3'),
          lbl=document.getElementById('pwLabel');
    b1.className=b2.className=b3.className='pw-bar';
    if (!val) { lbl.textContent='Masukkan password baru'; lbl.style.color='#9ca3af'; return; }
    let score=0;
    if (val.length>=6)  score++;
    if (val.length>=10) score++;
    if (/[A-Z]/.test(val)&&/[0-9]/.test(val)) score++;
    if (score===1) {
        b1.classList.add('lemah');
        lbl.textContent='Lemah — tambahkan angka atau huruf kapital';
        lbl.style.color='#ef4444';
    } else if (score===2) {
        b1.classList.add('sedang'); b2.classList.add('sedang');
        lbl.textContent='Sedang — coba tambahkan karakter lebih banyak';
        lbl.style.color='#f59e0b';
    } else if (score===3) {
        b1.classList.add('kuat'); b2.classList.add('kuat'); b3.classList.add('kuat');
        lbl.textContent='Kuat — password bagus!';
        lbl.style.color='#22c55e';
    }
}

// Cek kecocokan konfirmasi
function cekCocok() {
    const baru  = document.getElementById('pwBaru').value;
    const ulang = document.getElementById('pwUlang').value;
    const lbl   = document.getElementById('cocokLabel');
    if (!ulang) { lbl.textContent=''; return; }
    if (baru === ulang) {
        lbl.textContent = '✓ Password cocok';
        lbl.style.color = '#22c55e';
    } else {
        lbl.textContent = '✗ Password tidak cocok';
        lbl.style.color = '#ef4444';
    }
}
</script>