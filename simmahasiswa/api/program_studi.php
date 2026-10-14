<?php

header('Content-Type: application/json; charset=utf-8');

require_once '../koneksi.php';

$response = [
    'success' => false,
    'message' => '',
    'data'    => []
];

try {

    $id_fakultas = isset($_GET['id_fakultas'])
        ? (int) $_GET['id_fakultas']
        : 0;

    $kode = isset($_GET['kode'])
        ? trim($_GET['kode'])
        : '';


    $where = [];
    $params = [];
    $types = "";


    if ($id_fakultas > 0) {

        $where[] = "ps.id_fakultas = ?";

        $params[] = $id_fakultas;

        $types .= "i";
    }


    if ($kode !== '') {

        $where[] = "ps.kode_program_studi = ?";

        $params[] = $kode;

        $types .= "s";
    }


    $where_sql = '';

    if (!empty($where)) {
        $where_sql =
            'WHERE ' . implode(' AND ', $where);
    }


    $sql = "
        SELECT
            ps.id_program_studi,
            ps.kode_program_studi,
            ps.nama_program_studi,
            ps.jenjang,
            ps.status_aktif,

            f.id_fakultas,
            f.kode_fakultas,
            f.nama_fakultas,

            u.id_universitas,
            u.kode_universitas,
            u.nama_universitas

        FROM program_studi ps

        INNER JOIN fakultas f
            ON ps.id_fakultas = f.id_fakultas

        INNER JOIN universitas u
            ON f.id_universitas = u.id_universitas

        $where_sql

        ORDER BY ps.nama_program_studi ASC
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
        'Data program studi berhasil diambil';

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