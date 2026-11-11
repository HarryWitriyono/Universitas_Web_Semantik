<?php
/**
 * dashboard.php - SOA Consumer SIM Mahasiswa.
 * Tidak menggunakan koneksi.php maupun query database langsung.
 */

require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/api_client.php';

$role = (string) ($_SESSION['kode_role'] ?? '');
$namaRole = (string) ($_SESSION['nama_role'] ?? '');
$namaLengkap = (string) ($_SESSION['nama_lengkap'] ?? '');
$username = (string) ($_SESSION['username'] ?? '');
$idPengguna = (int) ($_SESSION['id_pengguna'] ?? 0);

$jumlahMahasiswa = 0;
$jumlahProdi = 0;
$jumlahFakultas = 0;
$prodiLabel = 'Seluruh Program Studi';
$apiError = '';

if ($idPengguna < 1) {
    // Session tidak lengkap: minta pengguna login ulang.
    $_SESSION = [];
    session_destroy();
    header('Location: login.php');
    exit;
}

// Minta data dashboard kepada SOA Server.
// Role dan scope akan diverifikasi ulang oleh apidashboard.php dari database pusat.
$api = soa_request('apidashboard.php', [
    'id_pengguna' => $idPengguna
]);

if ($api['error'] !== null) {
    error_log('SOA dashboard connection error: ' . $api['error']);
    $apiError = 'Layanan dashboard tidak dapat dihubungi. Periksa SOA Server.';
} elseif (
    $api['http_code'] !== 200 ||
    empty($api['data']['success']) ||
    !isset($api['data']['data']['statistik'])
) {
    $apiError = $api['data']['message'] ?? 'Data dashboard gagal dimuat.';
} else {
    $data = $api['data']['data'];
    $statistik = $data['statistik'];

    // Sinkronkan informasi role dari server pusat.
    if (!empty($data['pengguna']['kode_role'])) {
        $role = (string) $data['pengguna']['kode_role'];
        $_SESSION['kode_role'] = $role;
    }
    if (!empty($data['pengguna']['nama_role'])) {
        $namaRole = (string) $data['pengguna']['nama_role'];
        $_SESSION['nama_role'] = $namaRole;
    }

    $jumlahMahasiswa = (int) ($statistik['jumlah_mahasiswa'] ?? 0);
    $jumlahProdi = (int) ($statistik['jumlah_prodi'] ?? 0);
    $jumlahFakultas = (int) ($statistik['jumlah_fakultas'] ?? 0);
    $prodiLabel = (string) ($data['scope_label'] ?? $prodiLabel);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - SIM Mahasiswa SOA</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="app-body">

<?php include __DIR__ . '/topbar.php'; ?>
<?php include __DIR__ . '/sideleftbar.php'; ?>

<main class="main-content">
    <div class="page-heading">
        <div>
            <span class="eyebrow">DASHBOARD · SOA CONSUMER</span>
            <h1>Selamat datang, <?= htmlspecialchars($namaLengkap, ENT_QUOTES, 'UTF-8') ?></h1>
            <p>
                <?= htmlspecialchars($namaRole, ENT_QUOTES, 'UTF-8') ?>
                ·
                <?= htmlspecialchars($prodiLabel, ENT_QUOTES, 'UTF-8') ?>
            </p>
        </div>
    </div>

    <?php if ($apiError !== ''): ?>
        <div class="alert alert-danger" role="alert">
            <strong>Data dashboard belum tersedia.</strong><br>
            <?= htmlspecialchars($apiError, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <?php if ($role === 'MAHASISWA'): ?>
        <div class="welcome-card">
            <div>
                <span class="eyebrow">PORTAL MAHASISWA</span>
                <h2>Data akademik Anda</h2>
                <p>
                    Anda dapat melihat data mahasiswa yang terdaftar.
                    NPM dan tanggal masuk merupakan data yang dikelola oleh
                    pihak yang memiliki kewenangan.
                </p>
            </div>
            <a href="mahasiswa_profil.php" class="btn btn-primary">Lihat Profil</a>
        </div>
    <?php endif; ?>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">👨‍🎓</div>
            <div>
                <span>Total Mahasiswa</span>
                <strong><?= number_format($jumlahMahasiswa, 0, ',', '.') ?></strong>
            </div>
        </div>

        <?php if ($role !== 'MAHASISWA'): ?>
            <div class="stat-card">
                <div class="stat-icon">📚</div>
                <div>
                    <span>Program Studi</span>
                    <strong><?= number_format($jumlahProdi, 0, ',', '.') ?></strong>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">🏛</div>
                <div>
                    <span>Fakultas</span>
                    <strong><?= number_format($jumlahFakultas, 0, ',', '.') ?></strong>
                </div>
            </div>
        <?php endif; ?>

        <div class="stat-card">
            <div class="stat-icon">🔐</div>
            <div>
                <span>Hak Akses</span>
                <strong class="role-value"><?= htmlspecialchars($namaRole, ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
        </div>
    </div>

    <div class="dashboard-panel">
        <div class="panel-heading">
            <h2>Informasi Sistem</h2>
        </div>
        <div class="info-grid">
            <div>
                <span>Universitas</span>
                <strong>Universitas Muhammadiyah Bengkulu</strong>
            </div>
            <div>
                <span>Role</span>
                <strong><?= htmlspecialchars($namaRole, ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
            <div>
                <span>Username</span>
                <strong><?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
            <div>
                <span>Status</span>
                <strong class="status-active">Aktif</strong>
            </div>
        </div>
    </div>
</main>
</body>
</html>
