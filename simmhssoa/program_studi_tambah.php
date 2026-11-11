<?php
require_once __DIR__ . '/program_studi_helper.php';
require_once __DIR__ . '/csrf.php';

$daftar_fakultas = ps_fakultas_list();
$kode_program_studi = '';
$nama_program_studi = '';
$jenjang = 'S1';
$status_aktif = 'Aktif';
$id_fakultas = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        csrf_verify();
        $id_fakultas = (int)($_POST['id_fakultas'] ?? 0);
        $kode_program_studi = trim($_POST['kode_program_studi'] ?? '');
        $nama_program_studi = trim($_POST['nama_program_studi'] ?? '');
        $jenjang = trim($_POST['jenjang'] ?? '');
        $status_aktif = $_POST['status_aktif'] ?? 'Aktif';

        if ($id_fakultas <= 0 || $kode_program_studi === '' || $nama_program_studi === '' || $jenjang === '') {
            throw new RuntimeException('Fakultas, kode, nama program studi, dan jenjang wajib diisi.');
        }
        if (!in_array($status_aktif, ['Aktif', 'Tidak Aktif'], true)) {
            throw new RuntimeException('Status program studi tidak valid.');
        }
        $response = ps_soa_call('create', [
            'id_fakultas' => $id_fakultas,
            'kode_program_studi' => $kode_program_studi,
            'nama_program_studi' => $nama_program_studi,
            'jenjang' => $jenjang,
            'status_aktif' => $status_aktif
        ]);
        if (!empty($response['error'])) {
            throw new RuntimeException(ps_soa_message($response, 'Data program studi gagal disimpan.'));
        }
        $_SESSION['success'] = ps_soa_message($response, 'Program Studi berhasil ditambahkan.');
        header('Location: program_studi.php'); exit;
    } catch (Throwable $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="id"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tambah Program Studi - SIM Mahasiswa</title><link rel="stylesheet" href="style.css">
</head><body class="app-body">
<?php include __DIR__ . '/topbar.php'; ?><?php include __DIR__ . '/sideleftbar.php'; ?>
<main class="main-content">
    <div class="page-heading"><span class="eyebrow">MASTER DATA</span><h1>Tambah Program Studi</h1><p>Tambahkan program studi baru ke dalam fakultas.</p></div>
    <?php if (!empty($_SESSION['error'])): ?><div class="alert alert-danger"><?= ps_h($_SESSION['error']) ?></div><?php unset($_SESSION['error']); endif; ?>
    <?php if (!$daftar_fakultas): ?><div class="alert alert-danger">Daftar fakultas tidak dapat dimuat. Periksa API Fakultas.</div><?php endif; ?>
    <div class="dashboard-panel">
        <form method="post" action="program_studi_tambah.php">
            <?= csrf_field() ?>
            <div class="form-group"><label for="id_fakultas">Fakultas</label>
                <select name="id_fakultas" id="id_fakultas" class="form-control" required>
                    <option value="">-- Pilih Fakultas --</option>
                    <?php foreach ($daftar_fakultas as $f): ?>
                    <option value="<?= (int)$f['id_fakultas'] ?>" <?= (string)$id_fakultas === (string)$f['id_fakultas'] ? 'selected' : '' ?>>
                        <?= ps_h(($f['kode_universitas'] ?? '') . ' - ' . ($f['nama_universitas'] ?? '') . ' | ' . ($f['kode_fakultas'] ?? '') . ' - ' . ($f['nama_fakultas'] ?? '')) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label for="kode_program_studi">Kode Program Studi</label>
                <input type="text" name="kode_program_studi" id="kode_program_studi" class="form-control" maxlength="20" value="<?= ps_h($kode_program_studi) ?>" required>
            </div>
            <div class="form-group"><label for="nama_program_studi">Nama Program Studi</label>
                <input type="text" name="nama_program_studi" id="nama_program_studi" class="form-control" maxlength="200" value="<?= ps_h($nama_program_studi) ?>" required>
            </div>
            <div class="form-group"><label for="jenjang">Jenjang</label>
                <input type="text" name="jenjang" id="jenjang" class="form-control" maxlength="20" value="<?= ps_h($jenjang) ?>" placeholder="Contoh: S1, S2, S3, D3, D4" required>
            </div>
            <div class="form-group"><label for="status_aktif">Status</label>
                <select name="status_aktif" id="status_aktif" class="form-control">
                    <option value="Aktif" <?= $status_aktif === 'Aktif' ? 'selected' : '' ?>>Aktif</option>
                    <option value="Tidak Aktif" <?= $status_aktif === 'Tidak Aktif' ? 'selected' : '' ?>>Tidak Aktif</option>
                </select>
            </div>
            <div class="form-actions"><a href="program_studi.php" class="btn btn-outline">Batal</a><button type="submit" class="btn btn-primary">Simpan Program Studi</button></div>
        </form>
    </div>
</main></body></html>
