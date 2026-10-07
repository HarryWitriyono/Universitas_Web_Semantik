<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/koneksi.php';

wajib_role(['ADMIN', 'OPERATOR_PRODI']);

$role = $_SESSION['kode_role'] ?? '';
$id_prodi_scope = (int)($_SESSION['id_program_studi'] ?? 0);
$error = '';

$npm = '';
$nama_mahasiswa = '';
$jenis_kelamin = 'L';
$tempat_lahir = '';
$tanggal_lahir = '';
$tanggal_masuk = date('Y-m-d');
$alamat = '';
$status_mahasiswa = 'Aktif';
$id_program_studi = $role === 'OPERATOR_PRODI' ? $id_prodi_scope : '';
$id_pengguna = '';

$prodi = mysqli_query($koneksi,
    "SELECT ps.id_program_studi, ps.kode_program_studi, ps.nama_program_studi, ps.jenjang,
            f.nama_fakultas
     FROM program_studi ps
     INNER JOIN fakultas f ON f.id_fakultas = ps.id_fakultas
     WHERE ps.status_aktif = 'Aktif'
     ORDER BY f.nama_fakultas, ps.nama_program_studi"
);

$pengguna = mysqli_query($koneksi,
    "SELECT p.id_pengguna, p.username, p.nama_lengkap
     FROM pengguna p
     INNER JOIN roles r ON r.id_role = p.id_role
     LEFT JOIN mahasiswa m ON m.id_pengguna = p.id_pengguna
     WHERE r.kode_role = 'MAHASISWA'
       AND p.status_aktif = 'Aktif'
       AND m.id_mahasiswa IS NULL
     ORDER BY p.nama_lengkap"
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $npm = trim($_POST['npm'] ?? '');
    $nama_mahasiswa = trim($_POST['nama_mahasiswa'] ?? '');
    $jenis_kelamin = $_POST['jenis_kelamin'] ?? '';
    $tempat_lahir = trim($_POST['tempat_lahir'] ?? '');
    $tanggal_lahir = trim($_POST['tanggal_lahir'] ?? '');
    $tanggal_masuk = trim($_POST['tanggal_masuk'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');
    $status_mahasiswa = $_POST['status_mahasiswa'] ?? 'Aktif';
    $id_program_studi = (int)($_POST['id_program_studi'] ?? 0);
    $id_pengguna = (int)($_POST['id_pengguna'] ?? 0);

    if ($npm === '') $error = 'NPM wajib diisi.';
    elseif (mb_strlen($npm) > 30) $error = 'NPM maksimal 30 karakter.';
    elseif ($nama_mahasiswa === '') $error = 'Nama mahasiswa wajib diisi.';
    elseif (mb_strlen($nama_mahasiswa) > 200) $error = 'Nama mahasiswa maksimal 200 karakter.';
    elseif (!in_array($jenis_kelamin, ['L','P'], true)) $error = 'Jenis kelamin tidak valid.';
    elseif (!$id_program_studi) $error = 'Program studi wajib dipilih.';
    elseif ($tanggal_masuk === '') $error = 'Tanggal masuk wajib diisi.';
    elseif (!in_array($status_mahasiswa, ['Aktif','Cuti','Lulus','Mengundurkan Diri','Drop Out','Tidak Aktif'], true)) $error = 'Status mahasiswa tidak valid.';

    if ($error === '' && $role === 'OPERATOR_PRODI' && $id_program_studi !== $id_prodi_scope) {
        $error = 'Operator Prodi hanya dapat menambahkan mahasiswa pada Program Studi yang menjadi kewenangannya.';
    }

    if ($error === '') {
        $stmt = mysqli_prepare($koneksi, "SELECT id_mahasiswa FROM mahasiswa WHERE npm = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 's', $npm);
        mysqli_stmt_execute($stmt);
        $ada = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);
        if ($ada) $error = 'NPM sudah digunakan.';
    }

    if ($error === '') {
        $stmt = mysqli_prepare($koneksi,
            "SELECT ps.id_program_studi
             FROM program_studi ps
             WHERE ps.id_program_studi = ? AND ps.status_aktif = 'Aktif' LIMIT 1");
        mysqli_stmt_bind_param($stmt, 'i', $id_program_studi);
        mysqli_stmt_execute($stmt);
        $ada = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);
        if (!$ada) $error = 'Program studi tidak ditemukan atau tidak aktif.';
    }

    if ($error === '' && $id_pengguna > 0) {
        $stmt = mysqli_prepare($koneksi,
            "SELECT p.id_pengguna
             FROM pengguna p
             INNER JOIN roles r ON r.id_role = p.id_role
             LEFT JOIN mahasiswa m ON m.id_pengguna = p.id_pengguna
             WHERE p.id_pengguna = ? AND r.kode_role = 'MAHASISWA'
               AND p.status_aktif = 'Aktif' AND m.id_mahasiswa IS NULL
             LIMIT 1");
        mysqli_stmt_bind_param($stmt, 'i', $id_pengguna);
        mysqli_stmt_execute($stmt);
        $ada = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);
        if (!$ada) $error = 'Akun pengguna tidak valid atau sudah terhubung dengan mahasiswa lain.';
    }

    if ($error === '') {
        $stmt = mysqli_prepare($koneksi,
            "INSERT INTO mahasiswa
             (id_pengguna, id_program_studi, npm, nama_mahasiswa, jenis_kelamin,
              tempat_lahir, tanggal_lahir, tanggal_masuk, alamat, status_mahasiswa)
             VALUES (NULLIF(?,0), ?, ?, ?, ?, NULLIF(?,''), NULLIF(?,''), ?, NULLIF(?,''), ?)");
        if (!$stmt) {
            $error = 'Gagal menyiapkan proses penyimpanan.';
        } else {
            mysqli_stmt_bind_param($stmt, 'iissssssss',
                $id_pengguna, $id_program_studi, $npm, $nama_mahasiswa, $jenis_kelamin,
                $tempat_lahir, $tanggal_lahir, $tanggal_masuk, $alamat, $status_mahasiswa);
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                $_SESSION['success'] = 'Data mahasiswa berhasil ditambahkan.';
                header('Location: mahasiswa.php');
                exit;
            }
            $error = 'Gagal menambahkan mahasiswa: ' . mysqli_stmt_error($stmt);
            mysqli_stmt_close($stmt);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tambah Mahasiswa - SIM Mahasiswa</title><link rel="stylesheet" href="style.css">
<style>
.master-form-card{width:100%;max-width:900px;background:#fff;border:1px solid #e5e7eb;border-radius:14px;overflow:hidden;box-shadow:0 4px 18px rgba(0,0,0,.05)}
.master-form-header{padding:22px 25px;background:#fbfdfb;border-bottom:1px solid #e5e7eb}.master-form-header h2{margin:0 0 4px;font-size:20px}.master-form-header p{margin:0;color:#6b7280;font-size:13px}.master-form{padding:25px}.grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px}.full{grid-column:1/-1}.group label{display:block;margin-bottom:7px;font-size:14px;font-weight:700}.control{width:100%;box-sizing:border-box;padding:11px 13px;border:1px solid #d1d5db;border-radius:8px;font:inherit;background:#fff}.control:focus{outline:none;border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,.1)}textarea.control{min-height:100px;resize:vertical}.actions{display:flex;gap:10px;margin-top:22px}
@media(max-width:760px){.grid{grid-template-columns:1fr}.full{grid-column:auto}}
</style></head>
<body class="app-body">
<?php include __DIR__ . '/topbar.php'; ?><?php include __DIR__ . '/sideleftbar.php'; ?>
<main class="main-content">
<div class="page-heading"><span class="eyebrow">DATA AKADEMIK</span><h1>Tambah Mahasiswa</h1><p>Masukkan data mahasiswa baru.</p></div>
<?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
<div class="master-form-card"><div class="master-form-header"><h2>Form Mahasiswa</h2><p>Field bertanda * wajib diisi.</p></div><form method="post" class="master-form">
<?= csrf_field() ?>
<div class="grid">
<div class="group"><label>NPM *</label><input class="control" type="text" name="npm" maxlength="30" required value="<?= htmlspecialchars($npm, ENT_QUOTES, 'UTF-8') ?>"></div>
<div class="group"><label>Nama Mahasiswa *</label><input class="control" type="text" name="nama_mahasiswa" maxlength="200" required value="<?= htmlspecialchars($nama_mahasiswa, ENT_QUOTES, 'UTF-8') ?>"></div>
<div class="group"><label>Program Studi *</label><select class="control" name="id_program_studi" required <?= $role === 'OPERATOR_PRODI' ? 'disabled' : '' ?>><option value="">-- Pilih Program Studi --</option><?php while($p=mysqli_fetch_assoc($prodi)): ?><option value="<?= (int)$p['id_program_studi'] ?>" <?= (int)$id_program_studi === (int)$p['id_program_studi'] ? 'selected' : '' ?>><?= htmlspecialchars($p['kode_program_studi'].' - '.$p['nama_program_studi'].' ('.$p['jenjang'].')', ENT_QUOTES, 'UTF-8') ?></option><?php endwhile; ?></select><?php if($role==='OPERATOR_PRODI'): ?><input type="hidden" name="id_program_studi" value="<?= $id_prodi_scope ?>"><?php endif; ?></div>
<div class="group"><label>Akun Pengguna</label><select class="control" name="id_pengguna"><option value="0">-- Tidak dihubungkan --</option><?php while($p=mysqli_fetch_assoc($pengguna)): ?><option value="<?= (int)$p['id_pengguna'] ?>" <?= (int)$id_pengguna === (int)$p['id_pengguna'] ? 'selected' : '' ?>><?= htmlspecialchars($p['username'].' - '.$p['nama_lengkap'], ENT_QUOTES, 'UTF-8') ?></option><?php endwhile; ?></select></div>
<div class="group"><label>Jenis Kelamin *</label><select class="control" name="jenis_kelamin" required><option value="L" <?= $jenis_kelamin==='L'?'selected':'' ?>>Laki-laki</option><option value="P" <?= $jenis_kelamin==='P'?'selected':'' ?>>Perempuan</option></select></div>
<div class="group"><label>Tempat Lahir</label><input class="control" type="text" name="tempat_lahir" maxlength="100" value="<?= htmlspecialchars($tempat_lahir, ENT_QUOTES, 'UTF-8') ?>"></div>
<div class="group"><label>Tanggal Lahir</label><input class="control" type="date" name="tanggal_lahir" value="<?= htmlspecialchars($tanggal_lahir, ENT_QUOTES, 'UTF-8') ?>"></div>
<div class="group"><label>Tanggal Masuk *</label><input class="control" type="date" name="tanggal_masuk" required value="<?= htmlspecialchars($tanggal_masuk, ENT_QUOTES, 'UTF-8') ?>"></div>
<div class="group"><label>Status Mahasiswa *</label><select class="control" name="status_mahasiswa" required><?php foreach(['Aktif','Cuti','Lulus','Mengundurkan Diri','Drop Out','Tidak Aktif'] as $s): ?><option value="<?= htmlspecialchars($s, ENT_QUOTES, 'UTF-8') ?>" <?= $status_mahasiswa===$s?'selected':'' ?>><?= htmlspecialchars($s, ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></div>
<div class="group full"><label>Alamat</label><textarea class="control" name="alamat"><?= htmlspecialchars($alamat, ENT_QUOTES, 'UTF-8') ?></textarea></div>
</div>
<div class="actions"><a href="mahasiswa.php" class="btn btn-sm btn-outline">Kembali</a><button type="submit" class="btn btn-primary">Simpan</button></div>
</form></div></main></body></html>
