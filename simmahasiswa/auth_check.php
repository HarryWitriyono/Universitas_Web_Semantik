<?php
/*
 * File helper autentikasi.
 * Dapat dipanggil pada halaman yang membutuhkan login.
 */

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/csrf.php';

if (empty($_SESSION['login']) || $_SESSION['login'] !== true) {
    header("Location: login.php");
    exit;
}

function wajib_role(array $roles): void
{
    $role = $_SESSION['kode_role'] ?? '';

    if (!in_array($role, $roles, true)) {
        http_response_code(403);
        die('403 - Anda tidak memiliki hak akses ke halaman ini.');
    }
}
