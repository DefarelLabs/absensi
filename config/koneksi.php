<?php
/**
 * config/koneksi.php
 * Konfigurasi koneksi database MySQL
 */

// Timezone WIB - wajib agar jam PHP sesuai waktu Indonesia
date_default_timezone_set('Asia/Jakarta');

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'db_absensi');

$koneksi = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (!$koneksi) {
    die(json_encode([
        'status'  => 'error',
        'message' => 'Koneksi database gagal: ' . mysqli_connect_error()
    ]));
}

mysqli_set_charset($koneksi, 'utf8mb4');
