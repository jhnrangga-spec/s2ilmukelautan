<?php
require_once __DIR__ . '/../config/auth.php';
requireAdmin();

$pdo = db();

// Download single file
if (isset($_GET['berkas'])) {
    $bid = (int)$_GET['berkas'];
    $stmt = $pdo->prepare("SELECT * FROM berkas WHERE id = ?");
    $stmt->execute([$bid]);
    $bk = $stmt->fetch();
    if (!$bk) {
        http_response_code(404);
        exit('Berkas tidak ditemukan');
    }
    $path = UPLOAD_DIR . '/' . $bk['nama_file'];
    if (!is_file($path)) {
        http_response_code(404);
        exit('File tidak ditemukan di server');
    }
    header('Content-Type: ' . $bk['mime_type']);
    header('Content-Disposition: attachment; filename="' . basename($bk['nama_asli']) . '"');
    header('Content-Length: ' . filesize($path));
    readfile($path);
    exit;
}

// Download all berkas for one pendaftar as ZIP
if (isset($_GET['id']) && !empty($_GET['all'])) {
    $pid = (int)$_GET['id'];
    $stmtP = $pdo->prepare("SELECT * FROM pendaftar WHERE id = ?");
    $stmtP->execute([$pid]);
    $p = $stmtP->fetch();
    if (!$p) { http_response_code(404); exit('Pendaftar tidak ditemukan'); }

    $stmtB = $pdo->prepare("SELECT * FROM berkas WHERE pendaftar_id = ?");
    $stmtB->execute([$pid]);
    $berkas = $stmtB->fetchAll();
    if (empty($berkas)) { exit('Tidak ada berkas.'); }

    if (!class_exists('ZipArchive')) {
        http_response_code(500);
        exit('Ekstensi ZipArchive PHP tidak tersedia.');
    }

    $tmpZip = tempnam(sys_get_temp_dir(), 'berkas_') . '.zip';
    try {
        $zip = new ZipArchive();
        if ($zip->open($tmpZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            http_response_code(500);
            exit('Gagal membuat ZIP.');
        }
        foreach ($berkas as $bk) {
            $src = UPLOAD_DIR . '/' . basename(dirname($bk['nama_file'])) . '/' . basename($bk['nama_file']);
            if (is_file($src)) {
                $ext = pathinfo($bk['nama_asli'], PATHINFO_EXTENSION);
                $entry = $bk['jenis'] . '.' . ($ext ?: pathinfo($bk['nama_file'], PATHINFO_EXTENSION));
                $zip->addFile($src, $entry);
            }
        }
        $zip->close();

        $zipName = $p['nomor_registrasi'] . '_' . preg_replace('/[^a-zA-Z0-9]/', '_', $p['nama_lengkap']) . '.zip';
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $zipName . '"');
        header('Content-Length: ' . filesize($tmpZip));
        readfile($tmpZip);
    } finally {
        if (is_file($tmpZip)) @unlink($tmpZip);
    }
    exit;
}

// Download ALL berkas across all pendaftar
if (isset($_GET['everything'])) {
    if (!class_exists('ZipArchive')) {
        http_response_code(500);
        exit('Ekstensi ZipArchive PHP tidak tersedia.');
    }
    $all = $pdo->query("
        SELECT b.*, p.nomor_registrasi, p.nama_lengkap
        FROM berkas b JOIN pendaftar p ON b.pendaftar_id = p.id
        ORDER BY p.nomor_registrasi
    ")->fetchAll();
    if (empty($all)) { exit('Belum ada berkas yang diunggah.'); }

    $tmpZip = tempnam(sys_get_temp_dir(), 'berkas_all_') . '.zip';
    try {
        $zip = new ZipArchive();
        if ($zip->open($tmpZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            http_response_code(500); exit('Gagal membuat ZIP.');
        }
        foreach ($all as $bk) {
            $src = UPLOAD_DIR . '/' . basename(dirname($bk['nama_file'])) . '/' . basename($bk['nama_file']);
            if (is_file($src)) {
                $ext = pathinfo($bk['nama_asli'], PATHINFO_EXTENSION) ?: pathinfo($bk['nama_file'], PATHINFO_EXTENSION);
                $folder = $bk['nomor_registrasi'] . '_' . preg_replace('/[^a-zA-Z0-9]/', '_', $bk['nama_lengkap']);
                $entry = $folder . '/' . $bk['jenis'] . '.' . $ext;
                $zip->addFile($src, $entry);
            }
        }
        $zip->close();

        $name = 'semua_berkas_' . date('Ymd_His') . '.zip';
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $name . '"');
        header('Content-Length: ' . filesize($tmpZip));
        readfile($tmpZip);
    } finally {
        if (is_file($tmpZip)) @unlink($tmpZip);
    }
    exit;
}

http_response_code(400);
exit('Permintaan tidak valid.');
