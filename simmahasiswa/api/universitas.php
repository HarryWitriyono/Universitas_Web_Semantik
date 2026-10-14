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
            WHERE kode_universitas = ?
        ";

        $params[] = $kode;
        $types = "s";
    }


    $sql = "
        SELECT
            id_universitas,
            kode_universitas,
            nama_universitas,
            slogan,
            alamat,
            kota,
            provinsi,
            kode_pos,
            website,
            email,
            telepon

        FROM universitas

        $where

        ORDER BY nama_universitas ASC
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
        'Data universitas berhasil diambil';

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