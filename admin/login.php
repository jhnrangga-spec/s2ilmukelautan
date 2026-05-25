<?php
require_once __DIR__ . '/../config/auth.php';
db();
startSession();

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfVerify($_POST['csrf_token'] ?? null)) {
        $error = 'Sesi kedaluwarsa. Muat ulang halaman lalu coba lagi.';
    } else {
        $u = trim((string)($_POST['username'] ?? ''));
        $p = (string)($_POST['password'] ?? '');
        if (loginAdmin($u, $p)) {
            header('Location: dashboard.php');
            exit;
        }
        $error = 'Username atau password salah.';
    }
}

$pageTitle = 'Login Admin';
$baseUrl = '..';
include __DIR__ . '/../includes/header.php';
?>

<div class="login-wrap">
    <div class="card">
        <h2>Login Admin</h2>
        <p style="color:var(--muted); font-size:13px; text-align:center;">Panel admin pendaftaran S2 Ilmu Kelautan</p>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required autofocus>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;">Masuk</button>
        </form>
        <p style="text-align:center; margin-top:14px; font-size:12px; color:var(--muted);">
            Default akun: <code>admin</code> / <code>admin123</code><br>(harap ubah setelah deploy)
        </p>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
