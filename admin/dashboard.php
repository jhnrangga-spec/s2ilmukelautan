<?php
require_once __DIR__ . '/../config/auth.php';
requireAdmin();

$admin = currentAdmin();
$search = trim((string)($_GET['q'] ?? ''));
$statusFilter = (string)($_GET['status'] ?? '');

$pdo = db();
$sql = "SELECT id, nomor_registrasi, nama_lengkap, program_studi_pilihan, jalur_masuk, ipk_s1, status, created_at FROM pendaftar WHERE 1=1";
$params = [];
if ($search !== '') {
    $sql .= " AND (nama_lengkap LIKE ? OR nomor_registrasi LIKE ? OR email LIKE ? OR asal_universitas LIKE ?)";
    $like = '%' . $search . '%';
    array_push($params, $like, $like, $like, $like);
}
if (in_array($statusFilter, ['menunggu','diterima','ditolak','revisi'], true)) {
    $sql .= " AND status = ?";
    $params[] = $statusFilter;
}
$sql .= " ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

// stats
$stats = [
    'total' => (int)$pdo->query("SELECT COUNT(*) FROM pendaftar")->fetchColumn(),
    'menunggu' => (int)$pdo->query("SELECT COUNT(*) FROM pendaftar WHERE status='menunggu'")->fetchColumn(),
    'diterima' => (int)$pdo->query("SELECT COUNT(*) FROM pendaftar WHERE status='diterima'")->fetchColumn(),
    'ditolak' => (int)$pdo->query("SELECT COUNT(*) FROM pendaftar WHERE status='ditolak'")->fetchColumn(),
];

$pageTitle = 'Dashboard Admin';
$baseUrl = '..';
include __DIR__ . '/../includes/header.php';
?>

<div class="card" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
    <div>
        <h2 style="margin:0;">Dashboard Pendaftar</h2>
        <p style="margin:4px 0 0; color:var(--muted); font-size:14px;">
            Selamat datang, <strong><?= e($admin['nama']) ?></strong>
        </p>
    </div>
    <div style="display:flex; gap:8px;">
        <a href="export.php" class="btn btn-secondary btn-sm">Ekspor CSV</a>
        <a href="download.php?everything=1" class="btn btn-success btn-sm">Unduh Semua Berkas (ZIP)</a>
        <a href="logout.php" class="btn btn-danger btn-sm">Keluar</a>
    </div>
</div>

<div class="stats">
    <div class="stat-card"><div class="num"><?= $stats['total'] ?></div><div class="label">Total Pendaftar</div></div>
    <div class="stat-card"><div class="num" style="color:var(--warning)"><?= $stats['menunggu'] ?></div><div class="label">Menunggu</div></div>
    <div class="stat-card"><div class="num" style="color:var(--success)"><?= $stats['diterima'] ?></div><div class="label">Diterima</div></div>
    <div class="stat-card"><div class="num" style="color:var(--danger)"><?= $stats['ditolak'] ?></div><div class="label">Ditolak</div></div>
</div>

<div class="card">
    <form method="get" class="toolbar">
        <input type="text" name="q" placeholder="Cari nama, no.reg, email, kampus..." value="<?= e($search) ?>" style="flex:1; min-width:220px;">
        <select name="status">
            <option value="">Semua Status</option>
            <?php foreach (['menunggu' => 'Menunggu', 'diterima' => 'Diterima', 'ditolak' => 'Ditolak', 'revisi' => 'Revisi'] as $k => $v): ?>
                <option value="<?= $k ?>" <?= $statusFilter === $k ? 'selected' : '' ?>><?= $v ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-primary btn-sm">Filter</button>
        <?php if ($search !== '' || $statusFilter !== ''): ?>
            <a href="dashboard.php" class="btn btn-secondary btn-sm">Reset</a>
        <?php endif; ?>
    </form>

    <div class="table-wrap">
        <table class="data">
            <thead>
                <tr>
                    <th>No. Registrasi</th>
                    <th>Nama</th>
                    <th>Konsentrasi</th>
                    <th>Jalur</th>
                    <th>IPK</th>
                    <th>Status</th>
                    <th>Tanggal</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="8" style="text-align:center; color:var(--muted); padding:20px;">Belum ada pendaftar.</td></tr>
                <?php else: foreach ($rows as $r): ?>
                    <tr>
                        <td><strong><?= e($r['nomor_registrasi']) ?></strong></td>
                        <td><?= e($r['nama_lengkap']) ?></td>
                        <td><?= e($r['program_studi_pilihan']) ?></td>
                        <td><?= e($r['jalur_masuk']) ?></td>
                        <td><?= e(number_format((float)$r['ipk_s1'], 2)) ?></td>
                        <td><span class="badge badge-<?= e($r['status']) ?>"><?= e($r['status']) ?></span></td>
                        <td><?= e(substr($r['created_at'], 0, 16)) ?></td>
                        <td><a href="detail.php?id=<?= (int)$r['id'] ?>" class="btn btn-primary btn-sm">Detail</a></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
