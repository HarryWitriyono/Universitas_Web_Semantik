<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/koneksi.php';

wajib_role(['ADMIN']);

$error = '';

$universitas = mysqli_query(
    $koneksi,
    "SELECT id_universitas, kode_universitas, nama_universitas
     FROM universitas
     ORDER BY nama_universitas ASC"
);

if (!$universitas) {
    die('Gagal mengambil data universitas.');
}

$id_universitas = '';
$kode_fakultas = '';
$nama_fakultas = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $id_universitas = filter_input(INPUT_POST, 'id_universitas', FILTER_VALIDATE_INT);
    $kode_fakultas = strtoupper(trim($_POST['kode_fakultas'] ?? ''));
    $nama_fakultas = trim($_POST['nama_fakultas'] ?? '');

    if (!$id_universitas) {
        $error = 'Universitas wajib dipilih.';
    } elseif ($kode_fakultas === '') {
        $error = 'Kode fakultas wajib diisi.';
    } elseif (!preg_match('/^[A-Z0-9_-]+$/', $kode_fakultas)) {
        $error = 'Kode fakultas hanya boleh berisi huruf kapital, angka, underscore, dan tanda minus.';
    } elseif (mb_strlen($kode_fakultas) > 20) {
        $error = 'Kode fakultas maksimal 20 karakter.';
    } elseif ($nama_fakultas === '') {
        $error = 'Nama fakultas wajib diisi.';
    } elseif (mb_strlen($nama_fakultas) > 200) {
        $error = 'Nama fakultas maksimal 200 karakter.';
    }

    if ($error === '') {
        $stmt = mysqli_prepare(
            $koneksi,
            "SELECT id_universitas FROM universitas WHERE id_universitas = ? LIMIT 1"
        );
        mysqli_stmt_bind_param($stmt, 'i', $id_universitas);
        mysqli_stmt_execute($stmt);
        $data_univ = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        if (!$data_univ) {
            $error = 'Universitas yang dipilih tidak ditemukan.';
        }
    }

    if ($error === '') {
        $stmt = mysqli_prepare(
            $koneksi,
            "SELECT id_fakultas
             FROM fakultas
             WHERE id_universitas = ? AND kode_fakultas = ?
             LIMIT 1"
        );
        mysqli_stmt_bind_param($stmt, 'is', $id_universitas, $kode_fakultas);
        mysqli_stmt_execute($stmt);
        $sudah_ada = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        if ($sudah_ada) {
            $error = 'Kode fakultas tersebut sudah digunakan pada universitas yang dipilih.';
        }
    }

    if ($error === '') {
        $stmt = mysqli_prepare(
            $koneksi,
            "INSERT INTO fakultas (id_universitas, kode_fakultas, nama_fakultas)
             VALUES (?, ?, ?)"
        );

        if (!$stmt) {
            $error = 'Gagal menyiapkan proses penyimpanan.';
        } else {
            mysqli_stmt_bind_param(
                $stmt,
                'iss',
                $id_universitas,
                $kode_fakultas,
                $nama_fakultas
            );

            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                $_SESSION['success'] = 'Data fakultas berhasil ditambahkan.';
                header('Location: fakultas.php');
                exit;
            }

            $error = 'Gagal menambahkan fakultas: ' . mysqli_stmt_error($stmt);
            mysqli_stmt_close($stmt);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Fakultas - SIM Mahasiswa</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .master-form-card{width:100%;max-width:760px;background:#fff;border:1px solid #e5e7eb;border-radius:14px;overflow:hidden;box-shadow:0 4px 18px rgba(0,0,0,.05)}
        .master-form-header{padding:22px 25px;background:#fbfdfb;border-bottom:1px solid #e5e7eb}
        .master-form-header h2{margin:0 0 4px;font-size:20px}.master-form-header p{margin:0;color:#6b7280;font-size:13px}
        .master-form{padding:25px}.master-form-group{margin-bottom:20px}.master-form-group label{display:block;margin-bottom:8px;font-size:14px;font-weight:700;color:#1f2937}
        .master-form-group input,.master-form-group select{width:100%;box-sizing:border-box;padding:11px 13px;border:1px solid #d1d5db;border-radius:8px;font:inherit;background:#fff}
        .master-form-group input:focus,.master-form-group select:focus{outline:none;border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,.1)}
        .form-help{margin-top:6px;font-size:12px;color:#6b7280}.form-actions{display:flex;gap:10px;align-items:center;padding-top:5px}
    </style>
</head>
<body class="app-body">
<?php include __DIR__ . '/topbar.php'; ?>
<?php include __DIR__ . '/sideleftbar.php'; ?>
<main class="main-content">
    <div class="page-heading">
        <span class="eyebrow">MASTER DATA</span>
        <h1>Tambah Fakultas</h1>
        <p>Tambahkan fakultas dan hubungkan dengan universitas.</p>
    </div>

    <?php if ($error !== ''): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="master-form-card">
        <div class="master-form-header">
            <h2>Form Fakultas</h2>
            <p>Semua field wajib diisi.</p>
        </div>
        <form method="post" class="master-form">
            <?= csrf_field() ?>

            <div class="master-form-group">
                <label for="id_universitas">Universitas *</label>
                <select name="id_universitas" id="id_universitas" required>
                    <option value="">-- Pilih Universitas --</option>
                    <?php while ($data_universitas = mysqli_fetch_assoc($universitas)): ?>
                        <option
                            value="<?= (int)$data_universitas['id_universitas'] ?>"
                            <?= ((string)$id_universitas === (string)$data_universitas['id_universitas']) ? 'selected' : '' ?>
                        >
                            <?= htmlspecialchars($data_universitas['kode_universitas'] . ' - ' . $data_universitas['nama_universitas'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="master-form-group">
                <label for="kode_fakultas">Kode Fakultas *</label>
                <input type="text" name="kode_fakultas" id="kode_fakultas" maxlength="20" value="<?= htmlspecialchars($kode_fakultas, ENT_QUOTES, 'UTF-8') ?>" required>
                <div class="form-help">Contoh: FT, FEB, FIKES.</div>
            </div>

            <div class="master-form-group">
                <label for="nama_fakultas">Nama Fakultas *</label>
                <input type="text" name="nama_fakultas" id="nama_fakultas" maxlength="200" value="<?= htmlspecialchars($nama_fakultas, ENT_QUOTES, 'UTF-8') ?>" required>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Simpan</button>
                <a href="fakultas.php" class="btn btn-outline">Batal</a>
            </div>
        </form>
    </div>
</main>
</body>
</html>
