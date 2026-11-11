<?php
/**
 * SOA Provider - API Program Studi
 * Letakkan di folder htdocs/soa/apiprogramstudi.php
 */
require_once __DIR__ . '/koneksi.php';

header('Content-Type: application/json; charset=utf-8');

function ps_json($success, $message = '', $data = [], $httpCode = 200) {
    http_response_code($httpCode);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function ps_input() {
    $raw = file_get_contents('php://input');
    $json = json_decode($raw, true);
    if (is_array($json)) return $json;
    return $_POST ?: [];
}

function ps_admin_valid($koneksi, $idPengguna) {
    if ($idPengguna <= 0) return false;
    $stmt = mysqli_prepare(
        $koneksi,
        "SELECT p.id_pengguna
         FROM pengguna p
         INNER JOIN roles r ON r.id_role = p.id_role
         WHERE p.id_pengguna = ?
           AND p.status_aktif = 'Aktif'
           AND r.kode_role = 'ADMIN'
         LIMIT 1"
    );
    if (!$stmt) return false;
    mysqli_stmt_bind_param($stmt, 'i', $idPengguna);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $valid = mysqli_num_rows($result) === 1;
    mysqli_stmt_close($stmt);
    return $valid;
}

function ps_audit($koneksi, $idPengguna, $idRecord, $aksi, $lama = null, $baru = null) {
    $stmt = mysqli_prepare(
        $koneksi,
        "INSERT INTO audit_log
            (id_pengguna, tabel_nama, record_id, aksi, data_lama, data_baru, ip_address, user_agent)
         VALUES (?, 'program_studi', ?, ?, ?, ?, ?, ?)"
    );
    if (!$stmt) return;
    $record = (string)$idRecord;
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    mysqli_stmt_bind_param($stmt, 'issssss', $idPengguna, $record, $aksi, $lama, $baru, $ip, $agent);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

function ps_data_lama($row) {
    return json_encode([
        'id_fakultas' => (int)$row['id_fakultas'],
        'kode_program_studi' => $row['kode_program_studi'],
        'nama_program_studi' => $row['nama_program_studi'],
        'jenjang' => $row['jenjang'],
        'status_aktif' => $row['status_aktif']
    ], JSON_UNESCAPED_UNICODE);
}

$input = ps_input();
$action = trim((string)($input['action'] ?? $_GET['action'] ?? 'list'));
$idPengguna = (int)($input['id_pengguna'] ?? 0);

/*
 * Saat masih satu mesin, batasi akses provider ke localhost.
 * Untuk deployment lintas server, ganti mekanisme ini dengan autentikasi
 * antar-layanan/token dan HTTPS; jangan sekadar menghapus pembatasan.
 */
$remote = $_SERVER['REMOTE_ADDR'] ?? '';
if (!in_array($remote, ['127.0.0.1', '::1'], true)) {
    ps_json(false, 'Akses provider ditolak.', [], 403);
}

if (!ps_admin_valid($koneksi, $idPengguna)) {
    ps_json(false, 'Akses ditolak. Pengguna ADMIN aktif diperlukan.', [], 403);
}

if ($action === 'list') {
    $sql = "SELECT
                ps.id_program_studi, ps.kode_program_studi,
                ps.nama_program_studi, ps.jenjang, ps.status_aktif,
                f.id_fakultas, f.kode_fakultas, f.nama_fakultas,
                u.id_universitas, u.kode_universitas, u.nama_universitas,
                (SELECT COUNT(*) FROM mahasiswa m
                 WHERE m.id_program_studi = ps.id_program_studi) AS jumlah_mahasiswa
            FROM program_studi ps
            INNER JOIN fakultas f ON f.id_fakultas = ps.id_fakultas
            INNER JOIN universitas u ON u.id_universitas = f.id_universitas
            ORDER BY u.nama_universitas, f.nama_fakultas, ps.nama_program_studi";
    $result = mysqli_query($koneksi, $sql);
    if (!$result) ps_json(false, 'Gagal mengambil data program studi.', [], 500);
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) $rows[] = $row;
    ps_json(true, 'Data program studi berhasil diambil.', ['program_studi' => $rows]);
}

if ($action === 'detail') {
    $id = (int)($input['id_program_studi'] ?? 0);
    if ($id <= 0) ps_json(false, 'ID program studi tidak valid.', [], 422);
    $stmt = mysqli_prepare($koneksi,
        "SELECT id_program_studi, id_fakultas, kode_program_studi,
                nama_program_studi, jenjang, status_aktif
         FROM program_studi WHERE id_program_studi = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    if (!$row) ps_json(false, 'Data program studi tidak ditemukan.', [], 404);
    ps_json(true, 'Detail program studi berhasil diambil.', ['program_studi' => $row]);
}

if ($action === 'create' || $action === 'update') {
    $id = (int)($input['id_program_studi'] ?? 0);
    $idFakultas = (int)($input['id_fakultas'] ?? 0);
    $kode = trim((string)($input['kode_program_studi'] ?? ''));
    $nama = trim((string)($input['nama_program_studi'] ?? ''));
    $jenjang = trim((string)($input['jenjang'] ?? ''));
    $status = (string)($input['status_aktif'] ?? 'Aktif');

    if ($idFakultas <= 0 || $kode === '' || $nama === '' || $jenjang === '') {
        ps_json(false, 'Fakultas, kode, nama program studi, dan jenjang wajib diisi.', [], 422);
    }
    if (mb_strlen($kode) > 20 || mb_strlen($nama) > 200 || mb_strlen($jenjang) > 20) {
        ps_json(false, 'Panjang salah satu isian melebihi batas yang ditentukan.', [], 422);
    }
    if (!in_array($status, ['Aktif', 'Tidak Aktif'], true)) {
        ps_json(false, 'Status program studi tidak valid.', [], 422);
    }

    $cekFakultas = mysqli_prepare($koneksi, "SELECT id_fakultas FROM fakultas WHERE id_fakultas = ? LIMIT 1");
    mysqli_stmt_bind_param($cekFakultas, 'i', $idFakultas);
    mysqli_stmt_execute($cekFakultas);
    $validFakultas = mysqli_num_rows(mysqli_stmt_get_result($cekFakultas)) > 0;
    mysqli_stmt_close($cekFakultas);
    if (!$validFakultas) ps_json(false, 'Fakultas yang dipilih tidak ditemukan.', [], 422);

    if ($action === 'create') {
        $cek = mysqli_prepare($koneksi,
            "SELECT id_program_studi FROM program_studi
             WHERE id_fakultas = ? AND kode_program_studi = ? LIMIT 1");
        mysqli_stmt_bind_param($cek, 'is', $idFakultas, $kode);
    } else {
        if ($id <= 0) ps_json(false, 'ID program studi tidak valid.', [], 422);
        $cek = mysqli_prepare($koneksi,
            "SELECT id_program_studi FROM program_studi
             WHERE id_fakultas = ? AND kode_program_studi = ?
               AND id_program_studi <> ? LIMIT 1");
        mysqli_stmt_bind_param($cek, 'isi', $idFakultas, $kode, $id);
    }
    mysqli_stmt_execute($cek);
    $duplikat = mysqli_num_rows(mysqli_stmt_get_result($cek)) > 0;
    mysqli_stmt_close($cek);
    if ($duplikat) ps_json(false, 'Kode program studi sudah digunakan pada fakultas tersebut.', [], 409);

    $baru = json_encode([
        'id_fakultas' => $idFakultas,
        'kode_program_studi' => $kode,
        'nama_program_studi' => $nama,
        'jenjang' => $jenjang,
        'status_aktif' => $status
    ], JSON_UNESCAPED_UNICODE);

    if ($action === 'create') {
        $stmt = mysqli_prepare($koneksi,
            "INSERT INTO program_studi
                (id_fakultas, kode_program_studi, nama_program_studi, jenjang, status_aktif)
             VALUES (?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'issss', $idFakultas, $kode, $nama, $jenjang, $status);
        if (!mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            ps_json(false, 'Data program studi gagal disimpan.', [], 500);
        }
        $newId = mysqli_insert_id($koneksi);
        mysqli_stmt_close($stmt);
        ps_audit($koneksi, $idPengguna, $newId, 'INSERT', null, $baru);
        ps_json(true, 'Program Studi berhasil ditambahkan.', ['id_program_studi' => $newId]);
    }

    $stmt = mysqli_prepare($koneksi,
        "SELECT id_fakultas, kode_program_studi, nama_program_studi, jenjang, status_aktif
         FROM program_studi WHERE id_program_studi = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $oldRow = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    if (!$oldRow) ps_json(false, 'Data program studi tidak ditemukan.', [], 404);

    $lama = ps_data_lama($oldRow);
    $stmt = mysqli_prepare($koneksi,
        "UPDATE program_studi
         SET id_fakultas = ?, kode_program_studi = ?, nama_program_studi = ?,
             jenjang = ?, status_aktif = ?
         WHERE id_program_studi = ?");
    mysqli_stmt_bind_param($stmt, 'issssi', $idFakultas, $kode, $nama, $jenjang, $status, $id);
    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        ps_json(false, 'Data program studi gagal diperbarui.', [], 500);
    }
    mysqli_stmt_close($stmt);
    ps_audit($koneksi, $idPengguna, $id, 'UPDATE', $lama, $baru);
    ps_json(true, 'Program Studi berhasil diperbarui.', ['id_program_studi' => $id]);
}

if ($action === 'delete') {
    $id = (int)($input['id_program_studi'] ?? 0);
    if ($id <= 0) ps_json(false, 'ID program studi tidak valid.', [], 422);

    $stmt = mysqli_prepare($koneksi,
        "SELECT id_fakultas, kode_program_studi, nama_program_studi, jenjang, status_aktif
         FROM program_studi WHERE id_program_studi = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $oldRow = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    if (!$oldRow) ps_json(false, 'Data program studi tidak ditemukan.', [], 404);

    foreach (['mahasiswa', 'pengguna'] as $table) {
        $stmt = mysqli_prepare($koneksi, "SELECT COUNT(*) AS jumlah FROM `$table` WHERE id_program_studi = ?");
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        $jumlah = (int)(mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['jumlah'] ?? 0);
        mysqli_stmt_close($stmt);
        if ($jumlah > 0) {
            $pesan = $table === 'mahasiswa'
                ? "Program Studi tidak dapat dihapus karena masih memiliki {$jumlah} data mahasiswa."
                : "Program Studi tidak dapat dihapus karena masih digunakan oleh {$jumlah} pengguna.";
            ps_json(false, $pesan, [], 409);
        }
    }

    $lama = ps_data_lama($oldRow);
    $stmt = mysqli_prepare($koneksi, "DELETE FROM program_studi WHERE id_program_studi = ?");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        ps_json(false, 'Program Studi gagal dihapus.', [], 500);
    }
    mysqli_stmt_close($stmt);
    ps_audit($koneksi, $idPengguna, $id, 'DELETE', $lama, null);
    ps_json(true, 'Program Studi berhasil dihapus.', ['id_program_studi' => $id]);
}

ps_json(false, 'Action API tidak dikenal.', [], 400);
