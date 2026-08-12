<?php
// ======================================================
// File: api_surat_sehat.php
// Endpoint khusus membalas request cURL temanmu (Pendidikan)
// ======================================================
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once 'koneksi.php';

$nik = isset($_GET['nik']) ? trim($_GET['nik']) : '';

if (!empty($nik)) {
    $nik_aman = mysqli_real_escape_string($conn, $nik);
    
    // Cari data pasien dan rekam medis terakhirnya
    $sql = "SELECT p.nik, p.nama, rm.id_pemeriksaan, rm.gol_darah, rm.tanggal_periksa, rm.status_penyakit, rm.tes_narkoba
            FROM pasien p
            LEFT JOIN rekam_medis rm ON p.nik = rm.nik_pasien
            WHERE p.nik = '$nik_aman'
            ORDER BY rm.tanggal_periksa DESC LIMIT 1";
            
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) > 0) {
        $data = mysqli_fetch_assoc($result);

        if ($data['id_pemeriksaan']) {
            // Kita meracik datanya agar SESUAI dengan yang diminta PHP temanmu
            
            // 1. Generate Nomor Surat (Contoh: SK-2026-0001)
            $no_surat = "SK-" . date('Y', strtotime($data['tanggal_periksa'])) . "-" . str_pad($data['id_pemeriksaan'], 4, '0', STR_PAD_LEFT);
            
            // 2. Tanggal Terbit & Expired (Misal expired 30 hari setelah diperiksa)
            $tgl_terbit = $data['tanggal_periksa'];
            $tgl_expired = date('Y-m-d', strtotime($tgl_terbit . ' + 30 days'));
            
            // 3. Status Surat (VALID atau EXPIRED)
            $status_surat = (strtotime($tgl_expired) >= time()) ? 'VALID' : 'EXPIRED';
            
            // 4. Keterangan Medis
            $keterangan = "Status Fisik: " . $data['status_penyakit'] . " | Bebas Narkoba: " . $data['tes_narkoba'];

            // Bungkus ke JSON dengan struktur yang pas
            $response = [
                'status' => 'success',
                'message' => 'Surat sehat berhasil ditemukan',
                'data' => [
                    'no_surat' => $no_surat,
                    'nik' => $data['nik'],
                    'nama_lengkap' => $data['nama'],
                    'golongan_darah' => $data['gol_darah'],
                    'tgl_terbit' => $tgl_terbit,
                    'tgl_expired' => $tgl_expired,
                    'status' => $status_surat,
                    'keterangan' => $keterangan
                ]
            ];
        } else {
            $response = ['status' => 'not_found', 'message' => 'Pasien belum memiliki riwayat pemeriksaan.'];
        }
    } else {
        $response = ['status' => 'not_found', 'message' => 'NIK tidak terdaftar di database kesehatan.'];
    }
} else {
    $response = ['error' => true, 'message' => 'Parameter NIK kosong.'];
}

// Catat log aktivitas API ke Dashboard kamu
$ip = $_SERVER['REMOTE_ADDR'];
$st_log = (isset($response['status']) && $response['status'] == 'success') ? 'success' : 'not_found';
mysqli_query($conn, "INSERT INTO log_api (node_asal, action, nik_request, status, keterangan, ip_address) 
                     VALUES ('Node Pendidikan', 'surat_sehat', '$nik', '$st_log', 'Cek Surat Sehat via API', '$ip')");

echo json_encode($response);
?>