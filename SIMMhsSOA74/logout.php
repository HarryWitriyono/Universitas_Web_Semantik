<?php
session_start();
require_once 'koneksi.php';
require_once 'csrf.php';

csrf_verify();

/* Audit logout sebelum session dihancurkan */
if (!empty($_SESSION['id_pengguna'])) {
    $id_pengguna = (int) $_SESSION['id_pengguna'];

    $audit = mysqli_prepare(
        $koneksi,
        "INSERT INTO audit_log
        (id_pengguna, tabel_nama, record_id, aksi, ip_address, user_agent)
        VALUES (?, 'pengguna', ?, 'LOGOUT', ?, ?)"
    );

    if ($audit) {
        $record_id = (string) $id_pengguna;
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $agent = $_SERVER['HTTP_USER_AGENT'] ?? null;

        mysqli_stmt_bind_param(
            $audit,
            "isss",
            $id_pengguna,
            $record_id,
            $ip,
            $agent
        );
        mysqli_stmt_execute($audit);
        mysqli_stmt_close($audit);
    }
}

$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

session_destroy();

header("Location: index.php");
exit;
