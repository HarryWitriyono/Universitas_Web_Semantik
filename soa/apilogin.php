<?php
/**
 * apilogin.php
 * API autentikasi terpusat untuk aplikasi-aplikasi dalam ekosistem SOA.
 *
 * Endpoint:
 *   POST http://localhost/soa/apilogin.php
 *
 * Request JSON:
 *   {
 *     "username": "Admin",
 *     "password": "password_pengguna"
 *   }
 *
 * Respons berupa JSON. API ini tidak membuat session PHP pada aplikasi klien.
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');

function response(int $httpCode, array $data): never
{
    http_response_code($httpCode);
    echo json_encode(
        $data,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}

// Menangani preflight CORS untuk aplikasi web yang berbeda origin.
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST, OPTIONS');
    response(405, [
        'success' => false,
        'message' => 'Metode tidak diizinkan. Gunakan POST.'
    ]);
}

// Batasi ukuran request agar endpoint tidak menerima payload berlebihan.
$contentLength = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
if ($contentLength > 16384) {
    response(413, [
        'success' => false,
        'message' => 'Ukuran request terlalu besar.'
    ]);
}

// Mendukung JSON (utama) dan form-urlencoded (opsional).
$contentType = strtolower(trim(explode(';', $_SERVER['CONTENT_TYPE'] ?? '')[0]));
if ($contentType === 'application/json') {
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput ?: '', true);

    if (!is_array($input) || json_last_error() !== JSON_ERROR_NONE) {
        response(400, [
            'success' => false,
            'message' => 'Format JSON tidak valid.'
        ]);
    }
} else {
    $input = $_POST;
}

$loginUsername = trim((string) ($input['username'] ?? ''));
$loginPassword = (string) ($input['password'] ?? '');

if ($loginUsername === '' || $loginPassword === '') {
    response(400, [
        'success' => false,
        'message' => 'Username dan password wajib diisi.'
    ]);
}

if (strlen($loginUsername) > 100 || strlen($loginPassword) > 4096) {
    response(400, [
        'success' => false,
        'message' => 'Username atau password melebihi batas.'
    ]);
}

// File koneksi.php berada satu folder dengan apilogin.php: htdocs/soa/
require_once __DIR__ . '/koneksi.php';

if (!isset($koneksi) || !($koneksi instanceof mysqli) || $koneksi->connect_errno) {
    response(500, [
        'success' => false,
        'message' => 'Layanan autentikasi sedang mengalami gangguan.'
    ]);
}

mysqli_set_charset($koneksi, 'utf8mb4');

/*
 * Mengambil akun, role, dan profil mahasiswa (jika akun tersebut mahasiswa).
 * Password hash hanya digunakan untuk verifikasi dan tidak pernah dikirim
 * kembali kepada aplikasi pemanggil.
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
        p.status_aktif,
        r.kode_role,
        r.nama_role,
        m.id_mahasiswa,
        m.npm,
        m.nama_mahasiswa,
        m.status_mahasiswa
    FROM pengguna AS p
    INNER JOIN roles AS r ON r.id_role = p.id_role
    LEFT JOIN mahasiswa AS m ON m.id_pengguna = p.id_pengguna
    WHERE p.username = ?
    LIMIT 1
";

$stmt = mysqli_prepare($koneksi, $sql);
if (!$stmt) {
    error_log('API login prepare error: ' . mysqli_error($koneksi));
    response(500, [
        'success' => false,
        'message' => 'Layanan autentikasi sedang mengalami gangguan.'
    ]);
}

mysqli_stmt_bind_param($stmt, 's', $loginUsername);

if (!mysqli_stmt_execute($stmt)) {
    error_log('API login execute error: ' . mysqli_stmt_error($stmt));
    mysqli_stmt_close($stmt);
    response(500, [
        'success' => false,
        'message' => 'Layanan autentikasi sedang mengalami gangguan.'
    ]);
}

$result = mysqli_stmt_get_result($stmt);
$user = $result ? mysqli_fetch_assoc($result) : null;
mysqli_stmt_close($stmt);

// Gunakan pesan umum agar tidak membocorkan apakah username terdaftar.
if (!$user || !password_verify($loginPassword, $user['password_hash'])) {
    response(401, [
        'success' => false,
        'message' => 'Username atau password salah.'
    ]);
}

if ($user['status_aktif'] !== 'Aktif') {
    response(403, [
        'success' => false,
        'message' => 'Akun tidak aktif. Silakan hubungi administrator.'
    ]);
}

// Perbarui waktu login terakhir.
$update = mysqli_prepare(
    $koneksi,
    'UPDATE pengguna SET last_login = NOW() WHERE id_pengguna = ?'
);

if ($update) {
    mysqli_stmt_bind_param($update, 'i', $user['id_pengguna']);
    if (!mysqli_stmt_execute($update)) {
        error_log('API login last_login update error: ' . mysqli_stmt_error($update));
    }
    mysqli_stmt_close($update);
}

// Catat aktivitas login. Kegagalan pencatatan tidak membatalkan autentikasi.
$aksi = 'LOGIN';
$tabel = 'pengguna';
$recordId = (string) $user['id_pengguna'];
$ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
$userAgent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 2000);

$audit = mysqli_prepare(
    $koneksi,
    'INSERT INTO audit_log
        (id_pengguna, tabel_nama, record_id, aksi, ip_address, user_agent)
     VALUES (?, ?, ?, ?, ?, ?)'
);

if ($audit) {
    mysqli_stmt_bind_param(
        $audit,
        'isssss',
        $user['id_pengguna'],
        $tabel,
        $recordId,
        $aksi,
        $ipAddress,
        $userAgent
    );

    if (!mysqli_stmt_execute($audit)) {
        error_log('API login audit error: ' . mysqli_stmt_error($audit));
    }
    mysqli_stmt_close($audit);
}

// Susun data pengguna yang aman untuk dikirim ke aplikasi pemanggil.
$dataPengguna = [
    'id_pengguna'      => (int) $user['id_pengguna'],
    'username'         => $user['username'],
    'nama_lengkap'     => $user['nama_lengkap'],
    'email'            => $user['email'],
    'id_role'          => (int) $user['id_role'],
    'kode_role'        => $user['kode_role'],
    'nama_role'        => $user['nama_role'],
    'id_universitas'   => $user['id_universitas'] !== null ? (int) $user['id_universitas'] : null,
    'id_fakultas'      => $user['id_fakultas'] !== null ? (int) $user['id_fakultas'] : null,
    'id_program_studi' => $user['id_program_studi'] !== null ? (int) $user['id_program_studi'] : null,
];

// Tambahkan profil mahasiswa jika akun terhubung ke tabel mahasiswa.
if ($user['id_mahasiswa'] !== null) {
    $dataPengguna['mahasiswa'] = [
        'id_mahasiswa'    => (int) $user['id_mahasiswa'],
        'npm'             => $user['npm'],
        'nama_mahasiswa'  => $user['nama_mahasiswa'],
        'status_mahasiswa'=> $user['status_mahasiswa']
    ];
} else {
    $dataPengguna['mahasiswa'] = null;
}

response(200, [
    'success' => true,
    'message' => 'Login berhasil.',
    'data' => [
        'pengguna' => $dataPengguna,
        'login_at' => date('Y-m-d H:i:s')
    ]
]);
