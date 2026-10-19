-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 19, 2026 at 06:27 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `simskripsi`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_lokal`
--

CREATE TABLE `admin_lokal` (
  `id_admin` bigint(20) UNSIGNED NOT NULL,
  `nama_lengkap` varchar(150) NOT NULL,
  `username` varchar(80) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `status_aktif` enum('aktif','tidak_aktif') NOT NULL DEFAULT 'aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin_lokal`
--

INSERT INTO `admin_lokal` (`id_admin`, `nama_lengkap`, `username`, `password_hash`, `status_aktif`, `created_at`, `updated_at`) VALUES
(1, 'Admin SIM Skripsi', 'AdminSkripsi', '$2y$10$KWVDCKWEuqac/uyibL0FCO7vktVhVwTEHnqby.hZFaR2I7yaIdMTS', 'aktif', '2026-10-16 04:38:22', '2026-10-16 04:38:22');

-- --------------------------------------------------------

--
-- Table structure for table `akun_dosen`
--

CREATE TABLE `akun_dosen` (
  `id_akun_dosen` bigint(20) UNSIGNED NOT NULL,
  `id_dosen` bigint(20) UNSIGNED NOT NULL,
  `username` varchar(80) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `status_aktif` enum('aktif','tidak_aktif') NOT NULL DEFAULT 'aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dosen`
--

CREATE TABLE `dosen` (
  `id_dosen` bigint(20) UNSIGNED NOT NULL,
  `nidn` varchar(30) NOT NULL,
  `nip` varchar(30) DEFAULT NULL,
  `nama_dosen` varchar(150) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `no_hp` varchar(30) DEFAULT NULL,
  `status` enum('aktif','tidak_aktif') NOT NULL DEFAULT 'aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `dosen`
--

INSERT INTO `dosen` (`id_dosen`, `nidn`, `nip`, `nama_dosen`, `email`, `no_hp`, `status`, `created_at`, `updated_at`) VALUES
(2, '0210126903', '196912102010081091', 'Harry Witriyono', 'harrywitriyono@umb.ac.id', '08153902534', 'aktif', '2026-10-16 07:11:43', '2026-10-16 07:11:43');

-- --------------------------------------------------------

--
-- Table structure for table `konfigurasi_aplikasi`
--

CREATE TABLE `konfigurasi_aplikasi` (
  `id_konfigurasi` bigint(20) UNSIGNED NOT NULL,
  `nama_konfigurasi` varchar(100) NOT NULL,
  `nilai_konfigurasi` text NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `konfigurasi_aplikasi`
--

INSERT INTO `konfigurasi_aplikasi` (`id_konfigurasi`, `nama_konfigurasi`, `nilai_konfigurasi`, `updated_at`, `updated_by`) VALUES
(1, 'api_base_url', 'http://localhost/simmahasiswa/api', '2026-10-16 04:38:22', 1);

-- --------------------------------------------------------

--
-- Table structure for table `pembimbing_skripsi`
--

CREATE TABLE `pembimbing_skripsi` (
  `id_pembimbing` bigint(20) UNSIGNED NOT NULL,
  `id_skripsi` bigint(20) UNSIGNED NOT NULL,
  `id_dosen` bigint(20) UNSIGNED NOT NULL,
  `pembimbing_ke` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `tanggal_penetapan` date DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  `status` enum('aktif','diganti','selesai') NOT NULL DEFAULT 'aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `penguji_skripsi`
--

CREATE TABLE `penguji_skripsi` (
  `id_penguji` bigint(20) UNSIGNED NOT NULL,
  `id_skripsi` bigint(20) UNSIGNED NOT NULL,
  `id_dosen` bigint(20) UNSIGNED NOT NULL,
  `penguji_ke` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `jenis_ujian` enum('seminar_proposal','seminar_hasil','sidang') NOT NULL DEFAULT 'seminar_proposal',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `penilaian_seminar`
--

CREATE TABLE `penilaian_seminar` (
  `id_penilaian` bigint(20) UNSIGNED NOT NULL,
  `id_seminar` bigint(20) UNSIGNED NOT NULL,
  `id_dosen` bigint(20) UNSIGNED NOT NULL,
  `originalitas_ide` decimal(5,2) DEFAULT NULL,
  `kejelasan_masalah` decimal(5,2) DEFAULT NULL,
  `bidang_it` decimal(5,2) DEFAULT NULL,
  `kesesuaian_masalah` decimal(5,2) DEFAULT NULL,
  `rancangan_penelitian` decimal(5,2) DEFAULT NULL,
  `pengumpulan_data` decimal(5,2) DEFAULT NULL,
  `ketepatan_rujukan` decimal(5,2) DEFAULT NULL,
  `keterbaruan_rujukan` decimal(5,2) DEFAULT NULL,
  `penyajian` decimal(5,2) DEFAULT NULL,
  `penguasaan_materi` decimal(5,2) DEFAULT NULL,
  `kemampuan_menjawab` decimal(5,2) DEFAULT NULL,
  `kemampuan_berbahasa` decimal(5,2) DEFAULT NULL,
  `nilai_akhir` decimal(5,2) DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `saran_perbaikan`
--

CREATE TABLE `saran_perbaikan` (
  `id_perbaikan` bigint(20) UNSIGNED NOT NULL,
  `id_seminar` bigint(20) UNSIGNED NOT NULL,
  `id_dosen` bigint(20) UNSIGNED NOT NULL,
  `bagian` varchar(150) NOT NULL,
  `saran` text NOT NULL,
  `status` enum('belum_diperbaiki','diajukan','diverifikasi','disetujui') NOT NULL DEFAULT 'belum_diperbaiki',
  `tanggal_saran` date DEFAULT NULL,
  `tanggal_pengajuan_revisi` date DEFAULT NULL,
  `tanggal_verifikasi` date DEFAULT NULL,
  `catatan_verifikasi` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `seminar_hasil`
--

CREATE TABLE `seminar_hasil` (
  `id_seminar_hasil` bigint(20) UNSIGNED NOT NULL,
  `id_skripsi` bigint(20) UNSIGNED NOT NULL,
  `nomor_seminar` varchar(50) DEFAULT NULL,
  `tanggal_seminar` date NOT NULL,
  `waktu_mulai` time DEFAULT NULL,
  `waktu_selesai` time DEFAULT NULL,
  `tempat` varchar(200) DEFAULT NULL,
  `status_seminar` enum('diajukan','dijadwalkan','berlangsung','selesai','dibatalkan') NOT NULL DEFAULT 'diajukan',
  `hasil_seminar` enum('diterima','diterima_dengan_revisi','ditolak','belum_ditetapkan') NOT NULL DEFAULT 'belum_ditetapkan',
  `catatan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `seminar_proposal`
--

CREATE TABLE `seminar_proposal` (
  `id_seminar` bigint(20) UNSIGNED NOT NULL,
  `id_skripsi` bigint(20) UNSIGNED NOT NULL,
  `nomor_seminar` varchar(50) DEFAULT NULL,
  `tanggal_seminar` date NOT NULL,
  `waktu_mulai` time DEFAULT NULL,
  `waktu_selesai` time DEFAULT NULL,
  `tempat` varchar(200) DEFAULT NULL,
  `status_seminar` enum('diajukan','dijadwalkan','berlangsung','selesai','dibatalkan') NOT NULL DEFAULT 'diajukan',
  `hasil_seminar` enum('diterima','diterima_dengan_revisi','ditolak','belum_ditetapkan') NOT NULL DEFAULT 'belum_ditetapkan',
  `catatan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sidang_skripsi`
--

CREATE TABLE `sidang_skripsi` (
  `id_sidang` bigint(20) UNSIGNED NOT NULL,
  `id_skripsi` bigint(20) UNSIGNED NOT NULL,
  `nomor_sidang` varchar(50) DEFAULT NULL,
  `tanggal_sidang` date NOT NULL,
  `waktu_mulai` time DEFAULT NULL,
  `waktu_selesai` time DEFAULT NULL,
  `tempat` varchar(200) DEFAULT NULL,
  `status_sidang` enum('diajukan','dijadwalkan','berlangsung','selesai','dibatalkan') NOT NULL DEFAULT 'diajukan',
  `hasil_sidang` enum('lulus','lulus_dengan_revisi','tidak_lulus','belum_ditetapkan') NOT NULL DEFAULT 'belum_ditetapkan',
  `nilai_akhir` decimal(5,2) DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `skripsi`
--

CREATE TABLE `skripsi` (
  `id_skripsi` bigint(20) UNSIGNED NOT NULL,
  `npm` varchar(30) NOT NULL,
  `judul` varchar(500) NOT NULL,
  `latar_belakang` text DEFAULT NULL,
  `rumusan_masalah` text DEFAULT NULL,
  `tujuan_penelitian` text DEFAULT NULL,
  `metode_penelitian` text DEFAULT NULL,
  `objek_penelitian` text DEFAULT NULL,
  `tahun_pengajuan` year(4) NOT NULL,
  `tanggal_pengajuan` date DEFAULT NULL,
  `id_status` int(10) UNSIGNED NOT NULL,
  `keterangan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `status_instalasi`
--

CREATE TABLE `status_instalasi` (
  `id_status` tinyint(3) UNSIGNED NOT NULL,
  `selesai` tinyint(1) NOT NULL DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `status_instalasi`
--

INSERT INTO `status_instalasi` (`id_status`, `selesai`, `updated_at`) VALUES
(1, 1, '2026-10-16 04:38:22');

-- --------------------------------------------------------

--
-- Table structure for table `status_skripsi`
--

CREATE TABLE `status_skripsi` (
  `id_status` int(10) UNSIGNED NOT NULL,
  `kode_status` varchar(30) NOT NULL,
  `nama_status` varchar(100) NOT NULL,
  `keterangan` text DEFAULT NULL,
  `urutan` int(11) NOT NULL DEFAULT 0,
  `aktif` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `status_skripsi`
--

INSERT INTO `status_skripsi` (`id_status`, `kode_status`, `nama_status`, `keterangan`, `urutan`, `aktif`) VALUES
(1, 'PENGAJUAN', 'Pengajuan Judul', 'Mahasiswa mengajukan judul skripsi', 1, 1),
(2, 'VERIFIKASI', 'Verifikasi', 'Pengajuan sedang diverifikasi', 2, 1),
(3, 'DITERIMA', 'Judul Diterima', 'Judul skripsi diterima', 3, 1),
(4, 'PEMBIMBING', 'Penetapan Pembimbing', 'Penetapan dosen pembimbing', 4, 1),
(5, 'PROPOSAL', 'Penyusunan Proposal', 'Mahasiswa menyusun proposal', 5, 1),
(6, 'SEMINAR_PROPOSAL', 'Seminar Proposal', 'Tahap seminar proposal', 6, 1),
(7, 'REVISI_PROPOSAL', 'Revisi Proposal', 'Mahasiswa melakukan revisi proposal', 7, 1),
(8, 'PENELITIAN', 'Pelaksanaan Penelitian', 'Mahasiswa melaksanakan penelitian', 8, 1),
(9, 'SEMINAR_HASIL', 'Seminar Hasil', 'Tahap seminar hasil', 9, 1),
(10, 'REVISI_HASIL', 'Revisi Hasil', 'Mahasiswa melakukan revisi hasil penelitian', 10, 1),
(11, 'SIDANG', 'Sidang Skripsi', 'Tahap sidang skripsi', 11, 1),
(12, 'LULUS', 'Lulus', 'Skripsi dinyatakan lulus', 12, 1),
(13, 'SELESAI', 'Selesai', 'Seluruh proses skripsi selesai', 13, 1),
(14, 'DITOLAK', 'Ditolak', 'Pengajuan ditolak', 99, 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_lokal`
--
ALTER TABLE `admin_lokal`
  ADD PRIMARY KEY (`id_admin`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `akun_dosen`
--
ALTER TABLE `akun_dosen`
  ADD PRIMARY KEY (`id_akun_dosen`),
  ADD UNIQUE KEY `id_dosen` (`id_dosen`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `dosen`
--
ALTER TABLE `dosen`
  ADD PRIMARY KEY (`id_dosen`),
  ADD UNIQUE KEY `nidn` (`nidn`);

--
-- Indexes for table `konfigurasi_aplikasi`
--
ALTER TABLE `konfigurasi_aplikasi`
  ADD PRIMARY KEY (`id_konfigurasi`),
  ADD UNIQUE KEY `nama_konfigurasi` (`nama_konfigurasi`);

--
-- Indexes for table `pembimbing_skripsi`
--
ALTER TABLE `pembimbing_skripsi`
  ADD PRIMARY KEY (`id_pembimbing`),
  ADD UNIQUE KEY `uk_pembimbing` (`id_skripsi`,`id_dosen`,`pembimbing_ke`),
  ADD KEY `idx_pembimbing_skripsi` (`id_skripsi`),
  ADD KEY `idx_pembimbing_dosen` (`id_dosen`);

--
-- Indexes for table `penguji_skripsi`
--
ALTER TABLE `penguji_skripsi`
  ADD PRIMARY KEY (`id_penguji`),
  ADD UNIQUE KEY `uk_penguji` (`id_skripsi`,`id_dosen`,`jenis_ujian`),
  ADD KEY `idx_penguji_skripsi` (`id_skripsi`),
  ADD KEY `idx_penguji_dosen` (`id_dosen`);

--
-- Indexes for table `penilaian_seminar`
--
ALTER TABLE `penilaian_seminar`
  ADD PRIMARY KEY (`id_penilaian`),
  ADD UNIQUE KEY `uk_penilaian` (`id_seminar`,`id_dosen`),
  ADD KEY `fk_penilaian_dosen` (`id_dosen`),
  ADD KEY `idx_penilaian_seminar` (`id_seminar`);

--
-- Indexes for table `saran_perbaikan`
--
ALTER TABLE `saran_perbaikan`
  ADD PRIMARY KEY (`id_perbaikan`),
  ADD KEY `fk_perbaikan_dosen` (`id_dosen`),
  ADD KEY `idx_perbaikan_seminar` (`id_seminar`),
  ADD KEY `idx_perbaikan_status` (`status`);

--
-- Indexes for table `seminar_hasil`
--
ALTER TABLE `seminar_hasil`
  ADD PRIMARY KEY (`id_seminar_hasil`),
  ADD KEY `fk_hasil_skripsi` (`id_skripsi`);

--
-- Indexes for table `seminar_proposal`
--
ALTER TABLE `seminar_proposal`
  ADD PRIMARY KEY (`id_seminar`),
  ADD KEY `idx_seminar_skripsi` (`id_skripsi`),
  ADD KEY `idx_seminar_tanggal` (`tanggal_seminar`);

--
-- Indexes for table `sidang_skripsi`
--
ALTER TABLE `sidang_skripsi`
  ADD PRIMARY KEY (`id_sidang`),
  ADD KEY `fk_sidang_skripsi` (`id_skripsi`);

--
-- Indexes for table `skripsi`
--
ALTER TABLE `skripsi`
  ADD PRIMARY KEY (`id_skripsi`),
  ADD KEY `idx_skripsi_npm` (`npm`),
  ADD KEY `idx_skripsi_status` (`id_status`),
  ADD KEY `idx_skripsi_tahun` (`tahun_pengajuan`);

--
-- Indexes for table `status_instalasi`
--
ALTER TABLE `status_instalasi`
  ADD PRIMARY KEY (`id_status`);

--
-- Indexes for table `status_skripsi`
--
ALTER TABLE `status_skripsi`
  ADD PRIMARY KEY (`id_status`),
  ADD UNIQUE KEY `kode_status` (`kode_status`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_lokal`
--
ALTER TABLE `admin_lokal`
  MODIFY `id_admin` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `akun_dosen`
--
ALTER TABLE `akun_dosen`
  MODIFY `id_akun_dosen` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `dosen`
--
ALTER TABLE `dosen`
  MODIFY `id_dosen` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `konfigurasi_aplikasi`
--
ALTER TABLE `konfigurasi_aplikasi`
  MODIFY `id_konfigurasi` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `pembimbing_skripsi`
--
ALTER TABLE `pembimbing_skripsi`
  MODIFY `id_pembimbing` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `penguji_skripsi`
--
ALTER TABLE `penguji_skripsi`
  MODIFY `id_penguji` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `penilaian_seminar`
--
ALTER TABLE `penilaian_seminar`
  MODIFY `id_penilaian` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `saran_perbaikan`
--
ALTER TABLE `saran_perbaikan`
  MODIFY `id_perbaikan` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `seminar_hasil`
--
ALTER TABLE `seminar_hasil`
  MODIFY `id_seminar_hasil` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `seminar_proposal`
--
ALTER TABLE `seminar_proposal`
  MODIFY `id_seminar` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sidang_skripsi`
--
ALTER TABLE `sidang_skripsi`
  MODIFY `id_sidang` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `skripsi`
--
ALTER TABLE `skripsi`
  MODIFY `id_skripsi` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `status_skripsi`
--
ALTER TABLE `status_skripsi`
  MODIFY `id_status` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `akun_dosen`
--
ALTER TABLE `akun_dosen`
  ADD CONSTRAINT `fk_akun_dosen_dosen` FOREIGN KEY (`id_dosen`) REFERENCES `dosen` (`id_dosen`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `pembimbing_skripsi`
--
ALTER TABLE `pembimbing_skripsi`
  ADD CONSTRAINT `fk_pembimbing_dosen` FOREIGN KEY (`id_dosen`) REFERENCES `dosen` (`id_dosen`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pembimbing_skripsi` FOREIGN KEY (`id_skripsi`) REFERENCES `skripsi` (`id_skripsi`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `penguji_skripsi`
--
ALTER TABLE `penguji_skripsi`
  ADD CONSTRAINT `fk_penguji_dosen` FOREIGN KEY (`id_dosen`) REFERENCES `dosen` (`id_dosen`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_penguji_skripsi` FOREIGN KEY (`id_skripsi`) REFERENCES `skripsi` (`id_skripsi`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `penilaian_seminar`
--
ALTER TABLE `penilaian_seminar`
  ADD CONSTRAINT `fk_penilaian_dosen` FOREIGN KEY (`id_dosen`) REFERENCES `dosen` (`id_dosen`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_penilaian_seminar` FOREIGN KEY (`id_seminar`) REFERENCES `seminar_proposal` (`id_seminar`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `saran_perbaikan`
--
ALTER TABLE `saran_perbaikan`
  ADD CONSTRAINT `fk_perbaikan_dosen` FOREIGN KEY (`id_dosen`) REFERENCES `dosen` (`id_dosen`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_perbaikan_seminar` FOREIGN KEY (`id_seminar`) REFERENCES `seminar_proposal` (`id_seminar`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `seminar_hasil`
--
ALTER TABLE `seminar_hasil`
  ADD CONSTRAINT `fk_hasil_skripsi` FOREIGN KEY (`id_skripsi`) REFERENCES `skripsi` (`id_skripsi`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `seminar_proposal`
--
ALTER TABLE `seminar_proposal`
  ADD CONSTRAINT `fk_seminar_skripsi` FOREIGN KEY (`id_skripsi`) REFERENCES `skripsi` (`id_skripsi`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `sidang_skripsi`
--
ALTER TABLE `sidang_skripsi`
  ADD CONSTRAINT `fk_sidang_skripsi` FOREIGN KEY (`id_skripsi`) REFERENCES `skripsi` (`id_skripsi`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `skripsi`
--
ALTER TABLE `skripsi`
  ADD CONSTRAINT `fk_skripsi_status` FOREIGN KEY (`id_status`) REFERENCES `status_skripsi` (`id_status`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
