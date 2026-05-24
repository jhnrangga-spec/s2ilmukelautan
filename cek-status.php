<?php
require_once __DIR__ . '/config/db.php';
db();

$error = null;
$pendaftar = null;
$berkasList = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nomor = trim((string)($_POST['nomor_registrasi'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    if ($nomor === '' || $password === '') {
        $error = 'Nomor registrasi dan password wajib diisi.';
    } else {
        $stmt = db()->prepare("SELECT * FROM pendaftar WHERE nomor_registrasi = ?");
        $stmt->execute([$nomor]);
        $row = $stmt->fetch();
        if (!$row || !password_verify($password, $row['password_hash'] ?? '')) {
            $error = 'Nomor registrasi atau password salah.';
        } else {
            $pendaftar = $row;
            $b = db()->prepare("SELECT * FROM berkas WHERE pendaftar_id = ? ORDER BY id");
            $b->execute([$row['id']]);
            $berkasList = $b->fetchAll();
        }
    }
}

$statusLabel = [
    'menunggu' => 'Menunggu Verifikasi',
    'diterima' => 'Diterima',
    'ditolak' => 'Tidak Diterima',
    'revisi' => 'Perlu Revisi Berkas',
];
$statusDesc = [
    'menunggu' => 'Berkas Anda sedang dalam proses verifikasi oleh panitia. Mohon menunggu 3&ndash;7 hari kerja.',
    'diterima' => 'Selamat! Anda dinyatakan diterima. Silakan tunggu informasi lanjutan via email untuk proses registrasi ulang.',
    'ditolak' => 'Mohon maaf, pendaftaran Anda belum dapat diterima pada periode ini. Lihat catatan dari panitia di bawah.',
    'revisi' => 'Terdapat berkas yang perlu Anda perbaiki. Lihat catatan dari panitia di bawah.',
];

$pageTitle = 'Cek Status';
include __DIR__ . '/includes/header.php';
?>

<?php if ($pendaftar): ?>
    <div class="status-box <?= e($pendaftar['status']) ?>">
        <h3>Status: <?= e($statusLabel[$pendaftar['status']] ?? $pendaftar['status']) ?></h3>
        <p><?= $statusDesc[$pendaftar['status']] ?? '' ?></p>
        <?php if (!empty($pendaftar['catatan_admin'])): ?>
            <p><strong>Catatan Panitia:</strong> <?= nl2br(e($pendaftar['catatan_admin'])) ?></p>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2>Detail Pendaftaran</h2>
        <dl class="detail-grid">
            <dt>Nomor Registrasi</dt><dd><strong><?= e($pendaftar['nomor_registrasi']) ?></strong></dd>
            <dt>Nama Lengkap</dt><dd><?= e($pendaftar['nama_lengkap']) ?></dd>
            <dt>Tempat, Tgl Lahir</dt><dd><?= e($pendaftar['tempat_lahir']) ?>, <?= e($pendaftar['tanggal_lahir']) ?></dd>
            <dt>Email</dt><dd><?= e($pendaftar['email']) ?></dd>
            <dt>No. HP</dt><dd><?= e($pendaftar['no_hp']) ?></dd>
            <dt>Asal Universitas S1</dt><dd><?= e($pendaftar['asal_universitas']) ?></dd>
            <dt>Program Studi S1</dt><dd><?= e($pendaftar['program_studi_s1']) ?> (<?= e((string)$pendaftar['tahun_lulus_s1']) ?>) &middot; IPK <?= e(number_format((float)$pendaftar['ipk_s1'], 2)) ?></dd>
            <dt>Konsentrasi Pilihan</dt><dd><?= e($pendaftar['program_studi_pilihan']) ?></dd>
            <dt>Jalur Masuk</dt><dd><?= e($pendaftar['jalur_masuk']) ?></dd>
            <dt>Tgl Pendaftaran</dt><dd><?= e($pendaftar['created_at']) ?></dd>
        </dl>

        <h3>Berkas yang Diunggah</h3>
        <table class="data">
            <thead>
                <tr><th>Jenis Berkas</th><th>Nama File</th><th>Ukuran</th></tr>
            </thead>
            <tbody>
                <?php foreach ($berkasList as $bk): ?>
                    <tr>
                        <td><?= e($bk['jenis']) ?></td>
                        <td><?= e($bk['nama_asli']) ?></td>
                        <td><?= number_format($bk['ukuran'] / 1024, 1) ?> KB</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <p style="margin-top:18px;">
            <a href="cek-status.php" class="btn btn-secondary">Keluar</a>
        </p>
    </div>
<?php else: ?>
    <div class="login-wrap">
        <div class="card">
            <h2>Cek Status Pendaftaran</h2>
            <p style="color:var(--muted); font-size:14px; text-align:center;">Masukkan nomor registrasi dan password yang Anda buat saat mendaftar.</p>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="post">
                <div class="form-group">
                    <label>Nomor Registrasi</label>
                    <input type="text" name="nomor_registrasi" placeholder="S2IK-<?= date('Y') ?>-0001" required autofocus>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;">Cek Status</button>
            </form>
            <p style="text-align:center; margin-top:16px; font-size:14px;">
                Belum daftar? <a href="daftar.php">Daftar di sini</a>
            </p>
        </div>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
