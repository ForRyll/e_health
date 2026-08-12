<?php
// ================================================================
// surat_sehat.php — Input Rekam Medis & Cetak Surat Sehat
// Terintegrasi dengan UI Master Template PELITA
// ================================================================
if (!isset($conn)) require_once 'koneksi.php';

// ── Penanganan AJAX Pencarian Pasien (Tanpa Error HTML) ─────────
if (isset($_GET['fetch_pasien'])) {
    ob_clean(); // Membersihkan output HTML dari index.php agar JSON tidak rusak
    header('Content-Type: application/json');
    $nik = mysqli_real_escape_string($conn, trim($_GET['fetch_pasien']));
    
    $res = mysqli_query($conn, "SELECT nik, nama, gol_darah, jenis_kelamin, alamat FROM pasien WHERE nik='$nik' LIMIT 1");
    if ($res && mysqli_num_rows($res) > 0) {
        echo json_encode(['ok'=>true, 'data'=>mysqli_fetch_assoc($res)]);
    } else {
        echo json_encode(['ok'=>false, 'msg'=>'NIK tidak ditemukan. Pasien belum terdaftar.']);
    }
    exit;
}

$msg_ok  = '';
$msg_err = '';
$id_baru = null;
$prefill_nik = trim($_GET['cari_nik'] ?? '');

// ── Proses POST: Simpan rekam medis ──────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_rekam'])) {
    $nik             = trim($_POST['nik'] ?? '');
    $gol_darah       = trim($_POST['gol_darah'] ?? '');
    $tinggi          = (int)($_POST['tinggi'] ?? 0);
    $berat           = (int)($_POST['berat'] ?? 0);
    $status_penyakit = trim($_POST['status_penyakit'] ?? '');
    $tes_narkoba     = trim($_POST['tes_narkoba'] ?? '');

    if (!$nik || !$gol_darah || !$tinggi || !$berat || !$status_penyakit || !$tes_narkoba) {
        $msg_err = 'Semua form data medis wajib diisi!';
    } else {
        $cek = mysqli_query($conn, "SELECT nik, nama FROM pasien WHERE nik='".mysqli_real_escape_string($conn,$nik)."' LIMIT 1");
        if (!$cek || mysqli_num_rows($cek) === 0) {
            $msg_err = 'NIK belum terdaftar! Silakan registrasi pasien terlebih dahulu.';
        } else {
            $pasien_row = mysqli_fetch_assoc($cek);
            $nik_s   = mysqli_real_escape_string($conn, $nik);
            $gol_s   = mysqli_real_escape_string($conn, $gol_darah);
            $sp_s    = mysqli_real_escape_string($conn, $status_penyakit);
            $tn_s    = mysqli_real_escape_string($conn, $tes_narkoba);

            $ins = mysqli_query($conn,
                "INSERT INTO rekam_medis (nik_pasien, gol_darah, tinggi_badan, berat_badan, status_penyakit, tes_narkoba)
                 VALUES ('$nik_s', '$gol_s', $tinggi, $berat, '$sp_s', '$tn_s')"
            );

            if ($ins) {
                $id_baru = mysqli_insert_id($conn);
                mysqli_query($conn, "UPDATE pasien SET gol_darah='$gol_s' WHERE nik='$nik_s'");
                $msg_ok = "Rekam medis untuk <b>{$pasien_row['nama']}</b> berhasil disimpan! Data sudah siap ditarik via API.";
            } else {
                $msg_err = 'Sistem Database Error: ' . mysqli_error($conn);
            }
        }
    }
}

// ── Ambil daftar rekam medis terbaru ─────────────────────────────
$rekam_list = [];
$rl = mysqli_query($conn,
    "SELECT r.*, p.nama, p.jenis_kelamin 
     FROM rekam_medis r
     JOIN pasien p ON p.nik = r.nik_pasien
     ORDER BY r.tanggal_periksa DESC LIMIT 6"
);
if ($rl) while ($row = mysqli_fetch_assoc($rl)) $rekam_list[] = $row;
?>

<style>
/* ─── STYLING KHUSUS SURAT SEHAT (Tema PELITA) ─── */
.ss-wrapper { display: grid; grid-template-columns: 1fr 380px; gap: 24px; align-items: start; }
@media(max-width: 992px) { .ss-wrapper { grid-template-columns: 1fr; } }

.hw-panel { background: white; border-radius: 16px; padding: 26px; box-shadow: 0 4px 20px rgba(0,0,0,.05); border: 1px solid rgba(0,0,0,.04); }
.hw-panel-header { display: flex; align-items: center; gap: 12px; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid #f3f4f6; }
.hw-icon-box { width: 44px; height: 44px; border-radius: 12px; background: linear-gradient(135deg, #0B7B6E, #0AD4BC); color: white; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; }
.hw-title { font-family: 'Syne', sans-serif; font-size: 1.1rem; font-weight: 700; color: #1f2937; margin: 0; }
.hw-subtitle { font-size: .8rem; color: #6b7280; margin: 0; }

/* Form Fields */
.form-group { margin-bottom: 18px; }
.form-label { font-size: .78rem; font-weight: 700; color: #4A6278; margin-bottom: 8px; display: block; text-transform: uppercase; letter-spacing: .5px; }
.form-control-styled { width: 100%; padding: 12px 16px; border: 1.5px solid #e5e7eb; border-radius: 10px; font-size: .95rem; font-family: 'DM Sans', sans-serif; transition: all .2s; background: #fafafa; }
.form-control-styled:focus { border-color: #0E9E8D; background: white; outline: none; box-shadow: 0 0 0 3px rgba(14,158,141,.15); }
.form-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 16px; }

/* NIK Search Bar */
.nik-search-box { position: relative; }
.nik-search-box input { padding-right: 50px; font-family: 'Courier New', Courier, monospace; font-weight: bold; letter-spacing: 1px; font-size: 1.1rem; }
.nik-btn { position: absolute; right: 6px; top: 50%; transform: translateY(-50%); background: #0E9E8D; color: white; border: none; border-radius: 8px; width: 36px; height: 36px; cursor: pointer; transition: .2s; }
.nik-btn:hover { background: #0B7B6E; }

/* Search Result Indicator */
#nikResult { margin-top: 10px; padding: 12px 16px; border-radius: 10px; font-size: .85rem; display: none; align-items: center; gap: 8px; font-weight: 600; }
#nikResult.loading { display: flex; background: #f3f4f6; color: #6b7280; border: 1px solid #e5e7eb; }
#nikResult.success { display: flex; background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
#nikResult.error   { display: flex; background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

/* Buttons */
.btn-primary-hw { background: linear-gradient(135deg, #0B7B6E, #0AD4BC); color: white; border: none; border-radius: 10px; padding: 14px 24px; font-size: .95rem; font-weight: 700; cursor: pointer; transition: all .3s; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 15px rgba(14,158,141,.25); }
.btn-primary-hw:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(14,158,141,.4); }
.btn-print-alert { background: white; color: #0E9E8D; border: 2px solid #0E9E8D; padding: 10px 20px; border-radius: 8px; font-weight: 700; text-decoration: none; transition: .2s; display: inline-flex; align-items: center; gap: 6px; }
.btn-print-alert:hover { background: #0E9E8D; color: white; }

/* Recent Medical Records List */
.rm-item { display: flex; align-items: flex-start; gap: 12px; padding: 14px 0; border-bottom: 1px solid #f3f4f6; }
.rm-item:last-child { border-bottom: none; }
.rm-avatar { width: 40px; height: 40px; border-radius: 10px; background: #f3f4f6; color: #0B7B6E; display: flex; align-items: center; justify-content: center; font-weight: 700; flex-shrink: 0; }
.rm-name { font-weight: 700; color: #1f2937; font-size: .88rem; margin-bottom: 2px; }
.rm-nik { font-family: monospace; color: #9ca3af; font-size: .75rem; letter-spacing: .5px; }
.rm-badges { display: flex; gap: 6px; margin-top: 6px; flex-wrap: wrap; }
.badge-status { font-size: .65rem; padding: 3px 8px; border-radius: 20px; font-weight: 700; }
.b-sehat { background: #dcfce7; color: #166534; } .b-sakit { background: #fee2e2; color: #991b1b; }
.b-negatif { background: #e0f2fe; color: #075985; } .b-positif { background: #fef3c7; color: #92400e; }
.rm-action { margin-left: auto; display: flex; flex-direction: column; align-items: flex-end; gap: 8px; }
.rm-date { font-size: .7rem; color: #9ca3af; font-weight: 600; }
.btn-print-sm { font-size: .7rem; color: #0E9E8D; border: 1px solid #0E9E8D; background: white; padding: 4px 10px; border-radius: 6px; text-decoration: none; font-weight: 700; transition: .2s; }
.btn-print-sm:hover { background: #0E9E8D; color: white; }

.alert-hw { border-radius: 12px; padding: 20px; border: 1px solid transparent; margin-bottom: 24px; animation: slideDown 0.4s ease; }
.alert-hw.success { background: #ecfdf5; border-color: #a7f3d0; color: #065f46; }
.alert-hw.error { background: #fef2f2; border-color: #fecaca; color: #991b1b; }
@keyframes slideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
</style>

<?php if ($msg_ok): ?>
<div class="alert-hw success d-flex align-items-center gap-3">
    <i class="fa-solid fa-circle-check fa-2x"></i>
    <div style="flex: 1;">
        <div style="font-weight: 700; font-size: 1rem;"><?= $msg_ok ?></div>
        <div style="font-size: .8rem; opacity: .8; margin-top: 2px;">Data berhasil dienkripsi dan disimpan. ID Surat: #<?= $id_baru ?></div>
    </div>
    <?php if ($id_baru): ?>
    <a href="index.php?id=<?= $id_baru ?>" target="_blank" class="btn-print-alert">
        <i class="fa-solid fa-print"></i> Cetak PDF
    </a>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if ($msg_err): ?>
<div class="alert-hw error d-flex align-items-center gap-3">
    <i class="fa-solid fa-triangle-exclamation fa-2x"></i>
    <div>
        <div style="font-weight: 700; font-size: 1rem;">Gagal Menyimpan Data</div>
        <div style="font-size: .85rem; margin-top: 2px;"><?= $msg_err ?></div>
    </div>
</div>
<?php endif; ?>

<div class="ss-wrapper">

    <div class="hw-panel">
        <div class="hw-panel-header">
            <div class="hw-icon-box"><i class="fa-solid fa-notes-medical"></i></div>
            <div>
                <h3 class="hw-title">Formulir Rekam Medis & Surat Sehat</h3>
                <p class="hw-subtitle">Data ini akan menjadi rujukan utama bagi Node Kepolisian (SKCK) dan Node Bansos.</p>
            </div>
        </div>

        <form method="POST" action="index.php?page=surat_sehat" id="formMedis">
            <input type="hidden" name="simpan_rekam" value="1">

            <div class="form-group">
                <label class="form-label"><i class="fa-solid fa-id-card me-1"></i> NIK Pasien <span class="text-danger">*</span></label>
                <div class="nik-search-box">
                    <input type="text" name="nik" id="inputNik" class="form-control-styled" 
                           placeholder="3301 xxxx xxxx xxxx" maxlength="16" autocomplete="off"
                           value="<?= htmlspecialchars($prefill_nik ?: ($_POST['nik'] ?? '')) ?>">
                    <button type="button" class="nik-btn" onclick="cariPasien()"><i class="fa-solid fa-magnifying-glass"></i></button>
                </div>
                <div id="nikResult"></div>
            </div>

            <hr style="border-color: #f3f4f6; margin: 24px 0;">

            <div class="form-row mb-3">
                <div class="form-group mb-0">
                    <label class="form-label">Golongan Darah</label>
                    <select name="gol_darah" id="selGol" class="form-control-styled">
                        <option value="" disabled selected>Pilih...</option>
                        <?php foreach(['A','B','AB','O'] as $g): ?>
                        <option value="<?= $g ?>" <?= ($_POST['gol_darah']??'')===$g?'selected':'' ?>><?= $g ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group mb-0">
                    <label class="form-label">Tinggi Badan (cm)</label>
                    <input type="number" name="tinggi" class="form-control-styled" placeholder="170" min="50" max="250" value="<?= htmlspecialchars($_POST['tinggi'] ?? '') ?>" required>
                </div>
                <div class="form-group mb-0">
                    <label class="form-label">Berat Badan (kg)</label>
                    <input type="number" name="berat" class="form-control-styled" placeholder="65" min="10" max="300" value="<?= htmlspecialchars($_POST['berat'] ?? '') ?>" required>
                </div>
            </div>

            <div class="form-group mt-4">
                <label class="form-label"><i class="fa-solid fa-hand-holding-heart me-1 text-warning"></i> Status Kelayakan Medis (Untuk Bansos)</label>
                <select name="status_penyakit" class="form-control-styled" required>
                    <option value="" disabled selected>Pilih Status...</option>
                    <option value="Sehat" <?= ($_POST['status_penyakit']??'')==='Sehat'?'selected':'' ?>>Sehat Bugar (Tidak ada penyakit kronis)</option>
                    <option value="Disabilitas" <?= ($_POST['status_penyakit']??'')==='Disabilitas'?'selected':'' ?>>Penyandang Disabilitas</option>
                    <option value="Penyakit Kronis" <?= ($_POST['status_penyakit']??'')==='Penyakit Kronis'?'selected':'' ?>>Penyakit Kronis (Menahun)</option>
                </select>
                <small class="text-muted d-block mt-1"><i class="fa-solid fa-circle-info"></i> Node Bansos akan menarik data ini untuk seleksi penerima bantuan kesehatan.</small>
            </div>

            <div class="form-group mt-3">
                <label class="form-label"><i class="fa-solid fa-shield-halved me-1 text-primary"></i> Tes Bebas Narkoba (Untuk SKCK Kepolisian)</label>
                <select name="tes_narkoba" class="form-control-styled" required>
                    <option value="" disabled selected>Pilih Hasil Tes...</option>
                    <option value="Negatif" <?= ($_POST['tes_narkoba']??'')==='Negatif'?'selected':'' ?>>✅ Negatif (Bebas Narkoba)</option>
                    <option value="Positif" <?= ($_POST['tes_narkoba']??'')==='Positif'?'selected':'' ?>>🔴 Positif (Terindikasi)</option>
                </select>
                <small class="text-muted d-block mt-1"><i class="fa-solid fa-circle-info"></i> Node Kepolisian (SKCK) akan me-reject otomatis jika hasilnya Positif.</small>
            </div>

            <div class="mt-4 pt-3 border-top d-flex gap-2">
                <button type="submit" class="btn-primary-hw w-100 justify-content-center">
                    <i class="fa-solid fa-floppy-disk"></i> Simpan Rekam Medis
                </button>
            </div>
        </form>
    </div>

    <div class="hw-panel">
        <div class="hw-panel-header" style="margin-bottom: 16px;">
            <div class="hw-icon-box" style="background: #f3f4f6; color: #0B7B6E; width: 38px; height: 38px; font-size: 1rem;"><i class="fa-solid fa-clock-rotate-left"></i></div>
            <div>
                <h3 class="hw-title" style="font-size: 1rem;">Rekam Medis Terbaru</h3>
                <p class="hw-subtitle" style="font-size: .75rem;">Siap ditarik via API</p>
            </div>
        </div>

        <?php if (empty($rekam_list)): ?>
        <div class="text-center py-5">
            <i class="fa-solid fa-folder-open fa-3x text-muted opacity-25 mb-3"></i>
            <p class="text-muted" style="font-size: .85rem;">Belum ada rekam medis yang diterbitkan.</p>
        </div>
        <?php else: foreach ($rekam_list as $rm):
            // Setup Badges
            $sts = $rm['status_penyakit'];
            $st_cls = ($sts === 'Sehat') ? 'b-sehat' : 'b-sakit';
            $nrk = $rm['tes_narkoba'];
            $nr_cls = ($nrk === 'Negatif') ? 'b-negatif' : 'b-positif';
            $inisial = strtoupper(substr($rm['nama'] ?? 'P', 0, 1));
        ?>
        <div class="rm-item">
            <div class="rm-avatar"><?= $inisial ?></div>
            <div>
                <div class="rm-name"><?= htmlspecialchars($rm['nama']) ?></div>
                <div class="rm-nik"><?= htmlspecialchars($rm['nik_pasien']) ?></div>
                <div class="rm-badges">
                    <span class="badge-status <?= $st_cls ?>"><?= $sts ?></span>
                    <span class="badge-status <?= $nr_cls ?>"><?= $nrk ?></span>
                </div>
            </div>
            <div class="rm-action">
                <div class="rm-date"><?= date('d/m/y', strtotime($rm['tanggal_periksa'])) ?></div>
                <a href="cetak_surat.php?id=<?= $rm['id_pemeriksaan'] ?>" target="_blank" class="btn-print-sm">
                    <i class="fa-solid fa-print"></i> Cetak
                </a>
            </div>
        </div>
        <?php endforeach; endif; ?>
    </div>

</div>

<script>
function cariPasien() {
    const nik = document.getElementById('inputNik').value.replace(/\D/g, ''); // Hapus karakter non-angka
    document.getElementById('inputNik').value = nik; // Update input
    const res = document.getElementById('nikResult');
    
    if (nik.length < 16) {
        res.className = 'error';
        res.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> NIK harus 16 digit angka!';
        return;
    }
    
    res.className = 'loading';
    res.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Memverifikasi NIK ke database...';

    fetch(`index.php?page=surat_sehat&fetch_pasien=${encodeURIComponent(nik)}`)
        .then(r => r.json())
        .then(d => {
            if (d.ok) {
                res.className = 'success';
                res.innerHTML = `
                    <i class="fa-solid fa-circle-check fa-lg"></i>
                    <div>
                        <div style="font-size: .75rem; text-transform: uppercase; letter-spacing: 1px;">Pasien Ditemukan</div>
                        <div style="font-size: .95rem;">${d.data.nama}</div>
                    </div>
                `;
                // Auto-fill golongan darah jika sudah ada
                if (d.data.gol_darah) {
                    document.getElementById('selGol').value = d.data.gol_darah;
                }
            } else {
                res.className = 'error';
                res.innerHTML = `<i class="fa-solid fa-circle-xmark fa-lg"></i> <div>${d.msg}</div>`;
            }
        })
        .catch(err => {
            res.className = 'error';
            res.innerHTML = '<i class="fa-solid fa-wifi"></i> <div>Gagal terhubung ke sistem.</div>';
        });
}

// Event Listeners
document.addEventListener('DOMContentLoaded', () => {
    // Auto-search jika dilempar parameter dari URL
    const nik = document.getElementById('inputNik').value.trim();
    if (nik.length === 16) cariPasien();

    // Trigger pencarian saat tekan Enter di input NIK
    document.getElementById('inputNik').addEventListener('keydown', e => {
        if (e.key === 'Enter') { e.preventDefault(); cariPasien(); }
    });
});
</script>