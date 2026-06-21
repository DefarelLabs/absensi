<?php
/**
 * includes/admin/dashboard.php
 * Konten menu Dashboard admin
 * Variabel tersedia dari admin.php: $r_hadir, $r_izin, $r_sakit, $r_total,
 *   $data_dash, $tgl_dash, $fil_stat
 */
?>
<!-- Stats Cards -->
<div class="stats-grid">
  <div class="stat-card stat-hadir"><div class="angka"><?= $r_hadir ?></div><div class="label">Hadir</div></div>
  <div class="stat-card stat-izin"> <div class="angka"><?= $r_izin  ?></div><div class="label">Izin</div></div>
  <div class="stat-card stat-sakit"><div class="angka"><?= $r_sakit ?></div><div class="label">Sakit</div></div>
  <div class="stat-card stat-total"><div class="angka"><?= $r_total ?></div><div class="label">Karyawan</div></div>
</div>

<!-- Tabel Riwayat -->
<div class="kartu">
  <div class="kartu-judul"><span><i class="bi bi-table me-1"></i>Riwayat Absensi + Foto</span></div>

  <!-- Filter -->
  <form method="GET" class="filter-bar">
    <input type="hidden" name="menu" value="dashboard">
    <input type="date" name="tgl" value="<?= $tgl_dash ?>" max="<?= date('Y-m-d') ?>"
      class="form-control" style="max-width:150px;" onchange="this.form.submit()">
    <select name="filter_status" class="form-select" style="max-width:140px;" onchange="this.form.submit()">
      <option value="semua" <?= $fil_stat==='semua'?'selected':'' ?>>Semua Status</option>
      <option value="Hadir" <?= $fil_stat==='Hadir'?'selected':'' ?>>Hadir</option>
      <option value="Izin"  <?= $fil_stat==='Izin'?'selected':'' ?>>Izin</option>
      <option value="Sakit" <?= $fil_stat==='Sakit'?'selected':'' ?>>Sakit</option>
      <option value="Alpa"  <?= $fil_stat==='Alpa'?'selected':'' ?>>Alpa</option>
    </select>
    <?php if ($tgl_dash !== date('Y-m-d')): ?>
      <a href="?menu=dashboard" style="font-size:.75rem;color:var(--biru);text-decoration:none;">
        <i class="bi bi-arrow-counterclockwise"></i>
      </a>
    <?php endif; ?>
  </form>
  <p style="font-size:.73rem;color:var(--abu);margin:0 0 10px;">
    Tanggal: <strong style="color:var(--gelap);"><?= date('d F Y', strtotime($tgl_dash)) ?></strong>
  </p>

  <div class="tbl-wrap">
    <table class="tbl">
      <thead>
        <tr>
          <th>Nama</th><th>Status</th><th>Masuk</th><th>Foto Masuk</th>
          <th>Pulang</th><th>Foto Pulang</th><th>Total</th><th>Terlambat</th>
          <th>Keterangan</th><th>Aksi</th>
        </tr>
      </thead>
      <tbody>
      <?php $ada = false; while ($b = mysqli_fetch_assoc($data_dash)): $ada = true;
        $s_          = ($b['status'] ?? '');
        $kls         = $s_==='Hadir'?'bs-hadir':($s_==='Izin'?'bs-izin':($s_==='Sakit'?'bs-sakit':($s_==='Alpa'?'bs-alpa':'')));
        $folder_masuk= in_array($s_, ['Izin','Sakit']) ? 'uploads-bukti' : 'uploads';
      ?>
        <tr>
          <td style="white-space:nowrap;font-weight:700;"><?= htmlspecialchars($b['nama'] ?? $b['nama_karyawan'] ?? '') ?></td>
          <td><span class="bs <?= $kls ?>"><?= $s_ ?></span></td>
          <td style="font-family:'DM Mono',monospace;font-size:.75rem;"><?= $b['jam_masuk'] ? date('H:i', strtotime($b['jam_masuk'])) : '--' ?></td>
          <td><?php if (!empty($b['foto_masuk'])): ?>
            <img src="<?= $folder_masuk ?>/<?= htmlspecialchars($b['foto_masuk']) ?>" class="thumb-foto"
              onclick="bukaFoto('<?= $folder_masuk ?>/<?= htmlspecialchars($b['foto_masuk']) ?>','<?= htmlspecialchars(addslashes($b['nama'] ?? '')) ?> - Masuk')"
              title="Klik perbesar">
          <?php else: ?><span class="no-foto">--</span><?php endif; ?></td>
          <td style="font-family:'DM Mono',monospace;font-size:.75rem;"><?php
            if ($b['jam_pulang'])                             echo date('H:i', strtotime($b['jam_pulang']));
            elseif (in_array($s_, ['Izin','Sakit','Alpa']))  echo 'N/A';
            else                                              echo '<em style="color:var(--abu)">Belum</em>';
          ?></td>
          <td><?php if (!empty($b['foto_pulang'])): ?>
            <img src="uploads/<?= htmlspecialchars($b['foto_pulang']) ?>" class="thumb-foto"
              onclick="bukaFoto('uploads/<?= htmlspecialchars($b['foto_pulang']) ?>','<?= htmlspecialchars(addslashes($b['nama'] ?? '')) ?> - Pulang')"
              title="Klik perbesar">
          <?php else: ?><span class="no-foto">--</span><?php endif; ?></td>
          <td style="font-size:.75rem;"><?= $b['total_jam_kerja'] ?? '--' ?></td>
          <td style="font-size:.75rem;"><?= ($b['terlambat_menit'] ?? 0) > 0 ? '<span style="color:var(--kuning);font-weight:700;">' . $b['terlambat_menit'] . 'm</span>' : '--' ?></td>
          <td style="font-size:.73rem;max-width:110px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
            title="<?= htmlspecialchars($b['keterangan'] ?? '') ?>">
            <?= !empty($b['keterangan']) ? htmlspecialchars($b['keterangan']) : '--' ?>
          </td>
          <td>
            <a href="?menu=dashboard&reset_absen=<?= $b['id'] ?>&tgl_back=<?= urlencode($tgl_dash) ?>&fil_back=<?= urlencode($fil_stat) ?>"
              class="btn btn-hapus" style="padding:3px 8px;font-size:.7rem;"
              onclick="return confirm('Reset absensi <?= htmlspecialchars(addslashes($b['nama'] ?? '')) ?>?\nData absensi akan dihapus dan PIN karyawan akan direset.')"
              title="Reset data absensi">
              <i class="bi bi-arrow-counterclockwise"></i> Reset
            </a>
          </td>
        </tr>
      <?php endwhile; if (!$ada): ?>
        <tr><td colspan="10" style="text-align:center;color:var(--abu);padding:20px;font-size:.8rem;">Tidak ada data</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
