<?php
require_once __DIR__ . '/pengguna_helper.php';

$response = pengguna_soa_call('list');
$daftar = pengguna_soa_data($response, 'pengguna');
$error = !empty($response['error']) ? pengguna_soa_message($response, 'Gagal mengambil data pengguna.') : '';
if (empty($response['error']) && (($response['data']['success'] ?? false) !== true)) {
    $error = pengguna_soa_message($response, 'Provider gagal mengambil data pengguna.');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pengguna - SIM Mahasiswa</title>
<link rel="stylesheet" href="style.css">
<style>
.user-role-badge{display:inline-block;padding:5px 9px;border-radius:6px;background:#ecfdf5;color:#166534;font-size:12px;font-weight:700}
.status-aktif{display:inline-block;padding:5px 9px;border-radius:20px;background:#dcfce7;color:#166534;font-size:12px;font-weight:700}
.status-nonaktif{display:inline-block;padding:5px 9px;border-radius:20px;background:#f3f4f6;color:#4b5563;font-size:12px;font-weight:700}
.scope-text{line-height:1.5;font-size:13px}
</style>
</head>
<body class="app-body">
<?php include __DIR__ . '/topbar.php'; ?>
<?php include __DIR__ . '/sideleftbar.php'; ?>
<main class="main-content">
    <div class="page-heading page-heading-flex">
        <div><span class="eyebrow">MASTER DATA</span><h1>Pengguna</h1><p>Kelola akun pengguna dan scope kewenangan RBAC.</p></div>
        <a href="pengguna_tambah.php" class="btn btn-primary">+ Tambah Pengguna</a>
    </div>

    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= pengguna_h($_SESSION['success']) ?></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= pengguna_h($_SESSION['error']) ?></div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <div class="alert alert-danger"><?= pengguna_h($error) ?></div>
    <?php endif; ?>

    <div class="dashboard-panel">
        <div class="table-responsive">
            <table class="data-table">
                <thead><tr>
                    <th width="55">No.</th><th>Username</th><th>Nama Lengkap</th>
                    <th>Role</th><th>Scope</th><th>Status</th><th>Last Login</th><th width="160">Aksi</th>
                </tr></thead>
                <tbody>
                <?php if ($daftar): $nomor = 1; foreach ($daftar as $data_pengguna): ?>
                    <tr>
                        <td><?= $nomor++ ?></td>
                        <td><strong><?= pengguna_h($data_pengguna['username'] ?? '') ?></strong></td>
                        <td><?= pengguna_h($data_pengguna['nama_lengkap'] ?? '') ?>
                            <?php if (!empty($data_pengguna['email'])): ?>
                                <div class="scope-text"><?= pengguna_h($data_pengguna['email']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td><span class="user-role-badge"><?= pengguna_h($data_pengguna['nama_role'] ?? '') ?></span></td>
                        <td class="scope-text">
                            <?php
                            $scope = array_filter([
                                $data_pengguna['nama_program_studi'] ?? '',
                                $data_pengguna['nama_fakultas'] ?? '',
                                $data_pengguna['nama_universitas'] ?? ''
                            ]);
                            echo $scope ? implode('<br>', array_map('pengguna_h', $scope)) : '-';
                            ?>
                        </td>
                        <td>
                            <?php if (($data_pengguna['status_aktif'] ?? '') === 'Aktif'): ?>
                                <span class="status-aktif">Aktif</span>
                            <?php else: ?>
                                <span class="status-nonaktif">Tidak Aktif</span>
                            <?php endif; ?>
                        </td>
                        <td><?= !empty($data_pengguna['last_login']) ? pengguna_h($data_pengguna['last_login']) : '-' ?></td>
                        <td><div class="action-group">
                            <a href="pengguna_edit.php?id=<?= (int)$data_pengguna['id_pengguna'] ?>" class="btn btn-sm btn-outline">Edit</a>
                            <form action="pengguna_hapus.php" method="post" onsubmit="return confirm('Hapus pengguna ini?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id_pengguna" value="<?= (int)$data_pengguna['id_pengguna'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                            </form>
                        </div></td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="8" class="empty-state"><?= $error !== '' ? 'Data pengguna belum dapat dimuat dari Provider SOA.' : 'Belum ada data pengguna.' ?></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>
</body>
</html>
