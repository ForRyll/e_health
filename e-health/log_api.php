<?php
// ============================================================
// log_api.php — Monitor Log Aktivitas API Antar-Node
// Dipanggil via include dari index.php
// ============================================================

require_once 'koneksi.php';

// KUNCI ZONA WAKTU KE INDONESIA AGAR TIDAK MINUS
date_default_timezone_set('Asia/Jakarta');

// Pastikan tabel ada
mysqli_query($conn, "
    CREATE TABLE IF NOT EXISTS log_api (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        node_asal   VARCHAR(100) NOT NULL,
        action      VARCHAR(50)  NOT NULL,
        nik_request VARCHAR(16)  DEFAULT NULL,
        status      ENUM('success','not_found','unauthorized','error') NOT NULL,
        keterangan  TEXT         DEFAULT NULL,
        ip_address  VARCHAR(45)  DEFAULT NULL,
        waktu       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// ── Filter ──
$f_node   = trim($_GET['node']   ?? '');
$f_status = trim($_GET['status'] ?? '');
$f_action = trim($_GET['act']    ?? '');

$where = ["1=1"];
if ($f_node)   $where[] = "node_asal = '" . mysqli_real_escape_string($conn, $f_node)   . "'";
if ($f_status) $where[] = "status = '"    . mysqli_real_escape_string($conn, $f_status) . "'";
if ($f_action) $where[] = "action = '"    . mysqli_real_escape_string($conn, $f_action) . "'";
$where_sql = implode(' AND ', $where);

// ── Data log ──
$logs = [];
$q = mysqli_query($conn, "SELECT * FROM log_api WHERE $where_sql ORDER BY waktu DESC LIMIT 100");
if ($q) while ($r = mysqli_fetch_assoc($q)) $logs[] = $r;

// ── Statistik ──
$stat = ['total'=>0,'success'=>0,'not_found'=>0,'unauthorized'=>0,'error'=>0];
$qs = mysqli_query($conn, "SELECT status, COUNT(*) c FROM log_api GROUP BY status");
if ($qs) while ($r = mysqli_fetch_assoc($qs)) {
    $stat[$r['status']] = (int)$r['c'];
    $stat['total'] += (int)$r['c'];
}

// Stat 1 jam terakhir
$q1h = mysqli_query($conn, "SELECT COUNT(*) c FROM log_api WHERE waktu >= NOW() - INTERVAL 1 HOUR");
$stat_1h = $q1h ? (int)mysqli_fetch_assoc($q1h)['c'] : 0;

// Node paling aktif
$q_node = mysqli_query($conn, "SELECT node_asal, COUNT(*) c FROM log_api GROUP BY node_asal ORDER BY c DESC LIMIT 1");
$top_node = $q_node && mysqli_num_rows($q_node) > 0 ? mysqli_fetch_assoc($q_node)['node_asal'] : '—';

// List unik node & action untuk filter dropdown
$nodes   = [];
$actions = [];
$qn = mysqli_query($conn, "SELECT DISTINCT node_asal FROM log_api ORDER BY node_asal");
if ($qn) while ($r = mysqli_fetch_assoc($qn)) $nodes[] = $r['node_asal'];
$qa = mysqli_query($conn, "SELECT DISTINCT action FROM log_api ORDER BY action");
if ($qa) while ($r = mysqli_fetch_assoc($qa)) $actions[] = $r['action'];

// ── FUNGSI HELPER WAKTU YANG SUDAH DIPERBAIKI (ANTI MINUS) ──
function rel_time($waktu) {
    if (empty($waktu) || $waktu === '0000-00-00 00:00:00') {
        return 'Waktu tidak valid';
    }

    $diff = time() - strtotime($waktu);

    // Jika waktu MySQL seolah lebih maju (hasilnya minus), kita anggap saja itu baru terjadi (0 detik)
    if ($diff < 0) {
        return 'Baru saja';
    }

    if ($diff < 60)        return $diff . ' dtk lalu';
    if ($diff < 3600)      return floor($diff/60) . ' mnt lalu';
    if ($diff < 86400)     return floor($diff/3600) . ' jam lalu';
    return floor($diff/86400) . ' hari lalu';
}
?>

<style>
.log-wrapper { max-width: 100%; }

/* ── STAT CARDS ── */
.log-stats { display:flex; gap:14px; margin-bottom:22px; flex-wrap:wrap; }
.log-stat {
    flex:1; min-width:130px; background:white; border-radius:14px;
    padding:16px 20px; box-shadow:0 2px 12px rgba(0,0,0,.06);
    display:flex; align-items:center; gap:14px; border-left:3px solid transparent;
}
.log-stat.s-all     { border-color:#008080; }
.log-stat.s-success { border-color:#22c55e; }
.log-stat.s-warn    { border-color:#f59e0b; }
.log-stat.s-err     { border-color:#ef4444; }
.log-stat.s-info    { border-color:#3b82f6; }

.ls-icon {
    width:40px; height:40px; border-radius:11px; flex-shrink:0;
    display:flex; align-items:center; justify-content:center;
    font-size:1rem; color:white;
}
.s-all     .ls-icon { background:linear-gradient(135deg,#006666,#008080); }
.s-success .ls-icon { background:linear-gradient(135deg,#15803d,#22c55e); }
.s-warn    .ls-icon { background:linear-gradient(135deg,#b45309,#f59e0b); }
.s-err     .ls-icon { background:linear-gradient(135deg,#991b1b,#ef4444); }
.s-info    .ls-icon { background:linear-gradient(135deg,#1d4ed8,#3b82f6); }

.ls-num   { font-size:1.6rem; font-weight:800; color:#1f2937; line-height:1; }
.ls-label { font-size:.7rem; color:#9ca3af; font-weight:500; margin-top:3px; }

/* ── TOOLBAR ── */
.log-toolbar {
    background:white; border-radius:14px; padding:14px 18px;
    box-shadow:0 2px 12px rgba(0,0,0,.06); margin-bottom:16px;
    display:flex; gap:10px; flex-wrap:wrap; align-items:center;
}
.log-select {
    border:1.5px solid #e5e7eb; border-radius:9px; padding:8px 12px;
    font-size:.825rem; color:#374151; background:white; cursor:pointer;
    transition:border-color .2s;
}
.log-select:focus { border-color:#008080; outline:none; }
.btn-filter {
    background:#008080; color:white; border:none; border-radius:9px;
    padding:8px 16px; font-size:.825rem; font-weight:600; cursor:pointer;
    transition:all .2s; display:flex; align-items:center; gap:6px;
}
.btn-filter:hover { background:#006666; }
.btn-reset {
    background:#f3f4f6; color:#6b7280; border:none; border-radius:9px;
    padding:8px 14px; font-size:.825rem; cursor:pointer;
    text-decoration:none; display:flex; align-items:center; gap:6px;
    transition:all .2s;
}
.btn-reset:hover { background:#e5e7eb; }

.api-url-box {
    margin-left:auto; background:#f8fafc; border:1px solid #e5e7eb;
    border-radius:9px; padding:8px 14px; font-size:.78rem;
    color:#374151; font-family:monospace; display:flex; align-items:center; gap:8px;
}
.api-url-box i { color:#008080; }

/* ── LOG TABLE ── */
.log-card { background:white; border-radius:16px; box-shadow:0 2px 16px rgba(0,0,0,.06); overflow:hidden; }
.log-table { width:100%; border-collapse:collapse; font-size:.8rem; }
.log-table th {
    padding:10px 14px; background:#f9fafb; color:#6b7280;
    font-size:.68rem; text-transform:uppercase; letter-spacing:.8px;
    font-weight:700; text-align:left; border-bottom:1px solid #f3f4f6; white-space:nowrap;
}
.log-table td { padding:11px 14px; border-top:1px solid #f3f4f6; vertical-align:middle; }
.log-table tr:hover td { background:#f9fafb; }

/* Row warna per status */
.log-table tr.row-success td { }
.log-table tr.row-error    td { background:#fff8f8; }
.log-table tr.row-unauth   td { background:#fffbf0; }

/* Badge status */
.bs { padding:3px 10px; border-radius:20px; font-size:.7rem; font-weight:700; display:inline-flex; align-items:center; gap:5px; }
.bs-success { background:#d1fae5; color:#065f46; }
.bs-not_found { background:#fef3c7; color:#92400e; }
.bs-unauthorized { background:#fee2e2; color:#991b1b; }
.bs-error { background:#fee2e2; color:#991b1b; }

/* Badge action */
.ba { padding:3px 10px; border-radius:8px; font-size:.7rem; font-weight:600; font-family:monospace; }
.ba-ping             { background:#eff6ff; color:#1d4ed8; }
.ba-surat_sehat      { background:#f0fdf4; color:#15803d; }
.ba-status_kesehatan { background:#f0fdf9; color:#0b7b6e; }
.ba-data_pasien      { background:#fdf4ff; color:#7c3aed; }
.ba-list_pasien      { background:#fff7ed; color:#c2410c; }
.ba-unknown          { background:#f3f4f6; color:#6b7280; }

/* Node chip */
.node-chip-sm {
    display:inline-flex; align-items:center; gap:6px;
    font-size:.75rem; font-weight:600; color:#374151;
}
.node-dot { width:7px; height:7px; border-radius:50%; flex-shrink:0; }

/* ID col */
.log-id { color:#d1d5db; font-size:.72rem; font-family:monospace; }

/* Empty */
.log-empty { text-align:center; padding:60px 20px; color:#9ca3af; }
.log-empty i { font-size:2.5rem; margin-bottom:12px; display:block; opacity:.4; }

/* Footer info */
.log-footer { padding:12px 18px; border-top:1px solid #f3f4f6; font-size:.75rem; color:#9ca3af; display:flex; justify-content:space-between; align-items:center; }

/* Auto-refresh badge */
.refresh-badge {
    display:flex; align-items:center; gap:6px;
    font-size:.72rem; color:#008080; font-weight:600;
}
.refresh-dot { width:7px; height:7px; border-radius:50%; background:#22c55e; animation:blink 1.5s infinite; }
@keyframes blink { 0%,100%{opacity:1} 50%{opacity:.3} }

/* Endpoint docs box */
.endpoint-box {
    background:var(--navy,#0D1B2A); border-radius:16px; padding:24px 28px;
    margin-top:20px; color:white;
}
.endpoint-box h6 { font-size:.85rem; font-weight:700; margin:0 0 16px; color:rgba(255,255,255,.7); letter-spacing:.5px; }
.ep-row { display:flex; align-items:flex-start; gap:14px; padding:10px 0; border-bottom:1px solid rgba(255,255,255,.06); }
.ep-row:last-child { border-bottom:none; }
.ep-method { font-family:monospace; font-size:.72rem; font-weight:700; padding:3px 9px; border-radius:6px; background:rgba(14,158,141,.2); color:#5DCFC2; flex-shrink:0; margin-top:1px; }
.ep-url { font-family:monospace; font-size:.78rem; color:rgba(255,255,255,.7); flex:1; line-height:1.4; word-break:break-all; }
.ep-desc { font-size:.75rem; color:rgba(255,255,255,.35); margin-top:3px; }
.key-row { display:flex; flex-wrap:wrap; gap:8px; margin-top:16px; }
.key-chip { background:rgba(255,255,255,.06); border:1px solid rgba(255,255,255,.1); border-radius:8px; padding:7px 14px; font-family:monospace; font-size:.75rem; color:rgba(255,255,255,.6); }
.key-chip strong { color:#5DCFC2; }
</style>

<div class="log-wrapper">

    <div class="log-stats">
        <div class="log-stat s-all">
            <div class="ls-icon"><i class="fa-solid fa-rotate"></i></div>
            <div><div class="ls-num"><?= number_format($stat['total']) ?></div><div class="ls-label">Total Request</div></div>
        </div>
        <div class="log-stat s-success">
            <div class="ls-icon"><i class="fa-solid fa-circle-check"></i></div>
            <div><div class="ls-num"><?= number_format($stat['success']) ?></div><div class="ls-label">Sukses</div></div>
        </div>
        <div class="log-stat s-warn">
            <div class="ls-icon"><i class="fa-solid fa-circle-question"></i></div>
            <div><div class="ls-num"><?= number_format($stat['not_found']) ?></div><div class="ls-label">Not Found</div></div>
        </div>
        <div class="log-stat s-err">
            <div class="ls-icon"><i class="fa-solid fa-circle-xmark"></i></div>
            <div><div class="ls-num"><?= number_format($stat['unauthorized'] + $stat['error']) ?></div><div class="ls-label">Error / Unauth</div></div>
        </div>
        <div class="log-stat s-info">
            <div class="ls-icon"><i class="fa-solid fa-clock"></i></div>
            <div><div class="ls-num"><?= $stat_1h ?></div><div class="ls-label">1 Jam Terakhir</div></div>
        </div>
    </div>

    <form method="GET" action="">
        <input type="hidden" name="page" value="log_api">
        <div class="log-toolbar">
            <select class="log-select" name="node">
                <option value="">Semua Node</option>
                <?php foreach($nodes as $n): ?>
                <option value="<?= htmlspecialchars($n) ?>" <?= $f_node===$n?'selected':'' ?>><?= htmlspecialchars($n) ?></option>
                <?php endforeach; ?>
            </select>

            <select class="log-select" name="status">
                <option value="">Semua Status</option>
                <option value="success"      <?= $f_status==='success'?'selected':'' ?>>✓ Sukses</option>
                <option value="not_found"    <?= $f_status==='not_found'?'selected':'' ?>>? Not Found</option>
                <option value="unauthorized" <?= $f_status==='unauthorized'?'selected':'' ?>>✗ Unauthorized</option>
                <option value="error"        <?= $f_status==='error'?'selected':'' ?>>✗ Error</option>
            </select>

            <select class="log-select" name="act">
                <option value="">Semua Action</option>
                <?php foreach($actions as $a): ?>
                <option value="<?= htmlspecialchars($a) ?>" <?= $f_action===$a?'selected':'' ?>><?= htmlspecialchars($a) ?></option>
                <?php endforeach; ?>
            </select>

            <button type="submit" class="btn-filter"><i class="fa-solid fa-filter"></i> Filter</button>
            <?php if($f_node||$f_status||$f_action): ?>
            <a href="?page=log_api" class="btn-reset"><i class="fa-solid fa-xmark"></i> Reset</a>
            <?php endif; ?>

            <div class="api-url-box">
                <i class="fa-solid fa-link"></i>
                <span><?= htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'localhost') ?>/e-health/api.php</span>
            </div>
        </div>
    </form>

    <div class="log-card">
        <?php if (empty($logs)): ?>
        <div class="log-empty">
            <i class="fa-solid fa-satellite-dish"></i>
            <p style="font-size:.875rem;">Belum ada log aktivitas API<?= ($f_node||$f_status||$f_action) ? ' untuk filter ini' : '' ?>.</p>
            <p style="font-size:.78rem;margin-top:6px;">Log akan muncul otomatis saat ada node lain yang mengakses endpoint API.</p>
        </div>
        <?php else: ?>
        <div style="overflow-x:auto;">
        <table class="log-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Waktu</th>
                    <th>Node Peminta</th>
                    <th>Action</th>
                    <th>NIK</th>
                    <th>Status</th>
                    <th>Keterangan</th>
                    <th>IP</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach($logs as $log):
                $row_cls = match($log['status']) {
                    'error','unauthorized' => 'row-error',
                    'not_found'            => 'row-unauth',
                    default                => 'row-success',
                };
                $act_cls = 'ba-' . (in_array($log['action'],['ping','surat_sehat','status_kesehatan','data_pasien','list_pasien']) ? $log['action'] : 'unknown');

                // Warna dot per node
                $node_colors = [
                    'Node Kepolisian'       => '#3b82f6',
                    'Node Bantuan Sosial'   => '#f59e0b',
                    'Node Kependudukan'     => '#0b7b6e',
                    'Node Pendidikan'       => '#7c3aed',
                    'Node Internal'         => '#6b7280',
                    'Node Dinas Sosial'     => '#f59e0b',
                ];
                $dot_color = $node_colors[$log['node_asal']] ?? '#9ca3af';
            ?>
            <tr class="<?= $row_cls ?>">
                <td class="log-id"><?= $log['id'] ?></td>
                <td>
                    <div style="font-size:.8rem;font-weight:600;color:#1f2937;"><?= date('H:i:s', strtotime($log['waktu'])) ?></div>
                    <div style="font-size:.7rem;color:#9ca3af;"><?= date('d M Y', strtotime($log['waktu'])) ?></div>
                </td>
                <td>
                    <div class="node-chip-sm">
                        <span class="node-dot" style="background:<?= $dot_color ?>"></span>
                        <?= htmlspecialchars($log['node_asal']) ?>
                    </div>
                    <?php if($log['ip_address']): ?>
                    <div style="font-size:.7rem;color:#d1d5db;margin-top:2px;font-family:monospace;"><?= htmlspecialchars($log['ip_address']) ?></div>
                    <?php endif; ?>
                </td>
                <td><span class="ba <?= $act_cls ?>"><?= htmlspecialchars($log['action']) ?></span></td>
                <td style="font-family:monospace;font-size:.75rem;color:#6b7280;">
                    <?= $log['nik_request']
                        ? substr($log['nik_request'],0,6).'..'.substr($log['nik_request'],-4)
                        : '<span style="color:#e5e7eb;">—</span>' ?>
                </td>
                <td>
                    <span class="bs bs-<?= $log['status'] ?>">
                        <?php
                        $icons = ['success'=>'fa-check','not_found'=>'fa-question','unauthorized'=>'fa-lock','error'=>'fa-xmark'];
                        echo '<i class="fa-solid '.($icons[$log['status']]??'fa-xmark').'"></i>';
                        echo ucfirst(str_replace('_',' ',$log['status']));
                        ?>
                    </span>
                </td>
                <td style="font-size:.78rem;color:#6b7280;max-width:240px;">
                    <?= htmlspecialchars(mb_strimwidth($log['keterangan'] ?? '—', 0, 80, '…')) ?>
                </td>
                <td style="font-size:.7rem;color:#9ca3af;font-family:monospace;white-space:nowrap;">
                    <?= rel_time($log['waktu']) ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <div class="log-footer">
            <span>Menampilkan <?= count($logs) ?> log terbaru<?= ($f_node||$f_status||$f_action)?' (difilter)':' (maks. 100)' ?></span>
            <div class="refresh-badge">
                <span class="refresh-dot"></span>
                Auto-refresh tiap 30 detik
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="endpoint-box">
        <h6><i class="fa-solid fa-code me-2"></i>DOKUMENTASI ENDPOINT API</h6>
        <div class="ep-row">
            <span class="ep-method">GET</span>
            <div>
                <div class="ep-url">api.php?action=ping</div>
                <div class="ep-desc">Cek status node — tidak perlu API key</div>
            </div>
        </div>
        <div class="ep-row">
            <span class="ep-method">GET</span>
            <div>
                <div class="ep-url">api.php?action=data_pasien&nik={NIK}&api_key={KEY}</div>
                <div class="ep-desc">Ambil data identitas pasien berdasarkan NIK</div>
            </div>
        </div>
        <div class="ep-row">
            <span class="ep-method">GET</span>
            <div>
                <div class="ep-url">api.php?action=surat_sehat&nik={NIK}&api_key={KEY}</div>
                <div class="ep-desc">Ambil rekam medis terbaru + status kelayakan SKCK — untuk Node Kepolisian</div>
            </div>
        </div>
        <div class="ep-row">
            <span class="ep-method">GET</span>
            <div>
                <div class="ep-url">api.php?action=status_kesehatan&nik={NIK}&api_key={KEY}</div>
                <div class="ep-desc">Cek status penyakit + prioritas bansos — untuk Node Bantuan Sosial</div>
            </div>
        </div>
        <div class="ep-row">
            <span class="ep-method">GET</span>
            <div>
                <div class="ep-url">api.php?action=list_pasien&api_key={KEY}&limit=50&offset=0</div>
                <div class="ep-desc">Ambil daftar semua pasien (max 200 per request)</div>
            </div>
        </div>

        <div style="margin-top:18px;font-size:.72rem;color:rgba(255,255,255,.35);letter-spacing:1px;text-transform:uppercase;font-weight:700;">API Keys</div>
        <div class="key-row">
            <div class="key-chip"><strong>POLISI-KEY-2024</strong> · Node Kepolisian</div>
            <div class="key-chip"><strong>BANSOS-KEY-2024</strong> · Node Bantuan Sosial</div>
            <div class="key-chip"><strong>DUKCAPIL-KEY-2024</strong> · Node Kependudukan</div>
            <div class="key-chip"><strong>PENDIDIKAN-KEY-2024</strong> · Node Pendidikan</div>
        </div>
    </div>

</div>

<script>
// Auto-refresh tiap 30 detik (hanya jika tidak ada filter aktif)
<?php if(!$f_node && !$f_status && !$f_action): ?>
setTimeout(() => location.reload(), 30000);
<?php endif; ?>
</script>