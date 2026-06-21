<?php
/**
 * includes/partials/kamera_foto.php
 * Komponen kamera live yang dipakai di form masuk & pulang index.php
 * Dipanggil dengan include di dalam <div class="mb-3">
 */
?>
<div class="kamera-wrap" id="kamera-wrap">
  <video id="video-foto" autoplay playsinline muted></video>
  <div class="overlay-wajah">
    <div class="oval-guide"></div>
    <div class="face-status">
      <span id="face-badge" class="face-badge face-load">Memuat deteksi wajah...</span>
    </div>
  </div>
  <!-- Overlay izin kamera -->
  <div class="cam-overlay" id="cam-overlay">
    <i class="bi bi-camera-video-off" style="color:#fff;font-size:2.5rem;opacity:.6;"></i>
    <p>Izinkan akses kamera untuk mengambil foto absensi</p>
    <button type="button" class="btn-izinkan-cam" onclick="bukaKameraFoto()">
      <i class="bi bi-camera-fill"></i> Izinkan Kamera
    </button>
  </div>
</div>

<!-- Preview hasil foto -->
<div class="foto-hasil-wrap" id="foto-hasil-wrap">
  <img id="foto-hasil" src="" alt="Foto absen">
  <button type="button" class="btn-retake" id="btn-retake">
    <i class="bi bi-arrow-counterclockwise"></i> Ulang
  </button>
</div>

<!-- Tombol ambil foto -->
<button type="button" class="btn-ambil" id="btn-ambil-foto" disabled>
  <i class="bi bi-camera-fill"></i>
  <span id="teks-btn-ambil">Memuat kamera...</span>
</button>

<!-- Field tersembunyi menyimpan foto base64 -->
<input type="hidden" name="foto_kamera" id="foto-kamera-data">
<canvas id="kanvas-foto" style="display:none;"></canvas>

<p class="hint-wajib" id="hint-wajib">
  <i class="bi bi-exclamation-circle"></i>Foto wajib diambil!
</p>
