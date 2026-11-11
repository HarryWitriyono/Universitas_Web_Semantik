<?php
/**
 * SOA Provider - API Pengguna
 * Letakkan di htdocs/soa/apipengguna.php
 */
require_once __DIR__ . '/koneksi.php';

header('Content-Type: application/json; charset=utf-8');

function pg_json(bool $success, string $message = '', array $data = [], int $httpCode = 200): void {
    http_response_code($httpCode);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function pg_input(): array {
    $raw = file_get_contents('php://input');
    $json = json_decode($raw, true);
    return is_array($json) ? $json : ($_POST ?: []);
}

function pg_admin_valid(mysqli $db, int $actorId): bool {
    if ($actorId <= 0) return false;
    $stmt = mysqli_prepare(
        $db,
        "SELECT p.id_pengguna
         FROM pengguna p
         INNER JOIN roles r ON r.id_role = p.id_role
         WHERE p.id_pengguna = ?
           AND p.status_aktif = 'Aktif'
           AND r.kode_role = 'ADMIN'
         LIMIT 1"
    );
    if (!$stmt) return false;
    mysqli_stmt_bind_param($stmt, 'i', $actorId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $valid = $result && mysqli_num_rows($result) === 1;
    mysqli_stmt_close($stmt);
    return $valid;
}

function pg_fetch_all(mysqli $db, string $sql): array {
    $result = mysqli_query($db, $sql);
    if (!$result) return [];
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) $rows[] = $row;
    mysqli_free_result($result);
    return $rows;
}

function pg_role_rules(string $code): array {
    return match ($code) {
        'ADMIN' => ['universitas' => true, 'fakultas' => false, 'prodi' => false],
        'OPERATOR_PRODI' => ['universitas' => true, 'fakultas' => true, 'prodi' => true],
        'DEKANAT' => ['universitas' => true, 'fakultas' => true, 'prodi' => false],
        'REKTORAT' => ['universitas' => true, 'fakultas' => false, 'prodi' => false],
        'MAHASISWA' => ['universitas' => true, 'fakultas' => true, 'prodi' => true],
        default => ['universitas' => false, 'fakultas' => false, 'prodi' => false],
    };
}

function pg_validate_scope(mysqli $db, string $roleCode, ?int $univId, ?int $facultyId, ?int $studyId): string {
    $rules = pg_role_rules($roleCode);

    if ($rules['universitas'] && !$univId) return 'Universitas wajib dipilih untuk role tersebut.';
    if (!$rules['universitas'] && $univId) return 'Role tersebut tidak menggunakan scope Universitas.';
    if ($rules['fakultas'] && !$facultyId) return 'Fakultas wajib dipilih untuk role tersebut.';
    if (!$rules['fakultas'] && $facultyId) return 'Role tersebut tidak menggunakan scope Fakultas.';
    if ($rules['prodi'] && !$studyId) return 'Program Studi wajib dipilih untuk role tersebut.';
    if (!$rules['prodi'] && $studyId) return 'Role tersebut tidak menggunakan scope Program Studi.';

    if ($facultyId) {
        $stmt = mysqli_prepare($db, "SELECT id_universitas FROM fakultas WHERE id_fakultas = ? LIMIT 1");
        if (!$stmt) return 'Gagal memeriksa relasi Fakultas.';
        mysqli_stmt_bind_param($stmt, 'i', $facultyId);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);
        if (!$row) return 'Fakultas yang dipilih tidak ditemukan.';
        if ($univId && (int)$row['id_universitas'] !== $univId) {
            return 'Fakultas yang dipilih bukan bagian dari Universitas tersebut.';
        }
    }

    if ($studyId) {
        $stmt = mysqli_prepare($db, "SELECT id_fakultas FROM program_studi WHERE id_program_studi = ? LIMIT 1");
        if (!$stmt) return 'Gagal memeriksa relasi Program Studi.';
        mysqli_stmt_bind_param($stmt, 'i', $studyId);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);
        if (!$row) return 'Program Studi yang dipilih tidak ditemukan.';
        if ($facultyId && (int)$row['id_fakultas'] !== $facultyId) {
            return 'Program Studi yang dipilih bukan bagian dari Fakultas tersebut.';
        }
    }

    return '';
}

function pg_validate_fields(array $in, bool $isUpdate): array {
    $roleId = (int)($in['id_role'] ?? 0);
    $univId = (int)($in['id_universitas'] ?? 0) ?: null;
    $facultyId = (int)($in['id_fakultas'] ?? 0) ?: null;
    $studyId = (int)($in['id_program_studi'] ?? 0) ?: null;
    $username = trim((string)($in['username'] ?? ''));
    $password = (string)($in['password'] ?? '');
    $name = trim((string)($in['nama_lengkap'] ?? ''));
    $email = trim((string)($in['email'] ?? ''));
    $phone = trim((string)($in['no_hp'] ?? ''));
    $status = (string)($in['status_aktif'] ?? 'Aktif');

    if ($roleId <= 0) return [null, 'Role wajib dipilih.'];
    if ($username === '') return [null, 'Username wajib diisi.'];
    if (!preg_match('/^[A-Za-z0-9._-]+$/', $username)) {
        return [null, 'Username hanya boleh berisi huruf, angka, titik, underscore, dan tanda minus.'];
    }
    if (mb_strlen($username) > 100) return [null, 'Username maksimal 100 karakter.'];
    if (!$isUpdate && mb_strlen($password) < 8) return [null, 'Password minimal 8 karakter.'];
    if ($isUpdate && $password !== '' && mb_strlen($password) < 8) return [null, 'Password baru minimal 8 karakter.'];
    if ($name === '') return [null, 'Nama lengkap wajib diisi.'];
    if (mb_strlen($name) > 200) return [null, 'Nama lengkap maksimal 200 karakter.'];
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) return [null, 'Format email tidak valid.'];
    if (mb_strlen($email) > 150) return [null, 'Email maksimal 150 karakter.'];
    if (mb_strlen($phone) > 30) return [null, 'Nomor HP maksimal 30 karakter.'];
    if (!in_array($status, ['Aktif', 'Tidak Aktif'], true)) return [null, 'Status pengguna tidak valid.'];

    return [[
        'id_role' => $roleId,
        'id_universitas' => $univId,
        'id_fakultas' => $facultyId,
        'id_program_studi' => $studyId,
        'username' => $username,
        'password' => $password,
        'nama_lengkap' => $name,
        'email' => $email,
        'no_hp' => $phone,
        'status_aktif' => $status
    ], ''];
}

$input = pg_input();
$action = trim((string)($input['action'] ?? $_GET['action'] ?? 'list'));
$actorId = (int)($input['id_pengguna'] ?? 0);

/* Pembatasan localhost untuk tahap pengujian satu mesin. */
$remote = $_SERVER['REMOTE_ADDR'] ?? '';
if (!in_array($remote, ['127.0.0.1', '::1'], true)) {
    pg_json(false, 'Akses provider ditolak.', [], 403);
}
if (!pg_admin_valid($koneksi, $actorId)) {
    pg_json(false, 'Akses ditolak. Pengguna ADMIN aktif diperlukan.', [], 403);
}

if ($action === 'options') {
    $roles = pg_fetch_all($koneksi, "SELECT id_role, kode_role, nama_role FROM roles ORDER BY id_role ASC");
    $universitas = pg_fetch_all($koneksi, "SELECT id_universitas, kode_universitas, nama_universitas FROM universitas ORDER BY nama_universitas ASC");
    $fakultas = pg_fetch_all($koneksi, "SELECT id_fakultas, id_universitas, kode_fakultas, nama_fakultas FROM fakultas ORDER BY nama_fakultas ASC");
    $prodi = pg_fetch_all($koneksi, "SELECT id_program_studi, id_fakultas, kode_program_studi, nama_program_studi FROM program_studi ORDER BY nama_program_studi ASC");
    pg_json(true, 'Data pilihan formulir berhasil diambil.', [
        'roles' => $roles,
        'universitas' => $universitas,
        'fakultas' => $fakultas,
        'program_studi' => $prodi
    ]);
}

if ($action === 'list') {
    $sql = "SELECT
                p.id_pengguna, p.username, p.nama_lengkap, p.email, p.no_hp,
                p.status_aktif, p.last_login, r.kode_role, r.nama_role,
                u.nama_universitas, f.nama_fakultas, ps.nama_program_studi
            FROM pengguna p
            INNER JOIN roles r ON r.id_role = p.id_role
            LEFT JOIN universitas u ON u.id_universitas = p.id_universitas
            LEFT JOIN fakultas f ON f.id_fakultas = p.id_fakultas
            LEFT JOIN program_studi ps ON ps.id_program_studi = p.id_program_studi
            ORDER BY p.nama_lengkap ASC";
    $rows = pg_fetch_all($koneksi, $sql);
    pg_json(true, 'Data pengguna berhasil diambil.', ['pengguna' => $rows]);
}

if ($action === 'detail') {
    $id = (int)($input['target_id_pengguna'] ?? 0);
    if ($id <= 0) pg_json(false, 'ID pengguna tidak valid.', [], 422);

    $stmt = mysqli_prepare($koneksi,
        "SELECT id_pengguna, id_role, id_universitas, id_fakultas,
                id_program_studi, username, nama_lengkap, email, no_hp, status_aktif
         FROM pengguna WHERE id_pengguna = ? LIMIT 1");
    if (!$stmt) pg_json(false, 'Gagal menyiapkan data pengguna.', [], 500);
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    if (!$row) pg_json(false, 'Data pengguna tidak ditemukan.', [], 404);
    pg_json(true, 'Detail pengguna berhasil diambil.', ['pengguna' => $row]);
}

if ($action === 'create' || $action === 'update') {
    $isUpdate = $action === 'update';
    $targetId = (int)($input['target_id_pengguna'] ?? 0);
    if ($isUpdate && $targetId <= 0) pg_json(false, 'ID pengguna tidak valid.', [], 422);

    [$data, $validationError] = pg_validate_fields($input, $isUpdate);
    if ($validationError !== '') pg_json(false, $validationError, [], 422);

    $stmt = mysqli_prepare($koneksi, "SELECT kode_role FROM roles WHERE id_role = ? LIMIT 1");
    if (!$stmt) pg_json(false, 'Gagal memeriksa Role.', [], 500);
    mysqli_stmt_bind_param($stmt, 'i', $data['id_role']);
    mysqli_stmt_execute($stmt);
    $roleRow = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    if (!$roleRow) pg_json(false, 'Role tidak ditemukan.', [], 422);

    $scopeError = pg_validate_scope(
        $koneksi,
        $roleRow['kode_role'],
        $data['id_universitas'],
        $data['id_fakultas'],
        $data['id_program_studi']
    );
    if ($scopeError !== '') pg_json(false, $scopeError, [], 422);

    if ($isUpdate) {
        $stmt = mysqli_prepare($koneksi,
            "SELECT id_pengguna FROM pengguna WHERE username = ? AND id_pengguna <> ? LIMIT 1");
        if (!$stmt) pg_json(false, 'Gagal memeriksa username.', [], 500);
        mysqli_stmt_bind_param($stmt, 'si', $data['username'], $targetId);
    } else {
        $stmt = mysqli_prepare($koneksi,
            "SELECT id_pengguna FROM pengguna WHERE username = ? LIMIT 1");
        if (!$stmt) pg_json(false, 'Gagal memeriksa username.', [], 500);
        mysqli_stmt_bind_param($stmt, 's', $data['username']);
    }
    mysqli_stmt_execute($stmt);
    $exists = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    if ($exists) pg_json(false, 'Username sudah digunakan oleh pengguna lain.', [], 409);

    if ($isUpdate) {
        // Pastikan record target memang ada sebelum update.
        $stmt = mysqli_prepare($koneksi, "SELECT id_pengguna FROM pengguna WHERE id_pengguna = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 'i', $targetId);
        mysqli_stmt_execute($stmt);
        $found = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);
        if (!$found) pg_json(false, 'Data pengguna tidak ditemukan.', [], 404);

        if ($data['password'] !== '') {
            $hash = password_hash($data['password'], PASSWORD_DEFAULT);
            $sql = "UPDATE pengguna SET
                        id_role = ?, id_universitas = ?, id_fakultas = ?, id_program_studi = ?,
                        username = ?, password_hash = ?, nama_lengkap = ?,
                        email = NULLIF(?, ''), no_hp = NULLIF(?, ''), status_aktif = ?
                    WHERE id_pengguna = ?";
            $stmt = mysqli_prepare($koneksi, $sql);
            if (!$stmt) pg_json(false, 'Gagal menyiapkan perubahan pengguna.', [], 500);
            mysqli_stmt_bind_param($stmt, 'iiiissssssi',
                $data['id_role'], $data['id_universitas'], $data['id_fakultas'],
                $data['id_program_studi'], $data['username'], $hash,
                $data['nama_lengkap'], $data['email'], $data['no_hp'],
                $data['status_aktif'], $targetId
            );
        } else {
            $sql = "UPDATE pengguna SET
                        id_role = ?, id_universitas = ?, id_fakultas = ?, id_program_studi = ?,
                        username = ?, nama_lengkap = ?, email = NULLIF(?, ''),
                        no_hp = NULLIF(?, ''), status_aktif = ?
                    WHERE id_pengguna = ?";
            $stmt = mysqli_prepare($koneksi, $sql);
            if (!$stmt) pg_json(false, 'Gagal menyiapkan perubahan pengguna.', [], 500);
            mysqli_stmt_bind_param($stmt, 'iiiisssssi',
                $data['id_role'], $data['id_universitas'], $data['id_fakultas'],
                $data['id_program_studi'], $data['username'], $data['nama_lengkap'],
                $data['email'], $data['no_hp'], $data['status_aktif'], $targetId
            );
        }

        if (!mysqli_stmt_execute($stmt)) {
            $error = mysqli_stmt_error($stmt);
            mysqli_stmt_close($stmt);
            pg_json(false, 'Gagal memperbarui pengguna: ' . $error, [], 500);
        }
        mysqli_stmt_close($stmt);
        pg_json(true, 'Pengguna berhasil diperbarui.', ['id_pengguna' => $targetId]);
    }

    $hash = password_hash($data['password'], PASSWORD_DEFAULT);
    $sql = "INSERT INTO pengguna
                (id_role, id_universitas, id_fakultas, id_program_studi,
                 username, password_hash, nama_lengkap, email, no_hp, status_aktif)
            VALUES (?, ?, ?, ?, ?, ?, ?, NULLIF(?, ''), NULLIF(?, ''), ?)";
    $stmt = mysqli_prepare($koneksi, $sql);
    if (!$stmt) pg_json(false, 'Gagal menyiapkan penyimpanan pengguna.', [], 500);
    mysqli_stmt_bind_param($stmt, 'iiiissssss',
        $data['id_role'], $data['id_universitas'], $data['id_fakultas'],
        $data['id_program_studi'], $data['username'], $hash,
        $data['nama_lengkap'], $data['email'], $data['no_hp'], $data['status_aktif']
    );
    if (!mysqli_stmt_execute($stmt)) {
        $error = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        pg_json(false, 'Gagal menambahkan pengguna: ' . $error, [], 500);
    }
    $newId = mysqli_insert_id($koneksi);
    mysqli_stmt_close($stmt);
    pg_json(true, 'Pengguna berhasil ditambahkan.', ['id_pengguna' => $newId]);
}

if ($action === 'delete') {
    $targetId = (int)($input['target_id_pengguna'] ?? 0);
    if ($targetId <= 0) pg_json(false, 'ID pengguna tidak valid.', [], 422);
    if ($targetId === $actorId) pg_json(false, 'Akun yang sedang digunakan tidak dapat dihapus.', [], 409);

    $stmt = mysqli_prepare($koneksi, "SELECT username, nama_lengkap FROM pengguna WHERE id_pengguna = ? LIMIT 1");
    if (!$stmt) pg_json(false, 'Gagal memeriksa pengguna.', [], 500);
    mysqli_stmt_bind_param($stmt, 'i', $targetId);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    if (!$row) pg_json(false, 'Data pengguna tidak ditemukan.', [], 404);

    $stmt = mysqli_prepare($koneksi, "DELETE FROM pengguna WHERE id_pengguna = ?");
    if (!$stmt) pg_json(false, 'Gagal menyiapkan proses hapus.', [], 500);
    mysqli_stmt_bind_param($stmt, 'i', $targetId);
    if (!mysqli_stmt_execute($stmt)) {
        $error = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        pg_json(false, 'Gagal menghapus pengguna: ' . $error, [], 409);
    }
    mysqli_stmt_close($stmt);
    pg_json(true, 'Pengguna "' . $row['nama_lengkap'] . '" berhasil dihapus.', ['id_pengguna' => $targetId]);
}

pg_json(false, 'Action API tidak dikenal.', [], 400);
