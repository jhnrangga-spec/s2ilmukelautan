<?php
$pageTitle = $pageTitle ?? 'Pendaftaran S2 Ilmu Kelautan';
$baseUrl = $baseUrl ?? '.';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?> &mdash; Universitas Khairun</title>
<link rel="stylesheet" href="<?= e($baseUrl) ?>/assets/style.css">
</head>
<body>
<header class="site-header">
    <div class="container header-inner">
        <a href="<?= e($baseUrl) ?>/index.php" class="brand">
            <div class="brand-logo">UK</div>
            <div class="brand-text">
                <strong>Universitas Khairun</strong>
                <span>Program Magister (S2) &middot; Fakultas Ilmu Kelautan</span>
            </div>
        </a>
        <nav class="site-nav">
            <a href="<?= e($baseUrl) ?>/index.php">Beranda</a>
            <a href="<?= e($baseUrl) ?>/daftar.php">Daftar</a>
            <a href="<?= e($baseUrl) ?>/cek-status.php">Cek Status</a>
            <a href="<?= e($baseUrl) ?>/admin/login.php" class="nav-admin">Admin</a>
        </nav>
    </div>
</header>
<main class="container main-content">
