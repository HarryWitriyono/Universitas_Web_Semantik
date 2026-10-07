<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/koneksi.php';

wajib_role(['ADMIN', 'OPERATOR_PRODI', 'DEKANAT', 'REKTORAT']);

$role = $_SESSION['kode_role'] ?? '';

$where = [];
$params = [];
$types = '';

$cari = trim($_GET['cari'] ?? '');
$status = trim($_GET['status'] ?? '');

if ($cari !== '') {
    $where[] = "(m.npm LIKE ? OR m.nama_mahasiswa LIKE ?)";
    $like = '%' . $cari . '%';
    $params[] = $like;
    $params[] = $like;
    $types .= 'ss';
}

if ($status !== '') {
    $where[] = "m.status_mahasiswa = ?";
    $params[] = $status;
    $types .= 's';
}

/*
 * Pembatasan berdasarkan scope RBAC.
 */
if ($role === 'OPERATOR_PRODI') {
    $id_program_studi = (int)($_SESSION['id_program_studi'] ?? 0);

    if ($id_program_studi <= 0) {
        $where[] = "1 = 0";
    } else {
        $where[] = "m.id_program_studi = ?";
        $params[] = $id_program_studi;
        $types .= 'i';
    }
} elseif ($role === 'DEKANAT') {
    $id_fakultas = (int)($_SESSION['id_fakultas'] ?? 0);

    if ($id_fakultas <= 0) {
        $where[] = "1 = 0";
    } else {
        $where[] = "ps.id_fakultas = ?";
        $params[] = $id_fakultas;
        $types .= 'i';
    }
} elseif ($role === 'REKTORAT') {
    $id_universitas = (int)($_SESSION['id_universitas'] ?? 0);

    if ($id_universitas <= 0) {
        $where[] = "1 = 0";
    } else {
        $where[] = "f.id_universitas = ?";
        $params[] = $id_universitas;
        $types .= 'i';
    }
}

$sql = "SELECT
            m.id_mahasiswa,
            m.npm,
            m.nama_mahasiswa,
            m.jenis_kelamin,
            m.tanggal_masuk,
            m.status_mahasiswa,
            ps.kode_program_studi,
            ps.nama_program_studi,
            f.kode_fakultas,
            f.nama_fakultas
        FROM mahasiswa m
        INNER JOIN program_studi ps
            ON ps.id_program_studi = m.id_program_studi
        INNER JOIN fakultas f
            ON f.id_fakultas = ps.id_fakultas";

if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY m.nama_mahasiswa ASC";

$stmt = mysqli_prepare($koneksi, $sql);

if (!$stmt) {
    die('Gagal menyiapkan query mahasiswa: ' .
        htmlspecialchars(mysqli_error($koneksi), ENT_QUOTES, 'UTF-8'));
}

if ($params) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$result) {
    die('Gagal mengambil data mahasiswa: ' .
        htmlspecialchars(mysqli_stmt_error($stmt), ENT_QUOTES, 'UTF-8'));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mahasiswa - SIM Mahasiswa</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="app-body">

<?php include __DIR__ . '/topbar.php'; ?>
<?php include __DIR__ . '/sideleftbar.php'; ?>

<main class="main-content">

    <div class="page-heading page-heading-flex">
        <div>
            <span class="eyebrow">DATA AKADEMIK</span>
            <h1>Data Mahasiswa</h1>
            <p>Kelola dan lihat data mahasiswa sesuai hak akses.</p>
        </div>

        <?php if (in_array($role, ['ADMIN', 'OPERATOR_PRODI'], true)): ?>
            <a href="mahasiswa_tambah.php" class="btn btn-primary">
                + Tambah Mahasiswa
            </a>
        <?php endif; ?>
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

    <div class="dashboard-panel" style="margin-bottom:25px;">
        <form method="get" style="padding:20px;">
            <div style="display:grid;grid-template-columns:2fr 1fr auto auto;gap:10px;align-items:end;">
                <div>
                    <label style="display:block;font-weight:700;font-size:13px;margin-bottom:7px;">
                        Cari Mahasiswa
                    </label>
                    <input
                        type="text"
                        name="cari"
                        value="<?= htmlspecialchars($cari, ENT_QUOTES, 'UTF-8') ?>"
                        placeholder="NPM atau nama mahasiswa"
                        style="width:100%;box-sizing:border-box;padding:11px 13px;border:1px solid #d1d5db;border-radius:8px;font:inherit;"
                    >
                </div>

                <div>
                    <label style="display:block;font-weight:700;font-size:13px;margin-bottom:7px;">
                        Status
                    </label>
                    <select
                        name="status"
                        style="width:100%;box-sizing:border-box;padding:11px 13px;border:1px solid #d1d5db;border-radius:8px;font:inherit;background:#fff;"
                    >
                        <option value="">-- Semua Status --</option>
                        <?php foreach (['Aktif','Cuti','Lulus','Mengundurkan Diri','Drop Out','Tidak Aktif'] as $status_option): ?>
                            <option
                                value="<?= htmlspecialchars($status_option, ENT_QUOTES, 'UTF-8') ?>"
                                <?= $status === $status_option ? 'selected' : '' ?>
                            >
                                <?= htmlspecialchars($status_option, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">Cari</button>
                <a href="mahasiswa.php" class="btn btn-outline">Reset</a>
            </div>
        </form>
    </div>

    <div class="dashboard-panel">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th width="55">No.</th>
                        <th width="120">NPM</th>
                        <th>Nama Mahasiswa</th>
                        <th>Program Studi</th>
                        <th>Fakultas</th>
                        <th>JK</th>
                        <th>Status</th>
                        <th width="180">Aksi</th>
                    </tr>
                </thead>

                <tbody>
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <?php $nomor = 1; ?>

                    <?php while ($data_mahasiswa = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?= $nomor++ ?></td>

                            <td>
                                <span class="badge">
                                    <?= htmlspecialchars($data_mahasiswa['npm'], ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>

                            <td>
                                <strong>
                                    <?= htmlspecialchars($data_mahasiswa['nama_mahasiswa'], ENT_QUOTES, 'UTF-8') ?>
                                </strong>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $data_mahasiswa['nama_program_studi'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                                <small style="display:block;color:#6b7280;">
                                    <?= htmlspecialchars(
                                        $data_mahasiswa['kode_program_studi'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </small>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $data_mahasiswa['nama_fakultas'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                                <small style="display:block;color:#6b7280;">
                                    <?= htmlspecialchars(
                                        $data_mahasiswa['kode_fakultas'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </small>
                            </td>

                            <td>
                                <?= $data_mahasiswa['jenis_kelamin'] === 'L'
                                    ? 'Laki-laki'
                                    : 'Perempuan' ?>
                            </td>

                            <td>
                                <?php if ($data_mahasiswa['status_mahasiswa'] === 'Aktif'): ?>
                                    <span class="badge">
                                        Aktif
                                    </span>
                                <?php else: ?>
                                    <?= htmlspecialchars(
                                        $data_mahasiswa['status_mahasiswa'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                <?php endif; ?>
                            </td>

                            <td>
                                <div class="action-group">

                                    <?php if (in_array($role, ['ADMIN', 'OPERATOR_PRODI'], true)): ?>
                                        <a
                                            href="mahasiswa_edit.php?id=<?= (int)$data_mahasiswa['id_mahasiswa'] ?>"
                                            class="btn btn-sm btn-outline"
                                        >Edit</a>
                                    <?php endif; ?>

                                    <?php if ($role === 'ADMIN'): ?>
                                        <form
                                            action="mahasiswa_hapus.php"
                                            method="post"
                                            onsubmit="return confirm('Hapus mahasiswa ini?');"
                                        >
                                            <?= csrf_field() ?>

                                            <input
                                                type="hidden"
                                                name="id_mahasiswa"
                                                value="<?= (int)$data_mahasiswa['id_mahasiswa'] ?>"
                                            >

                                            <button
                                                type="submit"
                                                class="btn btn-sm btn-danger"
                                            >Hapus</button>
                                        </form>
                                    <?php endif; ?>

                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>

                <?php else: ?>
                    <tr>
                        <td colspan="8" class="empty-state">
                            Belum ada data mahasiswa.
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
<?php mysqli_stmt_close($stmt); ?>
