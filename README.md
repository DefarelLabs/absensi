# 🏢 Sistem Absensi Karyawan (Kiosk Mode)

Aplikasi absensi karyawan berbasis web untuk digunakan di satu perangkat bersama (kios) di kantor/toko. Karyawan absen masuk & pulang dengan ID Card / QR Code, verifikasi foto wajah secara live, PIN keamanan, dan validasi lokasi GPS (geofencing). Admin punya panel lengkap untuk mengelola karyawan, shift, dan laporan.

---

## ✨ Fitur Utama

### Sisi Karyawan (`index.php`)
- **Login tanpa nama bebas** — karyawan input ID Card (`ID1234`) atau scan QR Code dari kartu fisik
- **Absen Masuk / Pulang** dengan deteksi otomatis status harian
- **Status kehadiran**: Hadir, Izin, Sakit — dengan keterangan wajib untuk Izin/Sakit
- **Foto wajib via kamera live** (tidak bisa upload dari galeri, mencegah kecurangan)
- **Deteksi wajah real-time** (face-api.js) — tombol ambil foto baru aktif kalau wajah terdeteksi
- **PIN keamanan 4-6 digit** — dibuat saat absen masuk, wajib diinput ulang saat pulang (mencegah orang lain absen-pulangkan karyawan lain)
- **Geofencing GPS** — karyawan hanya bisa absen jika berada dalam radius lokasi kerja yang ditentukan admin
- **Perhitungan otomatis**: total jam kerja, keterlambatan, dan status **Alpa** otomatis jika terlambat & tidak mengganti waktu saat pulang
- **Riwayat absensi pribadi** — tersembunyi sampai karyawan login dengan ID valid (privasi antar karyawan terjaga)

### Sisi Admin (`admin.php`)
- **Login terpisah** dengan password
- **Dashboard** — ringkasan harian (Hadir/Izin/Sakit/Total), filter tanggal & status, lihat foto absen langsung, tombol reset data per baris
- **Laporan Absen** — filter Hari Ini / 1-3 Minggu / 1 Bulan / tanggal spesifik, export ke CSV, XLSX, dan PDF
- **Jam Kerja / Shift** — kelola shift kerja & toleransi keterlambatan
- **Manajemen Karyawan**:
  - Tambah karyawan dengan ID Card auto-generate atau custom
  - Edit data karyawan (nama, jabatan, shift, lokasi GPS)
  - **Peta interaktif (Leaflet + OpenStreetMap)** — klik peta, pakai GPS sendiri, atau cari lokasi by nama tempat untuk set titik geofencing
  - **QR Code otomatis** per karyawan + download satu/semua (ZIP)
  - Pencarian karyawan + bulk delete
  - Lihat PIN aktif karyawan (untuk bantu karyawan yang lupa PIN)

---

## 🛠️ Teknologi yang Digunakan

| Kategori | Teknologi |
|---|---|
| **Backend** | PHP 8 (native, tanpa framework), MySQLi (prepared statements) |
| **Database** | MySQL / MariaDB |
| **Frontend** | HTML5, CSS3 (custom, tanpa framework CSS), Vanilla JavaScript (ES6+) |
| **Font** | [DM Sans & DM Mono](https://fonts.google.com/) (Google Fonts) |
| **Ikon** | [Bootstrap Icons](https://icons.getbootstrap.com/) |
| **QR Code Generator** | [qrcodejs](https://github.com/davidshimjs/qrcodejs) |
| **QR/Barcode Scanner** | [ZXing-js](https://github.com/zxing-js/library) |
| **Deteksi Wajah** | [face-api.js](https://github.com/justadudewhohacks/face-api.js) (TinyFaceDetector) |
| **Peta Interaktif** | [Leaflet.js](https://leafletjs.com/) + [OpenStreetMap](https://www.openstreetmap.org/) |
| **Geocoding (cari lokasi)** | [Nominatim API](https://nominatim.org/) |
| **Kompresi ZIP** | [JSZip](https://stuk.github.io/jszip/) |
| **Web Server (lokal)** | Apache (XAMPP) |

---

## 📂 Struktur Folder

```
absensi/
├── actions/
│   └── simpan.php             # Logika proses simpan absensi (masuk/pulang)
├── assets/
│   ├── css/
│   │   └── style.css          # Semua styling (index + admin)
│   └── js/
│       ├── script.js          # JS halaman absensi (kamera, GPS, face detection)
│       └── admin.js           # JS halaman admin (peta, QR, sidebar)
├── config/
│   └── koneksi.php            # Koneksi database
├── includes/
│   ├── header.php             # <head> halaman index
│   ├── header_admin.php       # <head> halaman admin
│   ├── footer.php             # Penutup index + load script.js
│   ├── footer_admin.php       # Penutup admin + load admin.js
│   ├── partials/
│   │   └── kamera_foto.php    # Komponen kamera (dipakai 2x di index)
│   └── admin/
│       ├── dashboard.php      # Konten menu Dashboard
│       ├── laporan.php        # Konten menu Laporan
│       ├── jamkerja.php       # Konten menu Jam Kerja/Shift
│       ├── karyawan.php       # Konten menu Karyawan
│       └── form_edit_karyawan.php  # Form modal edit karyawan
├── uploads/                   # Foto absen Hadir (masuk/pulang) — auto-generated
├── uploads-bukti/             # Foto bukti Izin/Sakit — auto-generated
├── index.php                  # Halaman utama absensi (kiosk)
├── admin.php                  # Panel admin
├── database.sql               # Script SQL setup database
├── .gitignore
└── README.md
```

---

## 🚀 Instalasi (Local — XAMPP)

### 1. Clone / Download repo
```bash
git clone https://github.com/DefarelLabs/absensi.git
cd absensi
```

### 2. Pindahkan ke folder htdocs XAMPP
Salin seluruh folder ke:
```
C:\xampp\htdocs\absensi\
```

### 3. Buat database
- Jalankan **Apache** dan **MySQL** di XAMPP Control Panel
- Buka `http://localhost/phpmyadmin`
- Klik tab **SQL**, jalankan isi file `database.sql`

### 4. Sesuaikan koneksi database
Buka `config/koneksi.php`, sesuaikan jika perlu:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'db_absensi');
```

### 5. Buat folder upload (kalau belum otomatis ada)
```
absensi/uploads/
absensi/uploads-bukti/
```

### 6. Akses aplikasi
- Halaman absensi: `http://localhost/absensi/`
- Halaman admin: `http://localhost/absensi/admin.php`
  **Password default:** `admin123`
  *(ubah di `admin.php`, baris `define('ADMIN_PASSWORD', 'admin123');`)*

---

## ⚠️ Catatan Penting

- **Kamera & GPS butuh HTTPS** di kebanyakan browser modern (kecuali `localhost`). Untuk testing di HP lewat jaringan WiFi, gunakan tool tunneling seperti [ngrok](https://ngrok.com/) agar dapat HTTPS.
- **face-api.js** memuat model dari CDN — pastikan koneksi internet aktif saat pertama kali membuka halaman absensi.
- Jangan lupa ganti `ADMIN_PASSWORD` sebelum digunakan secara production.
- PIN absensi disimpan dengan **hash** (`password_hash()`) di tabel `absensi` untuk verifikasi keamanan, sementara PIN versi plain disimpan terpisah di kolom `pin_aktif` tabel `karyawan` khusus untuk ditampilkan ke admin (membantu karyawan yang lupa PIN).

---

## 📄 Lisensi

Proyek ini bebas digunakan dan dimodifikasi untuk keperluan pribadi maupun komersial.
