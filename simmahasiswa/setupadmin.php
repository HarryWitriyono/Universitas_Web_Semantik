<?php
/**
 * setupadmin.php
 *
 * Setup awal akun Administrator.
 * File ini hanya dapat digunakan jika belum ada akun ADMIN.
 *
 * Setelah berhasil:
 * 1. Login menggunakan akun yang dibuat.
 * 2. Hapus file setupadmin.php dari server.
 *
 * Keamanan:
 * - Password menggunakan password_hash() / PASSWORD_DEFAULT.
 * - Menggunakan prepared statement.
 * - Tidak membuat password default.
 * - Tidak dapat membuat ADMIN kedua melalui file ini.
 */

session_start();
require_once 'koneksi.php';

$error = '';
$success = '';

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/* Pastikan role ADMIN tersedia */
$stmt = mysqli_prepare(
    $koneksi,
    "SELECT id_role FROM roles WHERE kode_role = 'ADMIN' LIMIT 1"
);

if (!$stmt) {
    die("Kesalahan database: " . e(mysqli_error($koneksi)));
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$role = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$role) {
    die("Role ADMIN belum tersedia. Silakan import dump database terlebih dahulu.");
}

$id_role_admin = (int)$role['id_role'];

/* Cek apakah ADMIN sudah ada */
$stmt = mysqli_prepare(
    $koneksi,
    "SELECT id_pengguna, username, nama_lengkap
     FROM pengguna
     WHERE id_role = ?
     LIMIT 1"
);

mysqli_stmt_bind_param($stmt, "i", $id_role_admin);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$admin_existing = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

/*
 * Jika ADMIN sudah ada, halaman dikunci.
 * Ini membuat setupadmin.php menjadi setup satu kali.
 */
if ($admin_existing) {
    $locked = true;
} else {
    $locked = false;
}

if (!$locked && $_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    if ($username === '' || $nama_lengkap === '' || $password === '') {
        $error = 'Username, nama lengkap, dan password wajib diisi.';
    } elseif (strlen($username) < 4) {
        $error = 'Username minimal 4 karakter.';
    } elseif (strlen($password) < 8) {
        $error = 'Password minimal 8 karakter.';
    } elseif ($password !== $password_confirm) {
        $error = 'Konfirmasi password tidak sama.';
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    }

    /* Cek username */
    if ($error === '') {
        $stmt = mysqli_prepare(
            $koneksi,
            "SELECT id_pengguna FROM pengguna WHERE username = ? LIMIT 1"
        );

        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_fetch_assoc($result)) {
            $error = 'Username tersebut sudah digunakan.';
        }

        mysqli_stmt_close($stmt);
    }

    if ($error === '') {

        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = mysqli_prepare(
            $koneksi,
            "INSERT INTO pengguna
            (
                id_role,
                id_universitas,
                username,
                password_hash,
                nama_lengkap,
                email,
                status_aktif
            )
            VALUES (?, 1, ?, ?, ?, NULLIF(?, ''), 'Aktif')"
        );

        if (!$stmt) {
            $error = 'Gagal menyiapkan proses pembuatan administrator.';
        } else {

            mysqli_stmt_bind_param(
                $stmt,
                "issss",
                $id_role_admin,
                $username,
                $password_hash,
                $nama_lengkap,
                $email
            );

            if (mysqli_stmt_execute($stmt)) {
                $success = 'Administrator berhasil dibuat. Silakan login menggunakan akun tersebut.';
                $locked = true;
            } else {
                $error = 'Gagal membuat administrator: ' . mysqli_stmt_error($stmt);
            }

            mysqli_stmt_close($stmt);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Administrator - SIM Mahasiswa</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: Arial, Helvetica, sans-serif;
            background: #f3f7f4;
            color: #1f2937;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 25px;
        }

        .card {
            width: min(560px, 100%);
            background: #fff;
            border-radius: 16px;
            padding: 35px;
            box-shadow: 0 15px 45px rgba(0,0,0,.10);
            border: 1px solid #e5e7eb;
        }

        .logo {
            width: 52px;
            height: 52px;
            border-radius: 13px;
            background: #166534;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            margin-bottom: 20px;
        }

        h1 {
            margin: 0 0 8px;
            font-size: 28px;
        }

        .description {
            color: #6b7280;
            line-height: 1.6;
            margin-bottom: 25px;
        }

        label {
            display: block;
            font-weight: 600;
            margin: 17px 0 7px;
        }

        input {
            width: 100%;
            padding: 12px 13px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 15px;
        }

        input:focus {
            outline: 2px solid #bbf7d0;
            border-color: #166534;
        }

        button {
            width: 100%;
            border: 0;
            border-radius: 8px;
            background: #166534;
            color: #fff;
            padding: 13px;
            margin-top: 24px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
        }

        button:hover {
            background: #14532d;
        }

        .alert {
            padding: 13px 15px;
            border-radius: 8px;
            margin: 15px 0;
            line-height: 1.5;
        }

        .danger {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .success {
            background: #ecfdf5;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .locked {
            background: #fff7ed;
            color: #9a3412;
            border: 1px solid #fed7aa;
            padding: 15px;
            border-radius: 8px;
            line-height: 1.6;
        }

        .back {
            display: inline-block;
            margin-top: 20px;
            color: #166534;
            font-weight: 600;
        }

        .note {
            font-size: 13px;
            color: #6b7280;
            margin-top: 20px;
            line-height: 1.6;
        }
    </style>
</head>
<body>

<div class="card">

    <div class="logo">UMB</div>

    <h1>Setup Administrator</h1>

    <p class="description">
        Buat akun Administrator pertama untuk
        <strong>Sistem Informasi Manajemen Mahasiswa</strong>.
    </p>

    <?php if ($success): ?>
        <div class="alert success">
            <?= e($success) ?>
        </div>

        <a class="back" href="login.php">→ Login ke sistem</a>

        <p class="note">
            Demi keamanan, hapus file <strong>setupadmin.php</strong>
            dari folder aplikasi setelah setup selesai.
        </p>

    <?php elseif ($locked): ?>

        <div class="locked">
            Setup Administrator sudah dikunci karena akun ADMIN telah tersedia.
            <br><br>
            Akun administrator:
            <strong><?= e($admin_existing['username']) ?></strong>
        </div>

        <a class="back" href="login.php">→ Ke halaman login</a>

        <p class="note">
            File setupadmin.php tidak diperlukan lagi setelah administrator
            pertama berhasil dibuat. Hapus file ini dari server.
        </p>

    <?php else: ?>

        <?php if ($error): ?>
            <div class="alert danger">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <form method="post" autocomplete="off">

            <label for="username">Username Administrator</label>
            <input
                type="text"
                id="username"
                name="username"
                minlength="4"
                maxlength="100"
                required
                value="<?= e($_POST['username'] ?? '') ?>"
            >

            <label for="nama_lengkap">Nama Lengkap</label>
            <input
                type="text"
                id="nama_lengkap"
                name="nama_lengkap"
                maxlength="200"
                required
                value="<?= e($_POST['nama_lengkap'] ?? '') ?>"
            >

            <label for="email">Email</label>
            <input
                type="email"
                id="email"
                name="email"
                maxlength="150"
                value="<?= e($_POST['email'] ?? '') ?>"
            >

            <label for="password">Password</label>
            <input
                type="password"
                id="password"
                name="password"
                minlength="8"
                required
            >

            <label for="password_confirm">Konfirmasi Password</label>
            <input
                type="password"
                id="password_confirm"
                name="password_confirm"
                minlength="8"
                required
            >

            <button type="submit">
                Buat Administrator
            </button>

        </form>

        <p class="note">
            Password tidak disimpan dalam bentuk plaintext.
            Sistem menggunakan <strong>password_hash()</strong> dengan
            algoritma default PHP.
        </p>

    <?php endif; ?>

</div>

</body>
</html>
