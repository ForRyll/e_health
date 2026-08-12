<?php
// ======================================================
// File: api_pendidikan.php
// Endpoint khusus untuk Node Pendidikan
// ======================================================
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once 'koneksi.php';

if (isset($_GET['nik'])) {
    $nik_request = mysqli_real_escape_string($conn, $_GET['nik']);

    // Mengambil data identitas + rekam medis terakhir
    $sql = "SELECT p.nik, p.nama, rm.gol_darah, rm.tinggi_badan, rm.berat_badan, rm.status_penyakit 
            FROM pasien p 
            LEFT JOIN rekam_medis rm ON rm.nik_pasien = p.nik 
            WHERE p.nik = '$nik_request' 
            ORDER BY rm.tanggal_periksa DESC LIMIT 1";
            
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) > 0) {
        $data = mysqli_fetch_assoc($result);
        
        $response = [
            "status" => "success",
            "message" => "Data kesehatan siswa ditemukan",
            "data" => [
                "nik" => $data['nik'],
                "nama_siswa" => $data['nama'],
                "golongan_darah" => $data['gol_darah'] ?? 'Belum ada data',
                "tinggi_badan" => $data['tinggi_badan'] ?? 0,
                "berat_badan" => $data['berat_badan'] ?? 0,
                "status_kesehatan" => $data['status_penyakit'] ?? 'Belum terperiksa'
            ]
        ];
    } else {
        $response = [
            "status" => "not_found",
            "message" => "Siswa dengan NIK tersebut belum memiliki rekam medis."
        ];
    }
} else {
    $response = [
        "status" => "error",
        "message" => "Parameter NIK tidak disertakan!"
    ];
}

// Catat log aktivitas ke Dashboard Kesehatanmu
$ip_peminta = $_SERVER['REMOTE_ADDR'];
$status_log = ($response['status'] == 'success') ? 'success' : 'not_found';
$nik_log = $nik_request ?? 'NULL';

mysqli_query($conn, "INSERT INTO log_api (node_asal, action, nik_request, ip_address, status, keterangan) 
                     VALUES ('Node Pendidikan', 'data_pasien', '$nik_log', '$ip_peminta', '$status_log', 'Validasi Kesehatan Siswa')");

echo json_encode($response);
?>