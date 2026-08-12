<?php
// ============================================================
// export_pasien.php — Export Data Pasien ke Excel / PDF
// Akses: export_pasien.php?format=excel  atau  ?format=pdf
//        Bisa ditambah filter: &jk=Laki-laki&gol=A&q=keyword
// ============================================================
session_start();
if (!isset($_SESSION['user'])) { header("Location: login.php"); exit; }
require_once 'koneksi.php';

$format    = trim($_GET['format'] ?? 'excel');
$search    = trim($_GET['q']      ?? '');
$filter_jk = trim($_GET['jk']    ?? '');
$filter_gol= trim($_GET['gol']   ?? '');

// ── Build query (sama dengan lihat_data.php) ──
$where = ["1=1"];
if ($search !== '') {
    $s = mysqli_real_escape_string($conn, $search);
    $where[] = "(p.nik LIKE '%$s%' OR p.nama LIKE '%$s%' OR p.kecamatan LIKE '%$s%' OR p.kabupaten LIKE '%$s%')";
}
if ($filter_jk)  $where[] = "p.jenis_kelamin = '" . mysqli_real_escape_string($conn,$filter_jk) . "'";
if ($filter_gol) $where[] = "p.gol_darah = '"     . mysqli_real_escape_string($conn,$filter_gol) . "'";
$where_sql = implode(' AND ', $where);

$sql = "
    SELECT p.nik, p.nama, p.jenis_kelamin, p.tanggal_lahir, p.alamat,
           p.rt_rw, p.kecamatan, p.kabupaten, p.provinsi, p.gol_darah,
           p.sumber_data, p.tanggal_daftar,
           COUNT(DISTINCT rm.id_pemeriksaan) AS jumlah_rekam,
           MAX(rm.status_penyakit) AS status_penyakit,
           MAX(rm.tes_narkoba) AS tes_narkoba,
           IF(lk.id IS NOT NULL,1,0) AS sudah_meninggal
    FROM pasien p
    LEFT JOIN rekam_medis rm ON rm.nik_pasien = p.nik
    LEFT JOIN laporan_kematian lk ON lk.nik_pasien = p.nik
    WHERE $where_sql
    GROUP BY p.nik
    ORDER BY p.tanggal_daftar DESC
";
$result = mysqli_query($conn, $sql);
$rows   = [];
if ($result) while ($r = mysqli_fetch_assoc($result)) $rows[] = $r;

$tgl_export = date('d M Y H:i');
$filename   = 'data_pasien_' . date('Ymd_His');

// ══════════════════════════════════════════════
// EXPORT EXCEL (HTML table → .xls)
// ══════════════════════════════════════════════
if ($format === 'excel') {
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
    header('Cache-Control: max-age=0');
    echo "\xEF\xBB\xBF"; // BOM UTF-8
?>
<html><head><meta charset="UTF-8"></head><body>
<table border="1" style="border-collapse:collapse;font-family:Arial;font-size:11px;">
<tr>
    <td colspan="13" style="background:#008080;color:white;font-size:14px;font-weight:bold;padding:10px;">
        DATA ARSIP PASIEN — PELITA E-Health · Diekspor: <?= $tgl_export ?> oleh <?= htmlspecialchars($_SESSION['user']) ?>
    </td>
</tr>
<tr style="background:#e0f7f4;font-weight:bold;">
    <td style="padding:6px 10px;">No</td>
    <td style="padding:6px 10px;">NIK</td>
    <td style="padding:6px 10px;">Nama Lengkap</td>
    <td style="padding:6px 10px;">Jenis Kelamin</td>
    <td style="padding:6px 10px;">Tanggal Lahir</td>
    <td style="padding:6px 10px;">Alamat</td>
    <td style="padding:6px 10px;">Kecamatan</td>
    <td style="padding:6px 10px;">Kabupaten</td>
    <td style="padding:6px 10px;">Gol. Darah</td>
    <td style="padding:6px 10px;">Rekam Medis</td>
    <td style="padding:6px 10px;">Status Penyakit</td>
    <td style="padding:6px 10px;">Status</td>
    <td style="padding:6px 10px;">Tgl Daftar</td>
</tr>
<?php foreach ($rows as $i => $r): ?>
<tr style="background:<?= ($i%2===0)?'#ffffff':'#f9fafb' ?>">
    <td style="padding:5px 10px;text-align:center;"><?= $i+1 ?></td>
    <td style="padding:5px 10px;font-family:monospace;"><?= htmlspecialchars($r['nik']) ?></td>
    <td style="padding:5px 10px;font-weight:600;"><?= htmlspecialchars($r['nama']) ?></td>
    <td style="padding:5px 10px;"><?= htmlspecialchars($r['jenis_kelamin'] ?? '—') ?></td>
    <td style="padding:5px 10px;"><?= $r['tanggal_lahir'] ? date('d/m/Y',strtotime($r['tanggal_lahir'])) : '—' ?></td>
    <td style="padding:5px 10px;"><?= htmlspecialchars($r['alamat']) ?></td>
    <td style="padding:5px 10px;"><?= htmlspecialchars($r['kecamatan'] ?? '—') ?></td>
    <td style="padding:5px 10px;"><?= htmlspecialchars($r['kabupaten'] ?? '—') ?></td>
    <td style="padding:5px 10px;text-align:center;"><?= htmlspecialchars($r['gol_darah'] ?? '—') ?></td>
    <td style="padding:5px 10px;text-align:center;"><?= $r['jumlah_rekam'] ?></td>
    <td style="padding:5px 10px;"><?= htmlspecialchars($r['status_penyakit'] ?? '—') ?></td>
    <td style="padding:5px 10px;"><?= $r['sudah_meninggal'] ? 'Meninggal' : 'Aktif' ?></td>
    <td style="padding:5px 10px;"><?= date('d/m/Y',strtotime($r['tanggal_daftar'])) ?></td>
</tr>
<?php endforeach; ?>
<tr>
    <td colspan="13" style="padding:6px 10px;background:#f3f4f6;color:#6b7280;font-size:10px;">
        Total: <?= count($rows) ?> pasien · Diekspor dari Sistem PELITA E-Health
    </td>
</tr>
</table>
</body></html>
<?php
    exit;
}

// ══════════════════════════════════════════════
// EXPORT PDF (print-ready HTML)
// ══════════════════════════════════════════════
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Arsip Pasien — PELITA E-Health</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600&display=swap" rel="stylesheet">
    <style>
    *, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
    body { font-family:'DM Sans',sans-serif; background:#e8ecef; }

    /* toolbar */
    .toolbar { background:#0D1B2A; padding:14px 32px; display:flex; justify-content:space-between; align-items:center; position:sticky; top:0; z-index:100; }
    .btn-back { background:rgba(255,255,255,.08); border:1px solid rgba(255,255,255,.15); color:rgba(255,255,255,.7); border-radius:9px; padding:8px 16px; font-size:13px; cursor:pointer; text-decoration:none; display:flex; align-items:center; gap:7px; transition:all .2s; font-family:'DM Sans',sans-serif; }
    .btn-back:hover { background:rgba(255,255,255,.14); color:white; }
    .btn-print { background:linear-gradient(135deg,#0E9E8D,#0AD4BC); color:white; border:none; border-radius:9px; padding:10px 22px; font-size:14px; font-weight:600; cursor:pointer; display:flex; align-items:center; gap:8px; font-family:'DM Sans',sans-serif; }
    .toolbar-title { color:white; font-size:14px; font-weight:600; }

    /* PDF page */
    .pdf-page { max-width:1000px; margin:28px auto; background:white; box-shadow:0 4px 24px rgba(0,0,0,.12); border-radius:4px; overflow:hidden; }

    /* header stripe */
    .pdf-stripe { height:6px; background:linear-gradient(90deg,#0B7B6E,#0E9E8D,#0AD4BC); }

    .pdf-header { padding:20px 36px 16px; border-bottom:2px solid #0B7B6E; display:flex; align-items:center; gap:16px; }
    .pdf-logo { width:52px; height:52px; border-radius:12px; background:linear-gradient(135deg,#0B7B6E,#0AD4BC); display:flex; align-items:center; justify-content:center; color:white; font-size:22px; flex-shrink:0; }
    .pdf-logo-text h3 { font-size:18px; font-weight:700; color:#0B7B6E; }
    .pdf-logo-text p  { font-size:11px; color:#6B7280; margin-top:2px; }

    .pdf-meta { padding:12px 36px; background:#F8FAFC; border-bottom:1px solid #E5E7EB; display:flex; justify-content:space-between; font-size:11.5px; color:#6B7280; }
    .pdf-meta strong { color:#374151; }

    /* table */
    .pdf-table { width:100%; border-collapse:collapse; font-size:11px; }
    .pdf-table thead tr { background:#F0FDF9; }
    .pdf-table th { padding:9px 12px; text-align:left; font-weight:700; color:#0B7B6E; font-size:10px; text-transform:uppercase; letter-spacing:.7px; border-bottom:2px solid #CCEFEB; white-space:nowrap; }
    .pdf-table td { padding:8px 12px; border-bottom:1px solid #F3F4F6; color:#374151; vertical-align:middle; }
    .pdf-table tr:nth-child(even) td { background:#F9FAFB; }

    .bs { padding:2px 8px; border-radius:20px; font-size:10px; font-weight:700; }
    .bs-sehat   { background:#D1FAE5; color:#065F46; }
    .bs-kronis  { background:#FEE2E2; color:#991B1B; }
    .bs-dis     { background:#FEF3C7; color:#92400E; }
    .bs-mati    { background:#FEE2E2; color:#991B1B; }
    .bs-aktif   { background:#DBEAFE; color:#1D4ED8; }

    .pdf-footer { padding:10px 36px; background:#F8FAFC; border-top:1px solid #E5E7EB; display:flex; justify-content:space-between; font-size:10.5px; color:#9CA3AF; }

    @media print {
        body { background:white; }
        .toolbar { display:none; }
        .pdf-page { margin:0; box-shadow:none; max-width:100%; }
        @page { size:A4 landscape; margin:15mm; }
    }
    </style>
</head>
<body>

<div class="toolbar">
    <div style="display:flex;align-items:center;gap:14px;">
        <a href="javascript:history.back()" class="btn-back">← Kembali</a>
        <span class="toolbar-title">Export PDF — Arsip Data Pasien</span>
    </div>
    <button class="btn-print" onclick="window.print()">🖨️ Cetak / Simpan PDF</button>
</div>

<div class="pdf-page">
    <div class="pdf-stripe"></div>

    <div class="pdf-header">
        <div class="pdf-logo">💊</div>
        <div class="pdf-logo-text">
            <h3>PELITA E-Health · Arsip Data Pasien</h3>
            <p>Node Sistem Kesehatan E-Government Terpadu</p>
        </div>
    </div>

    <div class="pdf-meta">
        <span>Diekspor: <strong><?= $tgl_export ?></strong> &nbsp;·&nbsp; Oleh: <strong><?= htmlspecialchars($_SESSION['user']) ?></strong></span>
        <span>Total: <strong><?= count($rows) ?> pasien</strong>
            <?= $search   ? " · Kata kunci: <strong>$search</strong>"   : '' ?>
            <?= $filter_jk  ? " · JK: <strong>$filter_jk</strong>"        : '' ?>
            <?= $filter_gol ? " · Gol. Darah: <strong>$filter_gol</strong>": '' ?>
        </span>
    </div>

    <table class="pdf-table">
        <thead>
            <tr>
                <th>No</th>
                <th>NIK</th>
                <th>Nama Lengkap</th>
                <th>JK</th>
                <th>Tgl Lahir</th>
                <th>Kecamatan</th>
                <th>Kabupaten</th>
                <th>Gol.</th>
                <th>Rekam</th>
                <th>Status Penyakit</th>
                <th>Kondisi</th>
                <th>Terdaftar</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $i => $r):
            $meninggal = (bool)$r['sudah_meninggal'];
            $sp = $r['status_penyakit'] ?? '—';
            $sp_cls = $sp==='Sehat'?'bs-sehat':($sp==='Disabilitas'?'bs-dis':($sp==='Penyakit Kronis'?'bs-kronis':''));
        ?>
        <tr>
            <td style="color:#9CA3AF;text-align:center;"><?= $i+1 ?></td>
            <td style="font-family:monospace;font-size:10px;"><?= htmlspecialchars($r['nik']) ?></td>
            <td style="font-weight:600;"><?= htmlspecialchars($r['nama']) ?></td>
            <td><?= htmlspecialchars($r['jenis_kelamin'][0] ?? '—') ?></td>
            <td><?= $r['tanggal_lahir'] ? date('d/m/Y',strtotime($r['tanggal_lahir'])) : '—' ?></td>
            <td><?= htmlspecialchars($r['kecamatan'] ?? '—') ?></td>
            <td><?= htmlspecialchars($r['kabupaten'] ?? '—') ?></td>
            <td style="text-align:center;"><?= htmlspecialchars($r['gol_darah'] ?? '—') ?></td>
            <td style="text-align:center;"><?= $r['jumlah_rekam'] ?></td>
            <td><?= $sp_cls ? "<span class='bs $sp_cls'>$sp</span>" : $sp ?></td>
            <td><?= $meninggal
                ? "<span class='bs bs-mati'>Meninggal</span>"
                : "<span class='bs bs-aktif'>Aktif</span>" ?></td>
            <td><?= date('d/m/Y',strtotime($r['tanggal_daftar'])) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <div class="pdf-footer">
        <span>PELITA E-Health · Sistem Informasi Kesehatan Terpadu</span>
        <span><?= count($rows) ?> data pasien · <?= $tgl_export ?></span>
    </div>
</div>

</body>
</html>
<?php
exit;
?>