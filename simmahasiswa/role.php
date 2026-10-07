<?php
require_once 'auth_check.php';
require_once 'koneksi.php';

wajib_role(['ADMIN']);

$result = mysqli_query(
    $koneksi,
    "SELECT id_role, kode_role, nama_role, keterangan, created_at
     FROM roles
     ORDER BY id_role ASC"
);

if (!$result) {
    die("Gagal mengambil data role: " . htmlspecialchars(mysqli_error($koneksi)));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Role - SIM Mahasiswa</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="app-body">

<?php include 'topbar.php'; ?>
<?php include 'sideleftbar.php'; ?>

<main class="main-content">
    <div class="page-heading page-heading-flex">
        <div>
            <span class="eyebrow">MASTER DATA</span>
            <h1>Role Pengguna</h1>
            <p>Kelola role dan hak akses dasar pengguna aplikasi.</p>
        </div>

        <a href="role_tambah.php" class="btn btn-primary">+ Tambah Role</a>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($_SESSION['success']) ?>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($_SESSION['error']) ?>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <div class="dashboard-panel">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th width="70">No.</th>
                        <th width="150">Kode Role</th>
                        <th>Nama Role</th>
                        <th>Keterangan</th>
                        <th width="160">Dibuat</th>
                        <th width="150">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <?php $no = 1; ?>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><span class="badge"><?= htmlspecialchars($row['kode_role']) ?></span></td>
                            <td><strong><?= htmlspecialchars($row['nama_role']) ?></strong></td>
                            <td><?= htmlspecialchars($row['keterangan'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($row['created_at']) ?></td>
                            <td>
                                <div class="action-group">
                                    <a class="btn btn-sm btn-outline" href="role_edit.php?id=<?= (int)$row['id_role'] ?>">Edit</a>

                                    <form action="role_hapus.php" method="post" onsubmit="return confirm('Hapus role ini?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id_role" value="<?= (int)$row['id_role'] ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="empty-state">Belum ada data role.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>
</body>
</html>
