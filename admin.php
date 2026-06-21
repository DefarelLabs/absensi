<?php
/**
 * admin.php
 * Panel administrasi sistem absensi
 * CSS dan JS dipisah ke assets/ - hanya berisi logika PHP + template HTML
 */

require_once 'config/koneksi.php';
session_start();

// ============================================================
//  AUTENTIKASI ADMIN
// ============================================================
define('ADMIN_PASSWORD', 'admin123'); // Ganti password di sini

$page_title = 'Admin - Absensi';
$err_login  = '';
$pesan_ok   = '';

if (isset($_POST['aksi']) && $_POST['aksi'] === 'login') {
    if ($_POST['password'] === ADMIN_PASSWORD) $_SESSION['admin_login'] = true;
    else $err_login = 'Password salah!';
}
if (isset($_GET['logout'])) { session_destroy(); header('Location: admin.php'); exit(); }

$login = !empty($_SESSION['admin_login']);

// ============================================================
//  PROSES AKSI (hanya jika sudah login)
// ============================================================
if ($login) {

    // --- Tambah karyawan ---
    if (isset($_POST['aksi']) && $_POST['aksi'] === 'tambah_karyawan') {
        $nama        = trim($_POST['nama']        ?? '');
        $posisi      = trim($_POST['posisi']      ?? '');
        $shift       = intval($_POST['shift_id']  ?? 0);
        $lat         = trim($_POST['lat']         ?? '');
        $lng         = trim($_POST['lng']         ?? '');
        $radius      = intval($_POST['radius']    ?? 100);
        $kode_custom = strtoupper(trim($_POST['kode_custom'] ?? ''));

        if (!empty($nama)) {
            $idc = '';
            if (!empty($kode_custom)) {
                if (!preg_match('/^[A-Z0-9]{4,8}$/', $kode_custom)) {
                    $pesan_ok = "gagal|Kode harus 4-8 karakter huruf/angka.";
                } else {
                    $cx = mysqli_prepare($koneksi, "SELECT id FROM karyawan WHERE id_card=?");
                    mysqli_stmt_bind_param($cx, "s", $kode_custom);
                    mysqli_stmt_execute($cx); mysqli_stmt_store_result($cx);
                    $ada = mysqli_stmt_num_rows($cx) > 0; mysqli_stmt_close($cx);
                    if ($ada) $pesan_ok = "gagal|Kode <strong>{$kode_custom}</strong> sudah dipakai.";
                    else $idc = $kode_custom;
                }
            } else {
                do {
                    $idc = 'ID' . str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);
                    $cx  = mysqli_prepare($koneksi, "SELECT id FROM karyawan WHERE id_card=?");
                    mysqli_stmt_bind_param($cx, "s", $idc); mysqli_stmt_execute($cx);
                    mysqli_stmt_store_result($cx); $ada = mysqli_stmt_num_rows($cx) > 0; mysqli_stmt_close($cx);
                } while ($ada);
            }

            if (!empty($idc) && empty($pesan_ok)) {
                $lat_esc   = mysqli_real_escape_string($koneksi, $lat);
                $lng_esc   = mysqli_real_escape_string($koneksi, $lng);
                $nama_esc  = mysqli_real_escape_string($koneksi, $nama);
                $pos_esc   = mysqli_real_escape_string($koneksi, $posisi);
                $idc_esc   = mysqli_real_escape_string($koneksi, $idc);
                $shift_val = ($shift > 0) ? intval($shift) : 'NULL';
                $lat_val   = !empty($lat_esc) ? "'$lat_esc'" : 'NULL';
                $lng_val   = !empty($lng_esc) ? "'$lng_esc'" : 'NULL';

                $ok = mysqli_query($koneksi,
                    "INSERT INTO karyawan(id_card,nama,posisi,shift_id,lat,lng,radius_meter)
                     VALUES('$idc_esc','$nama_esc','$pos_esc',$shift_val,$lat_val,$lng_val,$radius)"
                );
                $pesan_ok = $ok
                    ? "sukses|Karyawan ditambahkan! ID Card: <strong>{$idc}</strong>"
                    : "gagal|Gagal menyimpan.";
            }
        } else {
            $pesan_ok = "gagal|Nama karyawan wajib diisi!";
        }
    }

    // --- Edit karyawan ---
    if (isset($_POST['aksi']) && $_POST['aksi'] === 'edit_karyawan') {
        $id       = intval($_POST['edit_id'] ?? 0);
        $nama     = trim($_POST['nama']      ?? '');
        $posisi   = trim($_POST['posisi']    ?? '');
        $shift    = intval($_POST['shift_id'] ?? 0);
        $lat      = trim($_POST['lat']       ?? '');
        $lng      = trim($_POST['lng']       ?? '');
        $radius   = intval($_POST['radius']  ?? 100);
        $new_kode = strtoupper(trim($_POST['new_kode'] ?? ''));

        if ($id > 0 && !empty($nama)) {
            $lat_esc  = mysqli_real_escape_string($koneksi, $lat);
            $lng_esc  = mysqli_real_escape_string($koneksi, $lng);
            $nama_esc = mysqli_real_escape_string($koneksi, $nama);
            $pos_esc  = mysqli_real_escape_string($koneksi, $posisi);
            $shift_val= ($shift > 0) ? intval($shift) : 'NULL';
            $lat_val  = !empty($lat_esc) ? "'$lat_esc'" : 'NULL';
            $lng_val  = !empty($lng_esc) ? "'$lng_esc'" : 'NULL';

            if (!empty($new_kode) && preg_match('/^[A-Z0-9]{4,8}$/', $new_kode)) {
                $cx = mysqli_prepare($koneksi, "SELECT id FROM karyawan WHERE id_card=? AND id!=?");
                mysqli_stmt_bind_param($cx, "si", $new_kode, $id);
                mysqli_stmt_execute($cx); mysqli_stmt_store_result($cx);
                $bentrok = mysqli_stmt_num_rows($cx) > 0; mysqli_stmt_close($cx);
                if ($bentrok) {
                    $pesan_ok = "gagal|Kode sudah dipakai.";
                } else {
                    $kode_esc = mysqli_real_escape_string($koneksi, $new_kode);
                    $ok = mysqli_query($koneksi,
                        "UPDATE karyawan SET id_card='$kode_esc',nama='$nama_esc',posisi='$pos_esc',
                         shift_id=$shift_val,lat=$lat_val,lng=$lng_val,radius_meter=$radius WHERE id=$id"
                    );
                    $pesan_ok = $ok ? "sukses|Data berhasil diubah." : "gagal|Gagal menyimpan.";
                }
            } else {
                $ok = mysqli_query($koneksi,
                    "UPDATE karyawan SET nama='$nama_esc',posisi='$pos_esc',
                     shift_id=$shift_val,lat=$lat_val,lng=$lng_val,radius_meter=$radius WHERE id=$id"
                );
                $pesan_ok = $ok ? "sukses|Data berhasil diubah." : "gagal|Gagal menyimpan.";
            }
        }
    }

    // --- Bulk delete karyawan ---
    if (isset($_POST['aksi']) && $_POST['aksi'] === 'bulk_hapus' && !empty($_POST['ids'])) {
        $ids = array_map('intval', $_POST['ids']);
        mysqli_query($koneksi, "DELETE FROM karyawan WHERE id IN(" . implode(',', $ids) . ")");
        $pesan_ok = "sukses|" . count($ids) . " karyawan berhasil dihapus.";
    }

    // --- Toggle aktif karyawan ---
    if (isset($_GET['toggle_karyawan'])) {
        mysqli_query($koneksi, "UPDATE karyawan SET aktif=IF(aktif=1,0,1) WHERE id=" . intval($_GET['toggle_karyawan']));
        header('Location: admin.php?menu=karyawan'); exit();
    }

    // --- Reset absensi + PIN karyawan ---
    if (isset($_GET['reset_absen'])) {
        $absen_id = intval($_GET['reset_absen']);
        $back_tgl = $_GET['tgl_back'] ?? date('Y-m-d');
        $back_fil = $_GET['fil_back'] ?? 'semua';

        $qa = mysqli_prepare($koneksi, "SELECT id_card FROM absensi WHERE id=? LIMIT 1");
        mysqli_stmt_bind_param($qa, "i", $absen_id);
        mysqli_stmt_execute($qa);
        $ra = mysqli_fetch_assoc(mysqli_stmt_get_result($qa));
        mysqli_stmt_close($qa);

        if ($ra && !empty($ra['id_card'])) {
            mysqli_query($koneksi, "DELETE FROM absensi WHERE id=$absen_id");
            $ic_esc = mysqli_real_escape_string($koneksi, $ra['id_card']);
            mysqli_query($koneksi, "UPDATE karyawan SET pin_aktif=NULL WHERE id_card='$ic_esc'");
        }
        header('Location: admin.php?menu=dashboard&tgl=' . $back_tgl . '&filter_status=' . $back_fil . '&pesan=reset-ok');
        exit();
    }

    // --- Tambah shift ---
    if (isset($_POST['aksi']) && $_POST['aksi'] === 'tambah_shift') {
        $ns  = trim($_POST['nama_shift'] ?? '');
        $jm  = $_POST['jam_masuk']  ?? '';
        $jp  = $_POST['jam_pulang'] ?? '';
        $tol = intval($_POST['toleransi'] ?? 15);
        if ($ns && $jm && $jp) {
            $st = mysqli_prepare($koneksi, "INSERT INTO shift(nama_shift,jam_masuk,jam_pulang,toleransi_menit)VALUES(?,?,?,?)");
            mysqli_stmt_bind_param($st, "sssi", $ns, $jm, $jp, $tol);
            mysqli_stmt_execute($st); mysqli_stmt_close($st);
            $pesan_ok = "sukses|Shift ditambahkan.";
        }
    }

    // --- Hapus shift ---
    if (isset($_GET['hapus_shift'])) {
        mysqli_query($koneksi, "DELETE FROM shift WHERE id=" . intval($_GET['hapus_shift']));
        header('Location: admin.php?menu=jamkerja'); exit();
    }
}

// ============================================================
//  DATA PER MENU
// ============================================================
$menu = $_GET['menu'] ?? 'dashboard';

if ($login) {
    // Dashboard
    $tgl_dash = isset($_GET['tgl']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['tgl'])
        ? $_GET['tgl'] : date('Y-m-d');
    $fil_stat = $_GET['filter_status'] ?? 'semua';

    $sql_d = "SELECT a.*,k.nama,k.posisi FROM absensi a LEFT JOIN karyawan k ON a.id_card=k.id_card WHERE a.tanggal=?";
    $bp    = [$tgl_dash];
    if ($fil_stat !== 'semua') { $sql_d .= " AND a.status=?"; $bp[] = $fil_stat; }
    $sql_d .= " ORDER BY a.id DESC";

    $qd = mysqli_prepare($koneksi, $sql_d);
    if (count($bp) === 1) mysqli_stmt_bind_param($qd, "s", $bp[0]);
    else mysqli_stmt_bind_param($qd, "ss", $bp[0], $bp[1]);
    mysqli_stmt_execute($qd); $data_dash = mysqli_stmt_get_result($qd);

    // Laporan
    $range   = $_GET['range'] ?? '0';
    $tgl_lap = isset($_GET['tgl_lap']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['tgl_lap']) ? $_GET['tgl_lap'] : '';
    if (!empty($tgl_lap)) {
        $tgl_mulai = $tgl_lap; $range = 'tgl';
        $ql = mysqli_prepare($koneksi, "SELECT a.*,k.nama FROM absensi a LEFT JOIN karyawan k ON a.id_card=k.id_card WHERE a.tanggal=? ORDER BY a.id DESC");
        mysqli_stmt_bind_param($ql, "s", $tgl_mulai); mysqli_stmt_execute($ql); $data_lap = mysqli_stmt_get_result($ql);
    } elseif ($range === '0' || !in_array($range, ['7','14','21','30'])) {
        $tgl_mulai = date('Y-m-d'); $range = '0';
        $ql = mysqli_prepare($koneksi, "SELECT a.*,k.nama FROM absensi a LEFT JOIN karyawan k ON a.id_card=k.id_card WHERE a.tanggal=? ORDER BY a.id DESC");
        mysqli_stmt_bind_param($ql, "s", $tgl_mulai); mysqli_stmt_execute($ql); $data_lap = mysqli_stmt_get_result($ql);
    } else {
        $tgl_mulai = date('Y-m-d', strtotime("-{$range} days"));
        $ql = mysqli_prepare($koneksi, "SELECT a.*,k.nama FROM absensi a LEFT JOIN karyawan k ON a.id_card=k.id_card WHERE a.tanggal>=? ORDER BY a.tanggal DESC,a.id DESC");
        mysqli_stmt_bind_param($ql, "s", $tgl_mulai); mysqli_stmt_execute($ql); $data_lap = mysqli_stmt_get_result($ql);
    }

    // Shift
    $data_shift = mysqli_query($koneksi, "SELECT * FROM shift ORDER BY jam_masuk ASC");
    $shifts_opt = mysqli_query($koneksi, "SELECT * FROM shift WHERE aktif=1");

    // Karyawan
    $cari = trim($_GET['cari'] ?? '');
    if ($cari) {
        $qk  = mysqli_prepare($koneksi, "SELECT k.*,s.nama_shift FROM karyawan k LEFT JOIN shift s ON k.shift_id=s.id WHERE k.nama LIKE ? OR k.id_card LIKE ? ORDER BY k.aktif DESC,k.id DESC");
        $lk  = "%{$cari}%"; mysqli_stmt_bind_param($qk, "ss", $lk, $lk);
    } else {
        $qk = mysqli_prepare($koneksi, "SELECT k.*,s.nama_shift FROM karyawan k LEFT JOIN shift s ON k.shift_id=s.id ORDER BY k.aktif DESC,k.id DESC");
    }
    mysqli_stmt_execute($qk); $data_kar = mysqli_stmt_get_result($qk);
    $jml_kar = mysqli_num_rows($data_kar); mysqli_data_seek($data_kar, 0);

    // Stats dashboard
    $r_hadir = mysqli_query($koneksi, "SELECT COUNT(*) as n FROM absensi WHERE tanggal='$tgl_dash' AND status='Hadir'")->fetch_assoc()['n'] ?? 0;
    $r_izin  = mysqli_query($koneksi, "SELECT COUNT(*) as n FROM absensi WHERE tanggal='$tgl_dash' AND status='Izin'")->fetch_assoc()['n']  ?? 0;
    $r_sakit = mysqli_query($koneksi, "SELECT COUNT(*) as n FROM absensi WHERE tanggal='$tgl_dash' AND status='Sakit'")->fetch_assoc()['n'] ?? 0;
    $r_total = mysqli_query($koneksi, "SELECT COUNT(*) as n FROM karyawan WHERE aktif=1")->fetch_assoc()['n'] ?? 0;

    // Data edit karyawan (buka modal)
    $edit_data = null;
    if (isset($_GET['edit_id'])) {
        $eid = intval($_GET['edit_id']);
        $eq  = mysqli_prepare($koneksi, "SELECT * FROM karyawan WHERE id=?");
        mysqli_stmt_bind_param($eq, "i", $eid); mysqli_stmt_execute($eq);
        $edit_data = mysqli_fetch_assoc(mysqli_stmt_get_result($eq)); mysqli_stmt_close($eq);
    }
}

// ============================================================
//  RENDER HTML
// ============================================================
include 'includes/header_admin.php';
?>

<?php if (!$login): ?>
<!-- ====== HALAMAN LOGIN ====== -->
<div style="max-width:320px;margin:60px auto;padding:0 14px;">
  <div class="kartu">
    <div style="text-align:center;margin-bottom:18px;">
      <div style="font-size:2rem;margin-bottom:5px;"></div>
      <h2 style="font-size:1.2rem;font-weight:800;">Admin Login</h2>
      <p style="color:var(--abu);font-size:.8rem;margin-top:2px;">Sistem Absensi</p>
    </div>
    <?php if ($err_login): ?>
    <div class="notif n-err"><i class="bi bi-x-circle"></i><?= htmlspecialchars($err_login) ?></div>
    <?php endif; ?>
    <form method="POST">
      <input type="hidden" name="aksi" value="login">
      <div style="margin-bottom:10px;">
        <label class="form-label">Password Admin</label>
        <input type="password" name="password" class="form-control" autofocus>
      </div>
      <button type="submit" class="btn btn-primer" style="width:100%;justify-content:center;padding:10px;">
        <i class="bi bi-box-arrow-in-right"></i> Masuk
      </button>
    </form>
  </div>
  <p style="text-align:center;margin-top:10px;">
    <a href="index.php" style="color:var(--abu);font-size:.78rem;text-decoration:none;">
      <i class="bi bi-arrow-left me-1"></i>Ke Halaman Absensi
    </a>
  </p>
</div>

<?php else: ?>
<!-- ====== ADMIN PANEL ====== -->
<div class="sb-wrap">

  <!-- Backdrop mobile -->
  <div class="sb-backdrop" id="sb-backdrop" onclick="tutupSidebar()"></div>

  <!-- SIDEBAR -->
  <nav class="sidebar" id="sidebar">
    <div class="sb-logo">
      <h2><i class="bi bi-building"></i> Absensi</h2>
      <p>Panel Admin</p>
    </div>
    <div class="sb-nav">
      <a href="?menu=dashboard" class="nav-item <?= $menu==='dashboard'?'aktif':'' ?>"><i class="bi bi-speedometer2"></i> Dashboard</a>
      <a href="?menu=laporan"   class="nav-item <?= $menu==='laporan'?'aktif':'' ?>"><i class="bi bi-bar-chart-line"></i> Laporan Absen</a>
      <a href="?menu=jamkerja"  class="nav-item <?= $menu==='jamkerja'?'aktif':'' ?>"><i class="bi bi-clock-history"></i> Jam Kerja / Shift</a>
      <a href="?menu=karyawan"  class="nav-item <?= $menu==='karyawan'?'aktif':'' ?>"><i class="bi bi-people"></i> Karyawan</a>
    </div>
    <div class="sb-foot">
      <a href="index.php"><i class="bi bi-house"></i> Halaman Absensi</a>
      <a href="?logout=1"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </div>
  </nav>

  <!-- MAIN CONTENT -->
  <div class="main" id="main">
    <div class="topbar">
      <div style="display:flex;align-items:center;gap:10px;">
        <button class="btn-toggle-sb" id="btn-toggle-sb" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
        <span class="topbar-judul"><?php
          $menu_names = ['dashboard'=>'Dashboard','laporan'=>'Laporan Absen','jamkerja'=>'Jam Kerja / Shift','karyawan'=>'Manajemen Karyawan'];
          echo $menu_names[$menu] ?? 'Admin';
        ?></span>
      </div>
      <span style="font-size:.7rem;color:var(--abu);white-space:nowrap;"><?= date('d M Y') ?></span>
    </div>

    <div class="content">

    <!-- Notifikasi global -->
    <?php if (!empty($pesan_ok)): [$tp,$isi] = explode('|', $pesan_ok, 2); ?>
      <div class="notif <?= $tp==='sukses'?'n-ok':'n-err' ?>">
        <i class="bi bi-<?= $tp==='sukses'?'check-circle':'x-circle' ?>"></i><span><?= $isi ?></span>
      </div>
    <?php endif; ?>
    <?php if (isset($_GET['pesan']) && $_GET['pesan'] === 'reset-ok'): ?>
      <div class="notif n-ok"><i class="bi bi-trash3"></i> Data absensi direset. PIN karyawan juga direset.</div>
    <?php endif; ?>

    <?php if ($menu === 'dashboard'): ?>
    <!-- ====== DASHBOARD ====== -->
    <?php include 'includes/admin/dashboard.php'; ?>

    <?php elseif ($menu === 'laporan'): ?>
    <!-- ====== LAPORAN ====== -->
    <?php include 'includes/admin/laporan.php'; ?>

    <?php elseif ($menu === 'jamkerja'): ?>
    <!-- ====== JAM KERJA ====== -->
    <?php include 'includes/admin/jamkerja.php'; ?>

    <?php elseif ($menu === 'karyawan'): ?>
    <!-- ====== KARYAWAN ====== -->
    <?php include 'includes/admin/karyawan.php'; ?>

    <?php endif; ?>

    </div><!-- /.content -->
  </div><!-- /.main -->
</div><!-- /.sb-wrap -->

<!-- LIGHTBOX FOTO -->
<div class="lightbox" id="lightbox" onclick="tutupFoto()">
  <button class="lightbox-close" onclick="tutupFoto()">x</button>
  <img id="lightbox-img" src="" alt="Foto absen">
  <div class="lightbox-info" id="lightbox-info"></div>
</div>

<!-- MODAL EDIT KARYAWAN -->
<?php if (!empty($edit_data)): ?>
<div class="modal-overlay aktif" id="modal-edit">
  <div class="modal-box">
    <h3>
      <span><i class="bi bi-pencil-square me-1"></i>Edit Karyawan</span>
      <button class="modal-close" onclick="tutupModal()">x</button>
    </h3>
    <?php include 'includes/admin/form_edit_karyawan.php'; ?>
  </div>
</div>
<?php endif; ?>

<?php endif; ?>

<?php include 'includes/footer_admin.php'; ?>
