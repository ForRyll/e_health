<?php
// ======================================================
// File: api_kepolisian.php
// Endpoint khusus untuk Sistem Kepolisian (Laravel)
// ======================================================
header("Access-Control-Allow-Origin: *"); // Mengizinkan Laravel temanmu mengakses ini
header("Content-Type: application/json; charset=UTF-8");

require_once 'koneksi.php';

// Menangkap NIK yang "ditembak" oleh Laravel temanmu
if (isset($_GET['nik'])) {
    $nik_request = mysqli_real_escape_string($conn, $_GET['nik']);

    // Mengambil data identitas + rekam medis terakhir
    $sql = "SELECT p.nik, p.nama, rm.gol_darah, rm.tes_narkoba, rm.status_penyakit 
            FROM pasien p 
            LEFT JOIN rekam_medis rm ON rm.nik_pasien = p.nik 
            WHERE p.nik = '$nik_request' 
            ORDER BY rm.tanggal_periksa DESC LIMIT 1";
            
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) > 0) {
        $data = mysqli_fetch_assoc($result);
        
        // Membungkus data dalam format JSON
        $response = [
            "status" => "success",
            "message" => "Data Surat Sehat & Rekam Medis ditemukan",
            "data" => [
                "nik" => $data['nik'],
                "nama_pemohon" => $data['nama'],
                "golongan_darah" => $data['gol_darah'] ?? 'Belum ada data',
                "status_narkoba" => $data['tes_narkoba'] ?? 'Belum Tes',
                "catatan_penyakit" => $data['status_penyakit'] ?? 'Sehat'
            ]
        ];
    } else {
        $response = [
            "status" => "not_found",
            "message" => "NIK tidak ditemukan atau belum pernah periksa di Faskes."
        ];
    }
} else {
    $response = [
        "status" => "error",
        "message" => "Parameter NIK tidak disertakan dalam request!"
    ];
}

// Catat log aktivitas agar muncul di Dashboard Kesehatanmu
$ip_peminta = $_SERVER['REMOTE_ADDR'];
$status_log = ($response['status'] == 'success') ? 'success' : 'not_found';
$nik_log = $nik_request ?? 'NULL';

mysqli_query($conn, "INSERT INTO log_api (node_asal, action, nik_request, ip_address, status, keterangan) 
                     VALUES ('Node Kepolisian', 'surat_sehat', '$nik_log', '$ip_peminta', '$status_log', 'Validasi syarat SKCK')");

echo json_encode($response);
?>