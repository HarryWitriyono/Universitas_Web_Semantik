<?php
/**
 * universitas_hapus.php - SOA Consumer.
 * Tidak melakukan query database langsung.
 */

require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/api_client.php';

wajib_role(['ADMIN']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    header('Location: universitas.php');
    exit;
}

csrf_verify();

$idUniversitas = filter_input(INPUT_POST, 'id_universitas', FILTER_VALIDATE_INT);
if (!$idUniversitas || $idUniversitas < 1) {
    $_SESSION['error'] = 'ID universitas tidak valid.';
    header('Location: universitas.php');
    exit;
}

$api = soa_request('apiuniversitas.php', [
    'action' => 'delete',
    'id_pengguna' => (int) ($_SESSION['id_pengguna'] ?? 0),
    'id_universitas' => (int) $idUniversitas
]);

if ($api['error'] !== null) {
    $_SESSION['error'] = 'SOA Server tidak dapat dihubungi.';
} elseif ($api['http_code'] !== 200 || empty($api['data']['success'])) {
    $_SESSION['error'] = $api['data']['message'] ?? 'Gagal menghapus universitas.';
} else {
    $_SESSION['success'] = $api['data']['message'] ?? 'Universitas berhasil dihapus.';
}

header('Location: universitas.php');
exit;
