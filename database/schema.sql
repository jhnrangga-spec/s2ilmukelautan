-- ============================================================
-- Schema Pendaftaran S2 Ilmu Kelautan - Universitas Khairun
-- Database: PostgreSQL (Supabase)
-- ============================================================
-- Cara pakai:
-- 1. Buka Supabase Dashboard -> SQL Editor -> New Query
-- 2. Paste seluruh isi file ini, lalu klik "Run"
-- ============================================================

-- Drop existing (hati-hati: hanya untuk fresh install)
DROP TABLE IF EXISTS berkas CASCADE;
DROP TABLE IF EXISTS pendaftar CASCADE;
DROP TABLE IF EXISTS admin CASCADE;

-- ============================================================
-- Tabel: admin
-- ============================================================
CREATE TABLE admin (
    id              SERIAL PRIMARY KEY,
    username        VARCHAR(50) UNIQUE NOT NULL,
    password_hash   VARCHAR(255) NOT NULL,
    nama            VARCHAR(100) NOT NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- Tabel: pendaftar
-- ============================================================
CREATE TABLE pendaftar (
    id                      SERIAL PRIMARY KEY,
    nomor_registrasi        VARCHAR(30) UNIQUE NOT NULL,
    nama_lengkap            VARCHAR(150) NOT NULL,
    tempat_lahir            VARCHAR(100) NOT NULL,
    tanggal_lahir           DATE NOT NULL,
    jenis_kelamin           VARCHAR(20) NOT NULL,
    agama                   VARCHAR(30) NOT NULL,
    nik                     VARCHAR(16) NOT NULL,
    alamat                  TEXT NOT NULL,
    kota                    VARCHAR(100) NOT NULL,
    provinsi                VARCHAR(100) NOT NULL,
    no_hp                   VARCHAR(20) NOT NULL,
    email                   VARCHAR(150) NOT NULL,
    asal_universitas        VARCHAR(200) NOT NULL,
    program_studi_s1        VARCHAR(200) NOT NULL,
    tahun_lulus_s1          INTEGER NOT NULL,
    ipk_s1                  NUMERIC(3,2) NOT NULL,
    program_studi_pilihan   VARCHAR(150) NOT NULL,
    jalur_masuk             VARCHAR(100) NOT NULL,
    pekerjaan               VARCHAR(150),
    instansi                VARCHAR(200),
    status                  VARCHAR(20) NOT NULL DEFAULT 'menunggu'
                            CHECK (status IN ('menunggu','diterima','ditolak','revisi')),
    catatan_admin           TEXT,
    password_hash           VARCHAR(255),
    created_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_pendaftar_status ON pendaftar(status);
CREATE INDEX idx_pendaftar_nomor ON pendaftar(nomor_registrasi);
CREATE INDEX idx_pendaftar_email ON pendaftar(email);

-- ============================================================
-- Tabel: berkas
-- ============================================================
CREATE TABLE berkas (
    id              SERIAL PRIMARY KEY,
    pendaftar_id    INTEGER NOT NULL REFERENCES pendaftar(id) ON DELETE CASCADE,
    jenis           VARCHAR(50) NOT NULL,
    nama_asli       VARCHAR(255) NOT NULL,
    nama_file       VARCHAR(500) NOT NULL,
    ukuran          INTEGER NOT NULL,
    mime_type       VARCHAR(100) NOT NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_berkas_pendaftar ON berkas(pendaftar_id);

-- ============================================================
-- Auto-update updated_at saat row pendaftar diubah
-- ============================================================
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_pendaftar_updated_at
    BEFORE UPDATE ON pendaftar
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

-- ============================================================
-- Akun admin default
-- Username: admin
-- Password: admin123  (bcrypt hash di bawah)
-- ============================================================
INSERT INTO admin (username, password_hash, nama)
VALUES (
    'admin',
    '$2y$12$504fFoG3SaPHYPe0d0ljD.x1VOW2ulnoEooMPfyAh5xsflYXRYat2',
    'Administrator'
);

-- Verifikasi
SELECT 'Schema created successfully' AS status,
       (SELECT COUNT(*) FROM admin) AS admin_count;
