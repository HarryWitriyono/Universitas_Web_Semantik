-- =========================================================
-- DATABASE SIM DOSEN
-- Universitas Muhammadiyah Bengkulu
-- MySQL / MariaDB (phpMyAdmin)
-- =========================================================

CREATE DATABASE IF NOT EXISTS sim_dosen
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE sim_dosen;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS pengguna;
DROP TABLE IF EXISTS dosen;
DROP TABLE IF EXISTS roles;
DROP TABLE IF EXISTS penempatan;
DROP TABLE IF EXISTS status_kepegawaian;
DROP TABLE IF EXISTS rumpun_ilmu;
DROP TABLE IF EXISTS pendidikan;
DROP TABLE IF EXISTS kepangkatan;
DROP TABLE IF EXISTS jabatan_fungsional;

SET FOREIGN_KEY_CHECKS = 1;

-- Referensi jabatan fungsional
CREATE TABLE jabatan_fungsional (
    id_jabatan INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama_jabatan VARCHAR(100) NOT NULL UNIQUE,
    aktif TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

-- Referensi kepangkatan
CREATE TABLE kepangkatan (
    id_kepangkatan INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama_pangkat VARCHAR(100) NOT NULL,
    golongan VARCHAR(20) NULL,
    ruang VARCHAR(10) NULL,
    UNIQUE KEY uq_pangkat_golongan (nama_pangkat, golongan, ruang)
) ENGINE=InnoDB;

-- Referensi pendidikan terakhir
CREATE TABLE pendidikan (
    id_pendidikan INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    jenjang VARCHAR(30) NOT NULL UNIQUE,
    urutan TINYINT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB;

-- Referensi rumpun ilmu
CREATE TABLE rumpun_ilmu (
    id_rumpun_ilmu INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama_rumpun VARCHAR(150) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- Referensi status kepegawaian dan ikatan kerja
CREATE TABLE status_kepegawaian (
    id_status_kepegawaian INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    status_pegawai VARCHAR(100) NOT NULL,
    ikatan_kerja VARCHAR(100) NOT NULL,
    UNIQUE KEY uq_status_ikatan (status_pegawai, ikatan_kerja)
) ENGINE=InnoDB;

-- Struktur penempatan akademik
CREATE TABLE penempatan (
    id_penempatan INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    universitas VARCHAR(200) NOT NULL,
    fakultas VARCHAR(200) NOT NULL,
    program_studi VARCHAR(200) NOT NULL,
    aktif TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY uq_penempatan (universitas, fakultas, program_studi)
) ENGINE=InnoDB;

-- Data pokok dosen
CREATE TABLE dosen (
    id_dosen BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nomor_registrasi VARCHAR(50) NULL,
    nuptk VARCHAR(30) NULL,
    nama VARCHAR(150) NOT NULL,
    nip VARCHAR(30) NULL,

    id_jabatan INT UNSIGNED NULL,
    id_kepangkatan INT UNSIGNED NULL,
    id_pendidikan INT UNSIGNED NULL,
    id_rumpun_ilmu INT UNSIGNED NULL,
    id_status_kepegawaian INT UNSIGNED NULL,
    id_penempatan INT UNSIGNED NULL,

    status_serdos ENUM('Sudah','Belum','Tidak Berlaku','Belum Diketahui')
        NOT NULL DEFAULT 'Belum Diketahui',
    status_aktivitas ENUM('Aktif','Tidak Aktif','Cuti','Pensiun','Belum Diketahui')
        NOT NULL DEFAULT 'Aktif',

    keterangan TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uq_nomor_registrasi (nomor_registrasi),
    UNIQUE KEY uq_nuptk (nuptk),
    UNIQUE KEY uq_nip (nip),
    KEY idx_nama (nama),
    KEY idx_status_aktivitas (status_aktivitas),
    KEY idx_penempatan (id_penempatan),

    CONSTRAINT fk_dosen_jabatan
        FOREIGN KEY (id_jabatan) REFERENCES jabatan_fungsional(id_jabatan)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_dosen_kepangkatan
        FOREIGN KEY (id_kepangkatan) REFERENCES kepangkatan(id_kepangkatan)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_dosen_pendidikan
        FOREIGN KEY (id_pendidikan) REFERENCES pendidikan(id_pendidikan)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_dosen_rumpun
        FOREIGN KEY (id_rumpun_ilmu) REFERENCES rumpun_ilmu(id_rumpun_ilmu)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_dosen_status_pegawai
        FOREIGN KEY (id_status_kepegawaian) REFERENCES status_kepegawaian(id_status_kepegawaian)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_dosen_penempatan
        FOREIGN KEY (id_penempatan) REFERENCES penempatan(id_penempatan)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

-- Peran akun aplikasi
CREATE TABLE roles (
    id_role INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama_role VARCHAR(50) NOT NULL UNIQUE,
    keterangan VARCHAR(255) NULL
) ENGINE=InnoDB;

-- Akun login aplikasi; password wajib disimpan dalam bentuk hash
CREATE TABLE pengguna (
    id_pengguna BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_dosen BIGINT UNSIGNED NULL,
    id_role INT UNSIGNED NOT NULL,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    nama_tampilan VARCHAR(150) NULL,
    email VARCHAR(190) NULL,
    aktif TINYINT(1) NOT NULL DEFAULT 1,
    login_terakhir DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uq_pengguna_email (email),
    KEY idx_pengguna_dosen (id_dosen),
    KEY idx_pengguna_role (id_role),

    CONSTRAINT fk_pengguna_dosen
        FOREIGN KEY (id_dosen) REFERENCES dosen(id_dosen)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_pengguna_role
        FOREIGN KEY (id_role) REFERENCES roles(id_role)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Data awal referensi: silakan sesuaikan dengan nomenklatur resmi kampus
INSERT INTO jabatan_fungsional (nama_jabatan) VALUES
('Tenaga Pengajar'),
('Asisten Ahli'),
('Lektor'),
('Lektor Kepala'),
('Profesor');

INSERT INTO pendidikan (jenjang, urutan) VALUES
('D3', 1),
('S1', 2),
('S2', 3),
('S3', 4),
('Spesialis', 4),
('Profesi', 3);

INSERT INTO status_kepegawaian (status_pegawai, ikatan_kerja) VALUES
('Dosen Tetap', 'Penuh Waktu'),
('Dosen Tetap', 'Paruh Waktu'),
('Dosen Tidak Tetap', 'Paruh Waktu'),
('Dosen Luar Biasa', 'Paruh Waktu');

INSERT INTO penempatan (universitas, fakultas, program_studi) VALUES
('Universitas Muhammadiyah Bengkulu', 'Teknik', 'Teknik Informatika');

INSERT INTO roles (nama_role, keterangan) VALUES
('ADMIN', 'Pengelola seluruh data dan akun SIM Dosen'),
('OPERATOR_PRODI', 'Pengelola data dosen pada program studi'),
('FAKULTAS', 'Akses pengelolaan dan laporan tingkat fakultas'),
('PIMPINAN', 'Akses melihat laporan dan rekapitulasi'),
('DOSEN', 'Akses terbatas untuk data sendiri');

-- Catatan:
-- 1. Dump ini tidak membuat akun login bawaan.
-- 2. Buat password_hash menggunakan password_hash() PHP.
-- 3. Kolom status dan referensi dapat disesuaikan dengan format resmi kampus.
-- 4. Jika dosen dapat ditempatkan pada beberapa prodi sekaligus,
--    pengembangan berikutnya sebaiknya memakai tabel relasi dosen_penempatan.
