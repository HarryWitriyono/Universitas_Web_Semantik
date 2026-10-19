<?php
declare(strict_types=1);

/**
 * Konfigurasi koneksi database SIMSKRIPSI.
 * Ubah nilai ini sesuai server tujuan.
 * File PHP tidak menampilkan nilai konfigurasi ke browser.
 */
const DB_HOST = '127.0.0.1';
const DB_PORT = 3306;
const DB_NAME = 'simskripsi';
const DB_USER = 'root';
const DB_PASS = '';

function simskripsi_db(): mysqli
{
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    $db->set_charset('utf8mb4');
    return $db;
}
