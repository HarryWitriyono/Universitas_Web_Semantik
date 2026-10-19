<?php
declare(strict_types=1);
require_once __DIR__ . '/database.php';

/** Mengambil URL dasar API dari database konfigurasi. */
function simskripsi_api_base_url(): string
{
    $db = simskripsi_db();
    $stmt = $db->prepare("SELECT nilai_konfigurasi FROM konfigurasi_aplikasi WHERE nama_konfigurasi = 'api_base_url' LIMIT 1");
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $db->close();
    return rtrim((string)($row['nilai_konfigurasi'] ?? ''), '/');
}

function simskripsi_api_login_url(): string
{
    $base = simskripsi_api_base_url();
    return $base === '' ? '' : $base . '/login.php';
}
