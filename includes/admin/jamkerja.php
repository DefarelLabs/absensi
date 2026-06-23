<?php /** includes/admin/jamkerja.php */ ?>
<!-- Form Tambah Shift -->
<div class="kartu">
  <div class="kartu-judul"><span><i class="bi bi-plus-circle me-1"></i>Tambah Shift</span></div>
  <form method="POST">
    <input type="hidden" name="aksi" value="tambah_shift">
    <div class="grid-shift-form">
      <div>
        <label class="form-label">Nama Shift *</label>
        <input type="text" name="nama_shift" class="form-control" placeholder="Shift Pagi" required>
      </div>
      <div>
        <label class="form-label">Toleransi (menit)</label>
        <input type="number" name="toleransi" class="form-control" value="15" min="0" max="120">
      </div>
      <div>
        <label class="form-label">Jam Masuk</label>
        <div class="time-input-wrap">
          <input type="time" name="jam_masuk" class="form-control time-input" required>
        </div>
      </div>
      <div>
        <label class="form-label">Jam Pulang</label>
        <div class="time-input-wrap">
          <input type="time" name="jam_pulang" class="form-control time-input" required>
        </div>
      </div>
    </div>
    <button type="submit" class="btn btn-primer"><i class="bi bi-plus"></i> Tambah Shift</button>
    <p style="font-size:.7rem;color:var(--abu);margin-top:7px;">
      <i class="bi bi-info-circle me-1"></i>Jika terlambat dan tidak mengganti waktu saat pulang = otomatis <strong>Alpa</strong>.
    </p>
  </form>
</div>

<!-- Daftar Shift -->
<div class="kartu">
  <div class="kartu-judul"><span><i class="bi bi-clock-history me-1"></i>Daftar Shift</span></div>
  <div class="tbl-wrap">
    <table class="tbl">
      <thead><tr><th>Nama</th><th>Masuk</th><th>Pulang</th><th>Toleransi</th><th>Aksi</th></tr></thead>
      <tbody>
      <?php while ($s = mysqli_fetch_assoc($data_shift)): ?>
        <tr>
          <td><strong><?= htmlspecialchars($s['nama_shift']) ?></strong></td>
          <td style="font-family:'DM Mono',monospace;"><?= date('H:i', strtotime($s['jam_masuk'])) ?></td>
          <td style="font-family:'DM Mono',monospace;"><?= date('H:i', strtotime($s['jam_pulang'])) ?></td>
          <td><?= $s['toleransi_menit'] ?> mnt</td>
          <td>
            <a href="?menu=jamkerja&hapus_shift=<?= $s['id'] ?>" class="btn btn-hapus"
              onclick="return confirm('Hapus shift ini?')"><i class="bi bi-trash3"></i></a>
          </td>
        </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>
