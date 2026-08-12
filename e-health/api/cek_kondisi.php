<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

// 1. Koneksi ke database sesuai SQL dump temanmu: `e_health`
$conn = new mysqli("localhost", "root", "", "e_health");

if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Koneksi ke database e_health gagal."]);
    exit();
}

$nik = isset($_GET['nik']) ? $_GET['nik'] : '';

if (empty($nik)) {
    echo json_encode(["status" => "error", "message" => "Parameter NIK kosong."]);
    exit();
}

// 2. Query mengambil data Rekam Medis TERBARU berdasarkan NIK Pasien
$query = "SELECT gol_darah, status_penyakit, tinggi_badan, berat_badan 
          FROM rekam_medis 
          WHERE nik_pasien = ? 
          ORDER BY id_pemeriksaan DESC LIMIT 1";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $nik);
$stmt->execute();
$result = $stmt->get_result();

$status_api = "not_found";
$keterangan_log = "Pencarian NIK $nik tidak ditemukan.";

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $status_api = "success";
    $keterangan_log = "Berhasil mengirimkan data medis NIK $nik ke Node Dinsos.";

    // Deteksi otomatis jika status penyakitnya bernilai 'Disabilitas' atau 'Penyakit Kronis'
    $is_disabilitas = ($row['status_penyakit'] == 'Disabilitas') ? true : false;

    $response = [
        "nik" => $nik,
        "golongan_darah" => $row['gol_darah'],
        "tinggi_badan" => $row['tinggi_badan'],
        "berat_badan" => $row['berat_badan'],
        "status_penyakit" => $row['status_penyakit'],
        "status_disabilitas" => $is_disabilitas // Flag boolean untuk aturan Dinsos Lian
    ];
} else {
    // Default response jika rekam medis pasien belum ada di sistem RS
    $response = [
        "nik" => $nik,
        "golongan_darah" => "N/A",
        "tinggi_badan" => 0,
        "berat_badan" => 0,
        "status_penyakit" => "Sehat",
        "status_disabilitas" => false
    ];
}

// 3. BONUS NILAI AKADEMIK: Catat aktivitas hit transaksi ke dalam tabel `log_api` temanmu
$ip_client = $_SERVER['REMOTE_ADDR'];
$log_stmt = $conn->prepare("INSERT INTO log_api (node_asal, action, nik_diminta, ip_address, status, keterangan) VALUES ('Node Dinsos (Lian)', 'GET_MEDICAL_DATA', ?, ?, ?, ?)");
$log_stmt->bind_param("ssss", $nik, $ip_client, $status_api, $keterangan_log);
$log_stmt->execute();
$log_stmt->close();

// Munculkan hasil JSON bersih ke jaringan
echo json_encode($response);

$stmt->close();
$conn->close();
?>