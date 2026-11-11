<?php
require_once __DIR__ . '/pengguna_helper.php';
require_once __DIR__ . '/csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: pengguna.php'); exit;
}

try {
    csrf_verify();
    $targetId = (int)($_POST['id_pengguna'] ?? 0);
    if ($targetId <= 0) throw new RuntimeException('ID pengguna tidak valid.');

    $response = pengguna_soa_call('delete', ['target_id_pengguna' => $targetId]);
    if (!empty($response['error'])) {
        throw new RuntimeException(pengguna_soa_message($response, 'Gagal menghapus pengguna.'));
    }
    if (($response['data']['success'] ?? false) !== true) {
        throw new RuntimeException(pengguna_soa_message($response, 'Gagal menghapus pengguna.'));
    }
    $_SESSION['success'] = pengguna_soa_message($response, 'Pengguna berhasil dihapus.');
} catch (Throwable $e) {
    $_SESSION['error'] = $e->getMessage();
}

header('Location: pengguna.php');
exit;
