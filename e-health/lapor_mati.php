<?php
// ============================================================
// lapor_mati.php — Pelaporan Kematian Pasien
// Dipanggil via include dari index.php (session & $conn aktif)
// ============================================================

require_once 'koneksi.php';

// Pastikan tabel laporan_kematian ada (auto-create jika belum)
mysqli_query($conn, "
    CREATE TABLE IF NOT EXISTS laporan_kematian (
        id            INT AUTO_INCREMENT PRIMARY KEY,
        nik_pasien    VARCHAR(16) NOT NULL,
        nama_pasien   VARCHAR(100) NOT NULL,
        tanggal_lapor DATE NOT NULL,
        jam_lapor     TIME NOT NULL,
        penyebab      VARCHAR(255) DEFAULT NULL,
        pelapor       VARCHAR(100) DEFAULT NULL,
        status_kirim  ENUM('Pending','Terkirim') DEFAULT 'Pending',
        created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (nik_pasien) REFERENCES pasien(nik) ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$alert      = '';
$alert_type = '';
$pasien     = null;
$nik_cari   = '';
$laporan_berhasil = null; // data laporan yang baru disimpan (untuk ringkasan)

// ── STEP 1: Cari pasien via NIK ──
if (isset($_GET['cari_nik'])) {
    $nik_cari = preg_replace('/\D/', '', trim($_GET['cari_nik']));
    if (strlen($nik_cari) === 16) {
        $nik_s  = mysqli_real_escape_string($conn, $nik_cari);

        // Cek apakah sudah pernah dilaporkan meninggal
        $cek_lp = mysqli_query($conn, "SELECT id FROM laporan_kematian WHERE nik_pasien='$nik_s' LIMIT 1");
        if ($cek_lp && mysqli_num_rows($cek_lp) > 0) {
            $alert      = "NIK <strong>$nik_cari</strong> sudah pernah dilaporkan meninggal sebelumnya. Tidak dapat diproses ulang.";
            $alert_type = 'warning';
        } else {
            $result = mysqli_query($conn, "SELECT * FROM pasien WHERE nik='$nik_s' LIMIT 1");
            if ($result && mysqli_num_rows($result) === 1) {
                $pasien = mysqli_fetch_assoc($result);
            } else {
                $alert      = "NIK <strong>$nik_cari</strong> tidak ditemukan di database pasien. Pastikan pasien sudah terdaftar.";
                $alert_type = 'danger';
            }
        }
    } else {
        $alert      = 'NIK harus tepat 16 digit angka.';
        $alert_type = 'danger';
    }
}

// ── STEP 2: Simpan laporan kematian ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kirim_laporan'])) {
    $nik_pasien   = preg_replace('/\D/', '', trim($_POST['nik_pasien'] ?? ''));
    $tgl_lapor    = trim($_POST['tanggal_lapor'] ?? '');
    $jam_lapor    = trim($_POST['jam_lapor']     ?? '');
    $penyebab     = trim($_POST['penyebab']      ?? '');
    $pelapor      = trim($_POST['pelapor']        ?? '');
    $konfirmasi   = isset($_POST['konfirmasi']);

    $errors = [];
    if (strlen($nik_pasien) !== 16) $errors[] = 'NIK tidak valid.';
    if (empty($tgl_lapor))          $errors[] = 'Tanggal kejadian wajib diisi.';
    if (empty($jam_lapor))          $errors[] = 'Jam kejadian wajib diisi.';
    if (empty($pelapor))            $errors[] = 'Nama pelapor wajib diisi.';
    if (!$konfirmasi)               $errors[] = 'Centang kotak konfirmasi untuk melanjutkan.';

    // Re-load data pasien
    if (empty($errors)) {
        $nik_s  = mysqli_real_escape_string($conn, $nik_pasien);
        $re     = mysqli_query($conn, "SELECT * FROM pasien WHERE nik='$nik_s' LIMIT 1");
        if (!$re || mysqli_num_rows($re) === 0) {
            $errors[] = 'Data pasien tidak ditemukan.';
        } else {
            $pasien = mysqli_fetch_assoc($re);
        }
    }

    // Cek duplikat
    if (empty($errors)) {
        $cek_lp = mysqli_query($conn, "SELECT id FROM laporan_kematian WHERE nik_pasien='$nik_s' LIMIT 1");
        if ($cek_lp && mysqli_num_rows($cek_lp) > 0) {
            $errors[] = 'NIK ini sudah pernah dilaporkan sebelumnya.';
        }
    }

    if (empty($errors)) {
        $nama_s    = mysqli_real_escape_string($conn, $pasien['nama']);
        $tgl_s     = mysqli_real_escape_string($conn, $tgl_lapor);
        $jam_s     = mysqli_real_escape_string($conn, $jam_lapor);
        $penyebab_s= mysqli_real_escape_string($conn, $penyebab);
        $pelapor_s = mysqli_real_escape_string($conn, $pelapor);
        $user_s    = mysqli_real_escape_string($conn, $_SESSION['user'] ?? 'Sistem');

        $sql = "INSERT INTO laporan_kematian
                    (nik_pasien, nama_pasien, tanggal_lapor, jam_lapor, penyebab, pelapor, status_kirim)
                VALUES
                    ('$nik_s', '$nama_s', '$tgl_s', '$jam_s', '$penyebab_s', '$pelapor_s', 'Terkirim')";

        if (mysqli_query($conn, $sql)) {
            $id_laporan = mysqli_insert_id($conn);
            $laporan_berhasil = [
                'id'      => $id_laporan,
                'nama'    => $pasien['nama'],
                'nik'     => $nik_pasien,
                'tgl'     => $tgl_lapor,
                'jam'     => $jam_lapor,
                'penyebab'=> $penyebab ?: 'Tidak disebutkan',
                'pelapor' => $pelapor,
            ];
            $alert      = '';
            $pasien     = null;
            $nik_cari   = '';
        } else {
            $alert      = 'Gagal menyimpan laporan: ' . mysqli_error($conn);
            $alert_type = 'danger';
        }
    } else {
        $alert      = implode('<br>', $errors);
        $alert_type = 'danger';
        // Reload pasien supaya form tetap tampil
        if (strlen($nik_pasien) === 16) {
            $nik_s = mysqli_real_escape_string($conn, $nik_pasien);
            $re    = mysqli_query($conn, "SELECT * FROM pasien WHERE nik='$nik_s' LIMIT 1");
            if ($re && mysqli_num_rows($re) === 1) $pasien = mysqli_fetch_assoc($re);
            $nik_cari = $nik_pasien;
        }
    }
}

// Riwayat 5 laporan terakhir
$riwayat = [];
$q_r = mysqli_query($conn, "SELECT * FROM laporan_kematian ORDER BY created_at DESC LIMIT 5");
if ($q_r) while ($r = mysqli_fetch_assoc($q_r)) $riwayat[] = $r;
?>

<style>
.lm-wrapper { max-width: 860px; }

/* Alert */
.lm-alert {
    border-radius: 12px; padding: 14px 18px; font-size:.875rem;
    border: none; display:flex; align-items:flex-start; gap:12px; margin-bottom:20px;
}
.lm-alert i { flex-shrink:0; margin-top:2px; }
.lm-alert.success { background:#d1fae5; color:#065f46; }
.lm-alert.danger  { background:#fee2e2; color:#991b1b; }
.lm-alert.warning { background:#fef3c7; color:#92400e; }

/* Card */
.lm-card { background:white; border-radius:18px; box-shadow:0 2px 20px rgba(0,0,0,.07); overflow:hidden; margin-bottom:20px; }
.lm-card-header {
    background: linear-gradient(135deg, #7f1d1d, #b91c1c 60%, #ef4444);
    padding:22px 28px; color:white;
}
.lm-card-header h5 { font-size:1rem; font-weight:700; margin:0 0 3px; }
.lm-card-header p  { font-size:.78rem; opacity:.75; margin:0; }
.lm-card-body { padding:26px 28px; }

/* NIK Search */
.nik-search-row { display:flex; gap:10px; align-items:flex-end; }
.nik-search-row input {
    flex:1; border-radius:10px; border:1.5px solid #fecaca;
    padding:10px 14px; font-size:.875rem; font-family:monospace;
    letter-spacing:1px; transition:border-color .2s, box-shadow .2s; color:#1f2937;
}
.nik-search-row input:focus {
    border-color:#ef4444; box-shadow:0 0 0 3px rgba(239,68,68,.12); outline:none;
}
.btn-cari-red {
    background:#b91c1c; color:white; border:none; border-radius:10px;
    padding:10px 22px; font-size:.875rem; font-weight:600; cursor:pointer;
    white-space:nowrap; transition:all .2s; height:42px;
}
.btn-cari-red:hover { background:#7f1d1d; transform:translateY(-1px); }

/* Pasien ditemukan */
.pasien-danger-box {
    background: linear-gradient(135deg, #fef2f2, #fee2e2);
    border: 1.5px solid #fca5a5; border-radius:14px;
    padding:16px 20px; display:flex; align-items:center; gap:16px; margin-bottom:24px;
}
.pasien-avatar-red {
    width:52px; height:52px; border-radius:14px;
    background:linear-gradient(135deg, #7f1d1d, #ef4444);
    display:flex; align-items:center; justify-content:center;
    color:white; font-size:1.4rem; flex-shrink:0;
}
.pasien-nama  { font-size:.95rem; font-weight:700; color:#7f1d1d; margin:0 0 3px; }
.pasien-meta  { font-size:.78rem; color:#b91c1c; }
.pasien-nik   { font-family:monospace; font-size:.8rem; color:#b91c1c; }

/* Field section */
.field-section {
    font-size:.7rem; font-weight:700; text-transform:uppercase;
    letter-spacing:1.4px; color:#9ca3af; margin:22px 0 14px;
    display:flex; align-items:center; gap:10px;
}
.field-section::after { content:''; flex:1; height:1px; background:#f3f4f6; }
.field-section:first-child { margin-top:0; }

/* Form */
.form-label { font-size:.78rem; font-weight:600; color:#374151; margin-bottom:5px; display:block; }
.form-control, .form-select {
    border-radius:10px; border:1.5px solid #e5e7eb; font-size:.875rem;
    padding:10px 14px; width:100%; color:#1f2937;
    transition:border-color .2s, box-shadow .2s; background:white;
}
.form-control:focus, .form-select:focus {
    border-color:#ef4444; box-shadow:0 0 0 3px rgba(239,68,68,.12); outline:none;
}

/* Konfirmasi checkbox */
.konfirmasi-box {
    background:#fff1f2; border:1.5px solid #fecaca; border-radius:12px;
    padding:16px 18px; margin:20px 0; display:flex; align-items:flex-start; gap:12px;
}
.konfirmasi-box input[type=checkbox] { width:18px; height:18px; margin-top:2px; accent-color:#b91c1c; flex-shrink:0; cursor:pointer; }
.konfirmasi-box label { font-size:.83rem; color:#7f1d1d; line-height:1.5; cursor:pointer; }

/* Tombol simpan */
.btn-lapor {
    background:linear-gradient(135deg,#7f1d1d,#b91c1c);
    color:white; border:none; border-radius:12px; padding:13px 28px;
    font-size:.9rem; font-weight:600; width:100%; cursor:pointer;
    transition:all .25s; display:flex; align-items:center; justify-content:center; gap:9px;
}
.btn-lapor:hover { background:linear-gradient(135deg,#5c1313,#7f1d1d); transform:translateY(-1px); box-shadow:0 6px 20px rgba(185,28,28,.35); }
.btn-lapor:disabled { opacity:.4; cursor:not-allowed; transform:none; }

/* Sukses card */
.sukses-card {
    background:white; border-radius:18px; box-shadow:0 2px 20px rgba(0,0,0,.07);
    overflow:hidden; margin-bottom:20px; border-top:4px solid #22c55e;
}
.sukses-header { background:#f0fdf4; padding:20px 28px; border-bottom:1px solid #bbf7d0; }
.sukses-header h5 { color:#15803d; font-size:1rem; font-weight:700; margin:0; }
.sukses-body { padding:24px 28px; }
.detail-row { display:flex; justify-content:space-between; padding:9px 0; border-bottom:1px solid #f3f4f6; font-size:.875rem; }
.detail-row:last-child { border-bottom:none; }
.detail-label { color:#6b7280; font-weight:500; }
.detail-value { color:#1f2937; font-weight:600; text-align:right; }
.api-badge { background:#dcfce7; color:#15803d; padding:3px 12px; border-radius:20px; font-size:.72rem; font-weight:700; }

/* Riwayat */
.riwayat-card { background:white; border-radius:18px; box-shadow:0 2px 16px rgba(0,0,0,.06); overflow:hidden; }
.riwayat-header { padding:16px 22px; border-bottom:1px solid #f3f4f6; display:flex; justify-content:space-between; align-items:center; }
.riwayat-header h6 { font-size:.9rem; font-weight:700; color:#1f2937; margin:0; }
.riwayat-table { width:100%; border-collapse:collapse; font-size:.8rem; }
.riwayat-table th { padding:10px 14px; background:#f9fafb; color:#6b7280; font-size:.7rem; text-transform:uppercase; letter-spacing:.8px; font-weight:700; text-align:left; }
.riwayat-table td { padding:11px 14px; border-top:1px solid #f3f4f6; color:#374151; vertical-align:middle; }
.riwayat-table tr:hover td { background:#fef2f2; }
.badge-terkirim { background:#d1fae5; color:#065f46; padding:3px 10px; border-radius:20px; font-size:.7rem; font-weight:700; }
.badge-pending  { background:#fef3c7; color:#92400e; padding:3px 10px; border-radius:20px; font-size:.7rem; font-weight:700; }

.disabled-form { opacity:.4; pointer-events:none; user-select:none; }
</style>

<div class="lm-wrapper">

    <?php if ($alert): ?>
    <div class="lm-alert <?= $alert_type ?>">
        <i class="fa-solid <?= $alert_type==='warning' ? 'fa-triangle-exclamation' : 'fa-circle-xmark' ?>"></i>
        <div><?= $alert ?></div>
    </div>
    <?php endif; ?>

    <!-- ── RINGKASAN SUKSES ── -->
    <?php if ($laporan_berhasil): ?>
    <div class="sukses-card">
        <div class="sukses-header">
            <h5><i class="fa-solid fa-circle-check me-2"></i>Laporan Kematian Berhasil Dikirim</h5>
        </div>
        <div class="sukses-body">
            <div class="detail-row">
                <span class="detail-label">ID Laporan</span>
                <span class="detail-value">#<?= $laporan_berhasil['id'] ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Nama Pasien</span>
                <span class="detail-value"><?= htmlspecialchars($laporan_berhasil['nama']) ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">NIK</span>
                <span class="detail-value" style="font-family:monospace;"><?= htmlspecialchars($laporan_berhasil['nik']) ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Tanggal & Jam</span>
                <span class="detail-value">
                    <?= date('d M Y', strtotime($laporan_berhasil['tgl'])) ?>, <?= $laporan_berhasil['jam'] ?> WIB
                </span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Penyebab Kematian</span>
                <span class="detail-value"><?= htmlspecialchars($laporan_berhasil['penyebab']) ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Dilaporkan Oleh</span>
                <span class="detail-value"><?= htmlspecialchars($laporan_berhasil['pelapor']) ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Status API ke Induk</span>
                <span class="detail-value"><span class="api-badge"><i class="fa-solid fa-paper-plane me-1"></i>Terkirim ke Node Kependudukan</span></span>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── FORM UTAMA ── -->
    <div class="lm-card">
        <div class="lm-card-header">
            <h5><i class="fa-solid fa-bed-pulse me-2"></i>Form Pelaporan Kematian Pasien</h5>
            <p>Data akan dikirim ke Sistem Induk Kependudukan. Pastikan data benar sebelum mengirim.</p>
        </div>
        <div class="lm-card-body">

            <!-- STEP 1: Cari NIK -->
            <div class="field-section"><i class="fa-solid fa-magnifying-glass" style="color:#ef4444"></i> Langkah 1 — Cari Pasien via NIK</div>
            <form method="GET" action="">
                <input type="hidden" name="page" value="lapor_mati">
                <div class="nik-search-row">
                    <div style="flex:1">
                        <label class="form-label">NIK Pasien yang Meninggal (16 Digit)</label>
                        <input type="text" name="cari_nik" id="nikCari"
                               placeholder="Masukkan NIK lalu tekan Cari"
                               maxlength="16" inputmode="numeric"
                               value="<?= htmlspecialchars($nik_cari) ?>" autocomplete="off">
                    </div>
                    <button type="submit" class="btn-cari-red">
                        <i class="fa-solid fa-search me-1"></i> Cari
                    </button>
                </div>
            </form>

            <!-- STEP 2: Form Laporan (muncul kalau pasien ditemukan) -->
            <form method="POST" action="?page=lapor_mati" id="formLapor">
                <input type="hidden" name="nik_pasien" value="<?= htmlspecialchars($pasien['nik'] ?? '') ?>">

                <?php if ($pasien): ?>
                <div class="pasien-danger-box mt-4">
                    <div class="pasien-avatar-red">
                        <i class="fa-solid <?= ($pasien['jenis_kelamin'] ?? '') === 'Perempuan' ? 'fa-person-dress' : 'fa-person' ?>"></i>
                    </div>
                    <div>
                        <div class="pasien-nama"><?= htmlspecialchars($pasien['nama']) ?></div>
                        <div class="pasien-meta">
                            <?= htmlspecialchars($pasien['jenis_kelamin'] ?? '—') ?> &nbsp;·&nbsp;
                            <?= $pasien['tanggal_lahir'] ? date('d M Y', strtotime($pasien['tanggal_lahir'])) : '—' ?> &nbsp;·&nbsp;
                            <?= htmlspecialchars($pasien['kecamatan'] ? $pasien['kecamatan'].', '.$pasien['kabupaten'] : $pasien['alamat']) ?>
                        </div>
                        <div class="pasien-nik">NIK: <?= htmlspecialchars($pasien['nik']) ?></div>
                    </div>
                    <div class="ms-auto">
                        <span style="background:#fee2e2;color:#b91c1c;padding:4px 12px;border-radius:20px;font-size:.72rem;font-weight:700;">
                            <i class="fa-solid fa-circle-exclamation me-1"></i>Pasien Ditemukan
                        </span>
                    </div>
                </div>
                <?php endif; ?>

                <div class="<?= !$pasien ? 'disabled-form' : '' ?>">
                    <div class="field-section"><i class="fa-solid fa-file-pen" style="color:#ef4444"></i> Langkah 2 — Detail Kejadian</div>

                    <div class="row g-3 mb-2">
                        <div class="col-md-5">
                            <label class="form-label">Tanggal Kejadian *</label>
                            <input type="date" class="form-control" name="tanggal_lapor"
                                   max="<?= date('Y-m-d') ?>"
                                   value="<?= htmlspecialchars($_POST['tanggal_lapor'] ?? date('Y-m-d')) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Jam Kejadian *</label>
                            <input type="time" class="form-control" name="jam_lapor"
                                   value="<?= htmlspecialchars($_POST['jam_lapor'] ?? date('H:i')) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Dilaporkan Oleh *</label>
                            <input type="text" class="form-control" name="pelapor"
                                   placeholder="Nama dokter / petugas"
                                   value="<?= htmlspecialchars($_POST['pelapor'] ?? ($_SESSION['user'] ?? '')) ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Penyebab Kematian <span style="color:#9ca3af;font-weight:400;">(opsional)</span></label>
                            <input type="text" class="form-control" name="penyebab"
                                   placeholder="Cth: Gagal jantung, Kecelakaan, dsb."
                                   value="<?= htmlspecialchars($_POST['penyebab'] ?? '') ?>">
                        </div>
                    </div>

                    <!-- Konfirmasi -->
                    <div class="konfirmasi-box">
                        <input type="checkbox" name="konfirmasi" id="cbKonfirmasi">
                        <label for="cbKonfirmasi">
                            Saya memastikan bahwa data di atas <strong>benar dan dapat dipertanggungjawabkan</strong>.
                            Laporan ini akan dikirim ke <strong>Sistem Induk Kependudukan</strong> dan
                            <strong>tidak dapat dibatalkan</strong> setelah dikirim.
                        </label>
                    </div>

                    <button type="submit" name="kirim_laporan" class="btn-lapor" id="btnLapor" <?= !$pasien ? 'disabled' : '' ?>>
                        <i class="fa-solid fa-paper-plane"></i> Kirim Laporan ke Sistem Induk
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ── RIWAYAT ── -->
    <div class="riwayat-card">
        <div class="riwayat-header">
            <h6><i class="fa-solid fa-clock-rotate-left me-2" style="color:#ef4444"></i>Riwayat Laporan Kematian</h6>
            <span style="font-size:.75rem;color:#9ca3af;">5 laporan terakhir</span>
        </div>
        <?php if (empty($riwayat)): ?>
            <div class="text-center py-4" style="color:#9ca3af;font-size:.85rem;">
                <i class="fa-solid fa-inbox fa-2x mb-2 d-block"></i>Belum ada laporan kematian.
            </div>
        <?php else: ?>
        <div style="overflow-x:auto;">
        <table class="riwayat-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nama Pasien</th>
                    <th>NIK</th>
                    <th>Tgl Kejadian</th>
                    <th>Penyebab</th>
                    <th>Pelapor</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($riwayat as $r): ?>
            <tr>
                <td style="color:#9ca3af;"><?= $r['id'] ?></td>
                <td style="font-weight:600;"><?= htmlspecialchars($r['nama_pasien']) ?></td>
                <td style="font-family:monospace;font-size:.75rem;">
                    <?= substr($r['nik_pasien'],0,6).'..'.substr($r['nik_pasien'],-4) ?>
                </td>
                <td><?= date('d M Y', strtotime($r['tanggal_lapor'])) ?>, <?= substr($r['jam_lapor'],0,5) ?></td>
                <td style="color:#6b7280;"><?= $r['penyebab'] ? htmlspecialchars($r['penyebab']) : '<em style="color:#d1d5db;">—</em>' ?></td>
                <td><?= htmlspecialchars($r['pelapor']) ?></td>
                <td>
                    <span class="<?= $r['status_kirim']==='Terkirim' ? 'badge-terkirim' : 'badge-pending' ?>">
                        <?= $r['status_kirim']==='Terkirim' ? '<i class="fa-solid fa-paper-plane me-1"></i>' : '' ?>
                        <?= $r['status_kirim'] ?>
                    </span>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>

</div>

<script>
// NIK hanya angka
document.getElementById('nikCari').addEventListener('input', function(){
    this.value = this.value.replace(/\D/g,'');
});

// Tombol lapor aktif hanya kalau checkbox dicentang
const cb  = document.getElementById('cbKonfirmasi');
const btn = document.getElementById('btnLapor');
if (cb && btn) {
    cb.addEventListener('change', function(){
        btn.disabled = !this.checked;
    });
    // Default: disabled dulu walau pasien ada
    btn.disabled = true;
}
</script>