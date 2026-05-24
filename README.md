# Sistem Pendaftaran Mahasiswa Baru S2 Ilmu Kelautan

Aplikasi web pendaftaran mahasiswa baru Program Magister (S2) Fakultas Ilmu
Kelautan, Universitas Khairun.

Dibangun dengan **PHP (native)** + dukungan **PostgreSQL (Supabase)** atau
**SQLite** untuk pengembangan lokal.

## Fitur

- **Formulir pendaftaran lengkap** — data diri, riwayat S1, konsentrasi pilihan, jalur masuk.
- **Upload berkas** — ijazah, transkrip, KTP, pas foto, surat rekomendasi, proposal penelitian (PDF/JPG/PNG, max 3 MB).
- **Cek status pendaftaran** — pendaftar login dengan nomor registrasi + password.
- **Panel admin** — dashboard statistik, filter & pencarian, verifikasi status.
- **Unduh berkas admin** — per berkas, per pendaftar (ZIP), atau seluruh berkas semua pendaftar (ZIP).
- **Ekspor CSV** — semua data pendaftar untuk diolah di Excel.

## Persyaratan

- PHP 7.4+ dengan ekstensi: `pdo_sqlite` (lokal) / `pdo_pgsql` (Supabase), `fileinfo`, `zip`
- Web server (Apache / Nginx / PHP built-in server)

---

## Cara Setup dengan Supabase

### Langkah 1 — Jalankan SQL Schema di Supabase

1. Buka [Supabase Dashboard](https://supabase.com/dashboard) → pilih project `s2ilmukelautan`.
2. Di sidebar kiri, klik ikon **SQL Editor** (gambar terminal).
3. Klik **New query**.
4. Buka file [`database/schema.sql`](database/schema.sql), copy seluruh isinya, lalu paste ke editor.
5. Klik **Run** (atau tekan `Ctrl+Enter`).
6. Pastikan output `Schema created successfully` muncul dengan `admin_count = 1`.

### Langkah 2 — Ambil Credentials Koneksi

1. Di dashboard project Supabase, klik tombol hijau **Connect** di pojok atas.
2. Pilih tab **Direct connection** (atau **Session pooler** jika hosting Anda tidak mendukung IPv6).
3. Anda akan melihat informasi seperti:
   ```
   Host:     db.qrixabrmufbjnlqtaaek.supabase.co
   Port:     5432
   Database: postgres
   User:     postgres
   Password: [klik "Reveal" untuk lihat password]
   ```
4. Jika lupa password DB, buka **Project Settings → Database → Reset database password**.

### Langkah 3 — Buat File `.env`

Salin `.env.example` menjadi `.env`:

```bash
cp .env.example .env
```

Edit `.env` dan isi dengan credentials dari Langkah 2:

```env
DB_DRIVER=pgsql
DB_HOST=db.qrixabrmufbjnlqtaaek.supabase.co
DB_PORT=5432
DB_NAME=postgres
DB_USER=postgres
DB_PASSWORD=password-database-anda
```

### Langkah 4 — Jalankan Aplikasi

```bash
php -S localhost:8000
```

Buka <http://localhost:8000>. Login admin default:

- **Username:** `admin`
- **Password:** `admin123`

> ⚠️ Segera ubah password admin setelah deploy. Anda bisa update via SQL Editor Supabase:
> ```sql
> UPDATE admin SET password_hash = '<bcrypt-hash-baru>' WHERE username = 'admin';
> ```
> Generate hash baru dengan: `php -r "echo password_hash('passwordbaru', PASSWORD_BCRYPT);"`

---

## Mode Pengembangan Lokal (SQLite, tanpa Supabase)

Jika `.env` tidak ada atau `DB_DRIVER=sqlite`, aplikasi akan menggunakan
file SQLite di `data/app.db`. Akun admin & skema dibuat otomatis.

```bash
php -S localhost:8000
```

---

## Struktur Direktori

```
.
├── index.php            # Beranda
├── daftar.php           # Form pendaftaran
├── cek-status.php       # Cek status oleh pendaftar
├── admin/
│   ├── login.php
│   ├── logout.php
│   ├── dashboard.php    # Daftar pendaftar + statistik
│   ├── detail.php       # Detail + ubah status
│   ├── download.php     # Unduh berkas (single / per pendaftar / semua)
│   └── export.php       # Ekspor CSV
├── config/
│   ├── env.php          # Loader .env sederhana
│   ├── db.php           # Koneksi DB (SQLite / PostgreSQL)
│   └── auth.php         # Autentikasi admin
├── database/
│   └── schema.sql       # DDL PostgreSQL untuk Supabase
├── includes/            # header / footer
├── assets/style.css
├── uploads/             # Berkas pendaftar (diblok dari akses publik)
├── data/                # File database SQLite (mode lokal)
├── .env.example
└── README.md
```

## Catatan Keamanan

- File `.env` masuk `.gitignore` — tidak akan ter-commit.
- Folder `uploads/` & `data/` dilindungi `.htaccess` (Deny from all) untuk Apache.
  Untuk Nginx tambahkan: `location ~ ^/(uploads|data)/ { deny all; }`.
- Semua berkas hanya bisa diakses lewat `admin/download.php` setelah admin login.
- Password di-hash dengan `password_hash()` (bcrypt).
- Koneksi PostgreSQL ke Supabase menggunakan `sslmode=require`.
- Validasi MIME upload memakai `finfo` (bukan hanya extension).
