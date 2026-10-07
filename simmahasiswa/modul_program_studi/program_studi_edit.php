<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/csrf.php';

wajib_role(['ADMIN']);

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
if ($id <= 0) {
    header('Location: program_studi.php?pesan=gagal&detail=' . urlencode('ID Program Studi tidak valid.'));
    exit;
}

$judul_halaman = 'Edit Program Studi';
$error = '';

$stmt = mysqli_prepare(
    $koneksi,
    "SELECT id_program_studi, id_fakultas, kode_program_studi, nama_program_studi, jenjang, status_aktif
     FROM program_studi
     WHERE id_program_studi = ?
     LIMIT 1"
);
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$result_data = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result_data);
mysqli_stmt_close($stmt);

if (!$data) {
    header('Location: program_studi.php?pesan=gagal&detail=' . urlencode('Data Program Studi tidak ditemukan.'));
    exit;
}

$id_fakultas = (int) $data['id_fakultas'];
$kode = $data['kode_program_studi'];
$nama = $data['nama_program_studi'];
$jenjang = $data['jenjang'];
$status = $data['status_aktif'];

$fakultas = mysqli_query(
    $koneksi,
    "SELECT f.id_fakultas, f.kode_fakultas, f.nama_fakultas,
            u.kode_universitas, u.nama_universitas
     FROM fakultas f
     INNER JOIN universitas u ON u.id_universitas = f.id_universitas
     ORDER BY u.nama_universitas, f.nama_fakultas"
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        csrf_verify();
    } catch (Throwable $e) {
        $error = 'Permintaan tidak valid. Silakan coba lagi.';
    }

    $id_fakultas = (int) ($_POST['id_fakultas'] ?? 0);
    $kode = strtoupper(trim($_POST['kode_program_studi'] ?? ''));
    $nama = trim($_POST['nama_program_studi'] ?? '');
    $jenjang = trim($_POST['jenjang'] ?? '');
    $status = trim($_POST['status_aktif'] ?? '');

    if ($error === '' && ($kode === '' || $nama === '' || $jenjang === '' || $id_fakultas <= 0)) {
        $error = 'Kode, nama program studi, jenjang, dan fakultas wajib diisi.';
    }

    $jenjang_valid = ['D3', 'D4', 'S1', 'S2', 'S3', 'Profesi', 'Sp-1', 'Sp-2'];
    $status_valid = ['Aktif', 'Tidak Aktif'];

    if ($error === '' && !in_array($jenjang, $jenjang_valid, true)) {
        $error = 'Jenjang tidak valid.';
    }

    if ($error === '' && !in_array($status, $status_valid, true)) {
        $error = 'Status tidak valid.';
    }

    if ($error === '') {
        $cek = mysqli_prepare(
            $koneksi,
            "SELECT id_program_studi
             FROM program_studi
             WHERE id_fakultas = ?
               AND kode_program_studi = ?
               AND id_program_studi <> ?
             LIMIT 1"
        );
        mysqli_stmt_bind_param($cek, 'isi', $id_fakultas, $kode, $id);
        mysqli_stmt_execute($cek);
        $cek_result = mysqli_stmt_get_result($cek);
        $sudah_ada = mysqli_num_rows($cek_result) > 0;
        mysqli_stmt_close($cek);

        if ($sudah_ada) {
            $error = 'Kode Program Studi tersebut sudah digunakan pada fakultas yang dipilih.';
        }
    }

    if ($error === '') {
        $stmt = mysqli_prepare(
            $koneksi,
            "UPDATE program_studi
             SET id_fakultas = ?,
                 kode_program_studi = ?,
                 nama_program_studi = ?,
                 jenjang = ?,
                 status_aktif = ?
             WHERE id_program_studi = ?"
        );

        if (!$stmt) {
            $error = 'Gagal menyiapkan pembaruan data.';
        } else {
            mysqli_stmt_bind_param($stmt, 'issssi', $id_fakultas, $kode, $nama, $jenjang, $status, $id);

            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                header('Location: program_studi.php?pesan=edit');
                exit;
            }

            $error = 'Gagal memperbarui data Program Studi.';
            mysqli_stmt_close($stmt);
        }
    }
}

include __DIR__ . '/topbar.php';
include __DIR__ . '/sideleftbar.php';
?>

<div class="container-fluid py-3">
    <div class="mb-3">
        <h4 class="mb-1">Edit Program Studi</h4>
        <p class="text-muted mb-0">Perbarui data program studi.</p>
    </div>

    <?php if ($error !== ''): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="id" value="<?= $id ?>">

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Fakultas <span class="text-danger">*</span></label>
                        <select name="id_fakultas" class="form-select" required>
                            <option value="">-- Pilih Fakultas --</option>
                            <?php while ($f = mysqli_fetch_assoc($fakultas)): ?>
                                <option value="<?= (int) $f['id_fakultas'] ?>" <?= $id_fakultas === (int) $f['id_fakultas'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($f['kode_universitas'] . ' - ' . $f['nama_universitas'] . ' | ' . $f['kode_fakultas'] . ' - ' . $f['nama_fakultas'], ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Kode Program Studi <span class="text-danger">*</span></label>
                        <input type="text" name="kode_program_studi" class="form-control" maxlength="30" value="<?= htmlspecialchars($kode, ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Jenjang <span class="text-danger">*</span></label>
                        <select name="jenjang" class="form-select" required>
                            <?php foreach (['D3','D4','S1','S2','S3','Profesi','Sp-1','Sp-2'] as $item): ?>
                                <option value="<?= $item ?>" <?= $jenjang === $item ? 'selected' : '' ?>><?= $item ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-8">
                        <label class="form-label">Nama Program Studi <span class="text-danger">*</span></label>
                        <input type="text" name="nama_program_studi" class="form-control" maxlength="200" value="<?= htmlspecialchars($nama, ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Status</label>
                        <select name="status_aktif" class="form-select">
                            <option value="Aktif" <?= $status === 'Aktif' ? 'selected' : '' ?>>Aktif</option>
                            <option value="Tidak Aktif" <?= $status === 'Tidak Aktif' ? 'selected' : '' ?>>Tidak Aktif</option>
                        </select>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    <a href="program_studi.php" class="btn btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>
