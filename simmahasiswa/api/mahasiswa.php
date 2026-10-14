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

    $npm = isset($_GET['npm'])
        ? trim($_GET['npm'])
        : '';

    $search = isset($_GET['search'])
        ? trim($_GET['search'])
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
    GET SATU MAHASISWA BERDASARKAN NPM
    =========================================================
    */

    if ($npm !== '') {

        $sql = "
            SELECT
                id_mahasiswa,
                npm,
                nama_mahasiswa,
                jenis_kelamin,
                tempat_lahir,
                tanggal_lahir,
                tanggal_masuk,
                alamat,
                status_mahasiswa,
                kode_program_studi,
                nama_program_studi,
                jenjang,
                kode_fakultas,
                nama_fakultas,
                kode_universitas,
                nama_universitas
            FROM v_profil_mahasiswa
            WHERE npm = ?
            LIMIT 1
        ";

        $stmt = mysqli_prepare($koneksi, $sql);

        if (!$stmt) {
            throw new Exception(mysqli_error($koneksi));
        }

        mysqli_stmt_bind_param($stmt, "s", $npm);

        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        $data = mysqli_fetch_assoc($result);


        if (!$data) {

            http_response_code(404);

            $response['message'] =
                'Mahasiswa dengan NPM tersebut tidak ditemukan';

            echo json_encode(
                $response,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
            );

            exit;
        }


        $response['success'] = true;
        $response['message'] = 'Data mahasiswa ditemukan';
        $response['data'] = $data;


        echo json_encode(
            $response,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
        );

        exit;
    }


    /*
    =========================================================
    DAFTAR MAHASISWA
    =========================================================
    */

    $where = "";
    $params = [];
    $types = "";


    if ($search !== '') {

        $where = "
            WHERE
                npm LIKE ?
                OR nama_mahasiswa LIKE ?
        ";

        $keyword = "%{$search}%";

        $params[] = $keyword;
        $params[] = $keyword;

        $types = "ss";
    }


    /*
    =========================================================
    TOTAL DATA
    =========================================================
    */

    $count_sql = "
        SELECT COUNT(*) AS total
        FROM v_profil_mahasiswa
        $where
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

    $count_result =
        mysqli_stmt_get_result($stmt_count);

    $count_data =
        mysqli_fetch_assoc($count_result);

    $total = (int) $count_data['total'];


    /*
    =========================================================
    AMBIL DATA
    =========================================================
    */

    $sql = "
        SELECT
            id_mahasiswa,
            npm,
            nama_mahasiswa,
            jenis_kelamin,
            tempat_lahir,
            tanggal_lahir,
            tanggal_masuk,
            alamat,
            status_mahasiswa,
            kode_program_studi,
            nama_program_studi,
            jenjang,
            kode_fakultas,
            nama_fakultas,
            kode_universitas,
            nama_universitas
        FROM v_profil_mahasiswa
        $where
        ORDER BY nama_mahasiswa ASC
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

    $result = mysqli_stmt_get_result($stmt);

    $data = [];


    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }


    /*
    =========================================================
    RESPONSE
    =========================================================
    */

    $total_pages = ($limit > 0)
        ? ceil($total / $limit)
        : 0;


    $response['success'] = true;

    $response['message'] =
        'Data mahasiswa berhasil diambil';

    $response['data'] = $data;

    $response['meta'] = [
        'page'        => $page,
        'limit'       => $limit,
        'total_data'  => $total,
        'total_pages' => $total_pages
    ];


    echo json_encode(
        $response,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
    );


} catch (Throwable $e) {

    http_response_code(500);

    $response['success'] = false;

    $response['message'] =
        'Terjadi kesalahan pada server';

    echo json_encode(
        $response,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
    );
}