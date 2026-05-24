<?php
require_once __DIR__ . '/config/db.php';
db();

$errors = [];
$old = [];
$registrationResult = null;

const ALLOWED_MIME = [
    'application/pdf' => 'pdf',
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
];
const MAX_FILE_SIZE = 3 * 1024 * 1024; // 3 MB
const REQUIRED_FILES = [
    'berkas_ijazah' => 'Ijazah S1',
    'berkas_transkrip' => 'Transkrip Nilai S1',
    'berkas_ktp' => 'KTP / Identitas',
    'berkas_foto' => 'Pas Foto',
    'berkas_rekomendasi' => 'Surat Rekomendasi',
    'berkas_proposal' => 'Proposal Penelitian Singkat',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = [
        'nama_lengkap', 'tempat_lahir', 'tanggal_lahir', 'jenis_kelamin', 'agama',
        'nik', 'alamat', 'kota', 'provinsi', 'no_hp', 'email',
        'asal_universitas', 'program_studi_s1', 'tahun_lulus_s1', 'ipk_s1',
        'program_studi_pilihan', 'jalur_masuk', 'pekerjaan', 'instansi', 'password',
    ];
    foreach ($fields as $f) {
        $old[$f] = trim((string)($_POST[$f] ?? ''));
    }

    // Required validation
    $required = array_diff($fields, ['pekerjaan', 'instansi']);
    foreach ($required as $f) {
        if ($old[$f] === '') {
            $errors[$f] = 'Wajib diisi';
        }
    }
    if (!empty($old['email']) && !filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Format email tidak valid';
    }
    if (!empty($old['nik']) && !preg_match('/^\d{16}$/', $old['nik'])) {
        $errors['nik'] = 'NIK harus 16 digit angka';
    }
    if (!empty($old['ipk_s1'])) {
        $ipk = (float)str_replace(',', '.', $old['ipk_s1']);
        if ($ipk < 0 || $ipk > 4) {
            $errors['ipk_s1'] = 'IPK harus antara 0,00 - 4,00';
        } elseif ($ipk < 2.75) {
            $errors['ipk_s1'] = 'IPK minimal 2,75';
        }
    }
    if (!empty($old['tahun_lulus_s1'])) {
        $thn = (int)$old['tahun_lulus_s1'];
        if ($thn < 1980 || $thn > (int)date('Y')) {
            $errors['tahun_lulus_s1'] = 'Tahun lulus tidak valid';
        }
    }
    if (!empty($old['password']) && strlen($old['password']) < 6) {
        $errors['password'] = 'Password minimal 6 karakter';
    }

    // File validation
    $uploadedFiles = [];
    foreach (REQUIRED_FILES as $key => $label) {
        if (!isset($_FILES[$key]) || $_FILES[$key]['error'] === UPLOAD_ERR_NO_FILE) {
            $errors[$key] = "Wajib mengunggah: {$label}";
            continue;
        }
        $file = $_FILES[$key];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[$key] = "Gagal mengunggah {$label}";
            continue;
        }
        if ($file['size'] > MAX_FILE_SIZE) {
            $errors[$key] = "{$label} melebihi 3 MB";
            continue;
        }
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        if (!isset(ALLOWED_MIME[$mime])) {
            $errors[$key] = "{$label} harus berformat PDF/JPG/PNG";
            continue;
        }
        $uploadedFiles[$key] = ['file' => $file, 'mime' => $mime, 'label' => $label];
    }

    if (empty($errors)) {
        try {
            $pdo = db();
            $pdo->beginTransaction();
            $nomor = generateNomorRegistrasi();
            $passHash = password_hash($old['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("
                INSERT INTO pendaftar (
                    nomor_registrasi, nama_lengkap, tempat_lahir, tanggal_lahir,
                    jenis_kelamin, agama, nik, alamat, kota, provinsi, no_hp, email,
                    asal_universitas, program_studi_s1, tahun_lulus_s1, ipk_s1,
                    program_studi_pilihan, jalur_masuk, pekerjaan, instansi, password_hash
                ) VALUES (
                    :nomor, :nama, :tempat, :tgl, :jk, :agama, :nik, :alamat, :kota, :prov,
                    :hp, :email, :asal, :prodi_s1, :thn, :ipk, :prodi_pilih, :jalur, :pekerjaan, :instansi, :pass
                ) RETURNING id
            ");
            $stmt->execute([
                ':nomor' => $nomor,
                ':nama' => $old['nama_lengkap'],
                ':tempat' => $old['tempat_lahir'],
                ':tgl' => $old['tanggal_lahir'],
                ':jk' => $old['jenis_kelamin'],
                ':agama' => $old['agama'],
                ':nik' => $old['nik'],
                ':alamat' => $old['alamat'],
                ':kota' => $old['kota'],
                ':prov' => $old['provinsi'],
                ':hp' => $old['no_hp'],
                ':email' => $old['email'],
                ':asal' => $old['asal_universitas'],
                ':prodi_s1' => $old['program_studi_s1'],
                ':thn' => (int)$old['tahun_lulus_s1'],
                ':ipk' => (float)str_replace(',', '.', $old['ipk_s1']),
                ':prodi_pilih' => $old['program_studi_pilihan'],
                ':jalur' => $old['jalur_masuk'],
                ':pekerjaan' => $old['pekerjaan'] ?: null,
                ':instansi' => $old['instansi'] ?: null,
                ':pass' => $passHash,
            ]);
            $pendaftarId = (int)$stmt->fetchColumn();
            $stmt->closeCursor();

            if (!is_dir(UPLOAD_DIR)) {
                mkdir(UPLOAD_DIR, 0775, true);
            }
            $dirRel = $nomor;
            $dirAbs = UPLOAD_DIR . '/' . $dirRel;
            if (!is_dir($dirAbs)) {
                mkdir($dirAbs, 0775, true);
            }

            $stmtFile = $pdo->prepare("
                INSERT INTO berkas (pendaftar_id, jenis, nama_asli, nama_file, ukuran, mime_type)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            foreach ($uploadedFiles as $key => $data) {
                $ext = ALLOWED_MIME[$data['mime']];
                $safeName = $key . '.' . $ext;
                $dest = $dirAbs . '/' . $safeName;
                if (!move_uploaded_file($data['file']['tmp_name'], $dest)) {
                    throw new RuntimeException('Gagal menyimpan berkas: ' . $data['label']);
                }
                $stmtFile->execute([
                    $pendaftarId, $key,
                    basename($data['file']['name']),
                    $dirRel . '/' . $safeName,
                    (int)$data['file']['size'],
                    $data['mime'],
                ]);
            }
            $pdo->commit();
            $registrationResult = $nomor;
        } catch (Throwable $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $errors['_global'] = 'Terjadi kesalahan: ' . $ex->getMessage();
        }
    }
}

$pageTitle = 'Formulir Pendaftaran';
include __DIR__ . '/includes/header.php';
?>

<?php if ($registrationResult): ?>
    <div class="card" style="text-align:center;">
        <h2>Pendaftaran Berhasil!</h2>
        <p>Terima kasih telah mendaftar Program Magister Ilmu Kelautan Universitas Khairun.</p>
        <p>Catat <strong>Nomor Registrasi</strong> Anda untuk memantau status pendaftaran:</p>
        <div style="font-size:32px; font-weight:800; color:var(--primary); margin:18px 0; letter-spacing:2px;">
            <?= e($registrationResult) ?>
        </div>
        <p>Gunakan nomor registrasi dan password yang Anda buat untuk mengakses halaman <a href="cek-status.php">Cek Status</a>.</p>
        <a href="cek-status.php" class="btn btn-primary">Cek Status Sekarang</a>
        <a href="index.php" class="btn btn-secondary">Ke Beranda</a>
    </div>
<?php else: ?>

<div class="card">
    <h2>Formulir Pendaftaran Mahasiswa Baru S2 Ilmu Kelautan</h2>
    <p style="color:var(--muted);">Lengkapi seluruh data berikut dengan benar. Field bertanda <span style="color:var(--danger)">*</span> wajib diisi. Ukuran maksimal tiap berkas 3 MB (PDF/JPG/PNG).</p>

    <?php if (!empty($errors['_global'])): ?>
        <div class="alert alert-error"><?= e($errors['_global']) ?></div>
    <?php elseif (!empty($errors)): ?>
        <div class="alert alert-error">Terdapat <?= count($errors) ?> isian yang perlu diperbaiki. Silakan periksa kembali.</div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" novalidate>

        <div class="form-section">
            <div class="form-section-title">A. Data Diri</div>
            <div class="form-group">
                <label>Nama Lengkap <span class="req">*</span></label>
                <input type="text" name="nama_lengkap" value="<?= e($old['nama_lengkap'] ?? '') ?>" required>
                <?php if (!empty($errors['nama_lengkap'])): ?><small style="color:var(--danger)"><?= e($errors['nama_lengkap']) ?></small><?php endif; ?>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Tempat Lahir <span class="req">*</span></label>
                    <input type="text" name="tempat_lahir" value="<?= e($old['tempat_lahir'] ?? '') ?>" required>
                    <?php if (!empty($errors['tempat_lahir'])): ?><small style="color:var(--danger)"><?= e($errors['tempat_lahir']) ?></small><?php endif; ?>
                </div>
                <div class="form-group">
                    <label>Tanggal Lahir <span class="req">*</span></label>
                    <input type="date" name="tanggal_lahir" value="<?= e($old['tanggal_lahir'] ?? '') ?>" required>
                    <?php if (!empty($errors['tanggal_lahir'])): ?><small style="color:var(--danger)"><?= e($errors['tanggal_lahir']) ?></small><?php endif; ?>
                </div>
                <div class="form-group">
                    <label>Jenis Kelamin <span class="req">*</span></label>
                    <select name="jenis_kelamin" required>
                        <option value="">-- Pilih --</option>
                        <?php foreach (['Laki-laki', 'Perempuan'] as $jk): ?>
                            <option <?= ($old['jenis_kelamin'] ?? '') === $jk ? 'selected' : '' ?>><?= $jk ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($errors['jenis_kelamin'])): ?><small style="color:var(--danger)"><?= e($errors['jenis_kelamin']) ?></small><?php endif; ?>
                </div>
                <div class="form-group">
                    <label>Agama <span class="req">*</span></label>
                    <select name="agama" required>
                        <option value="">-- Pilih --</option>
                        <?php foreach (['Islam','Kristen Protestan','Kristen Katolik','Hindu','Buddha','Konghucu'] as $a): ?>
                            <option <?= ($old['agama'] ?? '') === $a ? 'selected' : '' ?>><?= $a ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($errors['agama'])): ?><small style="color:var(--danger)"><?= e($errors['agama']) ?></small><?php endif; ?>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>NIK (KTP, 16 digit) <span class="req">*</span></label>
                    <input type="text" name="nik" maxlength="16" value="<?= e($old['nik'] ?? '') ?>" required>
                    <?php if (!empty($errors['nik'])): ?><small style="color:var(--danger)"><?= e($errors['nik']) ?></small><?php endif; ?>
                </div>
                <div class="form-group">
                    <label>No. HP / WhatsApp <span class="req">*</span></label>
                    <input type="text" name="no_hp" value="<?= e($old['no_hp'] ?? '') ?>" required>
                    <?php if (!empty($errors['no_hp'])): ?><small style="color:var(--danger)"><?= e($errors['no_hp']) ?></small><?php endif; ?>
                </div>
                <div class="form-group">
                    <label>Email <span class="req">*</span></label>
                    <input type="email" name="email" value="<?= e($old['email'] ?? '') ?>" required>
                    <?php if (!empty($errors['email'])): ?><small style="color:var(--danger)"><?= e($errors['email']) ?></small><?php endif; ?>
                </div>
            </div>
            <div class="form-group">
                <label>Alamat Lengkap <span class="req">*</span></label>
                <textarea name="alamat" required><?= e($old['alamat'] ?? '') ?></textarea>
                <?php if (!empty($errors['alamat'])): ?><small style="color:var(--danger)"><?= e($errors['alamat']) ?></small><?php endif; ?>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Kota / Kabupaten <span class="req">*</span></label>
                    <input type="text" name="kota" value="<?= e($old['kota'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>Provinsi <span class="req">*</span></label>
                    <input type="text" name="provinsi" value="<?= e($old['provinsi'] ?? 'Maluku Utara') ?>" required>
                </div>
            </div>
        </div>

        <div class="form-section">
            <div class="form-section-title">B. Riwayat Pendidikan S1</div>
            <div class="form-row">
                <div class="form-group">
                    <label>Asal Universitas S1 <span class="req">*</span></label>
                    <input type="text" name="asal_universitas" value="<?= e($old['asal_universitas'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>Program Studi S1 <span class="req">*</span></label>
                    <input type="text" name="program_studi_s1" value="<?= e($old['program_studi_s1'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>Tahun Lulus S1 <span class="req">*</span></label>
                    <input type="number" name="tahun_lulus_s1" min="1980" max="<?= date('Y') ?>" value="<?= e($old['tahun_lulus_s1'] ?? '') ?>" required>
                    <?php if (!empty($errors['tahun_lulus_s1'])): ?><small style="color:var(--danger)"><?= e($errors['tahun_lulus_s1']) ?></small><?php endif; ?>
                </div>
                <div class="form-group">
                    <label>IPK S1 <span class="req">*</span></label>
                    <input type="text" name="ipk_s1" placeholder="3.25" value="<?= e($old['ipk_s1'] ?? '') ?>" required>
                    <small>Skala 0,00 - 4,00. Minimal 2,75</small>
                    <?php if (!empty($errors['ipk_s1'])): ?><small style="color:var(--danger)"><?= e($errors['ipk_s1']) ?></small><?php endif; ?>
                </div>
            </div>
        </div>

        <div class="form-section">
            <div class="form-section-title">C. Program Pilihan</div>
            <div class="form-row">
                <div class="form-group">
                    <label>Konsentrasi Program Studi <span class="req">*</span></label>
                    <select name="program_studi_pilihan" required>
                        <option value="">-- Pilih --</option>
                        <?php foreach ([
                            'Manajemen Sumberdaya Pesisir',
                            'Bioteknologi Kelautan',
                            'Konservasi Ekosistem Laut',
                            'Perikanan Tangkap Berkelanjutan',
                        ] as $p): ?>
                            <option <?= ($old['program_studi_pilihan'] ?? '') === $p ? 'selected' : '' ?>><?= $p ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Jalur Masuk <span class="req">*</span></label>
                    <select name="jalur_masuk" required>
                        <option value="">-- Pilih --</option>
                        <?php foreach ([
                            'Reguler (Mandiri)',
                            'Beasiswa LPDP',
                            'Beasiswa BPI Kemendikbud',
                            'Kerjasama Instansi',
                        ] as $j): ?>
                            <option <?= ($old['jalur_masuk'] ?? '') === $j ? 'selected' : '' ?>><?= $j ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Pekerjaan (jika ada)</label>
                    <input type="text" name="pekerjaan" value="<?= e($old['pekerjaan'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Instansi (jika ada)</label>
                    <input type="text" name="instansi" value="<?= e($old['instansi'] ?? '') ?>">
                </div>
            </div>
        </div>

        <div class="form-section">
            <div class="form-section-title">D. Unggah Berkas</div>
            <p style="color:var(--muted); font-size:13px;">Format: PDF, JPG, atau PNG. Ukuran maksimal 3 MB per berkas.</p>
            <div class="form-row">
                <?php foreach (REQUIRED_FILES as $key => $label): ?>
                    <div class="form-group">
                        <label><?= e($label) ?> <span class="req">*</span></label>
                        <input type="file" name="<?= $key ?>" accept=".pdf,.jpg,.jpeg,.png" required>
                        <?php if (!empty($errors[$key])): ?><small style="color:var(--danger)"><?= e($errors[$key]) ?></small><?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="form-section">
            <div class="form-section-title">E. Password Akun</div>
            <p style="color:var(--muted); font-size:13px;">Buat password untuk mengakses halaman cek status pendaftaran Anda.</p>
            <div class="form-group" style="max-width:300px;">
                <label>Password <span class="req">*</span></label>
                <input type="password" name="password" minlength="6" required>
                <small>Minimal 6 karakter</small>
                <?php if (!empty($errors['password'])): ?><small style="color:var(--danger)"><?= e($errors['password']) ?></small><?php endif; ?>
            </div>
        </div>

        <div style="display:flex; gap:10px; margin-top:20px;">
            <button type="submit" class="btn btn-primary">Kirim Pendaftaran</button>
            <a href="index.php" class="btn btn-secondary">Batal</a>
        </div>
    </form>
</div>

<?php endif; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
