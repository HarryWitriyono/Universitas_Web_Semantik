<?php
/**
 * security.php - lapisan keamanan bersama untuk SIMMAHASISWA Demo Secure.
 * Dipakai sebelum output HTML pada setiap halaman.
 */

if (session_status() !== PHP_SESSION_ACTIVE) {
    $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// Security headers dasar. CSP dibuat cukup longgar untuk demo karena Chart.js memakai CDN.
if (!headers_sent()) {
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function csrf_token(): string {
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function verify_csrf(): void {
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        http_response_code(419);
        exit('Permintaan ditolak: CSRF token tidak valid. Muat ulang halaman lalu coba lagi.');
    }
}

function require_login(): void {
    if (empty($_SESSION['id_pengguna'])) {
        header('Location: login.php');
        exit();
    }
}

function require_admin(): void {
    require_login();
    if (($_SESSION['kode_role'] ?? '') !== 'ADMIN') {
        http_response_code(403);
        echo '<!doctype html><html lang="id"><head><meta charset="utf-8"><title>403 - Akses Ditolak</title></head><body style="font-family:Segoe UI,sans-serif;padding:40px"><h1>403 - Akses Ditolak</h1><p>Anda tidak memiliki hak untuk mengakses halaman ini.</p><p><a href="dashboard.php">Kembali ke Dashboard</a></p></body></html>';
        exit();
    }
}
