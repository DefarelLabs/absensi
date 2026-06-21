<?php /** includes/admin/form_edit_karyawan.php - form modal edit karyawan */ ?>
<form method="POST">
  <input type="hidden" name="aksi"    value="edit_karyawan">
  <input type="hidden" name="edit_id" value="<?= $edit_data['id'] ?>">
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:9px;margin-bottom:14px;">

    <div style="grid-column:1/-1;">
      <label class="form-label">Nama Lengkap *</label>
      <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($edit_data['nama']) ?>" required>
    </div>

    <div>
      <label class="form-label">Jabatan</label>
      <input type="text" name="posisi" class="form-control" value="<?= htmlspecialchars($edit_data['posisi'] ?? '') ?>">
    </div>

    <div>
      <label class="form-label">Kode ID Baru</label>
      <input type="text" name="new_kode" class="form-control"
        placeholder="Kosong = tidak ubah" maxlength="8"
        style="font-family:'DM Mono',monospace;text-transform:uppercase;"
        oninput="this.value=this.value.toUpperCase()">
      <small style="font-size:.67rem;color:var(--abu);">Saat ini: <strong><?= htmlspecialchars($edit_data['id_card']) ?></strong></small>
    </div>

    <div>
      <label class="form-label">Shift</label>
      <select name="shift_id" class="form-select">
        <option value="">-- Tidak ada --</option>
        <?php $so = mysqli_query($koneksi, "SELECT * FROM shift WHERE aktif=1");
        while ($s = mysqli_fetch_assoc($so)): ?>
          <option value="<?= $s['id'] ?>" <?= $edit_data['shift_id'] == $s['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($s['nama_shift']) ?>
          </option>
        <?php endwhile; ?>
      </select>
    </div>

    <div>
      <label class="form-label">Radius GPS (meter)</label>
      <input type="number" name="radius" class="form-control"
        value="<?= htmlspecialchars($edit_data['radius_meter'] ?? 100) ?>" min="10" max="5000">
    </div>

    <!-- Hidden inputs lat/lng dari peta -->
    <input type="hidden" name="lat" id="edit-lat" value="<?= htmlspecialchars($edit_data['lat'] ?? '') ?>">
    <input type="hidden" name="lng" id="edit-lng" value="<?= htmlspecialchars($edit_data['lng'] ?? '') ?>">

    <div style="grid-column:1/-1;">
      <label class="form-label"><i class="bi bi-map me-1"></i>Titik Lokasi Kerja (klik peta untuk ubah)</label>
      <div style="display:flex;gap:6px;margin-bottom:6px;">
        <input type="text" id="search-edit" class="form-control" placeholder="Cari lokasi..." style="font-size:.82rem;">
        <button type="button" class="btn btn-primer" style="white-space:nowrap;padding:7px 12px;" onclick="cariLokasiEdit()"><i class="bi bi-search"></i></button>
        <button type="button" class="btn-gps-peta" id="btn-gps-edit" onclick="gunakanGPSEdit()" title="Gunakan GPS saya"><i class="bi bi-crosshair"></i></button>
      </div>
      <div id="hasil-cari-edit" style="display:none;background:var(--card);border:1px solid var(--border);border-radius:7px;max-height:160px;overflow-y:auto;margin-bottom:6px;box-shadow:0 4px 12px rgba(0,0,0,.1);position:relative;z-index:999;"></div>
      <div id="map-edit" style="height:200px;border-radius:8px;border:1px solid var(--border);z-index:0;"></div>
      <p class="peta-koordinat" id="koor-edit">
        <?php if (!empty($edit_data['lat']) && !empty($edit_data['lng'])): ?>
          Koordinat: <?= htmlspecialchars($edit_data['lat']) ?>, <?= htmlspecialchars($edit_data['lng']) ?>
        <?php else: ?>
          Belum ada titik. Klik peta untuk set.
        <?php endif; ?>
      </p>
      <button type="button" class="btn btn-hapus" style="margin-top:5px;font-size:.72rem;" onclick="hapusKoorEdit()"><i class="bi bi-x"></i> Hapus Titik</button>
    </div>

  </div>
  <div style="display:flex;gap:7px;">
    <button type="submit" class="btn btn-primer"><i class="bi bi-check-lg"></i> Simpan</button>
    <button type="button" class="btn" style="background:var(--bg-admin);color:var(--gelap);border:1px solid var(--border);" onclick="tutupModal()">Batal</button>
  </div>
</form>
