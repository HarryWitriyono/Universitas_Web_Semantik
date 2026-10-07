<?php
/**
 * Helper RBAC Modul Mahasiswa
 * Mengikuti struktur database sim_mahasiswa.
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/koneksi.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function h_mhs(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function current_pengguna(mysqli $koneksi): ?array {
    $id = 0;
    foreach (['id_pengguna', 'user_id', 'id_user', 'pengguna_id'] as $key) {
        if (isset($_SESSION[$key]) && is_numeric($_SESSION[$key])) {
            $id = (int) $_SESSION[$key];
            if ($id > 0) break;
        }
    }

    $username = '';
    foreach (['username', 'user_username', 'nama_user'] as $key) {
        if (!empty($_SESSION[$key])) {
            $username = trim((string) $_SESSION[$key]);
            if ($username !== '') break;
        }
    }

    if ($id > 0) {
        $stmt = mysqli_prepare($koneksi, "
            SELECT p.*, r.kode_role, r.nama_role
            FROM pengguna p
            INNER JOIN roles r ON r.id_role = p.id_role
            WHERE p.id_pengguna = ?
            LIMIT 1
        ");
        mysqli_stmt_bind_param($stmt, 'i', $id);
    } elseif ($username !== '') {
        $stmt = mysqli_prepare($koneksi, "
            SELECT p.*, r.kode_role, r.nama_role
            FROM pengguna p
            INNER JOIN roles r ON r.id_role = p.id_role
            WHERE p.username = ?
            LIMIT 1
        ");
        mysqli_stmt_bind_param($stmt, 's', $username);
    } else {
        return null;
    }

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result) ?: null;
    mysqli_stmt_close($stmt);

    return $user;
}

function audit_mahasiswa(
    mysqli $koneksi,
    ?int $idPengguna,
    string $aksi,
    int $recordId,
    ?array $dataLama = null,
    ?array $dataBaru = null
): void {
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
    $lama = $dataLama !== null ? json_encode($dataLama, JSON_UNESCAPED_UNICODE) : null;
    $baru = $dataBaru !== null ? json_encode($dataBaru, JSON_UNESCAPED_UNICODE) : null;

    $stmt = mysqli_prepare($koneksi, "
        INSERT INTO audit_log
        (id_pengguna, tabel_nama, record_id, aksi, data_lama, data_baru, ip_address, user_agent)
        VALUES (?, 'mahasiswa', ?, ?, ?, ?, ?, ?)
    ");
    $record = (string) $recordId;
    mysqli_stmt_bind_param($stmt, 'issssss', $idPengguna, $record, $aksi, $lama, $baru, $ip, $ua);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

function get_mahasiswa_by_id(mysqli $koneksi, int $id): ?array {
    $stmt = mysqli_prepare($koneksi, "
        SELECT m.*,
               ps.kode_program_studi, ps.nama_program_studi, ps.jenjang,
               f.id_fakultas, f.kode_fakultas, f.nama_fakultas,
               u.id_universitas, u.kode_universitas, u.nama_universitas,
               p.username, p.nama_lengkap AS nama_pengguna
        FROM mahasiswa m
        INNER JOIN program_studi ps ON ps.id_program_studi = m.id_program_studi
        INNER JOIN fakultas f ON f.id_fakultas = ps.id_fakultas
        INNER JOIN universitas u ON u.id_universitas = f.id_universitas
        LEFT JOIN pengguna p ON p.id_pengguna = m.id_pengguna
        WHERE m.id_mahasiswa = ?
        LIMIT 1
    ");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)) ?: null;
    mysqli_stmt_close($stmt);
    return $row;
}

function operator_scope_valid(mysqli $koneksi, int $idProgramStudi, array $user): bool {
    return (($user['kode_role'] ?? '') === 'OPERATOR_PRODI'
        && (int)($user['id_program_studi'] ?? 0) === $idProgramStudi);
}

function mahasiswa_scope_allowed(array $mhs, array $user): bool {
    $role = $user['kode_role'] ?? '';
    if ($role === 'ADMIN') {
        return true;
    }
    if ($role === 'OPERATOR_PRODI') {
        return (int)$mhs['id_program_studi'] === (int)($user['id_program_studi'] ?? 0);
    }
    if ($role === 'DEKANAT') {
        return (int)$mhs['id_fakultas'] === (int)($user['id_fakultas'] ?? 0);
    }
    if ($role === 'REKTORAT') {
        return (int)$mhs['id_universitas'] === (int)($user['id_universitas'] ?? 0);
    }
    return false;
}
