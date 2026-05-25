<?php
require_once __DIR__ . '/../config/auth.php';
requireAdmin();

$id = (int)($_GET['id'] ?? 0);
$pdo = db();
$stmt = $pdo->prepare("SELECT * FROM pendaftar WHERE id = ?");
$stmt->execute([$id]);
$p = $stmt->fetch();
if (!$p) {
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfVerify($_POST['csrf_token'] ?? null)) {
        flash('success', 'Sesi kedaluwarsa. Muat ulang halaman lalu coba lagi.');
        header('Location: detail.php?id=' . $id);
        exit;
    }
    $newStatus = (string)($_POST['status'] ?? '');
    $catatan = trim((string)($_POST['catatan_admin'] ?? ''));
    if (in_array($newStatus, ['menunggu','diterima','ditolak','revisi'], true)) {
        $u = $pdo->prepare("UPDATE pendaftar SET status = ?, catatan_admin = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $u->execute([$newStatus, $catatan ?: null, $id]);
        flash('success', 'Status berhasil diperbarui.');
        header('Location: detail.php?id=' . $id);
        exit;
    }
}

$b = $pdo->prepare("SELECT * FROM berkas WHERE pendaftar_id = ? ORDER BY id");
$b->execute([$id]);
$berkas = $b->fetchAll();

$success = flash('success');
$pageTitle = 'Detail Pendaftar';
$baseUrl = '..';
include __DIR__ . '/../includes/header.php';
?>

<div style="margin-bottom:14px;">
    <a href="dashboard.php" class="btn btn-secondary btn-sm">&larr; Kembali ke Daftar</a>
</div>

<?php if ($success): ?>
    <div class="alert alert-success"><?= e($success) ?></div>
<?php endif; ?>

<div class="card">
    <h2><?= e($p['nama_lengkap']) ?> &middot; <?= e($p['nomor_registrasi']) ?></h2>
    <p>Status saat ini: <span class="badge badge-<?= e($p['status']) ?>"><?= e($p['status']) ?></span></p>

    <h3>Data Diri</h3>
    <dl class="detail-grid">
        <dt>NIK</dt><dd><?= e($p['nik']) ?></dd>
        <dt>Tempat, Tgl Lahir</dt><dd><?= e($p['tempat_lahir']) ?>, <?= e($p['tanggal_lahir']) ?></dd>
        <dt>Jenis Kelamin</dt><dd><?= e($p['jenis_kelamin']) ?></dd>
        <dt>Agama</dt><dd><?= e($p['agama']) ?></dd>
        <dt>Email</dt><dd><?= e($p['email']) ?></dd>
        <dt>No. HP</dt><dd><?= e($p['no_hp']) ?></dd>
        <dt>Alamat</dt><dd><?= nl2br(e($p['alamat'])) ?>, <?= e($p['kota']) ?>, <?= e($p['provinsi']) ?></dd>
    </dl>

    <h3>Riwayat Pendidikan</h3>
    <dl class="detail-grid">
        <dt>Asal Universitas S1</dt><dd><?= e($p['asal_universitas']) ?></dd>
        <dt>Program Studi S1</dt><dd><?= e($p['program_studi_s1']) ?></dd>
        <dt>Tahun Lulus</dt><dd><?= e((string)$p['tahun_lulus_s1']) ?></dd>
        <dt>IPK</dt><dd><strong><?= e(number_format((float)$p['ipk_s1'], 2)) ?></strong></dd>
    </dl>

    <h3>Program Pilihan</h3>
    <dl class="detail-grid">
        <dt>Konsentrasi</dt><dd><?= e($p['program_studi_pilihan']) ?></dd>
        <dt>Jalur Masuk</dt><dd><?= e($p['jalur_masuk']) ?></dd>
        <dt>Pekerjaan</dt><dd><?= e($p['pekerjaan'] ?? '-') ?></dd>
        <dt>Instansi</dt><dd><?= e($p['instansi'] ?? '-') ?></dd>
        <dt>Tgl Daftar</dt><dd><?= e($p['created_at']) ?></dd>
    </dl>

    <h3>Berkas</h3>
    <div style="margin-bottom:10px;">
        <a href="download.php?id=<?= (int)$p['id'] ?>&all=1" class="btn btn-success btn-sm">Download Semua Berkas (ZIP)</a>
    </div>
    <table class="data">
        <thead><tr><th>Jenis</th><th>Nama File</th><th>Ukuran</th><th>Tipe</th><th>Aksi</th></tr></thead>
        <tbody>
            <?php foreach ($berkas as $bk): ?>
                <tr>
                    <td><?= e($bk['jenis']) ?></td>
                    <td><?= e($bk['nama_asli']) ?></td>
                    <td><?= number_format($bk['ukuran'] / 1024, 1) ?> KB</td>
                    <td><?= e($bk['mime_type']) ?></td>
                    <td>
                        <a href="download.php?berkas=<?= (int)$bk['id'] ?>" class="btn btn-primary btn-sm">Unduh</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="card">
    <h3>Verifikasi & Status</h3>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <div class="form-row">
            <div class="form-group">
                <label>Status</label>
                <select name="status">
                    <?php foreach (['menunggu' => 'Menunggu Verifikasi', 'diterima' => 'Diterima', 'ditolak' => 'Tidak Diterima', 'revisi' => 'Perlu Revisi Berkas'] as $k => $v): ?>
                        <option value="<?= $k ?>" <?= $p['status'] === $k ? 'selected' : '' ?>><?= $v ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label>Catatan Admin (akan ditampilkan ke pendaftar)</label>
            <textarea name="catatan_admin" rows="4"><?= e($p['catatan_admin'] ?? '') ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
    </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
