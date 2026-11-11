<?php
/**
 * File Setup Admin Awal - SIMMAHASISWA
 * Gunakan file ini sekali untuk menginisialisasi/reset akun Administrator.
 */
require_once 'security.php';

require_once 'koneksi.php';

$message = '';
$status  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['setup'])) {
    verify_csrf();
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $nama     = trim($_POST['nama_lengkap']);
    $email    = trim($_POST['email']);

    if (!empty($username) && !empty($password) && !empty($nama)) {
        // Enkripsi password menggunakan BCRYPT
        $password_hash = password_hash($password, PASSWORD_BCRYPT);

        // 1. Ambil ID role ADMIN dari database, jangan mengandalkan ID numerik tetap.
        $role_stmt = $koneksi->prepare("SELECT id_role FROM roles WHERE kode_role = 'ADMIN' LIMIT 1");
        $role_stmt->execute();
        $role = $role_stmt->get_result()->fetch_assoc();
        $role_stmt->close();
        if (!$role) { throw new RuntimeException('Role ADMIN belum tersedia.'); }
        $id_role = (int)$role['id_role'];

        // 2. Ambil ID Universitas awal (default: 1)
        $id_univ = 1; // Sesuaikan jika database demo memakai ID universitas berbeda.

        // Cek apakah username sudah ada
        $check_stmt = $koneksi->prepare("SELECT id_pengguna FROM pengguna WHERE username = ?");
        $check_stmt->bind_param('s', $username);
        $check_stmt->execute();
        $check_res = $check_stmt->get_result();

        if ($check_res->num_rows > 0) {
            // Update password & data admin yang sudah ada
            $update_stmt = $koneksi->prepare("UPDATE pengguna SET password_hash = ?, nama_lengkap = ?, email = ?, status_aktif = 'Aktif' WHERE username = ?");
            $update_stmt->bind_param('ssss', $password_hash, $nama, $email, $username);
            
            if ($update_stmt->execute()) {
                $status  = 'success';
                $message = "Akun Admin <strong>{$username}</strong> berhasil diperbarui/reset!";
            } else {
                $status  = 'error';
                $message = "Gagal memperbarui akun: " . $koneksi->error;
            }
            $update_stmt->close();
        } else {
            // Buat akun Admin baru
            $insert_stmt = $koneksi->prepare("INSERT INTO pengguna (id_role, id_universitas, username, password_hash, nama_lengkap, email, status_aktif) VALUES (?, ?, ?, ?, ?, ?, 'Aktif')");
            $insert_stmt->bind_param('iissss', $id_role, $id_univ, $username, $password_hash, $nama, $email);

            if ($insert_stmt->execute()) {
                $status  = 'success';
                $message = "Akun Admin <strong>{$username}</strong> berhasil dibuat!";
            } else {
                $status  = 'error';
                $message = "Gagal membuat akun admin: " . $koneksi->error;
            }
            $insert_stmt->close();
        }
        $check_stmt->close();
    } else {
        $status  = 'error';
        $message = "Semua kolom wajib diisi!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Administrator - SIMMAHASISWA</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        body { background: #002b49; display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; }
        .card { background: white; width: 100%; max-width: 450px; padding: 30px; border-radius: 8px; box-shadow: 0 8px 20px rgba(0,0,0,0.2); }
        .card h2 { color: #002b49; text-align: center; margin-bottom: 5px; }
        .card p { color: #666; font-size: 13px; text-align: center; margin-bottom: 20px; }
        .alert { padding: 12px; border-radius: 4px; font-size: 13px; margin-bottom: 20px; }
        .alert-success { background: #e8f5e9; color: #2e7d32; border-left: 4px solid #2e7d32; }
        .alert-error { background: #ffebee; color: #c62828; border-left: 4px solid #c62828; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-size: 12px; font-weight: bold; color: #333; margin-bottom: 5px; text-transform: uppercase; }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px; outline: none; }
        .form-group input:focus { border-color: #002b49; }
        .btn-submit { width: 100%; background: #ffb703; color: #002b49; border: none; padding: 12px; border-radius: 4px; font-weight: bold; cursor: pointer; font-size: 14px; transition: 0.3s; }
        .btn-submit:hover { background: #ffa200; }
        .btn-login { display: block; text-align: center; margin-top: 15px; font-size: 13px; color: #002b49; font-weight: bold; text-decoration: none; }
    </style>
</head>
<body>

    <div class="card">
        <h2>Setup Administrator</h2>
        <p>Inisialisasi atau Reset Akun Admin SIMMAHASISWA</p>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?= $status; ?>">
                <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <form action="setupadmin.php" method="POST">
            <?= csrf_field(); ?>
            <div class="form-group">
                <label for="username">Username Admin</label>
                <input type="text" id="username" name="username" value="Admin" required>
            </div>
            <div class="form-group">
                <label for="nama_lengkap">Nama Lengkap</label>
                <input type="text" id="nama_lengkap" name="nama_lengkap" value="Administrator Utama" required>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="admin@umb.ac.id" required>
            </div>
            <div class="form-group">
                <label for="password">Password Baru</label>
                <input type="password" id="password" name="password" placeholder="Masukkan password admin baru" required>
            </div>
            <button type="submit" name="setup" class="btn-submit">Simpan / Reset Admin</button>
        </form>

        <a href="login.php" class="btn-login">&rarr; Ke Halaman Login</a>
    </div>

</body>
</html>