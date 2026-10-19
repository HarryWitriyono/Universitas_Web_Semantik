<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../config/database.php';

// CRUD master dosen hanya untuk Admin SIMSKRIPSI lokal.
$user = $_SESSION['user'] ?? null;
if (
    !is_array($user) ||
    ($_SESSION['auth_source'] ?? '') !== 'local_admin' ||
    ($user['kode_role'] ?? '') !== 'ADMIN_SIMSKRIPSI'
) {
    http_response_code(403);
    exit('403 Forbidden: halaman ini hanya dapat diakses Admin SIMSKRIPSI.');
}

$nama_lengkap = (string)($user['nama_lengkap'] ?? $user['username'] ?? 'Administrator');
$nama_role = (string)($user['nama_role'] ?? 'Admin SIMSKRIPSI');
$nama_parts = preg_split('/\\s+/', trim($nama_lengkap)) ?: [];
$initial = '';
if (!empty($nama_parts[0])) {
    $initial .= strtoupper(substr($nama_parts[0], 0, 1));
}
if (count($nama_parts) > 1) {
    $initial .= strtoupper(substr($nama_parts[1], 0, 1));
}

$message = '';
$error = '';
$editDosen = null;
$search = trim((string)($_GET['q'] ?? ''));

try {
    $db = simskripsi_db();

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        csrf_verify();
        $action = (string)($_POST['action'] ?? '');
        $id = filter_var($_POST['id_dosen'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if ($action === 'create' || $action === 'update') {
            $nidn = trim((string)($_POST['nidn'] ?? ''));
            $nip = trim((string)($_POST['nip'] ?? ''));
            $nama = trim((string)($_POST['nama_dosen'] ?? ''));
            $email = trim((string)($_POST['email'] ?? ''));
            $noHp = trim((string)($_POST['no_hp'] ?? ''));
            $status = (string)($_POST['status'] ?? 'aktif');

            if ($nidn === '' || $nama === '') {
                $error = 'NIDN dan nama dosen wajib diisi.';
            } elseif (mb_strlen($nidn) > 30 || mb_strlen($nip) > 30 || mb_strlen($nama) > 150 || mb_strlen($email) > 150 || mb_strlen($noHp) > 30) {
                $error = 'Panjang salah satu kolom melebihi batas yang diizinkan.';
            } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Format email tidak valid.';
            } elseif (!in_array($status, ['aktif', 'tidak_aktif'], true)) {
                $error = 'Status dosen tidak valid.';
            } elseif ($action === 'update' && !$id) {
                $error = 'ID dosen untuk pembaruan tidak valid.';
            } else {
                try {
                    if ($action === 'create') {
                        $stmt = $db->prepare('INSERT INTO dosen (nidn, nip, nama_dosen, email, no_hp, status) VALUES (?, NULLIF(?, \'\'), ?, NULLIF(?, \'\'), NULLIF(?, \'\'), ?)');
                        $stmt->bind_param('ssssss', $nidn, $nip, $nama, $email, $noHp, $status);
                        $stmt->execute();
                        $stmt->close();
                        header('Location: index.php?msg=created');
                        exit;
                    }
                    $stmt = $db->prepare('UPDATE dosen SET nidn = ?, nip = NULLIF(?, \'\'), nama_dosen = ?, email = NULLIF(?, \'\'), no_hp = NULLIF(?, \'\'), status = ? WHERE id_dosen = ?');
                    $stmt->bind_param('ssssssi', $nidn, $nip, $nama, $email, $noHp, $status, $id);
                    $stmt->execute();
                    $affected = $stmt->affected_rows;
                    $stmt->close();
                    header('Location: index.php?msg=updated');
                    exit;
                } catch (mysqli_sql_exception $ex) {
                    if ((int)$ex->getCode() === 1062) {
                        $error = 'NIDN tersebut sudah terdaftar. Gunakan NIDN yang berbeda.';
                    } else {
                        throw $ex;
                    }
                }
            }
            if ($action === 'update' && $id) {
                $editDosen = ['id_dosen' => $id, 'nidn' => $nidn, 'nip' => $nip, 'nama_dosen' => $nama, 'email' => $email, 'no_hp' => $noHp, 'status' => $status];
            }
        } elseif ($action === 'delete') {
            if (!$id) {
                $error = 'ID dosen tidak valid.';
            } else {
                try {
                    $stmt = $db->prepare('DELETE FROM dosen WHERE id_dosen = ?');
                    $stmt->bind_param('i', $id);
                    $stmt->execute();
                    $deleted = $stmt->affected_rows;
                    $stmt->close();
                    if ($deleted) {
                        header('Location: index.php?msg=deleted');
                        exit;
                    }
                    $error = 'Data dosen tidak ditemukan.';
                } catch (mysqli_sql_exception $ex) {
                    if ((int)$ex->getCode() === 1451) {
                        $error = 'Dosen tidak dapat dihapus karena sudah dipakai pada data skripsi, pembimbing, penguji, atau penilaian. Ubah statusnya menjadi Tidak Aktif.';
                    } else {
                        throw $ex;
                    }
                }
            }
        } else {
            $error = 'Aksi tidak dikenal.';
        }
    }

    if ($editDosen === null && isset($_GET['edit'])) {
        $editId = filter_var($_GET['edit'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($editId) {
            $stmt = $db->prepare('SELECT id_dosen, nidn, nip, nama_dosen, email, no_hp, status FROM dosen WHERE id_dosen = ? LIMIT 1');
            $stmt->bind_param('i', $editId);
            $stmt->execute();
            $editDosen = $stmt->get_result()->fetch_assoc() ?: null;
            $stmt->close();
            if (!$editDosen) $error = 'Data dosen yang akan diedit tidak ditemukan.';
        }
    }

    if ($search !== '') {
        $like = '%' . $search . '%';
        $stmt = $db->prepare('SELECT id_dosen, nidn, nip, nama_dosen, email, no_hp, status, created_at FROM dosen WHERE nidn LIKE ? OR nip LIKE ? OR nama_dosen LIKE ? OR email LIKE ? ORDER BY nama_dosen ASC LIMIT 500');
        $stmt->bind_param('ssss', $like, $like, $like, $like);
    } else {
        $stmt = $db->prepare('SELECT id_dosen, nidn, nip, nama_dosen, email, no_hp, status, created_at FROM dosen ORDER BY nama_dosen ASC LIMIT 500');
    }
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $res = $db->query("SELECT COUNT(*) AS total, SUM(status = 'aktif') AS aktif, SUM(status = 'tidak_aktif') AS tidak_aktif FROM dosen");
    $summary = $res->fetch_assoc() ?: ['total' => 0, 'aktif' => 0, 'tidak_aktif' => 0];
    $res->free();
    $db->close();
} catch (Throwable $ex) {
    error_log('SIMSKRIPSI CRUD dosen: ' . $ex->getMessage());
    $error = $error ?: 'Terjadi kesalahan saat memproses data dosen. Periksa konfigurasi database atau log server.';
    $rows = $rows ?? [];
    $summary = $summary ?? ['total' => 0, 'aktif' => 0, 'tidak_aktif' => 0];
}

$noticeMap = [
    'created' => 'Data dosen berhasil ditambahkan.',
    'updated' => 'Data dosen berhasil diperbarui.',
    'deleted' => 'Data dosen berhasil dihapus.'
];
$notice = $noticeMap[(string)($_GET['msg'] ?? '')] ?? '';
$formIsEdit = is_array($editDosen);
$form = $editDosen ?? ['id_dosen' => '', 'nidn' => '', 'nip' => '', 'nama_dosen' => '', 'email' => '', 'no_hp' => '', 'status' => 'aktif'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Master Data Dosen | SIMSKRIPSI UMB</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>

        :root {
            --primary: #0866d8;
            --primary-dark: #064aa1;
            --navy: #14213d;
            --text: #26334d;
            --muted: #69768b;
            --border: #e3e9f1;
            --bg: #f5f8fc;
            --white: #ffffff;
            --success: #18a66a;
            --warning: #e8a100;
            --danger: #dc3545;
            --sidebar-width: 255px;
            --shadow: 0 8px 25px rgba(20,45,85,.07);
        }


        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }


        html,
        body {
            min-height: 100%;
        }


        body {
            font-family: "Poppins", sans-serif;
            color: var(--text);
            background: var(--bg);
            font-size: 13px;
        }


        a {
            text-decoration: none;
            color: inherit;
        }


        button {
            font-family: inherit;
        }


        /* =====================================================
           SIDEBAR
        ====================================================== */

        .sidebar {
            position: fixed;

            top: 0;
            left: 0;
            bottom: 0;

            width: var(--sidebar-width);

            background:
                linear-gradient(
                    180deg,
                    #17213a 0%,
                    #101a31 100%
                );

            color: #cbd4e3;

            z-index: 1000;

            display: flex;
            flex-direction: column;

            transition: transform .25s ease;
        }


        .sidebar-brand {
            min-height: 76px;

            padding: 0 19px;

            display: flex;
            align-items: center;

            gap: 10px;

            border-bottom: 1px solid rgba(255,255,255,.07);
        }


        .sidebar-logo {
            width: 40px;
            height: 40px;

            flex-shrink: 0;

            display: grid;
            place-items: center;

            border-radius: 50%;

            background: #0c8c51;

            border: 2px solid #f0c419;

            color: #fff;

            font-size: 18px;
        }


        .sidebar-brand strong {
            display: block;

            color: #fff;

            font-size: 20px;

            line-height: 1;
        }


        .sidebar-brand strong span {
            color: #3c9cff;
        }


        .sidebar-brand small {
            display: block;

            margin-top: 4px;

            font-size: 8px;

            color: #9daac0;
        }


        .sidebar-menu {
            padding: 18px 11px;

            overflow-y: auto;

            flex: 1;
        }


        .menu-title {
            padding: 8px 11px 7px;

            color: #687791;

            text-transform: uppercase;

            font-size: 9px;

            font-weight: 600;

            letter-spacing: 1px;
        }


        .menu-item {
            display: flex;

            align-items: center;

            gap: 11px;

            padding: 10px 12px;

            margin-bottom: 3px;

            border-radius: 8px;

            color: #b8c3d5;

            font-size: 11px;

            transition: .2s ease;
        }


        .menu-item i {
            width: 17px;

            text-align: center;

            font-size: 15px;
        }


        .menu-item:hover {
            color: #fff;

            background: rgba(255,255,255,.06);
        }


        .menu-item.active {
            color: #fff;

            background: var(--primary);

            box-shadow:
                0 5px 13px rgba(8,102,216,.25);
        }


        .menu-badge {
            margin-left: auto;

            min-width: 18px;

            height: 18px;

            display: grid;
            place-items: center;

            border-radius: 20px;

            color: #fff;

            background: var(--danger);

            font-size: 8px;
        }


        .sidebar-footer {
            padding: 13px;

            border-top: 1px solid rgba(255,255,255,.07);
        }


        .sidebar-user {
            display: flex;

            align-items: center;

            gap: 9px;

            padding: 9px;

            border-radius: 9px;

            background: rgba(255,255,255,.045);
        }


        .avatar-small {
            width: 32px;
            height: 32px;

            display: grid;
            place-items: center;

            flex-shrink: 0;

            border-radius: 50%;

            color: #fff;

            background: #2379d9;

            font-size: 10px;

            font-weight: 700;
        }


        .sidebar-user strong {
            display: block;

            max-width: 140px;

            overflow: hidden;

            text-overflow: ellipsis;

            white-space: nowrap;

            color: #fff;

            font-size: 10px;
        }


        .sidebar-user span {
            display: block;

            color: #8290a8;

            font-size: 8px;
        }


        /* =====================================================
           MAIN
        ====================================================== */

        .main {
            min-height: 100vh;

            margin-left: var(--sidebar-width);

            transition: margin-left .25s ease;
        }


        /* =====================================================
           TOPBAR
        ====================================================== */

        .topbar {
            position: sticky;

            top: 0;

            z-index: 900;

            height: 76px;

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 20px;

            padding: 0 30px;

            background: rgba(255,255,255,.96);

            backdrop-filter: blur(12px);

            border-bottom: 1px solid var(--border);
        }


        .topbar-left {
            display: flex;

            align-items: center;

            gap: 15px;
        }


        .sidebar-toggle {
            width: 38px;
            height: 38px;

            display: grid;
            place-items: center;

            border: 1px solid var(--border);

            border-radius: 9px;

            background: #fff;

            color: var(--navy);

            cursor: pointer;

            font-size: 18px;
        }


        .page-title h1 {
            font-size: 19px;

            color: var(--navy);

            line-height: 1.2;
        }


        .page-title p {
            margin-top: 2px;

            color: var(--muted);

            font-size: 10px;
        }


        .topbar-right {
            display: flex;

            align-items: center;

            gap: 18px;
        }


        .notification {
            position: relative;

            width: 36px;
            height: 36px;

            display: grid;
            place-items: center;

            color: #536078;

            border-radius: 50%;

            cursor: pointer;

            font-size: 17px;
        }


        .notification:hover {
            background: #f1f5fa;
        }


        .notification-badge {
            position: absolute;

            top: 4px;
            right: 3px;

            width: 14px;
            height: 14px;

            display: grid;
            place-items: center;

            border-radius: 50%;

            color: #fff;

            background: var(--danger);

            font-size: 7px;
        }


        .top-user {
            display: flex;

            align-items: center;

            gap: 9px;

            padding-left: 16px;

            border-left: 1px solid var(--border);
        }


        .avatar {
            width: 39px;
            height: 39px;

            display: grid;
            place-items: center;

            border-radius: 50%;

            color: #fff;

            background:
                linear-gradient(
                    145deg,
                    #1680eb,
                    #0753b4
                );

            font-size: 11px;

            font-weight: 700;
        }


        .top-user-info strong {
            display: block;

            max-width: 160px;

            overflow: hidden;

            text-overflow: ellipsis;

            white-space: nowrap;

            color: var(--navy);

            font-size: 10px;
        }


        .top-user-info span {
            display: block;

            margin-top: 2px;

            color: var(--muted);

            font-size: 8px;
        }


        /* =====================================================
           CONTENT
        ====================================================== */

        .content {
            padding: 28px 30px 40px;
        }


        /* =====================================================
           WELCOME
        ====================================================== */

        .welcome-card {
            position: relative;

            min-height: 170px;

            padding: 27px 31px;

            overflow: hidden;

            border-radius: 16px;

            color: #fff;

            background:
                linear-gradient(
                    120deg,
                    #0866d8,
                    #0752ad
                );

            box-shadow:
                0 12px 28px rgba(8,102,216,.16);
        }


        .welcome-card::before {
            content: "";

            position: absolute;

            width: 270px;
            height: 270px;

            border-radius: 50%;

            right: -85px;
            top: -155px;

            background: rgba(255,255,255,.08);
        }


        .welcome-card::after {
            content: "";

            position: absolute;

            width: 190px;
            height: 190px;

            border-radius: 50%;

            right: 100px;
            bottom: -160px;

            background: rgba(255,255,255,.06);
        }


        .welcome-content {
            position: relative;

            z-index: 2;
        }


        .welcome-label {
            display: block;

            margin-bottom: 4px;

            font-size: 10px;

            color: rgba(255,255,255,.75);
        }


        .welcome-card h2 {
            font-size: 25px;

            line-height: 1.25;

            margin-bottom: 7px;
        }


        .welcome-card p {
            max-width: 620px;

            font-size: 11px;

            line-height: 1.7;

            color: rgba(255,255,255,.82);
        }


        .welcome-role {
            position: absolute;

            right: 28px;
            bottom: 27px;

            z-index: 3;

            padding: 8px 13px;

            border-radius: 20px;

            color: #fff;

            background: rgba(255,255,255,.13);

            border: 1px solid rgba(255,255,255,.18);

            font-size: 9px;

            font-weight: 600;
        }


        /* =====================================================
           SECTION HEADER
        ====================================================== */

        .section-header {
            display: flex;

            align-items: center;

            justify-content: space-between;

            margin: 26px 0 13px;
        }


        .section-header h3 {
            font-size: 15px;

            color: var(--navy);
        }


        .section-header a {
            color: var(--primary);

            font-size: 10px;

            font-weight: 600;
        }


        /* =====================================================
           STAT CARDS
        ====================================================== */

        .stat-grid {
            display: grid;

            grid-template-columns:
                repeat(4, 1fr);

            gap: 14px;
        }


        .stat-card {
            display: flex;

            align-items: center;

            gap: 13px;

            padding: 18px;

            border-radius: 13px;

            background: #fff;

            border: 1px solid var(--border);

            box-shadow: var(--shadow);

            transition: .2s ease;
        }


        .stat-card:hover {
            transform: translateY(-2px);

            box-shadow:
                0 13px 28px rgba(20,45,85,.09);
        }


        .stat-icon {
            width: 43px;
            height: 43px;

            display: grid;
            place-items: center;

            flex-shrink: 0;

            border-radius: 11px;

            font-size: 19px;
        }


        .stat-icon.blue {
            color: #0866d8;
            background: #eaf4ff;
        }


        .stat-icon.green {
            color: #15935f;
            background: #e9f9f1;
        }


        .stat-icon.orange {
            color: #d88900;
            background: #fff5df;
        }


        .stat-icon.purple {
            color: #7754c7;
            background: #f2edff;
        }


        .stat-card strong {
            display: block;

            color: var(--navy);

            font-size: 21px;

            line-height: 1.1;
        }


        .stat-card span {
            display: block;

            margin-top: 3px;

            color: var(--muted);

            font-size: 9px;
        }


        /* =====================================================
           MAIN GRID
        ====================================================== */

        .dashboard-grid {
            display: grid;

            grid-template-columns:
                minmax(0, 1.45fr)
                minmax(300px, .85fr);

            gap: 18px;

            margin-top: 18px;
        }


        .panel {
            background: #fff;

            border: 1px solid var(--border);

            border-radius: 14px;

            box-shadow: var(--shadow);

            overflow: hidden;
        }


        .panel-header {
            min-height: 58px;

            padding: 0 18px;

            display: flex;

            align-items: center;

            justify-content: space-between;

            border-bottom: 1px solid var(--border);
        }


        .panel-header h3 {
            font-size: 13px;

            color: var(--navy);
        }


        .panel-header a {
            color: var(--primary);

            font-size: 9px;

            font-weight: 600;
        }


        /* =====================================================
           WORKFLOW
        ====================================================== */

        .workflow {
            padding: 19px;
        }


        .workflow-item {
            position: relative;

            display: grid;

            grid-template-columns: 40px 1fr auto;

            gap: 12px;

            align-items: center;

            padding: 12px 0;

            border-bottom: 1px solid #edf1f5;
        }


        .workflow-item:last-child {
            border-bottom: 0;
        }


        .workflow-number {
            width: 36px;
            height: 36px;

            display: grid;
            place-items: center;

            border-radius: 10px;

            color: var(--primary);

            background: #eaf4ff;

            font-size: 13px;

            font-weight: 700;
        }


        .workflow-item.done .workflow-number {
            color: #fff;

            background: var(--success);
        }


        .workflow-item.current .workflow-number {
            color: #fff;

            background: var(--primary);
        }


        .workflow-info strong {
            display: block;

            color: #34425b;

            font-size: 10px;
        }


        .workflow-info span {
            display: block;

            margin-top: 3px;

            color: var(--muted);

            font-size: 8px;
        }


        .status {
            padding: 5px 9px;

            border-radius: 20px;

            font-size: 8px;

            font-weight: 600;

            white-space: nowrap;
        }


        .status.done {
            color: #13764e;

            background: #e8f8f0;
        }


        .status.process {
            color: #0757ad;

            background: #eaf4ff;
        }


        .status.wait {
            color: #8b640d;

            background: #fff5dd;
        }


        /* =====================================================
           RIGHT PANEL
        ====================================================== */

        .activity-list {
            padding: 8px 18px;
        }


        .activity {
            display: flex;

            align-items: flex-start;

            gap: 10px;

            padding: 12px 0;

            border-bottom: 1px solid #edf1f5;
        }


        .activity:last-child {
            border-bottom: 0;
        }


        .activity-icon {
            width: 32px;
            height: 32px;

            display: grid;
            place-items: center;

            flex-shrink: 0;

            border-radius: 9px;

            color: var(--primary);

            background: #eaf4ff;

            font-size: 13px;
        }


        .activity-info strong {
            display: block;

            color: #34425b;

            font-size: 9px;

            line-height: 1.45;
        }


        .activity-info span {
            display: block;

            margin-top: 2px;

            color: #8a95a5;

            font-size: 8px;
        }


        /* =====================================================
           PROFILE PANEL
        ====================================================== */

        .profile-body {
            padding: 19px;
        }


        .profile-top {
            display: flex;

            align-items: center;

            gap: 12px;

            margin-bottom: 17px;
        }


        .profile-avatar {
            width: 51px;
            height: 51px;

            display: grid;
            place-items: center;

            border-radius: 50%;

            color: #fff;

            background:
                linear-gradient(
                    145deg,
                    #1680eb,
                    #0753b4
                );

            font-size: 13px;

            font-weight: 700;
        }


        .profile-top strong {
            display: block;

            color: var(--navy);

            font-size: 12px;
        }


        .profile-top span {
            display: block;

            margin-top: 2px;

            color: var(--muted);

            font-size: 9px;
        }


        .profile-data {
            display: grid;

            gap: 8px;
        }


        .profile-row {
            display: flex;

            justify-content: space-between;

            gap: 20px;

            padding-bottom: 8px;

            border-bottom: 1px dashed #e5eaf0;

            font-size: 8px;
        }


        .profile-row:last-child {
            border-bottom: 0;
        }


        .profile-row span:first-child {
            color: #8994a5;
        }


        .profile-row span:last-child {
            color: #34425b;

            font-weight: 500;

            text-align: right;
        }


        /* =====================================================
           QUICK ACTION
        ====================================================== */

        .quick-grid {
            display: grid;

            grid-template-columns:
                repeat(2, 1fr);

            gap: 9px;

            padding: 18px;
        }


        .quick-action {
            display: flex;

            align-items: center;

            gap: 8px;

            padding: 10px;

            border-radius: 9px;

            border: 1px solid var(--border);

            color: #48566c;

            background: #fbfcfe;

            font-size: 9px;

            transition: .2s;
        }


        .quick-action i {
            color: var(--primary);

            font-size: 14px;
        }


        .quick-action:hover {
            border-color: #b8d3f0;

            background: #f1f7ff;

            color: var(--primary);
        }


        /* =====================================================
           FOOTER
        ====================================================== */

        .footer {
            padding: 20px 30px;

            color: #8792a3;

            font-size: 9px;

            text-align: center;
        }


        /* =====================================================
           OVERLAY
        ====================================================== */

        .sidebar-overlay {
            display: none;

            position: fixed;

            inset: 0;

            z-index: 999;

            background: rgba(8,18,38,.45);
        }


        /* =====================================================
           RESPONSIVE
        ====================================================== */

        @media (max-width: 1150px) {

            .stat-grid {
                grid-template-columns:
                    repeat(2, 1fr);
            }

            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }


        @media (max-width: 850px) {

            :root {
                --sidebar-width: 255px;
            }


            .sidebar {
                transform: translateX(-100%);
            }


            body.sidebar-open .sidebar {
                transform: translateX(0);
            }


            body.sidebar-open .sidebar-overlay {
                display: block;
            }


            .main {
                margin-left: 0;
            }


            .topbar {
                padding: 0 18px;
            }


            .content {
                padding: 20px 18px 35px;
            }


            .welcome-card {
                padding: 23px;

                min-height: 180px;
            }


            .welcome-role {
                position: static;

                display: inline-block;

                margin-top: 17px;
            }
        }


        @media (max-width: 550px) {

            .top-user-info {
                display: none;
            }


            .top-user {
                border-left: 0;

                padding-left: 0;
            }


            .page-title h1 {
                font-size: 16px;
            }


            .page-title p {
                display: none;
            }


            .stat-grid {
                grid-template-columns: 1fr;
            }


            .welcome-card h2 {
                font-size: 20px;
            }


            .welcome-card p {
                font-size: 10px;
            }


            .workflow-item {
                grid-template-columns:
                    36px 1fr;
            }


            .workflow-item .status {
                grid-column: 2;

                justify-self: start;
            }
        }

    
/* =====================================================
   CRUD MASTER DOSEN - dashboard-compatible components
====================================================== */
.crud-heading {
    display:flex; align-items:flex-start; justify-content:space-between;
    gap:16px; flex-wrap:wrap; margin-bottom:22px;
}
.crud-heading h2 { color:var(--navy); font-size:20px; margin:0 0 5px; }
.crud-heading p { color:var(--muted); font-size:11px; margin:0; }
.crud-grid { display:grid; grid-template-columns:minmax(280px,.78fr) minmax(0,1.55fr); gap:18px; align-items:start; }
.crud-panel { background:#fff; border:1px solid var(--border); border-radius:14px; box-shadow:var(--shadow); overflow:hidden; min-width:0; }
.crud-panel-header { min-height:58px; padding:16px 19px; display:flex; align-items:center; justify-content:space-between; gap:12px; border-bottom:1px solid var(--border); }
.crud-panel-header h3 { font-size:13px; color:var(--navy); font-weight:600; margin:0; }
.crud-panel-body { padding:20px; }
.crud-stats { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:14px; margin-bottom:18px; }
.crud-stat { display:flex; align-items:center; gap:12px; padding:17px; background:#fff; border:1px solid var(--border); border-radius:13px; box-shadow:var(--shadow); min-width:0; }
.crud-stat-icon { width:40px; height:40px; flex:0 0 40px; display:grid; place-items:center; border-radius:11px; background:#eaf4ff; color:var(--primary); font-size:18px; }
.crud-stat-label { font-size:10px; color:var(--muted); margin-bottom:4px; }
.crud-stat-value { font-size:22px; line-height:1.1; font-weight:700; color:var(--navy); }
.crud-form-label { display:block; color:var(--text); font-size:11px; font-weight:600; margin-bottom:7px; }
.crud-input { width:100%; border:1px solid #d9e1ec; border-radius:8px; padding:10px 11px; font:inherit; font-size:12px; color:var(--text); background:#fff; }
.crud-input:focus { outline:0; border-color:var(--primary); box-shadow:0 0 0 3px rgba(8,102,216,.10); }
.crud-field { margin-bottom:15px; }
.crud-btn { display:inline-flex; align-items:center; justify-content:center; gap:7px; border:1px solid transparent; border-radius:8px; padding:9px 12px; font:inherit; font-size:11px; font-weight:600; cursor:pointer; transition:.2s ease; white-space:nowrap; }
.crud-btn-primary { color:#fff; background:var(--primary); }
.crud-btn-primary:hover { background:var(--primary-dark); color:#fff; }
.crud-btn-light { color:var(--text); background:#fff; border-color:var(--border); }
.crud-btn-light:hover { background:#f8fafc; }
.crud-btn-danger { color:#b42332; background:#fff; border-color:#f0c8ce; }
.crud-btn-danger:hover { background:#fff3f4; }
.crud-search { display:flex; gap:7px; align-items:center; }
.crud-table-wrap { width:100%; overflow:auto; }
.crud-table { width:100%; border-collapse:collapse; font-size:11px; }
.crud-table th { padding:12px 14px; color:var(--muted); background:#f8fafc; text-align:left; white-space:nowrap; font-weight:600; border-bottom:1px solid var(--border); }
.crud-table td { padding:13px 14px; border-bottom:1px solid #edf1f6; vertical-align:middle; }
.crud-table tbody tr:hover { background:#fbfdff; }
.crud-table tbody tr:last-child td { border-bottom:0; }
.crud-name { font-weight:600; color:var(--navy); font-size:11px; }
.crud-muted { color:var(--muted); font-size:10px; margin-top:3px; }
.crud-badge { display:inline-flex; padding:5px 8px; border-radius:20px; font-size:9px; font-weight:600; }
.crud-badge-active { color:#13764b; background:#e8f8ef; }
.crud-badge-inactive { color:#667085; background:#eef1f5; }
.crud-alert { border-radius:10px; padding:12px 15px; margin-bottom:16px; font-size:11px; border:1px solid; }
.crud-alert-success { color:#12643f; background:#effaf4; border-color:#c7eed7; }
.crud-alert-danger { color:#a51d2d; background:#fff3f4; border-color:#f5c9cf; }
.crud-note { padding:13px 16px; border-top:1px solid var(--border); color:var(--muted); font-size:10px; line-height:1.6; background:#fbfcfe; }
.crud-empty { padding:35px 15px !important; color:var(--muted); text-align:center; }
.crud-actions { display:flex; justify-content:flex-end; gap:6px; }
.crud-footer { padding:20px 30px 25px; color:var(--muted); font-size:10px; text-align:center; }
@media(max-width:1150px){ .crud-grid{grid-template-columns:1fr;} }
@media(max-width:650px){
 .crud-stats{grid-template-columns:1fr;}
 .crud-stat{padding:13px;}
 .crud-panel-body{padding:16px;}
 .crud-panel-header{align-items:flex-start; flex-direction:column;}
 .crud-search{width:100%;}
 .crud-search input{min-width:0; flex:1;}
 .crud-heading h2{font-size:17px;}
 .crud-footer{padding:18px;}
}
</style>
</head>
<body>
<aside class="sidebar">
  <div class="sidebar-brand">
    <div class="sidebar-logo"><i class="bi bi-mortarboard-fill"></i></div>
    <div><strong>SIM<span>SKRIPSI</span></strong><small>Universitas Muhammadiyah Bengkulu</small></div>
  </div>
  <nav class="sidebar-menu">
    <div class="menu-title">Menu Utama</div>
    <a href="../../dashboard.php" class="menu-item"><i class="bi bi-grid-1x2-fill"></i>Dashboard</a>
    <a href="#" class="menu-item"><i class="bi bi-file-earmark-text"></i>Pengajuan Judul</a>
    <a href="#" class="menu-item"><i class="bi bi-people"></i>Pembimbing</a>
    <a href="#" class="menu-item"><i class="bi bi-calendar-event"></i>Seminar Proposal</a>
    <a href="#" class="menu-item"><i class="bi bi-journal-check"></i>Sidang Skripsi</a>
    <a href="#" class="menu-item"><i class="bi bi-folder2-open"></i>Dokumen</a>
    <div class="menu-title" style="margin-top:10px;">Informasi</div>
    <a href="#" class="menu-item"><i class="bi bi-megaphone"></i>Pengumuman</a>
    <a href="#" class="menu-item"><i class="bi bi-question-circle"></i>Panduan</a>
    <a href="#" class="menu-item"><i class="bi bi-info-circle"></i>Tentang Sistem</a>
    <div class="menu-title" style="margin-top:10px;">Administrasi</div>
    <a href="index.php" class="menu-item active"><i class="bi bi-person-vcard"></i>Master Data Dosen</a>
    <a href="../pengaturan_api.php" class="menu-item"><i class="bi bi-gear"></i>Pengaturan Integrasi API</a>
    <div class="menu-title" style="margin-top:10px;">Akun</div>
    <a href="#" class="menu-item"><i class="bi bi-person-circle"></i>Profil Saya</a>
    <form method="post" action="../../dashboard.php" onsubmit="return confirm('Apakah Anda yakin ingin keluar?');">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="logout">
      <button type="submit" class="menu-item" style="width:100%;border:0;background:transparent;text-align:left;cursor:pointer;font:inherit;"><i class="bi bi-box-arrow-right"></i>Keluar</button>
    </form>
  </nav>
  <div class="sidebar-footer">
    <div class="sidebar-user">
      <div class="avatar-small"><?= e($initial) ?></div>
      <div><strong><?= e($nama_lengkap) ?></strong><span><?= e($nama_role) ?></span></div>
    </div>
  </div>
</aside>
<div class="sidebar-overlay"></div>
<main class="main">
  <header class="topbar">
    <div class="topbar-left">
      <button type="button" class="sidebar-toggle" id="sidebarToggle" aria-label="Buka menu"><i class="bi bi-list"></i></button>
      <div class="page-title"><h1>Master Data Dosen</h1><p>Kelola data dosen SIMSKRIPSI</p></div>
    </div>
    <div class="topbar-right">
      <div class="top-user">
        <div class="avatar"><?= e($initial) ?></div>
        <div class="top-user-info"><strong><?= e($nama_lengkap) ?></strong><span><?= e($nama_role) ?></span></div>
      </div>
    </div>
  </header>
  <div class="content">
    <div class="crud-heading">
      <div><h2>Master Data Dosen</h2><p>Kelola identitas dosen yang tersimpan pada database SIMSKRIPSI.</p></div>
      <a class="crud-btn crud-btn-light" href="../../dashboard.php"><i class="bi bi-arrow-left"></i>Kembali ke Dashboard</a>
    </div>
    <?php if ($notice !== ''): ?><div class="crud-alert crud-alert-success"><i class="bi bi-check-circle me-1"></i> <?= e($notice) ?></div><?php endif; ?>
    <?php if ($error !== ''): ?><div class="crud-alert crud-alert-danger"><i class="bi bi-exclamation-triangle me-1"></i> <?= e($error) ?></div><?php endif; ?>
    <div class="crud-stats">
      <div class="crud-stat"><div class="crud-stat-icon"><i class="bi bi-people-fill"></i></div><div><div class="crud-stat-label">Total Dosen</div><div class="crud-stat-value"><?= (int)$summary['total'] ?></div></div></div>
      <div class="crud-stat"><div class="crud-stat-icon" style="background:#e8f8ef;color:#15935f;"><i class="bi bi-person-check-fill"></i></div><div><div class="crud-stat-label">Dosen Aktif</div><div class="crud-stat-value"><?= (int)($summary['aktif'] ?? 0) ?></div></div></div>
      <div class="crud-stat"><div class="crud-stat-icon" style="background:#eef1f5;color:#667085;"><i class="bi bi-person-x-fill"></i></div><div><div class="crud-stat-label">Dosen Tidak Aktif</div><div class="crud-stat-value"><?= (int)($summary['tidak_aktif'] ?? 0) ?></div></div></div>
    </div>
    <div class="crud-grid">
      <section class="crud-panel">
        <div class="crud-panel-header"><h3><i class="bi <?= $formIsEdit ? 'bi-pencil-square' : 'bi-person-plus' ?> me-2"></i><?= $formIsEdit ? 'Edit Data Dosen' : 'Tambah Dosen' ?></h3></div>
        <div class="crud-panel-body">
          <form method="post" action="index.php<?= $formIsEdit ? '?edit=' . (int)$form['id_dosen'] : '' ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="<?= $formIsEdit ? 'update' : 'create' ?>">
            <input type="hidden" name="id_dosen" value="<?= e($form['id_dosen']) ?>">
            <div class="crud-field"><label class="crud-form-label" for="nidn">NIDN <span style="color:#dc3545">*</span></label><input class="crud-input" id="nidn" name="nidn" maxlength="30" required value="<?= e($form['nidn']) ?>"></div>
            <div class="crud-field"><label class="crud-form-label" for="nip">NIP</label><input class="crud-input" id="nip" name="nip" maxlength="30" value="<?= e($form['nip'] ?? '') ?>"></div>
            <div class="crud-field"><label class="crud-form-label" for="nama_dosen">Nama Dosen <span style="color:#dc3545">*</span></label><input class="crud-input" id="nama_dosen" name="nama_dosen" maxlength="150" required value="<?= e($form['nama_dosen']) ?>"></div>
            <div class="crud-field"><label class="crud-form-label" for="email">Email</label><input class="crud-input" type="email" id="email" name="email" maxlength="150" value="<?= e($form['email'] ?? '') ?>"></div>
            <div class="crud-field"><label class="crud-form-label" for="no_hp">Nomor HP</label><input class="crud-input" id="no_hp" name="no_hp" maxlength="30" value="<?= e($form['no_hp'] ?? '') ?>"></div>
            <div class="crud-field"><label class="crud-form-label" for="status">Status</label><select class="crud-input" id="status" name="status"><option value="aktif" <?= $form['status']==='aktif'?'selected':'' ?>>Aktif</option><option value="tidak_aktif" <?= $form['status']==='tidak_aktif'?'selected':'' ?>>Tidak Aktif</option></select></div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;"><button class="crud-btn crud-btn-primary" type="submit"><i class="bi bi-save"></i><?= $formIsEdit ? 'Simpan Perubahan' : 'Simpan Dosen' ?></button><?php if($formIsEdit): ?><a class="crud-btn crud-btn-light" href="index.php">Batal</a><?php else: ?><button class="crud-btn crud-btn-light" type="reset">Reset</button><?php endif; ?></div>
          </form>
          <div class="crud-note" style="margin:18px -20px -20px;">CRUD ini mengelola data master pada tabel <code>dosen</code>. Akun login dosen tetap dikelola terpisah.</div>
        </div>
      </section>
      <section class="crud-panel">
        <div class="crud-panel-header">
          <h3><i class="bi bi-list-ul me-2"></i>Daftar Dosen</h3>
          <form class="crud-search" method="get" action="index.php">
            <input class="crud-input" style="min-width:155px;" name="q" placeholder="Cari NIDN, nama, email..." value="<?= e($search) ?>">
            <button class="crud-btn crud-btn-light" type="submit" aria-label="Cari"><i class="bi bi-search"></i></button>
            <?php if($search!==''): ?><a class="crud-btn crud-btn-light" href="index.php">Reset</a><?php endif; ?>
          </form>
        </div>
        <div class="crud-table-wrap"><table class="crud-table"><thead><tr><th>NIDN / NIP</th><th>Nama dan Kontak</th><th>Status</th><th style="text-align:right;">Aksi</th></tr></thead><tbody>
        <?php if (!$rows): ?><tr><td colspan="4" class="crud-empty">Belum ada data dosen atau pencarian tidak ditemukan.</td></tr><?php else: foreach($rows as $row): ?>
        <tr>
          <td><div class="crud-name"><?= e($row['nidn']) ?></div><div class="crud-muted"><?= e($row['nip'] ?: '-') ?></div></td>
          <td><div class="crud-name"><?= e($row['nama_dosen']) ?></div><div class="crud-muted"><?= e($row['email'] ?: '-') ?><?= $row['no_hp'] ? ' · ' . e($row['no_hp']) : '' ?></div></td>
          <td><?php if($row['status']==='aktif'): ?><span class="crud-badge crud-badge-active">Aktif</span><?php else: ?><span class="crud-badge crud-badge-inactive">Tidak Aktif</span><?php endif; ?></td>
          <td><div class="crud-actions"><a class="crud-btn crud-btn-light" title="Edit" href="index.php?edit=<?= (int)$row['id_dosen'] ?>"><i class="bi bi-pencil-square"></i></a><form class="d-inline" method="post" action="index.php" onsubmit="return confirm('Hapus data dosen ini?');"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id_dosen" value="<?= (int)$row['id_dosen'] ?>"><button class="crud-btn crud-btn-danger" title="Hapus" type="submit"><i class="bi bi-trash"></i></button></form></div></td>
        </tr>
        <?php endforeach; endif; ?>
        </tbody></table></div>
        <div class="crud-note">Maksimal 500 data ditampilkan. Jika dosen sudah terhubung dengan proses skripsi, ubah status menjadi Tidak Aktif alih-alih menghapus.</div>
      </section>
    </div>
  </div>
  <footer class="crud-footer">SIMSKRIPSI &copy; <?= date('Y') ?> Universitas Muhammadiyah Bengkulu</footer>
</main>
<script>
const sidebarToggle = document.getElementById('sidebarToggle');
const body = document.body;
const overlay = document.querySelector('.sidebar-overlay');
if (sidebarToggle) sidebarToggle.addEventListener('click', () => body.classList.toggle('sidebar-open'));
if (overlay) overlay.addEventListener('click', () => body.classList.remove('sidebar-open'));
document.querySelectorAll('.sidebar .menu-item').forEach(item => item.addEventListener('click', () => {
  if (window.innerWidth <= 850) body.classList.remove('sidebar-open');
}));
</script>
</body>
</html>
