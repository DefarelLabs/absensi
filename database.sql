-- ============================================================
--  ABSENSI KARYAWAN - FRESH INSTALL SQL (v10)
--  Jalankan di phpMyAdmin > tab SQL
--  Database: db_absensi
-- ============================================================

-- 1. Buat database
CREATE DATABASE IF NOT EXISTS db_absensi CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE db_absensi;

-- ============================================================
--  TABEL 1: shift
--  Menyimpan jam kerja / shift karyawan
-- ============================================================
CREATE TABLE IF NOT EXISTS shift (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    nama_shift      VARCHAR(50)  NOT NULL,
    jam_masuk       TIME         NOT NULL,
    jam_pulang      TIME         NOT NULL,
    toleransi_menit INT          NOT NULL DEFAULT 15,
    aktif           TINYINT(1)   NOT NULL DEFAULT 1,
    dibuat_pada     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Data shift default (bisa dihapus/edit dari menu admin)
INSERT INTO shift (nama_shift, jam_masuk, jam_pulang, toleransi_menit) VALUES
('Shift Pagi',  '08:00:00', '17:00:00', 15),
('Shift Siang', '13:00:00', '21:00:00', 15);

-- ============================================================
--  TABEL 2: karyawan
--  Menyimpan data karyawan + ID Card + GPS + PIN
-- ============================================================
CREATE TABLE IF NOT EXISTS karyawan (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    id_card      VARCHAR(10)    NOT NULL UNIQUE,
    nama         VARCHAR(100)   NOT NULL,
    posisi       VARCHAR(100)   NULL DEFAULT NULL,
    shift_id     INT            NULL DEFAULT NULL,
    lat          VARCHAR(20)    NULL DEFAULT NULL,  -- disimpan string agar presisi desimal terjaga
    lng          VARCHAR(20)    NULL DEFAULT NULL,
    radius_meter INT            NOT NULL DEFAULT 100,
    pin_aktif    VARCHAR(10)    NULL DEFAULT NULL,  -- PIN plain untuk ditampilkan di admin
    aktif        TINYINT(1)     NOT NULL DEFAULT 1,
    dibuat_pada  DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (shift_id) REFERENCES shift(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
--  TABEL 3: absensi
--  Menyimpan semua data absensi harian karyawan
-- ============================================================
CREATE TABLE IF NOT EXISTS absensi (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    id_card          VARCHAR(10)  NULL DEFAULT NULL,
    nama_karyawan    VARCHAR(100) NOT NULL,
    tanggal          DATE         NOT NULL,
    jam_masuk        TIME         NULL DEFAULT NULL,
    jam_pulang       TIME         NULL DEFAULT NULL,
    total_jam_kerja  VARCHAR(20)  NULL DEFAULT NULL,
    status           ENUM('Hadir','Izin','Sakit','Alpa') NOT NULL DEFAULT 'Hadir',
    foto_masuk       VARCHAR(255) NULL DEFAULT NULL,
    foto_pulang      VARCHAR(255) NULL DEFAULT NULL,
    keterangan       TEXT         NULL DEFAULT NULL,
    pin_absen        VARCHAR(255) NULL DEFAULT NULL,  -- PIN di-hash dengan password_hash()
    terlambat_menit  INT          NULL DEFAULT NULL,
    shift_id         INT          NULL DEFAULT NULL,
    dibuat_pada      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_id_card   (id_card),
    INDEX idx_tanggal   (tanggal),
    INDEX idx_status    (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
--  SELESAI
--  Setelah menjalankan SQL ini:
--  1. Taruh semua file PHP di: C:\xampp\htdocs\absensi\
--  2. Buat 2 folder di dalam folder absensi:
--       uploads\        -> foto absen hadir masuk/pulang
--       uploads-bukti\  -> foto bukti izin/sakit
--  3. Buka: http://localhost/absensi/
--  4. Admin: http://localhost/absensi/admin.php
--     Password default: admin123
--     (Ubah di admin.php baris: define('ADMIN_PASSWORD','admin123');)
-- ============================================================
