<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/koneksi.php';

wajib_role(['ADMIN']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: universitas.php');
    exit;
}

csrf_verify();

$id_universitas = filter_input(
    INPUT_POST,
    'id_universitas',
    FILTER_VALIDATE_INT
);

if (!$id_universitas) {
    $_SESSION['error'] = 'ID universitas tidak valid.';
    header('Location: universitas.php');
    exit;
}

/*
 * Jangan hapus universitas yang masih mempunyai Fakultas.
 * Ini menjaga integritas relasi database dan mencegah data akademik
 * menjadi yatim.
 */
$stmt = mysqli_prepare(
    $koneksi,
    "SELECT COUNT(*) AS total
     FROM fakultas
     WHERE id_universitas = ?"
);

if (!$stmt) {
    $_SESSION['error'] = 'Gagal memeriksa data terkait.';
    header('Location: universitas.php');
    exit;
}

mysqli_stmt_bind_param($stmt, 'i', $id_universitas);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$data_terkait = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if ((int)$data_terkait['total'] > 0) {
    $_SESSION['error'] =
        'Universitas tidak dapat dihapus karena masih memiliki Fakultas.';
    header('Location: universitas.php');
    exit;
}

/* Ambil nama untuk pesan */
$stmt = mysqli_prepare(
    $koneksi,
    "SELECT nama_universitas
     FROM universitas
     WHERE id_universitas = ?
     LIMIT 1"
);

mysqli_stmt_bind_param($stmt, 'i', $id_universitas);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$data_universitas = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$data_universitas) {
    $_SESSION['error'] = 'Data universitas tidak ditemukan.';
    header('Location: universitas.php');
    exit;
}

$stmt = mysqli_prepare(
    $koneksi,
    "DELETE FROM universitas
     WHERE id_universitas = ?"
);

mysqli_stmt_bind_param($stmt, 'i', $id_universitas);

if (mysqli_stmt_execute($stmt)) {

    $_SESSION['success'] =
        'Universitas "' .
        $data_universitas['nama_universitas'] .
        '" berhasil dihapus.';

} else {

    $_SESSION['error'] =
        'Gagal menghapus universitas: ' .
        mysqli_stmt_error($stmt);
}

mysqli_stmt_close($stmt);

header('Location: universitas.php');
exit;
