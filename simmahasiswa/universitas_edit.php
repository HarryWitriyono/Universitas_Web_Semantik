<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/koneksi.php';

wajib_role(['ADMIN']);

$id_universitas = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id_universitas) {
    $_SESSION['error'] = 'ID universitas tidak valid.';
    header('Location: universitas.php');
    exit;
}

/*
 * Gunakan $data_universitas agar tidak bentrok dengan variabel
 * $role yang digunakan oleh sideleftbar.php.
 */
$stmt = mysqli_prepare(
    $koneksi,
    "SELECT
        id_universitas,
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
     FROM universitas
     WHERE id_universitas = ?
     LIMIT 1"
);

if (!$stmt) {
    die(
        'Gagal menyiapkan query: ' .
        htmlspecialchars(mysqli_error($koneksi), ENT_QUOTES, 'UTF-8')
    );
}

mysqli_stmt_bind_param($stmt, 'i', $id_universitas);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$data_universitas = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$data_universitas) {
    $_SESSION['error'] = 'Data universitas tidak ditemukan.';
    header('Location: universitas.php');
    exit;
}

$error = '';

/* =========================
   PROSES UPDATE
   ========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    csrf_verify();

    $nama_universitas = trim($_POST['nama_universitas'] ?? '');
    $slogan            = trim($_POST['slogan'] ?? '');
    $alamat            = trim($_POST['alamat'] ?? '');
    $kota              = trim($_POST['kota'] ?? '');
    $provinsi          = trim($_POST['provinsi'] ?? '');
    $kode_pos          = trim($_POST['kode_pos'] ?? '');
    $website            = trim($_POST['website'] ?? '');
    $email              = trim($_POST['email'] ?? '');
    $telepon           = trim($_POST['telepon'] ?? '');

    if ($nama_universitas === '') {
        $error = 'Nama universitas wajib diisi.';
    } elseif (mb_strlen($nama_universitas) > 200) {
        $error = 'Nama universitas maksimal 200 karakter.';
    } elseif (mb_strlen($slogan) > 255) {
        $error = 'Slogan maksimal 255 karakter.';
    } elseif (mb_strlen($kota) > 100) {
        $error = 'Kota maksimal 100 karakter.';
    } elseif (mb_strlen($provinsi) > 100) {
        $error = 'Provinsi maksimal 100 karakter.';
    } elseif (mb_strlen($kode_pos) > 10) {
        $error = 'Kode pos maksimal 10 karakter.';
    } elseif (mb_strlen($website) > 255) {
        $error = 'Website maksimal 255 karakter.';
    } elseif (mb_strlen($email) > 150) {
        $error = 'Email maksimal 150 karakter.';
    } elseif (mb_strlen($telepon) > 50) {
        $error = 'Telepon maksimal 50 karakter.';
    } elseif ($website !== '' && !filter_var($website, FILTER_VALIDATE_URL)) {
        $error = 'Format website tidak valid.';
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    }

    if ($error === '') {

        /*
         * Ada 10 parameter:
         * 1  nama_universitas
         * 2  slogan
         * 3  alamat
         * 4  kota
         * 5  provinsi
         * 6  kode_pos
         * 7  website
         * 8  email
         * 9  telepon
         * 10 id_universitas
         *
         * Jadi type string harus 9 buah + integer:
         * sssssssss i
         *
         * Kesalahan sebelumnya hanya memiliki 8 string + integer,
         * sehingga mysqli_stmt_bind_param() tidak cocok dengan
         * jumlah placeholder SQL.
         */
        $sql = "UPDATE universitas
                SET nama_universitas = ?,
                    slogan = NULLIF(?, ''),
                    alamat = NULLIF(?, ''),
                    kota = NULLIF(?, ''),
                    provinsi = NULLIF(?, ''),
                    kode_pos = NULLIF(?, ''),
                    website = NULLIF(?, ''),
                    email = NULLIF(?, ''),
                    telepon = NULLIF(?, '')
                WHERE id_universitas = ?";

        $stmt = mysqli_prepare($koneksi, $sql);

        if (!$stmt) {
            $error =
                'Gagal menyiapkan proses update: ' .
                mysqli_error($koneksi);
        } else {

            /*
             * 9 parameter string + 1 integer.
             */
            mysqli_stmt_bind_param(
                $stmt,
                'sssssssssi',
                $nama_universitas,
                $slogan,
                $alamat,
                $kota,
                $provinsi,
                $kode_pos,
                $website,
                $email,
                $telepon,
                $id_universitas
            );

            if (mysqli_stmt_execute($stmt)) {

                mysqli_stmt_close($stmt);

                $_SESSION['success'] =
                    'Data universitas berhasil diperbarui.';

                header('Location: universitas.php');
                exit;
            }

            $error =
                'Gagal memperbarui universitas: ' .
                mysqli_stmt_error($stmt);

            mysqli_stmt_close($stmt);
        }
    }

    /*
     * Jika validasi gagal, tampilkan kembali data yang
     * baru dimasukkan pengguna.
     */
    $data_universitas['nama_universitas'] = $nama_universitas;
    $data_universitas['slogan']            = $slogan;
    $data_universitas['alamat']            = $alamat;
    $data_universitas['kota']              = $kota;
    $data_universitas['provinsi']          = $provinsi;
    $data_universitas['kode_pos']          = $kode_pos;
    $data_universitas['website']           = $website;
    $data_universitas['email']             = $email;
    $data_universitas['telepon']           = $telepon;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit Universitas - SIM Mahasiswa</title>

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
            display: block !important;
            width: 100% !important;
            box-sizing: border-box !important;
            padding: 25px !important;
            margin: 0 !important;
        }

        .master-form-grid {
            display: grid !important;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
            width: 100%;
        }

        .master-form-group {
            display: block !important;
            min-width: 0;
            width: 100%;
        }

        .master-form-full {
            grid-column: 1 / -1;
        }

        .master-form-group label {
            display: block !important;
            margin: 0 0 8px !important;
            padding: 0 !important;
            font-size: 14px !important;
            font-weight: 700 !important;
            color: #1f2937 !important;
        }

        .master-form-control {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            width: 100% !important;
            box-sizing: border-box !important;
            min-height: 44px !important;
            padding: 11px 13px !important;
            border: 1px solid #cbd5e1 !important;
            border-radius: 8px !important;
            background: #fff !important;
            color: #1f2937 !important;
            font-family: inherit !important;
            font-size: 14px !important;
            line-height: 1.4 !important;
        }

        .master-form-control:focus {
            outline: 2px solid #bbf7d0 !important;
            border-color: #166534 !important;
        }

        .master-form-readonly {
            background: #f3f4f6 !important;
            color: #4b5563 !important;
            cursor: not-allowed;
        }

        textarea.master-form-control {
            min-height: 110px !important;
            resize: vertical;
        }

        .master-form-help {
            display: block !important;
            margin-top: 6px;
            color: #6b7280;
            font-size: 12px;
            line-height: 1.5;
        }

        .master-form-footer {
            display: flex !important;
            justify-content: flex-end;
            align-items: center;
            gap: 10px;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }

        .master-form-footer .btn {
            min-width: 120px;
        }

        @media (max-width: 700px) {
            .master-form-grid {
                grid-template-columns: 1fr;
            }

            .master-form-full {
                grid-column: auto;
            }

            .master-form {
                padding: 18px !important;
            }

            .master-form-header {
                padding: 18px;
            }

            .master-form-footer {
                flex-direction: column-reverse;
                align-items: stretch;
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

            <h1>Edit Universitas</h1>

            <p>
                Perbarui informasi universitas.
            </p>
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

            <div class="master-form-icon">
                🏫
            </div>

            <div>
                <h2>Informasi Universitas</h2>

                <p>
                    Field bertanda <strong>*</strong> wajib diisi.
                </p>
            </div>

        </div>


        <form
            method="post"
            class="master-form"
            action="universitas_edit.php?id=<?= (int)$id_universitas ?>"
            autocomplete="off"
        >

            <?= csrf_field() ?>

            <div class="master-form-grid">

                <!-- KODE UNIVERSITAS -->
                <div class="master-form-group">

                    <label for="kode_universitas">
                        Kode Universitas
                    </label>

                    <input
                        class="master-form-control master-form-readonly"
                        type="text"
                        id="kode_universitas"
                        value="<?= htmlspecialchars(
                            $data_universitas['kode_universitas'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        readonly
                    >

                    <small class="master-form-help">
                        Kode universitas digunakan sebagai identitas sistem
                        dan tidak dapat diubah.
                    </small>

                </div>


                <!-- NAMA UNIVERSITAS -->
                <div class="master-form-group">

                    <label for="nama_universitas">
                        Nama Universitas
                        <span style="color:#dc2626">*</span>
                    </label>

                    <input
                        class="master-form-control"
                        type="text"
                        id="nama_universitas"
                        name="nama_universitas"
                        maxlength="200"
                        required
                        value="<?= htmlspecialchars(
                            $data_universitas['nama_universitas'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                    >

                </div>


                <!-- SLOGAN -->
                <div class="master-form-group master-form-full">

                    <label for="slogan">
                        Slogan
                    </label>

                    <input
                        class="master-form-control"
                        type="text"
                        id="slogan"
                        name="slogan"
                        maxlength="255"
                        value="<?= htmlspecialchars(
                            $data_universitas['slogan'] ?? '',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                    >

                </div>


                <!-- ALAMAT -->
                <div class="master-form-group master-form-full">

                    <label for="alamat">
                        Alamat
                    </label>

                    <textarea
                        class="master-form-control"
                        id="alamat"
                        name="alamat"
                        rows="3"
                    ><?= htmlspecialchars(
                        $data_universitas['alamat'] ?? '',
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?></textarea>

                </div>


                <!-- KOTA -->
                <div class="master-form-group">

                    <label for="kota">
                        Kota
                    </label>

                    <input
                        class="master-form-control"
                        type="text"
                        id="kota"
                        name="kota"
                        maxlength="100"
                        value="<?= htmlspecialchars(
                            $data_universitas['kota'] ?? '',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                    >

                </div>


                <!-- PROVINSI -->
                <div class="master-form-group">

                    <label for="provinsi">
                        Provinsi
                    </label>

                    <input
                        class="master-form-control"
                        type="text"
                        id="provinsi"
                        name="provinsi"
                        maxlength="100"
                        value="<?= htmlspecialchars(
                            $data_universitas['provinsi'] ?? '',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                    >

                </div>


                <!-- KODE POS -->
                <div class="master-form-group">

                    <label for="kode_pos">
                        Kode Pos
                    </label>

                    <input
                        class="master-form-control"
                        type="text"
                        id="kode_pos"
                        name="kode_pos"
                        maxlength="10"
                        value="<?= htmlspecialchars(
                            $data_universitas['kode_pos'] ?? '',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                    >

                </div>


                <!-- TELEPON -->
                <div class="master-form-group">

                    <label for="telepon">
                        Telepon
                    </label>

                    <input
                        class="master-form-control"
                        type="text"
                        id="telepon"
                        name="telepon"
                        maxlength="50"
                        value="<?= htmlspecialchars(
                            $data_universitas['telepon'] ?? '',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                    >

                </div>


                <!-- WEBSITE -->
                <div class="master-form-group">

                    <label for="website">
                        Website
                    </label>

                    <input
                        class="master-form-control"
                        type="url"
                        id="website"
                        name="website"
                        maxlength="255"
                        value="<?= htmlspecialchars(
                            $data_universitas['website'] ?? '',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        placeholder="https://..."
                    >

                </div>


                <!-- EMAIL -->
                <div class="master-form-group">

                    <label for="email">
                        Email
                    </label>

                    <input
                        class="master-form-control"
                        type="email"
                        id="email"
                        name="email"
                        maxlength="150"
                        value="<?= htmlspecialchars(
                            $data_universitas['email'] ?? '',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                    >

                </div>

            </div>


            <div class="master-form-footer">

                <a
                    href="universitas.php"
                    class="btn btn-outline"
                >
                    Batal
                </a>

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Simpan Perubahan
                </button>

            </div>

        </form>

    </div>

</main>

</body>
</html>
