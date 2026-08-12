-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 12 Agu 2026 pada 15.41
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `e_health`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `laporan_kematian`
--

CREATE TABLE `laporan_kematian` (
  `id` int(11) NOT NULL,
  `nik_pasien` varchar(16) NOT NULL,
  `nama_pasien` varchar(100) NOT NULL,
  `tanggal_lapor` date NOT NULL,
  `jam_lapor` time NOT NULL,
  `penyebab` varchar(255) DEFAULT NULL,
  `pelapor` varchar(100) DEFAULT NULL,
  `status_kirim` enum('Pending','Terkirim') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `laporan_kematian`
--

INSERT INTO `laporan_kematian` (`id`, `nik_pasien`, `nama_pasien`, `tanggal_lapor`, `jam_lapor`, `penyebab`, `pelapor`, `status_kirim`, `created_at`) VALUES
(1, '3303101000332322', 'Kimul Boy', '2026-05-31', '00:21:00', 'Lose Streak dan Depresi', 'admin', 'Terkirim', '2026-05-31 17:22:10');

-- --------------------------------------------------------

--
-- Struktur dari tabel `log_api`
--

CREATE TABLE `log_api` (
  `id` int(11) NOT NULL,
  `waktu` timestamp NOT NULL DEFAULT current_timestamp(),
  `node_asal` varchar(100) DEFAULT 'Unknown',
  `action` varchar(50) DEFAULT NULL,
  `nik_request` varchar(16) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `status` enum('success','not_found','unauthorized','error') DEFAULT 'success',
  `keterangan` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `log_api`
--

INSERT INTO `log_api` (`id`, `waktu`, `node_asal`, `action`, `nik_request`, `ip_address`, `status`, `keterangan`) VALUES
(1, '2026-06-02 03:03:53', 'Node Dinsos (Lian)', 'GET_MEDICAL_DATA', '3301234567890002', '::1', 'success', 'Berhasil mengirimkan data medis NIK 3301234567890002 ke Node Dinsos.'),
(2, '2026-06-02 03:04:59', 'Node Dinsos (Lian)', 'GET_MEDICAL_DATA', '3301234567890002', '192.168.137.252', 'success', 'Berhasil mengirimkan data medis NIK 3301234567890002 ke Node Dinsos.'),
(3, '2026-06-02 05:16:13', 'Unknown', 'ping', NULL, '192.168.100.33', 'unauthorized', 'API key tidak valid atau tidak ada'),
(4, '2026-06-02 05:26:02', 'Unknown', 'status_kesehatan', '3303101000332322', '192.168.100.33', 'success', 'Status kesehatan diberikan ke '),
(5, '2026-06-02 05:26:06', 'Unknown', 'status_kesehatan', '3303101000332322', '192.168.100.33', 'success', 'Status kesehatan diberikan ke '),
(6, '2026-06-02 05:26:31', 'Unknown', 'status_kesehatan', '3303101000332322', '192.168.100.33', 'success', 'Status kesehatan diberikan ke '),
(7, '2026-06-02 05:26:35', 'Unknown', 'status_kesehatan', '3301234567890002', '192.168.100.33', 'success', 'Status kesehatan diberikan ke '),
(8, '2026-06-02 05:32:30', 'Unknown', 'status_kesehatan', '3303101000332322', '192.168.100.33', 'success', 'Status kesehatan diberikan ke '),
(9, '2026-06-02 05:32:59', 'Unknown', 'status_kesehatan', '3301234567890002', '192.168.100.33', 'success', 'Status kesehatan diberikan ke '),
(10, '2026-06-02 05:35:06', 'Unknown', 'status_kesehatan', '3301234567890002', '192.168.100.33', 'success', 'Status kesehatan diberikan ke '),
(11, '2026-06-02 05:36:01', 'Unknown', 'list_pasien', NULL, '::1', 'unauthorized', ' tidak punya akses list_pasien'),
(12, '2026-06-02 05:36:16', 'Unknown', 'list_pasien', NULL, '::1', 'unauthorized', ' tidak punya akses list_pasien'),
(13, '2026-06-02 05:36:53', 'Unknown', 'status_kesehatan', '3301234567890002', '192.168.100.33', 'success', 'Status kesehatan diberikan ke '),
(14, '2026-06-02 05:38:19', 'Unknown', 'status_kesehatan', '3301234567890002', '192.168.100.33', 'success', 'Status kesehatan diberikan ke '),
(15, '2026-06-02 05:38:22', 'Unknown', 'status_kesehatan', '3303101000332322', '192.168.100.33', 'success', 'Status kesehatan diberikan ke '),
(16, '2026-06-02 05:39:49', 'Unknown', 'status_kesehatan', '3303101000332322', '192.168.100.33', 'success', 'Status kesehatan diberikan ke '),
(17, '2026-06-02 05:39:54', 'Unknown', 'status_kesehatan', '3303101000332322', '192.168.100.33', 'success', 'Status kesehatan diberikan ke '),
(18, '2026-06-02 05:40:20', 'Unknown', 'ping', NULL, '::1', 'unauthorized', 'API key tidak valid atau tidak ada'),
(19, '2026-06-02 05:40:41', 'Unknown', 'status_kesehatan', '3301234567890002', '::1', 'success', 'Status kesehatan diberikan ke '),
(20, '2026-06-02 05:43:59', 'Unknown', 'ping', NULL, '::1', 'success', 'Ping berhasil'),
(21, '2026-06-02 05:44:05', 'Developer/Testing', 'status_kesehatan', '3301234567890002', '::1', 'success', 'Status kesehatan diberikan ke Developer/Testing'),
(22, '2026-06-02 05:46:51', 'Developer/Testing', 'status_kesehatan', '3301234567890002', '192.168.100.33', 'success', 'Status kesehatan diberikan ke Developer/Testing'),
(23, '2026-06-02 05:49:07', 'Developer/Testing', 'status_kesehatan', '3301234567890002', '192.168.100.33', 'success', 'Status kesehatan diberikan ke Developer/Testing'),
(24, '2026-06-02 05:49:41', 'Developer/Testing', 'status_kesehatan', '3303101000332322', '192.168.100.33', 'success', 'Status kesehatan diberikan ke Developer/Testing'),
(25, '2026-06-02 05:49:59', 'Developer/Testing', 'status_kesehatan', '3301234567890002', '192.168.100.33', 'success', 'Status kesehatan diberikan ke Developer/Testing'),
(26, '2026-06-02 05:53:16', 'Developer/Testing', 'status_kesehatan', '2341106010401334', '192.168.100.33', 'not_found', 'NIK 2341106010401334 tidak ditemukan'),
(27, '2026-06-02 05:54:34', 'Developer/Testing', 'status_kesehatan', '2341106010401334', '192.168.100.33', 'success', 'Status kesehatan diberikan ke Developer/Testing'),
(28, '2026-06-04 01:06:15', 'Developer/Testing', 'status_kesehatan', '3301234567890002', '::1', 'success', 'Status kesehatan diberikan ke Developer/Testing'),
(29, '2026-06-20 16:32:31', 'Node Kepolisian', 'surat_sehat', '3302123456789012', '192.168.100.10', 'not_found', 'Validasi syarat SKCK'),
(30, '2026-06-20 16:32:41', 'Node Kepolisian', 'surat_sehat', '3302123456789012', '192.168.100.10', 'not_found', 'Validasi syarat SKCK'),
(31, '2026-06-20 16:34:01', 'Node Kepolisian', 'surat_sehat', '3301234567890001', '192.168.100.10', 'success', 'Validasi syarat SKCK'),
(32, '2026-06-20 16:38:17', 'Node Kepolisian', 'surat_sehat', '1234567891234567', '192.168.100.10', 'success', 'Validasi syarat SKCK'),
(33, '2026-06-20 16:38:40', 'Node Kepolisian', 'surat_sehat', '3302123456789012', '192.168.100.10', 'not_found', 'Validasi syarat SKCK'),
(34, '2026-06-20 16:38:45', 'Node Kepolisian', 'surat_sehat', '1234567891234567', '192.168.100.10', 'success', 'Validasi syarat SKCK'),
(35, '2026-06-20 16:40:29', 'Developer/Testing', 'status_kesehatan', '3303101000332327', '192.168.100.7', 'not_found', 'NIK 3303101000332327 tidak ditemukan'),
(36, '2026-06-20 16:40:45', 'Developer/Testing', 'status_kesehatan', '3303101000332322', '192.168.100.7', 'success', 'Status kesehatan diberikan ke Developer/Testing'),
(37, '2026-06-20 16:42:29', 'Developer/Testing', 'status_kesehatan', '1234567891234567', '192.168.100.7', 'success', 'Status kesehatan diberikan ke Developer/Testing'),
(38, '2026-06-20 16:52:17', 'Unknown', 'status_kesehatan', NULL, '192.168.100.7', 'unauthorized', 'API key tidak valid'),
(39, '2026-06-20 16:52:22', 'Unknown', 'status_kesehatan', NULL, '192.168.100.7', 'unauthorized', 'API key tidak valid'),
(40, '2026-06-20 16:54:13', 'Developer/Testing', 'status_kesehatan', '1234567891234567', '192.168.100.7', 'success', 'Status kesehatan diberikan ke Developer/Testing'),
(41, '2026-06-20 16:57:42', 'Node Dinas Sosial', 'status_kesehatan', '1234567891234567', '192.168.100.7', 'success', 'Status kesehatan diberikan ke Node Dinas Sosial'),
(42, '2026-06-20 16:58:22', 'Node Dinas Sosial', 'status_kesehatan', '1234567891234567', '192.168.100.7', 'success', 'Status kesehatan diberikan ke Node Dinas Sosial'),
(43, '2026-06-20 16:59:28', 'Node Dinas Sosial', 'status_kesehatan', '6666666666666666', '192.168.100.7', 'success', 'Status kesehatan diberikan ke Node Dinas Sosial'),
(44, '2026-06-20 17:03:02', 'Node Kepolisian', 'surat_sehat', '6666666666666666', '192.168.100.10', 'success', 'Validasi syarat SKCK'),
(45, '2026-06-20 17:09:58', 'Node Dinas Sosial', 'list_pasien', NULL, '::1', 'success', 'List 20 pasien diberikan ke Node Dinas Sosial'),
(46, '2026-06-20 17:10:06', 'Node Dinas Sosial', 'status_kesehatan', '3301234567890001', '::1', 'success', 'Status kesehatan diberikan ke Node Dinas Sosial'),
(47, '2026-06-20 17:15:23', 'Node Dinas Sosial', 'status_kesehatan', '1234567891234567', '192.168.100.7', 'success', 'Status kesehatan diberikan ke Node Dinas Sosial'),
(48, '2026-06-21 15:21:37', 'Node Kepolisian', 'surat_sehat', '3302123456789012', '192.168.100.10', 'not_found', 'Validasi syarat SKCK'),
(49, '2026-06-21 15:21:50', 'Node Kepolisian', 'surat_sehat', '1234567891234567', '192.168.100.10', 'success', 'Validasi syarat SKCK'),
(50, '2026-06-21 15:37:34', 'Node Pendidikan', 'surat_sehat', '1234567891234567', '192.168.100.8', 'success', 'Cek Surat Sehat via API'),
(51, '2026-06-21 16:12:35', 'Node Kepolisian', 'surat_sehat', '6666666666666666', '192.168.100.10', 'success', 'Validasi syarat SKCK'),
(52, '2026-06-21 16:13:39', 'Node Kepolisian', 'surat_sehat', '3302123456789012', '192.168.100.10', 'not_found', 'Validasi syarat SKCK'),
(53, '2026-06-21 16:14:00', 'Node Kepolisian', 'surat_sehat', '1234567891234567', '192.168.100.10', 'success', 'Validasi syarat SKCK'),
(54, '2026-06-21 16:20:07', 'Node Pendidikan', 'surat_sehat', '3303101000332322', '192.168.100.8', 'success', 'Cek Surat Sehat via API'),
(55, '2026-06-21 16:24:12', 'Node Kepolisian', 'surat_sehat', '1234567891234567', '192.168.100.10', 'success', 'Validasi syarat SKCK'),
(56, '2026-06-21 16:26:03', 'Node Kepolisian', 'surat_sehat', '1234567891234567', '192.168.100.10', 'success', 'Validasi syarat SKCK'),
(57, '2026-06-21 16:26:35', 'Node Kepolisian', 'surat_sehat', '1234567891234567', '192.168.100.10', 'success', 'Validasi syarat SKCK');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pasien`
--

CREATE TABLE `pasien` (
  `nik` varchar(16) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `jenis_kelamin` enum('Laki-laki','Perempuan') DEFAULT NULL,
  `tanggal_lahir` date DEFAULT NULL,
  `alamat` text NOT NULL,
  `rt_rw` varchar(10) DEFAULT NULL,
  `sumber_data` varchar(50) DEFAULT 'Manual',
  `kecamatan` varchar(50) DEFAULT NULL,
  `kabupaten` varchar(50) DEFAULT NULL,
  `provinsi` varchar(50) DEFAULT NULL,
  `gol_darah` enum('A','B','AB','O') DEFAULT NULL,
  `tanggal_daftar` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `pasien`
--

INSERT INTO `pasien` (`nik`, `nama`, `jenis_kelamin`, `tanggal_lahir`, `alamat`, `rt_rw`, `sumber_data`, `kecamatan`, `kabupaten`, `provinsi`, `gol_darah`, `tanggal_daftar`) VALUES
('1234567891234567', 'AFDOLI SIREGAR', 'Laki-laki', '1990-09-15', 'KTP', '001/005', 'Manual', 'KTP', 'KTP', 'Jawa Barat', 'AB', '2026-06-20 16:37:28'),
('2341106010401334', 'RAJA JOPAN', 'Laki-laki', '1945-12-05', 'Kost Tiga Putri', '021/01', 'Manual', 'Kalikabong', 'Purbalingga', 'Jawa Tengah', 'AB', '2026-06-02 05:52:39'),
('3301234567890001', 'Budi Santoso', 'Laki-laki', '2005-06-30', 'Jl. Merdeka No. 10, Jakarta', '001/009', 'Manual', 'Kalikabong', 'Purbalingga', 'Jawa Tengah', 'B', '2026-05-26 11:41:22'),
('3303101000332322', 'Kimul Boy', 'Laki-laki', '2026-04-29', 'Dusun Sidamakmur, Rt/Rw 01/05, Kel. Kertanegara, Kec. Kertanegara, Kab. Purbalingga', NULL, 'Manual', NULL, NULL, NULL, 'B', '2026-05-26 12:34:09'),
('6666666666666666', 'LESTARI', 'Perempuan', '1990-11-02', 'Kost Hijau Daun, Kamar no 3', '001/004', 'Manual', 'Kalikabong', 'Saranjana', 'Jawa Timur', 'O', '2026-06-04 01:32:38');

-- --------------------------------------------------------

--
-- Struktur dari tabel `rekam_medis`
--

CREATE TABLE `rekam_medis` (
  `id_pemeriksaan` int(11) NOT NULL,
  `nik_pasien` varchar(16) NOT NULL,
  `gol_darah` enum('A','B','AB','O') NOT NULL,
  `tinggi_badan` int(3) NOT NULL COMMENT 'dalam satuan cm',
  `berat_badan` int(3) NOT NULL COMMENT 'dalam satuan kg',
  `status_penyakit` enum('Sehat','Disabilitas','Penyakit Kronis') NOT NULL,
  `tes_narkoba` enum('Negatif','Positif') NOT NULL,
  `tanggal_periksa` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `rekam_medis`
--

INSERT INTO `rekam_medis` (`id_pemeriksaan`, `nik_pasien`, `gol_darah`, `tinggi_badan`, `berat_badan`, `status_penyakit`, `tes_narkoba`, `tanggal_periksa`) VALUES
(1, '3301234567890001', 'O', 170, 65, 'Sehat', 'Negatif', '2026-05-26 11:41:23'),
(3, '3303101000332322', 'B', 177, 55, 'Sehat', '', '2026-05-26 12:34:09'),
(4, '3303101000332322', 'B', 165, 66, 'Sehat', 'Positif', '2026-05-31 17:16:20'),
(6, '2341106010401334', 'AB', 190, 78, 'Disabilitas', 'Positif', '2026-06-02 05:54:19'),
(7, '6666666666666666', 'O', 166, 66, 'Sehat', 'Negatif', '2026-06-04 01:33:39'),
(8, '1234567891234567', 'AB', 170, 70, 'Disabilitas', 'Positif', '2026-06-20 16:37:58');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(20) DEFAULT 'admin_rs'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`) VALUES
(1, 'admin', 'password123', 'admin_rs');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `laporan_kematian`
--
ALTER TABLE `laporan_kematian`
  ADD PRIMARY KEY (`id`),
  ADD KEY `laporan_kematian_ibfk_1` (`nik_pasien`);

--
-- Indeks untuk tabel `log_api`
--
ALTER TABLE `log_api`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `pasien`
--
ALTER TABLE `pasien`
  ADD PRIMARY KEY (`nik`);

--
-- Indeks untuk tabel `rekam_medis`
--
ALTER TABLE `rekam_medis`
  ADD PRIMARY KEY (`id_pemeriksaan`),
  ADD KEY `nik_pasien` (`nik_pasien`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `laporan_kematian`
--
ALTER TABLE `laporan_kematian`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `log_api`
--
ALTER TABLE `log_api`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT untuk tabel `rekam_medis`
--
ALTER TABLE `rekam_medis`
  MODIFY `id_pemeriksaan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `laporan_kematian`
--
ALTER TABLE `laporan_kematian`
  ADD CONSTRAINT `laporan_kematian_ibfk_1` FOREIGN KEY (`nik_pasien`) REFERENCES `pasien` (`nik`) ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `rekam_medis`
--
ALTER TABLE `rekam_medis`
  ADD CONSTRAINT `rekam_medis_ibfk_1` FOREIGN KEY (`nik_pasien`) REFERENCES `pasien` (`nik`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
