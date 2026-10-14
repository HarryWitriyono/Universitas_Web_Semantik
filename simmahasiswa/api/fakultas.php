<?php

header('Content-Type: application/json; charset=utf-8');

require_once '../koneksi.php';

$response = [
    'success' => false,
    'message' => '',
    'data'    => []
];

try {

    $kode = isset($_GET['kode'])
        ? trim($_GET['kode'])
        : '';


    $where = '';
    $params = [];
    $types = '';


    if ($kode !== '') {

        $where = "
            WHERE f.kode_fakultas = ?
        ";

        $params[] = $kode;
        $types = "s";
    }


    $sql = "
        SELECT
            f.id_fakultas,
            f.kode_fakultas,
            f.nama_fakultas,

            u.id_universitas,
            u.kode_universitas,
            u.nama_universitas

        FROM fakultas f

        INNER JOIN universitas u
            ON f.id_universitas = u.id_universitas

        $where

        ORDER BY f.nama_fakultas ASC
    ";


    $stmt = mysqli_prepare($koneksi, $sql);

    if (!$stmt) {
        throw new Exception(mysqli_error($koneksi));
    }


    if (!empty($params)) {

        mysqli_stmt_bind_param(
            $stmt,
            $types,
            ...$params
        );
    }


    mysqli_stmt_execute($stmt);

    $result =
        mysqli_stmt_get_result($stmt);

    $data = [];


    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }


    $response['success'] = true;

    $response['message'] =
        'Data fakultas berhasil diambil';

    $response['data'] = $data;


    echo json_encode(
        $response,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
    );


} catch (Throwable $e) {

    http_response_code(500);

    $response['message'] =
        'Terjadi kesalahan pada server';

    echo json_encode(
        $response,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
    );
}