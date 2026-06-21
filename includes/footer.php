<?php
/**
 * includes/footer.php
 * Penutup halaman index.php
 * Menginjeksikan konfigurasi PHP ke JavaScript via window.APP_CONFIG
 */
?>

<!-- Inject konfigurasi PHP ke JavaScript -->
<script>
window.APP_CONFIG = {
  latKar:   <?= json_encode($karyawan['lat']   ?? null) ?>,
  lngKar:   <?= json_encode($karyawan['lng']   ?? null) ?>,
  radiusM:  <?= json_encode($karyawan['radius_meter'] ?? 100) ?>,
  pakaiGeo: <?= json_encode($pakai_geo ?? false) ?>
};
</script>

<!-- Library JS (urutan penting) -->
<!-- ZXing untuk QR scanner -->
<script src="https://cdn.jsdelivr.net/npm/@zxing/library@0.19.1/umd/index.min.js"></script>
<!-- face-api.js untuk deteksi wajah -->
<script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
<!-- App JS -->
<script src="assets/js/script.js"></script>

</body>
</html>
