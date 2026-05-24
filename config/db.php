<?php
declare(strict_types=1);

define('DB_PATH', __DIR__ . '/../data/app.db');
define('UPLOAD_DIR', __DIR__ . '/../uploads');

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $isNew = !file_exists(DB_PATH);
        if (!is_dir(dirname(DB_PATH))) {
            mkdir(dirname(DB_PATH), 0775, true);
        }
        $pdo = new PDO('sqlite:' . DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec('PRAGMA foreign_keys = ON');
        if ($isNew) {
            initSchema($pdo);
        }
    }
    return $pdo;
}

function initSchema(PDO $pdo): void {
    $pdo->exec("
        CREATE TABLE pendaftar (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nomor_registrasi TEXT UNIQUE NOT NULL,
            nama_lengkap TEXT NOT NULL,
            tempat_lahir TEXT NOT NULL,
            tanggal_lahir TEXT NOT NULL,
            jenis_kelamin TEXT NOT NULL,
            agama TEXT NOT NULL,
            nik TEXT NOT NULL,
            alamat TEXT NOT NULL,
            kota TEXT NOT NULL,
            provinsi TEXT NOT NULL,
            no_hp TEXT NOT NULL,
            email TEXT NOT NULL,
            asal_universitas TEXT NOT NULL,
            program_studi_s1 TEXT NOT NULL,
            tahun_lulus_s1 INTEGER NOT NULL,
            ipk_s1 REAL NOT NULL,
            program_studi_pilihan TEXT NOT NULL,
            jalur_masuk TEXT NOT NULL,
            pekerjaan TEXT,
            instansi TEXT,
            status TEXT NOT NULL DEFAULT 'menunggu',
            catatan_admin TEXT,
            password_hash TEXT,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");

    $pdo->exec("
        CREATE TABLE berkas (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            pendaftar_id INTEGER NOT NULL,
            jenis TEXT NOT NULL,
            nama_asli TEXT NOT NULL,
            nama_file TEXT NOT NULL,
            ukuran INTEGER NOT NULL,
            mime_type TEXT NOT NULL,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (pendaftar_id) REFERENCES pendaftar(id) ON DELETE CASCADE
        )
    ");

    $pdo->exec("
        CREATE TABLE admin (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            password_hash TEXT NOT NULL,
            nama TEXT NOT NULL,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");

    $defaultPass = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO admin (username, password_hash, nama) VALUES (?, ?, ?)");
    $stmt->execute(['admin', $defaultPass, 'Administrator']);
}

function generateNomorRegistrasi(): string {
    $year = date('Y');
    $pdo = db();
    $count = $pdo->query("SELECT COUNT(*) FROM pendaftar WHERE nomor_registrasi LIKE 'S2IK-{$year}-%'")->fetchColumn();
    $seq = str_pad((string)($count + 1), 4, '0', STR_PAD_LEFT);
    return "S2IK-{$year}-{$seq}";
}

function e(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function flash(string $key, ?string $value = null): ?string {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if ($value !== null) {
        $_SESSION['flash'][$key] = $value;
        return null;
    }
    $val = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $val;
}
