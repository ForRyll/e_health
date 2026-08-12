<?php
// ================================================================
// index.php — Shell Utama PELITA E-Health v3.0
// Redesign: Dark sidebar, modern navbar, konsisten dengan login.php
// ================================================================
session_start();
if (!isset($_SESSION['user'])) { header("Location: login.php"); exit; }
require 'koneksi.php';

$page = isset($_GET['page']) ? $_GET['page'] : 'home';

$judul_map = [
    'home'        => 'Overview Dashboard',
    'registrasi'  => 'Pendaftaran Pasien Baru',
    'surat_sehat' => 'Rekam Medis & Surat Sehat',
    'lapor_mati'  => 'Laporan Kematian',
    'lihat_data'  => 'Arsip Data Pasien',
    'log_api'     => 'Log Aktivitas API',
    'pengaturan'  => 'Pengaturan Sistem',
    'profil'      => 'Profil Saya',
];
$page_title = $judul_map[$page] ?? ucfirst(str_replace('_', ' ', $page));

// ── Notifikasi API ───────────────────────────────────────────────
$notif_logs   = [];
$notif_unread = 0;
$tbl_check = mysqli_query($conn, "SHOW TABLES LIKE 'log_api'");
if ($tbl_check && mysqli_num_rows($tbl_check) > 0) {
    $nr = mysqli_query($conn, "SELECT * FROM log_api ORDER BY waktu DESC LIMIT 5");
    if ($nr) while ($row = mysqli_fetch_assoc($nr)) $notif_logs[] = $row;
    $ur = mysqli_query($conn, "SELECT COUNT(*) c FROM log_api WHERE waktu >= NOW() - INTERVAL 1 HOUR");
    if ($ur) $notif_unread = mysqli_fetch_assoc($ur)['c'] ?? 0;
}
$notif_status_map = [
    'success'      => ['icon'=>'fa-check-circle',    'bg'=>'#22c55e', 'label'=>'Sukses'],
    'not_found'    => ['icon'=>'fa-circle-question', 'bg'=>'#f59e0b', 'label'=>'Tidak Ditemukan'],
    'unauthorized' => ['icon'=>'fa-lock',            'bg'=>'#ef4444', 'label'=>'Unauthorized'],
    'error'        => ['icon'=>'fa-circle-xmark',    'bg'=>'#ef4444', 'label'=>'Error'],
];
$action_icon_map = [
    'ping'             => 'fa-satellite-dish',
    'surat_sehat'      => 'fa-file-medical',
    'status_kesehatan' => 'fa-heart-pulse',
    'data_pasien'      => 'fa-id-card',
    'list_pasien'      => 'fa-list',
];

// ── Nav menu definition ──────────────────────────────────────────
$nav_items = [
    ['page'=>'home',        'icon'=>'fa-border-all',           'label'=>'Dashboard'],
    ['page'=>'registrasi',  'icon'=>'fa-user-plus',            'label'=>'Pendaftaran Pasien'],
    ['page'=>'surat_sehat', 'icon'=>'fa-stethoscope',          'label'=>'Rekam Medis & SK'],
    ['page'=>'lapor_mati',  'icon'=>'fa-bed-pulse',            'label'=>'Lapor Kematian', 'danger'=>true],
    ['page'=>'lihat_data',  'icon'=>'fa-table-list',           'label'=>'Arsip Pasien'],
    ['page'=>'log_api',     'icon'=>'fa-rotate',               'label'=>'Log Aktivitas API'],
];
$nav_bottom = [
    ['page'=>'pengaturan',  'icon'=>'fa-gear',                 'label'=>'Pengaturan'],
    ['url' =>'logout.php',  'icon'=>'fa-arrow-right-from-bracket', 'label'=>'Keluar', 'danger'=>true],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PELITA E-Health · <?= htmlspecialchars($page_title) ?></title>
    <!-- Same fonts as login -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600;9..40,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
        --navy:       #0D1B2A;
        --navy-mid:   #1A2E42;
        --navy-light: #243547;
        --teal:       #0B7B6E;
        --teal-mid:   #0E9E8D;
        --teal-glow:  rgba(14,158,141,.15);
        --teal-text:  #0AD4BC;
        --surface:    #F0F4F8;
        --white:      #FFFFFF;
        --muted:      #8A9BAD;
        --slate:      #4A6278;
        --danger:     #EF5350;
        --sidebar-w:  240px;
    }

    html, body { height: 100%; font-family: 'DM Sans', sans-serif; }
    body { background: var(--surface); color: var(--navy); overflow-x: hidden; }

    /* ═══════════════════════════════
       SIDEBAR
    ═══════════════════════════════ */
    #sidebar {
        position: fixed; top: 0; left: 0; bottom: 0;
        width: var(--sidebar-w);
        background: var(--navy);
        display: flex; flex-direction: column;
        z-index: 1000;
        overflow: hidden;
    }

    /* Decorative grid overlay on sidebar */
    #sidebar::before {
        content: '';
        position: absolute; inset: 0; pointer-events: none;
        background-image:
            linear-gradient(rgba(14,158,141,.06) 1px, transparent 1px),
            linear-gradient(90deg, rgba(14,158,141,.06) 1px, transparent 1px);
        background-size: 32px 32px;
        mask-image: radial-gradient(ellipse 140% 80% at 20% 20%, black 0%, transparent 70%);
    }

    /* Top glow orb */
    #sidebar::after {
        content: '';
        position: absolute; top: -80px; left: -80px;
        width: 280px; height: 280px;
        background: radial-gradient(circle, rgba(14,158,141,.2) 0%, transparent 65%);
        pointer-events: none;
    }

    /* Brand */
    .sb-brand {
        padding: 24px 22px 20px;
        display: flex; align-items: center; gap: 12px;
        position: relative; z-index: 1;
        border-bottom: 1px solid rgba(255,255,255,.06);
    }
    .sb-brand-icon {
        width: 38px; height: 38px; border-radius: 11px;
        background: linear-gradient(135deg, var(--teal-mid), var(--teal-text));
        display: flex; align-items: center; justify-content: center;
        color: white; font-size: 16px; flex-shrink: 0;
        box-shadow: 0 4px 14px rgba(14,158,141,.4);
    }
    .sb-brand-text { line-height: 1.2; }
    .sb-brand-name {
        font-family: 'Syne', sans-serif;
        font-weight: 800; font-size: 15px; color: white; letter-spacing: .3px;
    }
    .sb-brand-sub { font-size: 10.5px; color: rgba(255,255,255,.35); font-weight: 400; }

    /* Nav section label */
    .sb-section-label {
        font-size: 9.5px; font-weight: 700; letter-spacing: 2px;
        text-transform: uppercase; color: rgba(255,255,255,.2);
        padding: 18px 22px 8px; position: relative; z-index: 1;
    }

    /* Nav items */
    .sb-nav { flex: 1; overflow-y: auto; padding: 8px 12px; position: relative; z-index: 1; }
    .sb-nav::-webkit-scrollbar { width: 0; }

    .sb-item {
        display: flex; align-items: center; gap: 11px;
        padding: 10px 12px; border-radius: 10px; margin-bottom: 2px;
        text-decoration: none; color: rgba(255,255,255,.45);
        font-size: 13.5px; font-weight: 500;
        transition: all .22s ease; position: relative;
        border: 1px solid transparent;
    }
    .sb-item:hover {
        color: rgba(255,255,255,.85);
        background: rgba(255,255,255,.06);
        border-color: rgba(255,255,255,.07);
    }
    .sb-item.active {
        color: var(--teal-text) !important;
        background: var(--teal-glow);
        border-color: rgba(14,158,141,.25);
        font-weight: 600;
    }
    .sb-item.active .sb-icon { color: var(--teal-text); }
    .sb-item.danger { color: rgba(239,83,80,.6); }
    .sb-item.danger:hover { color: var(--danger); background: rgba(239,83,80,.08); border-color: rgba(239,83,80,.15); }
    .sb-item.active.danger { color: var(--danger) !important; background: rgba(239,83,80,.1); border-color: rgba(239,83,80,.2); }

    .sb-icon {
        width: 32px; height: 32px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        font-size: 13px; flex-shrink: 0;
        background: rgba(255,255,255,.05);
        transition: all .22s;
    }
    .sb-item:hover .sb-icon { background: rgba(255,255,255,.09); }
    .sb-item.active .sb-icon { background: rgba(14,158,141,.2); }
    .sb-item.danger .sb-icon { background: rgba(239,83,80,.07); }
    .sb-item.danger:hover .sb-icon { background: rgba(239,83,80,.13); }

    /* Active indicator pill */
    .sb-item.active::before {
        content: '';
        position: absolute; left: -12px; top: 50%;
        transform: translateY(-50%);
        width: 3px; height: 22px; border-radius: 0 3px 3px 0;
        background: var(--teal-text);
    }
    .sb-item.danger.active::before { background: var(--danger); }

    /* Sidebar divider */
    .sb-divider { height: 1px; background: rgba(255,255,255,.07); margin: 10px 0; }

    /* Sidebar bottom */
    .sb-bottom { padding: 10px 12px 20px; position: relative; z-index: 1; border-top: 1px solid rgba(255,255,255,.06); }

    /* Node status strip */
    .sb-node-strip {
        margin: 0 12px 14px;
        background: rgba(255,255,255,.03);
        border: 1px solid rgba(255,255,255,.07);
        border-radius: 10px; padding: 10px 12px;
        position: relative; z-index: 1;
    }
    .sb-node-title { font-size: 9px; font-weight: 700; letter-spacing: 1.8px; text-transform: uppercase; color: rgba(255,255,255,.2); margin-bottom: 8px; }
    .sb-node-row { display: flex; align-items: center; gap: 7px; margin-bottom: 5px; }
    .sb-node-row:last-child { margin-bottom: 0; }
    .sb-node-dot { width: 5px; height: 5px; border-radius: 50%; flex-shrink: 0; }
    .sb-node-dot.on  { background: #22c55e; box-shadow: 0 0 4px #22c55e; animation: dot-pulse 2s infinite; }
    .sb-node-dot.off { background: rgba(255,255,255,.2); }
    @keyframes dot-pulse { 0%,100%{opacity:1} 50%{opacity:.4} }
    .sb-node-name { font-size: 10.5px; color: rgba(255,255,255,.4); flex: 1; }
    .sb-node-badge { font-size: 9px; font-weight: 700; padding: 1px 6px; border-radius: 20px; }
    .sb-node-badge.on  { background: rgba(34,197,94,.15); color: #4ade80; }
    .sb-node-badge.off { background: rgba(255,255,255,.07); color: rgba(255,255,255,.25); }

    /* ═══════════════════════════════
       MAIN CONTENT
    ═══════════════════════════════ */
    #content {
        margin-left: var(--sidebar-w);
        min-height: 100vh;
        display: flex; flex-direction: column;
    }

    /* ═══════════════════════════════
       NAVBAR
    ═══════════════════════════════ */
    #topbar {
        position: sticky; top: 0; z-index: 900;
        background: rgba(240,244,248,.85);
        backdrop-filter: blur(16px) saturate(180%);
        -webkit-backdrop-filter: blur(16px) saturate(180%);
        border-bottom: 1px solid rgba(0,0,0,.06);
        padding: 0 28px;
        height: 64px;
        display: flex; align-items: center; justify-content: space-between;
        gap: 16px;
    }

    /* Breadcrumb-style title */
    .topbar-title {
        display: flex; align-items: center; gap: 10px;
    }
    .topbar-back {
        width: 32px; height: 32px; border-radius: 8px;
        background: white; border: 1px solid rgba(0,0,0,.08);
        display: flex; align-items: center; justify-content: center;
        color: var(--slate); font-size: 12px;
        text-decoration: none; transition: all .2s;
        box-shadow: 0 1px 4px rgba(0,0,0,.06);
    }
    .topbar-back:hover { background: var(--teal-mid); color: white; border-color: var(--teal-mid); }
    .topbar-node-badge {
        display: inline-flex; align-items: center; gap: 5px;
        background: var(--teal-glow);
        border: 1px solid rgba(14,158,141,.2);
        border-radius: 20px; padding: 3px 10px;
        font-size: 10.5px; font-weight: 600; color: var(--teal-mid);
    }
    .topbar-page-name {
        font-family: 'Syne', sans-serif;
        font-weight: 700; font-size: 16px; color: var(--navy);
    }

    /* Topbar right actions */
    .topbar-actions { display: flex; align-items: center; gap: 10px; }

    /* Notif button */
    .notif-btn {
        width: 38px; height: 38px; border-radius: 10px;
        background: white; border: 1px solid rgba(0,0,0,.08);
        display: flex; align-items: center; justify-content: center;
        color: var(--slate); font-size: 15px; cursor: pointer;
        position: relative; transition: all .2s;
        box-shadow: 0 1px 4px rgba(0,0,0,.06);
    }
    .notif-btn:hover { background: var(--navy); color: white; border-color: var(--navy); }
    .notif-badge {
        position: absolute; top: -4px; right: -4px;
        min-width: 18px; height: 18px; border-radius: 9px;
        background: #ef4444; color: white;
        font-size: 9px; font-weight: 800;
        display: flex; align-items: center; justify-content: center;
        border: 2px solid var(--surface);
        padding: 0 3px;
    }

    /* User pill */
    .user-pill {
        display: flex; align-items: center; gap: 9px;
        background: white; border: 1px solid rgba(0,0,0,.08);
        border-radius: 40px; padding: 4px 14px 4px 4px;
        cursor: pointer; transition: all .2s;
        box-shadow: 0 1px 4px rgba(0,0,0,.06);
        text-decoration: none;
    }
    .user-pill:hover { border-color: var(--teal-mid); box-shadow: 0 2px 12px rgba(14,158,141,.15); }
    .user-pill img { width: 30px; height: 30px; border-radius: 50%; }
    .user-pill-name { font-size: 13px; font-weight: 600; color: var(--navy); }
    .user-pill-role { font-size: 10px; color: var(--muted); line-height: 1; }
    .user-pill i.caret { font-size: 10px; color: var(--muted); margin-left: 2px; }

    /* Dropdown shared style */
    .dd-menu {
        border: none !important;
        border-radius: 16px !important;
        box-shadow: 0 12px 40px rgba(0,0,0,.14) !important;
        overflow: hidden;
        padding: 0 !important;
        animation: ddIn .2s ease;
    }
    @keyframes ddIn { from{opacity:0;transform:translateY(8px)} to{opacity:1;transform:translateY(0)} }

    /* User dropdown items */
    .dd-head {
        padding: 16px 18px;
        background: linear-gradient(135deg, var(--navy), var(--navy-mid));
        color: white;
        display: flex; align-items: center; gap: 12px;
    }
    .dd-head img { width: 42px; height: 42px; border-radius: 50%; border: 2px solid rgba(255,255,255,.2); }
    .dd-head-name { font-size: 14px; font-weight: 700; }
    .dd-head-role { font-size: 11px; opacity: .5; margin-top: 1px; }

    .dd-item {
        display: flex; align-items: center; gap: 10px;
        padding: 11px 18px; font-size: 13px;
        color: var(--navy); text-decoration: none;
        transition: background .15s;
    }
    .dd-item:hover { background: var(--surface); color: var(--navy); }
    .dd-item i { width: 16px; color: var(--muted); font-size: 13px; }
    .dd-item.danger { color: #dc2626; }
    .dd-item.danger i { color: #dc2626; }
    .dd-item.danger:hover { background: #fee2e2; }
    .dd-divider { height: 1px; background: #f3f4f6; margin: 4px 0; }

    /* Notif dropdown */
    .notif-dd { width: 340px; }
    .notif-dd-head {
        padding: 14px 18px;
        background: linear-gradient(135deg, #006666, #008080);
        color: white; display: flex; justify-content: space-between; align-items: center;
    }
    .notif-dd-list { max-height: 300px; overflow-y: auto; }
    .notif-dd-item {
        display: flex; align-items: flex-start; gap: 11px;
        padding: 12px 18px; border-bottom: 1px solid #f9fafb;
        text-decoration: none; transition: background .15s;
    }
    .notif-dd-item:hover { background: #f9fafb; }
    .notif-dd-icon {
        width: 34px; height: 34px; border-radius: 9px;
        display: flex; align-items: center; justify-content: center;
        font-size: 13px; color: white; flex-shrink: 0;
    }
    .notif-dd-node { font-size: 12px; font-weight: 700; color: var(--navy); }
    .notif-dd-desc { font-size: 11.5px; color: var(--slate); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 220px; }
    .notif-dd-time { font-size: 10.5px; color: var(--muted); margin-top: 2px; }
    .notif-dd-foot { padding: 12px 18px; text-align: center; }
    .notif-dd-foot a { font-size: 12.5px; font-weight: 700; color: var(--teal-mid); text-decoration: none; }
    .notif-dd-foot a:hover { color: var(--teal); }

    /* ═══════════════════════════════
       PAGE CONTENT
    ═══════════════════════════════ */
    .page-content {
        flex: 1; padding: 28px 28px 40px;
        animation: pageIn .35s ease;
    }
    @keyframes pageIn { from{opacity:0;transform:translateY(8px)} to{opacity:1;transform:translateY(0)} }

    /* ═══════════════════════════════
       RESPONSIVE
    ═══════════════════════════════ */
    @media (max-width: 768px) {
        :root { --sidebar-w: 0px; }
        #sidebar { transform: translateX(-240px); transition: transform .3s; }
        #sidebar.open { transform: translateX(0); width: 240px; }
        #content { margin-left: 0; }
        .topbar-back { display: flex !important; }
    }
    </style>
</head>
<body>

<!-- ═══════════════════════════════════════════
     SIDEBAR
═══════════════════════════════════════════ -->
<nav id="sidebar">

    <!-- Brand -->
    <div class="sb-brand">
        <div class="sb-brand-icon"><i class="fa-solid fa-heart-pulse"></i></div>
        <div class="sb-brand-text">
            <div class="sb-brand-name">PELITA</div>
            <div class="sb-brand-sub">Sistem E-Health · Node Kesehatan</div>
        </div>
    </div>

    <!-- Main nav -->
    <div class="sb-nav">
        <div class="sb-section-label">Menu Utama</div>

        <?php foreach ($nav_items as $item):
            $is_active = ($page === $item['page']);
            $cls = 'sb-item' . ($is_active ? ' active' : '') . (($item['danger']??false) ? ' danger' : '');
        ?>
        <a href="index.php?page=<?= $item['page'] ?>" class="<?= $cls ?>">
            <div class="sb-icon"><i class="fa-solid <?= $item['icon'] ?>"></i></div>
            <?= $item['label'] ?>
        </a>
        <?php endforeach; ?>

        <div class="sb-section-label" style="margin-top:8px;">Sistem</div>
        <?php foreach ($nav_bottom as $item):
            $href = isset($item['url']) ? $item['url'] : 'index.php?page='.$item['page'];
            $is_active = isset($item['page']) && ($page === $item['page']);
            $cls = 'sb-item' . ($is_active ? ' active' : '') . (($item['danger']??false) ? ' danger' : '');
        ?>
        <a href="<?= $href ?>" class="<?= $cls ?>">
            <div class="sb-icon"><i class="fa-solid <?= $item['icon'] ?>"></i></div>
            <?= $item['label'] ?>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Node status strip -->
    <div class="sb-node-strip">
        <div class="sb-node-title">Status Koneksi Node</div>
        <?php
        $nodes_strip = [
            ['nama'=>'Kependudukan', 'on'=>true],
            ['nama'=>'Kepolisian',   'on'=>true],
            ['nama'=>'Bantuan Sosial','on'=>true],
            ['nama'=>'Pendidikan',   'on'=>false],
        ];
        foreach ($nodes_strip as $n):
        ?>
        <div class="sb-node-row">
            <div class="sb-node-dot <?= $n['on']?'on':'off' ?>"></div>
            <div class="sb-node-name"><?= $n['nama'] ?></div>
            <span class="sb-node-badge <?= $n['on']?'on':'off' ?>"><?= $n['on']?'Online':'Offline' ?></span>
        </div>
        <?php endforeach; ?>
    </div>

</nav>

<!-- ═══════════════════════════════════════════
     MAIN CONTENT
═══════════════════════════════════════════ -->
<div id="content">

    <!-- TOPBAR -->
    <header id="topbar">

        <!-- Left: title area -->
        <div class="topbar-title">
            <?php if ($page !== 'home'): ?>
            <a href="index.php" class="topbar-back"><i class="fa-solid fa-arrow-left"></i></a>
            <?php endif; ?>
            <div>
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:2px;">
                    <span class="topbar-node-badge">
                        <i class="fa-solid fa-hospital-user" style="font-size:9px;"></i>
                        Node Kesehatan
                    </span>
                    <?php if ($page !== 'home'): ?>
                    <span style="font-size:11px;color:var(--muted);">
                        <i class="fa-solid fa-chevron-right" style="font-size:9px;margin-right:4px;"></i>
                        <?= htmlspecialchars($page_title) ?>
                    </span>
                    <?php endif; ?>
                </div>
                <div class="topbar-page-name"><?= htmlspecialchars($page_title) ?></div>
            </div>
        </div>

        <!-- Right: actions -->
        <div class="topbar-actions">

            <!-- Notifikasi -->
            <div class="dropdown">
                <button class="notif-btn" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fa-solid fa-bell"></i>
                    <?php if ($notif_unread > 0): ?>
                    <span class="notif-badge"><?= min($notif_unread, 99) ?></span>
                    <?php endif; ?>
                </button>

                <div class="dropdown-menu dropdown-menu-end dd-menu notif-dd mt-2">
                    <div class="notif-dd-head">
                        <div>
                            <div style="font-size:.88rem;font-weight:700;">Notifikasi API</div>
                            <div style="font-size:.7rem;opacity:.7;">Request masuk 1 jam terakhir</div>
                        </div>
                        <?php if ($notif_unread > 0): ?>
                        <span style="background:rgba(255,255,255,.18);padding:3px 10px;border-radius:20px;font-size:.7rem;font-weight:700;">
                            <?= $notif_unread ?> Baru
                        </span>
                        <?php endif; ?>
                    </div>

                    <div class="notif-dd-list">
                        <?php if (empty($notif_logs)): ?>
                        <div style="text-align:center;padding:28px 20px;color:#9ca3af;">
                            <i class="fa-solid fa-bell-slash fa-2x d-block mb-2" style="opacity:.25;"></i>
                            <div style="font-size:.8rem;">Belum ada aktivitas</div>
                        </div>
                        <?php else: foreach ($notif_logs as $nl):
                            $nm  = $notif_status_map[$nl['status']] ?? $notif_status_map['error'];
                            $aic = $action_icon_map[$nl['action']] ?? 'fa-rotate';
                            $diff = time() - strtotime($nl['waktu']);
                            $rel  = $diff < 60 ? $diff.'dtk' : ($diff < 3600 ? floor($diff/60).'mnt' : ($diff < 86400 ? floor($diff/3600).'jam' : floor($diff/86400).'hr'));
                            $rel .= ' lalu';
                        ?>
                        <a href="index.php?page=log_api" class="notif-dd-item">
                            <div class="notif-dd-icon" style="background:<?= $nm['bg'] ?>;">
                                <i class="fa-solid <?= $aic ?>"></i>
                            </div>
                            <div style="min-width:0;">
                                <div class="notif-dd-node"><?= htmlspecialchars($nl['node_asal']) ?></div>
                                <div class="notif-dd-desc"><?= htmlspecialchars($nl['keterangan'] ?: $nl['action']) ?></div>
                                <div class="notif-dd-time">
                                    <i class="fa-regular fa-clock me-1"></i><?= $rel ?>
                                    &nbsp;·&nbsp;
                                    <span style="color:<?= $nl['status']==='success'?'#059669':'#d97706' ?>;font-weight:600;"><?= $nm['label'] ?></span>
                                </div>
                            </div>
                        </a>
                        <?php endforeach; endif; ?>
                    </div>

                    <div class="notif-dd-foot">
                        <a href="index.php?page=log_api">
                            <i class="fa-solid fa-arrow-right me-1"></i>Lihat Semua Log API
                        </a>
                    </div>
                </div>
            </div>

            <!-- User dropdown -->
            <div class="dropdown">
                <a class="user-pill dropdown-toggle" href="#" data-bs-toggle="dropdown"
                   style="text-decoration:none;" aria-expanded="false">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['user'] ?? 'Admin') ?>&background=0D1B2A&color=0AD4BC&bold=true"
                         alt="Avatar">
                    <div>
                        <div class="user-pill-name"><?= htmlspecialchars($_SESSION['user'] ?? 'Admin') ?></div>
                        <div class="user-pill-role"><?= htmlspecialchars($_SESSION['role'] ?? 'admin_rs') ?></div>
                    </div>
                    <i class="fa-solid fa-chevron-down caret"></i>
                </a>

                <ul class="dropdown-menu dropdown-menu-end dd-menu mt-2" style="min-width:220px;">
                    <li>
                        <div class="dd-head">
                            <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['user'] ?? 'Admin') ?>&background=0E9E8D&color=fff&bold=true">
                            <div>
                                <div class="dd-head-name"><?= htmlspecialchars($_SESSION['user'] ?? 'Admin') ?></div>
                                <div class="dd-head-role"><?= htmlspecialchars($_SESSION['role'] ?? 'admin_rs') ?></div>
                            </div>
                        </div>
                    </li>
                    <li>
                        <a class="dd-item" href="index.php?page=profil">
                            <i class="fa-regular fa-id-card"></i> Profil Saya
                        </a>
                    </li>
                    <li>
                        <a class="dd-item" href="index.php?page=profil">
                            <i class="fa-solid fa-key"></i> Ganti Password
                        </a>
                    </li>
                    <li><div class="dd-divider"></div></li>
                    <li>
                        <a class="dd-item danger" href="logout.php">
                            <i class="fa-solid fa-power-off"></i> Keluar
                        </a>
                    </li>
                </ul>
            </div>

        </div>
    </header>

    <!-- PAGE CONTENT -->
    <div class="page-content">
        <?php
        $allowed_pages = [
            'home','registrasi','surat_sehat','lapor_mati',
            'lihat_data','log_api','pengaturan','profil'
        ];
        $page_safe = in_array($page, $allowed_pages) ? $page : 'home';
        $file = $page_safe . '.php';
        if (file_exists($file)) include $file;
        else include 'under_construction.php';
        ?>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Highlight sidebar active item berdasarkan URL
(function(){
    const p = new URLSearchParams(window.location.search).get('page') || 'home';
    document.querySelectorAll('.sb-item').forEach(el => {
        const href = el.getAttribute('href') || '';
        if (href.includes('page=' + p)) el.classList.add('active');
    });
})();
</script>
</body>
</html>