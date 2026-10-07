<?php
/*
 * Helper CRUD Pengguna.
 * Jangan menggunakan nama variabel $role di halaman pemanggil,
 * karena sideleftbar.php menggunakan $role untuk kode role session.
 */

function pengguna_role_rules(string $kode_role): array
{
    return match ($kode_role) {
        'ADMIN' => [
            'universitas' => true,
            'fakultas' => false,
            'prodi' => false,
        ],
        'OPERATOR_PRODI' => [
            'universitas' => true,
            'fakultas' => true,
            'prodi' => true,
        ],
        'DEKANAT' => [
            'universitas' => true,
            'fakultas' => true,
            'prodi' => false,
        ],
        'REKTORAT' => [
            'universitas' => true,
            'fakultas' => false,
            'prodi' => false,
        ],
        'MAHASISWA' => [
            'universitas' => true,
            'fakultas' => true,
            'prodi' => true,
        ],
        default => [
            'universitas' => false,
            'fakultas' => false,
            'prodi' => false,
        ],
    };
}

function pengguna_validate_scope(
    string $kode_role,
    ?int $id_universitas,
    ?int $id_fakultas,
    ?int $id_program_studi
): string {
    $rules = pengguna_role_rules($kode_role);

    if ($rules['universitas'] && !$id_universitas) {
        return 'Universitas wajib dipilih untuk role tersebut.';
    }

    if (!$rules['universitas'] && $id_universitas) {
        return 'Role tersebut tidak menggunakan scope Universitas.';
    }

    if ($rules['fakultas'] && !$id_fakultas) {
        return 'Fakultas wajib dipilih untuk role tersebut.';
    }

    if (!$rules['fakultas'] && $id_fakultas) {
        return 'Role tersebut tidak menggunakan scope Fakultas.';
    }

    if ($rules['prodi'] && !$id_program_studi) {
        return 'Program Studi wajib dipilih untuk role tersebut.';
    }

    if (!$rules['prodi'] && $id_program_studi) {
        return 'Role tersebut tidak menggunakan scope Program Studi.';
    }

    return '';
}

function pengguna_scope_relationship_error(
    mysqli $koneksi,
    ?int $id_universitas,
    ?int $id_fakultas,
    ?int $id_program_studi
): string {
    if ($id_fakultas) {
        $stmt = mysqli_prepare(
            $koneksi,
            "SELECT id_universitas
             FROM fakultas
             WHERE id_fakultas = ?
             LIMIT 1"
        );

        if (!$stmt) {
            return 'Gagal memeriksa relasi Fakultas.';
        }

        mysqli_stmt_bind_param($stmt, 'i', $id_fakultas);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $fakultas = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if (!$fakultas) {
            return 'Fakultas yang dipilih tidak ditemukan.';
        }

        if ($id_universitas && (int)$fakultas['id_universitas'] !== $id_universitas) {
            return 'Fakultas yang dipilih bukan bagian dari Universitas tersebut.';
        }
    }

    if ($id_program_studi) {
        $stmt = mysqli_prepare(
            $koneksi,
            "SELECT id_fakultas
             FROM program_studi
             WHERE id_program_studi = ?
             LIMIT 1"
        );

        if (!$stmt) {
            return 'Gagal memeriksa relasi Program Studi.';
        }

        mysqli_stmt_bind_param($stmt, 'i', $id_program_studi);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $prodi = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if (!$prodi) {
            return 'Program Studi yang dipilih tidak ditemukan.';
        }

        if ($id_fakultas && (int)$prodi['id_fakultas'] !== $id_fakultas) {
            return 'Program Studi yang dipilih bukan bagian dari Fakultas tersebut.';
        }
    }

    return '';
}
