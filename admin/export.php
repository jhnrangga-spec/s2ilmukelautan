<?php
require_once __DIR__ . '/../config/auth.php';
requireAdmin();

$pdo = db();
$rows = $pdo->query("
    SELECT nomor_registrasi, nama_lengkap, jenis_kelamin, tempat_lahir, tanggal_lahir,
           nik, email, no_hp, alamat, kota, provinsi,
           asal_universitas, program_studi_s1, tahun_lulus_s1, ipk_s1,
           program_studi_pilihan, jalur_masuk, pekerjaan, instansi,
           status, catatan_admin, created_at
    FROM pendaftar ORDER BY created_at DESC
")->fetchAll();

$filename = 'pendaftar_s2_ilmukelautan_' . date('Ymd_His') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF"); // BOM untuk Excel

$headers = [
    'No. Registrasi','Nama Lengkap','Jenis Kelamin','Tempat Lahir','Tanggal Lahir',
    'NIK','Email','No HP','Alamat','Kota','Provinsi',
    'Universitas S1','Prodi S1','Tahun Lulus S1','IPK S1',
    'Konsentrasi','Jalur Masuk','Pekerjaan','Instansi',
    'Status','Catatan Admin','Tgl Daftar',
];
fputcsv($out, $headers);
foreach ($rows as $r) {
    fputcsv($out, array_values($r));
}
fclose($out);
exit;
