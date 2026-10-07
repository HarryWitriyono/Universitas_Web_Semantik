<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/csrf.php';

wajib_role(['MAHASISWA']);

$id_pengguna = (int)($_SESSION['id_pengguna'] ?? 0);
if ($id_pengguna <= 0) {
    header('Location: login.php');
    exit;
}

function e_profil_edit($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$sql = "SELECT
            m.id_mahasiswa, m.npm, m.nama_mahasiswa, m.jenis_kelamin,
            m.tempat_lahir, m.tanggal_lahir, m.tanggal_masuk,
            m.alamat, m.status_mahasiswa,
            p.email, p.no_hp,
            ps.kode_program_studi, ps.nama_program_studi, ps.jenjang,
            f.kode_fakultas, f.nama_fakultas
        FROM mahasiswa m
        INNER JOIN pengguna p ON p.id_pengguna = m.id_pengguna
        INNER JOIN program_studi ps ON ps.id_program_studi = m.id_program_studi
        INNER JOIN fakultas f ON f.id_fakultas = ps.id_fakultas
        WHERE m.id_pengguna = ?
        LIMIT 1";

$stmt = mysqli_prepare($koneksi, $sql);
mysqli_stmt_bind_param($stmt, 'i', $id_pengguna);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$mhs = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$mhs) {
    $_SESSION['error'] = 'Data mahasiswa tidak ditemukan.';
    header('Location: dashboard.php');
    exit;
}

$data = $mhs;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $data['nama_mahasiswa'] = trim((string)($_POST['nama_mahasiswa'] ?? ''));
    $data['jenis_kelamin'] = (string)($_POST['jenis_kelamin'] ?? '');
    $data['tempat_lahir'] = trim((string)($_POST['tempat_lahir'] ?? ''));
    $data['tanggal_lahir'] = trim((string)($_POST['tanggal_lahir'] ?? ''));
    $data['alamat'] = trim((string)($_POST['alamat'] ?? ''));
    $data['email'] = trim((string)($_POST['email'] ?? ''));
    $data['no_hp'] = trim((string)($_POST['no_hp'] ?? ''));

    if ($data['nama_mahasiswa'] === '') {
        $errors[] = 'Nama mahasiswa wajib diisi.';
    } elseif (mb_strlen($data['nama_mahasiswa']) > 200) {
        $errors[] = 'Nama mahasiswa maksimal 200 karakter.';
    }

    if (!in_array($data['jenis_kelamin'], ['L', 'P'], true)) {
        $errors[] = 'Jenis kelamin tidak valid.';
    }

    if ($data['email'] !== '' && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Format email tidak valid.';
    }

    if (mb_strlen($data['tempat_lahir']) > 100) {
        $errors[] = 'Tempat lahir maksimal 100 karakter.';
    }

    if (mb_strlen($data['no_hp']) > 30) {
        $errors[] = 'Nomor HP maksimal 30 karakter.';
    }

    if ($data['tanggal_lahir'] !== '') {
        $tanggal_valid = DateTime::createFromFormat('Y-m-d', $data['tanggal_lahir']);
        if (!$tanggal_valid || $tanggal_valid->format('Y-m-d') !== $data['tanggal_lahir']) {
            $errors[] = 'Tanggal lahir tidak valid.';
        }
    }

    if (!$errors) {
        mysqli_begin_transaction($koneksi);

        try {
            $sql_mhs = "UPDATE mahasiswa
                        SET nama_mahasiswa=?, jenis_kelamin=?, tempat_lahir=?,
                            tanggal_lahir=NULLIF(?, ''), alamat=?
                        WHERE id_mahasiswa=? AND id_pengguna=?";
            $stmt_mhs = mysqli_prepare($koneksi, $sql_mhs);
            if (!$stmt_mhs) throw new Exception('Gagal menyiapkan perubahan data mahasiswa.');

            mysqli_stmt_bind_param(
                $stmt_mhs,
                'sssssii',
                $data['nama_mahasiswa'],
                $data['jenis_kelamin'],
                $data['tempat_lahir'],
                $data['tanggal_lahir'],
                $data['alamat'],
                $mhs['id_mahasiswa'],
                $id_pengguna
            );

            if (!mysqli_stmt_execute($stmt_mhs)) {
                throw new Exception('Gagal memperbarui data mahasiswa: ' . mysqli_stmt_error($stmt_mhs));
            }
            mysqli_stmt_close($stmt_mhs);

            $sql_user = "UPDATE pengguna SET nama_lengkap=?, email=?, no_hp=? WHERE id_pengguna=?";
            $stmt_user = mysqli_prepare($koneksi, $sql_user);
            if (!$stmt_user) throw new Exception('Gagal menyiapkan perubahan data akun.');

            mysqli_stmt_bind_param(
                $stmt_user,
                'sssi',
                $data['nama_mahasiswa'],
                $data['email'],
                $data['no_hp'],
                $id_pengguna
            );

            if (!mysqli_stmt_execute($stmt_user)) {
                throw new Exception('Gagal memperbarui data akun: ' . mysqli_stmt_error($stmt_user));
            }
            mysqli_stmt_close($stmt_user);

            mysqli_commit($koneksi);

            $_SESSION['success'] = 'Profil mahasiswa berhasil diperbarui.';
            header('Location: mahasiswa_profil.php');
            exit;
        } catch (Throwable $e) {
            mysqli_rollback($koneksi);
            $errors[] = $e->getMessage();
        }
    }
}

$judul_halaman = 'Edit Profil Saya';
include __DIR__ . '/topbar.php';
include __DIR__ . '/sideleftbar.php';
?>
<main class="main-content">
    <div class="page-heading">
        <span class="eyebrow">AKUN MAHASISWA</span>
        <h1>Edit Profil Saya</h1>
        <p>Perbarui data pribadi dan informasi kontak Anda.</p>
    </div>

    <?php if ($errors): ?>
        <div class="alert alert-danger">
            <ul style="margin:0;padding-left:20px;">
                <?php foreach ($errors as $error): ?>
                    <li><?= e_profil_edit($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="dashboard-panel" style="margin-bottom:25px;">
        <div style="padding:20px 22px;border-bottom:1px solid #e5e7eb;font-weight:700;">Data Akademik — Tidak Dapat Diubah</div>
        <div style="padding:22px;display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;">
            <div>
                <label class="form-label">NPM</label>
                <input type="text" class="form-control" readonly value="<?= e_profil_edit($mhs['npm']) ?>">
            </div>
            <div>
                <label class="form-label">Program Studi</label>
                <input type="text" class="form-control" readonly value="<?= e_profil_edit($mhs['kode_program_studi'].' - '.$mhs['nama_program_studi']) ?>">
            </div>
            <div>
                <label class="form-label">Fakultas</label>
                <input type="text" class="form-control" readonly value="<?= e_profil_edit($mhs['kode_fakultas'].' - '.$mhs['nama_fakultas']) ?>">
            </div>
            <div>
                <label class="form-label">Tanggal Masuk</label>
                <input type="text" class="form-control" readonly value="<?= e_profil_edit($mhs['tanggal_masuk']) ?>">
            </div>
            <div>
                <label class="form-label">Status Mahasiswa</label>
                <input type="text" class="form-control" readonly value="<?= e_profil_edit($mhs['status_mahasiswa']) ?>">
            </div>
        </div>
    </div>

    <div class="dashboard-panel">
        <div style="padding:20px 22px;border-bottom:1px solid #e5e7eb;font-weight:700;">Data Pribadi & Kontak</div>
        <div style="padding:22px;">
            <form method="post" autocomplete="off">
                <?= csrf_field() ?>

                <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px;">
                    <div>
                        <label class="form-label">Nama Mahasiswa *</label>
                        <input type="text" name="nama_mahasiswa" class="form-control" maxlength="200" required value="<?= e_profil_edit($data['nama_mahasiswa']) ?>">
                    </div>
                    <div>
                        <label class="form-label">Jenis Kelamin *</label>
                        <select name="jenis_kelamin" class="form-control" required>
                            <option value="L" <?= $data['jenis_kelamin'] === 'L' ? 'selected' : '' ?>>Laki-laki</option>
                            <option value="P" <?= $data['jenis_kelamin'] === 'P' ? 'selected' : '' ?>>Perempuan</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Tempat Lahir</label>
                        <input type="text" name="tempat_lahir" class="form-control" maxlength="100" value="<?= e_profil_edit($data['tempat_lahir']) ?>">
                    </div>
                    <div>
                        <label class="form-label">Tanggal Lahir</label>
                        <input type="date" name="tanggal_lahir" class="form-control" value="<?= e_profil_edit($data['tanggal_lahir']) ?>">
                    </div>
                    <div>
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" maxlength="150" value="<?= e_profil_edit($data['email']) ?>">
                    </div>
                    <div>
                        <label class="form-label">No. HP</label>
                        <input type="text" name="no_hp" class="form-control" maxlength="30" value="<?= e_profil_edit($data['no_hp']) ?>">
                    </div>
                    <div style="grid-column:1 / -1;">
                        <label class="form-label">Alamat</label>
                        <textarea name="alamat" class="form-control" rows="4"><?= e_profil_edit($data['alamat']) ?></textarea>
                    </div>
                </div>

                <div style="margin-top:24px;display:flex;gap:10px;flex-wrap:wrap;">
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    <a href="mahasiswa_profil.php" class="btn btn-outline">Batal</a>
                </div>
            </form>
        </div>
    </div>

    <div class="dashboard-panel" style="margin-top:25px;">
        <div style="padding:18px 22px;">
            <strong>Catatan:</strong> NPM, Program Studi, Fakultas, Tanggal Masuk, dan Status Mahasiswa dikelola oleh pihak akademik dan tidak dapat diubah melalui halaman ini.
        </div>
    </div>
</main>
