<?php

header('Content-Type: application/json; charset=utf-8');

require_once '../koneksi.php';

$response = [
    'success' => false,
    'message' => '',
    'data'    => [],
    'meta'    => []
];

try {

    $search = isset($_GET['search'])
        ? trim($_GET['search'])
        : '';

    $status = isset($_GET['status'])
        ? trim($_GET['status'])
        : '';

    $page = isset($_GET['page'])
        ? max(1, (int) $_GET['page'])
        : 1;

    $limit = isset($_GET['limit'])
        ? max(1, min(100, (int) $_GET['limit']))
        : 10;

    $offset = ($page - 1) * $limit;


    /*
    =========================================================
    FILTER
    =========================================================
    */

    $where = [];
    $params = [];
    $types = "";


    if ($search !== '') {

        $where[] = "
            (
                m.npm LIKE ?
                OR m.nama_mahasiswa LIKE ?
            )
        ";

        $keyword = "%{$search}%";

        $params[] = $keyword;
        $params[] = $keyword;

        $types .= "ss";
    }


    if ($status !== '') {

        $where[] = "m.status_mahasiswa = ?";

        $params[] = $status;

        $types .= "s";
    }


    $where_sql = '';

    if (!empty($where)) {
        $where_sql = 'WHERE ' . implode(' AND ', $where);
    }


    /*
    =========================================================
    TOTAL
    =========================================================
    */

    $count_sql = "
        SELECT COUNT(*) AS total
        FROM mahasiswa m
        $where_sql
    ";

    $stmt_count = mysqli_prepare($koneksi, $count_sql);

    if (!$stmt_count) {
        throw new Exception(mysqli_error($koneksi));
    }


    if (!empty($params)) {

        mysqli_stmt_bind_param(
            $stmt_count,
            $types,
            ...$params
        );
    }


    mysqli_stmt_execute($stmt_count);

    $result_count =
        mysqli_stmt_get_result($stmt_count);

    $row_count =
        mysqli_fetch_assoc($result_count);

    $total = (int) $row_count['total'];


    /*
    =========================================================
    DATA
    =========================================================
    */

    $sql = "
        SELECT
            m.id_mahasiswa,
            m.npm,
            m.nama_mahasiswa,
            m.jenis_kelamin,
            m.tanggal_masuk,
            m.status_mahasiswa,
            ps.kode_program_studi,
            ps.nama_program_studi,
            ps.jenjang
        FROM mahasiswa m

        INNER JOIN program_studi ps
            ON m.id_program_studi = ps.id_program_studi

        $where_sql

        ORDER BY m.nama_mahasiswa ASC

        LIMIT ? OFFSET ?
    ";

    $stmt = mysqli_prepare($koneksi, $sql);

    if (!$stmt) {
        throw new Exception(mysqli_error($koneksi));
    }


    if (!empty($params)) {

        $params[] = $limit;
        $params[] = $offset;

        mysqli_stmt_bind_param(
            $stmt,
            $types . "ii",
            ...$params
        );

    } else {

        mysqli_stmt_bind_param(
            $stmt,
            "ii",
            $limit,
            $offset
        );
    }


    mysqli_stmt_execute($stmt);

    $result =
        mysqli_stmt_get_result($stmt);

    $data = [];


    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }


    /*
    =========================================================
    RESPONSE
    =========================================================
    */

    $response['success'] = true;

    $response['message'] =
        'Data mahasiswa berhasil diambil';

    $response['data'] = $data;

    $response['meta'] = [
        'page'        => $page,
        'limit'       => $limit,
        'total_data'  => $total,
        'total_pages' => ceil($total / $limit)
    ];


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