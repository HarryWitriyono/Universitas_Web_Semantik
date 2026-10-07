<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/koneksi.php';

wajib_role(['ADMIN', 'OPERATOR_PRODI', 'DEKANAT', 'REKTORAT', 'MAHASISWA']);

$role = $_SESSION['kode_role'] ?? '';
$id_pengguna_session = (int)($_SESSION['id_pengguna'] ?? 0);
$id_universitas_session = (int)($_SESSION['id_universitas'] ?? 0);
$id_fakultas_session = (int)($_SESSION['id_fakultas'] ?? 0);
$id_program_studi_session = (int)($_SESSION['id_program_studi'] ?? 0);

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function bind_dynamic(mysqli_stmt $stmt, string $types, array &$params): void
{
    $refs = [];
    $refs[] = $types;
    foreach ($params as $key => &$value) {
        $refs[] = &$value;
    }
    mysqli_stmt_bind_param($stmt, ...$refs);
}

$filter_fakultas = filter_input(INPUT_GET, 'id_fakultas', FILTER_VALIDATE_INT);
$filter_prodi = filter_input(INPUT_GET, 'id_program_studi', FILTER_VALIDATE_INT);
$filter_status = trim($_GET['status_mahasiswa'] ?? '');
$filter_jk = trim($_GET['jenis_kelamin'] ?? '');
$keyword = trim($_GET['q'] ?? '');

$filter_fakultas = $filter_fakultas ?: 0;
$filter_prodi = $filter_prodi ?: 0;

$statuses = ['Aktif', 'Cuti', 'Lulus', 'Mengundurkan Diri', 'Drop Out', 'Tidak Aktif'];
$jenis_kelamin_options = ['L' => 'Laki-laki', 'P' => 'Perempuan'];

if (!in_array($filter_status, $statuses, true)) {
    $filter_status = '';
}
if (!array_key_exists($filter_jk, $jenis_kelamin_options)) {
    $filter_jk = '';
}

/*
 * Daftar Fakultas/Prodi untuk filter.
 * Scope tetap dibatasi sesuai RBAC.
 */
$scope_where = [];
$scope_params = [];
$scope_types = '';

if ($role === 'OPERATOR_PRODI') {
    $scope_where[] = 'ps.id_program_studi = ?';
    $scope_params[] = $id_program_studi_session;
    $scope_types .= 'i';
} elseif ($role === 'DEKANAT') {
    $scope_where[] = 'f.id_fakultas = ?';
    $scope_params[] = $id_fakultas_session;
    $scope_types .= 'i';
} elseif ($role === 'REKTORAT') {
    $scope_where[] = 'u.id_universitas = ?';
    $scope_params[] = $id_universitas_session;
    $scope_types .= 'i';
} elseif ($role === 'MAHASISWA') {
    $scope_where[] = 'm.id_pengguna = ?';
    $scope_params[] = $id_pengguna_session;
    $scope_types .= 'i';
}

$scope_sql = $scope_where ? ' AND ' . implode(' AND ', $scope_where) : '';

$fakultas = [];
$sql_f = "SELECT f.id_fakultas, f.kode_fakultas, f.nama_fakultas
          FROM fakultas f
          INNER JOIN universitas u ON u.id_universitas = f.id_universitas
          WHERE 1=1";

$params_f = [];
$types_f = '';

if ($role === 'OPERATOR_PRODI') {
    $sql_f .= " AND f.id_fakultas = (
                    SELECT ps2.id_fakultas
                    FROM program_studi ps2
                    WHERE ps2.id_program_studi = ?
                )";
    $params_f[] = $id_program_studi_session;
    $types_f .= 'i';
} elseif ($role === 'DEKANAT') {
    $sql_f .= " AND f.id_fakultas = ?";
    $params_f[] = $id_fakultas_session;
    $types_f .= 'i';
} elseif ($role === 'REKTORAT') {
    $sql_f .= " AND u.id_universitas = ?";
    $params_f[] = $id_universitas_session;
    $types_f .= 'i';
} elseif ($role === 'MAHASISWA') {
    $sql_f .= " AND f.id_fakultas = ?";
    $params_f[] = $id_fakultas_session;
    $types_f .= 'i';
}

$sql_f .= " ORDER BY f.nama_fakultas ASC";
$stmt_f = mysqli_prepare($koneksi, $sql_f);
if ($stmt_f) {
    if ($types_f !== '') {
        bind_dynamic($stmt_f, $types_f, $params_f);
    }
    mysqli_stmt_execute($stmt_f);
    $result_f = mysqli_stmt_get_result($stmt_f);
    while ($row = mysqli_fetch_assoc($result_f)) {
        $fakultas[] = $row;
    }
    mysqli_stmt_close($stmt_f);
}

$prodi = [];
$sql_p = "SELECT ps.id_program_studi, ps.id_fakultas, ps.kode_program_studi,
                 ps.nama_program_studi, f.kode_fakultas, f.nama_fakultas
          FROM program_studi ps
          INNER JOIN fakultas f ON f.id_fakultas = ps.id_fakultas
          INNER JOIN universitas u ON u.id_universitas = f.id_universitas
          WHERE ps.status_aktif = 'Aktif'";

$params_p = [];
$types_p = '';

if ($role === 'OPERATOR_PRODI') {
    $sql_p .= " AND ps.id_program_studi = ?";
    $params_p[] = $id_program_studi_session;
    $types_p .= 'i';
} elseif ($role === 'DEKANAT') {
    $sql_p .= " AND f.id_fakultas = ?";
    $params_p[] = $id_fakultas_session;
    $types_p .= 'i';
} elseif ($role === 'REKTORAT') {
    $sql_p .= " AND u.id_universitas = ?";
    $params_p[] = $id_universitas_session;
    $types_p .= 'i';
} elseif ($role === 'MAHASISWA') {
    $sql_p .= " AND ps.id_program_studi = ?";
    $params_p[] = $id_program_studi_session;
    $types_p .= 'i';
}

$sql_p .= " ORDER BY f.nama_fakultas ASC, ps.nama_program_studi ASC";
$stmt_p = mysqli_prepare($koneksi, $sql_p);
if ($stmt_p) {
    if ($types_p !== '') {
        bind_dynamic($stmt_p, $types_p, $params_p);
    }
    mysqli_stmt_execute($stmt_p);
    $result_p = mysqli_stmt_get_result($stmt_p);
    while ($row = mysqli_fetch_assoc($result_p)) {
        $prodi[] = $row;
    }
    mysqli_stmt_close($stmt_p);
}

/*
 * Validasi filter fakultas/prodi terhadap scope.
 * Jika tidak valid, filter dikosongkan agar tidak bisa dipakai untuk
 * melewati batas RBAC.
 */
$allowed_fakultas = array_map('intval', array_column($fakultas, 'id_fakultas'));
$allowed_prodi = array_map('intval', array_column($prodi, 'id_program_studi'));

if ($filter_fakultas > 0 && !in_array($filter_fakultas, $allowed_fakultas, true)) {
    $filter_fakultas = 0;
}
if ($filter_prodi > 0 && !in_array($filter_prodi, $allowed_prodi, true)) {
    $filter_prodi = 0;
}

/*
 * Query utama laporan.
 */
$where = [];
$params = [];
$types = '';

if ($scope_where) {
    $where = $scope_where;
    $params = $scope_params;
    $types = $scope_types;
}

if ($filter_fakultas > 0) {
    $where[] = 'f.id_fakultas = ?';
    $params[] = $filter_fakultas;
    $types .= 'i';
}

if ($filter_prodi > 0) {
    $where[] = 'm.id_program_studi = ?';
    $params[] = $filter_prodi;
    $types .= 'i';
}

if ($filter_status !== '') {
    $where[] = 'm.status_mahasiswa = ?';
    $params[] = $filter_status;
    $types .= 's';
}

if ($filter_jk !== '') {
    $where[] = 'm.jenis_kelamin = ?';
    $params[] = $filter_jk;
    $types .= 's';
}

if ($keyword !== '') {
    $where[] = '(m.npm LIKE ? OR m.nama_mahasiswa LIKE ?)';
    $like = '%' . $keyword . '%';
    $params[] = $like;
    $params[] = $like;
    $types .= 'ss';
}

$where_sql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

$sql_data = "SELECT
                m.id_mahasiswa,
                m.npm,
                m.nama_mahasiswa,
                m.jenis_kelamin,
                m.tanggal_lahir,
                m.tanggal_masuk,
                m.status_mahasiswa,
                ps.kode_program_studi,
                ps.nama_program_studi,
                f.kode_fakultas,
                f.nama_fakultas
             FROM mahasiswa m
             INNER JOIN program_studi ps ON ps.id_program_studi = m.id_program_studi
             INNER JOIN fakultas f ON f.id_fakultas = ps.id_fakultas
             INNER JOIN universitas u ON u.id_universitas = f.id_universitas
             $where_sql
             ORDER BY f.nama_fakultas ASC,
                      ps.nama_program_studi ASC,
                      m.nama_mahasiswa ASC";

$rows = [];
$stmt = mysqli_prepare($koneksi, $sql_data);
if ($stmt) {
    if ($types !== '') {
        bind_dynamic($stmt, $types, $params);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    mysqli_stmt_close($stmt);
}

/*
 * Ringkasan dihitung dari hasil yang sama sehingga selalu konsisten
 * dengan filter yang sedang aktif.
 */
$total = count($rows);
$total_l = 0;
$total_p = 0;
$total_aktif = 0;
$total_lulus = 0;

foreach ($rows as $row) {
    if ($row['jenis_kelamin'] === 'L') {
        $total_l++;
    } elseif ($row['jenis_kelamin'] === 'P') {
        $total_p++;
    }

    if ($row['status_mahasiswa'] === 'Aktif') {
        $total_aktif++;
    }

    if ($row['status_mahasiswa'] === 'Lulus') {
        $total_lulus++;
    }
}

$judul_scope = 'Seluruh Data Mahasiswa';
if ($role === 'OPERATOR_PRODI') {
    $judul_scope = 'Program Studi Anda';
} elseif ($role === 'DEKANAT') {
    $judul_scope = 'Fakultas Anda';
} elseif ($role === 'REKTORAT') {
    $judul_scope = 'Universitas Anda';
} elseif ($role === 'MAHASISWA') {
    $judul_scope = 'Data Saya';
}

$query_string = http_build_query([
    'id_fakultas' => $filter_fakultas ?: null,
    'id_program_studi' => $filter_prodi ?: null,
    'status_mahasiswa' => $filter_status ?: null,
    'jenis_kelamin' => $filter_jk ?: null,
    'q' => $keyword ?: null,
]);

$print_url = 'laporan_mahasiswa.php' . ($query_string !== '' ? '?' . $query_string : '');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Laporan Mahasiswa - SIM Mahasiswa</title>
    <link rel="stylesheet" href="style.css">

    <style>
        .report-panel{background:#fff;border:1px solid #e5e7eb;border-radius:14px;box-shadow:0 4px 18px rgba(0,0,0,.04);overflow:hidden;margin-bottom:20px}
        .report-header{padding:22px 24px;border-bottom:1px solid #e5e7eb}
        .report-header h2{margin:0 0 5px;font-size:20px}
        .report-header p{margin:0;color:#6b7280;font-size:13px}
        .filter-form{padding:20px 24px}
        .filter-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:15px}
        .filter-group label{display:block;margin-bottom:7px;font-size:12px;font-weight:700;color:#374151}
        .filter-group input,.filter-group select{width:100%;box-sizing:border-box;padding:10px 12px;border:1px solid #d1d5db;border-radius:8px;background:#fff;font:inherit}
        .filter-actions{display:flex;gap:10px;align-items:center;margin-top:16px}
        .summary-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:15px;margin-bottom:20px}
        .summary-card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:18px 20px;box-shadow:0 4px 18px rgba(0,0,0,.04)}
        .summary-card .label{font-size:12px;color:#6b7280;margin-bottom:6px}
        .summary-card .value{font-size:25px;font-weight:800;color:#111827}
        .report-table-wrap{overflow-x:auto}
        .report-table{width:100%;border-collapse:collapse;min-width:900px}
        .report-table th,.report-table td{padding:11px 13px;border-bottom:1px solid #e5e7eb;text-align:left;font-size:13px;vertical-align:top}
        .report-table th{background:#f8fafc;color:#374151;font-size:12px;white-space:nowrap}
        .report-table td.center,.report-table th.center{text-align:center}
        .report-footer{padding:14px 20px;color:#6b7280;font-size:12px;border-top:1px solid #e5e7eb}
        .scope-badge{display:inline-block;padding:5px 9px;border-radius:999px;background:#f3f4f6;color:#374151;font-size:11px;font-weight:700}
        .status-badge{display:inline-block;padding:4px 8px;border-radius:999px;background:#f3f4f6;font-size:11px}
        .print-only{display:none}
        @media(max-width:1000px){
            .filter-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
            .summary-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
        }
        @media(max-width:600px){
            .filter-grid,.summary-grid{grid-template-columns:1fr}
            .filter-actions{flex-direction:column;align-items:stretch}
            .filter-actions .btn{width:100%;box-sizing:border-box;text-align:center}
        }
        @media print{
            @page{size:A4 landscape;margin:12mm}
            body{background:#fff!important}
            .topbar,.sidebar,.side-leftbar,.filter-panel,.no-print{display:none!important}
            .main-content{margin:0!important;padding:0!important;width:100%!important}
            .page-heading{margin-bottom:10px}
            .page-heading p{display:none}
            .summary-grid{grid-template-columns:repeat(4,1fr)}
            .summary-card{box-shadow:none}
            .report-panel{box-shadow:none;border:1px solid #ccc}
            .print-only{display:block}
            .report-table th,.report-table td{font-size:9px;padding:6px}
        }
    </style>
</head>
<body class="app-body">

<?php include __DIR__ . '/topbar.php'; ?>
<?php include __DIR__ . '/sideleftbar.php'; ?>

<main class="main-content">

    <div class="page-heading">
        <span class="eyebrow">LAPORAN AKADEMIK</span>
        <h1>Rekap Laporan Mahasiswa</h1>
        <p>Rekap data mahasiswa berdasarkan ruang lingkup akses pengguna.</p>
    </div>

    <div class="report-panel filter-panel">
        <div class="report-header">
            <h2>Filter Laporan</h2>
            <p>Scope akses: <span class="scope-badge"><?= e($judul_scope) ?></span></p>
        </div>

        <form method="get" class="filter-form">
            <div class="filter-grid">

                <?php if ($role === 'ADMIN' || $role === 'REKTORAT'): ?>
                <div class="filter-group">
                    <label for="id_fakultas">Fakultas</label>
                    <select name="id_fakultas" id="id_fakultas">
                        <option value="0">-- Semua Fakultas --</option>
                        <?php foreach ($fakultas as $f): ?>
                            <option value="<?= (int)$f['id_fakultas'] ?>"
                                <?= $filter_fakultas === (int)$f['id_fakultas'] ? 'selected' : '' ?>>
                                <?= e($f['kode_fakultas'] . ' - ' . $f['nama_fakultas']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php else: ?>
                    <input type="hidden" name="id_fakultas" value="<?= (int)$filter_fakultas ?>">
                <?php endif; ?>

                <div class="filter-group">
                    <label for="id_program_studi">Program Studi</label>
                    <select name="id_program_studi" id="id_program_studi">
                        <option value="0">-- Semua Program Studi --</option>
                        <?php foreach ($prodi as $p): ?>
                            <option value="<?= (int)$p['id_program_studi'] ?>"
                                <?= $filter_prodi === (int)$p['id_program_studi'] ? 'selected' : '' ?>>
                                <?= e($p['kode_program_studi'] . ' - ' . $p['nama_program_studi']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="status_mahasiswa">Status Mahasiswa</label>
                    <select name="status_mahasiswa" id="status_mahasiswa">
                        <option value="">-- Semua Status --</option>
                        <?php foreach ($statuses as $status): ?>
                            <option value="<?= e($status) ?>" <?= $filter_status === $status ? 'selected' : '' ?>>
                                <?= e($status) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="jenis_kelamin">Jenis Kelamin</label>
                    <select name="jenis_kelamin" id="jenis_kelamin">
                        <option value="">-- Semua --</option>
                        <?php foreach ($jenis_kelamin_options as $key => $label): ?>
                            <option value="<?= e($key) ?>" <?= $filter_jk === $key ? 'selected' : '' ?>>
                                <?= e($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="q">Cari NPM / Nama</label>
                    <input type="search" name="q" id="q" value="<?= e($keyword) ?>" maxlength="100"
                           placeholder="Ketik NPM atau nama mahasiswa">
                </div>

            </div>

            <div class="filter-actions no-print">
                <button type="submit" class="btn btn-primary">Tampilkan Laporan</button>
                <a href="laporan_mahasiswa.php" class="btn btn-outline">Reset</a>
                <button type="button" class="btn btn-outline" onclick="window.print()">Cetak</button>
            </div>
        </form>
    </div>

    <div class="summary-grid">
        <div class="summary-card">
            <div class="label">Total Mahasiswa</div>
            <div class="value"><?= number_format($total, 0, ',', '.') ?></div>
        </div>
        <div class="summary-card">
            <div class="label">Laki-laki</div>
            <div class="value"><?= number_format($total_l, 0, ',', '.') ?></div>
        </div>
        <div class="summary-card">
            <div class="label">Perempuan</div>
            <div class="value"><?= number_format($total_p, 0, ',', '.') ?></div>
        </div>
        <div class="summary-card">
            <div class="label">Mahasiswa Aktif</div>
            <div class="value"><?= number_format($total_aktif, 0, ',', '.') ?></div>
        </div>
    </div>

    <div class="report-panel">
        <div class="report-header">
            <h2>Daftar Mahasiswa</h2>
            <p><?= e($judul_scope) ?> · <?= date('d-m-Y H:i') ?></p>
            <div class="print-only" style="margin-top:8px;">
                <strong>Rekap Laporan Mahasiswa</strong><br>
                Dicetak: <?= date('d-m-Y H:i') ?>
            </div>
        </div>

        <div class="report-table-wrap">
            <table class="report-table">
                <thead>
                    <tr>
                        <th class="center">No</th>
                        <th>NPM</th>
                        <th>Nama Mahasiswa</th>
                        <th>Program Studi</th>
                        <th>Fakultas</th>
                        <th class="center">JK</th>
                        <th>Tanggal Masuk</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!$rows): ?>
                    <tr>
                        <td colspan="8" class="center">Tidak ada data mahasiswa sesuai filter.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rows as $index => $row): ?>
                        <tr>
                            <td class="center"><?= $index + 1 ?></td>
                            <td><?= e($row['npm']) ?></td>
                            <td><?= e($row['nama_mahasiswa']) ?></td>
                            <td><?= e($row['kode_program_studi'] . ' - ' . $row['nama_program_studi']) ?></td>
                            <td><?= e($row['kode_fakultas'] . ' - ' . $row['nama_fakultas']) ?></td>
                            <td class="center"><?= e($row['jenis_kelamin']) ?></td>
                            <td><?= e($row['tanggal_masuk']) ?></td>
                            <td><span class="status-badge"><?= e($row['status_mahasiswa']) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="report-footer">
            Menampilkan <strong><?= number_format($total, 0, ',', '.') ?></strong> data mahasiswa.
            <?php if ($total_lulus > 0): ?>
                Jumlah lulusan pada hasil filter: <strong><?= number_format($total_lulus, 0, ',', '.') ?></strong>.
            <?php endif; ?>
        </div>
    </div>

</main>
</body>
</html>
