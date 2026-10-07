<?php
require_once 'auth_check.php';
require_once 'koneksi.php';

wajib_role(['ADMIN']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: role.php");
    exit;
}

csrf_verify();

$id_role = filter_input(INPUT_POST, 'id_role', FILTER_VALIDATE_INT);

if (!$id_role) {
    $_SESSION['error'] = 'ID role tidak valid.';
    header("Location: role.php");
    exit;
}

/*
 * Role yang sudah dipakai pengguna tidak boleh dihapus.
 * Selain menjaga integritas FK, ini mencegah konfigurasi RBAC
 * pengguna menjadi tidak jelas.
 */
$stmt = mysqli_prepare(
    $koneksi,
    "SELECT COUNT(*) AS total
     FROM pengguna
     WHERE id_role = ?"
);
mysqli_stmt_bind_param($stmt, "i", $id_role);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if ((int)$row['total'] > 0) {
    $_SESSION['error'] = 'Role tidak dapat dihapus karena masih digunakan oleh pengguna.';
    header("Location: role.php");
    exit;
}

/* Lindungi role inti sistem */
$stmt = mysqli_prepare(
    $koneksi,
    "SELECT kode_role FROM roles WHERE id_role = ? LIMIT 1"
);
mysqli_stmt_bind_param($stmt, "i", $id_role);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$role = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$role) {
    $_SESSION['error'] = 'Role tidak ditemukan.';
    header("Location: role.php");
    exit;
}

$role_inti = ['ADMIN', 'OPERATOR_PRODI', 'DEKANAT', 'REKTORAT', 'MAHASISWA'];

if (in_array($role['kode_role'], $role_inti, true)) {
    $_SESSION['error'] = 'Role inti sistem tidak boleh dihapus.';
    header("Location: role.php");
    exit;
}

$stmt = mysqli_prepare(
    $koneksi,
    "DELETE FROM roles WHERE id_role = ?"
);
mysqli_stmt_bind_param($stmt, "i", $id_role);

if (mysqli_stmt_execute($stmt)) {
    $_SESSION['success'] = 'Role berhasil dihapus.';
} else {
    $_SESSION['error'] = 'Gagal menghapus role: ' . mysqli_stmt_error($stmt);
}

mysqli_stmt_close($stmt);

header("Location: role.php");
exit;
