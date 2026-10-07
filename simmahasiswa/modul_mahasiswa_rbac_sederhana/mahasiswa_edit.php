<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/koneksi.php';

wajib_role(['ADMIN', 'OPERATOR_PRODI', 'DEKANAT', 'REKTORAT']);

$role = $_SESSION['kode_role'] ?? '';
$id_universitas = (int)($_SESSION['id_universitas'] ?? 0);
$id_fakultas = (int)($_SESSION['id_fakultas'] ?? 0);
$id_prodi_scope = (int)($_SESSION['id_program_studi'] ?? 0);
$id_mahasiswa = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$mode_view = ($_GET['mode'] ?? '') === 'view' || in_array($role, ['DEKANAT','REKTORAT'], true);

if (!$id_mahasiswa) {
    $_SESSION['error'] = 'ID mahasiswa tidak valid.';
    header('Location: mahasiswa.php'); exit;
}

function ambil_mahasiswa(mysqli $koneksi, int $id): ?array {
    $stmt = mysqli_prepare($koneksi,
        "SELECT m.*, ps.id_fakultas, ps.kode_program_studi, ps.nama_program_studi, ps.jenjang,
                f.kode_fakultas, f.nama_fakultas, f.id_universitas,
                u.kode_universitas, u.nama_universitas,
                p.username
         FROM mahasiswa m
         INNER JOIN program_studi ps ON ps.id_program_studi = m.id_program_studi
         INNER JOIN fakultas f ON f.id_fakultas = ps.id_fakultas
         INNER JOIN universitas u ON u.id_universitas = f.id_universitas
         LEFT JOIN pengguna p ON p.id_pengguna = m.id_pengguna
         WHERE m.id_mahasiswa = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)) ?: null;
    mysqli_stmt_close($stmt);
    return $data;
}

$data = ambil_mahasiswa($koneksi, $id_mahasiswa);
if (!$data) {
    $_SESSION['error'] = 'Data mahasiswa tidak ditemukan.';
    header('Location: mahasiswa.php'); exit;
}

$allowed_scope = true;
if ($role === 'OPERATOR_PRODI') $allowed_scope = (int)$data['id_program_studi'] === $id_prodi_scope;
elseif ($role === 'DEKANAT') $allowed_scope = (int)$data['id_fakultas'] === $id_fakultas;
elseif ($role === 'REKTORAT') $allowed_scope = (int)$data['id_universitas'] === $id_universitas;
elseif ($role === 'ADMIN') $allowed_scope = true;

if (!$allowed_scope) {
    http_response_code(403);
    die('403 - Data mahasiswa berada di luar kewenangan Anda.');
}

if ($mode_view && !in_array($role, ['ADMIN','OPERATOR_PRODI','DEKANAT','REKTORAT'], true)) {
    http_response_code(403); die('403 - Anda tidak memiliki hak akses.');
}

$error = '';
$nama_mahasiswa = $data['nama_mahasiswa'];
$jenis_kelamin = $data['jenis_kelamin'];
$tempat_lahir = $data['tempat_lahir'] ?? '';
$tanggal_lahir = $data['tanggal_lahir'] ?? '';
$tanggal_masuk = $data['tanggal_masuk'];
$alamat = $data['alamat'] ?? '';
$status_mahasiswa = $data['status_mahasiswa'];
$id_program_studi = (int)$data['id_program_studi'];
$id_pengguna = (int)($data['id_pengguna'] ?? 0);

$can_edit = !$mode_view && in_array($role, ['ADMIN','OPERATOR_PRODI'], true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    if (!$can_edit) { http_response_code(403); die('403 - Anda tidak memiliki hak chỉnh sửa dữ liệu này.'); }

    $nama_mahasiswa = trim($_POST['nama_mahasiswa'] ?? '');
    $jenis_kelamin = $_POST['jenis_kelamin'] ?? '';
    $tempat_lahir = trim($_POST['tempat_lahir'] ?? '');
    $tanggal_lahir = trim($_POST['tanggal_lahir'] ?? '');
    $tanggal_masuk = trim($_POST['tanggal_masuk'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');
    $status_mahasiswa = $_POST['status_mahasiswa'] ?? '';
    $id_program_studi_post = (int)($_POST['id_program_studi'] ?? 0);
    $id_pengguna = (int)($_POST['id_pengguna'] ?? 0);

    if ($nama_mahasiswa === '') $error = 'Nama mahasiswa wajib diisi.';
    elseif (mb_strlen($nama_mahasiswa) > 200) $error = 'Nama mahasiswa maksimal 200 karakter.';
    elseif (!in_array($jenis_kelamin, ['L','P'], true)) $error = 'Jenis kelamin tidak valid.';
    elseif (!$id_program_studi_post) $error = 'Program studi wajib dipilih.';
    elseif ($tanggal_masuk === '') $error = 'Tanggal masuk wajib diisi.';
    elseif (!in_array($status_mahasiswa, ['Aktif','Cuti','Lulus','Mengundurkan Diri','Drop Out','Tidak Aktif'], true)) $error = 'Status mahasiswa tidak valid.';

    if ($error === '' && $role === 'OPERATOR_PRODI' && $id_program_studi_post !== $id_prodi_scope) $error = 'Operator Prodi tidak boleh memindahkan mahasiswa ke Program Studi lain.';

    if ($error === '') {
        $stmt = mysqli_prepare($koneksi, "SELECT id_program_studi FROM program_studi WHERE id_program_studi = ? AND status_aktif = 'Aktif' LIMIT 1");
        mysqli_stmt_bind_param($stmt, 'i', $id_program_studi_post);
        mysqli_stmt_execute($stmt);
        $cek = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);
        if (!$cek) $error = 'Program studi tidak ditemukan atau tidak aktif.';
    }

    if ($error === '') {
        $stmt = mysqli_prepare($koneksi,
            "UPDATE mahasiswa SET
                id_pengguna = NULLIF(?,0),
                id_program_studi = ?,
                nama_mahasiswa = ?,
                jenis_kelamin = ?,
                tempat_lahir = NULLIF(?,''),
                tanggal_lahir = NULLIF(?,''),
                tanggal_masuk = ?,
                alamat = NULLIF(?,''),
                status_mahasiswa = ?
             WHERE id_mahasiswa = ?");
        if (!$stmt) {
            $error = 'Gagal menyiapkan proses pembaruan.';
        } else {
            mysqli_stmt_bind_param($stmt, 'iisssssssi',
                $id_pengguna, $id_program_studi_post, $nama_mahasiswa, $jenis_kelamin,
                $tempat_lahir, $tanggal_lahir, $tanggal_masuk, $alamat,
                $status_mahasiswa, $id_mahasiswa);
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                $_SESSION['success'] = 'Data mahasiswa berhasil diperbarui.';
                header('Location: mahasiswa.php'); exit;
            }
            $error = 'Gagal memperbarui mahasiswa: ' . mysqli_stmt_error($stmt);
            mysqli_stmt_close($stmt);
        }
    }
}

$prodi = mysqli_query($koneksi,
    "SELECT ps.id_program_studi, ps.kode_program_studi, ps.nama_program_studi, ps.jenjang,
            f.nama_fakultas
     FROM program_studi ps INNER JOIN fakultas f ON f.id_fakultas = ps.id_fakultas
     WHERE ps.status_aktif = 'Aktif' ORDER BY f.nama_fakultas, ps.nama_program_studi");

$pengguna = mysqli_query($koneksi,
    "SELECT p.id_pengguna, p.username, p.nama_lengkap
     FROM pengguna p INNER JOIN roles r ON r.id_role = p.id_role
     LEFT JOIN mahasiswa m ON m.id_pengguna = p.id_pengguna
     WHERE r.kode_role = 'MAHASISWA' AND p.status_aktif = 'Aktif'
       AND (m.id_mahasiswa IS NULL OR m.id_mahasiswa = " . (int)$id_mahasiswa . ")
     ORDER BY p.nama_lengkap");
?>
<!DOCTYPE html>
<html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title><?= $mode_view ? 'Detail' : 'Edit' ?> Mahasiswa - SIM Mahasiswa</title><link rel="stylesheet" href="style.css"><style>
.master-form-card{width:100%;max-width:900px;background:#fff;border:1px solid #e5e7eb;border-radius:14px;overflow:hidden;box-shadow:0 4px 18px rgba(0,0,0,.05)}.master-form-header{padding:22px 25px;background:#fbfdfb;border-bottom:1px solid #e5e7eb}.master-form-header h2{margin:0 0 4px;font-size:20px}.master-form-header p{margin:0;color:#6b7280;font-size:13px}.master-form{padding:25px}.grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px}.full{grid-column:1/-1}.group label{display:block;margin-bottom:7px;font-size:14px;font-weight:700}.control{width:100%;box-sizing:border-box;padding:11px 13px;border:1px solid #d1d5db;border-radius:8px;font:inherit;background:#fff}.actions{display:flex;gap:10px;margin-top:22px}@media(max-width:760px){.grid{grid-template-columns:1fr}.full{grid-column:auto}}
</style></head>
<body class="app-body"><?php include __DIR__.'/topbar.php'; ?><?php include __DIR__.'/sideleftbar.php'; ?><main class="main-content">
<div class="page-heading"><span class="eyebrow">DATA AKADEMIK</span><h1><?= $mode_view ? 'Detail Mahasiswa' : 'Edit Mahasiswa' ?></h1><p><?= $mode_view ? 'Informasi lengkap mahasiswa.' : 'Perbarui data mahasiswa.' ?></p></div>
<?php if($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
<div class="master-form-card"><div class="master-form-header"><h2><?= htmlspecialchars($data['npm'], ENT_QUOTES, 'UTF-8') ?> — <?= htmlspecialchars($nama_mahasiswa, ENT_QUOTES, 'UTF-8') ?></h2><p><?= htmlspecialchars($data['kode_program_studi'].' - '.$data['nama_program_studi'], ENT_QUOTES, 'UTF-8') ?></p></div><form method="post" class="master-form">
<?php if($can_edit): ?><?= csrf_field() ?><?php endif; ?>
<div class="grid">
<div class="group"><label>NPM</label><input class="control" value="<?= htmlspecialchars($data['npm'], ENT_QUOTES, 'UTF-8') ?>" readonly></div>
<div class="group"><label>Nama Mahasiswa *</label><input class="control" name="nama_mahasiswa" value="<?= htmlspecialchars($nama_mahasiswa, ENT_QUOTES, 'UTF-8') ?>" <?= $can_edit?'':'readonly' ?>></div>
<div class="group"><label>Program Studi *</label><select class="control" name="id_program_studi" <?= $can_edit?'':'disabled' ?>><?php while($p=mysqli_fetch_assoc($prodi)): ?><option value="<?= (int)$p['id_program_studi'] ?>" <?= $id_program_studi===(int)$p['id_program_studi']?'selected':'' ?>><?= htmlspecialchars($p['kode_program_studi'].' - '.$p['nama_program_studi'].' ('.$p['jenjang'].')', ENT_QUOTES, 'UTF-8') ?></option><?php endwhile; ?></select></div>
<div class="group"><label>Akun Pengguna</label><select class="control" name="id_pengguna" <?= $can_edit?'':'disabled' ?>><option value="0">-- Tidak dihubungkan --</option><?php while($p=mysqli_fetch_assoc($pengguna)): ?><option value="<?= (int)$p['id_pengguna'] ?>" <?= $id_pengguna===(int)$p['id_pengguna']?'selected':'' ?>><?= htmlspecialchars($p['username'].' - '.$p['nama_lengkap'], ENT_QUOTES, 'UTF-8') ?></option><?php endwhile; ?></select></div>
<div class="group"><label>Jenis Kelamin *</label><select class="control" name="jenis_kelamin" <?= $can_edit?'':'disabled' ?>><option value="L" <?= $jenis_kelamin==='L'?'selected':'' ?>>Laki-laki</option><option value="P" <?= $jenis_kelamin==='P'?'selected':'' ?>>Perempuan</option></select></div>
<div class="group"><label>Tempat Lahir</label><input class="control" name="tempat_lahir" value="<?= htmlspecialchars($tempat_lahir, ENT_QUOTES, 'UTF-8') ?>" <?= $can_edit?'':'readonly' ?>></div>
<div class="group"><label>Tanggal Lahir</label><input class="control" type="date" name="tanggal_lahir" value="<?= htmlspecialchars($tanggal_lahir, ENT_QUOTES, 'UTF-8') ?>" <?= $can_edit?'':'readonly' ?>></div>
<div class="group"><label>Tanggal Masuk *</label><input class="control" type="date" name="tanggal_masuk" value="<?= htmlspecialchars($tanggal_masuk, ENT_QUOTES, 'UTF-8') ?>" <?= $can_edit?'':'readonly' ?>></div>
<div class="group"><label>Status Mahasiswa *</label><select class="control" name="status_mahasiswa" <?= $can_edit?'':'disabled' ?>><?php foreach(['Aktif','Cuti','Lulus','Mengundurkan Diri','Drop Out','Tidak Aktif'] as $s): ?><option value="<?= htmlspecialchars($s, ENT_QUOTES, 'UTF-8') ?>" <?= $status_mahasiswa===$s?'selected':'' ?>><?= htmlspecialchars($s, ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></div>
<div class="group full"><label>Alamat</label><textarea class="control" name="alamat" <?= $can_edit?'':'readonly' ?>><?= htmlspecialchars($alamat, ENT_QUOTES, 'UTF-8') ?></textarea></div>
</div><div class="actions"><a href="mahasiswa.php" class="btn btn-sm btn-outline">Kembali</a><?php if($can_edit): ?><button type="submit" class="btn btn-primary">Simpan Perubahan</button><?php endif; ?></div>
</form></div></main></body></html>
