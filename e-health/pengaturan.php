<?php
// ================================================================
// pengaturan.php — Halaman Pengaturan Sistem PELITA E-Health
// Partial — di-include dari index.php
// ================================================================
if (!isset($conn)) require_once 'koneksi.php';

// Info DB
$db_status  = $conn ? 'Terhubung' : 'Gagal';
$total_user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM users"))['c'] ?? 0;
$total_pasien = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM pasien"))['c'] ?? 0;
$total_rekam  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM rekam_medis"))['c'] ?? 0;

// Daftar API Key
$api_keys = [
    ['key'=>'POLISI-KEY-2024-EHEALTH',  'node'=>'Sistem Kepolisian',     'level'=>'polisi',    'icon'=>'fa-shield-halved',      'color'=>'#1d4ed8'],
    ['key'=>'BANSOS-KEY-2024-EHEALTH',  'node'=>'Sistem Bantuan Sosial', 'level'=>'bansos',    'icon'=>'fa-hand-holding-heart', 'color'=>'#d97706'],
    ['key'=>'INDUK-KEY-2024-EHEALTH',   'node'=>'Sistem Induk (E-Gov)',  'level'=>'admin',     'icon'=>'fa-landmark',           'color'=>'#008080'],
    ['key'=>'DIDIK-KEY-2024-EHEALTH',   'node'=>'Sistem Pendidikan',     'level'=>'pendidikan','icon'=>'fa-graduation-cap',     'color'=>'#7c3aed'],
    ['key'=>'DEV-KEY-TESTING-12345',    'node'=>'Developer / Testing',   'level'=>'admin',     'icon'=>'fa-code',               'color'=>'#6b7280'],
];

// Daftar endpoint API
$endpoints = [
    ['method'=>'GET','path'=>'api.php?action=ping',                          'desc'=>'Cek status API apakah aktif','akses'=>'Semua'],
    ['method'=>'GET','path'=>'api.php?action=surat_sehat&nik={NIK}',         'desc'=>'Ambil data surat sehat untuk SKCK','akses'=>'Kepolisian'],
    ['method'=>'GET','path'=>'api.php?action=status_kesehatan&nik={NIK}',    'desc'=>'Status kesehatan untuk verifikasi bansos','akses'=>'Bansos'],
    ['method'=>'GET','path'=>'api.php?action=data_pasien&nik={NIK}',         'desc'=>'Data pasien lengkap','akses'=>'Admin/Induk'],
    ['method'=>'GET','path'=>'api.php?action=list_pasien&limit=20',          'desc'=>'Daftar semua pasien (max 100)','akses'=>'Admin/Induk'],
];
?>

<style>
.pg-section { font-size:.75rem; font-weight:700; color:#9ca3af; text-transform:uppercase; letter-spacing:1.2px; margin:24px 0 14px; display:flex; align-items:center; gap:8px; }
.pg-section::after { content:''; flex:1; height:1px; background:#f3f4f6; }
.pg-card { background:white; border-radius:16px; padding:22px 24px; box-shadow:0 2px 14px rgba(0,0,0,.06); margin-bottom:16px; }
.pg-card-title { font-size:.9rem; font-weight:700; color:#1f2937; margin-bottom:4px; }
.pg-card-sub   { font-size:.78rem; color:#9ca3af; margin-bottom:18px; }

/* Info grid */
.info-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(180px,1fr)); gap:12px; }
.info-item { background:#f9fafb; border-radius:12px; padding:14px 16px; }
.info-label { font-size:.7rem; color:#9ca3af; font-weight:700; text-transform:uppercase; letter-spacing:.8px; margin-bottom:4px; }
.info-val   { font-size:.95rem; font-weight:700; color:#1f2937; }
.info-val.ok   { color:#059669; }
.info-val.err  { color:#dc2626; }

/* API Key cards */
.key-card {
    border:1.5px solid #f3f4f6; border-radius:14px; padding:16px 18px;
    display:flex; align-items:center; gap:14px; transition:border-color .2s;
    margin-bottom:10px;
}
.key-card:hover { border-color:#008080; }
.key-icon { width:42px; height:42px; border-radius:11px; display:flex; align-items:center; justify-content:center; font-size:1rem; color:white; flex-shrink:0; }
.key-node  { font-size:.88rem; font-weight:700; color:#1f2937; margin-bottom:3px; }
.key-level { font-size:.7rem; font-weight:600; padding:2px 8px; border-radius:20px; background:#f3f4f6; color:#6b7280; display:inline-block; margin-bottom:6px; }
.key-token {
    font-family:monospace; font-size:.78rem; background:#f9fafb;
    border:1px solid #e5e7eb; border-radius:8px; padding:6px 10px;
    color:#374151; cursor:pointer; transition:all .2s; user-select:all;
    display:flex; align-items:center; justify-content:space-between; gap:8px;
}
.key-token:hover { border-color:#008080; background:#f0fdf9; }
.copy-hint { font-size:.65rem; color:#9ca3af; white-space:nowrap; }

/* Endpoint table */
.ep-table { width:100%; border-collapse:collapse; font-size:.82rem; }
.ep-table th { padding:10px 14px; background:#f9fafb; color:#6b7280; font-size:.7rem; text-transform:uppercase; letter-spacing:.8px; font-weight:700; text-align:left; border-bottom:1px solid #f3f4f6; }
.ep-table td { padding:12px 14px; border-bottom:1px solid #f9fafb; vertical-align:middle; color:#374151; }
.ep-table tr:last-child td { border-bottom:none; }
.ep-table tr:hover td { background:#fafffe; }
.method-badge { display:inline-block; padding:2px 8px; border-radius:6px; font-size:.7rem; font-weight:700; background:#d1fae5; color:#065f46; font-family:monospace; }
.ep-path { font-family:monospace; font-size:.78rem; color:#374151; background:#f9fafb; padding:4px 8px; border-radius:6px; }
.akses-badge { font-size:.7rem; padding:2px 8px; border-radius:20px; font-weight:600; }
.akses-semua   { background:#e0f2fe; color:#0369a1; }
.akses-polisi  { background:#eff6ff; color:#1d4ed8; }
.akses-bansos  { background:#fef3c7; color:#92400e; }
.akses-admin   { background:#dcfce7; color:#15803d; }

/* Node status */
.node-status-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(200px,1fr)); gap:12px; }
.node-status-card {
    border:1.5px solid #f3f4f6; border-radius:14px; padding:16px;
    display:flex; align-items:center; gap:12px;
}
.node-dot { width:10px; height:10px; border-radius:50%; flex-shrink:0; }
.node-dot.on  { background:#22c55e; box-shadow:0 0 0 3px rgba(34,197,94,.2); animation:npulse 1.5s infinite; }
.node-dot.off { background:#d1d5db; }
@keyframes npulse { 0%,100%{box-shadow:0 0 0 3px rgba(34,197,94,.2);} 50%{box-shadow:0 0 0 6px rgba(34,197,94,.05);} }
.node-sname { font-size:.85rem; font-weight:700; color:#1f2937; }
.node-surl  { font-size:.72rem; color:#9ca3af; font-family:monospace; }
.node-sbadge { font-size:.68rem; font-weight:700; margin-top:3px; }

/* Toast */
.toast-copy { position:fixed; bottom:24px; right:24px; background:#1f2937; color:white; padding:10px 18px; border-radius:10px; font-size:.82rem; font-weight:600; z-index:9999; opacity:0; transform:translateY(10px); transition:all .3s; pointer-events:none; }
.toast-copy.show { opacity:1; transform:translateY(0); }
</style>

<!-- ═══ INFO SISTEM ═══ -->
<p class="pg-section"><i class="fa-solid fa-circle-info" style="color:#008080;"></i> Info Sistem & Database</p>
<div class="pg-card">
    <div class="pg-card-title">Status Sistem PELITA E-Health</div>
    <div class="pg-card-sub">Informasi runtime server dan koneksi database</div>
    <div class="info-grid">
        <div class="info-item">
            <div class="info-label">Status Database</div>
            <div class="info-val ok"><i class="fa-solid fa-circle-check me-1"></i><?= $db_status ?></div>
        </div>
        <div class="info-item">
            <div class="info-label">Versi PHP</div>
            <div class="info-val"><?= phpversion() ?></div>
        </div>
        <div class="info-item">
            <div class="info-label">Server</div>
            <div class="info-val"><?= $_SERVER['SERVER_SOFTWARE'] ?? 'Apache/XAMPP' ?></div>
        </div>
        <div class="info-item">
            <div class="info-label">Host</div>
            <div class="info-val"><?= $_SERVER['HTTP_HOST'] ?? 'localhost' ?></div>
        </div>
        <div class="info-item">
            <div class="info-label">Total User</div>
            <div class="info-val"><?= $total_user ?> akun</div>
        </div>
        <div class="info-item">
            <div class="info-label">Total Pasien</div>
            <div class="info-val"><?= $total_pasien ?> pasien</div>
        </div>
        <div class="info-item">
            <div class="info-label">Total Rekam Medis</div>
            <div class="info-val"><?= $total_rekam ?> data</div>
        </div>
        <div class="info-item">
            <div class="info-label">Waktu Server</div>
            <div class="info-val" style="font-size:.82rem;"><?= date('d M Y H:i:s') ?></div>
        </div>
    </div>
</div>

<!-- ═══ STATUS NODE ═══ -->
<p class="pg-section"><i class="fa-solid fa-network-wired" style="color:#008080;"></i> Status Koneksi Node Terdistribusi</p>
<div class="pg-card">
    <div class="pg-card-title">Topologi Jaringan E-Government</div>
    <div class="pg-card-sub">Status real-time koneksi antar node sistem terdistribusi</div>
    <div class="node-status-grid">
        <?php
        $nodes = [
            ['nama'=>'Kependudukan (Induk)', 'url'=>'localhost/e-penduduk',   'on'=>true,  'ikon'=>'fa-landmark',          'c'=>'#008080'],
            ['nama'=>'Kesehatan (Node Ini)', 'url'=>'localhost/e-health',     'on'=>true,  'ikon'=>'fa-heart-pulse',       'c'=>'#059669'],
            ['nama'=>'Kepolisian',           'url'=>'localhost/e-polisi',     'on'=>true,  'ikon'=>'fa-shield-halved',     'c'=>'#1d4ed8'],
            ['nama'=>'Pendidikan',           'url'=>'localhost/e-pendidikan', 'on'=>false, 'ikon'=>'fa-graduation-cap',    'c'=>'#7c3aed'],
            ['nama'=>'Bantuan Sosial',       'url'=>'localhost/e-bansos',     'on'=>true,  'ikon'=>'fa-hand-holding-heart','c'=>'#d97706'],
        ];
        foreach ($nodes as $n):
        ?>
        <div class="node-status-card">
            <div style="width:36px;height:36px;border-radius:10px;background:<?= $n['on']?'rgba(0,128,128,.1)':'#f3f4f6' ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fa-solid <?= $n['ikon'] ?>" style="color:<?= $n['on']?$n['c']:'#9ca3af' ?>;font-size:.9rem;"></i>
            </div>
            <div style="flex:1;min-width:0;">
                <div class="d-flex align-items-center gap-2">
                    <div class="node-dot <?= $n['on']?'on':'off' ?>"></div>
                    <div class="node-sname"><?= $n['nama'] ?></div>
                </div>
                <div class="node-surl"><?= $n['url'] ?></div>
                <div class="node-sbadge <?= $n['on']?'text-success':'text-secondary' ?>">
                    <?= $n['on'] ? '● Online' : '○ Offline' ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- ═══ API KEYS ═══ -->
<p class="pg-section"><i class="fa-solid fa-key" style="color:#008080;"></i> Manajemen API Key</p>
<div class="pg-card">
    <div class="pg-card-title">API Key per Node</div>
    <div class="pg-card-sub">Klik token untuk menyalin. Gunakan sebagai header <code>X-API-KEY</code> atau query param <code>&api_key=...</code></div>
    <?php foreach ($api_keys as $k): ?>
    <div class="key-card">
        <div class="key-icon" style="background:<?= $k['color'] ?>;"><i class="fa-solid <?= $k['icon'] ?>"></i></div>
        <div style="flex:1;min-width:0;">
            <div class="key-node"><?= $k['node'] ?></div>
            <span class="key-level"><?= $k['level'] ?></span>
            <div class="key-token" onclick="salinKey('<?= $k['key'] ?>', this)" title="Klik untuk salin">
                <span><?= $k['key'] ?></span>
                <span class="copy-hint"><i class="fa-regular fa-copy"></i> salin</span>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- ═══ ENDPOINT DOCS ═══ -->
<p class="pg-section"><i class="fa-solid fa-book" style="color:#008080;"></i> Dokumentasi Endpoint API</p>
<div class="pg-card">
    <div class="pg-card-title">Daftar Endpoint Tersedia</div>
    <div class="pg-card-sub">Base URL: <code>http://<?= $_SERVER['HTTP_HOST'] ?>/e-health/</code> &nbsp;·&nbsp; Format response: JSON</div>
    <div style="overflow-x:auto;">
    <table class="ep-table">
        <thead>
            <tr>
                <th>Method</th>
                <th>Path</th>
                <th>Deskripsi</th>
                <th>Akses</th>
                <th>Coba</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($endpoints as $ep):
            $akses_cls = match(true) {
                $ep['akses']==='Semua'        => 'akses-semua',
                $ep['akses']==='Kepolisian'   => 'akses-polisi',
                $ep['akses']==='Bansos'       => 'akses-bansos',
                default                       => 'akses-admin',
            };
            // Build test URL dengan DEV KEY
            $test_url = $ep['path'].'&api_key=DEV-KEY-TESTING-12345';
            $test_url = str_replace('{NIK}', '3301234567890001', $test_url);
        ?>
        <tr>
            <td><span class="method-badge"><?= $ep['method'] ?></span></td>
            <td><span class="ep-path"><?= htmlspecialchars($ep['path']) ?></span></td>
            <td style="font-size:.8rem;color:#6b7280;"><?= $ep['desc'] ?></td>
            <td><span class="akses-badge <?= $akses_cls ?>"><?= $ep['akses'] ?></span></td>
            <td>
                <a href="<?= htmlspecialchars($test_url) ?>" target="_blank"
                   class="btn btn-sm" style="font-size:.72rem;padding:3px 10px;border-radius:6px;background:#f0fdf9;color:#008080;border:1px solid #008080;font-weight:600;">
                    <i class="fa-solid fa-arrow-up-right-from-square me-1"></i>Test
                </a>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>

<!-- Toast notif -->
<div class="toast-copy" id="toastCopy"><i class="fa-solid fa-check me-2"></i>API Key disalin!</div>

<script>
function salinKey(key, el) {
    navigator.clipboard.writeText(key).then(() => {
        const t = document.getElementById('toastCopy');
        t.classList.add('show');
        setTimeout(() => t.classList.remove('show'), 2000);
    });
}
</script>