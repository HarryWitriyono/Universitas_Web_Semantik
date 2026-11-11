<?php
/**
 * fakultas_hapus.php - SOA Consumer.
 * Pemeriksaan relasi dan DELETE dilakukan oleh SOA Provider.
 */
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/api_client.php';

wajib_role(['ADMIN']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    header('Location: fakultas.php');
    exit;
}

csrf_verify();

$idFakultas = filter_input(INPUT_POST, 'id_fakultas', FILTER_VALIDATE_INT);
if (!$idFakultas || $idFakultas < 1) {
    $_SESSION['error'] = 'ID fakultas tidak valid.';
    header('Location: fakultas.php');
    exit;
}

$api = soa_request('apifakultas.php', [
    'action' => 'delete',
    'id_pengguna' => (int) ($_SESSION['id_pengguna'] ?? 0),
    'id_fakultas' => (int) $idFakultas
]);

if ($api['error'] !== null) {
    $_SESSION['error'] = 'SOA Server tidak dapat dihubungi.';
} elseif ($api['http_code'] !== 200 || empty($api['data']['success'])) {
    $_SESSION['error'] = $api['data']['message'] ?? 'Gagal menghapus fakultas.';
} else {
    $_SESSION['success'] = $api['data']['message'] ?? 'Fakultas berhasil dihapus.';
}

header('Location: fakultas.php');
exit;
