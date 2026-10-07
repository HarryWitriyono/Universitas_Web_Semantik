<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/koneksi.php';

wajib_role(['ADMIN']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: fakultas.php');
    exit;
}

csrf_verify();

$id_fakultas = filter_input(INPUT_POST, 'id_fakultas', FILTER_VALIDATE_INT);

if (!$id_fakultas) {
    $_SESSION['error'] = 'ID fakultas tidak valid.';
    header('Location: fakultas.php');
    exit;
}

/* Jangan hapus fakultas yang masih memiliki Program Studi. */
$stmt = mysqli_prepare(
    $koneksi,
    "SELECT COUNT(*) AS total FROM program_studi WHERE id_fakultas = ?"
);
mysqli_stmt_bind_param($stmt, 'i', $id_fakultas);
mysqli_stmt_execute($stmt);
$data_prodi = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if ((int)$data_prodi['total'] > 0) {
    $_SESSION['error'] = 'Fakultas tidak dapat dihapus karena masih memiliki Program Studi.';
    header('Location: fakultas.php');
    exit;
}

/* Jangan hapus fakultas yang masih menjadi scope pengguna. */
$stmt = mysqli_prepare(
    $koneksi,
    "SELECT COUNT(*) AS total FROM pengguna WHERE id_fakultas = ?"
);
mysqli_stmt_bind_param($stmt, 'i', $id_fakultas);
mysqli_stmt_execute($stmt);
$data_pengguna = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if ((int)$data_pengguna['total'] > 0) {
    $_SESSION['error'] = 'Fakultas tidak dapat dihapus karena masih digunakan oleh pengguna.';
    header('Location: fakultas.php');
    exit;
}

$stmt = mysqli_prepare(
    $koneksi,
    "SELECT nama_fakultas FROM fakultas WHERE id_fakultas = ? LIMIT 1"
);
mysqli_stmt_bind_param($stmt, 'i', $id_fakultas);
mysqli_stmt_execute($stmt);
$data_fakultas = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$data_fakultas) {
    $_SESSION['error'] = 'Data fakultas tidak ditemukan.';
    header('Location: fakultas.php');
    exit;
}

$stmt = mysqli_prepare(
    $koneksi,
    "DELETE FROM fakultas WHERE id_fakultas = ?"
);
mysqli_stmt_bind_param($stmt, 'i', $id_fakultas);

if (mysqli_stmt_execute($stmt)) {
    $_SESSION['success'] = 'Fakultas "' . $data_fakultas['nama_fakultas'] . '" berhasil dihapus.';
} else {
    $_SESSION['error'] = 'Gagal menghapus fakultas: ' . mysqli_stmt_error($stmt);
}

mysqli_stmt_close($stmt);
header('Location: fakultas.php');
exit;
