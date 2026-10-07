<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/koneksi.php';

wajib_role(['MAHASISWA']);

$id_pengguna = (int)($_SESSION['id_pengguna'] ?? 0);
if ($id_pengguna <= 0) {
    header('Location: login.php');
    exit;
}

$sql = "SELECT
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
        LIMIT 1";

$stmt = mysqli_prepare($koneksi, $sql);
mysqli_stmt_bind_param($stmt, 'i', $id_pengguna);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$mhs = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$mhs) {
    $_SESSION['error'] = 'Data mahasiswa yang terhubung dengan akun tidak ditemukan.';
    header('Location: dashboard.php');
    exit;
}

function e_profil($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function tanggal_id($tanggal): string
{
    if (!$tanggal || $tanggal === '0000-00-00') return '-';
    $bulan = [1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    $ts = strtotime($tanggal);
    if (!$ts) return e_profil($tanggal);
    return date('j', $ts).' '.$bulan[(int)date('n', $ts)].' '.date('Y', $ts);
}

$judul_halaman = 'Profil Saya';
include __DIR__ . '/topbar.php';
include __DIR__ . '/sideleftbar.php';
?>
<main class="main-content">
    <div class="page-heading page-heading-flex">
        <div>
            <span class="eyebrow">AKUN MAHASISWA</span>
            <h1>Profil Saya</h1>
            <p>Informasi akun, data akademik, dan data pribadi mahasiswa.</p>
        </div>
        <a href="mahasiswa_profil_edit.php" class="btn btn-primary">✎ Edit Profil</a>
    </div>

    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?= e_profil($_SESSION['success']) ?>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <?= e_profil($_SESSION['error']) ?>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <div class="dashboard-panel" style="margin-bottom:25px;">
        <div style="padding:22px;">
            <div style="display:flex;justify-content:space-between;gap:20px;align-items:flex-start;flex-wrap:wrap;">
                <div>
                    <div style="font-size:12px;font-weight:700;letter-spacing:.08em;opacity:.65;">MAHASISWA</div>
                    <h2 style="margin:6px 0 4px;"><?= e_profil($mhs['nama_mahasiswa']) ?></h2>
                    <div style="font-size:14px;opacity:.75;">NPM <?= e_profil($mhs['npm']) ?></div>
                </div>
                <div>
                    <span class="badge"><?= e_profil($mhs['status_mahasiswa']) ?></span>
                </div>
            </div>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:25px;">
        <div class="dashboard-panel">
            <div style="padding:20px 22px;border-bottom:1px solid #e5e7eb;font-weight:700;">Data Akademik</div>
            <div style="padding:22px;">
                <div style="display:grid;grid-template-columns:145px 1fr;gap:13px 18px;font-size:14px;">
                    <strong>NPM</strong><span><?= e_profil($mhs['npm']) ?></span>
                    <strong>Program Studi</strong><span><?= e_profil($mhs['kode_program_studi'].' - '.$mhs['nama_program_studi']) ?></span>
                    <strong>Jenjang</strong><span><?= e_profil($mhs['jenjang']) ?></span>
                    <strong>Fakultas</strong><span><?= e_profil($mhs['kode_fakultas'].' - '.$mhs['nama_fakultas']) ?></span>
                    <strong>Universitas</strong><span><?= e_profil($mhs['kode_universitas'].' - '.$mhs['nama_universitas']) ?></span>
                    <strong>Tanggal Masuk</strong><span><?= tanggal_id($mhs['tanggal_masuk']) ?></span>
                    <strong>Status</strong><span><?= e_profil($mhs['status_mahasiswa']) ?></span>
                </div>
            </div>
        </div>

        <div class="dashboard-panel">
            <div style="padding:20px 22px;border-bottom:1px solid #e5e7eb;font-weight:700;">Data Pribadi</div>
            <div style="padding:22px;">
                <div style="display:grid;grid-template-columns:145px 1fr;gap:13px 18px;font-size:14px;">
                    <strong>Nama</strong><span><?= e_profil($mhs['nama_mahasiswa']) ?></span>
                    <strong>Jenis Kelamin</strong><span><?= $mhs['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan' ?></span>
                    <strong>Tempat Lahir</strong><span><?= e_profil($mhs['tempat_lahir'] ?: '-') ?></span>
                    <strong>Tanggal Lahir</strong><span><?= tanggal_id($mhs['tanggal_lahir']) ?></span>
                    <strong>Email</strong><span><?= e_profil($mhs['email'] ?: '-') ?></span>
                    <strong>No. HP</strong><span><?= e_profil($mhs['no_hp'] ?: '-') ?></span>
                    <strong>Alamat</strong><span><?= nl2br(e_profil($mhs['alamat'] ?: '-')) ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="dashboard-panel" style="margin-top:25px;">
        <div style="padding:20px 22px;border-bottom:1px solid #e5e7eb;font-weight:700;">Informasi Akun</div>
        <div style="padding:22px;display:grid;grid-template-columns:145px 1fr;gap:13px 18px;font-size:14px;">
            <strong>Username</strong><span><?= e_profil($mhs['username']) ?></span>
            <strong>Hak Akses</strong><span>MAHASISWA</span>
        </div>
    </div>
</main>
