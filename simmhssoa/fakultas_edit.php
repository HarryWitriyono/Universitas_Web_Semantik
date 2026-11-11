<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/api_client.php';

wajib_role(['ADMIN']);

function ambil_daftar_universitas(): array
{
    $api = soa_request('apiuniversitas.php', [
        'action' => 'list',
        'id_pengguna' => (int) ($_SESSION['id_pengguna'] ?? 0)
    ]);

    if ($api['error'] !== null) {
        throw new RuntimeException('SOA Server tidak dapat dihubungi saat mengambil universitas.');
    }
    if ($api['http_code'] !== 200 || empty($api['data']['success'])) {
        throw new RuntimeException($api['data']['message'] ?? 'Gagal mengambil daftar universitas.');
    }

    return $api['data']['data']['universitas'] ?? [];
}

$id_fakultas = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id_fakultas || $id_fakultas < 1) {
    $_SESSION['error'] = 'ID fakultas tidak valid.';
    header('Location: fakultas.php');
    exit;
}

$error = '';
try {
    $daftar_universitas = ambil_daftar_universitas();
} catch (RuntimeException $e) {
    $_SESSION['error'] = $e->getMessage();
    header('Location: fakultas.php');
    exit;
}

$api = soa_request('apifakultas.php', [
    'action' => 'detail',
    'id_pengguna' => (int) ($_SESSION['id_pengguna'] ?? 0),
    'id_fakultas' => (int) $id_fakultas
]);

if ($api['error'] !== null) {
    $_SESSION['error'] = 'SOA Server tidak dapat dihubungi.';
    header('Location: fakultas.php');
    exit;
}
if ($api['http_code'] !== 200 || empty($api['data']['success'])) {
    $_SESSION['error'] = $api['data']['message'] ?? 'Data fakultas tidak ditemukan.';
    header('Location: fakultas.php');
    exit;
}

$data_fakultas = $api['data']['data']['fakultas'] ?? [];
if (!$data_fakultas) {
    $_SESSION['error'] = 'Data fakultas tidak ditemukan.';
    header('Location: fakultas.php');
    exit;
}

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
        $apiUpdate = soa_request('apifakultas.php', [
            'action' => 'update',
            'id_pengguna' => (int) ($_SESSION['id_pengguna'] ?? 0),
            'id_fakultas' => (int) $id_fakultas,
            'id_universitas' => (int) $id_universitas,
            'kode_fakultas' => $kode_fakultas,
            'nama_fakultas' => $nama_fakultas
        ]);

        if ($apiUpdate['error'] !== null) {
            $error = 'SOA Server tidak dapat dihubungi.';
        } elseif ($apiUpdate['http_code'] !== 200 || empty($apiUpdate['data']['success'])) {
            $error = $apiUpdate['data']['message'] ?? 'Gagal memperbarui fakultas.';
        } else {
            $_SESSION['success'] = $apiUpdate['data']['message'] ?? 'Data fakultas berhasil diperbarui.';
            header('Location: fakultas.php');
            exit;
        }
    }

    $data_fakultas['id_universitas'] = $id_universitas ?: '';
    $data_fakultas['kode_fakultas'] = $kode_fakultas;
    $data_fakultas['nama_fakultas'] = $nama_fakultas;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Fakultas - SIM Mahasiswa</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .master-form-card{width:100%;max-width:760px;background:#fff;border:1px solid #e5e7eb;border-radius:14px;overflow:hidden;box-shadow:0 4px 18px rgba(0,0,0,.05)}
        .master-form-header{padding:22px 25px;background:#fbfdfb;border-bottom:1px solid #e5e7eb}.master-form-header h2{margin:0 0 4px;font-size:20px}.master-form-header p{margin:0;color:#6b7280;font-size:13px}
        .master-form{padding:25px}.master-form-group{margin-bottom:20px}.master-form-group label{display:block;margin-bottom:8px;font-size:14px;font-weight:700;color:#1f2937}
        .master-form-group input,.master-form-group select{width:100%;box-sizing:border-box;padding:11px 13px;border:1px solid #d1d5db;border-radius:8px;font:inherit;background:#fff}
        .master-form-group input:focus,.master-form-group select:focus{outline:none;border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,.1)}.form-help{margin-top:6px;font-size:12px;color:#6b7280}.form-actions{display:flex;gap:10px;align-items:center;padding-top:5px}
    </style>
</head>
<body class="app-body">
<?php include __DIR__ . '/topbar.php'; ?>
<?php include __DIR__ . '/sideleftbar.php'; ?>
<main class="main-content">
    <div class="page-heading">
        <span class="eyebrow">MASTER DATA</span>
        <h1>Edit Fakultas</h1>
        <p>Perbarui data fakultas.</p>
    </div>

    <?php if ($error !== ''): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="master-form-card">
        <div class="master-form-header">
            <h2>Form Fakultas</h2>
            <p>Perubahan kode harus tetap unik dalam universitas.</p>
        </div>
        <form method="post" class="master-form">
            <?= csrf_field() ?>

            <div class="master-form-group">
                <label for="id_universitas">Universitas *</label>
                <select name="id_universitas" id="id_universitas" required>
                    <option value="">-- Pilih Universitas --</option>
                    <?php foreach ($daftar_universitas as $data_universitas): ?>
                        <option
                            value="<?= (int)$data_universitas['id_universitas'] ?>"
                            <?= ((int)$data_fakultas['id_universitas'] === (int)$data_universitas['id_universitas']) ? 'selected' : '' ?>
                        >
                            <?= htmlspecialchars($data_universitas['kode_universitas'] . ' - ' . $data_universitas['nama_universitas'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="master-form-group">
                <label for="kode_fakultas">Kode Fakultas *</label>
                <input type="text" name="kode_fakultas" id="kode_fakultas" maxlength="20" value="<?= htmlspecialchars($data_fakultas['kode_fakultas'], ENT_QUOTES, 'UTF-8') ?>" required>
                <div class="form-help">Contoh: FT, FEB, FIKES.</div>
            </div>

            <div class="master-form-group">
                <label for="nama_fakultas">Nama Fakultas *</label>
                <input type="text" name="nama_fakultas" id="nama_fakultas" maxlength="200" value="<?= htmlspecialchars($data_fakultas['nama_fakultas'], ENT_QUOTES, 'UTF-8') ?>" required>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                <a href="fakultas.php" class="btn btn-outline">Batal</a>
            </div>
        </form>
    </div>
</main>
</body>
</html>
