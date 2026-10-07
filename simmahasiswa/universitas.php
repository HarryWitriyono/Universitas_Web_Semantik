<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/koneksi.php';

wajib_role(['ADMIN']);

$result = mysqli_query(
    $koneksi,
    "SELECT id_universitas, kode_universitas, nama_universitas, slogan,
            kota, provinsi, website, email, telepon
     FROM universitas
     ORDER BY nama_universitas ASC"
);

if (!$result) {
    die('Gagal mengambil data universitas: ' . htmlspecialchars(mysqli_error($koneksi)));
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

                <?php if (mysqli_num_rows($result) > 0): ?>

                    <?php $nomor = 1; ?>

                    <?php while ($data_universitas = mysqli_fetch_assoc($result)): ?>

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

                    <?php endwhile; ?>

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
