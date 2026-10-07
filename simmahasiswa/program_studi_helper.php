<?php
require_once 'auth.php';
wajib_role(['ADMIN']);

function ambil_universitas_semua($koneksi) {
    $sql = "SELECT id_universitas, kode_universitas, nama_universitas
            FROM universitas ORDER BY nama_universitas";
    return mysqli_query($koneksi, $sql);
}

function ambil_fakultas_semua($koneksi) {
    $sql = "SELECT f.id_fakultas, f.id_universitas, f.kode_fakultas, f.nama_fakultas,
                   u.kode_universitas, u.nama_universitas
            FROM fakultas f
            INNER JOIN universitas u ON u.id_universitas = f.id_universitas
            ORDER BY u.nama_universitas, f.nama_fakultas";
    return mysqli_query($koneksi, $sql);
}

function fakultas_valid($koneksi, $id_fakultas, $id_universitas) {
    $stmt = mysqli_prepare($koneksi,
        "SELECT id_fakultas FROM fakultas
         WHERE id_fakultas = ? AND id_universitas = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'ii', $id_fakultas, $id_universitas);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $ok = mysqli_num_rows($result) === 1;
    mysqli_stmt_close($stmt);
    return $ok;
}

function kode_prodi_tersedia($koneksi, $kode, $id_fakultas, $id_prodi = 0) {
    if ($id_prodi > 0) {
        $stmt = mysqli_prepare($koneksi,
            "SELECT id_program_studi FROM program_studi
             WHERE id_fakultas = ? AND kode_program_studi = ?
             AND id_program_studi <> ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 'isi', $id_fakultas, $kode, $id_prodi);
    } else {
        $stmt = mysqli_prepare($koneksi,
            "SELECT id_program_studi FROM program_studi
             WHERE id_fakultas = ? AND kode_program_studi = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 'is', $id_fakultas, $kode);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $ok = mysqli_num_rows($result) === 0;
    mysqli_stmt_close($stmt);
    return $ok;
}

function prodi_digunakan($koneksi, $id_prodi) {
    $stmt = mysqli_prepare($koneksi,
        "SELECT COUNT(*) AS jumlah FROM mahasiswa WHERE id_program_studi = ?");
    mysqli_stmt_bind_param($stmt, 'i', $id_prodi);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    if ((int)$row['jumlah'] > 0) {
        return true;
    }

    $stmt = mysqli_prepare($koneksi,
        "SELECT COUNT(*) AS jumlah FROM pengguna WHERE id_program_studi = ?");
    mysqli_stmt_bind_param($stmt, 'i', $id_prodi);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    return (int)$row['jumlah'] > 0;
}

function redirect_gagal($pesan) {
    header('Location: program_studi.php?pesan=gagal&detail=' . urlencode($pesan));
    exit;
}