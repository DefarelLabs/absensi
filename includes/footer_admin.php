<?php
/**
 * includes/footer_admin.php
 * Penutup halaman admin.php
 * Menginjeksikan data edit karyawan ke JavaScript
 */
?>

<!-- Inject data edit karyawan ke JS (hanya kalau modal edit terbuka) -->
<?php if (!empty($edit_data)): ?>
<script>
window.EDIT_LAT = <?= json_encode($edit_data['lat'] ?? '') ?>;
window.EDIT_LNG = <?= json_encode($edit_data['lng'] ?? '') ?>;
</script>
<?php endif; ?>

<!-- Library JS -->
<!-- Leaflet untuk peta -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<!-- QR Code generator -->
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<!-- JSZip untuk download semua QR -->
<script src="https://cdn.jsdelivr.net/npm/jszip@3.10.1/dist/jszip.min.js"></script>
<!-- SheetJS untuk export XLSX -->
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<!-- jsPDF + autoTable untuk export PDF -->
<script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jspdf-autotable@3.8.2/dist/jspdf.plugin.autotable.min.js"></script>
<!-- App Admin JS -->
<script src="assets/js/admin.js"></script>

</body>
</html>
