<?php
// ============================================================
// registrasi.php — Form Pendaftaran Pasien Baru
// Dipanggil via include dari index.php (session sudah aktif)
// ============================================================

require_once 'koneksi.php';

$alert        = '';          // pesan feedback
$alert_type   = '';          // 'success' | 'danger' | 'warning'
$old          = [];          // simpan nilai lama agar form tidak kosong saat error

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['daftar_pasien'])) {

    // --- Ambil & bersihkan input ---
    $nik          = preg_replace('/\D/', '', trim($_POST['nik'] ?? ''));
    $nama         = trim($_POST['nama'] ?? '');
    $jk           = trim($_POST['jenis_kelamin'] ?? '');
    $tgl_lahir    = trim($_POST['tanggal_lahir'] ?? '');
    $alamat       = trim($_POST['alamat'] ?? '');
    $rt_rw        = trim($_POST['rt_rw'] ?? '');
    $kecamatan    = trim($_POST['kecamatan'] ?? '');
    $kabupaten    = trim($_POST['kabupaten'] ?? '');
    $provinsi     = trim($_POST['provinsi'] ?? '');
    $gol_darah    = trim($_POST['gol_darah'] ?? '');

    $old = compact('nik','nama','jk','tgl_lahir','alamat','rt_rw','kecamatan','kabupaten','provinsi','gol_darah');

    // --- Validasi ---
    $errors = [];
    if (strlen($nik) !== 16)        $errors[] = 'NIK harus tepat 16 digit angka.';
    if (empty($nama))               $errors[] = 'Nama lengkap wajib diisi.';
    if (empty($jk))                 $errors[] = 'Jenis kelamin wajib dipilih.';
    if (empty($tgl_lahir))          $errors[] = 'Tanggal lahir wajib diisi.';
    if (empty($alamat))             $errors[] = 'Alamat wajib diisi.';
    if (empty($kecamatan))          $errors[] = 'Kecamatan wajib diisi.';
    if (empty($kabupaten))          $errors[] = 'Kabupaten/Kota wajib diisi.';
    if (empty($provinsi))           $errors[] = 'Provinsi wajib diisi.';

    if (empty($errors)) {
        // Cek NIK duplikat
        $cek = mysqli_query($conn, "SELECT nik FROM pasien WHERE nik = '$nik' LIMIT 1");
        if (mysqli_num_rows($cek) > 0) {
            $errors[] = "NIK <strong>$nik</strong> sudah terdaftar di sistem. Gunakan fitur pencarian untuk melihat data pasien tersebut.";
        }
    }

    if (empty($errors)) {
        // Escape semua string sebelum query
        $nik_s       = mysqli_real_escape_string($conn, $nik);
        $nama_s      = mysqli_real_escape_string($conn, $nama);
        $jk_s        = mysqli_real_escape_string($conn, $jk);
        $tgl_s       = mysqli_real_escape_string($conn, $tgl_lahir);
        $alamat_s    = mysqli_real_escape_string($conn, $alamat);
        $rt_rw_s     = mysqli_real_escape_string($conn, $rt_rw);
        $kec_s       = mysqli_real_escape_string($conn, $kecamatan);
        $kab_s       = mysqli_real_escape_string($conn, $kabupaten);
        $prov_s      = mysqli_real_escape_string($conn, $provinsi);
        $gol_s       = !empty($gol_darah) ? "'$gol_darah'" : 'NULL';

        $sql = "INSERT INTO pasien
                    (nik, nama, jenis_kelamin, tanggal_lahir, alamat, rt_rw,
                     kecamatan, kabupaten, provinsi, gol_darah, sumber_data)
                VALUES
                    ('$nik_s', '$nama_s', '$jk_s', '$tgl_s', '$alamat_s', '$rt_rw_s',
                     '$kec_s', '$kab_s', '$prov_s', $gol_s, 'Manual')";

        if (mysqli_query($conn, $sql)) {
            $alert      = "Pasien <strong>" . htmlspecialchars($nama) . "</strong> berhasil didaftarkan dengan NIK <strong>$nik</strong>.";
            $alert_type = 'success';
            $old        = []; // kosongkan form setelah sukses
        } else {
            $alert      = 'Gagal menyimpan data: ' . mysqli_error($conn);
            $alert_type = 'danger';
        }
    } else {
        $alert      = implode('<br>', $errors);
        $alert_type = 'danger';
    }
}

// Helper nilai lama
function old_val($key, $old) { return htmlspecialchars($old[$key] ?? ''); }
function old_sel($key, $val, $old) { return (($old[$key] ?? '') === $val) ? 'selected' : ''; }
?>

<style>
/* ── Layout ── */
.reg-wrapper { max-width: 820px; }

/* ── Alert ── */
.reg-alert {
    border-radius: 12px;
    padding: 14px 18px;
    font-size: .875rem;
    border: none;
    display: flex;
    align-items: flex-start;
    gap: 12px;
    margin-bottom: 24px;
}
.reg-alert i { font-size: 1.1rem; flex-shrink: 0; margin-top: 1px; }
.reg-alert.success { background: #d1fae5; color: #065f46; }
.reg-alert.danger  { background: #fee2e2; color: #991b1b; }
.reg-alert.warning { background: #fef3c7; color: #92400e; }

/* ── Card ── */
.reg-card {
    background: white;
    border-radius: 18px;
    box-shadow: 0 2px 20px rgba(0,0,0,.07);
    overflow: hidden;
}

.reg-card-header {
    background: linear-gradient(135deg, #006666, #008080 60%, #20b2aa);
    padding: 24px 30px;
    color: white;
}

.reg-card-header h5 {
    font-size: 1.05rem;
    font-weight: 700;
    margin: 0 0 4px;
}

.reg-card-header p {
    font-size: .8rem;
    opacity: .75;
    margin: 0;
}

.reg-card-body { padding: 28px 30px; }

/* ── Section label ── */
.field-section {
    font-size: .7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1.4px;
    color: #9ca3af;
    margin: 24px 0 14px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.field-section::after {
    content: '';
    flex: 1;
    height: 1px;
    background: #f3f4f6;
}
.field-section:first-child { margin-top: 0; }

/* ── Form elements ── */
.form-label {
    font-size: .78rem;
    font-weight: 600;
    color: #374151;
    margin-bottom: 5px;
}

.form-control, .form-select {
    border-radius: 10px;
    border: 1.5px solid #e5e7eb;
    font-size: .875rem;
    padding: 10px 14px;
    color: #1f2937;
    transition: border-color .2s, box-shadow .2s;
}

.form-control:focus, .form-select:focus {
    border-color: #008080;
    box-shadow: 0 0 0 3px rgba(0,128,128,.12);
    outline: none;
}

.form-control.is-invalid, .form-select.is-invalid {
    border-color: #ef4444;
}

/* NIK field */
.nik-wrap { position: relative; }
.nik-wrap .form-control { padding-right: 50px; font-family: monospace; letter-spacing: 1px; }
.nik-counter {
    position: absolute;
    right: 14px;
    top: 50%;
    transform: translateY(-50%);
    font-size: .72rem;
    color: #9ca3af;
    font-weight: 600;
    pointer-events: none;
}
.nik-counter.full { color: #008080; }

/* ── Radio JK ── */
.jk-group { display: flex; gap: 10px; }
.jk-btn {
    flex: 1;
    border: 1.5px solid #e5e7eb;
    border-radius: 10px;
    padding: 10px;
    text-align: center;
    cursor: pointer;
    font-size: .83rem;
    font-weight: 500;
    color: #6b7280;
    transition: all .2s;
    user-select: none;
}
.jk-btn:has(input:checked) {
    border-color: #008080;
    background: rgba(0,128,128,.07);
    color: #008080;
}
.jk-btn input { display: none; }
.jk-btn i { display: block; font-size: 1.3rem; margin-bottom: 4px; }

/* ── Submit ── */
.btn-reg {
    background: linear-gradient(135deg, #006666, #008080);
    color: white;
    border: none;
    border-radius: 12px;
    padding: 13px 28px;
    font-size: .9rem;
    font-weight: 600;
    width: 100%;
    cursor: pointer;
    transition: all .25s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 9px;
    margin-top: 8px;
}
.btn-reg:hover {
    background: linear-gradient(135deg, #004d4d, #006666);
    transform: translateY(-1px);
    box-shadow: 0 6px 20px rgba(0,128,128,.3);
}

/* ── Info box ── */
.info-box {
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    border-radius: 10px;
    padding: 12px 16px;
    font-size: .79rem;
    color: #1d4ed8;
    display: flex;
    gap: 10px;
    align-items: flex-start;
    margin-top: 16px;
}
.info-box i { flex-shrink: 0; margin-top: 1px; }
</style>

<div class="reg-wrapper">

    <?php if ($alert): ?>
    <div class="reg-alert <?= $alert_type ?>">
        <i class="fa-solid <?= $alert_type === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation' ?>"></i>
        <div><?= $alert ?></div>
    </div>
    <?php endif; ?>

    <div class="reg-card">
        <div class="reg-card-header">
            <h5><i class="fa-solid fa-user-plus me-2"></i>Formulir Pendaftaran Pasien Baru</h5>
            <p>Isi data sesuai KTP / identitas resmi. Semua field bertanda * wajib diisi.</p>
        </div>

        <div class="reg-card-body">
            <form method="POST" action="" id="formReg" novalidate>

                <!-- ── IDENTITAS DASAR ── -->
                <div class="field-section"><i class="fa-solid fa-id-card" style="color:#008080;"></i> Identitas Dasar</div>

                <div class="row g-3">
                    <!-- NIK -->
                    <div class="col-12">
                        <label class="form-label">NIK (Nomor Induk Kependudukan) *</label>
                        <div class="nik-wrap">
                            <input type="text" class="form-control" name="nik" id="nikInput"
                                   placeholder="Masukkan 16 digit NIK"
                                   maxlength="16" inputmode="numeric"
                                   value="<?= old_val('nik', $old) ?>" required>
                            <span class="nik-counter" id="nikCounter">0/16</span>
                        </div>
                    </div>

                    <!-- Nama -->
                    <div class="col-md-8">
                        <label class="form-label">Nama Lengkap *</label>
                        <input type="text" class="form-control" name="nama"
                               placeholder="Sesuai KTP, huruf kapital"
                               value="<?= old_val('nama', $old) ?>" required>
                    </div>

                    <!-- Tanggal Lahir -->
                    <div class="col-md-4">
                        <label class="form-label">Tanggal Lahir *</label>
                        <input type="date" class="form-control" name="tanggal_lahir"
                               value="<?= old_val('tgl_lahir', $old) ?>" required>
                    </div>

                    <!-- Jenis Kelamin -->
<div class="col-12">
    <label class="form-label">Jenis Kelamin *</label>
    <div class="jk-group">
        <label class="jk-btn">
            <input type="radio" name="jenis_kelamin" value="L"
                   <?= old_sel('jk','L',$old) ?>>
            <i class="fa-solid fa-mars"></i>Laki-laki
        </label>
        <label class="jk-btn">
            <input type="radio" name="jenis_kelamin" value="P"
                   <?= old_sel('jk','P',$old) ?>>
            <i class="fa-solid fa-venus"></i>Perempuan
        </label>
    </div>
</div>
                    <!-- Golongan Darah -->
                    <div class="col-md-4">
                        <label class="form-label">Golongan Darah</label>
                        <select class="form-select" name="gol_darah">
                            <option value="">— Tidak Diketahui —</option>
                            <?php foreach (['A','B','AB','O'] as $g): ?>
                            <option value="<?= $g ?>" <?= old_sel('gol_darah',$g,$old) ?>><?= $g ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- ── ALAMAT ── -->
                <div class="field-section"><i class="fa-solid fa-location-dot" style="color:#008080;"></i> Alamat Domisili</div>

                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">Alamat (Jalan / Dusun / Desa) *</label>
                        <input type="text" class="form-control" name="alamat"
                               placeholder="Cth: Dusun Sidamakmur, Kel. Kertanegara"
                               value="<?= old_val('alamat', $old) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">RT / RW</label>
                        <input type="text" class="form-control" name="rt_rw"
                               placeholder="Cth: 001/005"
                               value="<?= old_val('rt_rw', $old) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Kecamatan *</label>
                        <input type="text" class="form-control" name="kecamatan"
                               placeholder="Cth: Kertanegara"
                               value="<?= old_val('kecamatan', $old) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Kabupaten / Kota *</label>
                        <input type="text" class="form-control" name="kabupaten"
                               placeholder="Cth: Purbalingga"
                               value="<?= old_val('kabupaten', $old) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Provinsi *</label>
                        <input type="text" class="form-control" name="provinsi"
                               placeholder="Cth: Jawa Tengah"
                               value="<?= old_val('provinsi', $old) ?>" required>
                    </div>
                </div>

                <!-- ── SUBMIT ── -->
                <div class="row mt-4">
                    <div class="col-12">
                        <button type="submit" name="daftar_pasien" class="btn-reg">
                            <i class="fa-solid fa-floppy-disk"></i> Simpan Data Pasien
                        </button>

                        <div class="info-box">
                            <i class="fa-solid fa-circle-info"></i>
                            <div>
                                Data yang disimpan dapat ditarik oleh <strong>Node Kepolisian</strong> (untuk keperluan SKCK)
                                dan <strong>Node Bantuan Sosial</strong> melalui API secara otomatis.
                                Pastikan data sesuai KTP agar tidak terjadi konflik antar-sistem.
                            </div>
                        </div>
                    </div>
                </div>

            </form>
        </div>
    </div>
</div>

<script>
// Counter NIK
const nikInput   = document.getElementById('nikInput');
const nikCounter = document.getElementById('nikCounter');

function updateCounter() {
    // Hapus non-digit
    nikInput.value = nikInput.value.replace(/\D/g, '');
    const len = nikInput.value.length;
    nikCounter.textContent = len + '/16';
    nikCounter.className   = 'nik-counter' + (len === 16 ? ' full' : '');
}

nikInput.addEventListener('input', updateCounter);
updateCounter(); // init saat load (jika ada nilai lama)

// Nama → auto uppercase
document.querySelector('input[name="nama"]').addEventListener('input', function() {
    const pos = this.selectionStart;
    this.value = this.value.toUpperCase();
    this.setSelectionRange(pos, pos);
});
</script>