<?php
// ================================================================
// lihat_data.php — Arsip Data Pasien + Edit & Hapus
// Partial — di-include dari index.php ($conn & $_SESSION aktif)
// ================================================================
if (!isset($conn)) require_once 'koneksi.php';

// ── Proses AJAX: Edit Pasien ──────────────────────────────────────
if (isset($_POST['aksi']) && $_POST['aksi'] === 'edit_pasien') {
    header('Content-Type: application/json');
    $nik          = mysqli_real_escape_string($conn, trim($_POST['nik']           ?? ''));
    $nama         = mysqli_real_escape_string($conn, trim($_POST['nama']          ?? ''));
    $jk           = mysqli_real_escape_string($conn, trim($_POST['jenis_kelamin'] ?? ''));
    $tgl_lahir    = mysqli_real_escape_string($conn, trim($_POST['tanggal_lahir'] ?? ''));
    $alamat       = mysqli_real_escape_string($conn, trim($_POST['alamat']        ?? ''));
    $rt_rw        = mysqli_real_escape_string($conn, trim($_POST['rt_rw']         ?? ''));
    $kecamatan    = mysqli_real_escape_string($conn, trim($_POST['kecamatan']     ?? ''));
    $kabupaten    = mysqli_real_escape_string($conn, trim($_POST['kabupaten']     ?? ''));
    $provinsi     = mysqli_real_escape_string($conn, trim($_POST['provinsi']      ?? ''));
    $gol_darah    = mysqli_real_escape_string($conn, trim($_POST['gol_darah']     ?? ''));

    if (!$nik || !$nama) {
        echo json_encode(['ok'=>false,'msg'=>'NIK dan Nama wajib diisi.']); exit;
    }

    $tgl_q = $tgl_lahir ? "'$tgl_lahir'" : 'NULL';
    $jk_q  = $jk        ? "'$jk'"        : 'NULL';
    $gol_q = $gol_darah ? "'$gol_darah'" : 'NULL';

    $upd = mysqli_query($conn,
        "UPDATE pasien SET
            nama='$nama', jenis_kelamin=$jk_q, tanggal_lahir=$tgl_q,
            alamat='$alamat', rt_rw='$rt_rw', kecamatan='$kecamatan',
            kabupaten='$kabupaten', provinsi='$provinsi', gol_darah=$gol_q
         WHERE nik='$nik'"
    );

    if ($upd) echo json_encode(['ok'=>true,'msg'=>'Data pasien berhasil diperbarui.']);
    else      echo json_encode(['ok'=>false,'msg'=>'Gagal update: '.mysqli_error($conn)]);
    exit;
}

// ── Proses AJAX: Hapus Pasien ─────────────────────────────────────
if (isset($_POST['aksi']) && $_POST['aksi'] === 'hapus_pasien') {
    header('Content-Type: application/json');
    $nik = mysqli_real_escape_string($conn, trim($_POST['nik'] ?? ''));
    if (!$nik) { echo json_encode(['ok'=>false,'msg'=>'NIK tidak valid.']); exit; }

    // ON DELETE CASCADE sudah di-set di DB jadi rekam_medis ikut terhapus
    $del = mysqli_query($conn, "DELETE FROM pasien WHERE nik='$nik'");
    if ($del) echo json_encode(['ok'=>true,'msg'=>'Pasien berhasil dihapus.']);
    else      echo json_encode(['ok'=>false,'msg'=>'Gagal hapus: '.mysqli_error($conn)]);
    exit;
}

// ── Filter & Search ───────────────────────────────────────────────
$search    = trim($_GET['q']   ?? '');
$filter_jk = trim($_GET['jk']  ?? '');
$filter_gol= trim($_GET['gol'] ?? '');

$where = ["1=1"];
if ($search !== '') {
    $s = mysqli_real_escape_string($conn, $search);
    $where[] = "(p.nik LIKE '%$s%' OR p.nama LIKE '%$s%' OR p.kecamatan LIKE '%$s%' OR p.kabupaten LIKE '%$s%')";
}
if ($filter_jk !== '')  $where[] = "p.jenis_kelamin = '".mysqli_real_escape_string($conn,$filter_jk)."'";
if ($filter_gol !== '') $where[] = "p.gol_darah = '".mysqli_real_escape_string($conn,$filter_gol)."'";
$where_sql = implode(' AND ', $where);

$sql = "
    SELECT p.*,
        COUNT(DISTINCT rm.id_pemeriksaan) AS jumlah_rekam,
        MAX(rm.id_pemeriksaan)            AS id_periksa_last,
        MAX(rm.tanggal_periksa)           AS last_periksa,
        MAX(rm.status_penyakit)           AS status_penyakit,
        MAX(rm.tes_narkoba)               AS tes_narkoba
    FROM pasien p
    LEFT JOIN rekam_medis rm ON rm.nik_pasien = p.nik
    WHERE $where_sql
    GROUP BY p.nik
    ORDER BY p.tanggal_daftar DESC
";
$result  = mysqli_query($conn, $sql);
$pasiens = [];
if ($result) while ($r = mysqli_fetch_assoc($result)) $pasiens[] = $r;

$total_semua = count($pasiens);
$total_rekam = array_sum(array_column($pasiens, 'jumlah_rekam'));
?>

<style>
/* ─── LIHAT DATA STYLES ─── */
.ld-stats{display:flex;gap:14px;margin-bottom:20px;flex-wrap:wrap;}
.ld-stat{background:white;border-radius:14px;padding:14px 20px;box-shadow:0 2px 12px rgba(0,0,0,.06);flex:1;min-width:130px;display:flex;align-items:center;gap:14px;}
.ld-stat-icon{width:42px;height:42px;border-radius:11px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0;color:white;}
.ld-stat-num{font-size:1.6rem;font-weight:800;color:#0D1B2A;line-height:1;}
.ld-stat-label{font-size:.72rem;color:#9ca3af;font-weight:500;margin-top:3px;}

/* Toolbar */
.ld-toolbar{background:white;border-radius:16px;padding:16px 20px;box-shadow:0 2px 12px rgba(0,0,0,.06);margin-bottom:16px;display:flex;gap:10px;flex-wrap:wrap;align-items:center;}
.ld-search-wrap{position:relative;flex:1;min-width:200px;}
.ld-search-wrap i{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#9ca3af;font-size:.85rem;}
.ld-search-wrap input{width:100%;border:1.5px solid #e5e7eb;border-radius:10px;padding:9px 14px 9px 35px;font-size:.85rem;color:#1f2937;font-family:'DM Sans',sans-serif;transition:all .2s;}
.ld-search-wrap input:focus{border-color:#0E9E8D;box-shadow:0 0 0 3px rgba(14,158,141,.1);outline:none;}
.ld-select{border:1.5px solid #e5e7eb;border-radius:10px;padding:9px 14px;font-size:.85rem;color:#374151;background:white;transition:border-color .2s;font-family:'DM Sans',sans-serif;}
.ld-select:focus{border-color:#0E9E8D;outline:none;}
.btn-teal{background:#0E9E8D;color:white;border:none;border-radius:10px;padding:9px 18px;font-size:.85rem;font-weight:600;cursor:pointer;transition:all .2s;display:flex;align-items:center;gap:7px;font-family:'DM Sans',sans-serif;}
.btn-teal:hover{background:#0B7B6E;}
.btn-dark{background:#0D1B2A;color:white;border:none;border-radius:10px;padding:9px 16px;font-size:.85rem;font-weight:600;cursor:pointer;transition:all .2s;display:flex;align-items:center;gap:7px;text-decoration:none;font-family:'DM Sans',sans-serif;}
.btn-dark:hover{background:#1A2E42;color:white;}
.btn-reset{background:#f3f4f6;color:#6b7280;border:none;border-radius:10px;padding:9px 14px;font-size:.85rem;cursor:pointer;transition:all .2s;text-decoration:none;display:flex;align-items:center;gap:6px;font-family:'DM Sans',sans-serif;}
.btn-reset:hover{background:#e5e7eb;color:#374151;}

/* Table */
.ld-card{background:white;border-radius:16px;box-shadow:0 2px 16px rgba(0,0,0,.06);overflow:hidden;}
.ld-table{width:100%;border-collapse:collapse;font-size:.825rem;}
.ld-table thead tr{background:#f9fafb;}
.ld-table th{padding:11px 14px;color:#6b7280;font-size:.7rem;text-transform:uppercase;letter-spacing:.8px;font-weight:700;text-align:left;border-bottom:1px solid #f3f4f6;white-space:nowrap;}
.ld-table td{padding:12px 14px;border-top:1px solid #f3f4f6;vertical-align:middle;color:#374151;}
.ld-table tr:hover td{background:#fafffe;}

/* Badges */
.badge-gol{padding:3px 10px;border-radius:20px;font-size:.72rem;font-weight:700;background:#e0f2fe;color:#0369a1;}
.badge-jk-l{background:#eff6ff;color:#1d4ed8;padding:3px 9px;border-radius:20px;font-size:.7rem;font-weight:600;}
.badge-jk-p{background:#fdf4ff;color:#7e22ce;padding:3px 9px;border-radius:20px;font-size:.7rem;font-weight:600;}
.badge-sehat{background:#d1fae5;color:#065f46;}
.badge-dis{background:#fef3c7;color:#92400e;}
.badge-kronis{background:#fee2e2;color:#991b1b;}
.badge-rekam{background:#f3f4f6;color:#6b7280;padding:2px 9px;border-radius:20px;font-size:.7rem;}
.badge-rekam.ada{background:#e0f2fe;color:#0369a1;}

/* Action buttons */
.act-btn{display:inline-flex;align-items:center;gap:5px;padding:5px 11px;border-radius:7px;font-size:.72rem;font-weight:700;cursor:pointer;border:none;transition:all .2s;font-family:'DM Sans',sans-serif;}
.act-btn-detail{background:#f3f4f6;color:#374151;}
.act-btn-detail:hover{background:#e5e7eb;}
.act-btn-edit{background:#e0f2fe;color:#0369a1;}
.act-btn-edit:hover{background:#bae6fd;color:#0369a1;}
.act-btn-hapus{background:#fee2e2;color:#991b1b;}
.act-btn-hapus:hover{background:#fecaca;}
.act-btn-cetak{background:#d1fae5;color:#065f46;}
.act-btn-cetak:hover{background:#a7f3d0;}

/* ── MODAL SHARED ── */
.m-overlay{display:none;position:fixed;inset:0;background:rgba(13,27,42,.55);z-index:9999;align-items:center;justify-content:center;padding:20px;backdrop-filter:blur(4px);}
.m-overlay.open{display:flex;}
.m-box{background:white;border-radius:20px;width:100%;box-shadow:0 24px 60px rgba(0,0,0,.2);animation:mIn .25s ease;}
@keyframes mIn{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
.m-head{padding:20px 24px;border-bottom:1px solid #f3f4f6;display:flex;justify-content:space-between;align-items:center;}
.m-head-title{font-family:'Syne',sans-serif;font-weight:700;font-size:.95rem;color:#0D1B2A;display:flex;align-items:center;gap:8px;}
.m-head-title i{color:#0E9E8D;}
.m-close{width:30px;height:30px;border-radius:8px;border:none;background:#f3f4f6;color:#6b7280;cursor:pointer;font-size:.9rem;transition:all .2s;display:flex;align-items:center;justify-content:center;}
.m-close:hover{background:#e5e7eb;}
.m-body{padding:22px 24px;}
.m-foot{padding:16px 24px;border-top:1px solid #f3f4f6;display:flex;gap:10px;justify-content:flex-end;}

/* Edit modal form */
.ef-row{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
.ef-group{margin-bottom:14px;}
.ef-group.full{grid-column:span 2;}
.ef-label{font-size:.72rem;font-weight:700;color:#4A6278;text-transform:uppercase;letter-spacing:.6px;margin-bottom:5px;display:block;}
.ef-input{width:100%;border:1.5px solid #e5e7eb;border-radius:9px;padding:9px 12px;font-size:.85rem;color:#0D1B2A;font-family:'DM Sans',sans-serif;transition:all .2s;}
.ef-input:focus{border-color:#0E9E8D;box-shadow:0 0 0 3px rgba(14,158,141,.1);outline:none;}
.ef-select{appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20' fill='%236b7280'%3E%3Cpath d='M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 10px center;background-size:15px;padding-right:32px;}

/* Hapus modal */
.hapus-icon{width:64px;height:64px;border-radius:50%;background:#fee2e2;display:flex;align-items:center;justify-content:center;font-size:1.6rem;margin:0 auto 16px;}
.btn-hapus-ok{background:#dc2626;color:white;border:none;border-radius:10px;padding:10px 24px;font-size:.88rem;font-weight:700;cursor:pointer;transition:all .2s;font-family:'DM Sans',sans-serif;}
.btn-hapus-ok:hover{background:#b91c1c;}
.btn-cancel{background:#f3f4f6;color:#374151;border:none;border-radius:10px;padding:10px 20px;font-size:.88rem;font-weight:600;cursor:pointer;font-family:'DM Sans',sans-serif;}
.btn-cancel:hover{background:#e5e7eb;}

/* Detail modal gradient head */
.m-head-gradient{background:linear-gradient(135deg,#0D1B2A,#1A2E42);border-radius:20px 20px 0 0;padding:20px 24px;color:white;display:flex;align-items:center;gap:14px;}
.m-avatar{width:52px;height:52px;border-radius:50%;border:2px solid rgba(255,255,255,.2);}
.m-dname{font-size:1rem;font-weight:700;}
.m-dnik{font-size:.75rem;opacity:.5;font-family:monospace;margin-top:2px;}
.detail-row{display:flex;justify-content:space-between;padding:9px 0;border-bottom:1px solid #f9fafb;font-size:.83rem;}
.detail-row:last-child{border-bottom:none;}
.detail-label{color:#6b7280;}
.detail-value{color:#0D1B2A;font-weight:600;text-align:right;max-width:60%;}

/* Toast */
.ld-toast{position:fixed;bottom:24px;right:24px;padding:13px 20px;border-radius:12px;font-size:.85rem;font-weight:600;color:white;z-index:99999;opacity:0;transform:translateY(10px);transition:all .3s;pointer-events:none;display:flex;align-items:center;gap:10px;min-width:240px;}
.ld-toast.show{opacity:1;transform:translateY(0);}
.ld-toast.ok  {background:#0B7B6E;box-shadow:0 8px 24px rgba(11,123,110,.4);}
.ld-toast.err {background:#dc2626;box-shadow:0 8px 24px rgba(220,38,38,.4);}
</style>

<!-- ── STATS ── -->
<div class="ld-stats">
    <div class="ld-stat">
        <div class="ld-stat-icon" style="background:linear-gradient(135deg,#006666,#20b2aa)"><i class="fa-solid fa-users"></i></div>
        <div><div class="ld-stat-num"><?= $total_semua ?></div><div class="ld-stat-label">Total Pasien</div></div>
    </div>
    <div class="ld-stat">
        <div class="ld-stat-icon" style="background:linear-gradient(135deg,#1d4ed8,#3b82f6)"><i class="fa-solid fa-file-medical"></i></div>
        <div><div class="ld-stat-num"><?= $total_rekam ?></div><div class="ld-stat-label">Total Rekam Medis</div></div>
    </div>
    <div class="ld-stat">
        <div class="ld-stat-icon" style="background:linear-gradient(135deg,#065f46,#22c55e)"><i class="fa-solid fa-user-check"></i></div>
        <div><div class="ld-stat-num"><?= $total_semua ?></div><div class="ld-stat-label">Pasien Aktif</div></div>
    </div>
    <div class="ld-stat">
        <div class="ld-stat-icon" style="background:linear-gradient(135deg,#92400e,#f59e0b)"><i class="fa-solid fa-rotate"></i></div>
        <div><div class="ld-stat-num"><?= $filter_jk||$filter_gol||$search ? count($pasiens) : '—' ?></div><div class="ld-stat-label">Hasil Filter</div></div>
    </div>
</div>

<!-- ── TOOLBAR ── -->
<form method="GET" id="formFilter">
    <input type="hidden" name="page" value="lihat_data">
    <div class="ld-toolbar">
        <div class="ld-search-wrap">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" name="q" placeholder="Cari nama, NIK, kecamatan..."
                   value="<?= htmlspecialchars($search) ?>">
        </div>
        <select class="ld-select" name="jk">
            <option value="">Semua JK</option>
            <option value="Laki-laki" <?= $filter_jk==='Laki-laki'?'selected':'' ?>>Laki-laki</option>
            <option value="Perempuan" <?= $filter_jk==='Perempuan'?'selected':'' ?>>Perempuan</option>
        </select>
        <select class="ld-select" name="gol">
            <option value="">Semua Gol. Darah</option>
            <?php foreach(['A','B','AB','O'] as $g): ?>
            <option value="<?= $g ?>" <?= $filter_gol===$g?'selected':'' ?>><?= $g ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn-teal"><i class="fa-solid fa-filter"></i> Filter</button>
        <?php if ($search||$filter_jk||$filter_gol): ?>
        <a href="?page=lihat_data" class="btn-reset"><i class="fa-solid fa-xmark"></i> Reset</a>
        <?php endif; ?>
        <a href="?page=registrasi" class="btn-dark ms-auto">
            <i class="fa-solid fa-user-plus"></i> Tambah Pasien
        </a>
    </div>
</form>

<!-- ── TABLE ── -->
<div class="ld-card">
<?php if (empty($pasiens)): ?>
    <div style="text-align:center;padding:50px 20px;color:#9ca3af;">
        <i class="fa-solid fa-folder-open fa-2x d-block mb-2" style="opacity:.3;"></i>
        <p style="font-size:.875rem;">Tidak ada data pasien<?= $search ? ' untuk &ldquo;'.htmlspecialchars($search).'&rdquo;' : '' ?></p>
    </div>
<?php else: ?>
<div style="overflow-x:auto;">
<table class="ld-table">
    <thead>
        <tr>
            <th>#</th>
            <th>NIK</th>
            <th>Nama Lengkap</th>
            <th>JK</th>
            <th>Gol.</th>
            <th>Domisili</th>
            <th>Rekam Medis</th>
            <th>Status</th>
            <th>Tgl Daftar</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($pasiens as $i => $p):
        $sp = $p['status_penyakit'] ?? '';
        $sp_cls = $sp==='Sehat'?'badge-sehat':($sp==='Disabilitas'?'badge-dis':'badge-kronis');
    ?>
    <tr>
        <td style="color:#d1d5db;"><?= $i+1 ?></td>
        <td style="font-family:monospace;font-size:.78rem;"><?= htmlspecialchars(substr($p['nik'],0,6).'..'.substr($p['nik'],-4)) ?></td>
        <td style="font-weight:600;"><?= htmlspecialchars($p['nama']) ?></td>
        <td>
            <?php if($p['jenis_kelamin']==='Laki-laki'): ?>
                <span class="badge-jk-l"><i class="fa-solid fa-mars me-1"></i>L</span>
            <?php elseif($p['jenis_kelamin']==='Perempuan'): ?>
                <span class="badge-jk-p"><i class="fa-solid fa-venus me-1"></i>P</span>
            <?php else: ?><span style="color:#d1d5db;">—</span><?php endif; ?>
        </td>
        <td>
            <?= $p['gol_darah']
                ? '<span class="badge-gol">'.$p['gol_darah'].'</span>'
                : '<span style="color:#d1d5db;">—</span>' ?>
        </td>
        <td style="font-size:.78rem;">
            <?= $p['kecamatan']
                ? htmlspecialchars($p['kecamatan'].', '.$p['kabupaten'])
                : htmlspecialchars(mb_strimwidth($p['alamat'],0,35,'…')) ?>
        </td>
        <td>
            <?php if($p['jumlah_rekam'] > 0): ?>
                <span class="badge-rekam ada"><i class="fa-solid fa-file-medical me-1"></i><?= $p['jumlah_rekam'] ?> data</span>
            <?php else: ?>
                <span class="badge-rekam">Belum ada</span>
            <?php endif; ?>
        </td>
        <td>
            <?php if($sp): ?>
                <span class="badge-rekam <?= $sp_cls ?>" style="padding:3px 9px;"><?= $sp ?></span>
            <?php else: ?><span style="color:#d1d5db;font-size:.78rem;">—</span><?php endif; ?>
        </td>
        <td style="font-size:.75rem;color:#9ca3af;"><?= date('d M Y', strtotime($p['tanggal_daftar'])) ?></td>
        <td>
            <div style="display:flex;gap:5px;flex-wrap:nowrap;">
                <button class="act-btn act-btn-detail" onclick='bukaDetail(<?= json_encode($p) ?>)' title="Detail">
                    <i class="fa-solid fa-eye"></i>
                </button>
                <button class="act-btn act-btn-edit" onclick='bukaEdit(<?= json_encode($p) ?>)' title="Edit">
                    <i class="fa-solid fa-pen-to-square"></i>
                </button>
                <?php if($p['jumlah_rekam'] > 0): ?>
                <a href="cetak_surat.php?id=<?= $p['id_periksa_last'] ?>" target="_blank"
                   class="act-btn act-btn-cetak" title="Cetak SK Terbaru">
                    <i class="fa-solid fa-print"></i>
                </a>
                <?php endif; ?>
                <button class="act-btn act-btn-hapus" onclick='bukaHapus("<?= $p['nik'] ?>","<?= htmlspecialchars($p['nama'],ENT_QUOTES) ?>")' title="Hapus">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </div>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<div style="padding:11px 18px;border-top:1px solid #f3f4f6;font-size:.75rem;color:#9ca3af;">
    Menampilkan <?= count($pasiens) ?> pasien <?= ($search||$filter_jk||$filter_gol)?'(hasil filter)':'' ?>
</div>
<?php endif; ?>
</div>

<!-- ═══ MODAL DETAIL ═══ -->
<div class="m-overlay" id="mDetail" onclick="if(event.target===this)tutup('mDetail')">
    <div class="m-box" style="max-width:480px;">
        <div class="m-head-gradient" id="mDetailHead"></div>
        <div class="m-body" id="mDetailBody"></div>
        <div class="m-foot">
            <button class="btn-cancel" onclick="tutup('mDetail')">Tutup</button>
            <a id="mDetailCetak" href="#" target="_blank" class="act-btn act-btn-cetak" style="padding:8px 16px;">
                <i class="fa-solid fa-print"></i> Cetak SK Terbaru
            </a>
        </div>
    </div>
</div>

<!-- ═══ MODAL EDIT ═══ -->
<div class="m-overlay" id="mEdit" onclick="if(event.target===this)tutup('mEdit')">
    <div class="m-box" style="max-width:560px;max-height:90vh;overflow-y:auto;">
        <div class="m-head">
            <div class="m-head-title"><i class="fa-solid fa-pen-to-square"></i> Edit Data Pasien</div>
            <button class="m-close" onclick="tutup('mEdit')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="m-body">
            <form id="formEdit">
                <input type="hidden" name="aksi" value="edit_pasien">
                <input type="hidden" name="nik" id="eNik">

                <!-- NIK display (readonly) -->
                <div class="ef-group" style="margin-bottom:16px;">
                    <label class="ef-label">NIK (tidak dapat diubah)</label>
                    <div style="background:#f9fafb;border:1.5px solid #f3f4f6;border-radius:9px;padding:9px 12px;font-size:.85rem;font-family:monospace;color:#6b7280;" id="eNikDisplay"></div>
                </div>

                <div class="ef-row">
                    <div class="ef-group full">
                        <label class="ef-label">Nama Lengkap *</label>
                        <input type="text" name="nama" id="eNama" class="ef-input" required>
                    </div>
                    <div class="ef-group">
                        <label class="ef-label">Jenis Kelamin</label>
                        <select name="jenis_kelamin" id="eJk" class="ef-input ef-select">
                            <option value="">— Pilih —</option>
                            <option value="Laki-laki">Laki-laki</option>
                            <option value="Perempuan">Perempuan</option>
                        </select>
                    </div>
                    <div class="ef-group">
                        <label class="ef-label">Tanggal Lahir</label>
                        <input type="date" name="tanggal_lahir" id="eTglLahir" class="ef-input">
                    </div>
                    <div class="ef-group">
                        <label class="ef-label">Golongan Darah</label>
                        <select name="gol_darah" id="eGol" class="ef-input ef-select">
                            <option value="">— Pilih —</option>
                            <?php foreach(['A','B','AB','O'] as $g): ?>
                            <option value="<?= $g ?>"><?= $g ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="ef-group full">
                        <label class="ef-label">Alamat</label>
                        <input type="text" name="alamat" id="eAlamat" class="ef-input">
                    </div>
                    <div class="ef-group">
                        <label class="ef-label">RT/RW</label>
                        <input type="text" name="rt_rw" id="eRtRw" class="ef-input" placeholder="01/05">
                    </div>
                    <div class="ef-group">
                        <label class="ef-label">Kecamatan</label>
                        <input type="text" name="kecamatan" id="eKecamatan" class="ef-input">
                    </div>
                    <div class="ef-group">
                        <label class="ef-label">Kabupaten</label>
                        <input type="text" name="kabupaten" id="eKabupaten" class="ef-input">
                    </div>
                    <div class="ef-group">
                        <label class="ef-label">Provinsi</label>
                        <input type="text" name="provinsi" id="eProvinsi" class="ef-input">
                    </div>
                </div>
            </form>
        </div>
        <div class="m-foot">
            <button class="btn-cancel" onclick="tutup('mEdit')">Batal</button>
            <button class="btn-teal" onclick="submitEdit()" id="btnSimpanEdit">
                <i class="fa-solid fa-floppy-disk me-1"></i> Simpan Perubahan
            </button>
        </div>
    </div>
</div>

<!-- ═══ MODAL HAPUS ═══ -->
<div class="m-overlay" id="mHapus" onclick="if(event.target===this)tutup('mHapus')">
    <div class="m-box" style="max-width:380px;">
        <div class="m-body" style="text-align:center;padding:32px 28px;">
            <div class="hapus-icon"><i class="fa-solid fa-trash" style="color:#dc2626;"></i></div>
            <div style="font-family:'Syne',sans-serif;font-size:1.05rem;font-weight:700;color:#0D1B2A;margin-bottom:8px;">
                Hapus Data Pasien?
            </div>
            <div style="font-size:.85rem;color:#6b7280;line-height:1.6;margin-bottom:8px;">
                Kamu akan menghapus data pasien:
            </div>
            <div style="font-size:.95rem;font-weight:700;color:#dc2626;margin-bottom:6px;" id="hNama"></div>
            <div style="font-family:monospace;font-size:.8rem;color:#9ca3af;margin-bottom:20px;" id="hNik"></div>
            <div style="background:#fff5f5;border:1px solid #fecaca;border-radius:10px;padding:12px;font-size:.78rem;color:#991b1b;margin-bottom:24px;text-align:left;">
                <i class="fa-solid fa-triangle-exclamation me-1"></i>
                <strong>Peringatan:</strong> Semua rekam medis pasien ini juga akan ikut terhapus secara permanen. Tindakan ini <strong>tidak dapat dibatalkan</strong>.
            </div>
        </div>
        <div class="m-foot" style="justify-content:center;">
            <button class="btn-cancel" onclick="tutup('mHapus')">Batal</button>
            <button class="btn-hapus-ok" id="btnHapusOk" onclick="submitHapus()">
                <i class="fa-solid fa-trash me-1"></i> Ya, Hapus Sekarang
            </button>
        </div>
    </div>
</div>

<!-- Toast -->
<div class="ld-toast" id="ldToast"></div>

<script>
let _hapusNik = '';

// ── Buka/tutup modal ─────────────────────────────────────────────
function buka(id)  { document.getElementById(id).classList.add('open'); }
function tutup(id) { document.getElementById(id).classList.remove('open'); }
document.addEventListener('keydown', e => { if(e.key==='Escape') document.querySelectorAll('.m-overlay.open').forEach(m=>m.classList.remove('open')); });

// ── Toast ────────────────────────────────────────────────────────
function showToast(msg, type='ok') {
    const t = document.getElementById('ldToast');
    t.textContent = '';
    const ico = document.createElement('i');
    ico.className = type==='ok' ? 'fa-solid fa-circle-check' : 'fa-solid fa-circle-xmark';
    t.appendChild(ico);
    t.appendChild(document.createTextNode(' ' + msg));
    t.className = `ld-toast ${type} show`;
    setTimeout(() => t.classList.remove('show'), 3500);
}

// ── Cetak SK terbaru by NIK ─────────────────────────────────────
function cetakTerbaru(nik, el) {
    // Ambil id_pemeriksaan terbaru lewat fetch
    fetch(`index.php?page=surat_sehat&fetch_pasien=${encodeURIComponent(nik)}`)
        .then(r => r.json())
        .then(d => {
            // ambil id rekam terakhir via endpoint khusus
        });
    // Langsung buka via query rekam medis terakhir
    window.open(`cetak_surat.php?nik=${encodeURIComponent(nik)}&last=1`, '_blank');
    return false;
}

// ── Modal DETAIL ─────────────────────────────────────────────────
function bukaDetail(p) {
    const tgl_lahir  = p.tanggal_lahir  ? new Date(p.tanggal_lahir).toLocaleDateString('id-ID',{day:'2-digit',month:'long',year:'numeric'}) : '—';
    const tgl_daftar = new Date(p.tanggal_daftar).toLocaleDateString('id-ID',{day:'2-digit',month:'long',year:'numeric'});
    const alamat_full = [p.alamat, p.rt_rw?'RT/RW '+p.rt_rw:'', p.kecamatan, p.kabupaten, p.provinsi].filter(Boolean).join(', ') || '—';

    document.getElementById('mDetailHead').innerHTML = `
        <img src="https://ui-avatars.com/api/?name=${encodeURIComponent(p.nama)}&background=0E9E8D&color=fff&bold=true" class="m-avatar">
        <div>
            <div class="m-dname">${p.nama}</div>
            <div class="m-dnik">${p.nik}</div>
        </div>`;

    const statusBadge = p.status_penyakit
        ? `<span style="background:${p.status_penyakit==='Sehat'?'#d1fae5':p.status_penyakit==='Disabilitas'?'#fef3c7':'#fee2e2'};color:${p.status_penyakit==='Sehat'?'#065f46':p.status_penyakit==='Disabilitas'?'#92400e':'#991b1b'};padding:2px 10px;border-radius:20px;font-size:.75rem;font-weight:700;">${p.status_penyakit}</span>`
        : '—';

    document.getElementById('mDetailBody').innerHTML = `
        <div class="detail-row"><span class="detail-label">NIK</span><span class="detail-value" style="font-family:monospace">${p.nik}</span></div>
        <div class="detail-row"><span class="detail-label">Nama</span><span class="detail-value">${p.nama}</span></div>
        <div class="detail-row"><span class="detail-label">Jenis Kelamin</span><span class="detail-value">${p.jenis_kelamin||'—'}</span></div>
        <div class="detail-row"><span class="detail-label">Tanggal Lahir</span><span class="detail-value">${tgl_lahir}</span></div>
        <div class="detail-row"><span class="detail-label">Golongan Darah</span><span class="detail-value">${p.gol_darah||'—'}</span></div>
        <div class="detail-row"><span class="detail-label">Alamat</span><span class="detail-value" style="max-width:70%;">${alamat_full}</span></div>
        <div class="detail-row"><span class="detail-label">Rekam Medis</span><span class="detail-value">${p.jumlah_rekam} pemeriksaan</span></div>
        <div class="detail-row"><span class="detail-label">Status</span><span class="detail-value">${statusBadge}</span></div>
        <div class="detail-row"><span class="detail-label">Tgl Daftar</span><span class="detail-value">${tgl_daftar}</span></div>
        <div class="detail-row"><span class="detail-label">Sumber Data</span><span class="detail-value">${p.sumber_data||'Manual'}</span></div>`;

    document.getElementById('mDetailCetak').href = `cetak_surat.php?nik=${encodeURIComponent(p.nik)}&last=1`;
    document.getElementById('mDetailCetak').style.display = p.jumlah_rekam > 0 ? '' : 'none';
    buka('mDetail');
}

// ── Modal EDIT ───────────────────────────────────────────────────
function bukaEdit(p) {
    document.getElementById('eNik').value        = p.nik;
    document.getElementById('eNikDisplay').textContent = p.nik;
    document.getElementById('eNama').value       = p.nama       || '';
    document.getElementById('eJk').value         = p.jenis_kelamin || '';
    document.getElementById('eTglLahir').value   = p.tanggal_lahir ? p.tanggal_lahir.split(' ')[0] : '';
    document.getElementById('eGol').value        = p.gol_darah  || '';
    document.getElementById('eAlamat').value     = p.alamat     || '';
    document.getElementById('eRtRw').value       = p.rt_rw      || '';
    document.getElementById('eKecamatan').value  = p.kecamatan  || '';
    document.getElementById('eKabupaten').value  = p.kabupaten  || '';
    document.getElementById('eProvinsi').value   = p.provinsi   || '';
    buka('mEdit');
}

function submitEdit() {
    const btn = document.getElementById('btnSimpanEdit');
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Menyimpan...';
    btn.disabled = true;

    const form = document.getElementById('formEdit');
    const data = new FormData(form);
    data.append('aksi','edit_pasien');

    fetch('index.php?page=lihat_data', { method:'POST', body:data })
        .then(r => r.json())
        .then(d => {
            btn.innerHTML = '<i class="fa-solid fa-floppy-disk me-1"></i> Simpan Perubahan';
            btn.disabled = false;
            tutup('mEdit');
            if (d.ok) {
                showToast(d.msg, 'ok');
                setTimeout(() => location.reload(), 1200);
            } else {
                showToast(d.msg, 'err');
            }
        })
        .catch(() => {
            btn.innerHTML = '<i class="fa-solid fa-floppy-disk me-1"></i> Simpan Perubahan';
            btn.disabled = false;
            showToast('Koneksi gagal. Coba lagi.', 'err');
        });
}

// ── Modal HAPUS ──────────────────────────────────────────────────
function bukaHapus(nik, nama) {
    _hapusNik = nik;
    document.getElementById('hNama').textContent = nama;
    document.getElementById('hNik').textContent  = nik;
    buka('mHapus');
}

function submitHapus() {
    const btn = document.getElementById('btnHapusOk');
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Menghapus...';
    btn.disabled = true;

    const data = new FormData();
    data.append('aksi', 'hapus_pasien');
    data.append('nik', _hapusNik);

    fetch('index.php?page=lihat_data', { method:'POST', body:data })
        .then(r => r.json())
        .then(d => {
            btn.innerHTML = '<i class="fa-solid fa-trash me-1"></i> Ya, Hapus Sekarang';
            btn.disabled = false;
            tutup('mHapus');
            if (d.ok) {
                showToast(d.msg, 'ok');
                setTimeout(() => location.reload(), 1200);
            } else {
                showToast(d.msg, 'err');
            }
        })
        .catch(() => {
            btn.innerHTML = '<i class="fa-solid fa-trash me-1"></i> Ya, Hapus Sekarang';
            btn.disabled = false;
            showToast('Koneksi gagal. Coba lagi.', 'err');
        });
}
</script>