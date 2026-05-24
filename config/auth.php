<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

function startSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function loginAdmin(string $username, string $password): bool {
    startSession();
    $stmt = db()->prepare("SELECT * FROM admin WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();
    if ($admin && password_verify($password, $admin['password_hash'])) {
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
