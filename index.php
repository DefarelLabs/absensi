<?php
/**
 * index.php
 * Halaman utama absensi karyawan (Kiosk Mode)
 * Hanya berisi logika PHP dan template HTML  CSS & JS dipisah
 */

require_once 'config/koneksi.php';

// ============================================================
//  VARIABEL HALAMAN
// ============================================================
$page_title = 'Absensi Karyawan';

$pesan   = $_GET['pesan'] ?? '';
$id_cek  = strtoupper(trim($_GET['id'] ?? ''));

$karyawan          = null;
$sudah_masuk       = false;
$sudah_selesai     = false;
$id_absen_hari_ini = null;
$info_masuk        = null;
$status_hari_ini   = null;

// ============================================================
//  AMBIL DATA KARYAWAN & STATUS ABSEN HARI INI
// ============================================================
if (!empty($id_cek)) {
    $q = mysqli_prepare($koneksi,
        "SELECT k.*, s.jam_masuk as shift_jam_masuk, s.jam_pulang as shift_jam_pulang,
                s.toleransi_menit, s.nama_shift
         FROM karyawan k
         LEFT JOIN shift s ON k.shift_id = s.id
         WHERE k.id_card = ? AND k.aktif = 1 LIMIT 1"
    );
    mysqli_stmt_bind_param($q, "s", $id_cek);
    mysqli_stmt_execute($q);
    $karyawan = mysqli_fetch_assoc(mysqli_stmt_get_result($q));
    mysqli_stmt_close($q);

    if ($karyawan) {
        $tanggal = date('Y-m-d');
        $q2 = mysqli_prepare($koneksi,
            "SELECT id, jam_masuk, jam_pulang, status
             FROM absensi WHERE id_card = ? AND tanggal = ?
             ORDER BY id DESC LIMIT 1"
        );
        mysqli_stmt_bind_param($q2, "ss", $id_cek, $tanggal);
        mysqli_stmt_execute($q2);
        $absen_hari = mysqli_fetch_assoc(mysqli_stmt_get_result($q2));
        mysqli_stmt_close($q2);

        if ($absen_hari) {
            $st = $absen_hari['status'];
            if (in_array($st, ['Izin','Sakit','Alpa']) || !is_null($absen_hari['jam_pulang'])) {
                $sudah_selesai   = true;
                $status_hari_ini = $st;
            } elseif ($st === 'Hadir' && is_null($absen_hari['jam_pulang'])) {
                $sudah_masuk       = true;
                $id_absen_hari_ini = $absen_hari['id'];
                $info_masuk        = $absen_hari['jam_masuk'];
                $status_hari_ini   = $st;
            }
        }
    }
}

// ============================================================
//  RIWAYAT ABSENSI (hanya tampil jika ID valid)
// ============================================================
$tanggal_filter = isset($_GET['tgl']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['tgl'])
    ? $_GET['tgl'] : date('Y-m-d');

$riwayat       = null;
$total_riwayat = 0;

if ($karyawan) {
    $r = mysqli_prepare($koneksi,
        "SELECT a.*, k.nama FROM absensi a
         LEFT JOIN karyawan k ON a.id_card = k.id_card
         WHERE a.id_card = ? AND a.tanggal = ?
         ORDER BY a.id DESC"
    );
    mysqli_stmt_bind_param($r, "ss", $id_cek, $tanggal_filter);
    mysqli_stmt_execute($r);
    $riwayat       = mysqli_stmt_get_result($r);
    $total_riwayat = mysqli_num_rows($riwayat);
}

// Apakah karyawan ini pakai geofencing?
$pakai_geo = $karyawan && !empty($karyawan['lat']) && !empty($karyawan['lng']);

// ============================================================
//  RENDER HTML
// ============================================================
include 'includes/header.php';
?>

<div style="padding: 16px 12px 48px;">

<!-- HEADER HALAMAN -->
<div class="page-header">
  <div class="badge-app"><i class="bi bi-building"></i> Sistem Absensi</div>
  <h1>Absensi Karyawan</h1>
  <p>Masukkan ID Card atau scan QR untuk mulai</p>
</div>

<!-- KARTU FORM ABSENSI -->
<div class="kartu">
  <div class="kartu-judul"><i class="bi bi-pencil-square"></i> Form Absensi</div>

  <!-- JAM REAL-TIME -->
  <div class="jam-display">
    <div>
      <div class="jam-besar" id="jam-live">--:--:--</div>
      <div class="tgl-kecil" id="tgl-live">Memuat...</div>
    </div>
    <div class="ikon-jam"><i class="bi bi-clock"></i></div>
  </div>

  <!-- NOTIFIKASI PESAN -->
  <?php if ($pesan === 'masuk-ok'):   ?><div class="notif n-masuk"><i class="bi bi-box-arrow-in-right"></i> Absen MASUK berhasil!</div>
  <?php elseif ($pesan === 'pulang-ok'):  ?><div class="notif n-pulang"><i class="bi bi-box-arrow-right"></i> Absen PULANG berhasil!</div>
  <?php elseif ($pesan === 'pulang-alpa'):?><div class="notif n-gagal"><i class="bi bi-exclamation-triangle"></i> Dicatat <strong>Alpa</strong> - tidak mengganti waktu terlambat.</div>
  <?php elseif ($pesan === 'izin-ok'):    ?><div class="notif n-izin"><i class="bi bi-check-circle"></i> Izin berhasil dicatat!</div>
  <?php elseif ($pesan === 'sakit-ok'):   ?><div class="notif n-izin"><i class="bi bi-check-circle"></i> Semoga lekas pulih!</div>
  <?php elseif ($pesan === 'pin-salah'):  ?><div class="notif n-gagal"><i class="bi bi-shield-x"></i> PIN salah!</div>
  <?php elseif ($pesan === 'no-foto'):    ?><div class="notif n-gagal"><i class="bi bi-camera"></i> Foto wajib diambil lewat kamera!</div>
  <?php elseif ($pesan === 'no-ket'):     ?><div class="notif n-gagal"><i class="bi bi-chat-left-text"></i> Keterangan wajib diisi!</div>
  <?php elseif ($pesan === 'id-invalid'): ?><div class="notif n-gagal"><i class="bi bi-person-x"></i> ID Card tidak ditemukan.</div>
  <?php elseif ($pesan === 'lokasi-gagal'):?><div class="notif n-gagal"><i class="bi bi-geo-alt"></i> Lokasi tidak sesuai lokasi kerja!</div>
  <?php elseif ($pesan === 'gagal'):      ?><div class="notif n-gagal"><i class="bi bi-exclamation-triangle"></i> Terjadi kesalahan, coba lagi.</div>
  <?php endif; ?>

  <!-- INPUT ID CARD + SCANNER -->
  <div class="mb-3">
    <label class="form-label"><i class="bi bi-credit-card me-1"></i>ID Card Karyawan</label>
    <div class="id-input-wrap">
      <span class="id-prefix">ID</span>
      <input type="text" id="input-id-card" maxlength="6" placeholder="xxxx"
        autocomplete="off" inputmode="numeric"
        value="<?= $karyawan
          ? substr($id_cek, 2)
          : (str_starts_with($id_cek, 'ID') ? substr($id_cek, 2) : '') ?>">
    </div>
    <button type="button" class="btn-scanner mt-2" id="btn-toggle-scanner">
      <i class="bi bi-qr-code-scan"></i> Scan QR Code
    </button>
    <div class="scanner-wrap" id="scanner-wrap">
      <video id="video-scanner" autoplay playsinline muted></video>
      <div class="scanner-overlay">
        <div class="scanner-garis"></div>
        <div style="color:rgba(255,255,255,.7);font-size:.72rem;margin-top:6px;">Arahkan QR ke kamera</div>
      </div>
    </div>
  </div>

  <!-- KARTU INFO KARYAWAN -->
  <div class="kartu-karyawan <?= $karyawan ? 'tampil' : '' ?>">
    <div class="badge-id-k"><?= htmlspecialchars($id_cek) ?></div>
    <p class="nama-besar"><?= htmlspecialchars($karyawan['nama'] ?? '') ?></p>
    <p class="posisi-kecil"><?= htmlspecialchars($karyawan['posisi'] ?? 'Karyawan') ?></p>
    <?php if ($karyawan && $karyawan['nama_shift']): ?>
    <p class="shift-info">
      <i class="bi bi-clock me-1"></i><?= htmlspecialchars($karyawan['nama_shift']) ?>
      (<?= date('H:i', strtotime($karyawan['shift_jam_masuk'] ?? '00:00')) ?>
       - <?= date('H:i', strtotime($karyawan['shift_jam_pulang'] ?? '00:00')) ?>)
    </p>
    <?php endif; ?>
    <i class="bi bi-person-badge-fill ikon-kartu"></i>
  </div>

  <?php if ($karyawan): ?>

    <?php if ($sudah_selesai): ?>
    <!-- SUDAH SELESAI HARI INI -->
    <div class="banner-selesai <?= strtolower($status_hari_ini) ?>">
      <i class="bi bi-check-circle-fill me-2" style="font-size:1.3rem;"></i><br>
      Absensi hari ini sudah <strong>selesai</strong>.<br>
      Status: <strong><?= htmlspecialchars($status_hari_ini) ?></strong><br>
      <small style="opacity:.7;margin-top:5px;display:block;">Sampai jumpa besok!</small>
    </div>

    <?php elseif ($sudah_masuk): ?>
    <!-- FORM PULANG -->
    <form action="actions/simpan.php" method="POST" enctype="multipart/form-data" id="form-absen">
      <input type="hidden" name="aksi"     value="pulang">
      <input type="hidden" name="id_card"  value="<?= htmlspecialchars($id_cek) ?>">
      <input type="hidden" name="id_absen" value="<?= $id_absen_hari_ini ?>">
      <input type="hidden" name="lat_user" id="lat-user">
      <input type="hidden" name="lng_user" id="lng-user">

      <div class="banner-masuk">
        <i class="bi bi-check-circle-fill" style="font-size:1.2rem;flex-shrink:0;"></i>
        <div>Sudah absen <strong>MASUK</strong> pukul
          <strong><?= date('H:i', strtotime($info_masuk)) ?></strong>.
          Ambil foto untuk absen pulang.
        </div>
      </div>

      <!-- GPS (hanya untuk Hadir) -->
      <?php if ($pakai_geo): ?>
      <div id="geo-box" class="geo-box geo-check">
        <i class="bi bi-geo-alt"></i>
        <span id="geo-teks">Memeriksa lokasi...</span>
        <button type="button" class="btn-izinkan-geo" id="btn-izinkan-geo"
          style="display:none;" onclick="mintaGPS()">
          <i class="bi bi-geo-alt-fill"></i> Izinkan GPS
        </button>
      </div>
      <?php endif; ?>

      <!-- INPUT PIN -->
      <div class="mb-3">
        <label class="form-label">
          <i class="bi bi-shield-check me-1"></i>Masukkan PIN
          <span style="color:var(--merah);font-weight:800;">*</span>
        </label>
        <input type="password" name="pin_absen" id="pin-input" class="form-control"
          maxlength="6" placeholder="PIN kamu"
          style="font-family:'DM Mono',monospace;font-size:1rem;letter-spacing:.3em;text-align:center;"
          autocomplete="off">
        <p class="hint-wajib" id="hint-pin"><i class="bi bi-exclamation-circle"></i>PIN wajib diisi!</p>
      </div>

      <!-- KAMERA FOTO PULANG -->
      <div class="mb-3">
        <label class="form-label">
          <i class="bi bi-camera me-1"></i>Foto Absensi Pulang
          <span style="color:var(--merah);font-weight:800;">*</span>
        </label>
        <?php include 'includes/partials/kamera_foto.php'; ?>
      </div>

      <div class="divider"></div>
      <button type="submit" class="btn-pulang" id="btn-submit">
        <i class="bi bi-box-arrow-right"></i> Absen PULANG Sekarang
      </button>
    </form>

    <?php else: ?>
    <!-- FORM MASUK -->
    <form action="actions/simpan.php" method="POST" enctype="multipart/form-data" id="form-absen">
      <input type="hidden" name="aksi"    value="masuk">
      <input type="hidden" name="id_card" value="<?= htmlspecialchars($id_cek) ?>">
      <input type="hidden" name="lat_user" id="lat-user">
      <input type="hidden" name="lng_user" id="lng-user">

      <!-- STATUS KEHADIRAN -->
      <div class="mb-3">
        <label class="form-label"><i class="bi bi-ui-checks me-1"></i>Status Kehadiran</label>
        <div class="status-wrap">
          <input type="radio" id="hadir" name="status" value="Hadir" checked>
          <label for="hadir"><i class="bi bi-check2-circle me-1"></i>Hadir</label>
          <input type="radio" id="izin"  name="status" value="Izin">
          <label for="izin"><i class="bi bi-file-earmark-text me-1"></i>Izin</label>
          <input type="radio" id="sakit" name="status" value="Sakit">
          <label for="sakit"><i class="bi bi-thermometer-half me-1"></i>Sakit</label>
        </div>
      </div>

      <!-- GPS (hanya Hadir + ada geofencing) -->
      <?php if ($pakai_geo): ?>
      <div id="geo-box" class="geo-box geo-check" style="display:none;">
        <i class="bi bi-geo-alt"></i>
        <span id="geo-teks">Memeriksa lokasi...</span>
        <button type="button" class="btn-izinkan-geo" id="btn-izinkan-geo"
          style="display:none;" onclick="mintaGPS()">
          <i class="bi bi-geo-alt-fill"></i> Izinkan GPS
        </button>
      </div>
      <?php endif; ?>

      <!-- PIN MASUK -->
      <div class="mb-3" id="blok-pin">
        <label class="form-label">
          <i class="bi bi-shield-lock me-1"></i>Buat PIN
          <span style="color:var(--merah);font-weight:800;">*</span>
          <span style="color:var(--abu);font-weight:400;font-size:.75rem;">(4-6 digit angka)</span>
        </label>
        <input type="password" name="pin_absen" id="pin-input" class="form-control"
          maxlength="6" placeholder="Buat PIN 4-6 digit"
          style="font-family:'DM Mono',monospace;font-size:1rem;letter-spacing:.3em;text-align:center;"
          autocomplete="new-password">
        <p style="font-size:.72rem;color:var(--abu);margin-top:4px;">
          <i class="bi bi-info-circle me-1"></i>Ingat PIN ini - wajib diinput saat absen pulang.
        </p>
        <p class="hint-wajib" id="hint-pin"><i class="bi bi-exclamation-circle"></i>PIN minimal 4 digit angka!</p>
      </div>

      <!-- KAMERA FOTO -->
      <div class="mb-3">
        <label class="form-label">
          <i class="bi bi-camera me-1"></i><span id="teks-label-foto">Foto Absensi</span>
          <span style="color:var(--merah);font-weight:800;">*</span>
        </label>
        <?php include 'includes/partials/kamera_foto.php'; ?>
      </div>

      <!-- KETERANGAN (Izin/Sakit) -->
      <div class="mb-3 blok-ket" id="blok-ket">
        <label class="form-label">
          <i class="bi bi-chat-left-text me-1"></i>
          <span id="teks-label-ket">Keterangan</span>
          <span style="color:var(--merah);font-weight:800;">*</span>
        </label>
        <textarea name="keterangan" id="keterangan" class="form-control"
          rows="3" placeholder="Tulis keterangan di sini..."></textarea>
        <p class="hint-wajib" id="hint-ket"><i class="bi bi-exclamation-circle"></i>Keterangan wajib diisi!</p>
      </div>

      <div class="divider"></div>
      <button type="submit" class="btn-masuk" id="btn-submit">
        <i class="bi bi-box-arrow-in-right" id="ikon-submit"></i>
        <span id="teks-submit">Absen MASUK Sekarang</span>
      </button>
    </form>
    <?php endif; ?>

  <?php elseif (!empty($id_cek)): ?>
  <p style="text-align:center;color:var(--abu);font-size:.85rem;margin-top:4px;">
    <i class="bi bi-info-circle me-1"></i>ID tidak ditemukan. Hubungi admin.
  </p>
  <?php endif; ?>
</div><!-- /.kartu -->

<!-- RIWAYAT (tersembunyi sampai ID valid) -->
<div id="seksi-riwayat" class="<?= $karyawan ? 'tampil' : '' ?>">
  <div class="kartu kartu-tabel">
    <div class="kartu-judul" style="flex-wrap:wrap;gap:8px;">
      <span><i class="bi bi-clock-history"></i> Riwayat Saya</span>
      <form method="GET" style="display:flex;align-items:center;gap:6px;margin-left:auto;">
        <input type="hidden" name="id" value="<?= htmlspecialchars($id_cek) ?>">
        <input type="date" name="tgl" value="<?= htmlspecialchars($tanggal_filter) ?>"
          max="<?= date('Y-m-d') ?>"
          style="border:1.5px solid var(--border);border-radius:7px;padding:4px 9px;font-size:.78rem;color:var(--gelap);"
          onchange="this.form.submit()">
        <?php if ($tanggal_filter !== date('Y-m-d')): ?>
        <a href="index.php?id=<?= urlencode($id_cek) ?>"
          style="font-size:.75rem;color:var(--biru);text-decoration:none;">
          <i class="bi bi-arrow-counterclockwise"></i>
        </a>
        <?php endif; ?>
      </form>
    </div>
    <p style="font-size:.75rem;color:var(--abu);margin:0 0 12px;">
      <strong style="color:var(--gelap);">
        <?= date('d F Y', strtotime($tanggal_filter)) ?>
        <?= $tanggal_filter === date('Y-m-d') ? ' (Hari ini)' : '' ?>
      </strong>
    </p>
    <div style="overflow-x:auto;">
      <table class="tbl">
        <thead>
          <tr><th>Nama</th><th>Status</th><th>Masuk</th><th>Pulang</th><th>Jam</th></tr>
        </thead>
        <tbody>
        <?php if ($riwayat): while ($b = mysqli_fetch_assoc($riwayat)):
          $s_  = ($b['status'] ?? '');
          $kls = $s_ === 'Hadir' ? 'bs-hadir' : ($s_ === 'Izin' ? 'bs-izin' : ($s_ === 'Sakit' ? 'bs-sakit' : ($s_ === 'Alpa' ? 'bs-alpa' : '')));
        ?>
          <tr>
            <td style="white-space:nowrap;"><strong><?= htmlspecialchars($b['nama'] ?? $b['nama_karyawan'] ?? '') ?></strong></td>
            <td>
              <span class="bs <?= $kls ?>"><?= htmlspecialchars($s_) ?></span>
              <?php if (($b['terlambat_menit'] ?? 0) > 0): ?>
              <span class="bs bs-terlambat">+<?= $b['terlambat_menit'] ?>m</span>
              <?php endif; ?>
            </td>
            <td style="font-family:'DM Mono',monospace;font-size:.78rem;">
              <?= $b['jam_masuk'] ? date('H:i', strtotime($b['jam_masuk'])) : '--' ?>
            </td>
            <td style="font-family:'DM Mono',monospace;font-size:.78rem;">
              <?php
              if ($b['jam_pulang'])                            echo date('H:i', strtotime($b['jam_pulang']));
              elseif (in_array($s_, ['Izin','Sakit','Alpa'])) echo '<span class="badge-belum">N/A</span>';
              else                                             echo '<span class="badge-belum">Belum</span>';
              ?>
            </td>
            <td>
              <?= $b['total_jam_kerja']
                ? '<span class="badge-jam">' . htmlspecialchars($b['total_jam_kerja']) . '</span>'
                : '<span class="badge-belum">--</span>' ?>
            </td>
          </tr>
        <?php endwhile; endif; ?>
        <?php if (!$riwayat || $total_riwayat === 0): ?>
          <tr>
            <td colspan="5" style="text-align:center;color:var(--abu);padding:20px;font-size:.82rem;">
              Tidak ada data untuk tanggal ini
            </td>
          </tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div><!-- /#seksi-riwayat -->

<div class="link-admin"><a href="admin.php"><i class="bi bi-gear"></i> Halaman Admin</a></div>

</div><!-- /padding wrapper -->

<?php include 'includes/footer.php'; ?>
