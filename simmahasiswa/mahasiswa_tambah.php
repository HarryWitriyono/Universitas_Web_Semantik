<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/koneksi.php';

wajib_role(['ADMIN', 'OPERATOR_PRODI']);

$error = '';

$nama_mahasiswa = '';
$npm = '';
$jenis_kelamin = 'L';
$tempat_lahir = '';
$tanggal_lahir = '';
$tanggal_masuk = date('Y-m-d');
$alamat = '';
$status_mahasiswa = 'Aktif';
$id_program_studi = '';

$role = $_SESSION['kode_role'] ?? '';
$id_pengguna_login = (int)($_SESSION['id_pengguna'] ?? 0);

/*
 * Ambil daftar Program Studi aktif.
 */
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $npm = trim($_POST['npm'] ?? '');
    $nama_mahasiswa = trim($_POST['nama_mahasiswa'] ?? '');
    $jenis_kelamin = $_POST['jenis_kelamin'] ?? '';
    $tempat_lahir = trim($_POST['tempat_lahir'] ?? '');
    $tanggal_lahir = trim($_POST['tanggal_lahir'] ?? '');
    $tanggal_masuk = trim($_POST['tanggal_masuk'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');
    $status_mahasiswa = $_POST['status_mahasiswa'] ?? '';
    $id_program_studi = filter_input(INPUT_POST, 'id_program_studi', FILTER_VALIDATE_INT);

    /* Validasi dasar */
    if (!$id_program_studi) {
        $error = 'Program studi wajib dipilih.';
    } elseif ($npm === '') {
        $error = 'NPM wajib diisi.';
    } elseif (mb_strlen($npm) > 30) {
        $error = 'NPM maksimal 30 karakter.';
    } elseif ($nama_mahasiswa === '') {
        $error = 'Nama mahasiswa wajib diisi.';
    } elseif (mb_strlen($nama_mahasiswa) > 200) {
        $error = 'Nama mahasiswa maksimal 200 karakter.';
    } elseif (!in_array($jenis_kelamin, ['L', 'P'], true)) {
        $error = 'Jenis kelamin tidak valid.';
    } elseif ($tanggal_masuk === '') {
        $error = 'Tanggal masuk wajib diisi.';
    } elseif (!in_array(
        $status_mahasiswa,
        ['Aktif','Cuti','Lulus','Mengundurkan Diri','Drop Out','Tidak Aktif'],
        true
    )) {
        $error = 'Status mahasiswa tidak valid.';
    }

    /* Operator hanya boleh menambahkan mahasiswa pada Prodi sendiri. */
    if ($error === '' && $role === 'OPERATOR_PRODI') {
        $scope_prodi = (int)($_SESSION['id_program_studi'] ?? 0);

        if (!$scope_prodi || (int)$id_program_studi !== $scope_prodi) {
            $error = 'Anda hanya dapat menambahkan mahasiswa pada Program Studi Anda.';
        }
    }

    /*
     * Pastikan Prodi valid sekaligus ambil scope Universitas/Fakultas.
     * Scope ini akan dipakai untuk membuat akun pengguna mahasiswa.
     */
    $data_prodi = null;

    if ($error === '') {
        $stmt = mysqli_prepare(
            $koneksi,
            "SELECT
                ps.id_program_studi,
                ps.nama_program_studi,
                f.id_fakultas,
                f.nama_fakultas,
                u.id_universitas,
                u.nama_universitas
             FROM program_studi ps
             INNER JOIN fakultas f
                ON f.id_fakultas = ps.id_fakultas
             INNER JOIN universitas u
                ON u.id_universitas = f.id_universitas
             WHERE ps.id_program_studi = ?
               AND ps.status_aktif = 'Aktif'
             LIMIT 1"
        );

        if (!$stmt) {
            $error = 'Gagal menyiapkan validasi program studi: ' . mysqli_error($koneksi);
        } else {
            mysqli_stmt_bind_param($stmt, 'i', $id_program_studi);
            mysqli_stmt_execute($stmt);
            $data_prodi = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            mysqli_stmt_close($stmt);

            if (!$data_prodi) {
                $error = 'Program studi yang dipilih tidak ditemukan atau tidak aktif.';
            }
        }
    }

    /* NPM harus unik pada mahasiswa. */
    if ($error === '') {
        $stmt = mysqli_prepare(
            $koneksi,
            "SELECT id_mahasiswa
             FROM mahasiswa
             WHERE npm = ?
             LIMIT 1"
        );

        mysqli_stmt_bind_param($stmt, 's', $npm);
        mysqli_stmt_execute($stmt);
        $sudah_ada = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        if ($sudah_ada) {
            $error = 'NPM tersebut sudah digunakan.';
        }
    }

    /*
     * Username akun mahasiswa menggunakan NPM.
     * Karena username unik, pastikan belum ada akun dengan username tersebut.
     */
    if ($error === '') {
        $stmt = mysqli_prepare(
            $koneksi,
            "SELECT id_pengguna
             FROM pengguna
             WHERE username = ?
             LIMIT 1"
        );

        mysqli_stmt_bind_param($stmt, 's', $npm);
        mysqli_stmt_execute($stmt);
        $akun_lama = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        if ($akun_lama) {
            $error = 'NPM tersebut sudah memiliki akun pengguna. Periksa data pengguna sebelum melanjutkan.';
        }
    }

    if ($error === '') {
        $tanggal_lahir_db = $tanggal_lahir !== '' ? $tanggal_lahir : null;
        $password_awal = strrev($npm);
        $password_hash = password_hash($password_awal, PASSWORD_DEFAULT);

        /*
         * Versi A:
         * Semua logika pembuatan akun dilakukan di PHP menggunakan transaksi.
         * Jika salah satu INSERT gagal, seluruh proses dibatalkan.
         */
        mysqli_begin_transaction($koneksi);

        try {
            /* Ambil role MAHASISWA. */
            $stmt_role = mysqli_prepare(
                $koneksi,
                "SELECT id_role
                 FROM roles
                 WHERE kode_role = 'MAHASISWA'
                 LIMIT 1"
            );

            if (!$stmt_role) {
                throw new Exception('Gagal menyiapkan pencarian role MAHASISWA.');
            }

            mysqli_stmt_execute($stmt_role);
            $data_role = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_role));
            mysqli_stmt_close($stmt_role);

            if (!$data_role) {
                throw new Exception('Role MAHASISWA belum tersedia pada tabel roles.');
            }

            $id_role_mahasiswa = (int)$data_role['id_role'];

            /* 1. Buat akun pengguna mahasiswa. */
            $stmt_user = mysqli_prepare(
                $koneksi,
                "INSERT INTO pengguna
                (
                    id_role,
                    id_universitas,
                    id_fakultas,
                    id_program_studi,
                    username,
                    password_hash,
                    nama_lengkap,
                    status_aktif
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, 'Aktif')"
            );

            if (!$stmt_user) {
                throw new Exception('Gagal menyiapkan pembuatan akun: ' . mysqli_error($koneksi));
            }

            $id_universitas = (int)$data_prodi['id_universitas'];
            $id_fakultas = (int)$data_prodi['id_fakultas'];
            $nama_lengkap = $nama_mahasiswa;

            mysqli_stmt_bind_param(
                $stmt_user,
                'iiiisss',
                $id_role_mahasiswa,
                $id_universitas,
                $id_fakultas,
                $id_program_studi,
                $npm,
                $password_hash,
                $nama_lengkap
            );

            if (!mysqli_stmt_execute($stmt_user)) {
                throw new Exception('Gagal membuat akun mahasiswa: ' . mysqli_stmt_error($stmt_user));
            }

            $id_pengguna_baru = mysqli_insert_id($koneksi);
            mysqli_stmt_close($stmt_user);

            /* 2. Buat data mahasiswa dan hubungkan dengan akun. */
            $stmt_mhs = mysqli_prepare(
                $koneksi,
                "INSERT INTO mahasiswa
                (
                    id_pengguna,
                    id_program_studi,
                    npm,
                    nama_mahasiswa,
                    jenis_kelamin,
                    tempat_lahir,
                    tanggal_lahir,
                    tanggal_masuk,
                    alamat,
                    status_mahasiswa
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );

            if (!$stmt_mhs) {
                throw new Exception('Gagal menyiapkan penyimpanan mahasiswa: ' . mysqli_error($koneksi));
            }

            mysqli_stmt_bind_param(
                $stmt_mhs,
                'iissssssss',
                $id_pengguna_baru,
                $id_program_studi,
                $npm,
                $nama_mahasiswa,
                $jenis_kelamin,
                $tempat_lahir,
                $tanggal_lahir_db,
                $tanggal_masuk,
                $alamat,
                $status_mahasiswa
            );

            if (!mysqli_stmt_execute($stmt_mhs)) {
                throw new Exception('Gagal menyimpan data mahasiswa: ' . mysqli_stmt_error($stmt_mhs));
            }

            $id_mahasiswa_baru = mysqli_insert_id($koneksi);
            mysqli_stmt_close($stmt_mhs);

            /*
             * 3. Audit log: pembuatan akun dan data mahasiswa.
             * Dilakukan di transaksi yang sama.
             */
            $data_baru_pengguna = json_encode([
                'id_pengguna' => $id_pengguna_baru,
                'username' => $npm,
                'nama_lengkap' => $nama_mahasiswa,
                'id_role' => $id_role_mahasiswa,
                'id_universitas' => $id_universitas,
                'id_fakultas' => $id_fakultas,
                'id_program_studi' => (int)$id_program_studi
            ], JSON_UNESCAPED_UNICODE);

            $data_baru_mahasiswa = json_encode([
                'id_mahasiswa' => $id_mahasiswa_baru,
                'id_pengguna' => $id_pengguna_baru,
                'id_program_studi' => (int)$id_program_studi,
                'npm' => $npm,
                'nama_mahasiswa' => $nama_mahasiswa,
                'jenis_kelamin' => $jenis_kelamin,
                'tanggal_masuk' => $tanggal_masuk,
                'status_mahasiswa' => $status_mahasiswa
            ], JSON_UNESCAPED_UNICODE);

            $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;

            $stmt_audit = mysqli_prepare(
                $koneksi,
                "INSERT INTO audit_log
                (
                    id_pengguna,
                    tabel_nama,
                    record_id,
                    aksi,
                    data_lama,
                    data_baru,
                    ip_address,
                    user_agent
                )
                VALUES (?, ?, ?, 'INSERT', NULL, ?, ?, ?)"
            );

            if (!$stmt_audit) {
                throw new Exception('Gagal menyiapkan audit log.');
            }

            $tabel_nama = 'pengguna';
            $record_id = (string)$id_pengguna_baru;

            mysqli_stmt_bind_param(
                $stmt_audit,
                'isssss',
                $id_pengguna_login,
                $tabel_nama,
                $record_id,
                $data_baru_pengguna,
                $ip_address,
                $user_agent
            );

            if (!mysqli_stmt_execute($stmt_audit)) {
                throw new Exception('Gagal mencatat audit akun mahasiswa.');
            }
            mysqli_stmt_close($stmt_audit);

            $stmt_audit2 = mysqli_prepare(
                $koneksi,
                "INSERT INTO audit_log
                (
                    id_pengguna,
                    tabel_nama,
                    record_id,
                    aksi,
                    data_lama,
                    data_baru,
                    ip_address,
                    user_agent
                )
                VALUES (?, ?, ?, 'INSERT', NULL, ?, ?, ?)"
            );

            if (!$stmt_audit2) {
                throw new Exception('Gagal menyiapkan audit data mahasiswa.');
            }

            $tabel_nama2 = 'mahasiswa';
            $record_id2 = (string)$id_mahasiswa_baru;

            mysqli_stmt_bind_param(
                $stmt_audit2,
                'isssss',
                $id_pengguna_login,
                $tabel_nama2,
                $record_id2,
                $data_baru_mahasiswa,
                $ip_address,
                $user_agent
            );

            if (!mysqli_stmt_execute($stmt_audit2)) {
                throw new Exception('Gagal mencatat audit data mahasiswa.');
            }
            mysqli_stmt_close($stmt_audit2);

            mysqli_commit($koneksi);

            /*
             * Jangan menyimpan password plaintext ke database.
             * Password awal diinformasikan melalui session flash hanya untuk
             * ditampilkan sekali setelah proses berhasil.
             */
            $_SESSION['success'] =
                'Data mahasiswa "' . $nama_mahasiswa .
                '" berhasil ditambahkan. Akun login otomatis dibuat dengan username NPM. ' .
                'Password awal: ' . $password_awal .
                ' (sebaiknya segera diganti setelah login).';

            header('Location: mahasiswa.php');
            exit;

        } catch (Throwable $e) {
            mysqli_rollback($koneksi);
            $error = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Mahasiswa - SIM Mahasiswa</title>
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
        .master-form-group textarea{resize:vertical;min-height:90px}
        .master-form-group input:focus,.master-form-group select:focus,.master-form-group textarea:focus{outline:none;border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,.1)}
        .form-help{margin-top:6px;font-size:12px;color:#6b7280}
        .account-info{padding:13px 15px;background:#f0f7ff;border:1px solid #bfdbfe;border-radius:9px;color:#1e3a5f;font-size:13px;line-height:1.55}
        .account-info strong{color:#12345b}
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
        <h1>Tambah Mahasiswa</h1>
        <p>Tambahkan data mahasiswa baru beserta akun login secara otomatis.</p>
    </div>

    <?php if ($error !== ''): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <div class="master-form-card">

        <div class="master-form-header">
            <h2>Form Mahasiswa</h2>
            <p>Field bertanda * wajib diisi.</p>
        </div>

        <form method="post" class="master-form">

            <?= csrf_field() ?>

            <div class="master-form-grid">

                <div class="master-form-group">
                    <label for="npm">NPM *</label>
                    <input
                        type="text"
                        name="npm"
                        id="npm"
                        maxlength="30"
                        value="<?= htmlspecialchars($npm, ENT_QUOTES, 'UTF-8') ?>"
                        required
                    >
                    <div class="form-help">
                        NPM juga akan menjadi username akun mahasiswa.
                    </div>
                </div>

                <div class="master-form-group">
                    <label for="nama_mahasiswa">Nama Mahasiswa *</label>
                    <input
                        type="text"
                        name="nama_mahasiswa"
                        id="nama_mahasiswa"
                        maxlength="200"
                        value="<?= htmlspecialchars($nama_mahasiswa, ENT_QUOTES, 'UTF-8') ?>"
                        required
                    >
                </div>

                <div class="master-form-group">
                    <label for="id_program_studi">Program Studi *</label>
                    <select name="id_program_studi" id="id_program_studi" required>
                        <option value="">-- Pilih Program Studi --</option>

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
                                <?= ((string)$id_program_studi === (string)$data_prodi['id_program_studi']) ? 'selected' : '' ?>
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
                        <option value="L" <?= $jenis_kelamin === 'L' ? 'selected' : '' ?>>Laki-laki</option>
                        <option value="P" <?= $jenis_kelamin === 'P' ? 'selected' : '' ?>>Perempuan</option>
                    </select>
                </div>

                <div class="master-form-group">
                    <label for="tempat_lahir">Tempat Lahir</label>
                    <input
                        type="text"
                        name="tempat_lahir"
                        id="tempat_lahir"
                        maxlength="100"
                        value="<?= htmlspecialchars($tempat_lahir, ENT_QUOTES, 'UTF-8') ?>"
                    >
                </div>

                <div class="master-form-group">
                    <label for="tanggal_lahir">Tanggal Lahir</label>
                    <input
                        type="date"
                        name="tanggal_lahir"
                        id="tanggal_lahir"
                        value="<?= htmlspecialchars($tanggal_lahir, ENT_QUOTES, 'UTF-8') ?>"
                    >
                </div>

                <div class="master-form-group">
                    <label for="tanggal_masuk">Tanggal Masuk *</label>
                    <input
                        type="date"
                        name="tanggal_masuk"
                        id="tanggal_masuk"
                        value="<?= htmlspecialchars($tanggal_masuk, ENT_QUOTES, 'UTF-8') ?>"
                        required
                    >
                </div>

                <div class="master-form-group">
                    <label for="status_mahasiswa">Status Mahasiswa *</label>
                    <select name="status_mahasiswa" id="status_mahasiswa" required>
                        <?php foreach (['Aktif','Cuti','Lulus','Mengundurkan Diri','Drop Out','Tidak Aktif'] as $status_option): ?>
                            <option
                                value="<?= htmlspecialchars($status_option, ENT_QUOTES, 'UTF-8') ?>"
                                <?= $status_mahasiswa === $status_option ? 'selected' : '' ?>
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
                    ><?= htmlspecialchars($alamat, ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>

                <div class="master-form-group master-form-full">
                    <div class="account-info">
                        <strong>Akun login dibuat otomatis.</strong><br>
                        Username = <strong>NPM</strong><br>
                        Password awal = <strong>NPM dibalik</strong><br>
                        Contoh: NPM <strong>20260001</strong> → password awal <strong>10006202</strong>.<br>
                        Password disimpan dalam database menggunakan <code>password_hash()</code>, bukan plaintext.
                    </div>
                </div>

            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    Simpan Mahasiswa + Buat Akun
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
