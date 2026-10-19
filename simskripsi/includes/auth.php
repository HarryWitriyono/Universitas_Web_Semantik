<?php
declare(strict_types=1);

require_once __DIR__ . '/session.php';

/**
 * Mengembalikan data pengguna yang disimpan setelah login API berhasil.
 */
function current_user(): ?array
{
    $user = $_SESSION['user'] ?? null;

    return (is_array($user) && !empty($user['id_pengguna'])) ? $user : null;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function require_login(string $loginUrl = 'login.php'): void
{
    if (!is_logged_in()) {
        header('Location: ' . $loginUrl);
        exit;
    }
}

/**
 * Ambil kode peran dari data API. Sesuaikan alias hanya bila respons API
 * SIM Mahasiswa memang menggunakan nama field yang berbeda.
 */
function current_role_code(): string
{
    $user = current_user() ?? [];

    return strtoupper(trim((string) (
        $user['kode_role'] ?? $user['role_code'] ?? ''
    )));
}

/**
 * RBAC: halaman hanya dapat diakses oleh kode peran yang diizinkan.
 * Contoh: require_role(['ADMIN', 'OPERATOR_PRODI']);
 */
function require_role(array $allowedRoles, string $loginUrl = 'login.php'): void
{
    require_login($loginUrl);

    $role = current_role_code();
    $allowed = array_map(
        static fn($value): string => strtoupper(trim((string) $value)),
        $allowedRoles
    );

    if ($role === '' || !in_array($role, $allowed, true)) {
        http_response_code(403);
        exit('403 Forbidden: Anda tidak memiliki hak akses ke halaman ini.');
    }
}

/**
 * Untuk menu/tombol: hanya mengatur tampilan, bukan pengganti require_role()
 * di halaman pemrosesan atau endpoint.
 */
function has_role(array $allowedRoles): bool
{
    if (!is_logged_in()) {
        return false;
    }

    $role = current_role_code();
    $allowed = array_map(
        static fn($value): string => strtoupper(trim((string) $value)),
        $allowedRoles
    );

    return $role !== '' && in_array($role, $allowed, true);
}
