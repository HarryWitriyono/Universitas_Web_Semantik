<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/koneksi.php';

wajib_role(['ADMIN']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: mahasiswa.php'); exit;
}
csrf_verify();

$id_mahasiswa = filter_input(INPUT_POST, 'id_mahasiswa', FILTER_VALIDATE_INT);
if (!$id_mahasiswa) {
    $_SESSION['error'] = 'ID mahasiswa tidak valid.';
    header('Location: mahasiswa.php'); exit;
}

$stmt = mysqli_prepare($koneksi,
    "SELECT nama_mahasiswa, npm FROM mahasiswa WHERE id_mahasiswa = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, 'i', $id_mahasiswa);
mysqli_stmt_execute($stmt);
$data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$data) {
    $_SESSION['error'] = 'Data mahasiswa tidak ditemukan.';
    header('Location: mahasiswa.php'); exit;
}

$stmt = mysqli_prepare($koneksi, "DELETE FROM mahasiswa WHERE id_mahasiswa = ?");
mysqli_stmt_bind_param($stmt, 'i', $id_mahasiswa);

if (mysqli_stmt_execute($stmt)) {
    $_SESSION['success'] = 'Mahasiswa "' . $data['npm'] . ' - ' . $data['nama_mahasiswa'] . '" berhasil dihapus.';
} else {
    $_SESSION['error'] = 'Gagal menghapus mahasiswa: ' . mysqli_stmt_error($stmt);
}
mysqli_stmt_close($stmt);
header('Location: mahasiswa.php'); exit;
