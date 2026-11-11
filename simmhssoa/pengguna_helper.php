<?php
/**
 * Helper Consumer SOA Pengguna.
 * Tidak menggunakan koneksi database lokal.
 */
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/api_client.php';

wajib_role(['ADMIN']);

function pengguna_soa_call(string $action, array $payload = []): array {
    $payload['action'] = $action;
    $payload['id_pengguna'] = (int)($_SESSION['id_pengguna'] ?? 0);
    return soa_request('apipengguna.php', $payload);
}

function pengguna_soa_data(array $response, string $key): array {
    if (!empty($response['error']) || !is_array($response['data'] ?? null)) return [];
    $body = $response['data'];
    if (($body['success'] ?? false) !== true || !is_array($body['data'] ?? null)) return [];
    $value = $body['data'][$key] ?? [];
    return is_array($value) ? $value : [];
}

function pengguna_soa_message(array $response, string $fallback): string {
    if (!empty($response['error'])) return (string)$response['error'];
    return (string)($response['data']['message'] ?? $fallback);
}

function pengguna_h($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function pengguna_role_rules(string $kode_role): array {
    return match ($kode_role) {
        'ADMIN' => ['universitas' => true, 'fakultas' => false, 'prodi' => false],
        'OPERATOR_PRODI' => ['universitas' => true, 'fakultas' => true, 'prodi' => true],
        'DEKANAT' => ['universitas' => true, 'fakultas' => true, 'prodi' => false],
        'REKTORAT' => ['universitas' => true, 'fakultas' => false, 'prodi' => false],
        'MAHASISWA' => ['universitas' => true, 'fakultas' => true, 'prodi' => true],
        default => ['universitas' => false, 'fakultas' => false, 'prodi' => false],
    };
}

function pengguna_validate_scope(
    string $kode_role,
    ?int $id_universitas,
    ?int $id_fakultas,
    ?int $id_program_studi
): string {
    $rules = pengguna_role_rules($kode_role);
    if ($rules['universitas'] && !$id_universitas) return 'Universitas wajib dipilih untuk role tersebut.';
    if (!$rules['universitas'] && $id_universitas) return 'Role tersebut tidak menggunakan scope Universitas.';
    if ($rules['fakultas'] && !$id_fakultas) return 'Fakultas wajib dipilih untuk role tersebut.';
    if (!$rules['fakultas'] && $id_fakultas) return 'Role tersebut tidak menggunakan scope Fakultas.';
    if ($rules['prodi'] && !$id_program_studi) return 'Program Studi wajib dipilih untuk role tersebut.';
    if (!$rules['prodi'] && $id_program_studi) return 'Role tersebut tidak menggunakan scope Program Studi.';
    return '';
}

function pengguna_options(): array {
    $response = pengguna_soa_call('options');
    if (!empty($response['error'])) return [];
    $body = $response['data'] ?? [];
    if (($body['success'] ?? false) !== true) return [];
    return $body['data'] ?? [];
}
