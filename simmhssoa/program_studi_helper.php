<?php
/**
 * Helper Consumer SOA Program Studi.
 * Tidak membuka koneksi database lokal.
 */
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/api_client.php';

wajib_role(['ADMIN']);

/**
 * Memanggil endpoint Provider Program Studi.
 */
function ps_soa_call($action, array $payload = []) {
    $payload['action'] = $action;
    $payload['id_pengguna'] = (int)($_SESSION['id_pengguna'] ?? 0);

    return soa_request('apiprogramstudi.php', $payload);
}

/**
 * Mengambil data dari envelope JSON Provider:
 * {
 *   "success": true,
 *   "message": "...",
 *   "data": { "program_studi": [...] }
 * }
 */
function ps_soa_data($response, $key) {
    if (
        !is_array($response) ||
        !empty($response['error']) ||
        !is_array($response['data'] ?? null)
    ) {
        return [];
    }

    $body = $response['data'];

    if (($body['success'] ?? false) !== true) {
        return [];
    }

    if (!isset($body['data']) || !is_array($body['data'])) {
        return [];
    }

    return $body['data'][$key] ?? [];
}

/**
 * Mengambil pesan kesalahan atau pesan keberhasilan Provider.
 */
function ps_soa_message($response, $fallback) {
    if (!is_array($response)) {
        return $fallback;
    }

    if (!empty($response['error'])) {
        return (string)$response['error'];
    }

    if (
        isset($response['data']['message']) &&
        is_string($response['data']['message']) &&
        $response['data']['message'] !== ''
    ) {
        return $response['data']['message'];
    }

    return $fallback;
}

/**
 * Mengambil daftar Fakultas melalui Provider Fakultas.
 */
function ps_fakultas_list() {
    $response = soa_request('apifakultas.php', [
        'action' => 'list',
        'id_pengguna' => (int)($_SESSION['id_pengguna'] ?? 0)
    ]);

    return ps_soa_data($response, 'fakultas');
}

/**
 * Escape output HTML.
 */
function ps_h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
