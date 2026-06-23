<?php /** includes/admin/laporan.php */ ?>
<div class="kartu">
  <div class="kartu-judul">
    <span><i class="bi bi-bar-chart-line me-1"></i>Rekap Absensi</span>
    <div style="display:flex;gap:5px;flex-wrap:wrap;">
      <button class="btn btn-export" onclick="exportCSV()" title="Export ke CSV">
        <i class="bi bi-filetype-csv"></i> CSV
      </button>
      <button class="btn btn-export" style="background:#1d6f42;" onclick="exportXLSX()" title="Export ke Excel">
        <i class="bi bi-file-earmark-excel"></i> XLSX
      </button>
      <button class="btn btn-export" style="background:#c0392b;" onclick="exportPDF()" title="Export ke PDF">
        <i class="bi bi-file-earmark-pdf"></i> PDF
      </button>
    </div>
  </div>

  <!-- Filter rentang + tanggal spesifik -->
  <div class="filter-bar" style="flex-wrap:wrap;gap:6px;">
    <?php foreach (['0'=>'Hari Ini','7'=>'1 Minggu','14'=>'2 Minggu','21'=>'3 Minggu','30'=>'1 Bulan'] as $v => $l): ?>
      <a href="?menu=laporan&range=<?= $v ?>"
        style="padding:5px 11px;border-radius:7px;font-size:.76rem;font-weight:700;text-decoration:none;border:1.5px solid var(--border);
               background:<?= ($range==$v&&$range!=='tgl')?'var(--biru)':'var(--card)' ?>;
               color:<?= ($range==$v&&$range!=='tgl')?'#fff':'var(--gelap)' ?>;"><?= $l ?></a>
    <?php endforeach; ?>
    <form method="GET" style="display:inline-flex;align-items:center;gap:5px;">
      <input type="hidden" name="menu" value="laporan">
      <input type="date" name="tgl_lap" value="<?= htmlspecialchars($tgl_lap) ?>" max="<?= date('Y-m-d') ?>"
        class="form-control input-tgl-laporan"
        class="form-control"
        style="max-width:148px;font-size:.78rem;padding:5px 8px;border-color:<?= $range==='tgl'?'var(--biru)':'var(--border)' ?>;background:<?= $range==='tgl'?'var(--biru-muda)':'var(--card)' ?>;"
        onchange="this.form.submit()" title="Pilih tanggal spesifik">
    </form>
  </div>

  <p style="font-size:.73rem;color:var(--abu);margin:0 0 10px;">
    <?php if ($range === 'tgl'): ?>Tanggal: <strong><?= date('d F Y', strtotime($tgl_lap)) ?></strong>
    <?php elseif ($range === '0'): ?>Tanggal: <strong><?= date('d F Y') ?> (Hari ini)</strong>
    <?php else: ?>Dari <strong><?= date('d M Y', strtotime($tgl_mulai)) ?></strong> s/d <strong><?= date('d M Y') ?></strong>
    <?php endif; ?>
  </p>

  <div class="tbl-wrap">
    <table class="tbl" id="tbl-laporan">
      <thead>
        <tr><th>Nama</th><th>Tanggal</th><th>Status</th><th>Masuk</th><th>Pulang</th><th>Total Jam</th><th>Terlambat</th><th>Keterangan</th></tr>
      </thead>
      <tbody>
      <?php $ada = false; while ($b = mysqli_fetch_assoc($data_lap)): $ada = true;
        $s_  = ($b['status'] ?? '');
        $kls = $s_==='Hadir'?'bs-hadir':($s_==='Izin'?'bs-izin':($s_==='Sakit'?'bs-sakit':($s_==='Alpa'?'bs-alpa':'')));
      ?>
        <tr>
          <td style="white-space:nowrap;"><?= htmlspecialchars($b['nama'] ?? $b['nama_karyawan'] ?? '') ?></td>
          <td style="font-size:.75rem;white-space:nowrap;"><?= date('d M Y', strtotime($b['tanggal'])) ?></td>
          <td><span class="bs <?= $kls ?>"><?= $s_ ?></span></td>
          <td style="font-family:'DM Mono',monospace;font-size:.75rem;"><?= $b['jam_masuk'] ? date('H:i', strtotime($b['jam_masuk'])) : '--' ?></td>
          <td style="font-family:'DM Mono',monospace;font-size:.75rem;"><?= $b['jam_pulang'] ? date('H:i', strtotime($b['jam_pulang'])) : ($s_ !== 'Hadir' ? 'N/A' : '--') ?></td>
          <td style="font-size:.75rem;"><?= $b['total_jam_kerja'] ?? '--' ?></td>
          <td style="font-size:.75rem;"><?= ($b['terlambat_menit'] ?? 0) > 0 ? $b['terlambat_menit'] . 'm' : '--' ?></td>
          <td style="font-size:.73rem;max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?= htmlspecialchars($b['keterangan'] ?? '') ?>"><?= htmlspecialchars($b['keterangan'] ?? '--') ?></td>
        </tr>
      <?php endwhile; if (!$ada): ?>
        <tr><td colspan="8" style="text-align:center;color:var(--abu);padding:20px;font-size:.8rem;">Tidak ada data</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
