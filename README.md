# Sistem Pendaftaran Mahasiswa Baru S2 Ilmu Kelautan

Aplikasi web pendaftaran mahasiswa baru Program Magister (S2) Fakultas Ilmu
Kelautan, Universitas Khairun.

Dibangun dengan **PHP (native) + SQLite** — tanpa dependency eksternal,
mudah dideploy ke shared hosting kampus maupun server VPS.

## Fitur

- **Formulir pendaftaran lengkap** — data diri, riwayat S1, konsentrasi pilihan, jalur masuk.
- **Upload berkas** — ijazah, transkrip, KTP, pas foto, surat rekomendasi, proposal penelitian (PDF/JPG/PNG, max 3 MB).
- **Cek status pendaftaran** — pendaftar login dengan nomor registrasi + password untuk melihat status.
- **Panel admin** — dashboard statistik, filter & pencarian, verifikasi status.
- **Unduh berkas admin** — per berkas, per pendaftar (ZIP), atau seluruh berkas semua pendaftar (ZIP).
- **Ekspor CSV** — semua data pendaftar untuk diolah di Excel.

## Persyaratan

- PHP 7.4+ dengan ekstensi: `pdo_sqlite`, `fileinfo`, `zip`
- Web server (Apache / Nginx / PHP built-in server)

## Menjalankan secara lokal

```bash
cd s2ilmukelautan
php -S localhost:8000
```

Lalu buka <http://localhost:8000> di browser.

Database SQLite (`data/app.db`) dibuat otomatis saat pertama kali diakses,
beserta akun admin default:

- **Username:** `admin`
- **Password:** `admin123`

> ⚠️ Segera ubah password admin setelah deploy ke production.

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
│   ├── db.php           # Koneksi & schema SQLite
│   └── auth.php         # Autentikasi admin
├── includes/
│   ├── header.php
│   └── footer.php
├── assets/
│   └── style.css
├── uploads/             # Berkas pendaftar (terblok dari akses publik)
└── data/                # File database SQLite
```

## Deploy ke Shared Hosting (cPanel)

1. Unggah seluruh isi folder ke `public_html/` (atau subfolder).
2. Pastikan direktori `data/` dan `uploads/` writable oleh PHP (`chmod 775`).
3. Akses domain — database & akun admin akan otomatis terbentuk.
4. Login admin (`/admin/login.php`), lalu ubah password default lewat database
   atau tambahkan halaman ganti password sesuai kebutuhan.

## Catatan Keamanan

- Folder `uploads/` dan `data/` dilindungi `.htaccess` (Deny from all) untuk
  Apache. Untuk Nginx, tambahkan blok `location ~ ^/(uploads|data)/ { deny all; }`.
- Semua berkas hanya dapat diakses lewat `admin/download.php` setelah admin login.
- Password di-hash dengan `password_hash()` (bcrypt).
- Validasi MIME file dilakukan dengan `finfo` (bukan hanya extension).
