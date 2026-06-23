<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<title><?= $page_title ?? 'Absensi Karyawan' ?></title>

<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<!-- Bootstrap Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<!-- App CSS -->
<link rel="stylesheet" href="assets/css/style.css?v=<?= file_exists(__DIR__.'/../assets/css/style.css') ? filemtime(__DIR__.'/../assets/css/style.css') : time() ?>">
<?php if (!empty($extra_css)) foreach ($extra_css as $css) echo "<link rel=\"stylesheet\" href=\"{$css}\">\n"; ?>
<!-- Logo Web -->
<link rel="icon" type="image/x-icon" href="assets/favicon.webp">
</head>
<body>
