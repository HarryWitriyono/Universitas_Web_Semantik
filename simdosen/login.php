<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/koneksi.php';

if (!isset($conn) || !($conn instanceof mysqli)) {
    http_response_code(500);
    exit('Koneksi database belum tersedia. Periksa koneksi.php.');
}
$conn->set_charset('utf8mb4');

// Jika sudah login, arahkan ke dashboard (dibuat pada tahap berikutnya).
if (isset($_SESSION['user_id'], $_SESSION['role'])) {
    redirect('dashboard.php');
}

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $token = $_POST['csrf_token'] ?? null;

    if (!csrf_validate(is_string($token) ? $token : null)) {
        $error = 'Sesi formulir tidak valid atau sudah kedaluwarsa. Silakan muat ulang halaman.';
    } elseif ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } else {
        $stmt = $conn->prepare(
            "SELECT id_pengguna, nama_lengkap, username, password_hash, peran, aktif
             FROM sd_pengguna
             WHERE username = ?
             LIMIT 1"
        );

        if (!$stmt) {
            error_log('SIM Dosen login prepare error: ' . $conn->error);
            $error = 'Login belum dapat diproses. Silakan coba lagi.';
        } else {
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result ? $result->fetch_assoc() : null;
            $stmt->close();

            // Pesan dibuat umum agar tidak membocorkan keberadaan username.
            if (!$user || !password_verify($password, $user['password_hash']) || (int)$user['aktif'] !== 1) {
                $error = 'Username atau password tidak sesuai, atau akun tidak aktif.';
            } else {
                session_regenerate_id(true);
                $_SESSION['user_id'] = (int)$user['id_pengguna'];
                $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['peran'];

                // Token sesi/form sebelumnya tidak dipakai ulang setelah autentikasi.
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                redirect('dashboard.php');
            }
        }
    }
}
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login | SIM Dosen UMB</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
:root{--blue:#1458b8;--navy:#0b2d59}
body{min-height:100vh;background:linear-gradient(135deg,#f5f8fe,#eaf2ff);color:#1b2b43;font-family:system-ui,-apple-system,"Segoe UI",sans-serif}
.login-wrap{min-height:100vh;display:grid;place-items:center;padding:24px}
.login-card{width:100%;max-width:460px;border:1px solid #e3eaf4;border-radius:22px;background:#fff;box-shadow:0 22px 65px rgba(17,53,100,.12);overflow:hidden}
.login-head{padding:30px 32px 22px;background:linear-gradient(135deg,#0b2d59,#1458b8);color:white}
.brand-icon{display:grid;place-items:center;width:54px;height:54px;border-radius:15px;background:rgba(255,255,255,.16);font-size:1.7rem}
.login-body{padding:30px 32px}
.form-label{font-weight:650;font-size:.92rem}
.form-control{border-radius:10px;padding:.78rem .9rem;border-color:#d9e2ef}
.form-control:focus{border-color:#7eaae9;box-shadow:0 0 0 .2rem rgba(20,88,184,.12)}
.btn-primary{background:var(--blue);border-color:var(--blue);border-radius:10px;padding:.8rem;font-weight:750}
.small-muted{color:#718096;font-size:.85rem}
@media(max-width:480px){.login-head,.login-body{padding:24px}}
</style>
</head>
<body>
<div class="login-wrap">
    <main class="login-card">
        <header class="login-head">
            <div class="brand-icon mb-3"><i class="bi bi-mortarboard-fill"></i></div>
            <div class="small text-white-50 fw-semibold mb-1">UNIVERSITAS MUHAMMADIYAH BENGKULU</div>
            <h1 class="h3 fw-bold mb-2">Login SIM Dosen</h1>
            <p class="mb-0 text-white-50">Masuk untuk melanjutkan ke sistem manajemen dosen.</p>
        </header>
        <div class="login-body">
            <?php if ($error !== ''): ?>
                <div class="alert alert-danger" role="alert"><?= e($error) ?></div>
            <?php endif; ?>
            <form method="post" action="login.php" autocomplete="on">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-person"></i></span>
                        <input class="form-control" type="text" id="username" name="username"
                               value="<?= e($username) ?>" maxlength="50" autocomplete="username" required autofocus>
                    </div>
                </div>
                <div class="mb-4">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-lock"></i></span>
                        <input class="form-control" type="password" id="password" name="password"
                               autocomplete="current-password" required>
                    </div>
                </div>
                <button class="btn btn-primary w-100" type="submit"><i class="bi bi-box-arrow-in-right me-2"></i>Login</button>
            </form>
            <div class="text-center small-muted mt-4">
                <i class="bi bi-shield-check me-1"></i>Akses terbatas untuk pengguna terdaftar.
            </div>
            <div class="text-center mt-3"><a class="text-decoration-none small" href="index.php"><i class="bi bi-arrow-left me-1"></i>Kembali ke halaman utama</a></div>
        </div>
    </main>
</div>
</body>
</html>
