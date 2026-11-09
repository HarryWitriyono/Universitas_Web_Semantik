<?php
/**
 * apiloginsoa.php
 * Service autentikasi untuk SIMMhsSOA74.
 *
 * Endpoint : POST /SOA74/apiloginsoa.php
 * Content  : application/json
 *
 * Request:
 * {
 *   "username": "Admin",
 *   "password": "password"
 * }
 *
 * Response berhasil:
 * {
 *   "success": true,
 *   "message": "Login berhasil.",
 *   "data": { ...data pengguna... }
 * }
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');

/**
 * Mengirim response JSON dan menghentikan proses.
 */
function json_response(int $httpCode, bool $success, string $message, ?array $data = null): void
{
    http_response_code($httpCode);

    $response = [
        'success' => $success,
        'message' => $message,
    ];

    if ($data !== null) {
        $response['data'] = $data;
    }

    echo json_encode(
        $response,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}

/* Service ini hanya menerima HTTP POST. */
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    json_response(405, false, 'Metode request tidak diizinkan.');
}

/* Batasi ukuran request agar endpoint tidak menerima payload berlebihan. */
$contentLength = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
if ($contentLength > 16384) {
    json_response(413, false, 'Ukuran request terlalu besar.');
}

/* Pastikan body request berupa JSON. */
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (stripos($contentType, 'application/json') !== 0) {
    json_response(415, false, 'Content-Type harus application/json.');
}

/* Baca dan validasi JSON request. */
$rawInput = file_get_contents('php://input');
if ($rawInput === false || trim($rawInput) === '') {
    json_response(400, false, 'Request body tidak boleh kosong.');
}

$input = json_decode($rawInput, true);
if (json_last_error() !== JSON_ERROR_NONE || !is_array($input)) {
    json_response(400, false, 'Format JSON tidak valid.');
}

$username = isset($input['username']) && is_string($input['username'])
    ? trim($input['username'])
    : '';

$password = isset($input['password']) && is_string($input['password'])
    ? $input['password']
    : '';

if ($username === '' || $password === '') {
    json_response(422, false, 'Username dan password wajib diisi.');
}

if (strlen($username) > 100 || strlen($password) > 1024) {
    json_response(422, false, 'Username atau password melebihi batas karakter.');
}

/*
 * File koneksi database harus tersedia di folder SOA74:
 * htdocs/SOA74/koneksi.php
 *
 * File tersebut mendefinisikan variabel $koneksi (MySQLi).
 */
try {
    require_once __DIR__ . '/koneksi.php';

    if (!isset($koneksi) || !($koneksi instanceof mysqli)) {
        throw new RuntimeException('Objek koneksi database tidak tersedia.');
    }

    mysqli_set_charset($koneksi, 'utf8mb4');

    /*
     * Autentikasi dilakukan di sisi service.
     * Password hash tidak pernah dikirim kembali ke client.
     */
    $sql = "SELECT
                p.id_pengguna,
                p.id_role,
                p.id_universitas,
                p.id_fakultas,
                p.id_program_studi,
                p.username,
                p.password_hash,
                p.nama_lengkap,
                p.email,
                p.status_aktif,
                r.kode_role,
                r.nama_role
            FROM pengguna p
            INNER JOIN roles r ON r.id_role = p.id_role
            WHERE p.username = ?
            LIMIT 1";

    $stmt = mysqli_prepare($koneksi, $sql);
    if (!$stmt) {
        throw new RuntimeException('Gagal menyiapkan query autentikasi.');
    }

    mysqli_stmt_bind_param($stmt, 's', $username);

    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        throw new RuntimeException('Gagal menjalankan query autentikasi.');
    }

    $result = mysqli_stmt_get_result($stmt);
    $user = $result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);

    /*
     * Pesan dibuat sama untuk username tidak ditemukan, password salah,
     * dan akun tidak aktif agar tidak membocorkan keberadaan akun.
     */
    if (
        !$user ||
        $user['status_aktif'] !== 'Aktif' ||
        !password_verify($password, $user['password_hash'])
    ) {
        json_response(401, false, 'Username atau password salah.');
    }

    /*
     * Catat waktu login dan audit di sisi service/database.
     * Kedua operasi dijalankan dalam satu transaksi.
     */
    mysqli_begin_transaction($koneksi);

    $update = mysqli_prepare(
        $koneksi,
        'UPDATE pengguna SET last_login = NOW() WHERE id_pengguna = ?'
    );

    if (!$update) {
        throw new RuntimeException('Gagal menyiapkan pembaruan waktu login.');
    }

    $idPengguna = (int)$user['id_pengguna'];
    mysqli_stmt_bind_param($update, 'i', $idPengguna);

    if (!mysqli_stmt_execute($update)) {
        mysqli_stmt_close($update);
        throw new RuntimeException('Gagal memperbarui waktu login.');
    }
    mysqli_stmt_close($update);

    $audit = mysqli_prepare(
        $koneksi,
        "INSERT INTO audit_log
            (id_pengguna, tabel_nama, record_id, aksi, ip_address, user_agent)
         VALUES (?, 'pengguna', ?, 'LOGIN', ?, ?)"
    );

    if (!$audit) {
        throw new RuntimeException('Gagal menyiapkan audit login.');
    }

    $recordId = (string)$idPengguna;
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

    mysqli_stmt_bind_param(
        $audit,
        'isss',
        $idPengguna,
        $recordId,
        $ipAddress,
        $userAgent
    );

    if (!mysqli_stmt_execute($audit)) {
        mysqli_stmt_close($audit);
        throw new RuntimeException('Gagal mencatat audit login.');
    }
    mysqli_stmt_close($audit);

    mysqli_commit($koneksi);

    /*
     * Data ini akan digunakan auth.php pada aplikasi client
     * untuk membentuk session lokal.
     */
    $dataPengguna = [
        'id_pengguna'       => (int)$user['id_pengguna'],
        'id_role'           => (int)$user['id_role'],
        'id_universitas'    => $user['id_universitas'] !== null
            ? (int)$user['id_universitas'] : null,
        'id_fakultas'       => $user['id_fakultas'] !== null
            ? (int)$user['id_fakultas'] : null,
        'id_program_studi'  => $user['id_program_studi'] !== null
            ? (int)$user['id_program_studi'] : null,
        'username'          => $user['username'],
        'nama_lengkap'      => $user['nama_lengkap'],
        'email'             => $user['email'],
        'status_aktif'      => $user['status_aktif'],
        'kode_role'         => $user['kode_role'],
        'nama_role'         => $user['nama_role'],
    ];

    json_response(200, true, 'Login berhasil.', $dataPengguna);

} catch (Throwable $e) {
    if (isset($koneksi) && $koneksi instanceof mysqli) {
        try {
            mysqli_rollback($koneksi);
        } catch (Throwable $ignored) {
            // Jangan tampilkan detail internal database ke client.
        }
    }

    error_log('[SOA74 apiloginsoa] ' . $e->getMessage());
    json_response(500, false, 'Terjadi kesalahan pada service autentikasi.');
}
