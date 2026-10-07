<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/koneksi.php';

wajib_role(['ADMIN']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: mahasiswa.php');
    exit;
}

csrf_verify();

$id_mahasiswa = filter_input(
    INPUT_POST,
    'id_mahasiswa',
    FILTER_VALIDATE_INT
);

if (!$id_mahasiswa) {
    $_SESSION['error'] = 'ID mahasiswa tidak valid.';
    header('Location: mahasiswa.php');
    exit;
}

/* Ambil data sebelum dihapus. */
$stmt = mysqli_prepare(
    $koneksi,
    "SELECT npm, nama_mahasiswa
     FROM mahasiswa
     WHERE id_mahasiswa = ?
     LIMIT 1"
);

if (!$stmt) {
    $_SESSION['error'] = 'Gagal memeriksa data mahasiswa.';
    header('Location: mahasiswa.php');
    exit;
}

mysqli_stmt_bind_param(
    $stmt,
    'i',
    $id_mahasiswa
);

mysqli_stmt_execute($stmt);

$data_mahasiswa = mysqli_fetch_assoc(
    mysqli_stmt_get_result($stmt)
);

mysqli_stmt_close($stmt);

if (!$data_mahasiswa) {
    $_SESSION['error'] = 'Data mahasiswa tidak ditemukan.';
    header('Location: mahasiswa.php');
    exit;
}

/* Hapus mahasiswa. */
$stmt = mysqli_prepare(
    $koneksi,
    "DELETE FROM mahasiswa
     WHERE id_mahasiswa = ?"
);

if (!$stmt) {
    $_SESSION['error'] = 'Gagal menyiapkan proses hapus.';
    header('Location: mahasiswa.php');
    exit;
}

mysqli_stmt_bind_param(
    $stmt,
    'i',
    $id_mahasiswa
);

if (mysqli_stmt_execute($stmt)) {
    $_SESSION['success'] =
        'Mahasiswa "' .
        $data_mahasiswa['nama_mahasiswa'] .
        '" berhasil dihapus.';
} else {
    $_SESSION['error'] =
        'Gagal menghapus mahasiswa: ' .
        mysqli_stmt_error($stmt);
}

mysqli_stmt_close($stmt);

header('Location: mahasiswa.php');
exit;
