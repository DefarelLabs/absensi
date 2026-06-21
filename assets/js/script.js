/**
 * assets/js/script.js
 * JavaScript untuk halaman index.php (Absensi Karyawan)
 * Variabel PHP diinject via tag <script> di includes/footer.php
 */

/* ============================================================
   JAM REAL-TIME
   ============================================================ */
function updateJam() {
  const n = new Date();
  const pad = x => String(x).padStart(2, '0');
  const elJam = document.getElementById('jam-live');
  const elTgl = document.getElementById('tgl-live');
  if (elJam) elJam.textContent = [n.getHours(), n.getMinutes(), n.getSeconds()].map(pad).join(':');
  if (elTgl) elTgl.textContent = n.toLocaleDateString('id-ID', {
    weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
  });
}
updateJam();
setInterval(updateJam, 1000);

/* ============================================================
   AUTO CEK ID CARD
   ============================================================ */
let timerId;
const inputId = document.getElementById('input-id-card');
if (inputId) {
  inputId.addEventListener('input', function () {
    clearTimeout(timerId);
    this.value = this.value.replace(/\D/g, '');
    if (this.value.length >= 4) {
      timerId = setTimeout(() => {
        window.location.href = 'index.php?id=ID' + encodeURIComponent(this.value);
      }, 700);
    }
  });
}

/* ============================================================
   QR BARCODE SCANNER (ZXing)
   ============================================================ */
let scannerAktif = false, codeReader = null, scannerStream = null;
const btnScanner  = document.getElementById('btn-toggle-scanner');
const scannerWrap = document.getElementById('scanner-wrap');
const videoScanner= document.getElementById('video-scanner');

if (btnScanner) {
  btnScanner.addEventListener('click', async function () {
    if (!scannerAktif) {
      try {
        if (typeof ZXing === 'undefined') { alert('Library scanner belum siap.'); return; }
        codeReader   = new ZXing.BrowserMultiFormatReader();
        scannerStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' }, audio: false });
        videoScanner.srcObject = scannerStream;
        scannerWrap.classList.add('aktif');
        btnScanner.classList.add('aktif');
        btnScanner.innerHTML = '<i class="bi bi-x-circle"></i> Tutup Scanner';
        scannerAktif = true;

        codeReader.decodeFromVideoDevice(null, 'video-scanner', (result) => {
          if (result) {
            const kode  = result.getText().toUpperCase().trim();
            const angka = kode.startsWith('ID') ? kode.substring(2) : kode.replace(/\D/g, '');
            if (angka.length >= 4) {
              tutupScanner();
              window.location.href = 'index.php?id=ID' + encodeURIComponent(angka);
            }
          }
        });
      } catch (e) {
        alert('Tidak bisa akses kamera scanner. Gunakan input manual.');
      }
    } else {
      tutupScanner();
    }
  });
}

function tutupScanner() {
  if (codeReader)     try { codeReader.reset(); } catch (e) {}
  if (scannerStream)  scannerStream.getTracks().forEach(t => t.stop());
  scannerWrap?.classList.remove('aktif');
  if (btnScanner) {
    btnScanner.classList.remove('aktif');
    btnScanner.innerHTML = '<i class="bi bi-qr-code-scan"></i> Scan QR Code';
  }
  scannerAktif = false; codeReader = null; scannerStream = null;
}

/* ============================================================
   GPS / GEOFENCING
   PHP akan menginjek: window.APP_CONFIG = { latKar, lngKar, radiusM, pakaiGeo }
   ============================================================ */
const cfg      = window.APP_CONFIG || {};
const latKar   = cfg.latKar   ?? null;
const lngKar   = cfg.lngKar   ?? null;
const radiusM  = cfg.radiusM  ?? 100;
const pakaiGeo = cfg.pakaiGeo ?? false;

let geoValid = !pakaiGeo;

const geoBox   = document.getElementById('geo-box');
const geoTeks  = document.getElementById('geo-teks');
const btnGeo   = document.getElementById('btn-izinkan-geo');
const latInput = document.getElementById('lat-user');
const lngInput = document.getElementById('lng-user');

function hitungJarak(la1, lo1, la2, lo2) {
  const R  = 6371000;
  const dL = (la2 - la1) * Math.PI / 180;
  const dO = (lo2 - lo1) * Math.PI / 180;
  const a  = Math.sin(dL / 2) ** 2
           + Math.cos(la1 * Math.PI / 180) * Math.cos(la2 * Math.PI / 180)
           * Math.sin(dO / 2) ** 2;
  return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
}

function prosesGPS(pos) {
  if (latInput) latInput.value = pos.coords.latitude;
  if (lngInput) lngInput.value = pos.coords.longitude;

  const jarak = hitungJarak(pos.coords.latitude, pos.coords.longitude, latKar, lngKar);
  if (jarak <= radiusM) {
    geoValid = true;
    if (geoBox) { geoBox.className = 'geo-box geo-ok'; geoTeks.textContent = 'Lokasi sesuai (' + Math.round(jarak) + 'm) OK'; }
  } else {
    geoValid = false;
    if (geoBox) { geoBox.className = 'geo-box geo-fail'; geoTeks.textContent = 'Lokasi tidak sesuai! ' + Math.round(jarak) + 'm, max ' + radiusM + 'm'; }
  }
  if (btnGeo) btnGeo.style.display = 'none';
}

function errorGPS() {
  geoValid = false;
  if (geoBox) { geoBox.className = 'geo-box geo-fail'; geoTeks.textContent = 'GPS tidak bisa diakses.'; }
  if (btnGeo) btnGeo.style.display = 'inline-flex';
}

function mintaGPS() {
  if (geoBox) { geoBox.className = 'geo-box geo-check'; geoTeks.textContent = 'Meminta izin GPS...'; }
  if (btnGeo) btnGeo.style.display = 'none';
  navigator.geolocation.getCurrentPosition(prosesGPS, errorGPS, { timeout: 12000, enableHighAccuracy: true });
}

// Form pulang: langsung minta GPS
const isHadirForm = document.getElementById('hadir');
if (geoBox && !isHadirForm) { mintaGPS(); }

/* ============================================================
   STATUS RADIO (Hadir / Izin / Sakit)
   ============================================================ */
const radioHadir = document.getElementById('hadir');
const radioIzin  = document.getElementById('izin');
const radioSakit = document.getElementById('sakit');

function updateUI() {
  if (!radioHadir) return;
  const s         = document.querySelector('input[name="status"]:checked')?.value || 'Hadir';
  const blokPin   = document.getElementById('blok-pin');
  const blokKet   = document.getElementById('blok-ket');
  const teksLbl   = document.getElementById('teks-label-foto');
  const teksLblKet= document.getElementById('teks-label-ket');
  const btn       = document.getElementById('btn-submit');
  const ikon      = document.getElementById('ikon-submit');
  const teks      = document.getElementById('teks-submit');
  const pinInput  = document.getElementById('pin-input');

  if (s === 'Hadir') {
    blokPin && (blokPin.style.display = '');
    blokKet && blokKet.classList.remove('tampil');
    teksLbl && (teksLbl.textContent = 'Foto Absensi');
    if (btn)  { btn.className = 'btn-masuk'; ikon.className = 'bi bi-box-arrow-in-right'; teks.textContent = 'Absen MASUK Sekarang'; }
    if (pinInput) pinInput.required = true;
    if (pakaiGeo && geoBox) {
      geoBox.style.display = 'flex';
      if (geoTeks.textContent === 'Memeriksa lokasi...' || geoTeks.textContent === 'Memuat...') mintaGPS();
    }
    geoValid = !pakaiGeo;
  } else {
    blokPin && (blokPin.style.display = 'none');
    blokKet && blokKet.classList.add('tampil');
    if (teksLbl)    teksLbl.textContent    = s === 'Izin' ? 'Foto Bukti Izin' : 'Foto Bukti Sakit';
    if (teksLblKet) teksLblKet.textContent = s === 'Izin' ? 'Keterangan Izin' : 'Keterangan Sakit';
    if (btn) { btn.className = 'btn-izin-sakit'; ikon.className = s === 'Izin' ? 'bi bi-file-earmark-text' : 'bi bi-thermometer-half'; teks.textContent = s === 'Izin' ? 'Kirim Izin' : 'Kirim Sakit'; }
    if (pinInput) { pinInput.required = false; pinInput.value = ''; }
    if (geoBox) geoBox.style.display = 'none';
    geoValid = true;
  }
}

[radioHadir, radioIzin, radioSakit].forEach(r => r?.addEventListener('change', updateUI));
updateUI();

/* ============================================================
   FACE DETECTION (face-api.js)
   ============================================================ */
let faceModelLoaded = false;
let faceDeteksi     = false;
const faceBadge     = document.getElementById('face-badge');

async function muatModelFace() {
  if (typeof faceapi === 'undefined') return;
  const urls = [
    'https://cdn.jsdelivr.net/npm/@vladmandic/face-api/model',
    'https://justadudewhohacks.github.io/face-api.js/models'
  ];
  for (const url of urls) {
    try {
      await faceapi.nets.tinyFaceDetector.loadFromUri(url);
      faceModelLoaded = true;
      if (faceBadge) { faceBadge.textContent = 'Arahkan wajah ke kamera'; faceBadge.className = 'face-badge face-no'; }
      return;
    } catch (e) { /* coba URL berikutnya */ }
  }
  // Semua URL gagal
  faceModelLoaded = false;
  if (faceBadge) { faceBadge.textContent = ''; faceBadge.style.display = 'none'; }
}
muatModelFace();

let faceInterval = null;

async function mulaiDeteksiFace(videoEl) {
  if (!faceModelLoaded || !videoEl) return;
  faceInterval = setInterval(async () => {
    if (!videoEl || videoEl.paused || videoEl.ended || videoEl.readyState < 2) return;
    try {
      const det = await faceapi.detectSingleFace(videoEl, new faceapi.TinyFaceDetectorOptions({ inputSize: 160, scoreThreshold: 0.35 }));
      faceDeteksi = !!det;
      if (faceBadge) {
        faceBadge.style.display = '';
        faceBadge.textContent   = faceDeteksi ? 'Wajah terdeteksi - siap foto' : 'Tidak ada wajah - arahkan wajah ke kamera';
        faceBadge.className     = 'face-badge ' + (faceDeteksi ? 'face-ok' : 'face-no');
      }
      updateBtnAmbil();
    } catch (e) {}
  }, 600);
}

function stopDeteksiFace() {
  if (faceInterval) { clearInterval(faceInterval); faceInterval = null; }
}

function updateBtnAmbil() {
  const btnA  = document.getElementById('btn-ambil-foto');
  const teksA = document.getElementById('teks-btn-ambil');
  if (!btnA) return;

  if (!faceModelLoaded || faceDeteksi) {
    btnA.disabled = false;
    if (teksA) teksA.textContent = 'Ambil Foto Sekarang';
  } else {
    btnA.disabled = true;
    if (teksA) teksA.textContent = 'Arahkan wajah ke kamera dulu...';
  }
}

/* ============================================================
   KAMERA FOTO (cross-browser: Chrome Android, Firefox, dll)
   ============================================================ */
let streamFoto = null, adaFoto = false;

const videoFoto    = document.getElementById('video-foto');
const kanvas       = document.getElementById('kanvas-foto');
const fotoHasilWrap= document.getElementById('foto-hasil-wrap');
const fotoHasil    = document.getElementById('foto-hasil');
const kameraWrap   = document.getElementById('kamera-wrap');
const btnAmbil     = document.getElementById('btn-ambil-foto');
const teksBtnAmbil = document.getElementById('teks-btn-ambil');
const btnRetake    = document.getElementById('btn-retake');
const fotoData     = document.getElementById('foto-kamera-data');
const hintWajib    = document.getElementById('hint-wajib');
const camOverlay   = document.getElementById('cam-overlay');

async function bukaKameraFoto() {
  if (!videoFoto) return;
  if (camOverlay) camOverlay.style.opacity = '0.5';

  // Dicoba berurutan dari ideal ke paling basic (fix cross-browser Android)
  const candidates = [
    { video: { facingMode: { ideal: 'user' }, width: { ideal: 640 }, height: { ideal: 480 } }, audio: false },
    { video: { facingMode: 'user' }, audio: false },
    { video: true, audio: false },
  ];

  let berhasil = false;
  for (const c of candidates) {
    try {
      streamFoto = await navigator.mediaDevices.getUserMedia(c);
      berhasil = true;
      break;
    } catch (e) {
      if (e.name === 'NotAllowedError' || e.name === 'PermissionDeniedError') break;
    }
  }

  if (berhasil && streamFoto) {
    videoFoto.srcObject = streamFoto;
    if (camOverlay) camOverlay.classList.add('hide');
    if (btnAmbil)   btnAmbil.disabled = true;
    if (teksBtnAmbil) teksBtnAmbil.textContent = 'Memuat deteksi wajah...';

    videoFoto.onloadedmetadata = async () => {
      await videoFoto.play().catch(() => {});
      updateBtnAmbil();
      mulaiDeteksiFace(videoFoto);
    };

    // Fallback: 4 detik kalau model face tidak dimuat
    setTimeout(() => {
      if (btnAmbil && btnAmbil.disabled && !faceModelLoaded) {
        btnAmbil.disabled = false;
        if (teksBtnAmbil) teksBtnAmbil.textContent = 'Ambil Foto Sekarang';
      }
    }, 4000);
  } else {
    if (camOverlay) { camOverlay.classList.remove('hide'); camOverlay.style.opacity = '1'; }
    if (teksBtnAmbil) teksBtnAmbil.textContent = 'Kamera tidak tersedia';
    if (btnAmbil)   btnAmbil.disabled = true;
    if (faceBadge)  faceBadge.style.display = 'none';
  }
}

// Auto buka kamera saat load
(async () => {
  if (!videoFoto) return;
  if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
    if (camOverlay) {
      camOverlay.innerHTML = '<i class="bi bi-exclamation-triangle" style="color:#fff;font-size:2rem;opacity:.7;"></i><p style="color:rgba(255,255,255,.8);font-size:.82rem;text-align:center;padding:0 16px;">Kamera memerlukan HTTPS.<br>Hubungi admin.</p>';
      camOverlay.classList.remove('hide');
    }
    return;
  }
  bukaKameraFoto();
})();

// Tombol ambil foto
btnAmbil?.addEventListener('click', () => {
  if (!videoFoto || !streamFoto || btnAmbil.disabled) return;

  kanvas.width  = videoFoto.videoWidth  || 640;
  kanvas.height = videoFoto.videoHeight || 480;
  const ctx = kanvas.getContext('2d');
  // Flip horizontal agar foto tersimpan tidak mirror
  ctx.translate(kanvas.width, 0);
  ctx.scale(-1, 1);
  ctx.drawImage(videoFoto, 0, 0);
  ctx.setTransform(1, 0, 0, 1, 0, 0);

  const dataUrl     = kanvas.toDataURL('image/jpeg', 0.85);
  fotoData.value    = dataUrl;
  fotoHasil.src     = dataUrl;
  fotoHasilWrap?.classList.add('tampil');
  if (kameraWrap) kameraWrap.style.display = 'none';
  if (btnAmbil)   btnAmbil.style.display   = 'none';
  hintWajib?.classList.remove('tampil');
  adaFoto = true;

  stopDeteksiFace();
  streamFoto.getTracks().forEach(t => t.stop());
  streamFoto = null;
});

// Tombol ulang foto
btnRetake?.addEventListener('click', () => {
  fotoData.value = '';
  fotoHasil.src  = '';
  fotoHasilWrap?.classList.remove('tampil');
  if (kameraWrap) kameraWrap.style.display = '';
  if (btnAmbil)   { btnAmbil.style.display = ''; btnAmbil.disabled = true; }
  if (teksBtnAmbil) teksBtnAmbil.textContent = 'Memuat kamera...';
  adaFoto = false;
  faceDeteksi = false;
  bukaKameraFoto();
});

/* ============================================================
   VALIDASI FORM SEBELUM SUBMIT
   ============================================================ */
document.getElementById('form-absen')?.addEventListener('submit', function (e) {
  let valid = true;
  const isPulang = document.querySelector('input[name="aksi"]')?.value === 'pulang';
  const status   = document.querySelector('input[name="status"]:checked')?.value || 'Hadir';
  const pinInput = document.getElementById('pin-input');
  const hintPin  = document.getElementById('hint-pin');

  // Cek GPS
  if (pakaiGeo && (status === 'Hadir' || isPulang) && !geoValid) {
    e.preventDefault();
    alert('Absen ditolak: Lokasi tidak sesuai lokasi kerja.\nPastikan GPS aktif dan kamu berada di lokasi yang benar.');
    return;
  }

  // Cek PIN
  if (pinInput && (isPulang || status === 'Hadir')) {
    const pin = pinInput.value.trim();
    if (pin.length < 4 || !/^\d+$/.test(pin)) {
      e.preventDefault();
      pinInput.classList.add('is-invalid');
      hintPin?.classList.add('tampil');
      if (valid) pinInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
      valid = false;
    }
  }

  // Cek foto
  if (!adaFoto) {
    e.preventDefault();
    hintWajib?.classList.add('tampil');
    if (valid) btnAmbil?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    valid = false;
  }

  // Cek keterangan (izin/sakit)
  const blokKet  = document.getElementById('blok-ket');
  const inputKet = document.getElementById('keterangan');
  const hintKet  = document.getElementById('hint-ket');
  if (blokKet?.classList.contains('tampil') && !inputKet?.value.trim()) {
    e.preventDefault();
    inputKet?.classList.add('is-invalid');
    hintKet?.classList.add('tampil');
    if (valid) inputKet?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    valid = false;
  }
});

// Bersihkan error saat mengetik
document.getElementById('pin-input')?.addEventListener('input', function () {
  this.value = this.value.replace(/\D/g, '');
  this.classList.remove('is-invalid');
  document.getElementById('hint-pin')?.classList.remove('tampil');
});
document.getElementById('keterangan')?.addEventListener('input', function () {
  this.classList.remove('is-invalid');
  document.getElementById('hint-ket')?.classList.remove('tampil');
});
