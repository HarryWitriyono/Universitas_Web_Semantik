-- ============================================================
-- DUMP SQL APLIKASI DATA MAHASISWA & WEB SEMANTIK
-- Berdasarkan rancangan landing page "Data Mahasiswa Per Program Studi"
-- Target: MySQL / MariaDB
-- ============================================================

CREATE DATABASE IF NOT EXISTS universitas_semantik
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE universitas_semantik;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS audit_log;
DROP TABLE IF EXISTS mahasiswa;
DROP TABLE IF EXISTS pengguna;
DROP TABLE IF EXISTS roles;
DROP TABLE IF EXISTS program_studi;
DROP TABLE IF EXISTS fakultas;
DROP TABLE IF EXISTS universitas;

SET FOREIGN_KEY_CHECKS = 1;

-- ------------------------------------------------------------
-- 1. UNIVERSITAS
-- ------------------------------------------------------------
CREATE TABLE universitas (
    id_universitas BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kode_universitas VARCHAR(20) NOT NULL UNIQUE,
    nama_universitas VARCHAR(200) NOT NULL,
    slogan VARCHAR(255) DEFAULT NULL,
    alamat TEXT DEFAULT NULL,
    kota VARCHAR(100) DEFAULT NULL,
    provinsi VARCHAR(100) DEFAULT NULL,
    kode_pos VARCHAR(10) DEFAULT NULL,
    email VARCHAR(150) DEFAULT NULL,
    telepon VARCHAR(30) DEFAULT NULL,
    website VARCHAR(255) DEFAULT NULL,
    uri VARCHAR(255) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 2. FAKULTAS
-- ------------------------------------------------------------
CREATE TABLE fakultas (
    id_fakultas BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_universitas BIGINT UNSIGNED NOT NULL,
    kode_fakultas VARCHAR(20) NOT NULL,
    nama_fakultas VARCHAR(200) NOT NULL,
    dekan VARCHAR(150) DEFAULT NULL,
    uri VARCHAR(255) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_fakultas_universitas
        FOREIGN KEY (id_universitas)
        REFERENCES universitas(id_universitas)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    UNIQUE KEY uk_fakultas_kode (id_universitas, kode_fakultas)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 3. PROGRAM STUDI
-- ------------------------------------------------------------
CREATE TABLE program_studi (
    id_prodi BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_fakultas BIGINT UNSIGNED NOT NULL,
    kode_prodi VARCHAR(30) NOT NULL,
    nama_prodi VARCHAR(200) NOT NULL,
    jenjang ENUM('D3','D4','S1','S2','S3') NOT NULL DEFAULT 'S1',
    akreditasi VARCHAR(50) DEFAULT NULL,
    kaprodi VARCHAR(150) DEFAULT NULL,
    jumlah_mahasiswa INT UNSIGNED NOT NULL DEFAULT 0,
    uri VARCHAR(255) NOT NULL UNIQUE,
    aktif TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_prodi_fakultas
        FOREIGN KEY (id_fakultas)
        REFERENCES fakultas(id_fakultas)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    UNIQUE KEY uk_prodi_kode (id_fakultas, kode_prodi),
    INDEX idx_prodi_nama (nama_prodi),
    INDEX idx_prodi_jenjang (jenjang),
    INDEX idx_prodi_aktif (aktif)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 4. ROLE PENGGUNA
-- ------------------------------------------------------------
CREATE TABLE roles (
    id_role INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama_role VARCHAR(50) NOT NULL UNIQUE,
    keterangan VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 5. PENGGUNA / LOGIN
-- NIM/NIP pada rancangan digunakan sebagai username.
-- Password disimpan dalam bentuk hash, bukan plaintext.
-- ------------------------------------------------------------
CREATE TABLE pengguna (
    id_pengguna BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_role INT UNSIGNED NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    nama_lengkap VARCHAR(150) NOT NULL,
    email VARCHAR(150) DEFAULT NULL,
    password_hash VARCHAR(255) NOT NULL,
    id_prodi BIGINT UNSIGNED DEFAULT NULL,
    aktif TINYINT(1) NOT NULL DEFAULT 1,
    last_login DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_pengguna_role
        FOREIGN KEY (id_role)
        REFERENCES roles(id_role)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    CONSTRAINT fk_pengguna_prodi
        FOREIGN KEY (id_prodi)
        REFERENCES program_studi(id_prodi)
        ON UPDATE CASCADE ON DELETE SET NULL,

    INDEX idx_pengguna_role (id_role),
    INDEX idx_pengguna_prodi (id_prodi),
    INDEX idx_pengguna_aktif (aktif)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 6. MAHASISWA
-- ------------------------------------------------------------
CREATE TABLE mahasiswa (
    id_mahasiswa BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_prodi BIGINT UNSIGNED NOT NULL,
    nim VARCHAR(30) NOT NULL UNIQUE,
    nama_lengkap VARCHAR(150) NOT NULL,
    jenis_kelamin ENUM('L','P') DEFAULT NULL,
    tempat_lahir VARCHAR(100) DEFAULT NULL,
    tanggal_lahir DATE DEFAULT NULL,
    angkatan YEAR NOT NULL,
    semester TINYINT UNSIGNED DEFAULT NULL,
    status_mahasiswa ENUM(
        'Aktif',
        'Cuti',
        'Lulus',
        'Nonaktif',
        'Drop Out'
    ) NOT NULL DEFAULT 'Aktif',
    email VARCHAR(150) DEFAULT NULL,
    uri VARCHAR(255) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_mahasiswa_prodi
        FOREIGN KEY (id_prodi)
        REFERENCES program_studi(id_prodi)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    INDEX idx_mahasiswa_prodi (id_prodi),
    INDEX idx_mahasiswa_nama (nama_lengkap),
    INDEX idx_mahasiswa_angkatan (angkatan),
    INDEX idx_mahasiswa_status (status_mahasiswa)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 7. AUDIT LOG
-- Mendukung konsep forensic readiness.
-- ------------------------------------------------------------
CREATE TABLE audit_log (
    id_audit BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_pengguna BIGINT UNSIGNED DEFAULT NULL,
    aktivitas VARCHAR(100) NOT NULL,
    tabel_target VARCHAR(100) DEFAULT NULL,
    id_target VARCHAR(100) DEFAULT NULL,
    data_lama LONGTEXT DEFAULT NULL,
    data_baru LONGTEXT DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_audit_pengguna
        FOREIGN KEY (id_pengguna)
        REFERENCES pengguna(id_pengguna)
        ON UPDATE CASCADE ON DELETE SET NULL,

    INDEX idx_audit_pengguna (id_pengguna),
    INDEX idx_audit_aktivitas (aktivitas),
    INDEX idx_audit_target (tabel_target, id_target),
    INDEX idx_audit_waktu (created_at)
) ENGINE=InnoDB;

-- ============================================================
-- DATA AWAL
-- ============================================================

-- Universitas
INSERT INTO universitas
(kode_universitas, nama_universitas, slogan, alamat, kota, provinsi,
 kode_pos, email, telepon, website, uri)
VALUES
('UCONTOH',
 'Universitas Contoh',
 'Unggul · Islam · Berkemajuan',
 'Jl. Pendidikan No. 1',
 'Kota Bengkulu',
 'Bengkulu',
 '38119',
 'info@universitascontoh.ac.id',
 '+62 736 123456',
 'https://universitascontoh.ac.id',
 'https://universitascontoh.ac.id/resource/universitas');

-- Fakultas
INSERT INTO fakultas
(id_universitas, kode_fakultas, nama_fakultas, dekan, uri)
VALUES
(1, 'FT',  'Fakultas Teknik', 'Dr. Dekan Teknik',
 'https://universitascontoh.ac.id/resource/fakultas/FT'),
(1, 'FEB', 'Fakultas Ekonomi dan Bisnis', 'Dr. Dekan FEB',
 'https://universitascontoh.ac.id/resource/fakultas/FEB'),
(1, 'FKIP', 'Fakultas Keguruan dan Ilmu Pendidikan', 'Dr. Dekan FKIP',
 'https://universitascontoh.ac.id/resource/fakultas/FKIP'),
(1, 'FH', 'Fakultas Hukum', 'Dr. Dekan Hukum',
 'https://universitascontoh.ac.id/resource/fakultas/FH'),
(1, 'FIKES', 'Fakultas Ilmu Kesehatan', 'Dr. Dekan FIKES',
 'https://universitascontoh.ac.id/resource/fakultas/FIKES'),
(1, 'FISIP', 'Fakultas Ilmu Sosial dan Politik', 'Dr. Dekan FISIP',
 'https://universitascontoh.ac.id/resource/fakultas/FISIP'),
(1, 'FAI', 'Fakultas Agama Islam', 'Dr. Dekan FAI',
 'https://universitascontoh.ac.id/resource/fakultas/FAI'),
(1, 'FP', 'Fakultas Pertanian', 'Dr. Dekan Pertanian',
 'https://universitascontoh.ac.id/resource/fakultas/FP');

-- 28 Program Studi
INSERT INTO program_studi
(id_fakultas, kode_prodi, nama_prodi, jenjang, akreditasi, jumlah_mahasiswa, uri)
VALUES
(1,'TI','Teknik Informatika','S1','Baik Sekali',412,'https://universitascontoh.ac.id/resource/prodi/TI'),
(1,'SI','Sistem Informasi','S1','Baik Sekali',287,'https://universitascontoh.ac.id/resource/prodi/SI'),
(1,'TE','Teknik Elektro','S1','Baik',198,'https://universitascontoh.ac.id/resource/prodi/TE'),
(1,'TS','Teknik Sipil','S1','Baik',175,'https://universitascontoh.ac.id/resource/prodi/TS'),

(2,'MNJ','Manajemen','S1','Unggul',325,'https://universitascontoh.ac.id/resource/prodi/MNJ'),
(2,'AKN','Akuntansi','S1','Baik Sekali',298,'https://universitascontoh.ac.id/resource/prodi/AKN'),
(2,'ES','Ekonomi Syariah','S1','Baik Sekali',231,'https://universitascontoh.ac.id/resource/prodi/ES'),

(3,'PGSD','Pendidikan Guru Sekolah Dasar','S1','Unggul',410,'https://universitascontoh.ac.id/resource/prodi/PGSD'),
(3,'PBI','Pendidikan Bahasa Inggris','S1','Baik Sekali',260,'https://universitascontoh.ac.id/resource/prodi/PBI'),
(3,'PMAT','Pendidikan Matematika','S1','Baik',215,'https://universitascontoh.ac.id/resource/prodi/PMAT'),
(3,'PBSI','Pendidikan Bahasa dan Sastra Indonesia','S1','Baik',180,'https://universitascontoh.ac.id/resource/prodi/PBSI'),

(4,'HK','Ilmu Hukum','S1','Baik Sekali',390,'https://universitascontoh.ac.id/resource/prodi/HK'),
(4,'MH','Magister Hukum','S2','Baik',80,'https://universitascontoh.ac.id/resource/prodi/MH'),

(5,'KEP','Keperawatan','S1','Baik Sekali',350,'https://universitascontoh.ac.id/resource/prodi/KEP'),
(5,'KESMAS','Kesehatan Masyarakat','S1','Baik Sekali',310,'https://universitascontoh.ac.id/resource/prodi/KESMAS'),
(5,'GIZI','Gizi','S1','Baik',205,'https://universitascontoh.ac.id/resource/prodi/GIZI'),

(6,'ADM','Administrasi Publik','S1','Baik Sekali',275,'https://universitascontoh.ac.id/resource/prodi/ADM'),
(6,'KOM','Ilmu Komunikasi','S1','Baik',240,'https://universitascontoh.ac.id/resource/prodi/KOM'),
(6,'SOS','Sosiologi','S1','Baik',160,'https://universitascontoh.ac.id/resource/prodi/SOS'),

(7,'PAI','Pendidikan Agama Islam','S1','Unggul',320,'https://universitascontoh.ac.id/resource/prodi/PAI'),
(7,'HES','Hukum Ekonomi Syariah','S1','Baik',210,'https://universitascontoh.ac.id/resource/prodi/HES'),
(7,'MPI','Manajemen Pendidikan Islam','S1','Baik',175,'https://universitascontoh.ac.id/resource/prodi/MPI'),

(8,'AGR','Agroteknologi','S1','Baik Sekali',250,'https://universitascontoh.ac.id/resource/prodi/AGR'),
(8,'AGB','Agribisnis','S1','Baik Sekali',235,'https://universitascontoh.ac.id/resource/prodi/AGB'),
(8,'TP','Teknologi Pangan','S1','Baik',180,'https://universitascontoh.ac.id/resource/prodi/TP'),
(8,'KHT','Kehutanan','S1','Baik',145,'https://universitascontoh.ac.id/resource/prodi/KHT'),
(8,'THP','Teknologi Hasil Pertanian','S1','Baik',165,'https://universitascontoh.ac.id/resource/prodi/THP');

-- Roles
INSERT INTO roles (nama_role, keterangan) VALUES
('admin','Administrator sistem'),
('operator','Pengelola data akademik'),
('dosen','Pengguna dengan akses data sesuai kewenangan'),
('mahasiswa','Mahasiswa'),
('publik','Pengguna umum');

-- Pengguna
-- Password contoh: password
-- Ganti dengan password hash produksi.
INSERT INTO pengguna
(id_role, username, nama_lengkap, email, password_hash, id_prodi)
VALUES
(1,'admin','Administrator Sistem','admin@universitascontoh.ac.id',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llCz5VqJf4Y6m8J2xZK2',
 NULL),
(2,'operator','Operator Akademik','operator@universitascontoh.ac.id',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llCz5VqJf4Y6m8J2xZK2',
 NULL);

-- Contoh mahasiswa
INSERT INTO mahasiswa
(id_prodi, nim, nama_lengkap, jenis_kelamin, tempat_lahir, tanggal_lahir,
 angkatan, semester, status_mahasiswa, email, uri)
VALUES
(1,'2026010001','Ahmad Fauzan','L','Bengkulu','2007-02-12',2026,2,'Aktif',
 'ahmad.fauzan@example.ac.id','https://universitascontoh.ac.id/resource/mahasiswa/2026010001'),
(1,'2026010002','Siti Aisyah','P','Bengkulu','2007-05-21',2026,2,'Aktif',
 'siti.aisyah@example.ac.id','https://universitascontoh.ac.id/resource/mahasiswa/2026010002'),
(5,'2025010101','Muhammad Rizki','L','Curup','2006-01-10',2025,4,'Aktif',
 'muhammad.rizki@example.ac.id','https://universitascontoh.ac.id/resource/mahasiswa/2025010101'),
(5,'2025010102','Nur Azizah','P','Bengkulu','2006-07-18',2025,4,'Aktif',
 'nur.azizah@example.ac.id','https://universitascontoh.ac.id/resource/mahasiswa/2025010102'),
(6,'2024010201','Andi Saputra','L','Manna','2005-09-03',2024,6,'Aktif',
 'andi.saputra@example.ac.id','https://universitascontoh.ac.id/resource/mahasiswa/2024010201'),
(7,'2024010301','Rahma Putri','P','Bengkulu','2005-11-15',2024,6,'Aktif',
 'rahma.putri@example.ac.id','https://universitascontoh.ac.id/resource/mahasiswa/2024010301');

-- ============================================================
-- VIEW UNTUK DASHBOARD
-- ============================================================

CREATE OR REPLACE VIEW v_jumlah_mahasiswa_prodi AS
SELECT
    ps.id_prodi,
    ps.kode_prodi,
    ps.nama_prodi,
    ps.jenjang,
    f.kode_fakultas,
    f.nama_fakultas,
    COUNT(m.id_mahasiswa) AS jumlah_mahasiswa
FROM program_studi ps
JOIN fakultas f ON f.id_fakultas = ps.id_fakultas
LEFT JOIN mahasiswa m
       ON m.id_prodi = ps.id_prodi
      AND m.status_mahasiswa = 'Aktif'
GROUP BY
    ps.id_prodi,
    ps.kode_prodi,
    ps.nama_prodi,
    ps.jenjang,
    f.kode_fakultas,
    f.nama_fakultas;

CREATE OR REPLACE VIEW v_dashboard AS
SELECT
    (SELECT COUNT(*) FROM mahasiswa WHERE status_mahasiswa='Aktif') AS total_mahasiswa,
    (SELECT COUNT(*) FROM program_studi WHERE aktif=1) AS total_program_studi,
    (SELECT COUNT(*) FROM fakultas) AS total_fakultas,
    (SELECT COUNT(*) FROM mahasiswa WHERE status_mahasiswa='Aktif'
        AND YEAR(created_at)=YEAR(CURDATE())) AS mahasiswa_data_tahun_ini;

-- ============================================================
-- QUERY DASAR UNTUK APLIKASI
-- ============================================================

-- Daftar mahasiswa beserta program studi dan fakultas:
-- SELECT m.nim, m.nama_lengkap, ps.nama_prodi, f.nama_fakultas
-- FROM mahasiswa m
-- JOIN program_studi ps ON ps.id_prodi = m.id_prodi
-- JOIN fakultas f ON f.id_fakultas = ps.id_fakultas
-- ORDER BY ps.nama_prodi, m.nama_lengkap;

-- Statistik per program studi:
-- SELECT * FROM v_jumlah_mahasiswa_prodi ORDER BY jumlah_mahasiswa DESC;

-- Statistik dashboard:
-- SELECT * FROM v_dashboard;

-- ============================================================
-- CATATAN WEB SEMANTIK
-- ============================================================
-- Kolom URI sengaja disediakan pada universitas, fakultas,
-- program_studi dan mahasiswa agar data relasional dapat
-- dipetakan menjadi RDF.
--
-- Contoh konsep triple:
-- <.../mahasiswa/2026010001>
--     a <.../ontology/Mahasiswa> ;
--     <.../ontology/nama> "Ahmad Fauzan" ;
--     <.../ontology/terdaftarPada>
--          <.../prodi/TI> .
--
-- RDF/SPARQL sebaiknya dibangun pada layer aplikasi/endpoint,
-- bukan disimpan sebagai satu tabel RDF di database relasional.
