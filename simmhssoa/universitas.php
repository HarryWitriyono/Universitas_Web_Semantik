<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/api_client.php';

wajib_role(['ADMIN']);

$api = soa_request('apiuniversitas.php', [
    'action' => 'list',
    'id_pengguna' => (int) ($_SESSION['id_pengguna'] ?? 0)
]);

$daftar_universitas = [];
if ($api['error'] !== null) {
    $_SESSION['error'] = 'SOA Server tidak dapat dihubungi.';
} elseif ($api['http_code'] !== 200 || empty($api['data']['success'])) {
    $_SESSION['error'] = $api['data']['message'] ?? 'Gagal mengambil data universitas.';
} else {
    $daftar_universitas = $api['data']['data']['universitas'] ?? [];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Universitas - SIM Mahasiswa</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="app-body">

<?php include __DIR__ . '/topbar.php'; ?>
<?php include __DIR__ . '/sideleftbar.php'; ?>

<main class="main-content">

    <div class="page-heading page-heading-flex">
        <div>
            <span class="eyebrow">MASTER DATA</span>
            <h1>Universitas</h1>
            <p>Kelola data universitas dalam sistem.</p>
        </div>

        <a href="universitas_tambah.php" class="btn btn-primary">
            + Tambah Universitas
        </a>
    </div>

    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($_SESSION['success'], ENT_QUOTES, 'UTF-8') ?>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8') ?>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <div class="dashboard-panel">

        <div class="table-responsive">
            <table class="data-table">

                <thead>
                    <tr>
                        <th width="60">No.</th>
                        <th width="130">Kode</th>
                        <th>Nama Universitas</th>
                        <th>Slogan</th>
                        <th>Kota</th>
                        <th>Provinsi</th>
                        <th width="170">Aksi</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (!empty($daftar_universitas)): ?>

                    <?php $nomor = 1; ?>

                    <?php foreach ($daftar_universitas as $data_universitas): ?>

                        <tr>

                            <td><?= $nomor++ ?></td>

                            <td>
                                <span class="badge">
                                    <?= htmlspecialchars($data_universitas['kode_universitas'], ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>

                            <td>
                                <strong>
                                    <?= htmlspecialchars($data_universitas['nama_universitas'], ENT_QUOTES, 'UTF-8') ?>
                                </strong>
                            </td>

                            <td>
                                <?= htmlspecialchars($data_universitas['slogan'] ?? '-', ENT_QUOTES, 'UTF-8') ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($data_universitas['kota'] ?? '-', ENT_QUOTES, 'UTF-8') ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($data_universitas['provinsi'] ?? '-', ENT_QUOTES, 'UTF-8') ?>
                            </td>

                            <td>

                                <div class="action-group">

                                    <a
                                        href="universitas_edit.php?id=<?= (int)$data_universitas['id_universitas'] ?>"
                                        class="btn btn-sm btn-outline"
                                    >
                                        Edit
                                    </a>

                                    <form
                                        action="universitas_hapus.php"
                                        method="post"
                                        onsubmit="return confirm('Hapus universitas ini?');"
                                    >

                                        <?= csrf_field() ?>

                                        <input
                                            type="hidden"
                                            name="id_universitas"
                                            value="<?= (int)$data_universitas['id_universitas'] ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="btn btn-sm btn-danger"
                                        >
                                            Hapus
                                        </button>

                                    </form>

                                </div>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="7" class="empty-state">
                            Belum ada data universitas.
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>

            </table>
        </div>

    </div>

</main>

</body>
</html>
