<?php
/**
 * apiuniversitas.php
 * SOA Service Provider untuk CRUD master data universitas.
 *
 * Semua operasi menggunakan POST JSON:
 * action=list | detail | create | update | delete
 *
 * Consumer mengirim id_pengguna dari session lokal. Server memverifikasi
 * kembali bahwa pengguna tersebut masih aktif dan memiliki role ADMIN.
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

function apiuniversitas_response(int $status, array $payload): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function apiuniversitas_error(int $status, string $message): never
{
    apiuniversitas_response($status, [
        'success' => false,
        'message' => $message
    ]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    apiuniversitas_error(405, 'Metode tidak diizinkan. Gunakan POST.');
}

// Tahap praktikum lokal: endpoint hanya menerima request dari host SOA.
// Untuk Server A/B, ganti dengan autentikasi antarlayanan + HTTPS.
$remote = $_SERVER['REMOTE_ADDR'] ?? '';
if (!in_array($remote, ['127.0.0.1', '::1'], true)) {
    apiuniversitas_error(403, 'Akses endpoint tidak diizinkan.');
}

$contentLength = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
if ($contentLength > 32768) {
    apiuniversitas_error(413, 'Ukuran request terlalu besar.');
}

$raw = file_get_contents('php://input');
$input = json_decode($raw ?: '', true);
if (!is_array($input) || json_last_error() !== JSON_ERROR_NONE) {
    apiuniversitas_error(400, 'Format JSON tidak valid.');
}

$idPengguna = filter_var($input['id_pengguna'] ?? null, FILTER_VALIDATE_INT);
if (!$idPengguna || $idPengguna < 1) {
    apiuniversitas_error(400, 'Identitas pengguna tidak valid.');
}

require_once __DIR__ . '/koneksi.php';

if (!isset($koneksi) || !($koneksi instanceof mysqli) || $koneksi->connect_errno) {
    error_log('API universitas: koneksi database gagal.');
    apiuniversitas_error(500, 'Layanan database sedang mengalami gangguan.');
}
mysqli_set_charset($koneksi, 'utf8mb4');

// Verifikasi role di server pusat; jangan percaya kode_role dari consumer.
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
    error_log('API universitas auth prepare: ' . mysqli_error($koneksi));
    apiuniversitas_error(500, 'Gagal memverifikasi hak akses.');
}
mysqli_stmt_bind_param($stmtAuth, 'i', $idPengguna);
mysqli_stmt_execute($stmtAuth);
$authResult = mysqli_stmt_get_result($stmtAuth);
$authorized = $authResult ? mysqli_fetch_assoc($authResult) : null;
mysqli_stmt_close($stmtAuth);

if (!$authorized) {
    apiuniversitas_error(403, 'Akses ditolak. Hanya ADMIN yang dapat mengelola universitas.');
}

$action = strtolower(trim((string) ($input['action'] ?? 'list')));

function apiuniversitas_get_detail(mysqli $db, int $id): ?array
{
    $sql = "SELECT id_universitas, kode_universitas, nama_universitas,
                   slogan, alamat, kota, provinsi, kode_pos,
                   website, email, telepon
            FROM universitas
            WHERE id_universitas = ?
            LIMIT 1";
    $stmt = mysqli_prepare($db, $sql);
    if (!$stmt) {
        error_log('API universitas detail prepare: ' . mysqli_error($db));
        apiuniversitas_error(500, 'Gagal mengambil detail universitas.');
    }

    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = $result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);

    return $row ?: null;
}

function apiuniversitas_validate(array $data, bool $creating): array
{
    $fields = [
        'kode_universitas', 'nama_universitas', 'slogan', 'alamat',
        'kota', 'provinsi', 'kode_pos', 'website', 'email', 'telepon'
    ];

    $clean = [];
    foreach ($fields as $field) {
        $clean[$field] = trim((string) ($data[$field] ?? ''));
    }

    if ($creating) {
        $clean['kode_universitas'] = strtoupper($clean['kode_universitas']);
        if ($clean['kode_universitas'] === '') {
            apiuniversitas_error(422, 'Kode universitas wajib diisi.');
        }
        if (strlen($clean['kode_universitas']) > 20 ||
            !preg_match('/^[A-Z0-9_-]+$/', $clean['kode_universitas'])) {
            apiuniversitas_error(422, 'Kode universitas maksimal 20 karakter dan hanya boleh berisi huruf kapital, angka, underscore, atau tanda minus.');
        }
    }

    if ($clean['nama_universitas'] === '') {
        apiuniversitas_error(422, 'Nama universitas wajib diisi.');
    }
    if (mb_strlen($clean['nama_universitas']) > 200) {
        apiuniversitas_error(422, 'Nama universitas maksimal 200 karakter.');
    }

    $limits = [
        'slogan' => 255, 'alamat' => 1000, 'kota' => 100,
        'provinsi' => 100, 'kode_pos' => 10, 'website' => 255,
        'email' => 150, 'telepon' => 50
    ];
    foreach ($limits as $field => $max) {
        if (mb_strlen($clean[$field]) > $max) {
            apiuniversitas_error(422, ucfirst(str_replace('_', ' ', $field)) . " maksimal {$max} karakter.");
        }
    }

    if ($clean['website'] !== '' && !filter_var($clean['website'], FILTER_VALIDATE_URL)) {
        apiuniversitas_error(422, 'Format website tidak valid.');
    }
    if ($clean['email'] !== '' && !filter_var($clean['email'], FILTER_VALIDATE_EMAIL)) {
        apiuniversitas_error(422, 'Format email tidak valid.');
    }

    return $clean;
}

if ($action === 'list') {
    $sql = "SELECT id_universitas, kode_universitas, nama_universitas,
                   slogan, kota, provinsi, website, email, telepon
            FROM universitas
            ORDER BY nama_universitas ASC";
    $result = mysqli_query($koneksi, $sql);
    if (!$result) {
        error_log('API universitas list: ' . mysqli_error($koneksi));
        apiuniversitas_error(500, 'Gagal mengambil daftar universitas.');
    }

    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }

    apiuniversitas_response(200, [
        'success' => true,
        'message' => 'Daftar universitas berhasil diambil.',
        'data' => ['universitas' => $rows]
    ]);
}

if ($action === 'detail') {
    $id = filter_var($input['id_universitas'] ?? null, FILTER_VALIDATE_INT);
    if (!$id || $id < 1) {
        apiuniversitas_error(400, 'ID universitas tidak valid.');
    }
    $row = apiuniversitas_get_detail($koneksi, (int) $id);
    if (!$row) {
        apiuniversitas_error(404, 'Data universitas tidak ditemukan.');
    }

    apiuniversitas_response(200, [
        'success' => true,
        'message' => 'Detail universitas berhasil diambil.',
        'data' => ['universitas' => $row]
    ]);
}

if ($action === 'create') {
    $data = apiuniversitas_validate($input, true);

    $stmt = mysqli_prepare(
        $koneksi,
        'SELECT id_universitas FROM universitas WHERE kode_universitas = ? LIMIT 1'
    );
    if (!$stmt) {
        error_log('API universitas duplicate prepare: ' . mysqli_error($koneksi));
        apiuniversitas_error(500, 'Gagal memeriksa kode universitas.');
    }
    mysqli_stmt_bind_param($stmt, 's', $data['kode_universitas']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $exists = $result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);

    if ($exists) {
        apiuniversitas_error(409, 'Kode universitas sudah digunakan.');
    }

    $sql = "INSERT INTO universitas
            (kode_universitas, nama_universitas, slogan, alamat, kota,
             provinsi, kode_pos, website, email, telepon)
            VALUES (?, ?, NULLIF(?, ''), NULLIF(?, ''), NULLIF(?, ''),
                    NULLIF(?, ''), NULLIF(?, ''), NULLIF(?, ''),
                    NULLIF(?, ''), NULLIF(?, ''))";
    $stmt = mysqli_prepare($koneksi, $sql);
    if (!$stmt) {
        error_log('API universitas insert prepare: ' . mysqli_error($koneksi));
        apiuniversitas_error(500, 'Gagal menyiapkan penyimpanan universitas.');
    }

    mysqli_stmt_bind_param(
        $stmt, 'ssssssssss',
        $data['kode_universitas'], $data['nama_universitas'],
        $data['slogan'], $data['alamat'], $data['kota'],
        $data['provinsi'], $data['kode_pos'], $data['website'],
        $data['email'], $data['telepon']
    );

    if (!mysqli_stmt_execute($stmt)) {
        error_log('API universitas insert: ' . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        apiuniversitas_error(500, 'Gagal menambahkan universitas.');
    }

    $newId = mysqli_insert_id($koneksi);
    mysqli_stmt_close($stmt);

    apiuniversitas_response(201, [
        'success' => true,
        'message' => 'Data universitas berhasil ditambahkan.',
        'data' => ['id_universitas' => $newId]
    ]);
}

if ($action === 'update') {
    $id = filter_var($input['id_universitas'] ?? null, FILTER_VALIDATE_INT);
    if (!$id || $id < 1) {
        apiuniversitas_error(400, 'ID universitas tidak valid.');
    }

    $existing = apiuniversitas_get_detail($koneksi, (int) $id);
    if (!$existing) {
        apiuniversitas_error(404, 'Data universitas tidak ditemukan.');
    }

    $data = apiuniversitas_validate($input, false);

    $sql = "UPDATE universitas
            SET nama_universitas = ?,
                slogan = NULLIF(?, ''),
                alamat = NULLIF(?, ''),
                kota = NULLIF(?, ''),
                provinsi = NULLIF(?, ''),
                kode_pos = NULLIF(?, ''),
                website = NULLIF(?, ''),
                email = NULLIF(?, ''),
                telepon = NULLIF(?, '')
            WHERE id_universitas = ?";
    $stmt = mysqli_prepare($koneksi, $sql);
    if (!$stmt) {
        error_log('API universitas update prepare: ' . mysqli_error($koneksi));
        apiuniversitas_error(500, 'Gagal menyiapkan perubahan universitas.');
    }

    mysqli_stmt_bind_param(
        $stmt, 'sssssssssi',
        $data['nama_universitas'], $data['slogan'], $data['alamat'],
        $data['kota'], $data['provinsi'], $data['kode_pos'],
        $data['website'], $data['email'], $data['telepon'], $id
    );

    if (!mysqli_stmt_execute($stmt)) {
        error_log('API universitas update: ' . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        apiuniversitas_error(500, 'Gagal memperbarui universitas.');
    }
    mysqli_stmt_close($stmt);

    apiuniversitas_response(200, [
        'success' => true,
        'message' => 'Data universitas berhasil diperbarui.'
    ]);
}

if ($action === 'delete') {
    $id = filter_var($input['id_universitas'] ?? null, FILTER_VALIDATE_INT);
    if (!$id || $id < 1) {
        apiuniversitas_error(400, 'ID universitas tidak valid.');
    }

    $existing = apiuniversitas_get_detail($koneksi, (int) $id);
    if (!$existing) {
        apiuniversitas_error(404, 'Data universitas tidak ditemukan.');
    }

    // Pertahankan aturan bisnis aplikasi lama: universitas dengan fakultas tidak boleh dihapus.
    $stmt = mysqli_prepare(
        $koneksi,
        'SELECT COUNT(*) AS total FROM fakultas WHERE id_universitas = ?'
    );
    if (!$stmt) {
        error_log('API universitas delete check: ' . mysqli_error($koneksi));
        apiuniversitas_error(500, 'Gagal memeriksa data fakultas terkait.');
    }
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $related = $result ? mysqli_fetch_assoc($result) : ['total' => 0];
    mysqli_stmt_close($stmt);

    if ((int) ($related['total'] ?? 0) > 0) {
        apiuniversitas_error(409, 'Universitas tidak dapat dihapus karena masih memiliki Fakultas.');
    }

    $stmt = mysqli_prepare($koneksi, 'DELETE FROM universitas WHERE id_universitas = ?');
    if (!$stmt) {
        error_log('API universitas delete prepare: ' . mysqli_error($koneksi));
        apiuniversitas_error(500, 'Gagal menyiapkan penghapusan universitas.');
    }
    mysqli_stmt_bind_param($stmt, 'i', $id);

    if (!mysqli_stmt_execute($stmt)) {
        error_log('API universitas delete: ' . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        apiuniversitas_error(500, 'Gagal menghapus universitas.');
    }
    mysqli_stmt_close($stmt);

    apiuniversitas_response(200, [
        'success' => true,
        'message' => 'Universitas "' . $existing['nama_universitas'] . '" berhasil dihapus.'
    ]);
}

apiuniversitas_error(400, 'Aksi API tidak dikenal.');
