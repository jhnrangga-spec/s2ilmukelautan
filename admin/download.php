<?php
/**
 * admin/download.php
 * Download berkas pendaftar dari Supabase Storage
 * Mendukung: single file, ZIP per pendaftar, ZIP semua pendaftar
 */
require_once dirname(__DIR__) . '/config/env.php';
require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/config/auth.php';
require_once dirname(__DIR__) . '/config/storage.php';
requireAdmin();

$pdo  = db();
$mode = $_GET['mode'] ?? 'single';

// ── Mode: download satu file ─────────────────────────────────
if ($mode === 'single') {
    $berkasId = (int)($_GET['id'] ?? 0);
    if ($berkasId <= 0) { http_response_code(400); exit('ID berkas tidak valid.'); }

    $stmt = $pdo->prepare("
        SELECT b.nama_file, b.nama_asli, b.mime_type, p.nomor_registrasi
        FROM berkas b
        JOIN pendaftar p ON p.id = b.pendaftar_id
        WHERE b.id = ?
    ");
    $stmt->execute([$berkasId]);
    $berkas = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$berkas) { http_response_code(404); exit('Berkas tidak ditemukan.'); }

    storage_download_stream($berkas['nama_file'], $berkas['nama_asli']);
    exit;
}

// ── Mode: ZIP satu pendaftar ─────────────────────────────────
if ($mode === 'zip_one') {
    $pendaftarId = (int)($_GET['pendaftar_id'] ?? 0);
    if ($pendaftarId <= 0) { http_response_code(400); exit('ID pendaftar tidak valid.'); }

    $stmtP = $pdo->prepare("SELECT nomor_registrasi, nama_lengkap FROM pendaftar WHERE id = ?");
    $stmtP->execute([$pendaftarId]);
    $pendaftar = $stmtP->fetch(PDO::FETCH_ASSOC);
    if (!$pendaftar) { http_response_code(404); exit('Pendaftar tidak ditemukan.'); }

    $stmtB = $pdo->prepare("SELECT nama_file, nama_asli FROM berkas WHERE pendaftar_id = ?");
    $stmtB->execute([$pendaftarId]);
    $berkasRows = $stmtB->fetchAll(PDO::FETCH_ASSOC);
    if (empty($berkasRows)) { http_response_code(404); exit('Tidak ada berkas.'); }

    $zipName = sys_get_temp_dir() . '/' . $pendaftar['nomor_registrasi'] . '_berkas.zip';
    $zip     = new ZipArchive();
    if ($zip->open($zipName, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        http_response_code(500); exit('Gagal membuat ZIP.');
    }
    $cfg = storage_config();
    foreach ($berkasRows as $row) {
        $url  = $cfg['url'] . '/storage/v1/object/' . $cfg['bucket'] . '/' . ltrim($row['nama_file'], '/');
        $ch   = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $cfg['key']]]);
        $data = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($httpCode === 200) { $zip->addFromString(basename($row['nama_file']), $data); }
    }
    $zip->close();
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $pendaftar['nomor_registrasi'] . '_berkas.zip"');
    header('Content-Length: ' . filesize($zipName));
    readfile($zipName);
    unlink($zipName);
    exit;
}

// ── Mode: ZIP semua pendaftar ─────────────────────────────────
if ($mode === 'zip_all') {
    $stmtAll = $pdo->query("
        SELECT b.nama_file, b.nama_asli, p.nomor_registrasi
        FROM berkas b JOIN pendaftar p ON p.id = b.pendaftar_id
        ORDER BY p.nomor_registrasi, b.jenis
    ");
    $allBerkas = $stmtAll->fetchAll(PDO::FETCH_ASSOC);
    if (empty($allBerkas)) { http_response_code(404); exit('Belum ada berkas.'); }

    $zipName = sys_get_temp_dir() . '/semua_berkas_' . date('Ymd_His') . '.zip';
    $zip     = new ZipArchive();
    if ($zip->open($zipName, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        http_response_code(500); exit('Gagal membuat ZIP.');
    }
    $cfg = storage_config();
    foreach ($allBerkas as $row) {
        $url  = $cfg['url'] . '/storage/v1/object/' . $cfg['bucket'] . '/' . ltrim($row['nama_file'], '/');
        $ch   = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $cfg['key']]]);
        $data = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($httpCode === 200) {
            $zip->addFromString($row['nomor_registrasi'] . '/' . basename($row['nama_file']), $data);
        }
    }
    $zip->close();
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="semua_berkas_' . date('Ymd') . '.zip"');
    header('Content-Length: ' . filesize($zipName));
    readfile($zipName);
    unlink($zipName);
    exit;
}

http_response_code(400);
echo 'Mode tidak dikenal.';
