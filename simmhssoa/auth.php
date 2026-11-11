<?php
/**
 * auth.php
 * Proses login SOA Consumer SIM Mahasiswa.
 * Tidak melakukan koneksi atau query langsung ke database.
 */

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/api_client.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

try {
    csrf_verify();
} catch (Throwable $e) {
    $_SESSION['login_error'] = 'Permintaan tidak valid. Silakan coba lagi.';
    header('Location: login.php');
    exit;
}

$username = trim((string) ($_POST['username'] ?? ''));
$password = (string) ($_POST['password'] ?? '');

if ($username === '' || $password === '') {
    $_SESSION['login_error'] = 'Username dan password wajib diisi.';
    header('Location: login.php');
    exit;
}

// Meminta autentikasi kepada SOA Server.
$api = soa_request('apilogin.php', [
    'username' => $username,
    'password' => $password
]);

if ($api['error'] !== null) {
    error_log('SOA login connection error: ' . $api['error']);
    $_SESSION['login_error'] = 'Layanan login sedang tidak dapat dihubungi. Silakan coba kembali.';
    header('Location: login.php');
    exit;
}

$body = $api['data'];

if (
    $api['http_code'] !== 200 ||
    empty($body['success']) ||
    empty($body['data']['pengguna']) ||
    !is_array($body['data']['pengguna'])
) {
    // Pesan API yang aman untuk ditampilkan kepada pengguna.
    $message = $body['message'] ?? 'Username atau password salah.';
    $_SESSION['login_error'] = $message;
    header('Location: login.php');
    exit;
}

$user = $body['data']['pengguna'];

// Regenerasi ID session untuk mencegah session fixation.
session_regenerate_id(true);

// Session dibuat dan dikelola oleh aplikasi consumer ini.
$_SESSION['login'] = true;
$_SESSION['id_pengguna'] = (int) ($user['id_pengguna'] ?? 0);
$_SESSION['id_role'] = (int) ($user['id_role'] ?? 0);
$_SESSION['kode_role'] = (string) ($user['kode_role'] ?? '');
$_SESSION['nama_role'] = (string) ($user['nama_role'] ?? '');
$_SESSION['id_universitas'] = $user['id_universitas'] ?? null;
$_SESSION['id_fakultas'] = $user['id_fakultas'] ?? null;
$_SESSION['id_program_studi'] = $user['id_program_studi'] ?? null;
$_SESSION['username'] = (string) ($user['username'] ?? '');
$_SESSION['nama_lengkap'] = (string) ($user['nama_lengkap'] ?? '');
$_SESSION['email'] = $user['email'] ?? null;
$_SESSION['mahasiswa'] = $user['mahasiswa'] ?? null;
$_SESSION['login_at'] = $body['data']['login_at'] ?? date('Y-m-d H:i:s');

// Tidak menyimpan password maupun password_hash di session.
header('Location: dashboard.php');
exit;
