<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/csrf.php';

wajib_role(['ADMIN']);

$daftar_fakultas = mysqli_query(
    $koneksi,
    "SELECT
        f.id_fakultas,
        f.kode_fakultas,
        f.nama_fakultas,
        u.kode_universitas,
        u.nama_universitas
     FROM fakultas f
     INNER JOIN universitas u ON u.id_universitas = f.id_universitas
     ORDER BY u.nama_universitas, f.nama_fakultas"
);

if (!$daftar_fakultas) {
    die('Gagal mengambil data fakultas.');
}

$kode_program_studi = '';
$nama_program_studi = '';
$jenjang = 'S1';
$status_aktif = 'Aktif';
$id_fakultas = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        csrf_verify();

        $id_fakultas = (int)($_POST['id_fakultas'] ?? 0);
        $kode_program_studi = trim($_POST['kode_program_studi'] ?? '');
        $nama_program_studi = trim($_POST['nama_program_studi'] ?? '');
        $jenjang = trim($_POST['jenjang'] ?? '');
        $status_aktif = $_POST['status_aktif'] ?? 'Aktif';

        if ($id_fakultas <= 0 || $kode_program_studi === '' || $nama_program_studi === '' || $jenjang === '') {
            throw new RuntimeException('Fakultas, kode, nama program studi, dan jenjang wajib diisi.');
        }

        if (!in_array($status_aktif, ['Aktif', 'Tidak Aktif'], true)) {
            throw new RuntimeException('Status program studi tidak valid.');
        }

        $cek_fakultas = mysqli_prepare(
            $koneksi,
            "SELECT id_fakultas FROM fakultas WHERE id_fakultas = ? LIMIT 1"
        );
        mysqli_stmt_bind_param($cek_fakultas, 'i', $id_fakultas);
        mysqli_stmt_execute($cek_fakultas);
        $fakultas_ada = mysqli_stmt_get_result($cek_fakultas);
        $valid_fakultas = mysqli_num_rows($fakultas_ada) > 0;
        mysqli_stmt_close($cek_fakultas);

        if (!$valid_fakultas) {
            throw new RuntimeException('Fakultas yang dipilih tidak ditemukan.');
        }

        $cek = mysqli_prepare(
            $koneksi,
            "SELECT id_program_studi
             FROM program_studi
             WHERE id_fakultas = ? AND kode_program_studi = ?
             LIMIT 1"
        );
        mysqli_stmt_bind_param($cek, 'is', $id_fakultas, $kode_program_studi);
        mysqli_stmt_execute($cek);
        $duplikat = mysqli_stmt_get_result($cek);
        $ada_duplikat = mysqli_num_rows($duplikat) > 0;
        mysqli_stmt_close($cek);

        if ($ada_duplikat) {
            throw new RuntimeException('Kode program studi sudah digunakan pada fakultas tersebut.');
        }

        $stmt = mysqli_prepare(
            $koneksi,
            "INSERT INTO program_studi
                (id_fakultas, kode_program_studi, nama_program_studi, jenjang, status_aktif)
             VALUES (?, ?, ?, ?, ?)"
        );
        mysqli_stmt_bind_param(
            $stmt,
            'issss',
            $id_fakultas,
            $kode_program_studi,
            $nama_program_studi,
            $jenjang,
            $status_aktif
        );

        if (!mysqli_stmt_execute($stmt)) {
            throw new RuntimeException('Data program studi gagal disimpan.');
        }

        $id_baru = mysqli_insert_id($koneksi);
        mysqli_stmt_close($stmt);

        $data_baru = json_encode([
            'id_fakultas' => $id_fakultas,
            'kode_program_studi' => $kode_program_studi,
            'nama_program_studi' => $nama_program_studi,
            'jenjang' => $jenjang,
            'status_aktif' => $status_aktif
        ], JSON_UNESCAPED_UNICODE);

        $audit = mysqli_prepare(
            $koneksi,
            "INSERT INTO audit_log
                (id_pengguna, tabel_nama, record_id, aksi, data_baru, ip_address, user_agent)
             VALUES (?, 'program_studi', ?, 'INSERT', ?, ?, ?)"
        );

        if ($audit) {
            $id_pengguna = (int)($_SESSION['id_pengguna'] ?? 0);
            $record_id = (string)$id_baru;
            $ip = $_SERVER['REMOTE_ADDR'] ?? null;
            $agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            mysqli_stmt_bind_param($audit, 'issss', $id_pengguna, $record_id, $data_baru, $ip, $agent);
            mysqli_stmt_execute($audit);
            mysqli_stmt_close($audit);
        }

        $_SESSION['success'] = 'Program Studi berhasil ditambahkan.';
        header('Location: program_studi.php');
        exit;
    } catch (Throwable $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Program Studi - SIM Mahasiswa</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="app-body">

<?php include __DIR__ . '/topbar.php'; ?>
<?php include __DIR__ . '/sideleftbar.php'; ?>

<main class="main-content">

    <div class="page-heading">
        <span class="eyebrow">MASTER DATA</span>
        <h1>Tambah Program Studi</h1>
        <p>Tambahkan program studi baru ke dalam fakultas.</p>
    </div>

    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8') ?>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <div class="dashboard-panel">
        <form method="post" action="program_studi_tambah.php">

            <?= csrf_field() ?>

            <div class="form-group">
                <label for="id_fakultas">Fakultas</label>
                <select name="id_fakultas" id="id_fakultas" class="form-control" required>
                    <option value="">-- Pilih Fakultas --</option>
                    <?php while ($f = mysqli_fetch_assoc($daftar_fakultas)): ?>
                        <option
                            value="<?= (int)$f['id_fakultas'] ?>"
                            <?= (string)$id_fakultas === (string)$f['id_fakultas'] ? 'selected' : '' ?>
                        >
                            <?= htmlspecialchars(
                                $f['kode_universitas'] . ' - ' .
                                $f['nama_universitas'] . ' | ' .
                                $f['kode_fakultas'] . ' - ' .
                                $f['nama_fakultas'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="kode_program_studi">Kode Program Studi</label>
                <input
                    type="text"
                    name="kode_program_studi"
                    id="kode_program_studi"
                    class="form-control"
                    maxlength="20"
                    value="<?= htmlspecialchars($kode_program_studi, ENT_QUOTES, 'UTF-8') ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="nama_program_studi">Nama Program Studi</label>
                <input
                    type="text"
                    name="nama_program_studi"
                    id="nama_program_studi"
                    class="form-control"
                    maxlength="200"
                    value="<?= htmlspecialchars($nama_program_studi, ENT_QUOTES, 'UTF-8') ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="jenjang">Jenjang</label>
                <input
                    type="text"
                    name="jenjang"
                    id="jenjang"
                    class="form-control"
                    maxlength="20"
                    value="<?= htmlspecialchars($jenjang, ENT_QUOTES, 'UTF-8') ?>"
                    placeholder="Contoh: S1, S2, S3, D3, D4"
                    required
                >
            </div>

            <div class="form-group">
                <label for="status_aktif">Status</label>
                <select name="status_aktif" id="status_aktif" class="form-control">
                    <option value="Aktif" <?= $status_aktif === 'Aktif' ? 'selected' : '' ?>>Aktif</option>
                    <option value="Tidak Aktif" <?= $status_aktif === 'Tidak Aktif' ? 'selected' : '' ?>>Tidak Aktif</option>
                </select>
            </div>

            <div class="form-actions">
                <a href="program_studi.php" class="btn btn-outline">Batal</a>
                <button type="submit" class="btn btn-primary">Simpan Program Studi</button>
            </div>

        </form>
    </div>

</main>
</body>
</html>
