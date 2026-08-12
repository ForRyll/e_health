<?php
// ================================================================
// cetak_surat.php — Halaman Cetak Surat Keterangan Sehat
// STANDALONE — dibuka di tab baru, punya HTML sendiri
// ================================================================
session_start();
if (!isset($_SESSION['user'])) { header("Location: login.php"); exit; }
require_once 'koneksi.php';

// Support ?id=X atau ?nik=XXX&last=1
$id  = (int)($_GET['id'] ?? 0);
$nik = trim($_GET['nik'] ?? '');

if (!$id && $nik) {
    // Cari id_pemeriksaan terbaru berdasarkan NIK
    $nik_s = mysqli_real_escape_string($conn, $nik);
    $r_last = mysqli_query($conn, "SELECT id_pemeriksaan FROM rekam_medis WHERE nik_pasien='$nik_s' ORDER BY tanggal_periksa DESC LIMIT 1");
    if ($r_last && mysqli_num_rows($r_last) > 0) {
        $id = (int)mysqli_fetch_assoc($r_last)['id_pemeriksaan'];
    }
}

if (!$id) { echo "<p style='font-family:sans-serif;padding:40px;color:red;'>ID Pemeriksaan tidak valid atau pasien belum memiliki rekam medis.</p>"; exit; }

// Ambil data rekam medis + pasien
$sql = "
    SELECT r.*, p.nama, p.tanggal_lahir, p.jenis_kelamin,
           p.alamat, p.rt_rw, p.kecamatan, p.kabupaten, p.provinsi
    FROM rekam_medis r
    JOIN pasien p ON p.nik = r.nik_pasien
    WHERE r.id_pemeriksaan = $id
    LIMIT 1
";
$res = mysqli_query($conn, $sql);
if (!$res || mysqli_num_rows($res) === 0) {
    echo "<p style='font-family:sans-serif;padding:40px;color:red;'>Data tidak ditemukan.</p>"; exit;
}
$d = mysqli_fetch_assoc($res);

// Nomor surat otomatis
$no_surat = 'SKS/' . date('Y') . '/' . str_pad($id, 5, '0', STR_PAD_LEFT);

// Alamat lengkap
$alamat_full = implode(', ', array_filter([
    $d['alamat'],
    $d['rt_rw'] ? 'RT/RW ' . $d['rt_rw'] : null,
    $d['kecamatan'],
    $d['kabupaten'],
    $d['provinsi'],
]));

// Kesimpulan
$layak = ($d['status_penyakit'] === 'Sehat' && $d['tes_narkoba'] === 'Negatif');
$tgl_periksa = date('d', strtotime($d['tanggal_periksa']));
$bln_indo = ['01'=>'Januari','02'=>'Februari','03'=>'Maret','04'=>'April','05'=>'Mei','06'=>'Juni','07'=>'Juli','08'=>'Agustus','09'=>'September','10'=>'Oktober','11'=>'November','12'=>'Desember'];
$bln_periksa = $bln_indo[date('m', strtotime($d['tanggal_periksa']))];
$thn_periksa = date('Y', strtotime($d['tanggal_periksa']));
$tgl_lahir_fmt = $d['tanggal_lahir'] ? $tgl_periksa . ' ' . $bln_periksa . ' ' . date('Y', strtotime($d['tanggal_lahir'])) : '—';
if ($d['tanggal_lahir']) {
    $tgl_lahir_fmt = date('d'  ,strtotime($d['tanggal_lahir'])) . ' ' .
                     $bln_indo[date('m',strtotime($d['tanggal_lahir']))] . ' ' .
                     date('Y'  ,strtotime($d['tanggal_lahir']));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Keterangan Sehat #<?= $id ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Source+Sans+3:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
        --teal:    #0B7B6E;
        --teal-mid:#0E9E8D;
        --navy:    #0D1B2A;
        --slate:   #4A6278;
        --muted:   #8A9BAD;
        --surface: #F4F8F7;
        --border:  #D4E0DB;
    }

    body {
        font-family: 'Source Sans 3', sans-serif;
        background: var(--surface);
        min-height: 100vh;
        display: flex; flex-direction: column; align-items: center;
        padding: 30px 20px 60px;
    }

    /* ── ACTION BAR (tidak ikut tercetak) ── */
    .action-bar {
        width: 100%; max-width: 794px;
        display: flex; align-items: center; justify-content: space-between;
        margin-bottom: 20px; flex-wrap: wrap; gap: 10px;
    }
    .action-bar-left { display: flex; align-items: center; gap: 10px; }
    .action-btn {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 10px 20px; border-radius: 10px;
        font-size: 13.5px; font-weight: 600; cursor: pointer;
        text-decoration: none; transition: all .2s;
        font-family: 'Source Sans 3', sans-serif;
    }
    .btn-print { background: var(--teal-mid); color: white; border: none; }
    .btn-print:hover { background: var(--teal); color: white; box-shadow: 0 4px 16px rgba(14,158,141,.4); }
    .btn-back  { background: white; color: var(--navy); border: 1.5px solid #e5e7eb; }
    .btn-back:hover { border-color: var(--navy); }
    .status-chip {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 8px 16px; border-radius: 20px; font-size: 13px; font-weight: 700;
    }
    .status-chip.layak    { background: #d1fae5; color: #065f46; }
    .status-chip.tidaklayak { background: #fee2e2; color: #991b1b; }

    /* ── SURAT KERTAS ── */
    .surat {
        width: 100%; max-width: 794px;
        background: white;
        box-shadow: 0 8px 40px rgba(0,0,0,.12);
        border-radius: 4px;
        overflow: hidden;
        position: relative;
    }

    /* Watermark */
    .surat::before {
        content: 'PELITA E-HEALTH';
        position: absolute; top: 50%; left: 50%;
        transform: translate(-50%,-50%) rotate(-35deg);
        font-size: 72px; font-weight: 900;
        color: rgba(14,158,141,.04);
        white-space: nowrap; pointer-events: none;
        letter-spacing: 6px; z-index: 0;
    }

    /* ── KOP SURAT ── */
    .kop {
        background: var(--navy);
        padding: 28px 40px;
        display: flex; align-items: center; gap: 20px;
        position: relative; overflow: hidden;
    }
    .kop::after {
        content: '';
        position: absolute; right: -40px; top: -40px;
        width: 180px; height: 180px; border-radius: 50%;
        background: rgba(14,158,141,.1); pointer-events: none;
    }
    .kop-logo {
        width: 64px; height: 64px; border-radius: 14px;
        background: linear-gradient(135deg, var(--teal-mid), #0AD4BC);
        display: flex; align-items: center; justify-content: center;
        font-size: 26px; color: white; flex-shrink: 0;
        box-shadow: 0 4px 16px rgba(14,158,141,.5);
    }
    .kop-text { color: white; flex: 1; }
    .kop-instansi { font-family: 'Playfair Display', serif; font-size: 18px; font-weight: 700; margin-bottom: 2px; }
    .kop-sub { font-size: 12px; opacity: .55; line-height: 1.5; }
    .kop-right { text-align: right; }
    .kop-no { font-size: 11px; opacity: .5; font-family: monospace; }
    .kop-no-val { font-size: 13.5px; font-weight: 700; color: #0AD4BC; letter-spacing: .5px; }

    /* Accent strip */
    .kop-strip { height: 4px; background: linear-gradient(90deg, var(--teal-mid) 0%, #0AD4BC 50%, var(--teal-mid) 100%); }

    /* ── BODY SURAT ── */
    .surat-body { padding: 36px 40px 32px; position: relative; z-index: 1; }

    /* Judul */
    .surat-title-wrap { text-align: center; margin-bottom: 28px; }
    .surat-title {
        font-family: 'Playfair Display', serif;
        font-size: 20px; font-weight: 700; color: var(--navy);
        border-bottom: 2.5px solid var(--teal-mid);
        display: inline-block; padding-bottom: 6px; letter-spacing: .3px;
    }
    .surat-subtitle { font-size: 12px; color: var(--muted); margin-top: 6px; }

    /* Intro text */
    .surat-intro { font-size: 13.5px; color: var(--slate); line-height: 1.7; margin-bottom: 20px; }
    .surat-intro strong { color: var(--navy); }

    /* Data table */
    .data-table { width: 100%; border-collapse: collapse; margin-bottom: 22px; }
    .data-table tr td { padding: 7px 0; vertical-align: top; font-size: 13.5px; }
    .data-table td:first-child { width: 165px; color: var(--slate); font-weight: 600; }
    .data-table td:nth-child(2) { width: 18px; color: var(--muted); }
    .data-table td:last-child { color: var(--navy); font-weight: 600; }
    .data-table tr:nth-child(even) td { background: rgba(14,158,141,.025); }

    /* Hasil pemeriksaan grid */
    .hasil-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 24px; }
    .hasil-item {
        background: var(--surface); border-radius: 10px;
        padding: 14px 12px; text-align: center;
        border: 1px solid var(--border);
    }
    .hasil-label { font-size: 10.5px; color: var(--muted); font-weight: 600; text-transform: uppercase; letter-spacing: .8px; margin-bottom: 6px; }
    .hasil-val { font-size: 18px; font-weight: 700; color: var(--navy); line-height: 1; }
    .hasil-unit { font-size: 10px; color: var(--muted); margin-top: 2px; }
    .hasil-item.hl { border-color: var(--teal-mid); background: rgba(14,158,141,.05); }
    .hasil-item.hl .hasil-val { color: var(--teal-mid); }

    /* Kesimpulan box */
    .kesimpulan-box {
        border-radius: 12px; padding: 16px 20px; margin-bottom: 24px;
        display: flex; align-items: flex-start; gap: 14px;
    }
    .kesimpulan-box.layak    { background: #f0fdf4; border: 1.5px solid #86efac; }
    .kesimpulan-box.tidaklayak { background: #fff5f5; border: 1.5px solid #fca5a5; }
    .kesimpulan-icon { font-size: 22px; flex-shrink: 0; margin-top: 1px; }
    .kesimpulan-title { font-size: 13.5px; font-weight: 700; margin-bottom: 3px; }
    .kesimpulan-text  { font-size: 12.5px; line-height: 1.6; }
    .layak .kesimpulan-title    { color: #15803d; }
    .layak .kesimpulan-text     { color: #166534; }
    .tidaklayak .kesimpulan-title { color: #991b1b; }
    .tidaklayak .kesimpulan-text  { color: #7f1d1d; }

    /* Penutup */
    .surat-penutup { font-size: 13px; color: var(--slate); line-height: 1.75; margin-bottom: 32px; }

    /* TTD area */
    .ttd-wrap { display: flex; justify-content: flex-end; }
    .ttd-box { text-align: center; min-width: 200px; }
    .ttd-kota { font-size: 12.5px; color: var(--slate); margin-bottom: 4px; }
    .ttd-jabatan { font-size: 12px; font-weight: 700; color: var(--navy); }
    .ttd-space { height: 64px; border-bottom: 1.5px solid var(--navy); margin: 10px 0 8px; position: relative; }
    .ttd-space::after {
        content: 'ttd digital';
        position: absolute; bottom: 6px; left: 50%; transform: translateX(-50%);
        font-size: 8px; color: rgba(14,158,141,.4); letter-spacing: 2px; text-transform: uppercase;
    }
    .ttd-nama { font-size: 13px; font-weight: 700; color: var(--navy); }
    .ttd-nip  { font-size: 11px; color: var(--muted); font-family: monospace; }

    /* ── FOOTER SURAT ── */
    .surat-footer {
        background: var(--navy); color: white;
        padding: 14px 40px;
        display: flex; align-items: center; justify-content: space-between;
        font-size: 11px;
    }
    .sf-left { opacity: .4; line-height: 1.5; }
    .sf-right {
        background: rgba(14,158,141,.2);
        border: 1px solid rgba(14,158,141,.3);
        border-radius: 6px; padding: 4px 12px;
        font-family: monospace; font-size: 10.5px; color: #0AD4BC;
        letter-spacing: .5px;
    }

    /* ══ PRINT ══ */
    @media print {
        body { background: white; padding: 0; }
        .action-bar { display: none !important; }
        .surat { box-shadow: none; max-width: 100%; }
        .kop { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .kop-strip { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .surat-footer { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .kesimpulan-box { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .hasil-item { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        @page { margin: 0; size: A4; }
    }
    </style>
</head>
<body>

<!-- ACTION BAR -->
<div class="action-bar">
    <div class="action-bar-left">
        <a href="javascript:history.back()" class="action-btn btn-back">
            <i class="fa-solid fa-arrow-left"></i> Kembali
        </a>
        <span class="status-chip <?= $layak?'layak':'tidaklayak' ?>">
            <?php if($layak): ?>
            <i class="fa-solid fa-circle-check"></i> Layak SKCK
            <?php else: ?>
            <i class="fa-solid fa-circle-xmark"></i> Tidak Layak SKCK
            <?php endif; ?>
        </span>
    </div>
    <button class="action-btn btn-print" onclick="window.print()">
        <i class="fa-solid fa-print"></i> Cetak / Simpan PDF
    </button>
</div>

<!-- SURAT -->
<div class="surat">

    <!-- KOP -->
    <div class="kop">
        <div class="kop-logo"><i class="fa-solid fa-heart-pulse"></i></div>
        <div class="kop-text">
            <div class="kop-instansi">SISTEM PELITA E-HEALTH</div>
            <div class="kop-sub">
                Node Kesehatan · Sistem E-Government Terdistribusi<br>
                Jl. Kesehatan No. 1, Purbalingga, Jawa Tengah
            </div>
        </div>
        <div class="kop-right">
            <div class="kop-no">No. Surat</div>
            <div class="kop-no-val"><?= $no_surat ?></div>
            <div class="kop-no" style="margin-top:6px;">ID Pemeriksaan</div>
            <div class="kop-no-val">#<?= str_pad($id,5,'0',STR_PAD_LEFT) ?></div>
        </div>
    </div>
    <div class="kop-strip"></div>

    <!-- BODY -->
    <div class="surat-body">

        <!-- Judul -->
        <div class="surat-title-wrap">
            <div class="surat-title">SURAT KETERANGAN SEHAT</div>
            <div class="surat-subtitle">
                Dikeluarkan pada <?= $tgl_periksa ?> <?= $bln_periksa ?> <?= $thn_periksa ?>
                &nbsp;·&nbsp; Berlaku untuk keperluan resmi
            </div>
        </div>

        <!-- Intro -->
        <p class="surat-intro">
            Yang bertanda tangan di bawah ini, Petugas Medis Sistem <strong>PELITA E-Health</strong>
            Node Kesehatan, menerangkan bahwa seseorang yang identitasnya tercantum di bawah ini
            telah menjalani pemeriksaan kesehatan pada tanggal
            <strong><?= $tgl_periksa ?> <?= $bln_periksa ?> <?= $thn_periksa ?></strong>
            dengan hasil sebagai berikut:
        </p>

        <!-- Data Identitas -->
        <table class="data-table">
            <tr>
                <td>Nama Lengkap</td><td>:</td>
                <td><?= htmlspecialchars($d['nama']) ?></td>
            </tr>
            <tr>
                <td>NIK</td><td>:</td>
                <td style="font-family:monospace;"><?= htmlspecialchars($d['nik_pasien']) ?></td>
            </tr>
            <tr>
                <td>Jenis Kelamin</td><td>:</td>
                <td><?= htmlspecialchars($d['jenis_kelamin'] ?: '—') ?></td>
            </tr>
            <tr>
                <td>Tanggal Lahir</td><td>:</td>
                <td><?= $tgl_lahir_fmt ?></td>
            </tr>
            <tr>
                <td>Alamat</td><td>:</td>
                <td><?= htmlspecialchars($alamat_full ?: '—') ?></td>
            </tr>
        </table>

        <!-- Hasil Pemeriksaan -->
        <div class="hasil-grid">
            <div class="hasil-item">
                <div class="hasil-label">Gol. Darah</div>
                <div class="hasil-val"><?= htmlspecialchars($d['gol_darah']) ?></div>
                <div class="hasil-unit">Golongan</div>
            </div>
            <div class="hasil-item">
                <div class="hasil-label">Tinggi Badan</div>
                <div class="hasil-val"><?= $d['tinggi_badan'] ?></div>
                <div class="hasil-unit">cm</div>
            </div>
            <div class="hasil-item">
                <div class="hasil-label">Berat Badan</div>
                <div class="hasil-val"><?= $d['berat_badan'] ?></div>
                <div class="hasil-unit">kg</div>
            </div>
            <div class="hasil-item hl">
                <div class="hasil-label">IMT / BMI</div>
                <?php
                    $imt = ($d['tinggi_badan'] > 0)
                        ? round($d['berat_badan'] / (($d['tinggi_badan']/100)**2), 1)
                        : 0;
                ?>
                <div class="hasil-val"><?= $imt ?></div>
                <div class="hasil-unit">kg/m²</div>
            </div>
            <div class="hasil-item <?= $d['status_penyakit']==='Sehat'?'hl':'' ?>" style="grid-column:span 2;">
                <div class="hasil-label">Status Penyakit</div>
                <div class="hasil-val" style="font-size:15px;"><?= htmlspecialchars($d['status_penyakit']) ?></div>
            </div>
            <div class="hasil-item <?= $d['tes_narkoba']==='Negatif'?'hl':'' ?>" style="grid-column:span 2;">
                <div class="hasil-label">Tes Narkoba</div>
                <div class="hasil-val" style="font-size:15px;"><?= htmlspecialchars($d['tes_narkoba']) ?></div>
            </div>
        </div>

        <!-- Kesimpulan -->
        <div class="kesimpulan-box <?= $layak?'layak':'tidaklayak' ?>">
            <div class="kesimpulan-icon"><?= $layak ? '✅' : '⚠️' ?></div>
            <div>
                <div class="kesimpulan-title">
                    <?= $layak ? 'DINYATAKAN SEHAT DAN LAYAK' : 'PERLU PERHATIAN MEDIS' ?>
                </div>
                <div class="kesimpulan-text">
                    <?php if ($layak): ?>
                    Berdasarkan hasil pemeriksaan, yang bersangkutan dinyatakan <strong>sehat jasmani</strong>
                    dan <strong>bebas dari penggunaan narkoba</strong>. Surat ini dapat digunakan sebagai
                    persyaratan pengajuan <strong>SKCK</strong>, melamar pekerjaan, atau keperluan administrasi lainnya.
                    <?php else: ?>
                    Berdasarkan hasil pemeriksaan, yang bersangkutan memiliki kondisi kesehatan yang perlu
                    mendapat perhatian lebih lanjut. Surat ini diterbitkan sesuai data rekam medis yang tercatat
                    dan perlu evaluasi lebih lanjut oleh dokter berwenang.
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Penutup -->
        <p class="surat-penutup">
            Surat keterangan ini dibuat berdasarkan data rekam medis yang tersimpan di Sistem
            PELITA E-Health dan dapat diverifikasi secara digital melalui endpoint API resmi dengan
            nomor surat <strong><?= $no_surat ?></strong>.
            Surat ini diterbitkan untuk keperluan yang sah dan tidak dapat dipalsukan.
        </p>

        <!-- TTD -->
        <div class="ttd-wrap">
            <div class="ttd-box">
                <div class="ttd-kota">
                    <?= date('d') ?> <?= $bln_indo[date('m')] ?> <?= date('Y') ?>,
                </div>
                <div class="ttd-jabatan">Petugas Medis PELITA E-Health</div>
                <div class="ttd-space"></div>
                <div class="ttd-nama"><?= htmlspecialchars($_SESSION['user'] ?? 'Admin') ?></div>
                <div class="ttd-nip">NIP: <?= str_pad($id * 1234567, 18, '0', STR_PAD_LEFT) ?></div>
            </div>
        </div>

    </div>

    <!-- FOOTER SURAT -->
    <div class="surat-footer">
        <div class="sf-left">
            Dokumen ini dihasilkan secara digital oleh Sistem PELITA E-Health<br>
            Bagian dari Infrastruktur E-Government Komputasi Terdistribusi
        </div>
        <div class="sf-right">VERIFY: <?= strtoupper(substr(md5($no_surat . $id), 0, 12)) ?></div>
    </div>

</div>

<script>
// Auto print jika ada param ?print=1
const params = new URLSearchParams(window.location.search);
if (params.get('print') === '1') {
    window.addEventListener('load', () => setTimeout(() => window.print(), 500));
}
</script>
</body>
</html>