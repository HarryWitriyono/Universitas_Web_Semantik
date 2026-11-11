<?php
session_start();

if (isset($_SESSION['login']) && $_SESSION['login'] === true) {
    header("Location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIM Mahasiswa - Universitas Muhammadiyah Bengkulu</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="landing">
    <header class="landing-header">
        <div class="brand">
            <div class="brand-logo">UMB</div>
            <div>
                <strong>SIM Mahasiswa</strong>
                <small>Universitas Muhammadiyah Bengkulu</small>
            </div>
        </div>
        <a class="btn btn-primary" href="login.php">Login</a>
    </header>

    <main class="hero">
        <div class="hero-content">
            <span class="eyebrow">SISTEM INFORMASI MANAJEMEN</span>
            <h1>Data Mahasiswa<br>Per Program Studi</h1>
            <p>
                Sistem terintegrasi untuk pengelolaan dan penyajian data mahasiswa
                Universitas Muhammadiyah Bengkulu berdasarkan Program Studi,
                Fakultas, dan tingkat Universitas.
            </p>
            <div class="hero-actions">
                <a class="btn btn-primary btn-lg" href="login.php">Masuk ke Sistem</a>
                <a class="btn btn-outline btn-lg" href="#tentang">Tentang Sistem</a>
            </div>
        </div>

        <div class="hero-card">
            <div class="hero-card-icon">🎓</div>
            <h3>Portal Akademik Mahasiswa</h3>
            <p>Mahasiswa dapat melihat data pribadinya, sedangkan pengelolaan data dilakukan sesuai kewenangan masing-masing role.</p>
            <div class="role-list">
                <span>Admin</span>
                <span>Operator Prodi</span>
                <span>Dekanat</span>
                <span>Rektorat</span>
                <span>Mahasiswa</span>
            </div>
        </div>
    </main>

    <section id="tentang" class="landing-section">
        <h2>Pengelolaan Data Berbasis RBAC</h2>
        <p>
            Setiap pengguna memperoleh hak akses sesuai peran dan lingkup
            kewenangannya. Operator Program Studi hanya mengelola mahasiswa
            pada Program Studi yang menjadi tanggung jawabnya.
        </p>
    </section>

    <footer class="landing-footer">
        &copy; <?= date('Y') ?> Universitas Muhammadiyah Bengkulu
    </footer>
</body>
</html>
