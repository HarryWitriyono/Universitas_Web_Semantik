<?php
/**
 * apidashboard.php
 * Menyediakan statistik dashboard untuk SOA Consumer SIM Mahasiswa.
 *
 * Request:
 * POST /soa/apidashboard.php
 * JSON: {"id_pengguna": 1}
 *
 * Catatan:
 * Endpoint ini mengambil role dan scope dari database berdasarkan
 * id_pengguna; tidak mempercayai role/scope yang dikirim consumer.
 * Untuk deployment lintas server/produksi, tambahkan autentikasi
 * antarlayanan (mis. access token) dan HTTPS.
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

function api_response(int $status, array $payload): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    api_response(405, [
        'success' => false,
        'message' => 'Metode tidak diizinkan. Gunakan POST.'
    ]);
}

// Untuk tahap praktikum lokal: hanya izinkan pemanggilan dari komputer SOA sendiri.
// Hapus/ubah pembatasan ini saat arsitektur dipindah ke beberapa server,
// setelah autentikasi antarlayanan diterapkan.
$remoteAddress = $_SERVER['REMOTE_ADDR'] ?? '';
if (!in_array($remoteAddress, ['127.0.0.1', '::1'], true)) {
    api_response(403, [
        'success' => false,
        'message' => 'Akses endpoint ini hanya diizinkan dari server aplikasi.'
    ]);
}

$raw = file_get_contents('php://input');
$input = json_decode($raw ?: '', true);
if (!is_array($input) || json_last_error() !== JSON_ERROR_NONE) {
    api_response(400, [
        'success' => false,
        'message' => 'Format JSON tidak valid.'
    ]);
}

$idPengguna = filter_var($input['id_pengguna'] ?? null, FILTER_VALIDATE_INT);
if (!$idPengguna || $idPengguna < 1) {
    api_response(400, [
        'success' => false,
        'message' => 'ID pengguna tidak valid.'
    ]);
}

require_once __DIR__ . '/koneksi.php';

if (!isset($koneksi) || !($koneksi instanceof mysqli) || $koneksi->connect_errno) {
    error_log('SOA dashboard: database connection unavailable.');
    api_response(500, [
        'success' => false,
        'message' => 'Layanan dashboard sedang mengalami gangguan.'
    ]);
}

mysqli_set_charset($koneksi, 'utf8mb4');

// Ambil identitas, role, dan scope aktual dari database pusat.
$sqlUser = "
    SELECT
        p.id_pengguna,
        p.id_role,
        p.id_universitas,
        p.id_fakultas,
        p.id_program_studi,
        p.username,
        p.nama_lengkap,
        p.status_aktif,
        r.kode_role,
        r.nama_role
    FROM pengguna p
    INNER JOIN roles r ON r.id_role = p.id_role
    WHERE p.id_pengguna = ?
    LIMIT 1
";

$stmt = mysqli_prepare($koneksi, $sqlUser);
if (!$stmt) {
    error_log('SOA dashboard prepare user: ' . mysqli_error($koneksi));
    api_response(500, [
        'success' => false,
        'message' => 'Gagal memproses permintaan dashboard.'
    ]);
}

mysqli_stmt_bind_param($stmt, 'i', $idPengguna);
mysqli_stmt_execute($stmt);
$resUser = mysqli_stmt_get_result($stmt);
$user = $resUser ? mysqli_fetch_assoc($resUser) : null;
mysqli_stmt_close($stmt);

if (!$user || $user['status_aktif'] !== 'Aktif') {
    api_response(403, [
        'success' => false,
        'message' => 'Akun tidak ditemukan atau tidak aktif.'
    ]);
}

$role = (string) $user['kode_role'];
$idProdi = (int) ($user['id_program_studi'] ?? 0);
$idFakultas = (int) ($user['id_fakultas'] ?? 0);
$idUniversitas = (int) ($user['id_universitas'] ?? 0);

$jumlahMahasiswa = 0;
$jumlahProdi = 0;
$jumlahFakultas = 0;
$prodiLabel = 'Seluruh Program Studi';

/**
 * Menjalankan query COUNT sederhana dengan parameter integer.
 */
function count_query(mysqli $db, string $sql, string $types = '', array $params = []): int
{
    $stmt = mysqli_prepare($db, $sql);
    if (!$stmt) {
        error_log('SOA dashboard count prepare: ' . mysqli_error($db));
        return 0;
    }

    if ($types !== '' && count($params) === 1) {
        // Semua pemanggilan count_query pada endpoint ini memakai satu parameter integer.
        $parameter = (int) $params[0];
        mysqli_stmt_bind_param($stmt, $types, $parameter);
    }

    if (!mysqli_stmt_execute($stmt)) {
        error_log('SOA dashboard count execute: ' . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        return 0;
    }

    $result = mysqli_stmt_get_result($stmt);
    $row = $result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);

    return (int) ($row['total'] ?? 0);
}

/**
 * Mengambil satu label berdasarkan ID.
 */
function label_query(mysqli $db, string $sql, int $id, string $field): ?string
{
    $stmt = mysqli_prepare($db, $sql);
    if (!$stmt) {
        error_log('SOA dashboard label prepare: ' . mysqli_error($db));
        return null;
    }

    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = $result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);

    return $row[$field] ?? null;
}

// Statistik mahasiswa berdasarkan scope role.
if ($role === 'MAHASISWA') {
    $jumlahMahasiswa = count_query(
        $koneksi,
        'SELECT COUNT(*) AS total FROM mahasiswa WHERE id_pengguna = ?',
        'i',
        [$idPengguna]
    );
    $prodiLabel = 'Portal Mahasiswa';

} elseif ($role === 'OPERATOR_PRODI') {
    $jumlahMahasiswa = count_query(
        $koneksi,
        'SELECT COUNT(*) AS total FROM mahasiswa WHERE id_program_studi = ?',
        'i',
        [$idProdi]
    );

    if ($idProdi > 0) {
        $prodiLabel = label_query(
            $koneksi,
            'SELECT nama_program_studi FROM program_studi WHERE id_program_studi = ?',
            $idProdi,
            'nama_program_studi'
        ) ?? 'Program Studi';
    }

} elseif ($role === 'DEKANAT') {
    $jumlahMahasiswa = count_query(
        $koneksi,
        'SELECT COUNT(*) AS total
         FROM mahasiswa m
         INNER JOIN program_studi ps ON ps.id_program_studi = m.id_program_studi
         WHERE ps.id_fakultas = ?',
        'i',
        [$idFakultas]
    );

    if ($idFakultas > 0) {
        $prodiLabel = label_query(
            $koneksi,
            'SELECT nama_fakultas FROM fakultas WHERE id_fakultas = ?',
            $idFakultas,
            'nama_fakultas'
        ) ?? 'Fakultas';
    }

} elseif ($role === 'REKTORAT') {
    $jumlahMahasiswa = count_query(
        $koneksi,
        'SELECT COUNT(*) AS total
         FROM mahasiswa m
         INNER JOIN program_studi ps ON ps.id_program_studi = m.id_program_studi
         INNER JOIN fakultas f ON f.id_fakultas = ps.id_fakultas
         WHERE f.id_universitas = ?',
        'i',
        [$idUniversitas]
    );

    if ($idUniversitas > 0) {
        $prodiLabel = label_query(
            $koneksi,
            'SELECT nama_universitas FROM universitas WHERE id_universitas = ?',
            $idUniversitas,
            'nama_universitas'
        ) ?? 'Universitas';
    }

} elseif ($role === 'ADMIN') {
    $jumlahMahasiswa = count_query(
        $koneksi,
        'SELECT COUNT(*) AS total FROM mahasiswa'
    );

} else {
    api_response(403, [
        'success' => false,
        'message' => 'Role pengguna tidak dikenali.'
    ]);
}

// Statistik program studi dan fakultas sesuai scope role.
if ($role === 'ADMIN') {
    $jumlahProdi = count_query(
        $koneksi,
        "SELECT COUNT(*) AS total FROM program_studi WHERE status_aktif = 'Aktif'"
    );
    $jumlahFakultas = count_query(
        $koneksi,
        'SELECT COUNT(*) AS total FROM fakultas'
    );

} elseif ($role === 'OPERATOR_PRODI' && $idProdi > 0) {
    $stmt = mysqli_prepare(
        $koneksi,
        "SELECT
            COUNT(*) AS jumlah_prodi,
            COUNT(DISTINCT f.id_fakultas) AS jumlah_fakultas
         FROM program_studi ps
         INNER JOIN fakultas f ON f.id_fakultas = ps.id_fakultas
         WHERE ps.id_program_studi = ?
           AND ps.status_aktif = 'Aktif'"
    );

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $idProdi);
        mysqli_stmt_execute($stmt);
        $r = mysqli_stmt_get_result($stmt);
        $row = $r ? mysqli_fetch_assoc($r) : null;
        $jumlahProdi = (int) ($row['jumlah_prodi'] ?? 0);
        $jumlahFakultas = (int) ($row['jumlah_fakultas'] ?? 0);
        mysqli_stmt_close($stmt);
    }

} elseif ($role === 'DEKANAT' && $idFakultas > 0) {
    $stmt = mysqli_prepare(
        $koneksi,
        "SELECT
            COUNT(*) AS jumlah_prodi,
            COUNT(DISTINCT id_fakultas) AS jumlah_fakultas
         FROM program_studi
         WHERE id_fakultas = ?
           AND status_aktif = 'Aktif'"
    );

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $idFakultas);
        mysqli_stmt_execute($stmt);
        $r = mysqli_stmt_get_result($stmt);
        $row = $r ? mysqli_fetch_assoc($r) : null;
        $jumlahProdi = (int) ($row['jumlah_prodi'] ?? 0);
        $jumlahFakultas = (int) ($row['jumlah_fakultas'] ?? 0);
        mysqli_stmt_close($stmt);
    }

} elseif ($role === 'REKTORAT' && $idUniversitas > 0) {
    $stmt = mysqli_prepare(
        $koneksi,
        "SELECT
            COUNT(DISTINCT ps.id_program_studi) AS jumlah_prodi,
            COUNT(DISTINCT f.id_fakultas) AS jumlah_fakultas
         FROM fakultas f
         LEFT JOIN program_studi ps
             ON ps.id_fakultas = f.id_fakultas
            AND ps.status_aktif = 'Aktif'
         WHERE f.id_universitas = ?"
    );

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $idUniversitas);
        mysqli_stmt_execute($stmt);
        $r = mysqli_stmt_get_result($stmt);
        $row = $r ? mysqli_fetch_assoc($r) : null;
        $jumlahProdi = (int) ($row['jumlah_prodi'] ?? 0);
        $jumlahFakultas = (int) ($row['jumlah_fakultas'] ?? 0);
        mysqli_stmt_close($stmt);
    }
}

api_response(200, [
    'success' => true,
    'message' => 'Data dashboard berhasil diambil.',
    'data' => [
        'pengguna' => [
            'id_pengguna' => (int) $user['id_pengguna'],
            'username' => $user['username'],
            'nama_lengkap' => $user['nama_lengkap'],
            'kode_role' => $role,
            'nama_role' => $user['nama_role']
        ],
        'statistik' => [
            'jumlah_mahasiswa' => $jumlahMahasiswa,
            'jumlah_prodi' => $jumlahProdi,
            'jumlah_fakultas' => $jumlahFakultas
        ],
        'scope_label' => $prodiLabel
    ]
]);
