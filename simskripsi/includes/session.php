<?php
declare(strict_types=1);

/**
 * Pengaturan sesi bersama SIMSKRIPSI.
 * Sertakan file ini sebelum output HTML.
 */
if (session_status() !== PHP_SESSION_ACTIVE) {
    $https = (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443)
    );

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $https, // Otomatis true saat HTTPS aktif
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}
