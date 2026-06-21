-- =========================================================
-- SIMPEG MINI — Sistem Informasi Kepegawaian Mini
-- Database: simpeg_mini
-- Sesuai spesifikasi: users, pegawai, absensi
-- =========================================================

CREATE DATABASE IF NOT EXISTS simpeg_mini
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE simpeg_mini;

DROP TABLE IF EXISTS absensi;
DROP TABLE IF EXISTS pegawai;
DROP TABLE IF EXISTS activity_log;
DROP TABLE IF EXISTS users;

-- ---------------------------------------------------------
-- Tabel: users
-- Akun login + role + rate limiting + remember me
-- ---------------------------------------------------------
CREATE TABLE users (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    username        VARCHAR(50)  NOT NULL UNIQUE,
    password        VARCHAR(255) NOT NULL,        -- bcrypt hash
    nama_lengkap    VARCHAR(100) NOT NULL,
    role            ENUM('admin', 'manager', 'karyawan') NOT NULL DEFAULT 'karyawan',
    failed_attempts INT NOT NULL DEFAULT 0,
    locked_until    DATETIME NULL,
    remember_token  VARCHAR(255) NULL,
    remember_expires DATETIME NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Tabel: pegawai
-- Data kepegawaian, terhubung 1:1 ke users
-- ---------------------------------------------------------
CREATE TABLE pegawai (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL,
    nip             VARCHAR(30) NOT NULL UNIQUE,
    jabatan         VARCHAR(100) NOT NULL,
    divisi          VARCHAR(100) NOT NULL,
    no_telepon      VARCHAR(20) NULL,
    alamat          TEXT NULL,
    tanggal_masuk   DATE NULL,
    status          ENUM('aktif', 'nonaktif', 'cuti') NOT NULL DEFAULT 'aktif',
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_pegawai_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Tabel: absensi
-- Catatan absensi harian tiap pegawai
-- ---------------------------------------------------------
CREATE TABLE absensi (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    pegawai_id      INT NOT NULL,
    tanggal         DATE NOT NULL,
    jam_masuk       TIME NULL,
    jam_keluar      TIME NULL,
    status          ENUM('hadir', 'izin', 'sakit', 'alpha') NOT NULL DEFAULT 'hadir',
    keterangan      VARCHAR(255) NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_absensi_pegawai
        FOREIGN KEY (pegawai_id) REFERENCES pegawai(id)
        ON DELETE CASCADE,
    UNIQUE KEY unique_absen_harian (pegawai_id, tanggal)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Tabel: activity_log (audit trail)
-- ---------------------------------------------------------
CREATE TABLE activity_log (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NULL,
    aktivitas   VARCHAR(255) NOT NULL,
    ip_address  VARCHAR(45) NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_log_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- SEED DATA
-- Password untuk SEMUA akun di bawah: "password123"
-- ---------------------------------------------------------
INSERT INTO users (username, password, nama_lengkap, role) VALUES
('admin1',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin Utama',      'admin'),
('manager1',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Budi Manager',     'manager'),
('manager2',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Rina Manager',     'manager'),
('karyawan1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Siti Karyawan',    'karyawan'),
('karyawan2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Joko Karyawan',    'karyawan'),
('karyawan3', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dewi Karyawan',    'karyawan');

INSERT INTO pegawai (user_id, nip, jabatan, divisi, no_telepon, alamat, tanggal_masuk, status) VALUES
(1, '198001012010011001', 'Administrator Sistem',       'IT',           '081200000001', 'Jakarta',  '2010-01-01', 'aktif'),
(2, '198505152012012002', 'Kepala Divisi IT',           'IT',           '081200000002', 'Wonosobo', '2012-01-02', 'aktif'),
(3, '198703102013012003', 'Kepala Divisi Administrasi', 'Administrasi', '081200000003', 'Wonosobo', '2013-02-01', 'aktif'),
(4, '199003202015013004', 'Staff IT',                   'IT',           '081200000004', 'Wonosobo', '2015-01-03', 'aktif'),
(5, '199105102016013005', 'Staff Administrasi',         'Administrasi', '081200000005', 'Wonosobo', '2016-03-10', 'aktif'),
(6, '199208152017013006', 'Staff Administrasi',         'Administrasi', '081200000006', 'Wonosobo', '2017-05-20', 'aktif');

INSERT INTO absensi (pegawai_id, tanggal, jam_masuk, jam_keluar, status, keterangan) VALUES
(1, CURDATE() - INTERVAL 4 DAY, '08:00:00', '17:00:00', 'hadir', NULL),
(1, CURDATE() - INTERVAL 3 DAY, '08:05:00', '17:00:00', 'hadir', NULL),
(1, CURDATE() - INTERVAL 2 DAY, '08:00:00', '17:00:00', 'hadir', NULL),
(1, CURDATE() - INTERVAL 1 DAY, NULL, NULL, 'izin', 'Keperluan keluarga'),

(4, CURDATE() - INTERVAL 4 DAY, '08:10:00', '17:00:00', 'hadir', NULL),
(4, CURDATE() - INTERVAL 3 DAY, '08:00:00', '17:05:00', 'hadir', NULL),
(4, CURDATE() - INTERVAL 2 DAY, NULL, NULL, 'sakit', 'Demam'),
(4, CURDATE() - INTERVAL 1 DAY, '08:00:00', '17:00:00', 'hadir', NULL),

(5, CURDATE() - INTERVAL 4 DAY, '08:00:00', '17:00:00', 'hadir', NULL),
(5, CURDATE() - INTERVAL 3 DAY, '08:00:00', '17:00:00', 'hadir', NULL),
(5, CURDATE() - INTERVAL 2 DAY, '08:00:00', '17:00:00', 'hadir', NULL),
(5, CURDATE() - INTERVAL 1 DAY, '08:00:00', NULL, 'hadir', 'Belum absen pulang'),

(6, CURDATE() - INTERVAL 4 DAY, NULL, NULL, 'alpha', NULL),
(6, CURDATE() - INTERVAL 3 DAY, '08:15:00', '17:00:00', 'hadir', NULL),
(6, CURDATE() - INTERVAL 2 DAY, '08:00:00', '17:00:00', 'hadir', NULL),
(6, CURDATE() - INTERVAL 1 DAY, '08:00:00', '17:00:00', 'hadir', NULL);

-- =========================================================
-- CATATAN:
-- Hash bcrypt di atas adalah CONTOH untuk password "password123".
-- Generate hash sendiri sebelum dipakai serius:
--   php -r "echo password_hash('passwordkamu', PASSWORD_BCRYPT);"
-- =========================================================
