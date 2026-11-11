<?php
require_once __DIR__ . '/program_studi_helper.php';

$response = ps_soa_call('list');
$daftar = ps_soa_data($response, 'program_studi');
$error = !empty($response['error']) ? ps_soa_message($response, 'Gagal mengambil data program studi.') : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Program Studi - SIM Mahasiswa</title>
<link rel="stylesheet" href="style.css">
</head>
<body class="app-body">
<?php include __DIR__ . '/topbar.php'; ?>
<?php include __DIR__ . '/sideleftbar.php'; ?>
<main class="main-content">
    <div class="page-heading page-heading-flex">
        <div>
            <span class="eyebrow">MASTER DATA</span>
            <h1>Program Studi</h1>
            <p>Kelola data program studi berdasarkan fakultas.</p>
        </div>
        <a href="program_studi_tambah.php" class="btn btn-primary">+ Tambah Program Studi</a>
    </div>

    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= ps_h($_SESSION['success']) ?></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= ps_h($_SESSION['error']) ?></div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <div class="alert alert-danger"><?= ps_h($error) ?></div>
    <?php endif; ?>

    <div class="dashboard-panel">
        <div class="table-responsive">
            <table class="data-table">
                <thead><tr>
                    <th width="60">No.</th><th width="120">Kode</th>
                    <th>Nama Program Studi</th><th>Fakultas</th><th>Universitas</th>
                    <th width="100">Jenjang</th><th width="110">Mahasiswa</th>
                    <th width="110">Status</th><th width="170">Aksi</th>
                </tr></thead>
                <tbody>
                <?php if ($daftar): $nomor = 1; foreach ($daftar as $row): ?>
                    <tr>
                        <td><?= $nomor++ ?></td>
                        <td><span class="badge"><?= ps_h($row['kode_program_studi'] ?? '') ?></span></td>
                        <td><strong><?= ps_h($row['nama_program_studi'] ?? '') ?></strong></td>
                        <td><?= ps_h($row['nama_fakultas'] ?? '') ?><small style="display:block;color:#6b7280;"><?= ps_h($row['kode_fakultas'] ?? '') ?></small></td>
                        <td><?= ps_h($row['nama_universitas'] ?? '') ?><small style="display:block;color:#6b7280;"><?= ps_h($row['kode_universitas'] ?? '') ?></small></td>
                        <td><span class="badge"><?= ps_h($row['jenjang'] ?? '') ?></span></td>
                        <td><?= (int)($row['jumlah_mahasiswa'] ?? 0) ?></td>
                        <td><?php if (($row['status_aktif'] ?? '') === 'Aktif'): ?>
                            <span class="badge">Aktif</span>
                        <?php else: ?><span class="badge" style="opacity:0.6;">Tidak Aktif</span><?php endif; ?></td>
                        <td><div class="action-group">
                            <a class="btn btn-sm btn-outline" href="program_studi_edit.php?id=<?= (int)$row['id_program_studi'] ?>">Edit</a>
                            <form action="program_studi_hapus.php" method="post" onsubmit="return confirm('Hapus program studi ini?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id_program_studi" value="<?= (int)$row['id_program_studi'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                            </form>
                        </div></td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="9" class="empty-state"><?= $error !== '' ? 'Data belum dapat dimuat dari provider SOA.' : 'Belum ada data program studi.' ?></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>
</body>
</html>
