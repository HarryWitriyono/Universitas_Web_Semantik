<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/config/database.php';

$error = '';
$success = false;
$setupLocked = false;

try {
    $db = simskripsi_db();
    // Tabel ini sengaja dibuat saat setup agar setup bisa dijalankan setelah dump utama diimpor.
    $db->query("CREATE TABLE IF NOT EXISTS admin_lokal (
        id_admin BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        nama_lengkap VARCHAR(150) NOT NULL,
        username VARCHAR(80) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        status_aktif ENUM('aktif','tidak_aktif') NOT NULL DEFAULT 'aktif',
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $db->query("CREATE TABLE IF NOT EXISTS akun_dosen (
        id_akun_dosen BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        id_dosen BIGINT UNSIGNED NOT NULL UNIQUE,
        username VARCHAR(80) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        status_aktif ENUM('aktif','tidak_aktif') NOT NULL DEFAULT 'aktif',
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_akun_dosen_dosen FOREIGN KEY (id_dosen) REFERENCES dosen(id_dosen) ON UPDATE CASCADE ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $db->query("CREATE TABLE IF NOT EXISTS konfigurasi_aplikasi (
        id_konfigurasi BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        nama_konfigurasi VARCHAR(100) NOT NULL UNIQUE,
        nilai_konfigurasi TEXT NOT NULL,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        updated_by BIGINT UNSIGNED NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $db->query("CREATE TABLE IF NOT EXISTS status_instalasi (
        id_status TINYINT UNSIGNED PRIMARY KEY,
        selesai TINYINT(1) NOT NULL DEFAULT 0,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $db->query("INSERT IGNORE INTO status_instalasi (id_status, selesai) VALUES (1, 0)");
    $count = (int)$db->query('SELECT COUNT(*) AS total FROM admin_lokal')->fetch_assoc()['total'];
    $status = $db->query('SELECT selesai FROM status_instalasi WHERE id_status = 1')->fetch_assoc();
    $setupLocked = $count > 0 || (int)($status['selesai'] ?? 0) === 1;
} catch (Throwable $e) {
    error_log('SIMSKRIPSI setup error: ' . $e->getMessage());
    $error = 'Database belum siap. Pastikan database simskripsi sudah dibuat, dump SQL utama sudah diimpor, dan config/database.php sudah benar.';
}

if (!$setupLocked && $error === '' && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    csrf_verify();
    $nama = trim((string)($_POST['nama_lengkap'] ?? ''));
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $password2 = (string)($_POST['password_confirm'] ?? '');
    $apiBase = trim((string)($_POST['api_base_url'] ?? ''));

    if ($nama === '' || $username === '' || $password === '' || $apiBase === '') {
        $error = 'Semua kolom wajib diisi.';
    } elseif (!preg_match('/^[A-Za-z0-9._-]{4,80}$/', $username)) {
        $error = 'Username harus 4–80 karakter dan hanya boleh berisi huruf, angka, titik, garis bawah, atau tanda hubung.';
    } elseif (strlen($password) < 12) {
        $error = 'Password admin minimal 12 karakter.';
    } elseif ($password !== $password2) {
        $error = 'Konfirmasi password tidak sama.';
    } else {
        $parts = parse_url($apiBase);
        $validUrl = is_array($parts) && isset($parts['scheme'], $parts['host']) && in_array(strtolower($parts['scheme']), ['http','https'], true) && !isset($parts['user']) && !isset($parts['pass']) && !isset($parts['query']) && !isset($parts['fragment']);
        if (!$validUrl) {
            $error = 'Alamat API harus berupa URL http:// atau https:// yang valid, tanpa username/password, query, atau fragment.';
        } else {
            try {
                $apiBase = rtrim($apiBase, '/');
                $db = simskripsi_db();
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare('INSERT INTO admin_lokal (nama_lengkap, username, password_hash) VALUES (?, ?, ?)');
                $stmt->bind_param('sss', $nama, $username, $hash);
                $stmt->execute();
                $adminId = (int)$db->insert_id;
                $stmt->close();

                $key = 'api_base_url';
                $stmt = $db->prepare('INSERT INTO konfigurasi_aplikasi (nama_konfigurasi, nilai_konfigurasi, updated_by) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE nilai_konfigurasi = VALUES(nilai_konfigurasi), updated_by = VALUES(updated_by)');
                $stmt->bind_param('ssi', $key, $apiBase, $adminId);
                $stmt->execute();
                $stmt->close();
                $db->query('UPDATE status_instalasi SET selesai = 1 WHERE id_status = 1');
                $db->close();
                $success = true;
                $setupLocked = true;
            } catch (Throwable $e) {
                error_log('SIMSKRIPSI create admin error: ' . $e->getMessage());
                $error = 'Administrator belum berhasil dibuat. Periksa log PHP dan pastikan setup belum pernah diselesaikan.';
            }
        }
    }
}
function setup_e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
?>
<!doctype html>
<html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Setup Admin | SIMSKRIPSI</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>body{background:#f2f6fc;font-family:system-ui,-apple-system,Segoe UI,sans-serif}.setup-card{max-width:680px;margin:5vh auto;background:#fff;border:1px solid #e4eaf2;border-radius:18px;box-shadow:0 18px 55px #142d5514;padding:clamp(22px,4vw,42px)}.brand{color:#0866d8;font-weight:800;letter-spacing:.3px}.form-label{font-weight:600}.muted{color:#68758a;font-size:.92rem}</style></head>
<body><main class="setup-card"><div class="brand mb-2">SIMSKRIPSI UMB</div><h1 class="h3 mb-2">Setup Administrator</h1><p class="muted mb-4">Instalasi awal aplikasi, pembuatan admin lokal, dan konfigurasi dasar Web API SIM Mahasiswa.</p>
<?php if ($success): ?><div class="alert alert-success"><strong>Setup berhasil.</strong> Akun Admin SIMSKRIPSI dan konfigurasi API telah disimpan. Demi keamanan, hapus atau ubah nama file <code>setupadmin.php</code> setelah pengujian. <div class="mt-3"><a class="btn btn-primary" href="login.php">Lanjut ke Login</a></div></div>
<?php elseif ($setupLocked): ?><div class="alert alert-warning">Setup telah dikunci karena administrator sudah tersedia atau instalasi telah ditandai selesai. Untuk keamanan, halaman ini tidak dapat digunakan kembali. <a href="login.php">Buka halaman login</a>.</div>
<?php else: ?>
<?php if ($error !== ''): ?><div class="alert alert-danger"><?php echo setup_e($error); ?></div><?php endif; ?>
<div class="alert alert-info small">Pastikan dump SQL utama SIMSKRIPSI sudah diimpor terlebih dahulu. Nilai default API lokal adalah <code>http://localhost/simmahasiswa/api</code>.</div>
<form method="post" autocomplete="off"><?php echo csrf_field(); ?>
<div class="mb-3"><label class="form-label" for="nama_lengkap">Nama administrator</label><input class="form-control" id="nama_lengkap" name="nama_lengkap" maxlength="150" required></div>
<div class="mb-3"><label class="form-label" for="username">Username admin</label><input class="form-control" id="username" name="username" minlength="4" maxlength="80" pattern="[A-Za-z0-9._-]{4,80}" required></div>
<div class="mb-3"><label class="form-label" for="password">Password admin</label><input class="form-control" type="password" id="password" name="password" minlength="12" autocomplete="new-password" required><div class="form-text">Minimal 12 karakter. Gunakan password unik.</div></div>
<div class="mb-3"><label class="form-label" for="password_confirm">Konfirmasi password</label><input class="form-control" type="password" id="password_confirm" name="password_confirm" minlength="12" autocomplete="new-password" required></div>
<div class="mb-4"><label class="form-label" for="api_base_url">Alamat dasar Web API SIM Mahasiswa</label><input class="form-control" type="url" id="api_base_url" name="api_base_url" value="http://localhost/simmahasiswa/api" required><div class="form-text">Contoh hosting: https://domain-anda.example/simmahasiswa/api</div></div>
<button class="btn btn-primary w-100" type="submit">Simpan Konfigurasi dan Buat Admin</button>
</form><?php endif; ?><hr class="my-4"><p class="muted mb-0">Setelah setup, jangan biarkan file ini terbuka untuk instalasi ulang. Gunakan HTTPS pada server produksi.</p></main></body></html>
