<?php

header('Content-Type: application/json; charset=utf-8');

require_once '../koneksi.php';

$response = [
    'success' => false,
    'message' => '',
    'data'    => null
];

try {

    /*
    =========================================================
    HANYA MENERIMA METHOD POST
    =========================================================
    */

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

        http_response_code(405);

        $response['message'] =
            'Method tidak diizinkan. Gunakan POST.';

        echo json_encode(
            $response,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
        );

        exit;
    }


    /*
    =========================================================
    AMBIL JSON REQUEST
    =========================================================
    */

    $input = json_decode(
        file_get_contents('php://input'),
        true
    );


    if (!is_array($input)) {

        http_response_code(400);

        $response['message'] =
            'Format JSON tidak valid.';

        echo json_encode(
            $response,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
        );

        exit;
    }


    $username = isset($input['username'])
        ? trim($input['username'])
        : '';

    $password = isset($input['password'])
        ? $input['password']
        : '';


    /*
    =========================================================
    VALIDASI
    =========================================================
    */

    if ($username === '' || $password === '') {

        http_response_code(400);

        $response['message'] =
            'Username dan password wajib diisi.';

        echo json_encode(
            $response,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
        );

        exit;
    }


    /*
    =========================================================
    CARI PENGGUNA
    =========================================================
    */

    $sql = "
        SELECT
            p.id_pengguna,
            p.id_role,
            p.id_universitas,
            p.id_fakultas,
            p.id_program_studi,
            p.username,
            p.password_hash,
            p.nama_lengkap,
            p.email,
            p.no_hp,
            p.status_aktif,

            r.kode_role,
            r.nama_role

        FROM pengguna p

        INNER JOIN roles r
            ON p.id_role = r.id_role

        WHERE p.username = ?

        LIMIT 1
    ";


    $stmt = mysqli_prepare($koneksi, $sql);

    if (!$stmt) {
        throw new Exception(mysqli_error($koneksi));
    }


    mysqli_stmt_bind_param(
        $stmt,
        "s",
        $username
    );


    mysqli_stmt_execute($stmt);

    $result =
        mysqli_stmt_get_result($stmt);

    $user =
        mysqli_fetch_assoc($result);


    /*
    =========================================================
    USER TIDAK DITEMUKAN
    =========================================================
    */

    if (!$user) {

        http_response_code(401);

        $response['message'] =
            'Username atau password salah.';

        echo json_encode(
            $response,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
        );

        exit;
    }


    /*
    =========================================================
    CEK STATUS AKUN
    =========================================================
    */

    if ($user['status_aktif'] !== 'Aktif') {

        http_response_code(403);

        $response['message'] =
            'Akun tidak aktif.';

        echo json_encode(
            $response,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
        );

        exit;
    }


    /*
    =========================================================
    VERIFIKASI PASSWORD
    =========================================================
    */

    if (!password_verify(
        $password,
        $user['password_hash']
    )) {

        http_response_code(401);

        $response['message'] =
            'Username atau password salah.';

        echo json_encode(
            $response,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
        );

        exit;
    }


    /*
    =========================================================
    UPDATE LAST LOGIN
    =========================================================
    */

    $update_sql = "
        UPDATE pengguna
        SET last_login = NOW()
        WHERE id_pengguna = ?
    ";

    $update_stmt =
        mysqli_prepare(
            $koneksi,
            $update_sql
        );

    if ($update_stmt) {

        mysqli_stmt_bind_param(
            $update_stmt,
            "i",
            $user['id_pengguna']
        );

        mysqli_stmt_execute($update_stmt);
    }


    /*
    =========================================================
    RESPONSE
    =========================================================
    */

    unset($user['password_hash']);


    $response['success'] = true;

    $response['message'] =
        'Login berhasil.';

    $response['data'] = $user;


    echo json_encode(
        $response,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
    );


} catch (Throwable $e) {

    http_response_code(500);

    $response['message'] =
        'Terjadi kesalahan pada server.';

    echo json_encode(
        $response,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
    );
}