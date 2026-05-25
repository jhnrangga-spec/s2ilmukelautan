<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

function startSession(): void {
    if (session_status() !== PHP_SESSION_NONE) {
        return;
    }
    if (!headers_sent()) {
        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        ini_set('session.use_strict_mode', '1');
    }
    session_start();
}

function csrfToken(): string {
    startSession();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfVerify(?string $token): bool {
    startSession();
    $expected = $_SESSION['csrf_token'] ?? '';
    return is_string($token) && $expected !== '' && hash_equals($expected, $token);
}

function loginAdmin(string $username, string $password): bool {
    startSession();
    $stmt = db()->prepare("SELECT * FROM admin WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();
    if ($admin && password_verify($password, $admin['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['admin'] = [
            'id' => $admin['id'],
            'username' => $admin['username'],
            'nama' => $admin['nama'],
        ];
        return true;
    }
    return false;
}

function logoutAdmin(): void {
    startSession();
    unset($_SESSION['admin']);
    session_regenerate_id(true);
}

function currentAdmin(): ?array {
    startSession();
    return $_SESSION['admin'] ?? null;
}

function requireAdmin(): void {
    if (!currentAdmin()) {
        header('Location: ' . adminBaseUrl() . '/login.php');
        exit;
    }
}

function adminBaseUrl(): string {
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    if (strpos($script, '/admin/') !== false) {
        return '.';
    }
    return 'admin';
}
