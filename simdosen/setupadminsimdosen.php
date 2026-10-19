<?php
/*
 * setupadminsimdosen.php
 * Setup awal admin dan konfigurasi API SIM Mahasiswa untuk SIM Dosen.
 *
 * Prasyarat:
 * - Buat database sim_dosen terlebih dahulu.
 * - Siapkan koneksi.php yang mendefinisikan $conn sebagai objek mysqli.
 * - Hapus/ubah nama file setup ini setelah setup berhasil.
 *
 * Catatan:
 * - Tabel diberi awalan sd_ agar tidak berbenturan dengan tabel proyek lain.
 * - Sesuaikan nama endpoint dan format respons API dengan implementasi SIM Mahasiswa.
 */
session_start();
require_once __DIR__ . '/koneksi.php';

if (!isset($conn) || !($conn instanceof mysqli)) {
    http_response_code(500);
    exit('Koneksi database tidak ditemukan. Pastikan koneksi.php menyediakan variabel $conn (mysqli).');
}
$conn->set_charset('utf8mb4');

function h($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
function tableExists(mysqli $conn, string $table): bool {
    $sql = "SHOW TABLES LIKE ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $table);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result && $result->num_rows > 0;
    $stmt->close();
    return $exists;
}

$errors = [];
$success = '';
$alreadySetup = false;

/* Tabel khusus setup: tidak mengubah tabel dosen yang sudah ada. */
$sqlAdmin = "CREATE TABLE IF NOT EXISTS sd_pengguna (
    id_pengguna INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nama_lengkap VARCHAR(120) NOT NULL,
    username VARCHAR(50) NOT NULL,
    email VARCHAR(150) DEFAULT NULL,
    password_hash VARCHAR(255) NOT NULL,
    peran ENUM('ADMIN') NOT NULL DEFAULT 'ADMIN',
    aktif TINYINT(1) NOT NULL DEFAULT 1,
    dibuat_pada TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    diperbarui_pada TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_pengguna),
    UNIQUE KEY uq_sd_pengguna_username (username),
    UNIQUE KEY uq_sd_pengguna_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

$sqlApi = "CREATE TABLE IF NOT EXISTS sd_pengaturan_api (
    id_pengaturan TINYINT UNSIGNED NOT NULL PRIMARY KEY,
    nama_layanan VARCHAR(100) NOT NULL DEFAULT 'SIM Mahasiswa',
    base_url VARCHAR(255) NOT NULL,
    endpoint_fakultas VARCHAR(255) NOT NULL DEFAULT '/api/fakultas.php',
    endpoint_programstudi VARCHAR(255) NOT NULL DEFAULT '/api/programstudi.php',
    api_key VARCHAR(255) DEFAULT NULL,
    timeout_detik SMALLINT UNSIGNED NOT NULL DEFAULT 10,
    aktif TINYINT(1) NOT NULL DEFAULT 1,
    diperbarui_pada TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if (!$conn->query($sqlAdmin)) {
    $errors[] = 'Gagal menyiapkan tabel admin: ' . $conn->error;
}
if (!$conn->query($sqlApi)) {
    $errors[] = 'Gagal menyiapkan tabel konfigurasi API: ' . $conn->error;
}

$adminCount = 0;
if (!$errors) {
    $result = $conn->query("SELECT COUNT(*) AS jumlah FROM sd_pengguna WHERE peran='ADMIN'");
    if ($result) {
        $adminCount = (int)$result->fetch_assoc()['jumlah'];
    }
    $alreadySetup = $adminCount > 0;
}

$defaultBaseUrl = 'http://IP-SERVER-SIM-MAHASISWA/simmahasiswa';
$current = [
    'nama_layanan' => 'SIM Mahasiswa',
    'base_url' => $defaultBaseUrl,
    'endpoint_fakultas' => '/api/fakultas.php',
    'endpoint_programstudi' => '/api/programstudi.php',
    'timeout_detik' => 10,
    'aktif' => 1,
];
if (!$errors) {
    $res = $conn->query("SELECT * FROM sd_pengaturan_api WHERE id_pengaturan=1 LIMIT 1");
    if ($res && $res->num_rows) {
        $current = array_merge($current, $res->fetch_assoc());
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$errors) {
    $csrf = (string)($_POST['csrf_token'] ?? '');
    if (!isset($_SESSION['csrf_setup']) || !hash_equals($_SESSION['csrf_setup'], $csrf)) {
        $errors[] = 'Token keamanan tidak valid. Muat ulang halaman dan coba lagi.';
    } else {
        $action = (string)($_POST['action'] ?? '');
        $namaLayanan = trim((string)($_POST['nama_layanan'] ?? 'SIM Mahasiswa'));
        $baseUrl = rtrim(trim((string)($_POST['base_url'] ?? '')), '/');
        $endpointFakultas = '/' . ltrim(trim((string)($_POST['endpoint_fakultas'] ?? '/api/fakultas.php')), '/');
        $endpointProdi = '/' . ltrim(trim((string)($_POST['endpoint_programstudi'] ?? '/api/programstudi.php')), '/');
        $apiKey = trim((string)($_POST['api_key'] ?? ''));
        $timeout = (int)($_POST['timeout_detik'] ?? 10);
        $aktifApi = isset($_POST['aktif_api']) ? 1 : 0;

        if ($namaLayanan === '') $errors[] = 'Nama layanan API wajib diisi.';
        if (!filter_var($baseUrl, FILTER_VALIDATE_URL) || !in_array(parse_url($baseUrl, PHP_URL_SCHEME), ['http', 'https'], true)) {
            $errors[] = 'Base URL harus berupa URL lengkap yang valid, misalnya http://192.168.1.10/simmahasiswa.';
        }
        if ($timeout < 1 || $timeout > 60) $errors[] = 'Timeout harus antara 1 sampai 60 detik.';
        if ($endpointFakultas === '/' || $endpointProdi === '/') $errors[] = 'Endpoint fakultas dan program studi wajib diisi.';

        if ($action === 'create_admin') {
            if ($alreadySetup) $errors[] = 'Admin sudah dibuat. Pembuatan admin awal dikunci.';
            $nama = trim((string)($_POST['nama_lengkap'] ?? ''));
            $username = trim((string)($_POST['username'] ?? ''));
            $email = trim((string)($_POST['email'] ?? ''));
            $password = (string)($_POST['password'] ?? '');
            $password2 = (string)($_POST['password2'] ?? '');

            if ($nama === '') $errors[] = 'Nama admin wajib diisi.';
            if (!preg_match('/^[A-Za-z0-9._-]{4,50}$/', $username)) $errors[] = 'Username 4–50 karakter; gunakan huruf, angka, titik, garis bawah, atau tanda minus.';
            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Format email tidak valid.';
            if (strlen($password) < 12) $errors[] = 'Password admin minimal 12 karakter.';
            if ($password !== $password2) $errors[] = 'Konfirmasi password tidak sama.';

            if (!$errors) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO sd_pengguna (nama_lengkap, username, email, password_hash, peran, aktif) VALUES (?, ?, NULLIF(?, ''), ?, 'ADMIN', 1)");
                $stmt->bind_param('ssss', $nama, $username, $email, $hash);
                if (!$stmt->execute()) {
                    $errors[] = 'Admin gagal dibuat. Pastikan username/email belum digunakan.';
                }
                $stmt->close();
            }
        } elseif ($action !== 'save_api') {
            $errors[] = 'Aksi tidak dikenal.';
        }

        if (!$errors) {
            $stmt = $conn->prepare("INSERT INTO sd_pengaturan_api
                (id_pengaturan, nama_layanan, base_url, endpoint_fakultas, endpoint_programstudi, api_key, timeout_detik, aktif)
                VALUES (1, ?, ?, ?, ?, NULLIF(?, ''), ?, ?)
                ON DUPLICATE KEY UPDATE nama_layanan=VALUES(nama_layanan), base_url=VALUES(base_url),
                endpoint_fakultas=VALUES(endpoint_fakultas), endpoint_programstudi=VALUES(endpoint_programstudi),
                api_key=VALUES(api_key), timeout_detik=VALUES(timeout_detik), aktif=VALUES(aktif)");
            $stmt->bind_param('sssssii', $namaLayanan, $baseUrl, $endpointFakultas, $endpointProdi, $apiKey, $timeout, $aktifApi);
            if (!$stmt->execute()) {
                $errors[] = 'Konfigurasi API gagal disimpan: ' . $stmt->error;
            }
            $stmt->close();

            if (!$errors) {
                $success = ($action === 'create_admin')
                    ? 'Admin awal berhasil dibuat dan konfigurasi API SIM Mahasiswa disimpan.'
                    : 'Konfigurasi API SIM Mahasiswa berhasil disimpan.';
                $alreadySetup = true;
                $adminCount = 1;
                $current = [
                    'nama_layanan' => $namaLayanan,
                    'base_url' => $baseUrl,
                    'endpoint_fakultas' => $endpointFakultas,
                    'endpoint_programstudi' => $endpointProdi,
                    'timeout_detik' => $timeout,
                    'aktif' => $aktifApi,
                ];
            }
        }
    }
}

if (empty($_SESSION['csrf_setup'])) {
    $_SESSION['csrf_setup'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_setup'];
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Setup SIM Dosen UMB</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
body{background:#f3f7fc;color:#1b2b43;font-family:system-ui,-apple-system,"Segoe UI",sans-serif}
.setup-wrap{max-width:1000px;margin:42px auto;padding:0 16px}
.brand{display:flex;align-items:center;gap:12px;margin-bottom:24px}
.brand-icon{width:50px;height:50px;border-radius:14px;background:#1458b8;color:#fff;display:grid;place-items:center;font-size:1.5rem}
.panel{background:#fff;border:1px solid #e4ebf4;border-radius:18px;box-shadow:0 12px 36px rgba(20,50,90,.06)}
.panel-head{padding:22px 24px;border-bottom:1px solid #edf1f6}
.panel-body{padding:24px}
.form-label{font-weight:650;font-size:.92rem}
.form-control,.form-select{padding:.72rem .85rem;border-color:#d9e2ef;border-radius:10px}
.form-control:focus{border-color:#7eaae9;box-shadow:0 0 0 .2rem rgba(20,88,184,.12)}
.btn-primary{background:#1458b8;border-color:#1458b8;border-radius:10px;font-weight:700;padding:.75rem 1rem}
.hint{font-size:.84rem;color:#697991}
.step-title{font-size:1.05rem;font-weight:800;color:#0b2d59}
code{color:#1458b8}
</style>
</head>
<body>
<div class="setup-wrap">
    <div class="brand">
        <div class="brand-icon"><i class="bi bi-mortarboard-fill"></i></div>
        <div><div class="fw-bold fs-4">Setup SIM Dosen</div><div class="text-secondary small">Universitas Muhammadiyah Bengkulu</div></div>
    </div>

    <?php if ($success): ?><div class="alert alert-success"><i class="bi bi-check-circle-fill me-2"></i><?= h($success) ?></div><?php endif; ?>
    <?php if ($errors): ?><div class="alert alert-danger"><strong>Periksa kembali:</strong><ul class="mb-0 mt-2"><?php foreach ($errors as $error): ?><li><?= h($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <?php if ($errors && !empty($current)): ?>
      <script>/* Form tetap menampilkan konfigurasi terakhir yang tersimpan. */</script>
    <?php endif; ?>

    <div class="alert alert-warning">
        <i class="bi bi-shield-exclamation me-2"></i><strong>Penting:</strong> jalankan halaman ini hanya saat instalasi awal. Setelah selesai, hapus atau ganti nama <code>setupadminsimdosen.php</code> dan batasi akses ke halaman pengaturan API.
    </div>

    <div class="panel mb-4">
        <div class="panel-head"><div class="step-title"><i class="bi bi-person-lock me-2"></i>1. Pengguna Admin</div><div class="hint mt-1">Password disimpan menggunakan password_hash(), bukan plaintext.</div></div>
        <div class="panel-body">
        <?php if ($alreadySetup): ?>
            <div class="alert alert-success mb-0"><i class="bi bi-lock-fill me-2"></i>Admin awal sudah dibuat. Form pembuatan admin dikunci untuk mencegah pembuatan admin ulang dari halaman setup.</div>
        <?php else: ?>
            <form method="post" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                <input type="hidden" name="action" value="create_admin">
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label">Nama lengkap admin</label><input class="form-control" name="nama_lengkap" required maxlength="120" placeholder="Administrator SIM Dosen"></div>
                    <div class="col-md-6"><label class="form-label">Username</label><input class="form-control" name="username" required minlength="4" maxlength="50" pattern="[A-Za-z0-9._-]{4,50}" autocomplete="username" placeholder="admin.simdosen"></div>
                    <div class="col-md-6"><label class="form-label">Email (opsional)</label><input class="form-control" type="email" name="email" maxlength="150" placeholder="admin@umb.ac.id"></div>
                    <div class="col-md-6"><label class="form-label">Password admin</label><input class="form-control" type="password" name="password" required minlength="12" autocomplete="new-password"><div class="hint mt-1">Minimal 12 karakter.</div></div>
                    <div class="col-md-6"><label class="form-label">Ulangi password</label><input class="form-control" type="password" name="password2" required minlength="12" autocomplete="new-password"></div>
                </div>
                <div class="mt-4"><button class="btn btn-primary" type="submit"><i class="bi bi-person-check me-2"></i>Buat Admin & Simpan API</button></div>
            </form>
        <?php endif; ?>
        </div>
    </div>

    <div class="panel">
        <div class="panel-head"><div class="step-title"><i class="bi bi-plug me-2"></i>2. Konfigurasi Web API SIM Mahasiswa</div><div class="hint mt-1">Isi alamat server dan endpoint yang benar-benar tersedia pada proyek SIM Mahasiswa.</div></div>
        <div class="panel-body">
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                <input type="hidden" name="action" value="save_api">
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label">Nama layanan</label><input class="form-control" name="nama_layanan" required value="<?= h($current['nama_layanan'] ?? 'SIM Mahasiswa') ?>"></div>
                    <div class="col-md-6"><label class="form-label">Timeout (detik)</label><input class="form-control" type="number" name="timeout_detik" min="1" max="60" required value="<?= h($current['timeout_detik'] ?? 10) ?>"></div>
                    <div class="col-12"><label class="form-label">Base URL SIM Mahasiswa</label><input class="form-control" name="base_url" required value="<?= h($current['base_url'] ?? $defaultBaseUrl) ?>" placeholder="http://192.168.1.10/simmahasiswa"><div class="hint mt-1">Gunakan alamat IP/nama host server yang dapat dijangkau dari server SIM Dosen, bukan localhost jika servernya berbeda.</div></div>
                    <div class="col-md-6"><label class="form-label">Endpoint fakultas</label><input class="form-control" name="endpoint_fakultas" required value="<?= h($current['endpoint_fakultas'] ?? '/api/fakultas.php') ?>" placeholder="/api/fakultas.php"></div>
                    <div class="col-md-6"><label class="form-label">Endpoint program studi</label><input class="form-control" name="endpoint_programstudi" required value="<?= h($current['endpoint_programstudi'] ?? '/api/programstudi.php') ?>" placeholder="/api/programstudi.php"></div>
                    <div class="col-12"><label class="form-label">API key (opsional)</label><input class="form-control" type="password" name="api_key" value="" autocomplete="new-password" placeholder="Kosongkan jika API belum menggunakan API key"><div class="hint mt-1">Jika API key lama sudah tersimpan, kosongkan kolom ini hanya jika memang ingin menghapusnya. Untuk produksi, rahasia sebaiknya disimpan di konfigurasi server dengan akses terbatas.</div></div>
                    <div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" name="aktif_api" id="aktif_api" <?= !empty($current['aktif']) ? 'checked' : '' ?>><label class="form-check-label" for="aktif_api">Aktifkan konfigurasi API</label></div></div>
                </div>
                <div class="mt-4 d-flex flex-wrap gap-2"><button class="btn btn-primary" type="submit"><i class="bi bi-save me-2"></i>Simpan Konfigurasi API</button><a href="index.php" class="btn btn-outline-secondary rounded-3 px-3 py-2">Kembali ke Landing Page</a></div>
            </form>
            <div class="alert alert-info mt-4 mb-0 small"><strong>Belum menguji koneksi:</strong> halaman ini hanya menyimpan konfigurasi. Pengujian koneksi dan pembacaan JSON perlu ditambahkan setelah format respons endpoint SIM Mahasiswa dipastikan.</div>
        </div>
    </div>
    <p class="text-center text-secondary small mt-4">SIM Dosen UMB · Setup awal aplikasi</p>
</div>
</body>
</html>
