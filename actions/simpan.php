<?php
/**
 * actions/simpan.php
 * Memproses penyimpanan data absensi (masuk & pulang)
 */

require_once __DIR__ . '/../config/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit();
}

$aksi    = trim($_POST['aksi']    ?? '');
$id_card = strtoupper(trim($_POST['id_card'] ?? ''));

// ============================================================
//  HELPER: Ambil data karyawan + shift
// ============================================================
function getKaryawan($koneksi, $id_card) {
    $q = mysqli_prepare($koneksi,
        "SELECT k.*, s.jam_masuk as shift_jam_masuk, s.jam_pulang as shift_jam_pulang,
                s.toleransi_menit, s.nama_shift, s.id as shift_id_val
         FROM karyawan k
         LEFT JOIN shift s ON k.shift_id = s.id
         WHERE k.id_card = ? AND k.aktif = 1 LIMIT 1"
    );
    mysqli_stmt_bind_param($q, "s", $id_card);
    mysqli_stmt_execute($q);
    $r = mysqli_fetch_assoc(mysqli_stmt_get_result($q));
    mysqli_stmt_close($q);
    return $r;
}

// ============================================================
//  HELPER: Validasi GPS / Geofencing
// ============================================================
function validasiGeo($k, $lat_u, $lng_u) {
    if (empty($k['lat']) || empty($k['lng'])) return true;
    if (empty($lat_u)    || empty($lng_u))    return false;

    $R   = 6371000;
    $dLa = ((float)$lat_u - (float)$k['lat']) * M_PI / 180;
    $dLo = ((float)$lng_u - (float)$k['lng']) * M_PI / 180;
    $a   = sin($dLa / 2) ** 2
         + cos((float)$k['lat'] * M_PI / 180)
         * cos((float)$lat_u   * M_PI / 180)
         * sin($dLo / 2) ** 2;

    $jarak = $R * 2 * atan2(sqrt($a), sqrt(1 - $a));
    return $jarak <= ($k['radius_meter'] ?? 100);
}

// ============================================================
//  HELPER: Hitung keterlambatan (menit)
// ============================================================
function hitungTerlambat($k, $jam_masuk) {
    if (empty($k['shift_jam_masuk'])) return 0;

    $batas  = strtotime(date('Y-m-d') . ' ' . $k['shift_jam_masuk']);
    $batas += ($k['toleransi_menit'] ?? 15) * 60;
    $aktual = strtotime(date('Y-m-d') . ' ' . $jam_masuk);

    if ($aktual <= $batas) return 0;
    return (int)(($aktual - strtotime(date('Y-m-d') . ' ' . $k['shift_jam_masuk'])) / 60);
}

// ============================================================
//  HELPER: Cek Alpa (terlambat & tidak mengganti waktu)
// ============================================================
function cekAlpa($k, $jam_masuk, $jam_pulang, $terlambat) {
    if ($terlambat <= 0) return false;
    if (empty($k['shift_jam_masuk']) || empty($k['shift_jam_pulang'])) return false;

    $tgl        = date('Y-m-d');
    $dur_shift  = strtotime("$tgl {$k['shift_jam_pulang']}") - strtotime("$tgl {$k['shift_jam_masuk']}");
    $dur_aktual = strtotime("$tgl $jam_pulang")              - strtotime("$tgl $jam_masuk");

    return $dur_aktual < $dur_shift;
}

// ============================================================
//  HELPER: Simpan foto ke folder
//  Format nama: id2065-Farel_masuk_20260617_083000_abc123.jpg
// ============================================================
function simpanFoto($id_card, $nama, $label, $folder) {
    $dir = __DIR__ . '/../' . $folder . '/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $id_b  = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $id_card));
    $nm_b  = preg_replace('/[^a-zA-Z0-9]/', '', explode(' ', $nama)[0]);
    $file  = "{$id_b}-{$nm_b}_{$label}_" . date('Ymd_His') . '_' . substr(md5(uniqid()), 0, 6) . '.jpg';

    $b64 = $_POST['foto_kamera'] ?? '';
    if (!empty($b64)) {
        $data = base64_decode(preg_replace('/^data:image\/\w+;base64,/', '', $b64));
        if ($data === false) return false;
        file_put_contents($dir . $file, $data);
        return $file;
    }
    return false;
}

// ============================================================
//  MULAI PROSES
// ============================================================
$karyawan = getKaryawan($koneksi, $id_card);
if (!$karyawan) {
    header('Location: ../index.php?pesan=id-invalid');
    exit();
}

$nama    = $karyawan['nama'];
$lat_usr = trim($_POST['lat_user'] ?? '');
$lng_usr = trim($_POST['lng_user'] ?? '');

// ============================================================
//  AKSI: MASUK
// ============================================================
if ($aksi === 'masuk') {
    $status  = trim($_POST['status'] ?? 'Hadir');
    $tanggal = date('Y-m-d');

    // Cek duplikat absen hari ini
    $cx = mysqli_prepare($koneksi, "SELECT id FROM absensi WHERE id_card=? AND tanggal=? LIMIT 1");
    mysqli_stmt_bind_param($cx, "ss", $id_card, $tanggal);
    mysqli_stmt_execute($cx);
    mysqli_stmt_store_result($cx);
    $ada = mysqli_stmt_num_rows($cx) > 0;
    mysqli_stmt_close($cx);

    if ($ada) {
        header('Location: ../index.php?pesan=sudah-masuk&id=' . urlencode($id_card));
        exit();
    }

    // --- IZIN / SAKIT (sekali kirim, tanpa GPS & PIN) ---
    if (in_array($status, ['Izin', 'Sakit'])) {
        $ket = trim($_POST['keterangan'] ?? '');
        if (empty($ket)) {
            header('Location: ../index.php?pesan=no-ket&id=' . urlencode($id_card));
            exit();
        }

        $foto = simpanFoto($id_card, $nama, strtolower($status), 'uploads-bukti');
        if (!$foto) {
            header('Location: ../index.php?pesan=no-foto&id=' . urlencode($id_card));
            exit();
        }

        $st = mysqli_prepare($koneksi,
            "INSERT INTO absensi(id_card,nama_karyawan,tanggal,status,foto_masuk,keterangan)
             VALUES(?,?,?,?,?,?)"
        );
        mysqli_stmt_bind_param($st, "ssssss", $id_card, $nama, $tanggal, $status, $foto, $ket);
        $ok = mysqli_stmt_execute($st);
        mysqli_stmt_close($st);

        $pesan = ($status === 'Izin') ? 'izin-ok' : 'sakit-ok';
        header('Location: ../index.php?pesan=' . $pesan . '&id=' . urlencode($id_card));
        exit();
    }

    // --- HADIR: validasi GPS ---
    if (!validasiGeo($karyawan, $lat_usr, $lng_usr)) {
        header('Location: ../index.php?pesan=lokasi-gagal&id=' . urlencode($id_card));
        exit();
    }

    // Validasi PIN
    $pin_raw = trim($_POST['pin_absen'] ?? '');
    if (strlen($pin_raw) < 4 || !ctype_digit($pin_raw)) {
        header('Location: ../index.php?pesan=no-pin&id=' . urlencode($id_card));
        exit();
    }

    // Simpan PIN plain ke tabel karyawan (hanya admin yang bisa lihat)
    $upx = mysqli_prepare($koneksi, "UPDATE karyawan SET pin_aktif=? WHERE id_card=?");
    mysqli_stmt_bind_param($upx, "ss", $pin_raw, $id_card);
    mysqli_stmt_execute($upx);
    mysqli_stmt_close($upx);

    $pin_hash  = password_hash($pin_raw, PASSWORD_DEFAULT);
    $jam_masuk = date('H:i:s');
    $terlambat = hitungTerlambat($karyawan, $jam_masuk);
    $shift_bind = is_null($karyawan['shift_id_val'] ?? null) ? 0 : (int)$karyawan['shift_id_val'];

    $foto = simpanFoto($id_card, $nama, 'masuk', 'uploads');
    if (!$foto) {
        header('Location: ../index.php?pesan=no-foto&id=' . urlencode($id_card));
        exit();
    }

    $st = mysqli_prepare($koneksi,
        "INSERT INTO absensi(id_card,nama_karyawan,tanggal,jam_masuk,status,foto_masuk,pin_absen,terlambat_menit,shift_id)
         VALUES(?,?,?,?,?,?,?,?,?)"
    );
    mysqli_stmt_bind_param($st, "sssssssii",
        $id_card, $nama, $tanggal, $jam_masuk, $status, $foto, $pin_hash,
        $terlambat, $shift_bind
    );
    $ok = mysqli_stmt_execute($st);
    mysqli_stmt_close($st);

    header('Location: ../index.php?pesan=' . ($ok ? 'masuk-ok' : 'gagal') . '&id=' . urlencode($id_card));
    exit();
}

// ============================================================
//  AKSI: PULANG
// ============================================================
if ($aksi === 'pulang') {
    $id_absen   = intval($_POST['id_absen'] ?? 0);
    $pin_raw    = trim($_POST['pin_absen'] ?? '');
    $jam_pulang = date('H:i:s');
    $tanggal    = date('Y-m-d');

    if (strlen($pin_raw) < 4 || !ctype_digit($pin_raw)) {
        header('Location: ../index.php?pesan=pin-salah&id=' . urlencode($id_card));
        exit();
    }

    if (!validasiGeo($karyawan, $lat_usr, $lng_usr)) {
        header('Location: ../index.php?pesan=lokasi-gagal&id=' . urlencode($id_card));
        exit();
    }

    // Ambil data absen masuk
    $q = mysqli_prepare($koneksi,
        "SELECT jam_masuk, jam_pulang, pin_absen, terlambat_menit
         FROM absensi WHERE id=? AND id_card=?"
    );
    mysqli_stmt_bind_param($q, "is", $id_absen, $id_card);
    mysqli_stmt_execute($q);
    $data = mysqli_fetch_assoc(mysqli_stmt_get_result($q));
    mysqli_stmt_close($q);

    if (!$data) {
        header('Location: ../index.php?pesan=gagal&id=' . urlencode($id_card));
        exit();
    }
    if (!is_null($data['jam_pulang'])) {
        header('Location: ../index.php?pesan=sudah-pulang&id=' . urlencode($id_card));
        exit();
    }
    if (!password_verify($pin_raw, $data['pin_absen'])) {
        header('Location: ../index.php?pesan=pin-salah&id=' . urlencode($id_card));
        exit();
    }

    $foto = simpanFoto($id_card, $nama, 'pulang', 'uploads');
    if (!$foto) {
        header('Location: ../index.php?pesan=no-foto&id=' . urlencode($id_card));
        exit();
    }

    // Hitung total jam kerja
    $selisih   = max(0, strtotime("$tanggal $jam_pulang") - strtotime("$tanggal {$data['jam_masuk']}"));
    $total_jam = floor($selisih / 3600) . 'j ' . floor(($selisih % 3600) / 60) . 'm';

    // Cek logika Alpa
    $terlambat_menit = intval($data['terlambat_menit'] ?? 0);
    $status_final    = cekAlpa($karyawan, $data['jam_masuk'], $jam_pulang, $terlambat_menit)
                       ? 'Alpa' : 'Hadir';

    $upd = mysqli_prepare($koneksi,
        "UPDATE absensi SET jam_pulang=?, total_jam_kerja=?, foto_pulang=?, status=?
         WHERE id=? AND id_card=?"
    );
    mysqli_stmt_bind_param($upd, "ssss" . "i" . "s",
        $jam_pulang, $total_jam, $foto, $status_final, $id_absen, $id_card
    );
    $ok = mysqli_stmt_execute($upd);
    mysqli_stmt_close($upd);

    $pesan = $ok ? ($status_final === 'Alpa' ? 'pulang-alpa' : 'pulang-ok') : 'gagal';
    header('Location: ../index.php?pesan=' . $pesan . '&id=' . urlencode($id_card));
    exit();
}

// Fallback
header('Location: ../index.php');
exit();
