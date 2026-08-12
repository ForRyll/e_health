<?php
// ============================================================
// home.php — Dashboard Utama PELITA E-Health
// Semua data dari DB — tidak ada hardcode
// ============================================================
require_once 'koneksi.php';
// ── STAT CARDS ──────────────────────────────────────────────
$total_pasien = $total_rekam = $total_lapor_mati = $total_api_hari_ini = 0;

$q = mysqli_query($conn, "SELECT COUNT(*) c FROM pasien");
if ($q) $total_pasien = (int)mysqli_fetch_assoc($q)['c'];

$q = mysqli_query($conn, "SELECT COUNT(*) c FROM rekam_medis");
if ($q) $total_rekam = (int)mysqli_fetch_assoc($q)['c'];

$q = mysqli_query($conn, "SELECT COUNT(*) c FROM laporan_kematian");
if ($q) $total_lapor_mati = (int)mysqli_fetch_assoc($q)['c'];

$q = mysqli_query($conn, "SELECT COUNT(*) c FROM log_api WHERE DATE(waktu) = CURDATE()");
if ($q) $total_api_hari_ini = (int)mysqli_fetch_assoc($q)['c'];

// ── CHART: Registrasi pasien per bulan (tahun ini, dari DB) ─
$bulan_labels = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
$data_chart   = array_fill(0, 12, 0);

$q = mysqli_query($conn,
    "SELECT MONTH(tanggal_daftar) as bln, COUNT(*) as total
     FROM pasien
     WHERE YEAR(tanggal_daftar) = YEAR(CURDATE())
     GROUP BY MONTH(tanggal_daftar)"
);
if ($q) while ($r = mysqli_fetch_assoc($q)) $data_chart[(int)$r['bln'] - 1] = (int)$r['total'];

// ── CHART: Rekam medis per bulan (tahun ini) ────────────────
$data_rekam_chart = array_fill(0, 12, 0);
$q = mysqli_query($conn,
    "SELECT MONTH(tanggal_periksa) as bln, COUNT(*) as total
     FROM rekam_medis
     WHERE YEAR(tanggal_periksa) = YEAR(CURDATE())
     GROUP BY MONTH(tanggal_periksa)"
);
if ($q) while ($r = mysqli_fetch_assoc($q)) $data_rekam_chart[(int)$r['bln'] - 1] = (int)$r['total'];

// ── LOG API dari DB (beneran) ────────────────────────────────
$log_api_db = [];
$q = mysqli_query($conn,
    "SELECT * FROM log_api ORDER BY waktu DESC LIMIT 6"
);
if ($q) while ($r = mysqli_fetch_assoc($q)) $log_api_db[] = $r;

// ── PASIEN TERBARU ───────────────────────────────────────────
$pasien_baru = [];
$q = mysqli_query($conn,
    "SELECT p.nik, p.nama, p.jenis_kelamin, p.tanggal_daftar,
            COUNT(rm.id_pemeriksaan) as jml_rekam
     FROM pasien p
     LEFT JOIN rekam_medis rm ON rm.nik_pasien = p.nik
     GROUP BY p.nik
     ORDER BY p.tanggal_daftar DESC LIMIT 4"
);
if ($q) while ($r = mysqli_fetch_assoc($q)) $pasien_baru[] = $r;

// ── STATUS SEHAT vs TIDAK (dari rekam medis terbaru) ────────
$jml_sehat = $jml_tidak_sehat = 0;
$q = mysqli_query($conn,
    "SELECT status_penyakit, COUNT(*) c
     FROM (
         SELECT nik_pasien, status_penyakit,
                ROW_NUMBER() OVER (PARTITION BY nik_pasien ORDER BY tanggal_periksa DESC) rn
         FROM rekam_medis
     ) t WHERE rn = 1
     GROUP BY status_penyakit"
);
if ($q) while ($r = mysqli_fetch_assoc($q)) {
    if ($r['status_penyakit'] === 'Sehat') $jml_sehat = (int)$r['c'];
    else $jml_tidak_sehat += (int)$r['c'];
}

// ── GREETING ─────────────────────────────────────────────────
$jam      = (int)date('H');
$greeting = $jam < 11 ? 'Selamat Pagi' : ($jam < 15 ? 'Selamat Siang' : ($jam < 18 ? 'Selamat Sore' : 'Selamat Malam'));
$nama_user= $_SESSION['user'] ?? 'Petugas Medis';
$hari_id  = ['Sunday'=>'Minggu','Monday'=>'Senin','Tuesday'=>'Selasa','Wednesday'=>'Rabu','Thursday'=>'Kamis','Friday'=>'Jumat','Saturday'=>'Sabtu'];
$bln_id   = ['January'=>'Januari','February'=>'Februari','March'=>'Maret','April'=>'April','May'=>'Mei','June'=>'Juni','July'=>'Juli','August'=>'Agustus','September'=>'September','October'=>'Oktober','November'=>'November','December'=>'Desember'];
$tgl = date('l, d F Y');
foreach ($hari_id as $en => $id) $tgl = str_replace($en, $id, $tgl);
foreach ($bln_id  as $en => $id) $tgl = str_replace($en, $id, $tgl);

// Helper: waktu relatif
function rel($waktu) {
    $d = time() - strtotime($waktu);
    if ($d < 60)    return $d . ' dtk lalu';
    if ($d < 3600)  return floor($d/60) . ' mnt lalu';
    if ($d < 86400) return floor($d/3600) . ' jam lalu';
    return date('d M', strtotime($waktu));
}

// Icon & warna per action
function action_icon($action) {
    return [
        'ping'             => ['fa-satellite-dish', '#6366f1'],
        'surat_sehat'      => ['fa-file-medical',   '#0E9E8D'],
        'status_kesehatan' => ['fa-heart-pulse',    '#0E9E8D'],
        'data_pasien'      => ['fa-id-card',        '#3b82f6'],
        'list_pasien'      => ['fa-list',           '#f59e0b'],
        'GET_MEDICAL_DATA' => ['fa-stethoscope',    '#0E9E8D'],
    ][$action] ?? ['fa-rotate', '#9ca3af'];
}
?>

<style>
/* ── Greeting ── */
.hw-greeting {
    background: linear-gradient(135deg, #0D1B2A 0%, #1A2E42 50%, #0B4D44 100%);
    border-radius: 20px; padding: 28px 36px; color: white;
    position: relative; overflow: hidden; margin-bottom: 22px;
    border: 1px solid rgba(14,158,141,.2);
}
.hw-greeting::before {
    content: '';
    position: absolute; right: -60px; top: -60px;
    width: 240px; height: 240px; border-radius: 50%;
    background: radial-gradient(circle, rgba(14,158,141,.18) 0%, transparent 70%);
}
.hw-greeting::after {
    content: '';
    position: absolute; right: 120px; bottom: -80px;
    width: 300px; height: 300px; border-radius: 50%;
    background: rgba(14,158,141,.06);
}
.hw-greeting-icon {
    position: absolute; right: 36px; top: 50%; transform: translateY(-50%);
    font-size: 5.5rem; opacity: .07; z-index: 0;
}

/* ── Stat cards ── */
.hw-stat {
    background: white; border-radius: 16px; padding: 22px 22px 18px;
    box-shadow: 0 2px 16px rgba(0,0,0,.06); position: relative;
    overflow: hidden; height: 100%;
    transition: transform .25s, box-shadow .25s;
    border: 1px solid rgba(0,0,0,.04);
}
.hw-stat:hover { transform: translateY(-4px); box-shadow: 0 12px 32px rgba(0,0,0,.1); }
.hw-stat-label { font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1.2px; color: #9ca3af; margin-bottom: 10px; }
.hw-stat-val   { font-size: 2.4rem; font-weight: 800; line-height: 1; margin-bottom: 10px; }
.hw-stat-sub   { font-size: .75rem; display: flex; align-items: center; gap: 5px; }
.hw-stat-bg    { position: absolute; right: -10px; bottom: -14px; font-size: 5rem; opacity: .06; }
.hw-stat-bar   { position: absolute; bottom: 0; left: 0; right: 0; height: 3px; border-radius: 0 0 16px 16px; }

/* ── Quick actions ── */
.hw-quick {
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    background: white; border-radius: 14px; padding: 20px 10px;
    box-shadow: 0 2px 12px rgba(0,0,0,.05);
    text-decoration: none; color: #374151;
    transition: all .25s; border: 1.5px solid #f3f4f6;
    gap: 8px; font-size: .78rem; font-weight: 600; text-align: center; height: 100%;
}
.hw-quick:hover { transform: translateY(-3px); border-color: #0E9E8D; color: #0B7B6E; box-shadow: 0 8px 24px rgba(14,158,141,.15); }
.hw-quick i { font-size: 1.5rem; }

/* ── Panel card ── */
.hw-panel {
    background: white; border-radius: 16px; padding: 22px 24px;
    box-shadow: 0 2px 16px rgba(0,0,0,.06); height: 100%;
    border: 1px solid rgba(0,0,0,.04);
}
.hw-panel-title { font-size: .9rem; font-weight: 700; color: #1f2937; margin-bottom: 3px; }
.hw-panel-sub   { font-size: .74rem; color: #9ca3af; margin-bottom: 16px; }

/* ── Log API item ── */
.log-item {
    display: flex; align-items: flex-start; gap: 11px;
    padding: 10px 0; border-bottom: 1px solid #f9fafb;
}
.log-item:last-child { border-bottom: none; }
.log-icon {
    width: 32px; height: 32px; border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-size: .78rem; color: white; flex-shrink: 0;
}
.log-node  { font-size: .72rem; font-weight: 700; color: #6b7280; }
.log-desc  { font-size: .82rem; color: #374151; line-height: 1.4; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 240px; }
.log-time  { font-size: .7rem; color: #9ca3af; margin-top: 2px; }
.log-badge { font-size: .65rem; font-weight: 700; padding: 1px 7px; border-radius: 20px; }
.log-badge.success { background: #d1fae5; color: #065f46; }
.log-badge.error, .log-badge.unauthorized { background: #fee2e2; color: #991b1b; }
.log-badge.not_found { background: #fef3c7; color: #92400e; }

/* ── Pasien terbaru ── */
.px-item {
    display: flex; align-items: center; gap: 12px;
    padding: 10px 0; border-bottom: 1px solid #f9fafb;
}
.px-item:last-child { border-bottom: none; }
.px-avatar {
    width: 36px; height: 36px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 13px; font-weight: 700; color: white; flex-shrink: 0;
}
.px-name  { font-size: .84rem; font-weight: 600; color: #1f2937; }
.px-meta  { font-size: .72rem; color: #9ca3af; }

/* ── Section title ── */
.sec-title {
    font-size: .72rem; font-weight: 700; color: #9ca3af;
    text-transform: uppercase; letter-spacing: 1.5px;
    margin-bottom: 14px; display: flex; align-items: center; gap: 10px;
}
.sec-title::after { content: ''; flex: 1; height: 1px; background: #f3f4f6; }

/* ── Node status ── */
.node-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.node-item {
    display: flex; align-items: center; gap: 10px;
    background: #f9fafb; border-radius: 12px;
    padding: 12px 14px; border: 1px solid #f0f0f0;
    transition: border-color .2s;
}
.node-item:hover { border-color: #ccefeb; }
.node-icon-wrap { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: .9rem; flex-shrink: 0; }
.node-name  { font-size: .82rem; font-weight: 600; color: #374151; margin-bottom: 3px; }
.node-badge { font-size: .67rem; padding: 2px 8px; border-radius: 20px; font-weight: 700; display: inline-flex; align-items: center; gap: 4px; }
.node-badge.online  { background: #dcfce7; color: #15803d; }
.node-badge.offline { background: #f3f4f6; color: #9ca3af; }
.pulse-dot { width: 5px; height: 5px; border-radius: 50%; display: inline-block; }
.pulse-dot.online  { background: #22c55e; animation: pdot 1.5s infinite; }
.pulse-dot.offline { background: #d1d5db; }
@keyframes pdot { 0%,100%{box-shadow:0 0 0 2px rgba(34,197,94,.3)} 50%{box-shadow:0 0 0 5px rgba(34,197,94,.05)} }

/* ── Donut chart wrap ── */
.donut-wrap { position: relative; width: 120px; height: 120px; margin: 0 auto 12px; }
.donut-center { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; }
.donut-num  { font-size: 1.4rem; font-weight: 800; color: #1f2937; line-height: 1; }
.donut-label{ font-size: .65rem; color: #9ca3af; font-weight: 500; }
</style>

<!-- ══════════════════════════════════════
     GREETING
══════════════════════════════════════ -->
<div class="hw-greeting">
    <i class="fa-solid fa-heart-pulse hw-greeting-icon"></i>
    <div style="position:relative;z-index:1;">
        <p style="opacity:.65;font-size:.8rem;margin-bottom:6px;">
            <i class="fa-regular fa-calendar me-1"></i><?= $tgl ?>
            &nbsp;·&nbsp;
            <i class="fa-regular fa-clock me-1"></i><span id="jam-live"><?= date('H:i:s') ?></span>
        </p>
        <h3 style="font-family:'Syne',sans-serif;font-weight:800;font-size:1.45rem;margin-bottom:6px;">
            <?= $greeting ?>, <?= htmlspecialchars($nama_user) ?>! 👋
        </h3>
        <p style="opacity:.65;font-size:.83rem;margin:0;display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
            Sistem <strong>PELITA E-Health</strong> berjalan normal.
            <span style="background:rgba(14,158,141,.25);border:1px solid rgba(14,158,141,.3);padding:3px 12px;border-radius:20px;font-size:.72rem;font-weight:600;color:#5DCFC2;">
                <i class="fa-solid fa-circle-check me-1"></i>Semua Modul Aktif
            </span>
        </p>
    </div>
</div>

<!-- ══════════════════════════════════════
     STAT CARDS
══════════════════════════════════════ -->
<p class="sec-title">Ringkasan Data</p>
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="hw-stat">
            <div class="hw-stat-label">Total Pasien</div>
            <div class="hw-stat-val" style="color:#3b82f6;"><?= number_format($total_pasien) ?></div>
            <div class="hw-stat-sub" style="color:#6b7280;">
                <i class="fa-solid fa-database" style="color:#3b82f6;"></i> Tersimpan di DB
            </div>
            <i class="fa-solid fa-users hw-stat-bg" style="color:#3b82f6;"></i>
            <div class="hw-stat-bar" style="background:#3b82f6;"></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="hw-stat">
            <div class="hw-stat-label">Rekam Medis</div>
            <div class="hw-stat-val" style="color:#0E9E8D;"><?= number_format($total_rekam) ?></div>
            <div class="hw-stat-sub" style="color:#6b7280;">
                <i class="fa-solid fa-file-medical" style="color:#0E9E8D;"></i> Siap ditarik via API
            </div>
            <i class="fa-solid fa-stethoscope hw-stat-bg" style="color:#0E9E8D;"></i>
            <div class="hw-stat-bar" style="background:linear-gradient(90deg,#0B7B6E,#0AD4BC);"></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="hw-stat">
            <div class="hw-stat-label">Request API Hari Ini</div>
            <div class="hw-stat-val" style="color:#7c3aed;"><?= number_format($total_api_hari_ini) ?></div>
            <div class="hw-stat-sub" style="color:#6b7280;">
                <i class="fa-solid fa-rotate" style="color:#7c3aed;"></i> Request masuk ke node ini
            </div>
            <i class="fa-solid fa-satellite-dish hw-stat-bg" style="color:#7c3aed;"></i>
            <div class="hw-stat-bar" style="background:#7c3aed;"></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="hw-stat">
            <div class="hw-stat-label">Laporan Kematian</div>
            <div class="hw-stat-val" style="color:#ef4444;"><?= number_format($total_lapor_mati) ?></div>
            <div class="hw-stat-sub" style="color:#6b7280;">
                <i class="fa-solid fa-paper-plane" style="color:#ef4444;"></i> Terkirim ke Induk
            </div>
            <i class="fa-solid fa-book-skull hw-stat-bg" style="color:#ef4444;"></i>
            <div class="hw-stat-bar" style="background:#ef4444;"></div>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════
     QUICK ACTIONS
══════════════════════════════════════ -->
<p class="sec-title">Aksi Cepat</p>
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <a href="index.php?page=registrasi" class="hw-quick">
            <i class="fa-solid fa-user-plus" style="color:#0E9E8D;"></i>Daftar Pasien Baru
        </a>
    </div>
    <div class="col-6 col-md-3">
        <a href="index.php?page=surat_sehat" class="hw-quick">
            <i class="fa-solid fa-stethoscope" style="color:#3b82f6;"></i>Input Rekam Medis
        </a>
    </div>
    <div class="col-6 col-md-3">
        <a href="index.php?page=lihat_data" class="hw-quick">
            <i class="fa-solid fa-table-list" style="color:#f59e0b;"></i>Arsip Data Pasien
        </a>
    </div>
    <div class="col-6 col-md-3">
        <a href="index.php?page=lapor_mati" class="hw-quick">
            <i class="fa-solid fa-bed-pulse" style="color:#ef4444;"></i>Lapor Kematian
        </a>
    </div>
</div>

<!-- ══════════════════════════════════════
     CHART + LOG API
══════════════════════════════════════ -->
<p class="sec-title">Analitik &amp; Aktivitas API</p>
<div class="row g-3 mb-4">

    <!-- Chart -->
    <div class="col-lg-7">
        <div class="hw-panel">
            <div class="hw-panel-title"><i class="fa-solid fa-chart-column me-2" style="color:#0E9E8D;"></i>Tren Registrasi &amp; Rekam Medis</div>
            <div class="hw-panel-sub">Data aktual dari database · Tahun <?= date('Y') ?></div>
            <canvas id="chartPasien" height="110"></canvas>
        </div>
    </div>

    <!-- Log API real -->
    <div class="col-lg-5">
        <div class="hw-panel d-flex flex-column">
            <div class="hw-panel-title"><i class="fa-solid fa-rotate me-2" style="color:#0E9E8D;"></i>Log Aktivitas API</div>
            <div class="hw-panel-sub">Request terbaru masuk ke node ini</div>
            <div class="flex-grow-1">
                <?php if (empty($log_api_db)): ?>
                <div style="text-align:center;padding:24px 0;color:#9ca3af;font-size:.82rem;">
                    <i class="fa-solid fa-satellite-dish fa-2x d-block mb-2" style="opacity:.3;"></i>
                    Belum ada aktivitas API
                </div>
                <?php else: foreach ($log_api_db as $log):
                    [$lic, $lbg] = action_icon($log['action']);
                    $st = $log['status'];
                ?>
                <div class="log-item">
                    <div class="log-icon" style="background:<?= $lbg ?>;">
                        <i class="fa-solid <?= $lic ?>"></i>
                    </div>
                    <div style="min-width:0;flex:1;">
                        <div class="log-node">
                            <?= htmlspecialchars($log['node_asal']) ?>
                            <span class="log-badge <?= $st ?> ms-1"><?= ucfirst(str_replace('_',' ',$st)) ?></span>
                        </div>
                        <div class="log-desc"><?= htmlspecialchars($log['keterangan'] ?: $log['action']) ?></div>
                        <div class="log-time"><i class="fa-regular fa-clock me-1"></i><?= rel($log['waktu']) ?></div>
                    </div>
                </div>
                <?php endforeach; endif; ?>
            </div>
            <div class="text-center mt-3">
                <a href="index.php?page=log_api" class="btn btn-sm btn-outline-secondary" style="border-radius:20px;font-size:.76rem;">
                    Lihat Semua Log <i class="fa-solid fa-arrow-right ms-1"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════
     PASIEN TERBARU + STATUS KESEHATAN + NODE
══════════════════════════════════════ -->
<p class="sec-title">Detail &amp; Status Node</p>
<div class="row g-3 mb-2">

    <!-- Pasien terbaru -->
    <div class="col-md-4">
        <div class="hw-panel">
            <div class="hw-panel-title"><i class="fa-solid fa-user-clock me-2" style="color:#0E9E8D;"></i>Pasien Terbaru</div>
            <div class="hw-panel-sub">4 pendaftaran terakhir</div>
            <?php if (empty($pasien_baru)): ?>
                <div style="text-align:center;padding:20px 0;color:#9ca3af;font-size:.82rem;">Belum ada data</div>
            <?php else:
                $colors = ['#0E9E8D','#3b82f6','#7c3aed','#f59e0b'];
                foreach ($pasien_baru as $i => $p):
                    $inisial = strtoupper(mb_substr($p['nama'], 0, 1));
                    $jml_r   = (int)$p['jml_rekam'];
            ?>
            <div class="px-item">
                <div class="px-avatar" style="background:<?= $colors[$i % 4] ?>;"><?= $inisial ?></div>
                <div style="min-width:0;flex:1;">
                    <div class="px-name"><?= htmlspecialchars($p['nama']) ?></div>
                    <div class="px-meta">
                        <?= htmlspecialchars(mb_strimwidth($p['nik'], 0, 8, ''))  ?>...<?= mb_substr($p['nik'], -4) ?>
                        &nbsp;·&nbsp;
                        <?= $jml_r ?> rekam medis
                    </div>
                </div>
                <div style="font-size:.7rem;color:#9ca3af;white-space:nowrap;"><?= rel($p['tanggal_daftar']) ?></div>
            </div>
            <?php endforeach; endif; ?>
        </div>
    </div>

    <!-- Donut status kesehatan -->
    <div class="col-md-4">
        <div class="hw-panel text-center">
            <div class="hw-panel-title"><i class="fa-solid fa-circle-half-stroke me-2" style="color:#0E9E8D;"></i>Status Kesehatan Pasien</div>
            <div class="hw-panel-sub">Berdasarkan rekam medis terbaru</div>
            <div class="donut-wrap">
                <canvas id="chartDonut"></canvas>
                <div class="donut-center">
                    <div class="donut-num"><?= $jml_sehat + $jml_tidak_sehat ?></div>
                    <div class="donut-label">terperiksa</div>
                </div>
            </div>
            <div style="display:flex;justify-content:center;gap:20px;margin-top:8px;">
                <div style="text-align:center;">
                    <div style="font-size:1.2rem;font-weight:800;color:#22c55e;"><?= $jml_sehat ?></div>
                    <div style="font-size:.7rem;color:#9ca3af;">Sehat</div>
                </div>
                <div style="text-align:center;">
                    <div style="font-size:1.2rem;font-weight:800;color:#ef4444;"><?= $jml_tidak_sehat ?></div>
                    <div style="font-size:.7rem;color:#9ca3af;">Butuh Perhatian</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Node status -->
    <div class="col-md-4">
        <div class="hw-panel">
            <div class="hw-panel-title"><i class="fa-solid fa-network-wired me-2" style="color:#0E9E8D;"></i>Status Koneksi Node</div>
            <div class="hw-panel-sub">Jaringan e-Government Terpadu</div>
            <div class="node-grid">
                <?php
                $nodes_list = [
                    ['nama'=>'Kependudukan','ikon'=>'fa-landmark-dome',       'on'=>true,  'warna'=>'#0E9E8D'],
                    ['nama'=>'Kepolisian',  'ikon'=>'fa-shield-halved',       'on'=>true,  'warna'=>'#3b82f6'],
                    ['nama'=>'Bantuan Sosial','ikon'=>'fa-hand-holding-heart','on'=>true,  'warna'=>'#f59e0b'],
                    ['nama'=>'Pendidikan',  'ikon'=>'fa-graduation-cap',      'on'=>false, 'warna'=>'#9ca3af'],
                ];
                foreach ($nodes_list as $n):
                    $cls = $n['on'] ? 'online' : 'offline';
                    $bg  = $n['on'] ? 'rgba(14,158,141,.1)' : '#f3f4f6';
                ?>
                <div class="node-item">
                    <div class="node-icon-wrap" style="background:<?= $bg ?>;">
                        <i class="fa-solid <?= $n['ikon'] ?>" style="color:<?= $n['on'] ? $n['warna'] : '#9ca3af' ?>;"></i>
                    </div>
                    <div>
                        <div class="node-name"><?= $n['nama'] ?></div>
                        <span class="node-badge <?= $cls ?>">
                            <span class="pulse-dot <?= $cls ?>"></span>
                            <?= $n['on'] ? 'Online' : 'Offline' ?>
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

</div>

<!-- ══════════════════════════════════════
     SCRIPTS
══════════════════════════════════════ -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Jam live
setInterval(() => {
    const n = new Date();
    document.getElementById('jam-live').textContent =
        String(n.getHours()).padStart(2,'0') + ':' +
        String(n.getMinutes()).padStart(2,'0') + ':' +
        String(n.getSeconds()).padStart(2,'0');
}, 1000);

// Chart bar — data dari DB
const labels  = <?= json_encode($bulan_labels) ?>;
const dPasien = <?= json_encode(array_values($data_chart)) ?>;
const dRekam  = <?= json_encode(array_values($data_rekam_chart)) ?>;

new Chart(document.getElementById('chartPasien'), {
    type: 'bar',
    data: {
        labels,
        datasets: [
            {
                label: 'Pasien Baru',
                data: dPasien,
                backgroundColor: ctx => {
                    const g = ctx.chart.ctx.createLinearGradient(0,0,0,160);
                    g.addColorStop(0,'rgba(14,158,141,.55)');
                    g.addColorStop(1,'rgba(14,158,141,.05)');
                    return g;
                },
                borderColor: '#0E9E8D', borderWidth: 2,
                borderRadius: 6, borderSkipped: false,
            },
            {
                label: 'Rekam Medis',
                data: dRekam,
                type: 'line',
                borderColor: '#3b82f6', borderWidth: 2,
                pointBackgroundColor: '#3b82f6',
                pointRadius: 3, tension: .4, fill: false,
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: true,
                position: 'top',
                labels: { font: { size: 11 }, boxWidth: 12, padding: 16 }
            },
            tooltip: {
                backgroundColor: '#1f2937', padding: 10, cornerRadius: 8,
            }
        },
        scales: {
            x: { grid: { display: false }, border: { display: false }, ticks: { font: { size: 11 } } },
            y: { grid: { color: '#f3f4f6' }, border: { display: false }, beginAtZero: true, ticks: { font: { size: 11 }, stepSize: 1 } }
        }
    }
});

// Donut chart status kesehatan
new Chart(document.getElementById('chartDonut'), {
    type: 'doughnut',
    data: {
        labels: ['Sehat', 'Butuh Perhatian'],
        datasets: [{
            data: [<?= $jml_sehat ?>, <?= max($jml_tidak_sehat, 0) ?>],
            backgroundColor: ['#22c55e', '#ef4444'],
            borderWidth: 0,
            hoverOffset: 4,
        }]
    },
    options: {
        cutout: '72%',
        plugins: { legend: { display: false }, tooltip: { cornerRadius: 8 } },
        animation: { animateRotate: true, duration: 800 }
    }
});
</script>