/**
 * assets/js/admin.js
 * JavaScript untuk halaman admin.php
 */

/* ============================================================
   SIDEBAR TOGGLE
   ============================================================ */
const sidebar  = document.getElementById('sidebar');
const mainEl   = document.getElementById('main');
const backdrop = document.getElementById('sb-backdrop');

function toggleSidebar() {
  if (window.innerWidth < 768) {
    const buka = !sidebar.classList.contains('buka');
    if (buka) { sidebar.classList.add('buka'); backdrop?.classList.add('aktif'); }
    else tutupSidebar();
  } else {
    const tutup = !sidebar.classList.contains('tutup');
    sidebar.classList.toggle('tutup', tutup);
    mainEl?.classList.toggle('lebar', tutup);
  }
}

function tutupSidebar() {
  sidebar?.classList.remove('buka');
  backdrop?.classList.remove('aktif');
}

// Tutup sidebar saat klik nav item di mobile
document.querySelectorAll('.nav-item').forEach(a => {
  a.addEventListener('click', () => { if (window.innerWidth < 768) tutupSidebar(); });
});

/* ============================================================
   QR CODE 2D (qrcodejs)
   ============================================================ */
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.qr-wrap').forEach(wrap => {
    const idCard = wrap.id.replace('qr-', '');
    try {
      new QRCode(wrap, {
        text: idCard, width: 52, height: 52,
        colorDark: '#172b4d', colorLight: '#ffffff',
        correctLevel: QRCode.CorrectLevel.M
      });
    } catch (e) {}
  });

  // Init peta tambah karyawan
  if (document.getElementById('map-tambah')) initPetaTambah();

  // Init peta edit (kalau modal terbuka)
  if (document.getElementById('map-edit') && window.EDIT_LAT !== undefined) {
    initPetaEdit(window.EDIT_LAT, window.EDIT_LNG);
  }
});

/* ============================================================
   DOWNLOAD QR
   ============================================================ */
function downloadQR(idCard, nama) {
  const wrap   = document.getElementById('qr-' + idCard);
  const canvas = wrap?.querySelector('canvas');
  if (canvas) {
    const a    = document.createElement('a');
    a.download = 'QR-' + idCard + '-' + nama.replace(/\s+/g, '_') + '.png';
    a.href     = canvas.toDataURL('image/png');
    a.click();
  }
}

async function downloadSemuaQR() {
  const wraps = document.querySelectorAll('.qr-wrap');
  if (!wraps.length)              { alert('Tidak ada QR.'); return; }
  if (typeof JSZip === 'undefined') { alert('Library JSZip belum siap.'); return; }

  const zip = new JSZip(), promises = [];
  wraps.forEach(wrap => {
    const id     = wrap.id.replace('qr-', '');
    const canvas = wrap.querySelector('canvas');
    if (!canvas) return;
    promises.push(new Promise(res => {
      canvas.toBlob(blob => { if (blob) zip.file('QR-' + id + '.png', blob); res(); }, 'image/png');
    }));
  });
  await Promise.all(promises);
  const content = await zip.generateAsync({ type: 'blob' });
  const a = document.createElement('a');
  a.download = 'semua-qr.zip'; a.href = URL.createObjectURL(content); a.click();
}

/* ============================================================
   BULK PILIH KARYAWAN
   ============================================================ */
let modePilih = false;
var cariTimer;

function togglePilih() {
  modePilih = !modePilih;
  document.querySelectorAll('.chk-pilih').forEach(c => c.style.display = modePilih ? 'block' : 'none');
  document.getElementById('wrap-bulk').style.display = modePilih ? 'flex' : 'none';
  const btn = document.getElementById('btn-pilih');
  if (btn) btn.innerHTML = modePilih ? '<i class="bi bi-x-circle"></i> Batal' : '<i class="bi bi-check2-square"></i> Pilih';
  if (!modePilih) { document.querySelectorAll('.chk-pilih').forEach(c => c.checked = false); updateJml(); }
}

function updateJml() {
  const el = document.getElementById('jml-dipilih');
  if (el) el.textContent = document.querySelectorAll('.chk-pilih:checked').length;
}

function bulkHapus() {
  const n = document.querySelectorAll('.chk-pilih:checked').length;
  if (n === 0) { alert('Pilih minimal 1 karyawan.'); return; }
  if (confirm('Hapus ' + n + ' karyawan? Tidak bisa dibatalkan.')) {
    document.getElementById('form-bulk').submit();
  }
}

/* ============================================================
   KODE ID CARD - Auto prefix "ID"
   ============================================================ */
function syncKodeInput(el) {
  el.value = el.value.replace(/[^A-Za-z0-9]/g, '').toUpperCase();
  const hidden = document.getElementById('kode-custom-hidden');
  if (hidden) hidden.value = el.value ? 'ID' + el.value : '';
}

/* ============================================================
   EXPORT CSV
   ============================================================ */
function exportCSV() {
  const tbl = document.getElementById('tbl-laporan');
  if (!tbl) return;
  let csv = '';
  tbl.querySelectorAll('tr').forEach(tr => {
    const cols = [...tr.querySelectorAll('th,td')].map(c => '"' + c.textContent.trim().replace(/"/g, '""') + '"');
    csv += cols.join(',') + '\n';
  });
  const a = document.createElement('a');
  a.href     = URL.createObjectURL(new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' }));
  a.download = 'laporan-absensi-' + new Date().toISOString().slice(0, 10) + '.csv';
  a.click();
}

/* ============================================================
   LIGHTBOX FOTO
   ============================================================ */
function bukaFoto(src, info) {
  const lb  = document.getElementById('lightbox');
  const img = document.getElementById('lightbox-img');
  const inf = document.getElementById('lightbox-info');
  if (lb && img) { img.src = src; if (inf) inf.textContent = info || ''; lb.classList.add('aktif'); }
}
function tutupFoto() {
  document.getElementById('lightbox')?.classList.remove('aktif');
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') tutupFoto(); });

/* ============================================================
   MODAL EDIT KARYAWAN
   ============================================================ */
function tutupModal() {
  const m = document.getElementById('modal-edit');
  if (m) { m.classList.remove('aktif'); history.pushState({}, '', '?menu=karyawan'); }
}
document.getElementById('modal-edit')?.addEventListener('click', function (e) {
  if (e.target === this) tutupModal();
});

/* ============================================================
   PETA LEAFLET - HELPERS
   ============================================================ */
function setTitik(map, markerRef, lat, lng, labelKoor, inputLat, inputLng, labelTeks) {
  if (markerRef.current) map.removeLayer(markerRef.current);
  markerRef.current = L.marker([lat, lng]).addTo(map)
    .bindPopup(labelTeks || 'Titik kerja').openPopup();
  map.setView([lat, lng], map.getZoom() < 15 ? 16 : map.getZoom());
  if (inputLat) inputLat.value = lat.toFixed(8);
  if (inputLng) inputLng.value = lng.toFixed(8);
  if (labelKoor) labelKoor.textContent = 'Koordinat: ' + lat.toFixed(8) + ', ' + lng.toFixed(8);
}

async function cariNominatim(query) {
  try {
    const url = 'https://nominatim.openstreetmap.org/search?format=json&q=' + encodeURIComponent(query) + '&limit=6&accept-language=id';
    const res = await fetch(url, { headers: { 'Accept-Language': 'id' } });
    return await res.json();
  } catch (e) { return []; }
}

function tampilHasil(data, containerEl, onPilih) {
  if (!containerEl) return;
  containerEl.style.display = 'block';
  if (!data || data.length === 0) {
    containerEl.innerHTML = '<div style="padding:10px 12px;color:var(--abu);font-size:.8rem;">Lokasi tidak ditemukan.</div>';
    return;
  }
  containerEl.innerHTML = '';
  data.forEach(item => {
    const el = document.createElement('div');
    el.style.cssText = 'padding:9px 12px;cursor:pointer;border-bottom:1px solid var(--border);font-size:.8rem;display:flex;align-items:flex-start;gap:8px;';
    el.innerHTML = '<i class="bi bi-geo-alt-fill" style="color:var(--merah);margin-top:2px;flex-shrink:0;"></i><span>' + item.display_name + '</span>';
    el.addEventListener('mouseenter', () => el.style.background = 'var(--biru-muda)');
    el.addEventListener('mouseleave', () => el.style.background = '');
    el.addEventListener('click', () => { onPilih(parseFloat(item.lat), parseFloat(item.lon)); containerEl.style.display = 'none'; });
    containerEl.appendChild(el);
  });
}

// Tutup dropdown hasil pencarian saat klik luar
document.addEventListener('click', e => {
  ['#hasil-cari-tambah', '#hasil-cari-edit'].forEach(sel => {
    const el = document.querySelector(sel);
    if (el && !e.target.closest(sel) && !e.target.closest('[onclick*="cariLokasi"]') && !e.target.closest('[id*="search-"]')) {
      el.style.display = 'none';
    }
  });
});

/* ============================================================
   PETA TAMBAH KARYAWAN
   ============================================================ */
let mapTambah = null;
const markerTambah = { current: null };

function initPetaTambah() {
  const el = document.getElementById('map-tambah');
  if (!el || mapTambah) return;
  mapTambah = L.map('map-tambah').setView([-6.2, 106.8], 12);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap', maxZoom: 19 }).addTo(mapTambah);

  mapTambah.on('click', e => {
    setTitik(mapTambah, markerTambah, e.latlng.lat, e.latlng.lng,
      document.getElementById('koor-tambah'),
      document.getElementById('input-lat-tambah'),
      document.getElementById('input-lng-tambah'),
      'Titik kerja'
    );
    document.getElementById('hasil-cari-tambah').style.display = 'none';
  });

  document.getElementById('search-tambah')?.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); cariLokasiTambah(); } });
  gunakanGPSTambah(false);
}

function gunakanGPSTambah(setMarker) {
  const btn = document.getElementById('btn-gps-tambah');
  if (btn) btn.classList.add('loading');
  if (!navigator.geolocation) { alert('GPS tidak tersedia.'); if (btn) btn.classList.remove('loading'); return; }
  navigator.geolocation.getCurrentPosition(pos => {
    const { latitude: lat, longitude: lng } = pos.coords;
    if (btn) btn.classList.remove('loading');
    if (setMarker === false) { mapTambah.setView([lat, lng], 16); }
    else {
      setTitik(mapTambah, markerTambah, lat, lng,
        document.getElementById('koor-tambah'),
        document.getElementById('input-lat-tambah'),
        document.getElementById('input-lng-tambah'),
        'Lokasi GPS kamu'
      );
    }
  }, err => { if (btn) btn.classList.remove('loading'); alert('GPS error: ' + err.message); }, { timeout: 10000, enableHighAccuracy: true });
}

async function cariLokasiTambah() {
  const q = document.getElementById('search-tambah')?.value.trim();
  if (!q) return;
  const c = document.getElementById('hasil-cari-tambah');
  c.style.display = 'block';
  c.innerHTML = '<div style="padding:10px 12px;color:var(--abu);font-size:.8rem;"><i class="bi bi-hourglass-split me-1"></i>Mencari...</div>';
  const data = await cariNominatim(q);
  tampilHasil(data, c, (lat, lng) => {
    setTitik(mapTambah, markerTambah, lat, lng,
      document.getElementById('koor-tambah'),
      document.getElementById('input-lat-tambah'),
      document.getElementById('input-lng-tambah'),
      'Titik kerja'
    );
  });
}

function hapusKoorTambah() {
  if (markerTambah.current) mapTambah.removeLayer(markerTambah.current);
  markerTambah.current = null;
  document.getElementById('input-lat-tambah').value = '';
  document.getElementById('input-lng-tambah').value = '';
  document.getElementById('koor-tambah').textContent = 'Belum ada titik. Kosongkan = tidak pakai geofencing.';
  document.getElementById('hasil-cari-tambah').style.display = 'none';
}

/* ============================================================
   PETA EDIT KARYAWAN
   ============================================================ */
let mapEdit = null;
const markerEdit = { current: null };

function initPetaEdit(lat0, lng0) {
  const el = document.getElementById('map-edit');
  if (!el || mapEdit) return;

  lat0 = parseFloat(lat0) || 0;
  lng0 = parseFloat(lng0) || 0;

  const center = (lat0 && lng0) ? [lat0, lng0] : [-6.2, 106.8];
  const zoom   = (lat0 && lng0) ? 16 : 12;

  mapEdit = L.map('map-edit').setView(center, zoom);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap', maxZoom: 19 }).addTo(mapEdit);

  if (lat0 && lng0) {
    markerEdit.current = L.marker([lat0, lng0]).addTo(mapEdit).bindPopup('Titik kerja saat ini').openPopup();
  }

  mapEdit.on('click', e => {
    setTitik(mapEdit, markerEdit, e.latlng.lat, e.latlng.lng,
      document.getElementById('koor-edit'),
      document.getElementById('edit-lat'),
      document.getElementById('edit-lng'),
      'Titik kerja baru'
    );
    document.getElementById('hasil-cari-edit').style.display = 'none';
  });

  document.getElementById('search-edit')?.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); cariLokasiEdit(); } });
}

function gunakanGPSEdit() {
  const btn = document.getElementById('btn-gps-edit');
  if (btn) btn.classList.add('loading');
  if (!navigator.geolocation) { alert('GPS tidak tersedia.'); if (btn) btn.classList.remove('loading'); return; }
  navigator.geolocation.getCurrentPosition(pos => {
    const { latitude: lat, longitude: lng } = pos.coords;
    if (btn) btn.classList.remove('loading');
    setTitik(mapEdit, markerEdit, lat, lng,
      document.getElementById('koor-edit'),
      document.getElementById('edit-lat'),
      document.getElementById('edit-lng'),
      'Lokasi GPS kamu'
    );
  }, err => { if (btn) btn.classList.remove('loading'); alert('GPS error: ' + err.message); }, { timeout: 10000, enableHighAccuracy: true });
}

async function cariLokasiEdit() {
  const q = document.getElementById('search-edit')?.value.trim();
  if (!q) return;
  const c = document.getElementById('hasil-cari-edit');
  c.style.display = 'block';
  c.innerHTML = '<div style="padding:10px 12px;color:var(--abu);font-size:.8rem;"><i class="bi bi-hourglass-split me-1"></i>Mencari...</div>';
  const data = await cariNominatim(q);
  tampilHasil(data, c, (lat, lng) => {
    setTitik(mapEdit, markerEdit, lat, lng,
      document.getElementById('koor-edit'),
      document.getElementById('edit-lat'),
      document.getElementById('edit-lng'),
      'Titik kerja'
    );
  });
}

function hapusKoorEdit() {
  if (markerEdit.current) mapEdit.removeLayer(markerEdit.current);
  markerEdit.current = null;
  document.getElementById('edit-lat').value = '';
  document.getElementById('edit-lng').value = '';
  document.getElementById('koor-edit').textContent = 'Titik dihapus. Simpan untuk nonaktifkan geofencing.';
  document.getElementById('hasil-cari-edit').style.display = 'none';
}
