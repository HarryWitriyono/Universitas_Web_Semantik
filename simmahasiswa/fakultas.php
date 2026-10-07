<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/koneksi.php';

wajib_role(['ADMIN']);

$sql = "SELECT
            f.id_fakultas,
            f.kode_fakultas,
            f.nama_fakultas,
            f.id_universitas,
            u.kode_universitas,
            u.nama_universitas,
            (SELECT COUNT(*) FROM program_studi ps WHERE ps.id_fakultas = f.id_fakultas) AS jumlah_prodi
        FROM fakultas f
        INNER JOIN universitas u ON u.id_universitas = f.id_universitas
        ORDER BY u.nama_universitas ASC, f.nama_fakultas ASC";

$result = mysqli_query($koneksi, $sql);

if (!$result) {
    die('Gagal mengambil data fakultas: ' . htmlspecialchars(mysqli_error($koneksi), ENT_QUOTES, 'UTF-8'));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fakultas - SIM Mahasiswa</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="app-body">

<?php include __DIR__ . '/topbar.php'; ?>
<?php include __DIR__ . '/sideleftbar.php'; ?>

<main class="main-content">

    <div class="page-heading page-heading-flex">
        <div>
            <span class="eyebrow">MASTER DATA</span>
            <h1>Fakultas</h1>
            <p>Kelola data fakultas berdasarkan universitas.</p>
        </div>

        <a href="fakultas_tambah.php" class="btn btn-primary">
            + Tambah Fakultas
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
                        <th width="120">Kode</th>
                        <th>Nama Fakultas</th>
                        <th>Universitas</th>
                        <th width="120">Jml. Prodi</th>
                        <th width="170">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <?php $nomor = 1; ?>
                    <?php while ($data_fakultas = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?= $nomor++ ?></td>
                            <td>
                                <span class="badge">
                                    <?= htmlspecialchars($data_fakultas['kode_fakultas'], ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>
                            <td>
                                <strong>
                                    <?= htmlspecialchars($data_fakultas['nama_fakultas'], ENT_QUOTES, 'UTF-8') ?>
                                </strong>
                            </td>
                            <td>
                                <?= htmlspecialchars($data_fakultas['nama_universitas'], ENT_QUOTES, 'UTF-8') ?>
                                <small style="display:block;color:#6b7280;">
                                    <?= htmlspecialchars($data_fakultas['kode_universitas'], ENT_QUOTES, 'UTF-8') ?>
                                </small>
                            </td>
                            <td><?= (int)$data_fakultas['jumlah_prodi'] ?></td>
                            <td>
                                <div class="action-group">
                                    <a
                                        href="fakultas_edit.php?id=<?= (int)$data_fakultas['id_fakultas'] ?>"
                                        class="btn btn-sm btn-outline"
                                    >Edit</a>

                                    <form
                                        action="fakultas_hapus.php"
                                        method="post"
                                        onsubmit="return confirm('Hapus fakultas ini?');"
                                    >
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id_fakultas" value="<?= (int)$data_fakultas['id_fakultas'] ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="empty-state">Belum ada data fakultas.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</main>
</body>
</html>
