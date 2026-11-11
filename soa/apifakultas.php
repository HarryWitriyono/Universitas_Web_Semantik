<?php
/**
 * apifakultas.php
 * SOA Service Provider untuk CRUD master data fakultas.
 *
 * action=list   : daftar fakultas + nama universitas + jumlah prodi
 * action=detail : detail fakultas
 * action=create : tambah fakultas
 * action=update : ubah fakultas
 * action=delete : hapus fakultas
 *
 * Semua request menggunakan POST JSON dan wajib menyertakan id_pengguna.
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

function apifak_response(int $status, array $payload): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function apifak_error(int $status, string $message): never
{
    apifak_response($status, ['success' => false, 'message' => $message]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    apifak_error(405, 'Metode tidak diizinkan. Gunakan POST.');
}

// Pertahankan pembatasan localhost yang digunakan endpoint Universitas.
$remote = $_SERVER['REMOTE_ADDR'] ?? '';
if (!in_array($remote, ['127.0.0.1', '::1'], true)) {
    apifak_error(403, 'Akses endpoint tidak diizinkan.');
}

if ((int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 32768) {
    apifak_error(413, 'Ukuran request terlalu besar.');
}

$input = json_decode(file_get_contents('php://input') ?: '', true);
if (!is_array($input) || json_last_error() !== JSON_ERROR_NONE) {
    apifak_error(400, 'Format JSON tidak valid.');
}

$idPengguna = filter_var($input['id_pengguna'] ?? null, FILTER_VALIDATE_INT);
if (!$idPengguna || $idPengguna < 1) {
    apifak_error(400, 'Identitas pengguna tidak valid.');
}

require_once __DIR__ . '/koneksi.php';

if (!isset($koneksi) || !($koneksi instanceof mysqli) || $koneksi->connect_errno) {
    error_log('API fakultas: koneksi database gagal.');
    apifak_error(500, 'Layanan database sedang mengalami gangguan.');
}
mysqli_set_charset($koneksi, 'utf8mb4');

// Verifikasi ulang hak akses pada Service Provider.
$sqlAuth = "
    SELECT p.id_pengguna
    FROM pengguna p
    INNER JOIN roles r ON r.id_role = p.id_role
    WHERE p.id_pengguna = ?
      AND p.status_aktif = 'Aktif'
      AND r.kode_role = 'ADMIN'
    LIMIT 1
";
$stmtAuth = mysqli_prepare($koneksi, $sqlAuth);
if (!$stmtAuth) {
    error_log('API fakultas auth prepare: ' . mysqli_error($koneksi));
    apifak_error(500, 'Gagal memverifikasi hak akses.');
}
mysqli_stmt_bind_param($stmtAuth, 'i', $idPengguna);
mysqli_stmt_execute($stmtAuth);
$authResult = mysqli_stmt_get_result($stmtAuth);
$authorized = $authResult ? mysqli_fetch_assoc($authResult) : null;
mysqli_stmt_close($stmtAuth);

if (!$authorized) {
    apifak_error(403, 'Akses ditolak. Hanya ADMIN yang dapat mengelola fakultas.');
}

$action = strtolower(trim((string) ($input['action'] ?? 'list')));

function apifak_get_detail(mysqli $db, int $id): ?array
{
    $sql = "
        SELECT f.id_fakultas, f.id_universitas, f.kode_fakultas,
               f.nama_fakultas, u.kode_universitas, u.nama_universitas
        FROM fakultas f
        INNER JOIN universitas u ON u.id_universitas = f.id_universitas
        WHERE f.id_fakultas = ?
        LIMIT 1
    ";
    $stmt = mysqli_prepare($db, $sql);
    if (!$stmt) {
        error_log('API fakultas detail prepare: ' . mysqli_error($db));
        apifak_error(500, 'Gagal mengambil detail fakultas.');
    }
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = $result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);
    return $row ?: null;
}

function apifak_validate(array $input): array
{
    $idUniversitas = filter_var($input['id_universitas'] ?? null, FILTER_VALIDATE_INT);
    $kode = strtoupper(trim((string) ($input['kode_fakultas'] ?? '')));
    $nama = trim((string) ($input['nama_fakultas'] ?? ''));

    if (!$idUniversitas || $idUniversitas < 1) {
        apifak_error(422, 'Universitas wajib dipilih.');
    }
    if ($kode === '') {
        apifak_error(422, 'Kode fakultas wajib diisi.');
    }
    if (mb_strlen($kode) > 20 || !preg_match('/^[A-Z0-9_-]+$/', $kode)) {
        apifak_error(422, 'Kode fakultas maksimal 20 karakter dan hanya boleh berisi huruf kapital, angka, underscore, dan tanda minus.');
    }
    if ($nama === '') {
        apifak_error(422, 'Nama fakultas wajib diisi.');
    }
    if (mb_strlen($nama) > 200) {
        apifak_error(422, 'Nama fakultas maksimal 200 karakter.');
    }

    return [
        'id_universitas' => (int) $idUniversitas,
        'kode_fakultas' => $kode,
        'nama_fakultas' => $nama
    ];
}

function apifak_check_universitas(mysqli $db, int $id): void
{
    $stmt = mysqli_prepare($db, 'SELECT id_universitas FROM universitas WHERE id_universitas = ? LIMIT 1');
    if (!$stmt) {
        error_log('API fakultas check universitas: ' . mysqli_error($db));
        apifak_error(500, 'Gagal memeriksa universitas.');
    }
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $found = $result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);

    if (!$found) {
        apifak_error(422, 'Universitas yang dipilih tidak ditemukan.');
    }
}

function apifak_check_duplicate(mysqli $db, int $idUniversitas, string $kode, ?int $excludeId = null): void
{
    if ($excludeId !== null) {
        $sql = 'SELECT id_fakultas FROM fakultas
                WHERE id_universitas = ? AND kode_fakultas = ?
                  AND id_fakultas <> ? LIMIT 1';
        $stmt = mysqli_prepare($db, $sql);
        if (!$stmt) {
            error_log('API fakultas duplicate prepare: ' . mysqli_error($db));
            apifak_error(500, 'Gagal memeriksa kode fakultas.');
        }
        mysqli_stmt_bind_param($stmt, 'isi', $idUniversitas, $kode, $excludeId);
    } else {
        $sql = 'SELECT id_fakultas FROM fakultas
                WHERE id_universitas = ? AND kode_fakultas = ? LIMIT 1';
        $stmt = mysqli_prepare($db, $sql);
        if (!$stmt) {
            error_log('API fakultas duplicate prepare: ' . mysqli_error($db));
            apifak_error(500, 'Gagal memeriksa kode fakultas.');
        }
        mysqli_stmt_bind_param($stmt, 'is', $idUniversitas, $kode);
    }

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $duplicate = $result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);

    if ($duplicate) {
        apifak_error(409, 'Kode fakultas tersebut sudah digunakan pada universitas yang dipilih.');
    }
}

if ($action === 'list') {
    $sql = "
        SELECT f.id_fakultas, f.kode_fakultas, f.nama_fakultas,
               f.id_universitas, u.kode_universitas, u.nama_universitas,
               (SELECT COUNT(*) FROM program_studi ps
                WHERE ps.id_fakultas = f.id_fakultas) AS jumlah_prodi
        FROM fakultas f
        INNER JOIN universitas u ON u.id_universitas = f.id_universitas
        ORDER BY u.nama_universitas ASC, f.nama_fakultas ASC
    ";
    $result = mysqli_query($koneksi, $sql);
    if (!$result) {
        error_log('API fakultas list: ' . mysqli_error($koneksi));
        apifak_error(500, 'Gagal mengambil daftar fakultas.');
    }

    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }

    apifak_response(200, [
        'success' => true,
        'message' => 'Daftar fakultas berhasil diambil.',
        'data' => ['fakultas' => $rows]
    ]);
}

if ($action === 'detail') {
    $id = filter_var($input['id_fakultas'] ?? null, FILTER_VALIDATE_INT);
    if (!$id || $id < 1) {
        apifak_error(400, 'ID fakultas tidak valid.');
    }
    $row = apifak_get_detail($koneksi, (int) $id);
    if (!$row) {
        apifak_error(404, 'Data fakultas tidak ditemukan.');
    }
    apifak_response(200, [
        'success' => true,
        'message' => 'Detail fakultas berhasil diambil.',
        'data' => ['fakultas' => $row]
    ]);
}

if ($action === 'create') {
    $data = apifak_validate($input);
    apifak_check_universitas($koneksi, $data['id_universitas']);
    apifak_check_duplicate($koneksi, $data['id_universitas'], $data['kode_fakultas']);

    $stmt = mysqli_prepare(
        $koneksi,
        'INSERT INTO fakultas (id_universitas, kode_fakultas, nama_fakultas)
         VALUES (?, ?, ?)'
    );
    if (!$stmt) {
        error_log('API fakultas insert prepare: ' . mysqli_error($koneksi));
        apifak_error(500, 'Gagal menyiapkan penyimpanan fakultas.');
    }
    mysqli_stmt_bind_param(
        $stmt, 'iss',
        $data['id_universitas'], $data['kode_fakultas'], $data['nama_fakultas']
    );

    if (!mysqli_stmt_execute($stmt)) {
        error_log('API fakultas insert: ' . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        apifak_error(500, 'Gagal menambahkan fakultas.');
    }
    $newId = mysqli_insert_id($koneksi);
    mysqli_stmt_close($stmt);

    apifak_response(201, [
        'success' => true,
        'message' => 'Data fakultas berhasil ditambahkan.',
        'data' => ['id_fakultas' => $newId]
    ]);
}

if ($action === 'update') {
    $id = filter_var($input['id_fakultas'] ?? null, FILTER_VALIDATE_INT);
    if (!$id || $id < 1) {
        apifak_error(400, 'ID fakultas tidak valid.');
    }
    if (!apifak_get_detail($koneksi, (int) $id)) {
        apifak_error(404, 'Data fakultas tidak ditemukan.');
    }

    $data = apifak_validate($input);
    apifak_check_universitas($koneksi, $data['id_universitas']);
    apifak_check_duplicate(
        $koneksi, $data['id_universitas'], $data['kode_fakultas'], (int) $id
    );

    $stmt = mysqli_prepare(
        $koneksi,
        'UPDATE fakultas
         SET id_universitas = ?, kode_fakultas = ?, nama_fakultas = ?
         WHERE id_fakultas = ?'
    );
    if (!$stmt) {
        error_log('API fakultas update prepare: ' . mysqli_error($koneksi));
        apifak_error(500, 'Gagal menyiapkan perubahan fakultas.');
    }
    mysqli_stmt_bind_param(
        $stmt, 'issi',
        $data['id_universitas'], $data['kode_fakultas'],
        $data['nama_fakultas'], $id
    );

    if (!mysqli_stmt_execute($stmt)) {
        error_log('API fakultas update: ' . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        apifak_error(500, 'Gagal memperbarui fakultas.');
    }
    mysqli_stmt_close($stmt);

    apifak_response(200, [
        'success' => true,
        'message' => 'Data fakultas berhasil diperbarui.'
    ]);
}

if ($action === 'delete') {
    $id = filter_var($input['id_fakultas'] ?? null, FILTER_VALIDATE_INT);
    if (!$id || $id < 1) {
        apifak_error(400, 'ID fakultas tidak valid.');
    }

    $existing = apifak_get_detail($koneksi, (int) $id);
    if (!$existing) {
        apifak_error(404, 'Data fakultas tidak ditemukan.');
    }

    // Aturan bisnis dari aplikasi asal: tidak boleh menghapus jika masih ada prodi.
    $stmt = mysqli_prepare(
        $koneksi,
        'SELECT COUNT(*) AS total FROM program_studi WHERE id_fakultas = ?'
    );
    if (!$stmt) {
        error_log('API fakultas delete check prodi: ' . mysqli_error($koneksi));
        apifak_error(500, 'Gagal memeriksa Program Studi terkait.');
    }
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $prodi = $result ? mysqli_fetch_assoc($result) : ['total' => 0];
    mysqli_stmt_close($stmt);

    if ((int) ($prodi['total'] ?? 0) > 0) {
        apifak_error(409, 'Fakultas tidak dapat dihapus karena masih memiliki Program Studi.');
    }

    // Aturan bisnis dari aplikasi asal: tidak boleh menghapus jika masih dipakai pengguna.
    $stmt = mysqli_prepare(
        $koneksi,
        'SELECT COUNT(*) AS total FROM pengguna WHERE id_fakultas = ?'
    );
    if (!$stmt) {
        error_log('API fakultas delete check pengguna: ' . mysqli_error($koneksi));
        apifak_error(500, 'Gagal memeriksa pengguna terkait.');
    }
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $pengguna = $result ? mysqli_fetch_assoc($result) : ['total' => 0];
    mysqli_stmt_close($stmt);

    if ((int) ($pengguna['total'] ?? 0) > 0) {
        apifak_error(409, 'Fakultas tidak dapat dihapus karena masih digunakan oleh pengguna.');
    }

    $stmt = mysqli_prepare($koneksi, 'DELETE FROM fakultas WHERE id_fakultas = ?');
    if (!$stmt) {
        error_log('API fakultas delete prepare: ' . mysqli_error($koneksi));
        apifak_error(500, 'Gagal menyiapkan penghapusan fakultas.');
    }
    mysqli_stmt_bind_param($stmt, 'i', $id);

    if (!mysqli_stmt_execute($stmt)) {
        error_log('API fakultas delete: ' . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        apifak_error(500, 'Gagal menghapus fakultas.');
    }
    mysqli_stmt_close($stmt);

    apifak_response(200, [
        'success' => true,
        'message' => 'Fakultas "' . $existing['nama_fakultas'] . '" berhasil dihapus.'
    ]);
}

apifak_error(400, 'Aksi API tidak dikenal.');
