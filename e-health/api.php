<?php
// ================================================================
// api.php — REST API Endpoint Sistem PELITA E-Health
// Komputasi Paralel Terdistribusi — Node: Kesehatan
// ================================================================
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: X-API-KEY, Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once 'koneksi.php';

// Pastikan tabel log_api ada
mysqli_query($conn, "
    CREATE TABLE IF NOT EXISTS `log_api` (
        `id`          INT AUTO_INCREMENT PRIMARY KEY,
        `waktu`       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `node_asal`   VARCHAR(100) DEFAULT 'Unknown',
        `action`      VARCHAR(50),
        `nik_request` VARCHAR(16) DEFAULT NULL,
        `ip_address`  VARCHAR(45),
        `status`      ENUM('success','not_found','unauthorized','error') DEFAULT 'success',
        `keterangan`  TEXT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// ── Helper: kirim response JSON ──────────────────────────────────
function respond(int $code, array $data): void {
    http_response_code($code);
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// ── Helper: tulis log ────────────────────────────────────────────
function tulis_log($conn, string $action, ?string $nik, string $status, string $ket = '', string $node = 'Unknown'): void {
    $node   = mysqli_real_escape_string($conn, $node);
    $ip     = mysqli_real_escape_string($conn, $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    $action = mysqli_real_escape_string($conn, $action);
    $status = mysqli_real_escape_string($conn, $status);
    $ket    = mysqli_real_escape_string($conn, $ket);
    $nik_q  = $nik ? "'" . mysqli_real_escape_string($conn, $nik) . "'" : 'NULL';
    mysqli_query($conn, "INSERT INTO log_api (node_asal, action, nik_request, ip_address, status, keterangan)
                         VALUES ('$node', '$action', $nik_q, '$ip', '$status', '$ket')");
}

// ── Daftar API Key ── FORMAT SERAGAM: 'key' => ['nama', 'level'] ─
$api_keys = [
    'POLISI-KEY-2024-EHEALTH'  => ['Sistem Kepolisian',     'polisi'],
    'BANSOS-KEY-2024-EHEALTH'  => ['Sistem Bantuan Sosial', 'bansos'],
    'INDUK-KEY-2024-EHEALTH'   => ['Sistem Induk (E-Gov)',  'admin'],
    'DIDIK-KEY-2024-EHEALTH'   => ['Sistem Pendidikan',     'pendidikan'],
    'DEV-KEY-TESTING-12345'    => ['Node Dinas Sosial',     'admin'],
    'PELITA-KEY-2024'          => ['Node Internal',         'admin'],
    'BANSOS-KEY-2024'          => ['Node Bantuan Sosial',   'bansos'],
    'POLISI-KEY-2024'          => ['Node Kepolisian',       'polisi'],
    'DUKCAPIL-KEY-2024'        => ['Node Kependudukan',     'admin'],
    'PENDIDIKAN-KEY-2024'      => ['Node Pendidikan',       'pendidikan'],
];

$api_key   = trim($_SERVER['HTTP_X_API_KEY'] ?? ($_GET['api_key'] ?? ''));
$action    = strtolower(trim($_GET['action'] ?? ''));
$nik_input = trim($_GET['nik'] ?? '');

// ── PING — tidak perlu API key ───────────────────────────────────
if ($action === 'ping') {
    tulis_log($conn, 'ping', null, 'success', 'Ping berhasil', 'Unknown');
    respond(200, [
        'status'    => 'success',
        'code'      => 200,
        'message'   => 'PELITA E-Health API aktif dan berjalan.',
        'node'      => 'Sistem Kesehatan (E-Health)',
        'versi'     => '2.0',
        'waktu'     => date('Y-m-d H:i:s'),
        'endpoints' => ['ping','surat_sehat','status_kesehatan','data_pasien','list_pasien'],
    ]);
}

// ── Validasi API Key (semua selain ping) ─────────────────────────
if (empty($api_key) || !isset($api_keys[$api_key])) {
    tulis_log($conn, $action ?: 'unknown', null, 'unauthorized', 'API key tidak valid');
    respond(401, [
        'status'  => 'error',
        'code'    => 401,
        'message' => 'Unauthorized. Sertakan API key yang valid di header X-API-KEY atau parameter api_key.',
        'contoh'  => 'GET api.php?action=ping&api_key=DEV-KEY-TESTING-12345',
    ]);
}

[$nama_node, $level] = $api_keys[$api_key];

// ── Routing ──────────────────────────────────────────────────────
switch ($action) {

    // ── SURAT SEHAT: untuk Kepolisian (SKCK) ─────────────────────
    case 'surat_sehat':
        if (empty($nik_input)) {
            respond(400, ['status'=>'error','code'=>400,'message'=>'Parameter nik diperlukan.']);
        }
        $nik = mysqli_real_escape_string($conn, $nik_input);
        $sql = "SELECT p.nik, p.nama, p.jenis_kelamin, p.tanggal_lahir, p.alamat,
                       p.rt_rw, p.kecamatan, p.kabupaten, p.provinsi,
                       r.gol_darah, r.tinggi_badan, r.berat_badan,
                       r.status_penyakit, r.tes_narkoba, r.tanggal_periksa
                FROM pasien p
                JOIN rekam_medis r ON r.nik_pasien = p.nik
                WHERE p.nik = '$nik'
                ORDER BY r.tanggal_periksa DESC LIMIT 1";
        $result = mysqli_query($conn, $sql);
        if (!$result || mysqli_num_rows($result) === 0) {
            tulis_log($conn, 'surat_sehat', $nik_input, 'not_found', "NIK $nik_input tidak ditemukan", $nama_node);
            respond(404, ['status'=>'error','code'=>404,'message'=>"NIK $nik_input tidak ditemukan atau belum ada rekam medis."]);
        }
        $d = mysqli_fetch_assoc($result);
        $layak_skck = ($d['status_penyakit'] === 'Sehat' && $d['tes_narkoba'] === 'Negatif');
        tulis_log($conn, 'surat_sehat', $nik_input, 'success', "SK diberikan ke $nama_node", $nama_node);
        respond(200, [
            'status'      => 'success',
            'code'        => 200,
            'sumber_data' => 'Sistem PELITA E-Health',
            'pemohon'     => $nama_node,
            'waktu_tarik' => date('Y-m-d H:i:s'),
            'data_pasien' => [
                'nik'           => $d['nik'],
                'nama'          => $d['nama'],
                'jenis_kelamin' => $d['jenis_kelamin'],
                'tanggal_lahir' => $d['tanggal_lahir'],
                'alamat'        => trim(implode(', ', array_filter([
                    $d['alamat'], $d['rt_rw'] ? 'RT/RW '.$d['rt_rw'] : null,
                    $d['kecamatan'], $d['kabupaten'], $d['provinsi']
                ]))),
            ],
            'rekam_medis' => [
                'golongan_darah'  => $d['gol_darah'],
                'tinggi_badan'    => (int)$d['tinggi_badan'],
                'berat_badan'     => (int)$d['berat_badan'],
                'status_penyakit' => $d['status_penyakit'],
                'tes_narkoba'     => $d['tes_narkoba'],
                'tanggal_periksa' => $d['tanggal_periksa'],
            ],
            'layak_skck'  => $layak_skck,
            'kesimpulan'  => $layak_skck
                ? 'LAYAK — Pasien sehat dan bebas narkoba.'
                : 'TIDAK LAYAK — Pasien memiliki kondisi yang perlu ditinjau.',
        ]);

    // ── STATUS KESEHATAN: untuk Bantuan Sosial ───────────────────
    case 'status_kesehatan':
        if (empty($nik_input)) {
            respond(400, ['status'=>'error','code'=>400,'message'=>'Parameter nik diperlukan.']);
        }
        $nik = mysqli_real_escape_string($conn, $nik_input);

        // ✅ Query lengkap — ambil gol_darah, tinggi, berat juga
        $sql = "SELECT p.nik, p.nama,
                       r.gol_darah, r.tinggi_badan, r.berat_badan,
                       r.status_penyakit, r.tes_narkoba, r.tanggal_periksa
                FROM pasien p
                JOIN rekam_medis r ON r.nik_pasien = p.nik
                WHERE p.nik = '$nik'
                ORDER BY r.tanggal_periksa DESC LIMIT 1";

        $result = mysqli_query($conn, $sql);
        if (!$result || mysqli_num_rows($result) === 0) {
            tulis_log($conn, 'status_kesehatan', $nik_input, 'not_found', "NIK $nik_input tidak ditemukan", $nama_node);
            respond(404, ['status'=>'error','code'=>404,'message'=>"NIK $nik_input tidak ditemukan atau belum ada rekam medis."]);
        }
        $d = mysqli_fetch_assoc($result);
        $rekomendasi_bansos = in_array($d['status_penyakit'], ['Disabilitas', 'Penyakit Kronis']);

        tulis_log($conn, 'status_kesehatan', $nik_input, 'success', "Status kesehatan diberikan ke $nama_node", $nama_node);
        respond(200, [
            'status'             => 'success',
            'code'               => 200,
            'sumber_data'        => 'Sistem PELITA E-Health',
            'pemohon'            => $nama_node,
            'waktu_tarik'        => date('Y-m-d H:i:s'),
            'nik'                => $d['nik'],
            'nama'               => $d['nama'],
            // ✅ Field lengkap untuk analisis.blade.php temenmu
            'golongan_darah'     => $d['gol_darah'],
            'tinggi_badan'       => (int)$d['tinggi_badan'],
            'berat_badan'        => (int)$d['berat_badan'],
            'status_penyakit'    => $d['status_penyakit'],
            'status_kesehatan'   => $d['status_penyakit'], // alias
            'tes_narkoba'        => $d['tes_narkoba'],
            'tanggal_periksa'    => $d['tanggal_periksa'],
            'rekomendasi_bansos' => $rekomendasi_bansos,
            'keterangan'         => $rekomendasi_bansos
                ? 'Pasien berpotensi mendapatkan bantuan sosial bidang kesehatan.'
                : 'Pasien tidak masuk kriteria bantuan sosial kesehatan.',
        ]);

    // ── DATA PASIEN ───────────────────────────────────────────────
    case 'data_pasien':
        if (empty($nik_input)) {
            respond(400, ['status'=>'error','code'=>400,'message'=>'Parameter nik diperlukan.']);
        }
        $nik    = mysqli_real_escape_string($conn, $nik_input);
        $result = mysqli_query($conn, "SELECT * FROM pasien WHERE nik = '$nik' LIMIT 1");
        if (!$result || mysqli_num_rows($result) === 0) {
            tulis_log($conn, 'data_pasien', $nik_input, 'not_found', "NIK tidak ditemukan", $nama_node);
            respond(404, ['status'=>'error','code'=>404,'message'=>"NIK $nik_input tidak ditemukan."]);
        }
        $pasien    = mysqli_fetch_assoc($result);
        $rm_result = mysqli_query($conn, "SELECT * FROM rekam_medis WHERE nik_pasien='$nik' ORDER BY tanggal_periksa DESC");
        $rekam = [];
        while ($rm = mysqli_fetch_assoc($rm_result)) $rekam[] = $rm;
        tulis_log($conn, 'data_pasien', $nik_input, 'success', "Data pasien diberikan ke $nama_node", $nama_node);
        respond(200, [
            'status'      => 'success',
            'code'        => 200,
            'sumber_data' => 'Sistem PELITA E-Health',
            'pemohon'     => $nama_node,
            'waktu_tarik' => date('Y-m-d H:i:s'),
            'data_pasien' => $pasien,
            'rekam_medis' => $rekam,
            'total_rekam' => count($rekam),
        ]);

    // ── LIST PASIEN ───────────────────────────────────────────────
    case 'list_pasien':
        $limit  = max(1, min(200, (int)($_GET['limit']  ?? 50)));
        $offset = max(0, (int)($_GET['offset'] ?? 0));
        $result = mysqli_query($conn, "SELECT * FROM pasien ORDER BY tanggal_daftar DESC LIMIT $limit OFFSET $offset");
        $total  = (int)mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM pasien"))['c'];
        $list   = [];
        while ($row = mysqli_fetch_assoc($result)) $list[] = $row;
        tulis_log($conn, 'list_pasien', null, 'success', "List $limit pasien diberikan ke $nama_node", $nama_node);
        respond(200, [
            'status'      => 'success',
            'code'        => 200,
            'sumber_data' => 'Sistem PELITA E-Health',
            'pemohon'     => $nama_node,
            'waktu_tarik' => date('Y-m-d H:i:s'),
            'total'       => $total,
            'limit'       => $limit,
            'offset'      => $offset,
            'data'        => $list,
        ]);

    // ── Action tidak dikenal ──────────────────────────────────────
    default:
        tulis_log($conn, $action ?: 'unknown', null, 'error', 'Action tidak dikenal', $nama_node);
        respond(400, [
            'status'  => 'error',
            'code'    => 400,
            'message' => "Action '$action' tidak dikenal.",
            'endpoint_tersedia' => [
                'ping'             => 'api.php?action=ping',
                'surat_sehat'      => 'api.php?action=surat_sehat&nik=XXXX',
                'status_kesehatan' => 'api.php?action=status_kesehatan&nik=XXXX',
                'data_pasien'      => 'api.php?action=data_pasien&nik=XXXX',
                'list_pasien'      => 'api.php?action=list_pasien',
            ],
        ]);
}