<?php /** includes/admin/karyawan.php */ ?>
<!-- Form Tambah Karyawan -->
<div class="kartu">
  <div class="kartu-judul"><span><i class="bi bi-person-plus me-1"></i>Tambah Karyawan Baru</span></div>
  <form method="POST">
    <input type="hidden" name="aksi" value="tambah_karyawan">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:9px;margin-bottom:10px;">
      <div style="grid-column:1/-1;"><label class="form-label">Nama Lengkap *</label><input type="text" name="nama" class="form-control" placeholder="Nama karyawan" required></div>
      <div><label class="form-label">Jabatan</label><input type="text" name="posisi" class="form-control" placeholder="Kasir, Manager..."></div>
      <div>
        <label class="form-label">Kode ID Card</label>
        <div style="display:flex;align-items:center;border:1.5px solid var(--border);border-radius:7px;overflow:hidden;background:var(--card);" id="kode-wrap">
          <span style="padding:8px 10px;background:var(--bg-admin);color:var(--biru);font-family:'DM Mono',monospace;font-weight:800;font-size:.9rem;border-right:1.5px solid var(--border);flex-shrink:0;">ID</span>
          <input type="text" id="kode-input-visual" maxlength="6" placeholder="4-6 Digit" autocomplete="off"
            style="border:none;outline:none;padding:8px 10px;font-family:'DM Mono',monospace;font-size:.88rem;text-transform:uppercase;width:100%;background:transparent;"
            oninput="syncKodeInput(this)">
        </div>
        <input type="hidden" name="kode_custom" id="kode-custom-hidden">
        <small style="font-size:.68rem;color:var(--abu);">Kosongkan = auto. Isi angka saja, "ID" ditambah otomatis.</small>
      </div>
      <div>
        <label class="form-label">Shift</label>
        <select name="shift_id" class="form-select">
          <option value="">-- Tidak ada --</option>
          <?php if ($shifts_opt) while ($s = mysqli_fetch_assoc($shifts_opt)): ?>
            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['nama_shift']) ?> (<?= date('H:i', strtotime($s['jam_masuk'])) ?>-<?= date('H:i', strtotime($s['jam_pulang'])) ?>)</option>
          <?php endwhile; ?>
        </select>
      </div>
      <div><label class="form-label">Radius GPS (meter)</label><input type="number" name="radius" class="form-control" value="100" min="10" max="5000"><small style="font-size:.68rem;color:var(--abu);">Klik peta di bawah untuk set koordinat</small></div>
      <input type="hidden" name="lat" id="input-lat-tambah">
      <input type="hidden" name="lng" id="input-lng-tambah">
      <div style="grid-column:1/-1;">
        <label class="form-label"><i class="bi bi-map me-1"></i>Titik Lokasi Kerja (klik peta untuk set)</label>
        <div style="display:flex;gap:6px;margin-bottom:6px;">
          <input type="text" id="search-tambah" class="form-control" placeholder="Cari lokasi: contoh Masjid Al-Hidayah..." style="font-size:.82rem;">
          <button type="button" class="btn btn-primer" style="white-space:nowrap;padding:7px 12px;" onclick="cariLokasiTambah()"><i class="bi bi-search"></i></button>
          <button type="button" class="btn-gps-peta" id="btn-gps-tambah" onclick="gunakanGPSTambah(true)" title="Gunakan GPS saya"><i class="bi bi-crosshair"></i></button>
        </div>
        <div id="hasil-cari-tambah" style="display:none;background:var(--card);border:1px solid var(--border);border-radius:7px;max-height:160px;overflow-y:auto;margin-bottom:6px;box-shadow:0 4px 12px rgba(0,0,0,.1);position:relative;z-index:999;"></div>
        <div id="map-tambah" style="height:240px;border-radius:8px;border:1px solid var(--border);z-index:0;"></div>
        <p class="peta-koordinat" id="koor-tambah">Belum ada titik. Kosongkan = tidak pakai geofencing.</p>
        <button type="button" class="btn btn-hapus" style="margin-top:5px;font-size:.72rem;" onclick="hapusKoorTambah()"><i class="bi bi-x"></i> Hapus Titik</button>
      </div>
    </div>
    <button type="submit" class="btn btn-primer"><i class="bi bi-plus-circle"></i> Tambah & Generate ID Card</button>
  </form>
</div>

<!-- Daftar Karyawan -->
<div class="kartu">
  <div class="kartu-judul">
    <span><i class="bi bi-people me-1"></i>Daftar Karyawan</span>
    <span style="font-size:.73rem;font-weight:400;color:var(--abu);"><?= $jml_kar ?> terdaftar</span>
  </div>
  <div style="background:var(--kuning-muda);border:1px solid #ffb300;border-radius:7px;padding:7px 10px;margin-bottom:10px;font-size:.73rem;color:#974f0c;display:flex;align-items:center;gap:5px;">
    <i class="bi bi-shield-lock"></i> PIN ditampilkan setelah karyawan pertama kali absen masuk.
  </div>

  <!-- Toolbar -->
  <form method="GET" id="form-cari" style="display:flex;gap:7px;flex-wrap:wrap;margin-bottom:12px;align-items:center;">
    <input type="hidden" name="menu" value="karyawan">
    <input type="text" name="cari" value="<?= htmlspecialchars($cari) ?>" class="form-control" placeholder="Cari nama / kode..." style="max-width:190px;"
      oninput="clearTimeout(cariTimer);cariTimer=setTimeout(()=>this.form.submit(),500)">
    <button type="button" class="btn btn-edit" id="btn-pilih" onclick="togglePilih()"><i class="bi bi-check2-square"></i> Pilih</button>
    <span id="wrap-bulk" style="display:none;margin-left:16px;">
      <button type="button" class="btn btn-hapus" onclick="bulkHapus()">
        <i class="bi bi-trash3"></i> Hapus (<span id="jml-dipilih">0</span>)
      </button>
    </span>
    <span style="margin-left:auto;">
      <button type="button" class="btn btn-unduh" onclick="downloadSemuaQR()"><i class="bi bi-download"></i> Semua QR</button>
    </span>
  </form>

  <form id="form-bulk" method="POST"><input type="hidden" name="aksi" value="bulk_hapus">
  <?php if (mysqli_num_rows($data_kar) === 0): ?>
    <p style="text-align:center;color:var(--abu);padding:14px;font-size:.82rem;">Belum ada karyawan.</p>
  <?php else: while ($k = mysqli_fetch_assoc($data_kar)): ?>
  <div class="kar-item <?= $k['aktif'] ? '' : 'nonaktif' ?>">
    <input type="checkbox" name="ids[]" value="<?= $k['id'] ?>" class="kar-chk chk-pilih" style="display:none;" onchange="updateJml()">
    <div class="avatar-k"><?= strtoupper(substr($k['nama'], 0, 1)) ?></div>
    <div class="kar-info">
      <div class="kar-nama"><?= htmlspecialchars($k['nama']) ?>
        <?php if (!empty($k['pin_aktif'])): ?>
          <span class="badge-pin">PIN-<?= htmlspecialchars($k['pin_aktif']) ?></span>
        <?php else: ?>
          <span style="font-size:.63rem;color:var(--abu);font-style:italic;margin-left:2px;">Blm absen</span>
        <?php endif; ?>
      </div>
      <div class="kar-sub">
        <span class="badge-id-k"><?= htmlspecialchars($k['id_card']) ?></span>
        <?php if ($k['posisi']): ?><span><?= htmlspecialchars($k['posisi']) ?></span><?php endif; ?>
        <?php if ($k['nama_shift']): ?><span><i class="bi bi-clock" style="font-size:.62rem;"></i><?= htmlspecialchars($k['nama_shift']) ?></span><?php endif; ?>
        <?php if (!empty($k['lat'])): ?><span><i class="bi bi-geo-alt" style="font-size:.62rem;"></i>Geo ON</span><?php endif; ?>
        <span class="bs <?= $k['aktif'] ? 'bs-aktif' : 'bs-nonaktif' ?>"><?= $k['aktif'] ? 'Aktif' : 'Nonaktif' ?></span>
      </div>
    </div>
    <div class="qr-wrap" id="qr-<?= htmlspecialchars($k['id_card']) ?>"></div>
    <div class="kar-aksi">
      <button type="button" class="btn btn-unduh" style="padding:4px 7px;"
        onclick="downloadQR('<?= htmlspecialchars($k['id_card']) ?>','<?= addslashes(htmlspecialchars($k['nama'])) ?>')"
        title="Download QR"><i class="bi bi-download"></i></button>
      <a href="?menu=karyawan&edit_id=<?= $k['id'] ?>" class="btn btn-edit" style="padding:4px 7px;" title="Edit"><i class="bi bi-pencil"></i></a>
      <a href="?menu=karyawan&toggle_karyawan=<?= $k['id'] ?>" class="btn btn-toggle-k" style="padding:4px 7px;"
        onclick="return confirm('<?= $k['aktif'] ? 'Nonaktifkan' : 'Aktifkan' ?> karyawan ini?')">
        <i class="bi bi-<?= $k['aktif'] ? 'pause-circle' : 'play-circle' ?>"></i>
      </a>
    </div>
  </div>
  <?php endwhile; endif; ?>
  </form>
</div>
