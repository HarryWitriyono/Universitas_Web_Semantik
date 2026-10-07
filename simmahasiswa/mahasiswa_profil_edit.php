<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/koneksi.php';

wajib_role(['MAHASISWA']);

$id_pengguna = (int)($_SESSION['id_pengguna'] ?? 0);

if ($id_pengguna <= 0) {
    $_SESSION['error'] = 'Sesi pengguna tidak valid.';
    header('Location: login.php');
    exit;
}

$stmt = mysqli_prepare(
    $koneksi,
    "SELECT
        m.id_mahasiswa,
        m.npm,
        m.nama_mahasiswa,
        m.jenis_kelamin,
        m.tempat_lahir,
        m.tanggal_lahir,
        m.tanggal_masuk,
        m.alamat,
        m.status_mahasiswa,
        p.username,
        p.email,
        p.no_hp,
        ps.kode_program_studi,
        ps.nama_program_studi,
        ps.jenjang,
        f.kode_fakultas,
        f.nama_fakultas,
        u.kode_universitas,
        u.nama_universitas
     FROM mahasiswa m
     INNER JOIN pengguna p ON p.id_pengguna = m.id_pengguna
     INNER JOIN program_studi ps ON ps.id_program_studi = m.id_program_studi
     INNER JOIN fakultas f ON f.id_fakultas = ps.id_fakultas
     INNER JOIN universitas u ON u.id_universitas = f.id_universitas
     WHERE m.id_pengguna = ?
     LIMIT 1"
);

if (!$stmt) {
    die('Gagal menyiapkan data profil.');
}

mysqli_stmt_bind_param($stmt, 'i', $id_pengguna);
mysqli_stmt_execute($stmt);
$data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$data) {
    $_SESSION['error'] = 'Profil mahasiswa tidak ditemukan.';
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $nama_mahasiswa = trim($_POST['nama_mahasiswa'] ?? '');
    $jenis_kelamin = $_POST['jenis_kelamin'] ?? '';
    $tempat_lahir = trim($_POST['tempat_lahir'] ?? '');
    $tanggal_lahir = trim($_POST['tanggal_lahir'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $no_hp = trim($_POST['no_hp'] ?? '');

    if ($nama_mahasiswa === '') {
        $error = 'Nama mahasiswa wajib diisi.';
    } elseif (mb_strlen($nama_mahasiswa) > 200) {
        $error = 'Nama mahasiswa maksimal 200 karakter.';
    } elseif (!in_array($jenis_kelamin, ['L', 'P'], true)) {
        $error = 'Jenis kelamin tidak valid.';
    } elseif (mb_strlen($tempat_lahir) > 100) {
        $error = 'Tempat lahir maksimal 100 karakter.';
    } elseif ($tanggal_lahir !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal_lahir)) {
        $error = 'Format tanggal lahir tidak valid.';
    } elseif (mb_strlen($alamat) > 65535) {
        $error = 'Alamat terlalu panjang.';
    } elseif ($email !== '' && (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150)) {
        $error = 'Format email tidak valid atau terlalu panjang.';
    } elseif (mb_strlen($no_hp) > 30) {
        $error = 'No. HP maksimal 30 karakter.';
    }

    if ($error === '' && $tanggal_lahir !== '') {
        $tanggal_obj = DateTime::createFromFormat('Y-m-d', $tanggal_lahir);
        if (!$tanggal_obj || $tanggal_obj->format('Y-m-d') !== $tanggal_lahir) {
            $error = 'Tanggal lahir tidak valid.';
        }
    }

    if ($error === '') {
        mysqli_begin_transaction($koneksi);

        try {
            $stmt_m = mysqli_prepare(
                $koneksi,
                "UPDATE mahasiswa
                 SET nama_mahasiswa = ?,
                     jenis_kelamin = ?,
                     tempat_lahir = NULLIF(?, ''),
                     tanggal_lahir = NULLIF(?, ''),
                     alamat = NULLIF(?, '')
                 WHERE id_mahasiswa = ?
                   AND id_pengguna = ?"
            );

            if (!$stmt_m) {
                throw new Exception('Gagal menyiapkan update data mahasiswa.');
            }

            mysqli_stmt_bind_param(
                $stmt_m,
                'sssssii',
                $nama_mahasiswa,
                $jenis_kelamin,
                $tempat_lahir,
                $tanggal_lahir,
                $alamat,
                $data['id_mahasiswa'],
                $id_pengguna
            );

            if (!mysqli_stmt_execute($stmt_m)) {
                throw new Exception('Gagal memperbarui data mahasiswa: ' . mysqli_stmt_error($stmt_m));
            }
            mysqli_stmt_close($stmt_m);

            $stmt_p = mysqli_prepare(
                $koneksi,
                "UPDATE pengguna
                 SET nama_lengkap = ?,
                     email = NULLIF(?, ''),
                     no_hp = NULLIF(?, '')
                 WHERE id_pengguna = ?"
            );

            if (!$stmt_p) {
                throw new Exception('Gagal menyiapkan update data akun.');
            }

            mysqli_stmt_bind_param(
                $stmt_p,
                'sssi',
                $nama_mahasiswa,
                $email,
                $no_hp,
                $id_pengguna
            );

            if (!mysqli_stmt_execute($stmt_p)) {
                throw new Exception('Gagal memperbarui data akun: ' . mysqli_stmt_error($stmt_p));
            }
            mysqli_stmt_close($stmt_p);

            $stmt_a = mysqli_prepare(
                $koneksi,
                "INSERT INTO audit_log
                    (id_pengguna, tabel_nama, record_id, aksi, data_lama, data_baru, ip_address, user_agent)
                 VALUES (?, 'mahasiswa', ?, 'UPDATE', ?, ?, ?, ?)"
            );

            if (!$stmt_a) {
                throw new Exception('Gagal menyiapkan audit log.');
            }

            $data_lama = json_encode([
                'nama_mahasiswa' => $data['nama_mahasiswa'],
                'jenis_kelamin' => $data['jenis_kelamin'],
                'tempat_lahir' => $data['tempat_lahir'],
                'tanggal_lahir' => $data['tanggal_lahir'],
                'alamat' => $data['alamat'],
                'email' => $data['email'],
                'no_hp' => $data['no_hp']
            ], JSON_UNESCAPED_UNICODE);

            $data_baru = json_encode([
                'nama_mahasiswa' => $nama_mahasiswa,
                'jenis_kelamin' => $jenis_kelamin,
                'tempat_lahir' => $tempat_lahir,
                'tanggal_lahir' => $tanggal_lahir,
                'alamat' => $alamat,
                'email' => $email,
                'no_hp' => $no_hp
            ], JSON_UNESCAPED_UNICODE);

            $id_record = (string)$data['id_mahasiswa'];
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

            mysqli_stmt_bind_param(
                $stmt_a,
                'isssss',
                $id_pengguna,
                $id_record,
                $data_lama,
                $data_baru,
                $ip_address,
                $user_agent
            );

            if (!mysqli_stmt_execute($stmt_a)) {
                throw new Exception('Gagal mencatat audit log: ' . mysqli_stmt_error($stmt_a));
            }
            mysqli_stmt_close($stmt_a);

            mysqli_commit($koneksi);

            $_SESSION['success'] = 'Profil mahasiswa berhasil diperbarui.';
            header('Location: mahasiswa_profil.php');
            exit;

        } catch (Throwable $e) {
            mysqli_rollback($koneksi);
            $error = $e->getMessage();
        }
    }

    $data['nama_mahasiswa'] = $nama_mahasiswa;
    $data['jenis_kelamin'] = $jenis_kelamin;
    $data['tempat_lahir'] = $tempat_lahir;
    $data['tanggal_lahir'] = $tanggal_lahir;
    $data['alamat'] = $alamat;
    $data['email'] = $email;
    $data['no_hp'] = $no_hp;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profil Mahasiswa - SIM Mahasiswa</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .master-form-card{width:100%;max-width:850px;background:#fff;border:1px solid #e5e7eb;border-radius:14px;overflow:hidden;box-shadow:0 4px 18px rgba(0,0,0,.05)}
        .master-form-header{padding:22px 25px;background:#fbfdfb;border-bottom:1px solid #e5e7eb}
        .master-form-header h2{margin:0 0 4px;font-size:20px}.master-form-header p{margin:0;color:#6b7280;font-size:13px}
        .master-form{padding:25px}.master-form-group{margin-bottom:20px}
        .master-form-group label{display:block;margin-bottom:8px;font-size:14px;font-weight:700;color:#1f2937}
        .master-form-group input,.master-form-group select,.master-form-group textarea{width:100%;box-sizing:border-box;padding:11px 13px;border:1px solid #d1d5db;border-radius:8px;font:inherit;background:#fff}
        .master-form-group textarea{min-height:110px;resize:vertical}
        .master-form-group input:focus,.master-form-group select:focus,.master-form-group textarea:focus{outline:none;border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,.1)}
        .master-form-group input[readonly]{background:#f3f4f6;color:#4b5563}
        .form-help{margin-top:6px;font-size:12px;color:#6b7280;line-height:1.5}
        .form-actions{display:flex;gap:10px;align-items:center;padding-top:5px}
        .profile-scope{display:grid;grid-template-columns:repeat(2,1fr);gap:15px;margin-bottom:22px;padding:18px;background:#f8fafc;border:1px solid #e5e7eb;border-radius:10px}
        .scope-item label{display:block;color:#6b7280;font-size:11px;margin-bottom:4px}.scope-item strong{font-size:13px;color:#1f2937}
        @media(max-width:700px){.profile-scope{grid-template-columns:1fr}.form-actions{flex-direction:column-reverse}.form-actions .btn{width:100%}}
    </style>
</head>
<body class="app-body">

<?php include __DIR__ . '/topbar.php'; ?>
<?php include __DIR__ . '/sideleftbar.php'; ?>

<main class="main-content">

    <div class="page-heading">
        <span class="eyebrow">DATA MAHASISWA</span>
        <h1>Edit Profil Saya</h1>
        <p>Perbarui data pribadi Anda. Data akademik tidak dapat diubah dari halaman ini.</p>
    </div>

    <?php if ($error !== ''): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="master-form-card">

        <div class="master-form-header">
            <h2>Informasi Akademik</h2>
            <p>Data berikut dikelola oleh pihak akademik dan hanya dapat dilihat.</p>
        </div>

        <div class="master-form">
            <div class="profile-scope">
                <div class="scope-item">
                    <label>NPM</label>
                    <strong><?= htmlspecialchars($data['npm'], ENT_QUOTES, 'UTF-8') ?></strong>
                </div>
                <div class="scope-item">
                    <label>Program Studi</label>
                    <strong><?= htmlspecialchars($data['kode_program_studi'] . ' - ' . $data['nama_program_studi'], ENT_QUOTES, 'UTF-8') ?></strong>
                </div>
                <div class="scope-item">
                    <label>Fakultas</label>
                    <strong><?= htmlspecialchars($data['kode_fakultas'] . ' - ' . $data['nama_fakultas'], ENT_QUOTES, 'UTF-8') ?></strong>
                </div>
                <div class="scope-item">
                    <label>Tanggal Masuk</label>
                    <strong><?= htmlspecialchars($data['tanggal_masuk'], ENT_QUOTES, 'UTF-8') ?></strong>
                </div>
                <div class="scope-item">
                    <label>Status Mahasiswa</label>
                    <strong><?= htmlspecialchars($data['status_mahasiswa'], ENT_QUOTES, 'UTF-8') ?></strong>
                </div>
                <div class="scope-item">
                    <label>Universitas</label>
                    <strong><?= htmlspecialchars($data['nama_universitas'], ENT_QUOTES, 'UTF-8') ?></strong>
                </div>
            </div>

            <form method="post" class="master-form" style="padding:0;" autocomplete="off">
                <?= csrf_field() ?>

                <div class="master-form-group">
                    <label for="nama_mahasiswa">Nama Lengkap *</label>
                    <input type="text" name="nama_mahasiswa" id="nama_mahasiswa" maxlength="200"
                           value="<?= htmlspecialchars($data['nama_mahasiswa'], ENT_QUOTES, 'UTF-8') ?>" required>
                </div>

                <div class="master-form-group">
                    <label for="jenis_kelamin">Jenis Kelamin *</label>
                    <select name="jenis_kelamin" id="jenis_kelamin" required>
                        <option value="">-- Pilih Jenis Kelamin --</option>
                        <option value="L" <?= $data['jenis_kelamin'] === 'L' ? 'selected' : '' ?>>Laki-laki</option>
                        <option value="P" <?= $data['jenis_kelamin'] === 'P' ? 'selected' : '' ?>>Perempuan</option>
                    </select>
                </div>

                <div class="master-form-group">
                    <label for="tempat_lahir">Tempat Lahir</label>
                    <input type="text" name="tempat_lahir" id="tempat_lahir" maxlength="100"
                           value="<?= htmlspecialchars($data['tempat_lahir'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="master-form-group">
                    <label for="tanggal_lahir">Tanggal Lahir</label>
                    <input type="date" name="tanggal_lahir" id="tanggal_lahir"
                           value="<?= htmlspecialchars($data['tanggal_lahir'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="master-form-group">
                    <label for="alamat">Alamat</label>
                    <textarea name="alamat" id="alamat"><?= htmlspecialchars($data['alamat'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>

                <div class="master-form-group">
                    <label for="email">Email</label>
                    <input type="email" name="email" id="email" maxlength="150"
                           value="<?= htmlspecialchars($data['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="master-form-group">
                    <label for="no_hp">No. HP</label>
                    <input type="text" name="no_hp" id="no_hp" maxlength="30"
                           value="<?= htmlspecialchars($data['no_hp'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    <a href="mahasiswa_profil.php" class="btn btn-outline">Batal</a>
                </div>
            </form>
        </div>

    </div>

</main>
</body>
</html>
