<?php
/**
 * logout.php
 * Logout untuk SOA Consumer SIM Mahasiswa.
 *
 * Tidak menggunakan koneksi.php dan tidak mengakses database secara langsung.
 * Session dikelola oleh aplikasi consumer ini.
 */

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/csrf.php';

// Logout hanya boleh diproses melalui POST dari form topbar.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    header('Location: dashboard.php');
    exit;
}

// Validasi CSRF sebelum menghapus session.
try {
    csrf_verify();
} catch (Throwable $e) {
    http_response_code(419);
    exit('Permintaan logout tidak valid. Silakan kembali ke dashboard dan coba lagi.');
}

// Hapus seluruh data session pada consumer.
$_SESSION = [];

// Hapus cookie session dengan parameter cookie yang sedang digunakan.
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        [
            'expires'  => time() - 42000,
            'path'     => $params['path'] ?? '/',
            'domain'   => $params['domain'] ?? '',
            'secure'   => (bool) ($params['secure'] ?? false),
            'httponly' => (bool) ($params['httponly'] ?? true),
            'samesite' => $params['samesite'] ?? 'Lax',
        ]
    );
}

// Hancurkan session server-side.
session_destroy();

// Kembali ke halaman utama consumer.
header('Location: index.php');
exit;
