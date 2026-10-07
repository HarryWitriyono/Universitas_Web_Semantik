<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/koneksi.php';

wajib_role(['ADMIN']);

$sql = "SELECT
            p.id_pengguna,
            p.username,
            p.nama_lengkap,
            p.email,
            p.no_hp,
            p.status_aktif,
            p.last_login,
            r.kode_role,
            r.nama_role,
            u.nama_universitas,
            f.nama_fakultas,
            ps.nama_program_studi
        FROM pengguna p
        INNER JOIN roles r ON r.id_role = p.id_role
        LEFT JOIN universitas u ON u.id_universitas = p.id_universitas
        LEFT JOIN fakultas f ON f.id_fakultas = p.id_fakultas
        LEFT JOIN program_studi ps ON ps.id_program_studi = p.id_program_studi
        ORDER BY p.nama_lengkap ASC";

$result = mysqli_query($koneksi, $sql);

if (!$result) {
    die('Gagal mengambil data pengguna: ' . htmlspecialchars(mysqli_error($koneksi)));
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
        .user-role-badge {
            display:inline-block;
            padding:5px 9px;
            border-radius:6px;
            background:#ecfdf5;
            color:#166534;
            font-size:12px;
            font-weight:700;
        }
        .status-aktif {
            display:inline-block;
            padding:5px 9px;
            border-radius:20px;
            background:#dcfce7;
            color:#166534;
            font-size:12px;
            font-weight:700;
        }
        .status-nonaktif {
            display:inline-block;
            padding:5px 9px;
            border-radius:20px;
            background:#f3f4f6;
            color:#4b5563;
            font-size:12px;
            font-weight:700;
        }
        .scope-text {
            line-height:1.5;
            font-size:13px;
        }
    </style>
</head>
<body class="app-body">

<?php include __DIR__ . '/topbar.php'; ?>
<?php include __DIR__ . '/sideleftbar.php'; ?>

<main class="main-content">

    <div class="page-heading page-heading-flex">
        <div>
            <span class="eyebrow">MASTER DATA</span>
            <h1>Pengguna</h1>
            <p>Kelola akun pengguna dan scope kewenangan RBAC.</p>
        </div>

        <a href="pengguna_tambah.php" class="btn btn-primary">
            + Tambah Pengguna
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
                        <th width="55">No.</th>
                        <th>Username</th>
                        <th>Nama Lengkap</th>
                        <th>Role</th>
                        <th>Scope</th>
                        <th>Status</th>
                        <th>Last Login</th>
                        <th width="160">Aksi</th>
                    </tr>
                </thead>

                <tbody>
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <?php $nomor = 1; ?>

                    <?php while ($data_pengguna = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?= $nomor++ ?></td>

                            <td>
                                <strong><?= htmlspecialchars($data_pengguna['username'], ENT_QUOTES, 'UTF-8') ?></strong>
                            </td>

                            <td>
                                <?= htmlspecialchars($data_pengguna['nama_lengkap'], ENT_QUOTES, 'UTF-8') ?>
                                <?php if (!empty($data_pengguna['email'])): ?>
                                    <div class="scope-text">
                                        <?= htmlspecialchars($data_pengguna['email'], ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                <?php endif; ?>
                            </td>

                            <td>
                                <span class="user-role-badge">
                                    <?= htmlspecialchars($data_pengguna['nama_role'], ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>

                            <td class="scope-text">
                                <?php if ($data_pengguna['nama_program_studi']): ?>
                                    <?= htmlspecialchars($data_pengguna['nama_program_studi'], ENT_QUOTES, 'UTF-8') ?><br>
                                <?php endif; ?>

                                <?php if ($data_pengguna['nama_fakultas']): ?>
                                    <?= htmlspecialchars($data_pengguna['nama_fakultas'], ENT_QUOTES, 'UTF-8') ?><br>
                                <?php endif; ?>

                                <?php if ($data_pengguna['nama_universitas']): ?>
                                    <?= htmlspecialchars($data_pengguna['nama_universitas'], ENT_QUOTES, 'UTF-8') ?>
                                <?php endif; ?>

                                <?php if (!$data_pengguna['nama_universitas'] && !$data_pengguna['nama_fakultas'] && !$data_pengguna['nama_program_studi']): ?>
                                    -
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php if ($data_pengguna['status_aktif'] === 'Aktif'): ?>
                                    <span class="status-aktif">Aktif</span>
                                <?php else: ?>
                                    <span class="status-nonaktif">Tidak Aktif</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?= $data_pengguna['last_login']
                                    ? htmlspecialchars($data_pengguna['last_login'], ENT_QUOTES, 'UTF-8')
                                    : '-' ?>
                            </td>

                            <td>
                                <div class="action-group">
                                    <a
                                        href="pengguna_edit.php?id=<?= (int)$data_pengguna['id_pengguna'] ?>"
                                        class="btn btn-sm btn-outline"
                                    >
                                        Edit
                                    </a>

                                    <form
                                        action="pengguna_hapus.php"
                                        method="post"
                                        onsubmit="return confirm('Hapus pengguna ini?');"
                                    >
                                        <?= csrf_field() ?>

                                        <input
                                            type="hidden"
                                            name="id_pengguna"
                                            value="<?= (int)$data_pengguna['id_pengguna'] ?>"
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
                        <td colspan="8" class="empty-state">
                            Belum ada data pengguna.
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
