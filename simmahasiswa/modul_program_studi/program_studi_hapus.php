<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/csrf.php';

wajib_role(['ADMIN']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: program_studi.php?pesan=gagal&detail=' . urlencode('Metode permintaan tidak valid.'));
    exit;
}

try {
    csrf_verify();
} catch (Throwable $e) {
    header('Location: program_studi.php?pesan=gagal&detail=' . urlencode('Permintaan tidak valid.'));
    exit;
}

$id = (int) ($_POST['id'] ?? 0);

if ($id <= 0) {
    header('Location: program_studi.php?pesan=gagal&detail=' . urlencode('ID Program Studi tidak valid.'));
    exit;
}

/*
 * Jika Program Studi sudah dipakai oleh data mahasiswa,
 * foreign key akan mencegah penghapusan. Kita tampilkan pesan yang ramah.
 */
$stmt = mysqli_prepare($koneksi, "DELETE FROM program_studi WHERE id_program_studi = ?");

if (!$stmt) {
    header('Location: program_studi.php?pesan=gagal&detail=' . urlencode('Gagal menyiapkan penghapusan data.'));
    exit;
}

mysqli_stmt_bind_param($stmt, 'i', $id);
$berhasil = mysqli_stmt_execute($stmt);
$error_no = mysqli_stmt_errno($stmt);
mysqli_stmt_close($stmt);

if ($berhasil) {
    header('Location: program_studi.php?pesan=hapus');
    exit;
}

if ($error_no === 1451) {
    $detail = 'Program Studi tidak dapat dihapus karena masih digunakan oleh data lain.';
} else {
    $detail = 'Program Studi tidak dapat dihapus.';
}

header('Location: program_studi.php?pesan=gagal&detail=' . urlencode($detail));
exit;
