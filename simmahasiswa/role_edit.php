<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/koneksi.php';

wajib_role(['ADMIN']);

$id_role = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id_role) {
    $_SESSION['error'] = 'ID role tidak valid.';
    header('Location: role.php');
    exit;
}

/*
 * PENTING:
 * Gunakan nama $data_role, bukan $role.
 *
 * sideleftbar.php juga menggunakan variabel $role untuk menyimpan
 * kode role session. Jika kita menggunakan $role di halaman ini,
 * include sideleftbar.php akan menimpanya dan menyebabkan:
 * "Cannot access offset of type string on string".
 */
$stmt = mysqli_prepare(
    $koneksi,
    "SELECT id_role, kode_role, nama_role, keterangan
     FROM roles
     WHERE id_role = ?
     LIMIT 1"
);

if (!$stmt) {
    die('Gagal menyiapkan query: ' . htmlspecialchars(mysqli_error($koneksi)));
}

mysqli_stmt_bind_param($stmt, 'i', $id_role);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$data_role = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$data_role) {
    $_SESSION['error'] = 'Data role tidak ditemukan.';
    header('Location: role.php');
    exit;
}

$error = '';

/* =========================
   PROSES UPDATE
   ========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    csrf_verify();

    $nama_role = trim($_POST['nama_role'] ?? '');
    $keterangan = trim($_POST['keterangan'] ?? '');

    if ($nama_role === '') {
        $error = 'Nama role wajib diisi.';
    } elseif (mb_strlen($nama_role) > 100) {
        $error = 'Nama role maksimal 100 karakter.';
    } elseif (mb_strlen($keterangan) > 255) {
        $error = 'Keterangan maksimal 255 karakter.';
    }

    if ($error === '') {

        $stmt = mysqli_prepare(
            $koneksi,
            "UPDATE roles
             SET nama_role = ?,
                 keterangan = NULLIF(?, '')
             WHERE id_role = ?"
        );

        if (!$stmt) {
            $error = 'Gagal menyiapkan proses update.';
        } else {

            mysqli_stmt_bind_param(
                $stmt,
                'ssi',
                $nama_role,
                $keterangan,
                $id_role
            );

            if (mysqli_stmt_execute($stmt)) {

                mysqli_stmt_close($stmt);

                $_SESSION['success'] = 'Role berhasil diperbarui.';
                header('Location: role.php');
                exit;
            }

            $error = 'Gagal memperbarui role: ' . mysqli_stmt_error($stmt);
            mysqli_stmt_close($stmt);
        }
    }

    /* Pertahankan input jika validasi gagal */
    $data_role['nama_role'] = $nama_role;
    $data_role['keterangan'] = $keterangan;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Role - SIM Mahasiswa</title>
    <link rel="stylesheet" href="style.css">

    <style>
        .edit-role-wrapper {
            width: 100%;
            max-width: 920px;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            box-shadow: 0 4px 18px rgba(0,0,0,.05);
            overflow: hidden;
        }

        .edit-role-header {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 22px 25px;
            background: #fbfdfb;
            border-bottom: 1px solid #e5e7eb;
        }

        .edit-role-icon {
            width: 46px;
            height: 46px;
            min-width: 46px;
            border-radius: 10px;
            background: #ecfdf5;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
        }

        .edit-role-header h2 {
            margin: 0 0 5px;
            font-size: 20px;
            color: #1f2937;
        }

        .edit-role-header p {
            margin: 0;
            color: #6b7280;
            font-size: 13px;
        }

        .edit-role-form {
            display: block !important;
            width: 100% !important;
            padding: 25px !important;
            margin: 0 !important;
        }

        .edit-role-grid {
            display: grid !important;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 22px;
            width: 100%;
        }

        .edit-role-group {
            display: block !important;
            width: 100%;
            min-width: 0;
        }

        .edit-role-group.full {
            grid-column: 1 / -1;
        }

        .edit-role-group label {
            display: block !important;
            margin: 0 0 8px !important;
            padding: 0 !important;
            color: #1f2937 !important;
            font-size: 14px !important;
            font-weight: 700 !important;
        }

        .edit-role-input,
        .edit-role-textarea {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            width: 100% !important;
            box-sizing: border-box !important;
            border: 1px solid #cbd5e1 !important;
            border-radius: 8px !important;
            background: #fff !important;
            color: #1f2937 !important;
            font-family: Arial, Helvetica, sans-serif !important;
            font-size: 14px !important;
            line-height: 1.4 !important;
        }

        .edit-role-input {
            height: 46px !important;
            min-height: 46px !important;
            padding: 11px 13px !important;
        }

        .edit-role-textarea {
            min-height: 125px !important;
            padding: 11px 13px !important;
            resize: vertical;
        }

        .edit-role-input:focus,
        .edit-role-textarea:focus {
            outline: 2px solid #bbf7d0 !important;
            border-color: #166534 !important;
        }

        .edit-role-readonly {
            background: #f3f4f6 !important;
            color: #4b5563 !important;
            cursor: not-allowed;
        }

        .edit-role-help {
            display: block !important;
            margin-top: 7px;
            color: #6b7280;
            font-size: 12px;
            line-height: 1.5;
        }

        .edit-role-required {
            color: #dc2626;
        }

        .edit-role-footer {
            display: flex !important;
            justify-content: flex-end;
            align-items: center;
            gap: 10px;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }

        .edit-role-footer .btn {
            min-width: 120px;
        }

        @media (max-width: 700px) {
            .edit-role-grid {
                grid-template-columns: 1fr;
            }

            .edit-role-group.full {
                grid-column: auto;
            }

            .edit-role-form {
                padding: 18px !important;
            }

            .edit-role-header {
                padding: 18px;
            }

            .edit-role-footer {
                flex-direction: column-reverse;
                align-items: stretch;
            }

            .edit-role-footer .btn {
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
            <h1>Edit Role</h1>
            <p>Perbarui informasi role pengguna.</p>
        </div>

        <a href="role.php" class="btn btn-outline">
            ← Kembali
        </a>
    </div>

    <?php if ($error !== ''): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <div class="edit-role-wrapper">

        <div class="edit-role-header">
            <div class="edit-role-icon">🔐</div>

            <div>
                <h2>Informasi Role</h2>
                <p>Field bertanda <strong>*</strong> wajib diisi.</p>
            </div>
        </div>

        <form
            class="edit-role-form"
            method="post"
            action="role_edit.php?id=<?= (int)$id_role ?>"
            autocomplete="off"
        >

            <?= csrf_field() ?>

            <div class="edit-role-grid">

                <div class="edit-role-group">

                    <label for="kode_role">
                        Kode Role
                    </label>

                    <input
                        class="edit-role-input edit-role-readonly"
                        type="text"
                        id="kode_role"
                        value="<?= htmlspecialchars(
                            $data_role['kode_role'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        readonly
                    >

                    <small class="edit-role-help">
                        Kode role digunakan sebagai identitas sistem
                        dan tidak dapat diubah.
                    </small>

                </div>

                <div class="edit-role-group">

                    <label for="nama_role">
                        Nama Role
                        <span class="edit-role-required">*</span>
                    </label>

                    <input
                        class="edit-role-input"
                        type="text"
                        id="nama_role"
                        name="nama_role"
                        maxlength="100"
                        required
                        value="<?= htmlspecialchars(
                            $data_role['nama_role'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        placeholder="Contoh: Operator Program Studi"
                    >

                </div>

                <div class="edit-role-group full">

                    <label for="keterangan">
                        Keterangan
                    </label>

                    <textarea
                        class="edit-role-textarea"
                        id="keterangan"
                        name="keterangan"
                        maxlength="255"
                        rows="5"
                        placeholder="Jelaskan fungsi atau kewenangan role ini..."
                    ><?= htmlspecialchars(
                        $data_role['keterangan'] ?? '',
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?></textarea>

                    <small class="edit-role-help">
                        Maksimal 255 karakter.
                    </small>

                </div>

            </div>

            <div class="edit-role-footer">

                <a href="role.php" class="btn btn-outline">
                    Batal
                </a>

                <button type="submit" class="btn btn-primary">
                    Simpan Perubahan
                </button>

            </div>

        </form>

    </div>

</main>

</body>
</html>
