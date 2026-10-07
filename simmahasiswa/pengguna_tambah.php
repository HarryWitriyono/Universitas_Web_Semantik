<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/pengguna_helper.php';

wajib_role(['ADMIN']);

$roles = mysqli_query(
    $koneksi,
    "SELECT id_role, kode_role, nama_role
     FROM roles
     ORDER BY id_role ASC"
);

$universitas = mysqli_query(
    $koneksi,
    "SELECT id_universitas, kode_universitas, nama_universitas
     FROM universitas
     ORDER BY nama_universitas ASC"
);

$fakultas = mysqli_query(
    $koneksi,
    "SELECT id_fakultas, id_universitas, kode_fakultas, nama_fakultas
     FROM fakultas
     ORDER BY nama_fakultas ASC"
);

$prodi = mysqli_query(
    $koneksi,
    "SELECT id_program_studi, id_fakultas, kode_program_studi, nama_program_studi
     FROM program_studi
     ORDER BY nama_program_studi ASC"
);

if (!$roles || !$universitas || !$fakultas || !$prodi) {
    die('Gagal mengambil data master: ' . htmlspecialchars(mysqli_error($koneksi)));
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    csrf_verify();

    $id_role = filter_input(INPUT_POST, 'id_role', FILTER_VALIDATE_INT);
    $id_universitas = filter_input(INPUT_POST, 'id_universitas', FILTER_VALIDATE_INT) ?: null;
    $id_fakultas = filter_input(INPUT_POST, 'id_fakultas', FILTER_VALIDATE_INT) ?: null;
    $id_program_studi = filter_input(INPUT_POST, 'id_program_studi', FILTER_VALIDATE_INT) ?: null;

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $no_hp = trim($_POST['no_hp'] ?? '');
    $status_aktif = $_POST['status_aktif'] ?? 'Aktif';

    if (!$id_role) {
        $error = 'Role wajib dipilih.';
    } elseif ($username === '') {
        $error = 'Username wajib diisi.';
    } elseif (!preg_match('/^[A-Za-z0-9._-]+$/', $username)) {
        $error = 'Username hanya boleh berisi huruf, angka, titik, underscore, dan tanda minus.';
    } elseif (mb_strlen($username) > 100) {
        $error = 'Username maksimal 100 karakter.';
    } elseif (mb_strlen($password) < 8) {
        $error = 'Password minimal 8 karakter.';
    } elseif ($nama_lengkap === '') {
        $error = 'Nama lengkap wajib diisi.';
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif (!in_array($status_aktif, ['Aktif', 'Tidak Aktif'], true)) {
        $error = 'Status pengguna tidak valid.';
    }

    $kode_role = '';

    if ($error === '') {
        $stmt = mysqli_prepare(
            $koneksi,
            "SELECT kode_role FROM roles WHERE id_role = ? LIMIT 1"
        );
        mysqli_stmt_bind_param($stmt, 'i', $id_role);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $data_role = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if (!$data_role) {
            $error = 'Role tidak ditemukan.';
        } else {
            $kode_role = $data_role['kode_role'];
        }
    }

    if ($error === '') {
        $error = pengguna_validate_scope(
            $kode_role,
            $id_universitas,
            $id_fakultas,
            $id_program_studi
        );
    }

    if ($error === '') {
        $error = pengguna_scope_relationship_error(
            $koneksi,
            $id_universitas,
            $id_fakultas,
            $id_program_studi
        );
    }

    if ($error === '') {
        $stmt = mysqli_prepare(
            $koneksi,
            "SELECT id_pengguna FROM pengguna WHERE username = ? LIMIT 1"
        );
        mysqli_stmt_bind_param($stmt, 's', $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $exists = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if ($exists) {
            $error = 'Username sudah digunakan.';
        }
    }

    if ($error === '') {

        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO pengguna
                (id_role, id_universitas, id_fakultas, id_program_studi,
                 username, password_hash, nama_lengkap, email, no_hp, status_aktif)
                VALUES (?, ?, ?, ?, ?, ?, ?, NULLIF(?, ''), NULLIF(?, ''), ?)";

        $stmt = mysqli_prepare($koneksi, $sql);

        if (!$stmt) {
            $error = 'Gagal menyiapkan proses penyimpanan: ' . mysqli_error($koneksi);
        } else {

            mysqli_stmt_bind_param(
                $stmt,
                'iiiissssss',
                $id_role,
                $id_universitas,
                $id_fakultas,
                $id_program_studi,
                $username,
                $password_hash,
                $nama_lengkap,
                $email,
                $no_hp,
                $status_aktif
            );

            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                $_SESSION['success'] = 'Pengguna berhasil ditambahkan.';
                header('Location: pengguna.php');
                exit;
            }

            $error = 'Gagal menambahkan pengguna: ' . mysqli_stmt_error($stmt);
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
<title>Tambah Pengguna - SIM Mahasiswa</title>
<link rel="stylesheet" href="style.css">

<style>
.user-form-card {
    width:100%;
    max-width:1000px;
    background:#fff;
    border:1px solid #e5e7eb;
    border-radius:14px;
    overflow:hidden;
    box-shadow:0 4px 18px rgba(0,0,0,.05);
}
.user-form-header {
    display:flex;
    align-items:center;
    gap:15px;
    padding:22px 25px;
    background:#fbfdfb;
    border-bottom:1px solid #e5e7eb;
}
.user-form-icon {
    width:46px;height:46px;min-width:46px;border-radius:10px;
    background:#ecfdf5;display:flex;align-items:center;justify-content:center;font-size:21px;
}
.user-form-header h2 { margin:0 0 4px;font-size:20px; }
.user-form-header p { margin:0;color:#6b7280;font-size:13px; }
.user-form { display:block !important;width:100% !important;box-sizing:border-box !important;padding:25px !important;margin:0 !important; }
.user-form-grid { display:grid !important;grid-template-columns:repeat(2,minmax(0,1fr));gap:20px;width:100%; }
.user-form-group { display:block !important;min-width:0;width:100%; }
.user-form-full { grid-column:1/-1; }
.user-form-group label { display:block !important;margin:0 0 8px !important;font-size:14px !important;font-weight:700 !important;color:#1f2937 !important; }
.user-form-control {
    display:block !important;width:100% !important;box-sizing:border-box !important;
    min-height:44px !important;padding:11px 13px !important;border:1px solid #cbd5e1 !important;
    border-radius:8px !important;background:#fff !important;color:#1f2937 !important;
    font:inherit !important;
}
.user-form-control:focus { outline:2px solid #bbf7d0 !important;border-color:#166534 !important; }
.user-form-help { display:block;margin-top:6px;color:#6b7280;font-size:12px;line-height:1.5; }
.user-form-readonly { background:#f3f4f6 !important;color:#4b5563 !important; }
.user-form-footer { display:flex;justify-content:flex-end;gap:10px;margin-top:25px;padding-top:20px;border-top:1px solid #e5e7eb; }
.user-form-footer .btn { min-width:120px; }
.user-scope-box { padding:15px;background:#f8fafc;border:1px solid #e5e7eb;border-radius:10px; }
.user-scope-title { font-size:13px;font-weight:700;margin-bottom:12px;color:#374151; }
.user-scope-note { font-size:12px;color:#6b7280;margin-top:8px;line-height:1.5; }
.password-note { color:#6b7280;font-size:12px;margin-top:6px;line-height:1.5; }
@media(max-width:700px) {
    .user-form-grid { grid-template-columns:1fr; }
    .user-form-full { grid-column:auto; }
    .user-form { padding:18px !important; }
    .user-form-header { padding:18px; }
    .user-form-footer { flex-direction:column-reverse; }
    .user-form-footer .btn { width:100%; }
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
        <h1>Tambah Pengguna</h1>
        <p>Buat akun pengguna dan tentukan scope kewenangannya.</p>
    </div>

    <a href="pengguna.php" class="btn btn-outline">← Kembali</a>
</div>

<?php if ($error !== ''): ?>
<div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="user-form-card">

<div class="user-form-header">
    <div class="user-form-icon">👤</div>
    <div>
        <h2>Informasi Pengguna</h2>
        <p>Field bertanda <strong>*</strong> wajib diisi.</p>
    </div>
</div>

<form method="post" class="user-form" autocomplete="off">
<?= csrf_field() ?>

<div class="user-form-grid">

<div class="user-form-group">
<label for="id_role">Role <span style="color:#dc2626">*</span></label>
<select class="user-form-control" id="id_role" name="id_role" required>
<option value="">-- Pilih Role --</option>
<?php while ($data_role_option = mysqli_fetch_assoc($roles)): ?>
<option
    value="<?= (int)$data_role_option['id_role'] ?>"
    data-kode="<?= htmlspecialchars($data_role_option['kode_role'], ENT_QUOTES, 'UTF-8') ?>"
    <?= ((string)($_POST['id_role'] ?? '') === (string)$data_role_option['id_role']) ? 'selected' : '' ?>
>
<?= htmlspecialchars($data_role_option['nama_role'], ENT_QUOTES, 'UTF-8') ?>
</option>
<?php endwhile; ?>
</select>
</div>

<div class="user-form-group">
<label for="username">Username <span style="color:#dc2626">*</span></label>
<input class="user-form-control" type="text" id="username" name="username" maxlength="100" required value="<?= htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
</div>

<div class="user-form-group">
<label for="password">Password <span style="color:#dc2626">*</span></label>
<input class="user-form-control" type="password" id="password" name="password" minlength="8" required>
<div class="password-note">Minimal 8 karakter. Password disimpan menggunakan password_hash().</div>
</div>

<div class="user-form-group">
<label for="nama_lengkap">Nama Lengkap <span style="color:#dc2626">*</span></label>
<input class="user-form-control" type="text" id="nama_lengkap" name="nama_lengkap" maxlength="200" required value="<?= htmlspecialchars($_POST['nama_lengkap'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
</div>

<div class="user-form-group">
<label for="email">Email</label>
<input class="user-form-control" type="email" id="email" name="email" maxlength="150" value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
</div>

<div class="user-form-group">
<label for="no_hp">No. HP</label>
<input class="user-form-control" type="text" id="no_hp" name="no_hp" maxlength="30" value="<?= htmlspecialchars($_POST['no_hp'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
</div>

<div class="user-form-group">
<label for="status_aktif">Status</label>
<select class="user-form-control" id="status_aktif" name="status_aktif">
<option value="Aktif" <?= (($_POST['status_aktif'] ?? 'Aktif') === 'Aktif') ? 'selected' : '' ?>>Aktif</option>
<option value="Tidak Aktif" <?= (($_POST['status_aktif'] ?? '') === 'Tidak Aktif') ? 'selected' : '' ?>>Tidak Aktif</option>
</select>
</div>

<div class="user-form-group user-form-full">
<div class="user-scope-box">
<div class="user-scope-title">Scope Kewenangan</div>

<div class="user-form-grid">

<div class="user-form-group" id="group_universitas">
<label for="id_universitas">Universitas</label>
<select class="user-form-control" id="id_universitas" name="id_universitas">
<option value="">-- Pilih Universitas --</option>
<?php while ($data_uni = mysqli_fetch_assoc($universitas)): ?>
<option value="<?= (int)$data_uni['id_universitas'] ?>" <?= ((string)($_POST['id_universitas'] ?? '') === (string)$data_uni['id_universitas']) ? 'selected' : '' ?>>
<?= htmlspecialchars($data_uni['kode_universitas'] . ' - ' . $data_uni['nama_universitas'], ENT_QUOTES, 'UTF-8') ?>
</option>
<?php endwhile; ?>
</select>
</div>

<div class="user-form-group" id="group_fakultas">
<label for="id_fakultas">Fakultas</label>
<select class="user-form-control" id="id_fakultas" name="id_fakultas">
<option value="">-- Pilih Fakultas --</option>
<?php while ($data_fak = mysqli_fetch_assoc($fakultas)): ?>
<option
    value="<?= (int)$data_fak['id_fakultas'] ?>"
    data-universitas="<?= (int)$data_fak['id_universitas'] ?>"
>
<?= htmlspecialchars($data_fak['kode_fakultas'] . ' - ' . $data_fak['nama_fakultas'], ENT_QUOTES, 'UTF-8') ?>
</option>
<?php endwhile; ?>
</select>
</div>

<div class="user-form-group" id="group_prodi">
<label for="id_program_studi">Program Studi</label>
<select class="user-form-control" id="id_program_studi" name="id_program_studi">
<option value="">-- Pilih Program Studi --</option>
<?php while ($data_ps = mysqli_fetch_assoc($prodi)): ?>
<option
    value="<?= (int)$data_ps['id_program_studi'] ?>"
    data-fakultas="<?= (int)$data_ps['id_fakultas'] ?>"
>
<?= htmlspecialchars($data_ps['kode_program_studi'] . ' - ' . $data_ps['nama_program_studi'], ENT_QUOTES, 'UTF-8') ?>
</option>
<?php endwhile; ?>
</select>
</div>

</div>

<div class="user-scope-note">
Scope akan mengikuti role. Operator Program Studi dan Mahasiswa menggunakan
Universitas → Fakultas → Program Studi; Dekanat menggunakan Universitas → Fakultas;
Rektorat menggunakan Universitas; Admin menggunakan Universitas.
</div>
</div>
</div>

</div>

<div class="user-form-footer">
<a href="pengguna.php" class="btn btn-outline">Batal</a>
<button type="submit" class="btn btn-primary">Simpan Pengguna</button>
</div>

</form>
</div>

</main>


<script>
(function () {
    const roleSelect = document.getElementById('id_role');
    const universitasSelect = document.getElementById('id_universitas');
    const fakultasSelect = document.getElementById('id_fakultas');
    const prodiSelect = document.getElementById('id_program_studi');

    if (!roleSelect || !universitasSelect || !fakultasSelect || !prodiSelect) {
        return;
    }

    const fakultasOptions = Array.from(fakultasSelect.options).map(option => ({
        value: option.value,
        text: option.text,
        universitas: option.dataset.universitas || ''
    }));

    const prodiOptions = Array.from(prodiSelect.options).map(option => ({
        value: option.value,
        text: option.text,
        fakultas: option.dataset.fakultas || ''
    }));

    function rebuildFakultas(selectedValue = '') {
        const uni = universitasSelect.value;

        fakultasSelect.innerHTML = '<option value="">-- Pilih Fakultas --</option>';

        fakultasOptions.forEach(item => {
            if (item.value && (!uni || item.universitas === uni)) {
                const option = document.createElement('option');
                option.value = item.value;
                option.textContent = item.text;
                option.dataset.universitas = item.universitas;

                if (item.value === selectedValue) {
                    option.selected = true;
                }

                fakultasSelect.appendChild(option);
            }
        });

        rebuildProdi(fakultasSelect.value, '');
    }

    function rebuildProdi(selectedFakultas = '', selectedValue = '') {
        prodiSelect.innerHTML = '<option value="">-- Pilih Program Studi --</option>';

        prodiOptions.forEach(item => {
            if (item.value && (!selectedFakultas || item.fakultas === selectedFakultas)) {
                const option = document.createElement('option');
                option.value = item.value;
                option.textContent = item.text;
                option.dataset.fakultas = item.fakultas;

                if (item.value === selectedValue) {
                    option.selected = true;
                }

                prodiSelect.appendChild(option);
            }
        });
    }

    function applyRoleRule() {
        const roleCode = roleSelect.options[roleSelect.selectedIndex]?.dataset.kode || '';

        const needUni = ['ADMIN', 'OPERATOR_PRODI', 'DEKANAT', 'REKTORAT', 'MAHASISWA'].includes(roleCode);
        const needFakultas = ['OPERATOR_PRODI', 'DEKANAT', 'MAHASISWA'].includes(roleCode);
        const needProdi = ['OPERATOR_PRODI', 'MAHASISWA'].includes(roleCode);

        document.getElementById('group_universitas').style.display = needUni ? '' : 'none';
        document.getElementById('group_fakultas').style.display = needFakultas ? '' : 'none';
        document.getElementById('group_prodi').style.display = needProdi ? '' : 'none';

        universitasSelect.required = needUni;
        fakultasSelect.required = needFakultas;
        prodiSelect.required = needProdi;

        if (!needUni) {
            universitasSelect.value = '';
            fakultasSelect.value = '';
            prodiSelect.value = '';
        } else if (!needFakultas) {
            fakultasSelect.value = '';
            prodiSelect.value = '';
        } else if (!needProdi) {
            prodiSelect.value = '';
        }
    }

    universitasSelect.addEventListener('change', function () {
        rebuildFakultas('');
    });

    fakultasSelect.addEventListener('change', function () {
        rebuildProdi(fakultasSelect.value, '');
    });

    roleSelect.addEventListener('change', applyRoleRule);

    // Data awal: filter Fakultas berdasarkan Universitas dan Prodi berdasarkan Fakultas.
    const initialFakultas = fakultasSelect.value;
    const initialProdi = prodiSelect.value;

    rebuildFakultas(initialFakultas);

    if (initialFakultas) {
        fakultasSelect.value = initialFakultas;
        rebuildProdi(initialFakultas, initialProdi);
    }

    applyRoleRule();
})();
</script>

</body>
</html>
