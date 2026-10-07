<?php
session_start();
require_once 'csrf.php';

if (isset($_SESSION['login']) && $_SESSION['login'] === true) {
    header("Location: dashboard.php");
    exit;
}

$error = $_SESSION['login_error'] ?? '';
unset($_SESSION['login_error']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SIM Mahasiswa</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="login-page">
    <div class="login-wrapper">
        <div class="login-info">
            <div class="brand">
                <div class="brand-logo">UMB</div>
                <div>
                    <strong>SIM Mahasiswa</strong>
                    <small>Universitas Muhammadiyah Bengkulu</small>
                </div>
            </div>
            <h1>Selamat Datang</h1>
            <p>Silakan masuk menggunakan akun yang telah diberikan kepada Anda.</p>
            <a href="index.php" class="back-link">← Kembali ke halaman utama</a>
        </div>

        <div class="login-card">
            <h2>Login</h2>
            <p class="muted">Masukkan username dan password.</p>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form action="auth.php" method="post" autocomplete="off">
                <?= csrf_field() ?>

                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required autofocus>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Masuk</button>
            </form>
        </div>
    </div>
</body>
</html>
