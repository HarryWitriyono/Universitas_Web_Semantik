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
        p.nama_lengkap,
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

function format_tanggal_profil(?string $tanggal): string
{
    if (!$tanggal || $tanggal === '0000-00-00') {
        return '-';
    }

    $bulan = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];

    $ts = strtotime($tanggal);
    return date('j', $ts) . ' ' . $bulan[(int)date('n', $ts)] . ' ' . date('Y', $ts);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Mahasiswa - SIM Mahasiswa</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .profile-card{width:100%;max-width:1000px;background:#fff;border:1px solid #e5e7eb;border-radius:14px;overflow:hidden;box-shadow:0 4px 18px rgba(0,0,0,.04)}
        .profile-header{padding:25px;background:#fbfdfb;border-bottom:1px solid #e5e7eb;display:flex;align-items:center;justify-content:space-between;gap:20px}
        .profile-title{display:flex;align-items:center;gap:15px}
        .profile-icon{width:52px;height:52px;border-radius:12px;background:#ecfdf5;display:flex;align-items:center;justify-content:center;font-size:25px}
        .profile-header h2{margin:0 0 5px;font-size:21px}.profile-header p{margin:0;color:#6b7280;font-size:13px}
        .profile-section{padding:0 25px}
        .profile-section-title{padding:20px 0 12px;font-size:14px;font-weight:800;color:#166534;border-bottom:1px solid #e5e7eb}
        .profile-grid{display:grid;grid-template-columns:repeat(2,1fr)}
        .profile-item{padding:16px 15px 16px 0;border-bottom:1px solid #f1f5f9}
        .profile-item:nth-child(even){padding-left:15px;padding-right:0}
        .profile-item label{display:block;color:#6b7280;font-size:12px;margin-bottom:6px}
        .profile-item strong,.profile-item span.value{display:block;font-size:14px;color:#1f2937;line-height:1.5}
        .profile-footer{padding:20px 25px;background:#fafafa;border-top:1px solid #e5e7eb;display:flex;justify-content:flex-end;gap:10px}
        .profile-note{margin:18px 25px 0;padding:12px 14px;border-radius:8px;background:#f0fdf4;border:1px solid #bbf7d0;color:#166534;font-size:12px;line-height:1.6}
        .profile-address{white-space:pre-line}
        .badge-status{display:inline-block;padding:5px 10px;border-radius:6px;background:#ecfdf5;color:#166534;font-size:12px;font-weight:700}
        @media(max-width:700px){.profile-header{align-items:flex-start;flex-direction:column}.profile-grid{grid-template-columns:1fr}.profile-item:nth-child(even){padding-left:0}.profile-footer{flex-direction:column}.profile-footer .btn{width:100%}}
    </style>
</head>
<body class="app-body">

<?php include __DIR__ . '/topbar.php'; ?>
<?php include __DIR__ . '/sideleftbar.php'; ?>

<main class="main-content">

    <div class="page-heading page-heading-flex">
        <div>
            <span class="eyebrow">DATA MAHASISWA</span>
            <h1>Profil Saya</h1>
            <p>Informasi pribadi dan akademik Anda.</p>
        </div>

        <a href="mahasiswa_profil_edit.php" class="btn btn-primary">Edit Profil</a>
    </div>

    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($_SESSION['success'], ENT_QUOTES, 'UTF-8') ?>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8') ?>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <div class="profile-card">

        <div class="profile-header">
            <div class="profile-title">
                <div class="profile-icon">🎓</div>
                <div>
                    <h2><?= htmlspecialchars($data['nama_mahasiswa'], ENT_QUOTES, 'UTF-8') ?></h2>
                    <p><?= htmlspecialchars($data['npm'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($data['nama_program_studi'], ENT_QUOTES, 'UTF-8') ?></p>
                </div>
            </div>

            <span class="badge-status">
                <?= htmlspecialchars($data['status_mahasiswa'], ENT_QUOTES, 'UTF-8') ?>
            </span>
        </div>

        <div class="profile-note">
            Data akademik seperti NPM, Program Studi, Tanggal Masuk, dan Status Mahasiswa
            dikelola oleh pihak akademik. Mahasiswa hanya dapat memperbarui data pribadi
            yang tersedia pada menu <strong>Edit Profil</strong>.
        </div>

        <div class="profile-section">
            <div class="profile-section-title">IDENTITAS AKADEMIK</div>

            <div class="profile-grid">
                <div class="profile-item">
                    <label>NPM</label>
                    <strong><?= htmlspecialchars($data['npm'], ENT_QUOTES, 'UTF-8') ?></strong>
                </div>

                <div class="profile-item">
                    <label>Username</label>
                    <strong><?= htmlspecialchars($data['username'], ENT_QUOTES, 'UTF-8') ?></strong>
                </div>

                <div class="profile-item">
                    <label>Program Studi</label>
                    <strong><?= htmlspecialchars($data['kode_program_studi'] . ' - ' . $data['nama_program_studi'], ENT_QUOTES, 'UTF-8') ?></strong>
                </div>

                <div class="profile-item">
                    <label>Jenjang</label>
                    <strong><?= htmlspecialchars($data['jenjang'], ENT_QUOTES, 'UTF-8') ?></strong>
                </div>

                <div class="profile-item">
                    <label>Fakultas</label>
                    <strong><?= htmlspecialchars($data['kode_fakultas'] . ' - ' . $data['nama_fakultas'], ENT_QUOTES, 'UTF-8') ?></strong>
                </div>

                <div class="profile-item">
                    <label>Universitas</label>
                    <strong><?= htmlspecialchars($data['kode_universitas'] . ' - ' . $data['nama_universitas'], ENT_QUOTES, 'UTF-8') ?></strong>
                </div>

                <div class="profile-item">
                    <label>Tanggal Masuk</label>
                    <span class="value"><?= htmlspecialchars(format_tanggal_profil($data['tanggal_masuk']), ENT_QUOTES, 'UTF-8') ?></span>
                </div>

                <div class="profile-item">
                    <label>Status Mahasiswa</label>
                    <span class="value"><?= htmlspecialchars($data['status_mahasiswa'], ENT_QUOTES, 'UTF-8') ?></span>
                </div>
            </div>
        </div>

        <div class="profile-section">
            <div class="profile-section-title">DATA PRIBADI</div>

            <div class="profile-grid">
                <div class="profile-item">
                    <label>Nama Lengkap</label>
                    <strong><?= htmlspecialchars($data['nama_mahasiswa'], ENT_QUOTES, 'UTF-8') ?></strong>
                </div>

                <div class="profile-item">
                    <label>Jenis Kelamin</label>
                    <span class="value"><?= $data['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan' ?></span>
                </div>

                <div class="profile-item">
                    <label>Tempat Lahir</label>
                    <span class="value"><?= htmlspecialchars($data['tempat_lahir'] ?: '-', ENT_QUOTES, 'UTF-8') ?></span>
                </div>

                <div class="profile-item">
                    <label>Tanggal Lahir</label>
                    <span class="value"><?= htmlspecialchars(format_tanggal_profil($data['tanggal_lahir']), ENT_QUOTES, 'UTF-8') ?></span>
                </div>

                <div class="profile-item">
                    <label>Email</label>
                    <span class="value"><?= htmlspecialchars($data['email'] ?: '-', ENT_QUOTES, 'UTF-8') ?></span>
                </div>

                <div class="profile-item">
                    <label>No. HP</label>
                    <span class="value"><?= htmlspecialchars($data['no_hp'] ?: '-', ENT_QUOTES, 'UTF-8') ?></span>
                </div>

                <div class="profile-item" style="grid-column:1/-1;">
                    <label>Alamat</label>
                    <span class="value profile-address"><?= htmlspecialchars($data['alamat'] ?: '-', ENT_QUOTES, 'UTF-8') ?></span>
                </div>
            </div>
        </div>

        <div class="profile-footer">
            <a href="dashboard.php" class="btn btn-outline">Kembali</a>
            <a href="mahasiswa_profil_edit.php" class="btn btn-primary">Edit Profil</a>
        </div>

    </div>

</main>
</body>
</html>
