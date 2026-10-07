<?php
require_once __DIR__ . '/mahasiswa_rbac_helper.php';

$user = current_pengguna($koneksi);
if (!$user || !in_array($user['kode_role'], ['ADMIN','OPERATOR_PRODI','DEKANAT','REKTORAT'], true)) {
    header('Location: dashboard.php?pesan=akses_ditolak');
    exit;
}

$id = (int)($_GET['id'] ?? 0);
$mhs = $id > 0 ? get_mahasiswa_by_id($koneksi, $id) : null;

if (!$mhs || !mahasiswa_scope_allowed($mhs, $user)) {
    header('Location: mahasiswa.php?pesan=akses_ditolak');
    exit;
}

audit_mahasiswa($koneksi, (int)$user['id_pengguna'], 'VIEW', $id, null, null);

$judul_halaman = 'Detail Mahasiswa';
include 'topbar.php';
include 'sideleftbar.php';
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-1">Detail Mahasiswa</h4>
            <p class="text-muted mb-0">Informasi mahasiswa sesuai kewenangan pengguna.</p>
        </div>
        <a href="mahasiswa.php" class="btn btn-light">Kembali</a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-md-3">NPM</dt><dd class="col-md-9"><?= h_mhs($mhs['npm']) ?></dd>
                <dt class="col-md-3">Nama</dt><dd class="col-md-9"><?= h_mhs($mhs['nama_mahasiswa']) ?></dd>
                <dt class="col-md-3">Jenis Kelamin</dt><dd class="col-md-9"><?= $mhs['jenis_kelamin']==='L'?'Laki-laki':'Perempuan' ?></dd>
                <dt class="col-md-3">Tempat/Tanggal Lahir</dt><dd class="col-md-9"><?= h_mhs(($mhs['tempat_lahir'] ?? '').' / '.($mhs['tanggal_lahir'] ?? '-')) ?></dd>
                <dt class="col-md-3">Universitas</dt><dd class="col-md-9"><?= h_mhs($mhs['kode_universitas'].' - '.$mhs['nama_universitas']) ?></dd>
                <dt class="col-md-3">Fakultas</dt><dd class="col-md-9"><?= h_mhs($mhs['kode_fakultas'].' - '.$mhs['nama_fakultas']) ?></dd>
                <dt class="col-md-3">Program Studi</dt><dd class="col-md-9"><?= h_mhs($mhs['kode_program_studi'].' - '.$mhs['nama_program_studi']) ?></dd>
                <dt class="col-md-3">Tanggal Masuk</dt><dd class="col-md-9"><?= h_mhs($mhs['tanggal_masuk']) ?></dd>
                <dt class="col-md-3">Status</dt><dd class="col-md-9"><?= h_mhs($mhs['status_mahasiswa']) ?></dd>
                <dt class="col-md-3">Alamat</dt><dd class="col-md-9"><?= nl2br(h_mhs($mhs['alamat'] ?? '')) ?></dd>
            </dl>
        </div>
    </div>
</div>
<?php include 'footer.php'; ?>
