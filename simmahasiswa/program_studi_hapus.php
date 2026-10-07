<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/csrf.php';

wajib_role(['ADMIN']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: program_studi.php');
    exit;
}

try {
    csrf_verify();

    $id = (int)($_POST['id_program_studi'] ?? 0);

    if ($id <= 0) {
        throw new RuntimeException('ID program studi tidak valid.');
    }

    $stmt = mysqli_prepare(
        $koneksi,
        "SELECT
            ps.id_program_studi,
            ps.id_fakultas,
            ps.kode_program_studi,
            ps.nama_program_studi,
            ps.jenjang,
            ps.status_aktif
         FROM program_studi ps
         WHERE ps.id_program_studi = ?
         LIMIT 1"
    );
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $data_lama_row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$data_lama_row) {
        throw new RuntimeException('Data program studi tidak ditemukan.');
    }

    $cek_mahasiswa = mysqli_prepare(
        $koneksi,
        "SELECT COUNT(*) AS jumlah
         FROM mahasiswa
         WHERE id_program_studi = ?"
    );
    mysqli_stmt_bind_param($cek_mahasiswa, 'i', $id);
    mysqli_stmt_execute($cek_mahasiswa);
    $r_mhs = mysqli_stmt_get_result($cek_mahasiswa);
    $jumlah_mahasiswa = (int)(mysqli_fetch_assoc($r_mhs)['jumlah'] ?? 0);
    mysqli_stmt_close($cek_mahasiswa);

    if ($jumlah_mahasiswa > 0) {
        throw new RuntimeException(
            'Program Studi tidak dapat dihapus karena masih memiliki ' .
            $jumlah_mahasiswa . ' data mahasiswa.'
        );
    }

    $cek_pengguna = mysqli_prepare(
        $koneksi,
        "SELECT COUNT(*) AS jumlah
         FROM pengguna
         WHERE id_program_studi = ?"
    );
    mysqli_stmt_bind_param($cek_pengguna, 'i', $id);
    mysqli_stmt_execute($cek_pengguna);
    $r_pengguna = mysqli_stmt_get_result($cek_pengguna);
    $jumlah_pengguna = (int)(mysqli_fetch_assoc($r_pengguna)['jumlah'] ?? 0);
    mysqli_stmt_close($cek_pengguna);

    if ($jumlah_pengguna > 0) {
        throw new RuntimeException(
            'Program Studi tidak dapat dihapus karena masih digunakan oleh ' .
            $jumlah_pengguna . ' pengguna.'
        );
    }

    $stmt = mysqli_prepare(
        $koneksi,
        "DELETE FROM program_studi WHERE id_program_studi = ?"
    );
    mysqli_stmt_bind_param($stmt, 'i', $id);

    if (!mysqli_stmt_execute($stmt)) {
        throw new RuntimeException('Program Studi gagal dihapus.');
    }

    mysqli_stmt_close($stmt);

    $data_lama = json_encode([
        'id_fakultas' => (int)$data_lama_row['id_fakultas'],
        'kode_program_studi' => $data_lama_row['kode_program_studi'],
        'nama_program_studi' => $data_lama_row['nama_program_studi'],
        'jenjang' => $data_lama_row['jenjang'],
        'status_aktif' => $data_lama_row['status_aktif']
    ], JSON_UNESCAPED_UNICODE);

    $audit = mysqli_prepare(
        $koneksi,
        "INSERT INTO audit_log
            (id_pengguna, tabel_nama, record_id, aksi, data_lama, ip_address, user_agent)
         VALUES (?, 'program_studi', ?, 'DELETE', ?, ?, ?)"
    );

    if ($audit) {
        $id_pengguna = (int)($_SESSION['id_pengguna'] ?? 0);
        $record_id = (string)$id;
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        mysqli_stmt_bind_param($audit, 'issss', $id_pengguna, $record_id, $data_lama, $ip, $agent);
        mysqli_stmt_execute($audit);
        mysqli_stmt_close($audit);
    }

    $_SESSION['success'] = 'Program Studi berhasil dihapus.';
} catch (Throwable $e) {
    $_SESSION['error'] = $e->getMessage();
}

header('Location: program_studi.php');
exit;
