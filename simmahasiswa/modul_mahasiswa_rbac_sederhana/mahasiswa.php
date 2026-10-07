<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/koneksi.php';

wajib_role(['ADMIN', 'OPERATOR_PRODI', 'DEKANAT', 'REKTORAT']);

$role = $_SESSION['kode_role'] ?? '';
$id_universitas = (int)($_SESSION['id_universitas'] ?? 0);
$id_fakultas = (int)($_SESSION['id_fakultas'] ?? 0);
$id_prodi = (int)($_SESSION['id_program_studi'] ?? 0);

$where = [];
$params = [];
$types = '';

if ($role === 'OPERATOR_PRODI') {
    $where[] = 'm.id_program_studi = ?';
    $params[] = $id_prodi;
    $types .= 'i';
} elseif ($role === 'DEKANAT') {
    $where[] = 'f.id_fakultas = ?';
    $params[] = $id_fakultas;
    $types .= 'i';
} elseif ($role === 'REKTORAT') {
    $where[] = 'u.id_universitas = ?';
    $params[] = $id_universitas;
    $types .= 'i';
}

$cari = trim($_GET['cari'] ?? '');
$status = trim($_GET['status'] ?? '');
$prodi_filter = (int)($_GET['prodi'] ?? 0);

if ($cari !== '') {
    $where[] = '(m.npm LIKE ? OR m.nama_mahasiswa LIKE ?)';
    $like = '%' . $cari . '%';
    $params[] = $like;
    $params[] = $like;
    $types .= 'ss';
}

if ($status !== '' && in_array($status, ['Aktif','Cuti','Lulus','Mengundurkan Diri','Drop Out','Tidak Aktif'], true)) {
    $where[] = 'm.status_mahasiswa = ?';
    $params[] = $status;
    $types .= 's';
}

if ($prodi_filter > 0 && $role === 'ADMIN') {
    $where[] = 'm.id_program_studi = ?';
    $params[] = $prodi_filter;
    $types .= 'i';
}

$sql = "SELECT
            m.id_mahasiswa,
            m.id_pengguna,
            m.id_program_studi,
            m.npm,
            m.nama_mahasiswa,
            m.jenis_kelamin,
            m.tanggal_masuk,
            m.status_mahasiswa,
            ps.kode_program_studi,
            ps.nama_program_studi,
            ps.jenjang,
            f.kode_fakultas,
            f.nama_fakultas,
            u.nama_universitas
        FROM mahasiswa m
        INNER JOIN program_studi ps ON ps.id_program_studi = m.id_program_studi
        INNER JOIN fakultas f ON f.id_fakultas = ps.id_fakultas
        INNER JOIN universitas u ON u.id_universitas = f.id_universitas";

if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY m.nama_mahasiswa ASC';

$stmt = mysqli_prepare($koneksi, $sql);
if (!$stmt) {
    die('Gagal menyiapkan data mahasiswa: ' . htmlspecialchars(mysqli_error($koneksi), ENT_QUOTES, 'UTF-8'));
}

if ($params) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$prodi = mysqli_query($koneksi,
    "SELECT ps.id_program_studi, ps.kode_program_studi, ps.nama_program_studi,
            ps.jenjang, f.nama_fakultas
     FROM program_studi ps
     INNER JOIN fakultas f ON f.id_fakultas = ps.id_fakultas
     ORDER BY f.nama_fakultas, ps.nama_program_studi"
);
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
            <h1>Mahasiswa</h1>
            <p>Kelola dan lihat data mahasiswa sesuai kewenangan pengguna.</p>
        </div>
        <?php if (in_array($role, ['ADMIN','OPERATOR_PRODI'], true)): ?>
            <a href="mahasiswa_tambah.php" class="btn btn-primary">+ Tambah Mahasiswa</a>
        <?php endif; ?>
    </div>

    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success'], ENT_QUOTES, 'UTF-8') ?></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8') ?></div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <div class="dashboard-panel" style="margin-bottom:20px;">
        <form method="get" style="padding:18px;">
            <div style="display:grid;grid-template-columns:2fr 1fr 2fr auto;gap:12px;align-items:end;">
                <div>
                    <label style="display:block;margin-bottom:6px;font-weight:700;font-size:13px;">Cari NPM / Nama</label>
                    <input type="text" name="cari" value="<?= htmlspecialchars($cari, ENT_QUOTES, 'UTF-8') ?>" placeholder="Ketik NPM atau nama..." style="width:100%;box-sizing:border-box;padding:10px 12px;border:1px solid #d1d5db;border-radius:8px;">
                </div>
                <div>
                    <label style="display:block;margin-bottom:6px;font-weight:700;font-size:13px;">Status</label>
                    <select name="status" style="width:100%;padding:10px 12px;border:1px solid #d1d5db;border-radius:8px;">
                        <option value="">Semua</option>
                        <?php foreach (['Aktif','Cuti','Lulus','Mengundurkan Diri','Drop Out','Tidak Aktif'] as $s): ?>
                            <option value="<?= htmlspecialchars($s, ENT_QUOTES, 'UTF-8') ?>" <?= $status === $s ? 'selected' : '' ?>><?= htmlspecialchars($s, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if ($role === 'ADMIN'): ?>
                <div>
                    <label style="display:block;margin-bottom:6px;font-weight:700;font-size:13px;">Program Studi</label>
                    <select name="prodi" style="width:100%;padding:10px 12px;border:1px solid #d1d5db;border-radius:8px;">
                        <option value="0">Semua Program Studi</option>
                        <?php while ($p = mysqli_fetch_assoc($prodi)): ?>
                            <option value="<?= (int)$p['id_program_studi'] ?>" <?= $prodi_filter === (int)$p['id_program_studi'] ? 'selected' : '' ?>><?= htmlspecialchars($p['kode_program_studi'].' - '.$p['nama_program_studi'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <?php else: ?>
                    <div></div>
                <?php endif; ?>
                <div style="display:flex;gap:8px;">
                    <button type="submit" class="btn btn-primary">Cari</button>
                    <a href="mahasiswa.php" class="btn btn-sm btn-outline" style="padding:10px 14px;">Reset</a>
                </div>
            </div>
        </form>
    </div>

    <div class="dashboard-panel">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th width="50">No.</th>
                        <th>NPM</th>
                        <th>Nama Mahasiswa</th>
                        <th>Program Studi</th>
                        <th>Fakultas</th>
                        <th>JK</th>
                        <th>Status</th>
                        <th width="210">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <?php $nomor = 1; while ($m = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?= $nomor++ ?></td>
                        <td><span class="badge"><?= htmlspecialchars($m['npm'], ENT_QUOTES, 'UTF-8') ?></span></td>
                        <td>
                            <strong><?= htmlspecialchars($m['nama_mahasiswa'], ENT_QUOTES, 'UTF-8') ?></strong>
                            <small style="display:block;color:#6b7280;"><?= htmlspecialchars($m['nama_universitas'], ENT_QUOTES, 'UTF-8') ?></small>
                        </td>
                        <td><?= htmlspecialchars($m['kode_program_studi'].' - '.$m['nama_program_studi'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($m['kode_fakultas'].' - '.$m['nama_fakultas'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= $m['jenis_kelamin'] === 'L' ? 'L' : 'P' ?></td>
                        <td>
                            <?php if ($m['status_mahasiswa'] === 'Aktif'): ?>
                                <span class="badge" style="background:#dcfce7;color:#166534;">Aktif</span>
                            <?php else: ?>
                                <span class="badge"><?= htmlspecialchars($m['status_mahasiswa'], ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="action-group">
                                <a href="mahasiswa_edit.php?id=<?= (int)$m['id_mahasiswa'] ?>&mode=view" class="btn btn-sm btn-outline">Detail</a>
                                <?php if (in_array($role, ['ADMIN','OPERATOR_PRODI'], true)): ?>
                                    <a href="mahasiswa_edit.php?id=<?= (int)$m['id_mahasiswa'] ?>" class="btn btn-sm btn-outline">Edit</a>
                                <?php endif; ?>
                                <?php if ($role === 'ADMIN'): ?>
                                    <form action="mahasiswa_hapus.php" method="post" onsubmit="return confirm('Hapus data mahasiswa ini?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id_mahasiswa" value="<?= (int)$m['id_mahasiswa'] ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="8" class="empty-state">Belum ada data mahasiswa.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>
</body>
</html>
<?php mysqli_stmt_close($stmt); ?>
