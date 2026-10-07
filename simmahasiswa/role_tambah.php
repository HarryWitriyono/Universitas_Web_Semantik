<?php
require_once 'auth_check.php';
require_once 'koneksi.php';

wajib_role(['ADMIN']);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $kode_role = strtoupper(trim($_POST['kode_role'] ?? ''));
    $nama_role = trim($_POST['nama_role'] ?? '');
    $keterangan = trim($_POST['keterangan'] ?? '');

    if ($kode_role === '' || $nama_role === '') {
        $error = 'Kode role dan nama role wajib diisi.';
    } elseif (!preg_match('/^[A-Z0-9_]+$/', $kode_role)) {
        $error = 'Kode role hanya boleh berisi huruf kapital, angka, dan underscore.';
    } else {
        $stmt = mysqli_prepare(
            $koneksi,
            "SELECT id_role FROM roles WHERE kode_role = ? LIMIT 1"
        );
        mysqli_stmt_bind_param($stmt, "s", $kode_role);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $exists = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if ($exists) {
            $error = 'Kode role sudah digunakan.';
        }
    }

    if ($error === '') {
        $stmt = mysqli_prepare(
            $koneksi,
            "INSERT INTO roles (kode_role, nama_role, keterangan)
             VALUES (?, ?, NULLIF(?, ''))"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "sss",
            $kode_role,
            $nama_role,
            $keterangan
        );

        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success'] = 'Role berhasil ditambahkan.';
            mysqli_stmt_close($stmt);
            header("Location: role.php");
            exit;
        }

        $error = 'Gagal menambahkan role: ' . mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Role - SIM Mahasiswa</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="app-body">

<?php include 'topbar.php'; ?>
<?php include 'sideleftbar.php'; ?>

<main class="main-content">
    <div class="page-heading">
        <span class="eyebrow">MASTER DATA</span>
        <h1>Tambah Role</h1>
        <p>Tambahkan role baru ke dalam sistem.</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="dashboard-panel form-panel">
        <form method="post">
            <?= csrf_field() ?>

            <div class="form-group">
                <label for="kode_role">Kode Role</label>
                <input
                    type="text"
                    id="kode_role"
                    name="kode_role"
                    maxlength="30"
                    required
                    value="<?= htmlspecialchars($_POST['kode_role'] ?? '') ?>"
                    placeholder="Contoh: OPERATOR_PRODI"
                >
                <small class="form-help">Gunakan huruf kapital, angka, dan underscore.</small>
            </div>

            <div class="form-group">
                <label for="nama_role">Nama Role</label>
                <input
                    type="text"
                    id="nama_role"
                    name="nama_role"
                    maxlength="100"
                    required
                    value="<?= htmlspecialchars($_POST['nama_role'] ?? '') ?>"
                    placeholder="Contoh: Operator Program Studi"
                >
            </div>

            <div class="form-group">
                <label for="keterangan">Keterangan</label>
                <textarea
                    id="keterangan"
                    name="keterangan"
                    rows="4"
                    maxlength="255"
                    placeholder="Keterangan role"
                ><?= htmlspecialchars($_POST['keterangan'] ?? '') ?></textarea>
            </div>

            <div class="form-actions">
                <a href="role.php" class="btn btn-outline">Batal</a>
                <button type="submit" class="btn btn-primary">Simpan Role</button>
            </div>
        </form>
    </div>
</main>
</body>
</html>
