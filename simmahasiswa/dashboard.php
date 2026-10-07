<?php
require_once 'auth_check.php';
require_once 'koneksi.php';

$role = $_SESSION['kode_role'] ?? '';
$namaRole = $_SESSION['nama_role'] ?? '';
$idProdi = isset($_SESSION['id_program_studi']) ? (int)$_SESSION['id_program_studi'] : 0;
$idFakultas = isset($_SESSION['id_fakultas']) ? (int)$_SESSION['id_fakultas'] : 0;
$idUniversitas = isset($_SESSION['id_universitas']) ? (int)$_SESSION['id_universitas'] : 0;

$jumlahMahasiswa = 0;
$jumlahProdi = 0;
$jumlahFakultas = 0;

/*
 * Statistik dashboard mengikuti scope RBAC:
 *
 * ADMIN          : seluruh universitas
 * OPERATOR_PRODI : hanya Program Studi yang menjadi scope
 * DEKANAT        : hanya Fakultas yang menjadi scope
 * REKTORAT       : seluruh universitas yang menjadi scope
 * MAHASISWA      : hanya data sendiri
 */

/* =========================================================
 * TOTAL MAHASISWA
 * ========================================================= */
if ($role === 'MAHASISWA') {

    $stmt = mysqli_prepare(
        $koneksi,
        "SELECT COUNT(*) AS total
         FROM mahasiswa
         WHERE id_pengguna = ?"
    );

    if ($stmt) {
        $idPengguna = (int)($_SESSION['id_pengguna'] ?? 0);
        mysqli_stmt_bind_param($stmt, 'i', $idPengguna);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res);
        $jumlahMahasiswa = (int)($row['total'] ?? 0);
        mysqli_stmt_close($stmt);
    }

} elseif ($role === 'OPERATOR_PRODI') {

    $stmt = mysqli_prepare(
        $koneksi,
        "SELECT COUNT(*) AS total
         FROM mahasiswa
         WHERE id_program_studi = ?"
    );

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $idProdi);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res);
        $jumlahMahasiswa = (int)($row['total'] ?? 0);
        mysqli_stmt_close($stmt);
    }

} elseif ($role === 'DEKANAT') {

    $stmt = mysqli_prepare(
        $koneksi,
        "SELECT COUNT(*) AS total
         FROM mahasiswa m
         INNER JOIN program_studi ps
             ON ps.id_program_studi = m.id_program_studi
         WHERE ps.id_fakultas = ?"
    );

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $idFakultas);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res);
        $jumlahMahasiswa = (int)($row['total'] ?? 0);
        mysqli_stmt_close($stmt);
    }

} elseif ($role === 'REKTORAT') {

    $stmt = mysqli_prepare(
        $koneksi,
        "SELECT COUNT(*) AS total
         FROM mahasiswa m
         INNER JOIN program_studi ps
             ON ps.id_program_studi = m.id_program_studi
         INNER JOIN fakultas f
             ON f.id_fakultas = ps.id_fakultas
         WHERE f.id_universitas = ?"
    );

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $idUniversitas);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res);
        $jumlahMahasiswa = (int)($row['total'] ?? 0);
        mysqli_stmt_close($stmt);
    }

} else {
    // ADMIN
    $res = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM mahasiswa");

    if ($res) {
        $row = mysqli_fetch_assoc($res);
        $jumlahMahasiswa = (int)($row['total'] ?? 0);
    }
}


/* =========================================================
 * TOTAL PROGRAM STUDI DAN FAKULTAS
 * ========================================================= */
if ($role === 'ADMIN') {

    // ADMIN melihat seluruh struktur akademik.
    $res = mysqli_query(
        $koneksi,
        "SELECT COUNT(*) AS total
         FROM program_studi
         WHERE status_aktif = 'Aktif'"
    );

    if ($res) {
        $row = mysqli_fetch_assoc($res);
        $jumlahProdi = (int)($row['total'] ?? 0);
    }

    $res = mysqli_query(
        $koneksi,
        "SELECT COUNT(*) AS total
         FROM fakultas"
    );

    if ($res) {
        $row = mysqli_fetch_assoc($res);
        $jumlahFakultas = (int)($row['total'] ?? 0);
    }

} elseif ($role === 'OPERATOR_PRODI') {

    /*
     * Operator Prodi memiliki tepat satu scope Prodi.
     * Karena itu:
     * Program Studi = 1 jika Prodi aktif
     * Fakultas       = 1 jika Prodi memiliki Fakultas
     */
    if ($idProdi > 0) {

        $stmt = mysqli_prepare(
            $koneksi,
            "SELECT
                COUNT(*) AS jumlah_prodi,
                COUNT(DISTINCT f.id_fakultas) AS jumlah_fakultas
             FROM program_studi ps
             INNER JOIN fakultas f
                 ON f.id_fakultas = ps.id_fakultas
             WHERE ps.id_program_studi = ?
               AND ps.status_aktif = 'Aktif'"
        );

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'i', $idProdi);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $row = mysqli_fetch_assoc($res);

            $jumlahProdi = (int)($row['jumlah_prodi'] ?? 0);
            $jumlahFakultas = (int)($row['jumlah_fakultas'] ?? 0);

            mysqli_stmt_close($stmt);
        }
    }

} elseif ($role === 'DEKANAT') {

    // Dekanat melihat seluruh Prodi aktif di Fakultasnya dan 1 Fakultas.
    if ($idFakultas > 0) {

        $stmt = mysqli_prepare(
            $koneksi,
            "SELECT
                COUNT(*) AS jumlah_prodi,
                COUNT(DISTINCT id_fakultas) AS jumlah_fakultas
             FROM program_studi
             WHERE id_fakultas = ?
               AND status_aktif = 'Aktif'"
        );

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'i', $idFakultas);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $row = mysqli_fetch_assoc($res);

            $jumlahProdi = (int)($row['jumlah_prodi'] ?? 0);
            $jumlahFakultas = (int)($row['jumlah_fakultas'] ?? 0);

            mysqli_stmt_close($stmt);
        }
    }

} elseif ($role === 'REKTORAT') {

    // Rektorat melihat struktur akademik dalam universitasnya.
    if ($idUniversitas > 0) {

        $stmt = mysqli_prepare(
            $koneksi,
            "SELECT
                COUNT(DISTINCT ps.id_program_studi) AS jumlah_prodi,
                COUNT(DISTINCT f.id_fakultas) AS jumlah_fakultas
             FROM fakultas f
             LEFT JOIN program_studi ps
                 ON ps.id_fakultas = f.id_fakultas
                AND ps.status_aktif = 'Aktif'
             WHERE f.id_universitas = ?"
        );

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'i', $idUniversitas);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $row = mysqli_fetch_assoc($res);

            $jumlahProdi = (int)($row['jumlah_prodi'] ?? 0);
            $jumlahFakultas = (int)($row['jumlah_fakultas'] ?? 0);

            mysqli_stmt_close($stmt);
        }
    }
}


/* =========================================================
 * LABEL SCOPE
 * ========================================================= */
$prodiLabel = 'Seluruh Program Studi';

if ($role === 'OPERATOR_PRODI' && $idProdi > 0) {

    $stmt = mysqli_prepare(
        $koneksi,
        "SELECT nama_program_studi
         FROM program_studi
         WHERE id_program_studi = ?"
    );

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $idProdi);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        if ($row = mysqli_fetch_assoc($res)) {
            $prodiLabel = $row['nama_program_studi'];
        }

        mysqli_stmt_close($stmt);
    }

} elseif ($role === 'DEKANAT' && $idFakultas > 0) {

    $stmt = mysqli_prepare(
        $koneksi,
        "SELECT nama_fakultas
         FROM fakultas
         WHERE id_fakultas = ?"
    );

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $idFakultas);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        if ($row = mysqli_fetch_assoc($res)) {
            $prodiLabel = $row['nama_fakultas'];
        }

        mysqli_stmt_close($stmt);
    }

} elseif ($role === 'REKTORAT' && $idUniversitas > 0) {

    $stmt = mysqli_prepare(
        $koneksi,
        "SELECT nama_universitas
         FROM universitas
         WHERE id_universitas = ?"
    );

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $idUniversitas);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        if ($row = mysqli_fetch_assoc($res)) {
            $prodiLabel = $row['nama_universitas'];
        }

        mysqli_stmt_close($stmt);
    }

} elseif ($role === 'MAHASISWA') {
    $prodiLabel = 'Portal Mahasiswa';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - SIM Mahasiswa</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="app-body">

<?php include 'topbar.php'; ?>
<?php include 'sideleftbar.php'; ?>

<main class="main-content">
    <div class="page-heading">
        <div>
            <span class="eyebrow">DASHBOARD</span>
            <h1>Selamat datang, <?= htmlspecialchars($_SESSION['nama_lengkap'] ?? '') ?></h1>
            <p><?= htmlspecialchars($namaRole) ?> · <?= htmlspecialchars($prodiLabel) ?></p>
        </div>
    </div>

    <?php if ($role === 'MAHASISWA'): ?>
        <div class="welcome-card">
            <div>
                <span class="eyebrow">PORTAL MAHASISWA</span>
                <h2>Data akademik Anda</h2>
                <p>
                    Anda dapat melihat rekord mahasiswa yang terdaftar.
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
                <strong class="role-value"><?= htmlspecialchars($namaRole) ?></strong>
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
                <strong><?= htmlspecialchars($namaRole) ?></strong>
            </div>
            <div>
                <span>Username</span>
                <strong><?= htmlspecialchars($_SESSION['username'] ?? '') ?></strong>
            </div>
            <div>
                <span>Status</span>
                <strong class="status-active">Aktif</strong>
            </div>
        </div>
    </div>
</main>

<script>
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('show');
}
</script>
</body>
</html>
