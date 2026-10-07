<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/koneksi.php';

wajib_role(['ADMIN']);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    csrf_verify();

    $kode_universitas = strtoupper(trim($_POST['kode_universitas'] ?? ''));
    $nama_universitas = trim($_POST['nama_universitas'] ?? '');
    $slogan = trim($_POST['slogan'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');
    $kota = trim($_POST['kota'] ?? '');
    $provinsi = trim($_POST['provinsi'] ?? '');
    $kode_pos = trim($_POST['kode_pos'] ?? '');
    $website = trim($_POST['website'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telepon = trim($_POST['telepon'] ?? '');

    if ($kode_universitas === '') {
        $error = 'Kode universitas wajib diisi.';
    } elseif (!preg_match('/^[A-Z0-9_-]+$/', $kode_universitas)) {
        $error = 'Kode universitas hanya boleh berisi huruf kapital, angka, underscore, dan tanda minus.';
    } elseif ($nama_universitas === '') {
        $error = 'Nama universitas wajib diisi.';
    } elseif ($website !== '' && !filter_var($website, FILTER_VALIDATE_URL)) {
        $error = 'Format website tidak valid.';
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    }

    if ($error === '') {

        $stmt = mysqli_prepare(
            $koneksi,
            "SELECT id_universitas
             FROM universitas
             WHERE kode_universitas = ?
             LIMIT 1"
        );

        mysqli_stmt_bind_param($stmt, 's', $kode_universitas);
        mysqli_stmt_execute($stmt);

        $hasil = mysqli_stmt_get_result($stmt);
        $sudah_ada = mysqli_fetch_assoc($hasil);

        mysqli_stmt_close($stmt);

        if ($sudah_ada) {
            $error = 'Kode universitas sudah digunakan.';
        }
    }

    if ($error === '') {

        $stmt = mysqli_prepare(
            $koneksi,
            "INSERT INTO universitas
            (
                kode_universitas,
                nama_universitas,
                slogan,
                alamat,
                kota,
                provinsi,
                kode_pos,
                website,
                email,
                telepon
            )
            VALUES (?, ?, NULLIF(?, ''), NULLIF(?, ''), NULLIF(?, ''),
                    NULLIF(?, ''), NULLIF(?, ''), NULLIF(?, ''),
                    NULLIF(?, ''), NULLIF(?, ''))"
        );

        if (!$stmt) {
            $error = 'Gagal menyiapkan proses penyimpanan.';
        } else {

            mysqli_stmt_bind_param(
                $stmt,
                'ssssssssss',
                $kode_universitas,
                $nama_universitas,
                $slogan,
                $alamat,
                $kota,
                $provinsi,
                $kode_pos,
                $website,
                $email,
                $telepon
            );

            if (mysqli_stmt_execute($stmt)) {

                mysqli_stmt_close($stmt);

                $_SESSION['success'] = 'Data universitas berhasil ditambahkan.';
                header('Location: universitas.php');
                exit;
            }

            $error = 'Gagal menambahkan universitas: ' . mysqli_stmt_error($stmt);
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
    <title>Tambah Universitas - SIM Mahasiswa</title>
    <link rel="stylesheet" href="style.css">

    <style>
        .master-form-card {
            width: 100%;
            max-width: 950px;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 4px 18px rgba(0,0,0,.05);
        }

        .master-form-header {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 22px 25px;
            background: #fbfdfb;
            border-bottom: 1px solid #e5e7eb;
        }

        .master-form-icon {
            width: 46px;
            height: 46px;
            min-width: 46px;
            border-radius: 10px;
            background: #ecfdf5;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 21px;
        }

        .master-form-header h2 {
            margin: 0 0 4px;
            font-size: 20px;
        }

        .master-form-header p {
            margin: 0;
            color: #6b7280;
            font-size: 13px;
        }

        .master-form {
            padding: 25px;
        }

        .master-form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
        }

        .master-form-group {
            min-width: 0;
        }

        .master-form-full {
            grid-column: 1 / -1;
        }

        .master-form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 700;
        }

        .master-form-control {
            width: 100%;
            min-height: 44px;
            box-sizing: border-box;
            padding: 11px 13px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            background: #fff;
            color: #1f2937;
            font: inherit;
        }

        .master-form-control:focus {
            outline: 2px solid #bbf7d0;
            border-color: #166534;
        }

        textarea.master-form-control {
            min-height: 110px;
            resize: vertical;
        }

        .master-form-help {
            display: block;
            margin-top: 6px;
            color: #6b7280;
            font-size: 12px;
        }

        .master-form-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }

        @media (max-width: 700px) {
            .master-form-grid {
                grid-template-columns: 1fr;
            }

            .master-form-full {
                grid-column: auto;
            }

            .master-form {
                padding: 18px;
            }

            .master-form-footer {
                flex-direction: column-reverse;
            }

            .master-form-footer .btn {
                width: 100%;
            }
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
            <h1>Tambah Universitas</h1>
            <p>Tambahkan data universitas ke dalam sistem.</p>
        </div>

        <a href="universitas.php" class="btn btn-outline">
            ← Kembali
        </a>
    </div>

    <?php if ($error !== ''): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <div class="master-form-card">

        <div class="master-form-header">
            <div class="master-form-icon">🏛️</div>
            <div>
                <h2>Informasi Universitas</h2>
                <p>Field bertanda <strong>*</strong> wajib diisi.</p>
            </div>
        </div>

        <form method="post" class="master-form" autocomplete="off">

            <?= csrf_field() ?>

            <div class="master-form-grid">

                <div class="master-form-group">
                    <label for="kode_universitas">
                        Kode Universitas <span style="color:#dc2626">*</span>
                    </label>

                    <input
                        class="master-form-control"
                        type="text"
                        id="kode_universitas"
                        name="kode_universitas"
                        maxlength="20"
                        required
                        value="<?= htmlspecialchars($_POST['kode_universitas'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        placeholder="Contoh: UMB"
                    >

                    <small class="master-form-help">
                        Maksimal 20 karakter.
                    </small>
                </div>

                <div class="master-form-group">
                    <label for="nama_universitas">
                        Nama Universitas <span style="color:#dc2626">*</span>
                    </label>

                    <input
                        class="master-form-control"
                        type="text"
                        id="nama_universitas"
                        name="nama_universitas"
                        maxlength="200"
                        required
                        value="<?= htmlspecialchars($_POST['nama_universitas'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        placeholder="Nama lengkap universitas"
                    >
                </div>

                <div class="master-form-group master-form-full">
                    <label for="slogan">Slogan</label>

                    <input
                        class="master-form-control"
                        type="text"
                        id="slogan"
                        name="slogan"
                        maxlength="255"
                        value="<?= htmlspecialchars($_POST['slogan'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    >
                </div>

                <div class="master-form-group master-form-full">
                    <label for="alamat">Alamat</label>

                    <textarea
                        class="master-form-control"
                        id="alamat"
                        name="alamat"
                        rows="3"
                    ><?= htmlspecialchars($_POST['alamat'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>

                <div class="master-form-group">
                    <label for="kota">Kota</label>

                    <input
                        class="master-form-control"
                        type="text"
                        id="kota"
                        name="kota"
                        maxlength="100"
                        value="<?= htmlspecialchars($_POST['kota'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    >
                </div>

                <div class="master-form-group">
                    <label for="provinsi">Provinsi</label>

                    <input
                        class="master-form-control"
                        type="text"
                        id="provinsi"
                        name="provinsi"
                        maxlength="100"
                        value="<?= htmlspecialchars($_POST['provinsi'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    >
                </div>

                <div class="master-form-group">
                    <label for="kode_pos">Kode Pos</label>

                    <input
                        class="master-form-control"
                        type="text"
                        id="kode_pos"
                        name="kode_pos"
                        maxlength="10"
                        value="<?= htmlspecialchars($_POST['kode_pos'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    >
                </div>

                <div class="master-form-group">
                    <label for="telepon">Telepon</label>

                    <input
                        class="master-form-control"
                        type="text"
                        id="telepon"
                        name="telepon"
                        maxlength="50"
                        value="<?= htmlspecialchars($_POST['telepon'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    >
                </div>

                <div class="master-form-group">
                    <label for="website">Website</label>

                    <input
                        class="master-form-control"
                        type="url"
                        id="website"
                        name="website"
                        maxlength="255"
                        value="<?= htmlspecialchars($_POST['website'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        placeholder="https://..."
                    >
                </div>

                <div class="master-form-group">
                    <label for="email">Email</label>

                    <input
                        class="master-form-control"
                        type="email"
                        id="email"
                        name="email"
                        maxlength="150"
                        value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    >
                </div>

            </div>

            <div class="master-form-footer">
                <a href="universitas.php" class="btn btn-outline">
                    Batal
                </a>

                <button type="submit" class="btn btn-primary">
                    Simpan Universitas
                </button>
            </div>

        </form>

    </div>

</main>
</body>
</html>
