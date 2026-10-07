<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/koneksi.php';

wajib_role(['ADMIN']);

$judul_halaman = 'Program Studi';
include __DIR__ . '/topbar.php';
include __DIR__ . '/sideleftbar.php';

$sql = "SELECT
            ps.id_program_studi,
            ps.kode_program_studi,
            ps.nama_program_studi,
            ps.jenjang,
            ps.status_aktif,
            f.kode_fakultas,
            f.nama_fakultas,
            u.kode_universitas,
            u.nama_universitas
        FROM program_studi ps
        INNER JOIN fakultas f ON f.id_fakultas = ps.id_fakultas
        INNER JOIN universitas u ON u.id_universitas = f.id_universitas
        ORDER BY u.nama_universitas, f.nama_fakultas, ps.nama_program_studi";

$result = mysqli_query($koneksi, $sql);

if ($result === false) {
    $error_db = mysqli_error($koneksi);
}
?>

<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h4 class="mb-1">Program Studi</h4>
            <p class="text-muted mb-0">Kelola data program studi.</p>
        </div>
        <a href="program_studi_tambah.php" class="btn btn-primary">
            + Tambah Program Studi
        </a>
    </div>

    <?php if (isset($_GET['pesan'])): ?>
        <?php if ($_GET['pesan'] === 'tambah'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                Program Studi berhasil ditambahkan.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif ($_GET['pesan'] === 'edit'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                Program Studi berhasil diperbarui.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif ($_GET['pesan'] === 'hapus'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                Program Studi berhasil dihapus.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif ($_GET['pesan'] === 'gagal'): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_GET['detail'] ?? 'Data tidak dapat diproses.', ENT_QUOTES, 'UTF-8') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if (isset($error_db)): ?>
        <div class="alert alert-danger">
            Gagal mengambil data Program Studi.
        </div>
    <?php else: ?>
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="60">No</th>
                                <th>Universitas</th>
                                <th>Fakultas</th>
                                <th width="110">Kode</th>
                                <th>Program Studi</th>
                                <th width="90">Jenjang</th>
                                <th width="110">Status</th>
                                <th width="150">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php $no = 1; ?>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($row['kode_universitas'], ENT_QUOTES, 'UTF-8') ?></strong><br>
                                        <small class="text-muted"><?= htmlspecialchars($row['nama_universitas'], ENT_QUOTES, 'UTF-8') ?></small>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($row['kode_fakultas'], ENT_QUOTES, 'UTF-8') ?></strong><br>
                                        <small class="text-muted"><?= htmlspecialchars($row['nama_fakultas'], ENT_QUOTES, 'UTF-8') ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($row['kode_program_studi'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($row['nama_program_studi'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($row['jenjang'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <?php if ($row['status_aktif'] === 'Aktif'): ?>
                                            <span class="badge bg-success">Aktif</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Tidak Aktif</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-nowrap">
                                        <a href="program_studi_edit.php?id=<?= (int) $row['id_program_studi'] ?>" class="btn btn-sm btn-warning">
                                            Edit
                                        </a>
                                        <form method="post" action="program_studi_hapus.php" class="d-inline" onsubmit="return confirm('Hapus program studi ini?');">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="id" value="<?= (int) $row['id_program_studi'] ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    Belum ada data program studi.
                                </td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/footer.php'; ?>
