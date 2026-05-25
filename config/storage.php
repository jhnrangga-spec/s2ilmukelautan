<?php
/**
 * config/storage.php - Helper Supabase Storage REST API
 */
defined('APP_ROOT') || define('APP_ROOT', dirname(__DIR__));

function storage_config(): array {
    return [
        'url'    => rtrim(getenv('SUPABASE_URL') ?: ($_ENV['SUPABASE_URL'] ?? ''), '/'),
        'key'    => getenv('SUPABASE_SERVICE_ROLE_KEY') ?: ($_ENV['SUPABASE_SERVICE_ROLE_KEY'] ?? ''),
        'bucket' => getenv('SUPABASE_BUCKET') ?: ($_ENV['SUPABASE_BUCKET'] ?? 'berkas-pendaftar'),
    ];
}

function storage_upload(string $localPath, string $remotePath, string $mimeType = 'application/octet-stream'): array {
    $cfg = storage_config();
    if (empty($cfg['url']) || empty($cfg['key'])) {
        return ['success' => false, 'path' => '', 'error' => 'Supabase credentials tidak ditemukan'];
    }
    if (!file_exists($localPath)) {
        return ['success' => false, 'path' => '', 'error' => 'File lokal tidak ditemukan'];
    }
    $url      = $cfg['url'] . '/storage/v1/object/' . $cfg['bucket'] . '/' . ltrim($remotePath, '/');
    $fileData = file_get_contents($localPath);
    if ($fileData === false) {
        return ['success' => false, 'path' => '', 'error' => 'Gagal membaca file'];
    }
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST  => 'POST',
        CURLOPT_POSTFIELDS     => $fileData,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $cfg['key'],
            'Content-Type: ' . $mimeType,
            'Content-Length: ' . strlen($fileData),
            'x-upsert: true',
        ],
    ]);
    $response  = curl_exec($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    if ($curlError) {
        return ['success' => false, 'path' => '', 'error' => 'cURL error: ' . $curlError];
    }
    if ($httpCode === 200 || $httpCode === 201) {
        return ['success' => true, 'path' => $remotePath, 'error' => ''];
    }
    $body   = json_decode($response, true);
    $errMsg = $body['message'] ?? $body['error'] ?? $response;
    return ['success' => false, 'path' => '', 'error' => 'HTTP ' . $httpCode . ': ' . $errMsg];
}

function storage_download_stream(string $remotePath, string $downloadName = ''): void {
    $cfg = storage_config();
    $url = $cfg['url'] . '/storage/v1/object/' . $cfg['bucket'] . '/' . ltrim($remotePath, '/');
    $ch  = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 60,
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $cfg['key']],
    ]);
    $data     = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);
    if ($curlErr || $httpCode !== 200) {
        http_response_code(404);
        echo 'File tidak ditemukan.';
        return;
    }
    $ext     = strtolower(pathinfo($remotePath, PATHINFO_EXTENSION));
    $mimeMap = ['pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png'];
    $mime    = $mimeMap[$ext] ?? 'application/octet-stream';
    if (empty($downloadName)) { $downloadName = basename($remotePath); }
    header('Content-Type: ' . $mime);
    header('Content-Disposition: attachment; filename="' . $downloadName . '"');
    header('Content-Length: ' . strlen($data));
    header('Cache-Control: no-store');
    echo $data;
    exit;
}

function storage_delete(string $remotePath): bool {
    $cfg = storage_config();
    $url = $cfg['url'] . '/storage/v1/object/' . $cfg['bucket'] . '/' . ltrim($remotePath, '/');
    $ch  = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST  => 'DELETE',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $cfg['key']],
    ]);
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $httpCode === 200;
}
