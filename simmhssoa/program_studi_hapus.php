<?php
require_once __DIR__ . '/program_studi_helper.php';
require_once __DIR__ . '/csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: program_studi.php'); exit;
}
try {
    csrf_verify();
    $id = (int)($_POST['id_program_studi'] ?? 0);
    if ($id <= 0) throw new RuntimeException('ID program studi tidak valid.');

    $response = ps_soa_call('delete', ['id_program_studi' => $id]);
    if (!empty($response['error'])) {
        throw new RuntimeException(ps_soa_message($response, 'Program Studi gagal dihapus.'));
    }
    $_SESSION['success'] = ps_soa_message($response, 'Program Studi berhasil dihapus.');
} catch (Throwable $e) {
    $_SESSION['error'] = $e->getMessage();
}
header('Location: program_studi.php'); exit;
