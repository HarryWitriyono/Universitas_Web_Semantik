<?php
session_start();
require_once 'koneksi.php';
require_once 'csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit;
}

try {
    csrf_verify();
} catch (Throwable $e) {
    $_SESSION['login_error'] = 'Permintaan tidak valid. Silakan coba lagi.';
    header("Location: login.php");
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if ($username === '' || $password === '') {
    $_SESSION['login_error'] = 'Username dan password wajib diisi.';
    header("Location: login.php");
    exit;
}

/*
 * Query menggunakan prepared statement.
 * Password diverifikasi dengan password_verify().
 */
$sql = "SELECT
            p.id_pengguna,
            p.id_role,
            p.id_universitas,
            p.id_fakultas,
            p.id_program_studi,
            p.username,
            p.password_hash,
            p.nama_lengkap,
            p.email,
            p.status_aktif,
            r.kode_role,
            r.nama_role
        FROM pengguna p
        INNER JOIN roles r ON p.id_role = r.id_role
        WHERE p.username = ?
        LIMIT 1";

$stmt = mysqli_prepare($koneksi, $sql);

if (!$stmt) {
    $_SESSION['login_error'] = 'Terjadi kesalahan sistem.';
    header("Location: login.php");
    exit;
}

mysqli_stmt_bind_param($stmt, "s", $username);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

if (!$user || $user['status_aktif'] !== 'Aktif' || !password_verify($password, $user['password_hash'])) {
    $_SESSION['login_error'] = 'Username atau password salah.';
    mysqli_stmt_close($stmt);
    header("Location: login.php");
    exit;
}

mysqli_stmt_close($stmt);

session_regenerate_id(true);

$_SESSION['login'] = true;
$_SESSION['id_pengguna'] = (int) $user['id_pengguna'];
$_SESSION['id_role'] = (int) $user['id_role'];
$_SESSION['kode_role'] = $user['kode_role'];
$_SESSION['nama_role'] = $user['nama_role'];
$_SESSION['id_universitas'] = $user['id_universitas'];
$_SESSION['id_fakultas'] = $user['id_fakultas'];
$_SESSION['id_program_studi'] = $user['id_program_studi'];
$_SESSION['username'] = $user['username'];
$_SESSION['nama_lengkap'] = $user['nama_lengkap'];
$_SESSION['email'] = $user['email'];

/* Update waktu login */
$update = mysqli_prepare(
    $koneksi,
    "UPDATE pengguna SET last_login = NOW() WHERE id_pengguna = ?"
);

if ($update) {
    mysqli_stmt_bind_param($update, "i", $user['id_pengguna']);
    mysqli_stmt_execute($update);
    mysqli_stmt_close($update);
}

/* Audit login */
$audit = mysqli_prepare(
    $koneksi,
    "INSERT INTO audit_log
    (id_pengguna, tabel_nama, record_id, aksi, ip_address, user_agent)
    VALUES (?, 'pengguna', ?, 'LOGIN', ?, ?)"
);

if ($audit) {
    $record_id = (string) $user['id_pengguna'];
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $agent = $_SERVER['HTTP_USER_AGENT'] ?? null;

    mysqli_stmt_bind_param(
        $audit,
        "isss",
        $user['id_pengguna'],
        $record_id,
        $ip,
        $agent
    );
    mysqli_stmt_execute($audit);
    mysqli_stmt_close($audit);
}

header("Location: dashboard.php");
exit;
