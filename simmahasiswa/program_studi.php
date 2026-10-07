<?php

require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/koneksi.php';

wajib_role(['ADMIN']);

/*
 * Ambil data Program Studi
 * sekaligus Fakultas dan Universitas.
 */
$sql = "SELECT
            ps.id_program_studi,
            ps.kode_program_studi,
            ps.nama_program_studi,
            ps.jenjang,
            ps.status_aktif,

            f.id_fakultas,
            f.kode_fakultas,
            f.nama_fakultas,

            u.id_universitas,
            u.kode_universitas,
            u.nama_universitas,

            (
                SELECT COUNT(*)
                FROM mahasiswa m
                WHERE m.id_program_studi = ps.id_program_studi
            ) AS jumlah_mahasiswa

        FROM program_studi ps

        INNER JOIN fakultas f
            ON f.id_fakultas = ps.id_fakultas

        INNER JOIN universitas u
            ON u.id_universitas = f.id_universitas

        ORDER BY
            u.nama_universitas ASC,
            f.nama_fakultas ASC,
            ps.nama_program_studi ASC";

$result = mysqli_query($koneksi, $sql);

if (!$result) {
    die(
        'Gagal mengambil data program studi: ' .
        htmlspecialchars(
            mysqli_error($koneksi),
            ENT_QUOTES,
            'UTF-8'
        )
    );
}

?>
<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Program Studi - SIM Mahasiswa</title>

    <link
        rel="stylesheet"
        href="style.css"
    >

</head>

<body class="app-body">

<?php include __DIR__ . '/topbar.php'; ?>

<?php include __DIR__ . '/sideleftbar.php'; ?>


<main class="main-content">

    <!-- HEADER HALAMAN -->
    <div class="page-heading page-heading-flex">

        <div>

            <span class="eyebrow">
                MASTER DATA
            </span>

            <h1>
                Program Studi
            </h1>

            <p>
                Kelola data program studi berdasarkan fakultas.
            </p>

        </div>


        <a
            href="program_studi_tambah.php"
            class="btn btn-primary"
        >
            + Tambah Program Studi
        </a>

    </div>


    <!-- PESAN SUCCESS -->
    <?php if (!empty($_SESSION['success'])): ?>

        <div class="alert alert-success">

            <?= htmlspecialchars(
                $_SESSION['success'],
                ENT_QUOTES,
                'UTF-8'
            ) ?>

        </div>

        <?php unset($_SESSION['success']); ?>

    <?php endif; ?>


    <!-- PESAN ERROR -->
    <?php if (!empty($_SESSION['error'])): ?>

        <div class="alert alert-danger">

            <?= htmlspecialchars(
                $_SESSION['error'],
                ENT_QUOTES,
                'UTF-8'
            ) ?>

        </div>

        <?php unset($_SESSION['error']); ?>

    <?php endif; ?>


    <!-- DATA PROGRAM STUDI -->
    <div class="dashboard-panel">

        <div class="table-responsive">

            <table class="data-table">

                <thead>

                    <tr>

                        <th width="60">
                            No.
                        </th>

                        <th width="120">
                            Kode
                        </th>

                        <th>
                            Nama Program Studi
                        </th>

                        <th>
                            Fakultas
                        </th>

                        <th>
                            Universitas
                        </th>

                        <th width="100">
                            Jenjang
                        </th>

                        <th width="110">
                            Mahasiswa
                        </th>

                        <th width="110">
                            Status
                        </th>

                        <th width="170">
                            Aksi
                        </th>

                    </tr>

                </thead>


                <tbody>

                <?php if (mysqli_num_rows($result) > 0): ?>

                    <?php $nomor = 1; ?>


                    <?php while ($data_prodi = mysqli_fetch_assoc($result)): ?>

                        <tr>

                            <!-- NOMOR -->
                            <td>
                                <?= $nomor++ ?>
                            </td>


                            <!-- KODE -->
                            <td>

                                <span class="badge">

                                    <?= htmlspecialchars(
                                        $data_prodi['kode_program_studi'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </span>

                            </td>


                            <!-- NAMA PROGRAM STUDI -->
                            <td>

                                <strong>

                                    <?= htmlspecialchars(
                                        $data_prodi['nama_program_studi'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </strong>

                            </td>


                            <!-- FAKULTAS -->
                            <td>

                                <?= htmlspecialchars(
                                    $data_prodi['nama_fakultas'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                                <small
                                    style="display:block;color:#6b7280;"
                                >

                                    <?= htmlspecialchars(
                                        $data_prodi['kode_fakultas'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </small>

                            </td>


                            <!-- UNIVERSITAS -->
                            <td>

                                <?= htmlspecialchars(
                                    $data_prodi['nama_universitas'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                                <small
                                    style="display:block;color:#6b7280;"
                                >

                                    <?= htmlspecialchars(
                                        $data_prodi['kode_universitas'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </small>

                            </td>


                            <!-- JENJANG -->
                            <td>

                                <span class="badge">

                                    <?= htmlspecialchars(
                                        $data_prodi['jenjang'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </span>

                            </td>


                            <!-- JUMLAH MAHASISWA -->
                            <td>

                                <?= (int) $data_prodi['jumlah_mahasiswa'] ?>

                            </td>


                            <!-- STATUS -->
                            <td>

                                <?php if (
                                    $data_prodi['status_aktif'] === 'Aktif'
                                ): ?>

                                    <span class="badge">
                                        Aktif
                                    </span>

                                <?php else: ?>

                                    <span
                                        class="badge"
                                        style="opacity:0.6;"
                                    >
                                        Tidak Aktif
                                    </span>

                                <?php endif; ?>

                            </td>


                            <!-- AKSI -->
                            <td>

                                <div class="action-group">

                                    <a
                                        href="program_studi_edit.php?id=<?= (int) $data_prodi['id_program_studi'] ?>"
                                        class="btn btn-sm btn-outline"
                                    >
                                        Edit
                                    </a>


                                    <form
                                        action="program_studi_hapus.php"
                                        method="post"
                                        onsubmit="return confirm('Hapus program studi ini?');"
                                    >

                                        <?= csrf_field() ?>


                                        <input
                                            type="hidden"
                                            name="id_program_studi"
                                            value="<?= (int) $data_prodi['id_program_studi'] ?>"
                                        >


                                        <button
                                            type="submit"
                                            class="btn btn-sm btn-danger"
                                        >
                                            Hapus
                                        </button>

                                    </form>

                                </div>

                            </td>

                        </tr>

                    <?php endwhile; ?>


                <?php else: ?>

                    <tr>

                        <td
                            colspan="9"
                            class="empty-state"
                        >
                            Belum ada data program studi.
                        </td>

                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</main>

</body>

</html>