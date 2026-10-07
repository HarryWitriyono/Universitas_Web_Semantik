<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/koneksi.php';

wajib_role(['ADMIN']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: pengguna.php');
    exit;
}

csrf_verify();

$id_pengguna = filter_input(
    INPUT_POST,
    'id_pengguna',
    FILTER_VALIDATE_INT
);

if (!$id_pengguna) {
    $_SESSION['error'] = 'ID pengguna tidak valid.';
    header('Location: pengguna.php');
    exit;
}

/*
 * Jangan izinkan Admin menghapus akun yang sedang dipakai
 * untuk session login saat ini.
 */
$id_pengguna_session = (int)($_SESSION['id_pengguna'] ?? 0);

if ($id_pengguna === $id_pengguna_session) {
    $_SESSION['error'] = 'Akun yang sedang digunakan tidak dapat dihapus.';
    header('Location: pengguna.php');
    exit;
}

/* Ambil data sebelum dihapus */
$stmt = mysqli_prepare(
    $koneksi,
    "SELECT username, nama_lengkap
     FROM pengguna
     WHERE id_pengguna = ?
     LIMIT 1"
);

if (!$stmt) {
    $_SESSION['error'] = 'Gagal memeriksa pengguna.';
    header('Location: pengguna.php');
    exit;
}

mysqli_stmt_bind_param($stmt, 'i', $id_pengguna);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$data_pengguna = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$data_pengguna) {
    $_SESSION['error'] = 'Data pengguna tidak ditemukan.';
    header('Location: pengguna.php');
    exit;
}

$stmt = mysqli_prepare(
    $koneksi,
    "DELETE FROM pengguna
     WHERE id_pengguna = ?"
);

if (!$stmt) {
    $_SESSION['error'] = 'Gagal menyiapkan proses hapus.';
    header('Location: pengguna.php');
    exit;
}

mysqli_stmt_bind_param($stmt, 'i', $id_pengguna);

if (mysqli_stmt_execute($stmt)) {
    $_SESSION['success'] =
        'Pengguna "' .
        $data_pengguna['nama_lengkap'] .
        '" berhasil dihapus.';
} else {
    $_SESSION['error'] =
        'Gagal menghapus pengguna: ' .
        mysqli_stmt_error($stmt);
}

mysqli_stmt_close($stmt);

header('Location: pengguna.php');
exit;
