<?php
require_once __DIR__ . '/config/db.php';
db(); // initialize on first load
$pageTitle = 'Beranda';
include __DIR__ . '/includes/header.php';
?>

<section class="hero">
    <h1>Pendaftaran Mahasiswa Baru Program Magister (S2)</h1>
    <p>Fakultas Ilmu Kelautan, Universitas Khairun &mdash; Membentuk peneliti dan praktisi kelautan unggul untuk Indonesia Timur dan kawasan tropis.</p>
    <a href="daftar.php" class="btn btn-primary">Mulai Pendaftaran</a>
    &nbsp;
    <a href="cek-status.php" class="btn btn-secondary" style="background:rgba(255,255,255,0.9)">Cek Status</a>
</section>

<section class="grid-3">
    <div class="feature">
        <h3>Program Studi</h3>
        <p>Magister Ilmu Kelautan dengan konsentrasi: Manajemen Sumberdaya Pesisir, Bioteknologi Kelautan, dan Konservasi Ekosistem Laut.</p>
    </div>
    <div class="feature">
        <h3>Jalur Masuk</h3>
        <p>Tersedia jalur Reguler, Beasiswa LPDP, Beasiswa BPI Kemendikbud, dan Kerjasama Instansi.</p>
    </div>
    <div class="feature">
        <h3>Online Penuh</h3>
        <p>Seluruh proses pendaftaran dilakukan daring &mdash; unggah berkas, pantau status, hingga pengumuman hasil seleksi.</p>
    </div>
</section>

<div class="card" style="margin-top:24px;">
    <h2>Persyaratan Pendaftaran</h2>
    <ol>
        <li>Lulusan S1 dari perguruan tinggi terakreditasi minimal B / Baik Sekali.</li>
        <li>IPK minimal 2,75 dari skala 4,00.</li>
        <li>Mengisi formulir pendaftaran daring secara lengkap.</li>
        <li>Mengunggah berkas: ijazah S1, transkrip nilai, KTP, pas foto, surat rekomendasi, dan proposal penelitian singkat.</li>
        <li>Melengkapi seluruh tahapan sebelum batas akhir pendaftaran.</li>
    </ol>
</div>

<div class="card">
    <h2>Tahapan Pendaftaran</h2>
    <ol>
        <li><strong>Isi formulir</strong> &mdash; lengkapi data diri dan riwayat pendidikan.</li>
        <li><strong>Unggah berkas</strong> &mdash; siapkan dokumen pendukung dalam format PDF/JPG.</li>
        <li><strong>Catat nomor registrasi</strong> &mdash; digunakan untuk memantau status pendaftaran.</li>
        <li><strong>Verifikasi panitia</strong> &mdash; tim akan memeriksa berkas dalam 3&ndash;7 hari kerja.</li>
        <li><strong>Pengumuman</strong> &mdash; status diumumkan via halaman Cek Status.</li>
    </ol>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
