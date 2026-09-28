-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 28, 2026 at 05:10 AM
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
-- Database: `sim_mahasiswa`
--

-- --------------------------------------------------------

--
-- Table structure for table `audit_log`
--

CREATE TABLE `audit_log` (
  `id_audit` bigint(20) UNSIGNED NOT NULL,
  `id_pengguna` bigint(20) UNSIGNED DEFAULT NULL,
  `tabel_nama` varchar(100) NOT NULL,
  `record_id` varchar(100) DEFAULT NULL,
  `aksi` enum('LOGIN','LOGOUT','INSERT','UPDATE','DELETE','VIEW') NOT NULL,
  `data_lama` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data_lama`)),
  `data_baru` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data_baru`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `waktu` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `audit_log`
--

INSERT INTO `audit_log` (`id_audit`, `id_pengguna`, `tabel_nama`, `record_id`, `aksi`, `data_lama`, `data_baru`, `ip_address`, `user_agent`, `waktu`) VALUES
(1, 6, 'pengguna', '6', 'LOGIN', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-28 10:01:52');

-- --------------------------------------------------------

--
-- Table structure for table `fakultas`
--

CREATE TABLE `fakultas` (
  `id_fakultas` bigint(20) UNSIGNED NOT NULL,
  `id_universitas` bigint(20) UNSIGNED NOT NULL,
  `kode_fakultas` varchar(20) NOT NULL,
  `nama_fakultas` varchar(200) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `fakultas`
--

INSERT INTO `fakultas` (`id_fakultas`, `id_universitas`, `kode_fakultas`, `nama_fakultas`, `created_at`, `updated_at`) VALUES
(1, 1, 'FT', 'Fakultas Teknik', '2026-09-28 02:53:26', '2026-09-28 02:53:26');

-- --------------------------------------------------------

--
-- Table structure for table `mahasiswa`
--

CREATE TABLE `mahasiswa` (
  `id_mahasiswa` bigint(20) UNSIGNED NOT NULL,
  `id_pengguna` bigint(20) UNSIGNED DEFAULT NULL,
  `id_program_studi` bigint(20) UNSIGNED NOT NULL,
  `npm` varchar(30) NOT NULL,
  `nama_mahasiswa` varchar(200) NOT NULL,
  `jenis_kelamin` enum('L','P') NOT NULL,
  `tempat_lahir` varchar(100) DEFAULT NULL,
  `tanggal_lahir` date DEFAULT NULL,
  `tanggal_masuk` date NOT NULL,
  `alamat` text DEFAULT NULL,
  `status_mahasiswa` enum('Aktif','Cuti','Lulus','Mengundurkan Diri','Drop Out','Tidak Aktif') NOT NULL DEFAULT 'Aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `mahasiswa`
--

INSERT INTO `mahasiswa` (`id_mahasiswa`, `id_pengguna`, `id_program_studi`, `npm`, `nama_mahasiswa`, `jenis_kelamin`, `tempat_lahir`, `tanggal_lahir`, `tanggal_masuk`, `alamat`, `status_mahasiswa`, `created_at`, `updated_at`) VALUES
(1, 5, 1, '20260001', 'Contoh Mahasiswa', 'L', 'Bengkulu', '2005-01-15', '2026-09-01', 'Bengkulu', 'Aktif', '2026-09-28 02:53:27', '2026-09-28 02:53:27');

-- --------------------------------------------------------

--
-- Table structure for table `pengguna`
--

CREATE TABLE `pengguna` (
  `id_pengguna` bigint(20) UNSIGNED NOT NULL,
  `id_role` smallint(5) UNSIGNED NOT NULL,
  `id_universitas` bigint(20) UNSIGNED DEFAULT NULL,
  `id_fakultas` bigint(20) UNSIGNED DEFAULT NULL,
  `id_program_studi` bigint(20) UNSIGNED DEFAULT NULL,
  `username` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `nama_lengkap` varchar(200) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `no_hp` varchar(30) DEFAULT NULL,
  `status_aktif` enum('Aktif','Tidak Aktif') NOT NULL DEFAULT 'Aktif',
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pengguna`
--

INSERT INTO `pengguna` (`id_pengguna`, `id_role`, `id_universitas`, `id_fakultas`, `id_program_studi`, `username`, `password_hash`, `nama_lengkap`, `email`, `no_hp`, `status_aktif`, `last_login`, `created_at`, `updated_at`) VALUES
(2, 2, 1, 1, 1, 'operator.ti', '$2y$10$REPLACE_WITH_PASSWORD_HASH', 'Operator Program Studi Teknik Informatika', 'operator.ti@umb.ac.id', NULL, 'Aktif', NULL, '2026-09-28 02:53:26', '2026-09-28 02:53:26'),
(3, 3, 1, 1, NULL, 'dekanat.ft', '$2y$10$REPLACE_WITH_PASSWORD_HASH', 'Operator Dekanat Fakultas Teknik', 'dekanat.ft@umb.ac.id', NULL, 'Aktif', NULL, '2026-09-28 02:53:26', '2026-09-28 02:53:26'),
(4, 4, 1, NULL, NULL, 'rektorat', '$2y$10$REPLACE_WITH_PASSWORD_HASH', 'Operator Rektorat', 'rektorat@umb.ac.id', NULL, 'Aktif', NULL, '2026-09-28 02:53:27', '2026-09-28 02:53:27'),
(5, 5, 1, 1, 1, '20260001', '$2y$10$REPLACE_WITH_PASSWORD_HASH', 'Contoh Mahasiswa', '20260001@student.umb.ac.id', NULL, 'Aktif', NULL, '2026-09-28 02:53:27', '2026-09-28 02:53:27'),
(6, 1, 1, NULL, NULL, 'Admin', '$2y$10$HDxCEJy3pjBzPhKOPCyO2ucvD/GObtbU2rx5nf9HgrZ5IleU3xwte', 'Admin SIM Mhs UMB', 'harrywitriyono@umb.ac.id', NULL, 'Aktif', '2026-09-28 10:01:52', '2026-09-28 03:01:42', '2026-09-28 03:01:52');

-- --------------------------------------------------------

--
-- Table structure for table `program_studi`
--

CREATE TABLE `program_studi` (
  `id_program_studi` bigint(20) UNSIGNED NOT NULL,
  `id_fakultas` bigint(20) UNSIGNED NOT NULL,
  `kode_program_studi` varchar(20) NOT NULL,
  `nama_program_studi` varchar(200) NOT NULL,
  `jenjang` varchar(20) NOT NULL DEFAULT 'S1',
  `status_aktif` enum('Aktif','Tidak Aktif') NOT NULL DEFAULT 'Aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `program_studi`
--

INSERT INTO `program_studi` (`id_program_studi`, `id_fakultas`, `kode_program_studi`, `nama_program_studi`, `jenjang`, `status_aktif`, `created_at`, `updated_at`) VALUES
(1, 1, 'TI', 'Teknik Informatika', 'S1', 'Aktif', '2026-09-28 02:53:26', '2026-09-28 02:53:26');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id_role` smallint(5) UNSIGNED NOT NULL,
  `kode_role` varchar(30) NOT NULL,
  `nama_role` varchar(100) NOT NULL,
  `keterangan` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id_role`, `kode_role`, `nama_role`, `keterangan`, `created_at`) VALUES
(1, 'ADMIN', 'Admin', 'Mengelola seluruh data dan konfigurasi aplikasi', '2026-09-28 02:53:26'),
(2, 'OPERATOR_PRODI', 'Operator Program Studi', 'Mengelola mahasiswa pada Program Studi yang menjadi kewenangannya', '2026-09-28 02:53:26'),
(3, 'DEKANAT', 'Dekanat', 'Melihat data mahasiswa pada Fakultas yang menjadi kewenangannya', '2026-09-28 02:53:26'),
(4, 'REKTORAT', 'Rektorat', 'Melihat data mahasiswa pada tingkat universitas', '2026-09-28 02:53:26'),
(5, 'MAHASISWA', 'Mahasiswa', 'Melihat data pribadi/rekord mahasiswa sendiri', '2026-09-28 02:53:26'),
(6, '55201', 'Prodi Teknik Informatika', NULL, '2026-09-28 03:07:56');

-- --------------------------------------------------------

--
-- Table structure for table `universitas`
--

CREATE TABLE `universitas` (
  `id_universitas` bigint(20) UNSIGNED NOT NULL,
  `kode_universitas` varchar(20) NOT NULL,
  `nama_universitas` varchar(200) NOT NULL,
  `slogan` varchar(255) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `kota` varchar(100) DEFAULT NULL,
  `provinsi` varchar(100) DEFAULT NULL,
  `kode_pos` varchar(10) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `telepon` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `universitas`
--

INSERT INTO `universitas` (`id_universitas`, `kode_universitas`, `nama_universitas`, `slogan`, `alamat`, `kota`, `provinsi`, `kode_pos`, `website`, `email`, `telepon`, `created_at`, `updated_at`) VALUES
(1, 'UMB', 'Universitas Muhammadiyah Bengkulu', NULL, 'Jl. Bali, Kampung Bali', 'Bengkulu', 'Bengkulu', NULL, NULL, NULL, NULL, '2026-09-28 02:53:26', '2026-09-28 02:53:26');

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_mahasiswa_per_prodi`
-- (See below for the actual view)
--
CREATE TABLE `v_mahasiswa_per_prodi` (
`id_universitas` bigint(20) unsigned
,`kode_universitas` varchar(20)
,`nama_universitas` varchar(200)
,`id_fakultas` bigint(20) unsigned
,`kode_fakultas` varchar(20)
,`nama_fakultas` varchar(200)
,`id_program_studi` bigint(20) unsigned
,`kode_program_studi` varchar(20)
,`nama_program_studi` varchar(200)
,`jenjang` varchar(20)
,`id_mahasiswa` bigint(20) unsigned
,`npm` varchar(30)
,`nama_mahasiswa` varchar(200)
,`jenis_kelamin` enum('L','P')
,`jenis_kelamin_text` varchar(9)
,`tempat_lahir` varchar(100)
,`tanggal_lahir` date
,`tanggal_masuk` date
,`alamat` text
,`status_mahasiswa` enum('Aktif','Cuti','Lulus','Mengundurkan Diri','Drop Out','Tidak Aktif')
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_profil_mahasiswa`
-- (See below for the actual view)
--
CREATE TABLE `v_profil_mahasiswa` (
`id_mahasiswa` bigint(20) unsigned
,`npm` varchar(30)
,`nama_mahasiswa` varchar(200)
,`jenis_kelamin` enum('L','P')
,`tempat_lahir` varchar(100)
,`tanggal_lahir` date
,`tanggal_masuk` date
,`alamat` text
,`status_mahasiswa` enum('Aktif','Cuti','Lulus','Mengundurkan Diri','Drop Out','Tidak Aktif')
,`kode_program_studi` varchar(20)
,`nama_program_studi` varchar(200)
,`jenjang` varchar(20)
,`kode_fakultas` varchar(20)
,`nama_fakultas` varchar(200)
,`kode_universitas` varchar(20)
,`nama_universitas` varchar(200)
);

-- --------------------------------------------------------

--
-- Structure for view `v_mahasiswa_per_prodi`
--
DROP TABLE IF EXISTS `v_mahasiswa_per_prodi`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_mahasiswa_per_prodi`  AS SELECT `u`.`id_universitas` AS `id_universitas`, `u`.`kode_universitas` AS `kode_universitas`, `u`.`nama_universitas` AS `nama_universitas`, `f`.`id_fakultas` AS `id_fakultas`, `f`.`kode_fakultas` AS `kode_fakultas`, `f`.`nama_fakultas` AS `nama_fakultas`, `ps`.`id_program_studi` AS `id_program_studi`, `ps`.`kode_program_studi` AS `kode_program_studi`, `ps`.`nama_program_studi` AS `nama_program_studi`, `ps`.`jenjang` AS `jenjang`, `m`.`id_mahasiswa` AS `id_mahasiswa`, `m`.`npm` AS `npm`, `m`.`nama_mahasiswa` AS `nama_mahasiswa`, `m`.`jenis_kelamin` AS `jenis_kelamin`, CASE WHEN `m`.`jenis_kelamin` = 'L' THEN 'Laki-laki' WHEN `m`.`jenis_kelamin` = 'P' THEN 'Perempuan' END AS `jenis_kelamin_text`, `m`.`tempat_lahir` AS `tempat_lahir`, `m`.`tanggal_lahir` AS `tanggal_lahir`, `m`.`tanggal_masuk` AS `tanggal_masuk`, `m`.`alamat` AS `alamat`, `m`.`status_mahasiswa` AS `status_mahasiswa` FROM (((`mahasiswa` `m` join `program_studi` `ps` on(`m`.`id_program_studi` = `ps`.`id_program_studi`)) join `fakultas` `f` on(`ps`.`id_fakultas` = `f`.`id_fakultas`)) join `universitas` `u` on(`f`.`id_universitas` = `u`.`id_universitas`)) ;

-- --------------------------------------------------------

--
-- Structure for view `v_profil_mahasiswa`
--
DROP TABLE IF EXISTS `v_profil_mahasiswa`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_profil_mahasiswa`  AS SELECT `m`.`id_mahasiswa` AS `id_mahasiswa`, `m`.`npm` AS `npm`, `m`.`nama_mahasiswa` AS `nama_mahasiswa`, `m`.`jenis_kelamin` AS `jenis_kelamin`, `m`.`tempat_lahir` AS `tempat_lahir`, `m`.`tanggal_lahir` AS `tanggal_lahir`, `m`.`tanggal_masuk` AS `tanggal_masuk`, `m`.`alamat` AS `alamat`, `m`.`status_mahasiswa` AS `status_mahasiswa`, `ps`.`kode_program_studi` AS `kode_program_studi`, `ps`.`nama_program_studi` AS `nama_program_studi`, `ps`.`jenjang` AS `jenjang`, `f`.`kode_fakultas` AS `kode_fakultas`, `f`.`nama_fakultas` AS `nama_fakultas`, `u`.`kode_universitas` AS `kode_universitas`, `u`.`nama_universitas` AS `nama_universitas` FROM (((`mahasiswa` `m` join `program_studi` `ps` on(`m`.`id_program_studi` = `ps`.`id_program_studi`)) join `fakultas` `f` on(`ps`.`id_fakultas` = `f`.`id_fakultas`)) join `universitas` `u` on(`f`.`id_universitas` = `u`.`id_universitas`)) ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`id_audit`),
  ADD KEY `idx_audit_pengguna` (`id_pengguna`),
  ADD KEY `idx_audit_tabel_record` (`tabel_nama`,`record_id`),
  ADD KEY `idx_audit_waktu` (`waktu`);

--
-- Indexes for table `fakultas`
--
ALTER TABLE `fakultas`
  ADD PRIMARY KEY (`id_fakultas`),
  ADD UNIQUE KEY `uk_fakultas_univ_kode` (`id_universitas`,`kode_fakultas`);

--
-- Indexes for table `mahasiswa`
--
ALTER TABLE `mahasiswa`
  ADD PRIMARY KEY (`id_mahasiswa`),
  ADD UNIQUE KEY `uk_mahasiswa_npm` (`npm`),
  ADD UNIQUE KEY `uk_mahasiswa_pengguna` (`id_pengguna`),
  ADD KEY `idx_mahasiswa_prodi` (`id_program_studi`),
  ADD KEY `idx_mahasiswa_nama` (`nama_mahasiswa`),
  ADD KEY `idx_mahasiswa_status` (`status_mahasiswa`);

--
-- Indexes for table `pengguna`
--
ALTER TABLE `pengguna`
  ADD PRIMARY KEY (`id_pengguna`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `idx_pengguna_role` (`id_role`),
  ADD KEY `idx_pengguna_universitas` (`id_universitas`),
  ADD KEY `idx_pengguna_fakultas` (`id_fakultas`),
  ADD KEY `idx_pengguna_prodi` (`id_program_studi`);

--
-- Indexes for table `program_studi`
--
ALTER TABLE `program_studi`
  ADD PRIMARY KEY (`id_program_studi`),
  ADD UNIQUE KEY `uk_prodi_fakultas_kode` (`id_fakultas`,`kode_program_studi`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id_role`),
  ADD UNIQUE KEY `kode_role` (`kode_role`);

--
-- Indexes for table `universitas`
--
ALTER TABLE `universitas`
  ADD PRIMARY KEY (`id_universitas`),
  ADD UNIQUE KEY `kode_universitas` (`kode_universitas`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `id_audit` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `fakultas`
--
ALTER TABLE `fakultas`
  MODIFY `id_fakultas` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `mahasiswa`
--
ALTER TABLE `mahasiswa`
  MODIFY `id_mahasiswa` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `pengguna`
--
ALTER TABLE `pengguna`
  MODIFY `id_pengguna` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `program_studi`
--
ALTER TABLE `program_studi`
  MODIFY `id_program_studi` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id_role` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `universitas`
--
ALTER TABLE `universitas`
  MODIFY `id_universitas` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD CONSTRAINT `fk_audit_pengguna` FOREIGN KEY (`id_pengguna`) REFERENCES `pengguna` (`id_pengguna`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `fakultas`
--
ALTER TABLE `fakultas`
  ADD CONSTRAINT `fk_fakultas_universitas` FOREIGN KEY (`id_universitas`) REFERENCES `universitas` (`id_universitas`) ON UPDATE CASCADE;

--
-- Constraints for table `mahasiswa`
--
ALTER TABLE `mahasiswa`
  ADD CONSTRAINT `fk_mahasiswa_pengguna` FOREIGN KEY (`id_pengguna`) REFERENCES `pengguna` (`id_pengguna`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_mahasiswa_prodi` FOREIGN KEY (`id_program_studi`) REFERENCES `program_studi` (`id_program_studi`) ON UPDATE CASCADE;

--
-- Constraints for table `pengguna`
--
ALTER TABLE `pengguna`
  ADD CONSTRAINT `fk_pengguna_fakultas` FOREIGN KEY (`id_fakultas`) REFERENCES `fakultas` (`id_fakultas`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pengguna_prodi` FOREIGN KEY (`id_program_studi`) REFERENCES `program_studi` (`id_program_studi`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pengguna_role` FOREIGN KEY (`id_role`) REFERENCES `roles` (`id_role`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pengguna_universitas` FOREIGN KEY (`id_universitas`) REFERENCES `universitas` (`id_universitas`) ON UPDATE CASCADE;

--
-- Constraints for table `program_studi`
--
ALTER TABLE `program_studi`
  ADD CONSTRAINT `fk_prodi_fakultas` FOREIGN KEY (`id_fakultas`) REFERENCES `fakultas` (`id_fakultas`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
