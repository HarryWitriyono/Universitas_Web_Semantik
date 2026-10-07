<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/koneksi.php';

wajib_role(['ADMIN', 'OPERATOR_PRODI']);

$role = $_SESSION['kode_role'] ?? '';

$id_mahasiswa = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$id_mahasiswa) {
    $_SESSION['error'] = 'ID mahasiswa tidak valid.';
    header('Location: mahasiswa.php');
    exit;
}

/* Ambil data mahasiswa */
$stmt = mysqli_prepare(
    $koneksi,
    "SELECT
        m.id_mahasiswa,
        m.id_pengguna,
        m.id_program_studi,
        m.npm,
        m.nama_mahasiswa,
        m.jenis_kelamin,
        m.tempat_lahir,
        m.tanggal_lahir,
        m.tanggal_masuk,
        m.alamat,
        m.status_mahasiswa
     FROM mahasiswa m
     WHERE m.id_mahasiswa = ?
     LIMIT 1"
);

mysqli_stmt_bind_param($stmt, 'i', $id_mahasiswa);
mysqli_stmt_execute($stmt);

$data_mahasiswa = mysqli_fetch_assoc(
    mysqli_stmt_get_result($stmt)
);

mysqli_stmt_close($stmt);

if (!$data_mahasiswa) {
    $_SESSION['error'] = 'Data mahasiswa tidak ditemukan.';
    header('Location: mahasiswa.php');
    exit;
}

/*
 * Operator hanya boleh mengedit mahasiswa pada Prodi sendiri.
 */
if (
    $role === 'OPERATOR_PRODI' &&
    (int)$data_mahasiswa['id_program_studi'] !==
    (int)($_SESSION['id_program_studi'] ?? 0)
) {
    $_SESSION['error'] = 'Anda tidak memiliki hak akses ke mahasiswa tersebut.';
    header('Location: mahasiswa.php');
    exit;
}

$prodi = mysqli_query(
    $koneksi,
    "SELECT
        ps.id_program_studi,
        ps.kode_program_studi,
        ps.nama_program_studi,
        f.kode_fakultas,
        f.nama_fakultas
     FROM program_studi ps
     INNER JOIN fakultas f
        ON f.id_fakultas = ps.id_fakultas
     WHERE ps.status_aktif = 'Aktif'
     ORDER BY f.nama_fakultas ASC, ps.nama_program_studi ASC"
);

if (!$prodi) {
    die('Gagal mengambil data program studi.');
}

$pengguna = mysqli_query(
    $koneksi,
    "SELECT
        p.id_pengguna,
        p.username,
        p.nama_lengkap
     FROM pengguna p
     INNER JOIN roles r
        ON r.id_role = p.id_role
     LEFT JOIN mahasiswa m
        ON m.id_pengguna = p.id_pengguna
     WHERE r.kode_role = 'MAHASISWA'
       AND p.status_aktif = 'Aktif'
       AND (m.id_mahasiswa IS NULL OR m.id_mahasiswa = " . (int)$id_mahasiswa . ")
     ORDER BY p.nama_lengkap ASC"
);

if (!$pengguna) {
    die('Gagal mengambil data pengguna mahasiswa.');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $nama_mahasiswa = trim($_POST['nama_mahasiswa'] ?? '');
    $jenis_kelamin = $_POST['jenis_kelamin'] ?? '';
    $tempat_lahir = trim($_POST['tempat_lahir'] ?? '');
    $tanggal_lahir = trim($_POST['tanggal_lahir'] ?? '');
    $tanggal_masuk = trim($_POST['tanggal_masuk'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');
    $status_mahasiswa = $_POST['status_mahasiswa'] ?? '';
    $id_program_studi = filter_input(INPUT_POST, 'id_program_studi', FILTER_VALIDATE_INT);
    $id_pengguna = filter_input(INPUT_POST, 'id_pengguna', FILTER_VALIDATE_INT);

    if (!$id_program_studi) {
        $error = 'Program studi wajib dipilih.';
    } elseif ($nama_mahasiswa === '') {
        $error = 'Nama mahasiswa wajib diisi.';
    } elseif (mb_strlen($nama_mahasiswa) > 200) {
        $error = 'Nama mahasiswa maksimal 200 karakter.';
    } elseif (!in_array($jenis_kelamin, ['L', 'P'], true)) {
        $error = 'Jenis kelamin tidak valid.';
    } elseif ($tanggal_masuk === '') {
        $error = 'Tanggal masuk wajib diisi.';
    } elseif (!in_array($status_mahasiswa, ['Aktif','Cuti','Lulus','Mengundurkan Diri','Drop Out','Tidak Aktif'], true)) {
        $error = 'Status mahasiswa tidak valid.';
    }

    if ($error === '' && $role === 'OPERATOR_PRODI') {
        $scope_prodi = (int)($_SESSION['id_program_studi'] ?? 0);

        if (!$scope_prodi || (int)$id_program_studi !== $scope_prodi) {
            $error = 'Operator Prodi tidak dapat memindahkan mahasiswa ke Prodi lain.';
        }
    }

    if ($error === '') {
        $stmt = mysqli_prepare(
            $koneksi,
            "SELECT id_program_studi
             FROM program_studi
             WHERE id_program_studi = ?
               AND status_aktif = 'Aktif'
             LIMIT 1"
        );

        mysqli_stmt_bind_param($stmt, 'i', $id_program_studi);
        mysqli_stmt_execute($stmt);

        $data_prodi = mysqli_fetch_assoc(
            mysqli_stmt_get_result($stmt)
        );

        mysqli_stmt_close($stmt);

        if (!$data_prodi) {
            $error = 'Program studi yang dipilih tidak ditemukan atau tidak aktif.';
        }
    }

    if ($error === '' && $id_pengguna) {
        $stmt = mysqli_prepare(
            $koneksi,
            "SELECT id_mahasiswa
             FROM mahasiswa
             WHERE id_pengguna = ?
               AND id_mahasiswa <> ?
             LIMIT 1"
        );

        mysqli_stmt_bind_param(
            $stmt,
            'ii',
            $id_pengguna,
            $id_mahasiswa
        );

        mysqli_stmt_execute($stmt);

        $sudah_terhubung = mysqli_fetch_assoc(
            mysqli_stmt_get_result($stmt)
        );

        mysqli_stmt_close($stmt);

        if ($sudah_terhubung) {
            $error = 'Akun pengguna tersebut sudah terhubung dengan mahasiswa lain.';
        }
    }

    if ($error === '') {
        $tanggal_lahir_db = $tanggal_lahir !== '' ? $tanggal_lahir : null;
        $id_pengguna_db = $id_pengguna ?: null;

        $stmt = mysqli_prepare(
            $koneksi,
            "UPDATE mahasiswa
             SET
                id_pengguna = ?,
                id_program_studi = ?,
                nama_mahasiswa = ?,
                jenis_kelamin = ?,
                tempat_lahir = ?,
                tanggal_lahir = ?,
                tanggal_masuk = ?,
                alamat = ?,
                status_mahasiswa = ?
             WHERE id_mahasiswa = ?"
        );

        if (!$stmt) {
            $error = 'Gagal menyiapkan proses update: ' . mysqli_error($koneksi);
        } else {
            mysqli_stmt_bind_param(
                $stmt,
                'iisssssssi',
                $id_pengguna_db,
                $id_program_studi,
                $nama_mahasiswa,
                $jenis_kelamin,
                $tempat_lahir,
                $tanggal_lahir_db,
                $tanggal_masuk,
                $alamat,
                $status_mahasiswa,
                $id_mahasiswa
            );

            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);

                $_SESSION['success'] =
                    'Data mahasiswa "' .
                    $nama_mahasiswa .
                    '" berhasil diperbarui.';

                header('Location: mahasiswa.php');
                exit;
            }

            $error =
                'Gagal memperbarui mahasiswa: ' .
                mysqli_stmt_error($stmt);

            mysqli_stmt_close($stmt);
        }
    }

    /*
     * Tampilkan kembali data yang baru dikirim jika ada error.
     */
    $data_mahasiswa['id_pengguna'] = $id_pengguna;
    $data_mahasiswa['id_program_studi'] = $id_program_studi;
    $data_mahasiswa['nama_mahasiswa'] = $nama_mahasiswa;
    $data_mahasiswa['jenis_kelamin'] = $jenis_kelamin;
    $data_mahasiswa['tempat_lahir'] = $tempat_lahir;
    $data_mahasiswa['tanggal_lahir'] = $tanggal_lahir;
    $data_mahasiswa['tanggal_masuk'] = $tanggal_masuk;
    $data_mahasiswa['alamat'] = $alamat;
    $data_mahasiswa['status_mahasiswa'] = $status_mahasiswa;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Mahasiswa - SIM Mahasiswa</title>
    <link rel="stylesheet" href="style.css">

    <style>
        .master-form-card{width:100%;max-width:850px;background:#fff;border:1px solid #e5e7eb;border-radius:14px;overflow:hidden;box-shadow:0 4px 18px rgba(0,0,0,.05)}
        .master-form-header{padding:22px 25px;background:#fbfdfb;border-bottom:1px solid #e5e7eb}
        .master-form-header h2{margin:0 0 4px;font-size:20px}
        .master-form-header p{margin:0;color:#6b7280;font-size:13px}
        .master-form{padding:25px}
        .master-form-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px}
        .master-form-full{grid-column:1 / -1}
        .master-form-group{margin-bottom:0}
        .master-form-group label{display:block;margin-bottom:8px;font-size:14px;font-weight:700;color:#1f2937}
        .master-form-group input,.master-form-group select,.master-form-group textarea{width:100%;box-sizing:border-box;padding:11px 13px;border:1px solid #d1d5db;border-radius:8px;font:inherit;background:#fff}
        .master-form-group input:focus,.master-form-group select:focus,.master-form-group textarea:focus{outline:none;border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,.1)}
        .master-form-group textarea{resize:vertical;min-height:90px}
        .form-help{margin-top:6px;font-size:12px;color:#6b7280}
        .form-actions{display:flex;gap:10px;align-items:center;margin-top:25px;padding-top:20px;border-top:1px solid #e5e7eb}
        @media(max-width:700px){.master-form-grid{grid-template-columns:1fr}.master-form-full{grid-column:auto}}
    </style>
</head>
<body class="app-body">

<?php include __DIR__ . '/topbar.php'; ?>
<?php include __DIR__ . '/sideleftbar.php'; ?>

<main class="main-content">

    <div class="page-heading">
        <span class="eyebrow">DATA AKADEMIK</span>
        <h1>Edit Mahasiswa</h1>
        <p>Perbarui data mahasiswa.</p>
    </div>

    <?php if ($error !== ''): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <div class="master-form-card">

        <div class="master-form-header">
            <h2>Form Mahasiswa</h2>
            <p>NPM merupakan identitas utama mahasiswa dan tidak diubah pada form ini.</p>
        </div>

        <form method="post" class="master-form">

            <?= csrf_field() ?>

            <div class="master-form-grid">

                <div class="master-form-group">
                    <label>NPM</label>
                    <input
                        type="text"
                        value="<?= htmlspecialchars($data_mahasiswa['npm'], ENT_QUOTES, 'UTF-8') ?>"
                        readonly
                        style="background:#f3f4f6;"
                    >
                </div>

                <div class="master-form-group">
                    <label for="nama_mahasiswa">Nama Mahasiswa *</label>
                    <input
                        type="text"
                        name="nama_mahasiswa"
                        id="nama_mahasiswa"
                        maxlength="200"
                        value="<?= htmlspecialchars($data_mahasiswa['nama_mahasiswa'], ENT_QUOTES, 'UTF-8') ?>"
                        required
                    >
                </div>

                <div class="master-form-group">
                    <label for="id_program_studi">Program Studi *</label>
                    <select name="id_program_studi" id="id_program_studi" required>
                        <?php while ($data_prodi = mysqli_fetch_assoc($prodi)): ?>

                            <?php
                            if (
                                $role === 'OPERATOR_PRODI' &&
                                (int)$data_prodi['id_program_studi'] !==
                                (int)($_SESSION['id_program_studi'] ?? 0)
                            ) {
                                continue;
                            }
                            ?>

                            <option
                                value="<?= (int)$data_prodi['id_program_studi'] ?>"
                                <?= ((int)$data_mahasiswa['id_program_studi'] === (int)$data_prodi['id_program_studi']) ? 'selected' : '' ?>
                            >
                                <?= htmlspecialchars(
                                    $data_prodi['kode_program_studi'] .
                                    ' - ' .
                                    $data_prodi['nama_program_studi'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </option>

                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="master-form-group">
                    <label for="jenis_kelamin">Jenis Kelamin *</label>
                    <select name="jenis_kelamin" id="jenis_kelamin" required>
                        <option value="L" <?= $data_mahasiswa['jenis_kelamin'] === 'L' ? 'selected' : '' ?>>Laki-laki</option>
                        <option value="P" <?= $data_mahasiswa['jenis_kelamin'] === 'P' ? 'selected' : '' ?>>Perempuan</option>
                    </select>
                </div>

                <div class="master-form-group">
                    <label for="tempat_lahir">Tempat Lahir</label>
                    <input
                        type="text"
                        name="tempat_lahir"
                        id="tempat_lahir"
                        maxlength="100"
                        value="<?= htmlspecialchars($data_mahasiswa['tempat_lahir'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    >
                </div>

                <div class="master-form-group">
                    <label for="tanggal_lahir">Tanggal Lahir</label>
                    <input
                        type="date"
                        name="tanggal_lahir"
                        id="tanggal_lahir"
                        value="<?= htmlspecialchars($data_mahasiswa['tanggal_lahir'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    >
                </div>

                <div class="master-form-group">
                    <label for="tanggal_masuk">Tanggal Masuk *</label>
                    <input
                        type="date"
                        name="tanggal_masuk"
                        id="tanggal_masuk"
                        value="<?= htmlspecialchars($data_mahasiswa['tanggal_masuk'], ENT_QUOTES, 'UTF-8') ?>"
                        required
                    >
                </div>

                <div class="master-form-group">
                    <label for="status_mahasiswa">Status Mahasiswa *</label>
                    <select name="status_mahasiswa" id="status_mahasiswa" required>
                        <?php foreach (['Aktif','Cuti','Lulus','Mengundurkan Diri','Drop Out','Tidak Aktif'] as $status_option): ?>
                            <option
                                value="<?= htmlspecialchars($status_option, ENT_QUOTES, 'UTF-8') ?>"
                                <?= $data_mahasiswa['status_mahasiswa'] === $status_option ? 'selected' : '' ?>
                            >
                                <?= htmlspecialchars($status_option, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="master-form-group master-form-full">
                    <label for="alamat">Alamat</label>
                    <textarea
                        name="alamat"
                        id="alamat"
                        rows="3"
                    ><?= htmlspecialchars($data_mahasiswa['alamat'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>

                <div class="master-form-group master-form-full">
                    <label for="id_pengguna">Akun Pengguna Mahasiswa</label>

                    <select name="id_pengguna" id="id_pengguna">
                        <option value="">-- Tidak dihubungkan --</option>

                        <?php while ($data_pengguna = mysqli_fetch_assoc($pengguna)): ?>
                            <option
                                value="<?= (int)$data_pengguna['id_pengguna'] ?>"
                                <?= ((int)$data_mahasiswa['id_pengguna'] === (int)$data_pengguna['id_pengguna']) ? 'selected' : '' ?>
                            >
                                <?= htmlspecialchars(
                                    $data_pengguna['username'] .
                                    ' - ' .
                                    $data_pengguna['nama_lengkap'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    Simpan Perubahan
                </button>

                <a href="mahasiswa.php" class="btn btn-outline">
                    Batal
                </a>
            </div>

        </form>
    </div>

</main>
</body>
</html>
