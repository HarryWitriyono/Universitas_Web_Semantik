<?php
require_once 'auth.php';

function mahasiswa_scope_where($role, $id_universitas, $id_fakultas, $id_program_studi, &$params, &$types) {
    $where = [];
    $params = [];
    $types = '';

    if ($role === 'ADMIN') {
        return '';
    }

    if ($role === 'REKTORAT') {
        $where[] = 'u.id_universitas = ?';
        $params[] = $id_universitas;
        $types .= 'i';
    } elseif ($role === 'DEKANAT') {
        $where[] = 'f.id_fakultas = ?';
        $params[] = $id_fakultas;
        $types .= 'i';
    } elseif ($role === 'OPERATOR_PRODI') {
        $where[] = 'ps.id_program_studi = ?';
        $params[] = $id_program_studi;
        $types .= 'i';
    } elseif ($role === 'MAHASISWA') {
        $where[] = 'm.id_pengguna = ?';
        $params[] = $_SESSION['id_pengguna'] ?? 0;
        $types .= 'i';
    } else {
        $where[] = '1 = 0';
    }

    return $where ? ' WHERE ' . implode(' AND ', $where) : '';
}

function ambil_universitas_semua($koneksi) {
    return mysqli_query($koneksi,
        "SELECT id_universitas, kode_universitas, nama_universitas
         FROM universitas ORDER BY nama_universitas");
}

function ambil_fakultas_semua($koneksi) {
    return mysqli_query($koneksi,
        "SELECT id_fakultas, id_universitas, kode_fakultas, nama_fakultas
         FROM fakultas ORDER BY nama_fakultas");
}

function ambil_prodi_semua($koneksi) {
    return mysqli_query($koneksi,
        "SELECT id_program_studi, id_fakultas, kode_program_studi, nama_program_studi, jenjang
         FROM program_studi ORDER BY nama_program_studi");
}

function prodi_valid($koneksi, $id_program_studi, $id_fakultas, $id_universitas) {
    $stmt = mysqli_prepare($koneksi,
        "SELECT ps.id_program_studi
         FROM program_studi ps
         INNER JOIN fakultas f ON f.id_fakultas = ps.id_fakultas
         WHERE ps.id_program_studi = ? AND f.id_fakultas = ? AND f.id_universitas = ?
         LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'iii', $id_program_studi, $id_fakultas, $id_universitas);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $ok = mysqli_num_rows($result) === 1;
    mysqli_stmt_close($stmt);
    return $ok;
}

function npm_tersedia($koneksi, $npm, $id_mahasiswa = 0) {
    if ($id_mahasiswa > 0) {
        $stmt = mysqli_prepare($koneksi,
            "SELECT id_mahasiswa FROM mahasiswa
             WHERE npm = ? AND id_mahasiswa <> ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 'si', $npm, $id_mahasiswa);
    } else {
        $stmt = mysqli_prepare($koneksi,
            "SELECT id_mahasiswa FROM mahasiswa WHERE npm = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 's', $npm);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $ok = mysqli_num_rows($result) === 0;
    mysqli_stmt_close($stmt);
    return $ok;
}

function mahasiswa_boleh_kelola($role) {
    return in_array($role, ['ADMIN', 'OPERATOR_PRODI'], true);
}

function mahasiswa_boleh_tambah($role) {
    return in_array($role, ['ADMIN', 'OPERATOR_PRODI'], true);
}

function ambil_data_mahasiswa($koneksi, $id_mahasiswa) {
    $stmt = mysqli_prepare($koneksi,
        "SELECT m.*, ps.id_fakultas, f.id_universitas,
                ps.kode_program_studi, ps.nama_program_studi,
                f.kode_fakultas, f.nama_fakultas,
                u.kode_universitas, u.nama_universitas
         FROM mahasiswa m
         INNER JOIN program_studi ps ON ps.id_program_studi = m.id_program_studi
         INNER JOIN fakultas f ON f.id_fakultas = ps.id_fakultas
         INNER JOIN universitas u ON u.id_universitas = f.id_universitas
         WHERE m.id_mahasiswa = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'i', $id_mahasiswa);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $data = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return $data;
}

function data_mahasiswa_dalam_scope($koneksi, $id_mahasiswa) {
    $data = ambil_data_mahasiswa($koneksi, $id_mahasiswa);
    if (!$data) return false;

    $role = $_SESSION['kode_role'] ?? '';
    if ($role === 'ADMIN') return true;
    if ($role === 'REKTORAT') return (int)$data['id_universitas'] === (int)($_SESSION['id_universitas'] ?? 0);
    if ($role === 'DEKANAT') return (int)$data['id_fakultas'] === (int)($_SESSION['id_fakultas'] ?? 0);
    if ($role === 'OPERATOR_PRODI') return (int)$data['id_program_studi'] === (int)($_SESSION['id_program_studi'] ?? 0);
    if ($role === 'MAHASISWA') return (int)$data['id_pengguna'] === (int)($_SESSION['id_pengguna'] ?? 0);
    return false;
}