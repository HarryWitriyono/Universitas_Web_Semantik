<?php
/*
|--------------------------------------------------------------------------
| SIMSKRIPSI - DASHBOARD
|--------------------------------------------------------------------------
| Dashboard utama setelah login.
|
| Sumber identitas pengguna:
| Web API SIM Mahasiswa -> login.php -> $_SESSION['user']
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/csrf.php';

/*
|--------------------------------------------------------------------------
| CEK LOGIN
|--------------------------------------------------------------------------
*/
if (
    !isset($_SESSION['user']) ||
    !is_array($_SESSION['user']) ||
    empty($_SESSION['user']['id_pengguna'])
) {
    header('Location: login.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| DATA PENGGUNA DARI SESSION
|--------------------------------------------------------------------------
*/
$user = $_SESSION['user'];

$id_pengguna       = $user['id_pengguna'] ?? '';
$id_role           = $user['id_role'] ?? '';
$nama_lengkap      = $user['nama_lengkap'] ?? 'Pengguna';
$username          = $user['username'] ?? '';
$email             = $user['email'] ?? '';
$kode_role         = $user['kode_role'] ?? '';
$nama_role         = $user['nama_role'] ?? 'Pengguna';
$status_aktif      = $user['status_aktif'] ?? '';
$id_universitas    = $user['id_universitas'] ?? '';
$id_fakultas       = $user['id_fakultas'] ?? '';
$id_program_studi  = $user['id_program_studi'] ?? '';


/*
|--------------------------------------------------------------------------
| INISIAL NAMA
|--------------------------------------------------------------------------
*/
$nama_parts = preg_split('/\s+/', trim($nama_lengkap));

$initial = '';

if (!empty($nama_parts[0])) {
    $initial .= strtoupper(substr($nama_parts[0], 0, 1));
}

if (count($nama_parts) > 1) {
    $initial .= strtoupper(substr($nama_parts[1], 0, 1));
}


/*
|--------------------------------------------------------------------------
| HELPER
|--------------------------------------------------------------------------
*/
function e($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}


/*
|--------------------------------------------------------------------------
| LOGOUT
|--------------------------------------------------------------------------
*/
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['action'] ?? '') === 'logout') {
    csrf_verify();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $params['path'],
            'domain' => $params['domain'],
            'secure' => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => $params['samesite'] ?? 'Lax',
        ]);
    }
    session_destroy();
    header('Location: login.php');
    exit;
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

    <title>
        Dashboard | SIMSKRIPSI UMB
    </title>

    <meta
        name="description"
        content="Dashboard SIMSKRIPSI Universitas Muhammadiyah Bengkulu"
    >


    <!-- Google Font -->
    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet"
    >


    <!-- Bootstrap Icons -->
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >


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

    </style>

</head>


<body>


<!-- =========================================================
     SIDEBAR
========================================================= -->

<aside class="sidebar">

    <div class="sidebar-brand">

        <div class="sidebar-logo">
            <i class="bi bi-mortarboard-fill"></i>
        </div>

        <div>
            <strong>
                SIM<span>SKRIPSI</span>
            </strong>

            <small>
                Universitas Muhammadiyah Bengkulu
            </small>
        </div>

    </div>


    <nav class="sidebar-menu">

        <div class="menu-title">
            Menu Utama
        </div>


        <a
            href="dashboard.php"
            class="menu-item active"
        >
            <i class="bi bi-grid-1x2-fill"></i>
            Dashboard
        </a>


        <a
            href="#"
            class="menu-item"
        >
            <i class="bi bi-file-earmark-text"></i>
            Pengajuan Judul
            <span class="menu-badge">3</span>
        </a>


        <a
            href="#"
            class="menu-item"
        >
            <i class="bi bi-people"></i>
            Pembimbing
        </a>


        <a
            href="#"
            class="menu-item"
        >
            <i class="bi bi-calendar-event"></i>
            Seminar Proposal
        </a>


        <a
            href="#"
            class="menu-item"
        >
            <i class="bi bi-journal-check"></i>
            Sidang Skripsi
        </a>


        <a
            href="#"
            class="menu-item"
        >
            <i class="bi bi-folder2-open"></i>
            Dokumen
        </a>


        <div class="menu-title" style="margin-top: 10px;">
            Informasi
        </div>


        <a
            href="#"
            class="menu-item"
        >
            <i class="bi bi-megaphone"></i>
            Pengumuman
        </a>


        <a
            href="#"
            class="menu-item"
        >
            <i class="bi bi-question-circle"></i>
            Panduan
        </a>


        <a
            href="#"
            class="menu-item"
        >
            <i class="bi bi-info-circle"></i>
            Tentang Sistem
        </a>


        <div class="menu-title" style="margin-top: 10px;">
            Akun
        </div>


        <a
            href="#"
            class="menu-item"
        >
            <i class="bi bi-person-circle"></i>
            Profil Saya
        </a>


        <?php if (($_SESSION['auth_source'] ?? '') === 'local_admin' && ($user['kode_role'] ?? '') === 'ADMIN_SIMSKRIPSI'): ?>
        <a href="admin/dosen/index.php" class="menu-item">
            <i class="bi bi-person-vcard"></i>
            Master Data Dosen
        </a>
        <a href="admin/pengaturan_api.php" class="menu-item">
            <i class="bi bi-gear"></i>
            Pengaturan Integrasi API
        </a>
        <?php endif; ?>

        <form method="post" action="" onsubmit="return confirm('Apakah Anda yakin ingin keluar?');">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="logout">
            <button type="submit" class="menu-item" style="width:100%;border:0;background:transparent;text-align:left;cursor:pointer;font:inherit;">
                <i class="bi bi-box-arrow-right"></i>
                Keluar
            </button>
        </form>

    </nav>


    <div class="sidebar-footer">

        <div class="sidebar-user">

            <div class="avatar-small">
                <?php echo e($initial); ?>
            </div>

            <div>

                <strong>
                    <?php echo e($nama_lengkap); ?>
                </strong>

                <span>
                    <?php echo e($nama_role); ?>
                </span>

            </div>

        </div>

    </div>

</aside>


<div class="sidebar-overlay"></div>


<!-- =========================================================
     MAIN
========================================================= -->

<main class="main">


    <!-- =====================================================
         TOPBAR
    ====================================================== -->

    <header class="topbar">

        <div class="topbar-left">

            <button
                type="button"
                class="sidebar-toggle"
                id="sidebarToggle"
                aria-label="Buka menu"
            >
                <i class="bi bi-list"></i>
            </button>


            <div class="page-title">

                <h1>
                    Dashboard
                </h1>

                <p>
                    Sistem Informasi Manajemen Skripsi
                </p>

            </div>

        </div>


        <div class="topbar-right">

            <div
                class="notification"
                title="Notifikasi"
            >
                <i class="bi bi-bell"></i>

                <span class="notification-badge">
                    3
                </span>
            </div>


            <div class="top-user">

                <div class="avatar">
                    <?php echo e($initial); ?>
                </div>

                <div class="top-user-info">

                    <strong>
                        <?php echo e($nama_lengkap); ?>
                    </strong>

                    <span>
                        <?php echo e($nama_role); ?>
                    </span>

                </div>

            </div>

        </div>

    </header>


    <!-- =====================================================
         CONTENT
    ====================================================== -->

    <div class="content">


        <!-- WELCOME -->

        <section class="welcome-card">

            <div class="welcome-content">

                <span class="welcome-label">
                    Selamat datang di SIMSKRIPSI
                </span>

                <h2>
                    Halo, <?php echo e($nama_lengkap); ?> 👋
                </h2>

                <p>
                    Anda login sebagai
                    <strong><?php echo e($nama_role); ?></strong>.
                    Kelola proses skripsi melalui dashboard ini.
                </p>

                <div class="welcome-role">
                    <i class="bi bi-shield-check"></i>
                    Akun <?php echo e($status_aktif); ?>
                </div>

            </div>

        </section>


        <!-- STATISTIK -->

        <div class="section-header">

            <h3>
                Ringkasan Sistem
            </h3>

            <a href="#">
                Lihat detail
                <i class="bi bi-arrow-right"></i>
            </a>

        </div>


        <section class="stat-grid">


            <div class="stat-card">

                <div class="stat-icon blue">
                    <i class="bi bi-mortarboard-fill"></i>
                </div>

                <div>

                    <strong>
                        1.250+
                    </strong>

                    <span>
                        Mahasiswa Aktif
                    </span>

                </div>

            </div>


            <div class="stat-card">

                <div class="stat-icon green">
                    <i class="bi bi-people-fill"></i>
                </div>

                <div>

                    <strong>
                        150+
                    </strong>

                    <span>
                        Dosen Pembimbing
                    </span>

                </div>

            </div>


            <div class="stat-card">

                <div class="stat-icon orange">
                    <i class="bi bi-file-earmark-text-fill"></i>
                </div>

                <div>

                    <strong>
                        980+
                    </strong>

                    <span>
                        Judul Skripsi
                    </span>

                </div>

            </div>


            <div class="stat-card">

                <div class="stat-icon purple">
                    <i class="bi bi-check-circle-fill"></i>
                </div>

                <div>

                    <strong>
                        850+
                    </strong>

                    <span>
                        Skripsi Selesai
                    </span>

                </div>

            </div>


        </section>


        <!-- DASHBOARD GRID -->

        <section class="dashboard-grid">


            <!-- =============================================
                 ALUR SKRIPSI
            ============================================== -->

            <div class="panel">

                <div class="panel-header">

                    <h3>
                        Alur Pengelolaan Skripsi
                    </h3>

                    <a href="#">
                        Detail
                    </a>

                </div>


                <div class="workflow">


                    <div class="workflow-item done">

                        <div class="workflow-number">
                            <i class="bi bi-check-lg"></i>
                        </div>

                        <div class="workflow-info">

                            <strong>
                                Pengajuan Judul
                            </strong>

                            <span>
                                Pengajuan dan verifikasi judul skripsi
                            </span>

                        </div>

                        <span class="status done">
                            Selesai
                        </span>

                    </div>


                    <div class="workflow-item done">

                        <div class="workflow-number">
                            <i class="bi bi-check-lg"></i>
                        </div>

                        <div class="workflow-info">

                            <strong>
                                Penetapan Pembimbing
                            </strong>

                            <span>
                                Penentuan dosen pembimbing skripsi
                            </span>

                        </div>

                        <span class="status done">
                            Selesai
                        </span>

                    </div>


                    <div class="workflow-item current">

                        <div class="workflow-number">
                            3
                        </div>

                        <div class="workflow-info">

                            <strong>
                                Bimbingan Skripsi
                            </strong>

                            <span>
                                Monitoring proses konsultasi mahasiswa
                            </span>

                        </div>

                        <span class="status process">
                            Berjalan
                        </span>

                    </div>


                    <div class="workflow-item">

                        <div class="workflow-number">
                            4
                        </div>

                        <div class="workflow-info">

                            <strong>
                                Seminar Proposal
                            </strong>

                            <span>
                                Penjadwalan dan penilaian seminar proposal
                            </span>

                        </div>

                        <span class="status wait">
                            Menunggu
                        </span>

                    </div>


                    <div class="workflow-item">

                        <div class="workflow-number">
                            5
                        </div>

                        <div class="workflow-info">

                            <strong>
                                Sidang Skripsi
                            </strong>

                            <span>
                                Ujian akhir dan penilaian skripsi
                            </span>

                        </div>

                        <span class="status wait">
                            Menunggu
                        </span>

                    </div>


                </div>

            </div>


            <!-- =============================================
                 KOLOM KANAN
            ============================================== -->

            <div>


                <!-- PROFIL -->

                <div class="panel">

                    <div class="panel-header">

                        <h3>
                            Informasi Akun
                        </h3>

                        <a href="#">
                            Profil
                        </a>

                    </div>


                    <div class="profile-body">

                        <div class="profile-top">

                            <div class="profile-avatar">
                                <?php echo e($initial); ?>
                            </div>

                            <div>

                                <strong>
                                    <?php echo e($nama_lengkap); ?>
                                </strong>

                                <span>
                                    <?php echo e($nama_role); ?>
                                </span>

                            </div>

                        </div>


                        <div class="profile-data">


                            <div class="profile-row">

                                <span>
                                    Username
                                </span>

                                <span>
                                    <?php echo e($username); ?>
                                </span>

                            </div>


                            <div class="profile-row">

                                <span>
                                    Email
                                </span>

                                <span>
                                    <?php echo e($email ?: '-'); ?>
                                </span>

                            </div>


                            <div class="profile-row">

                                <span>
                                    ID Pengguna
                                </span>

                                <span>
                                    <?php echo e($id_pengguna); ?>
                                </span>

                            </div>


                            <div class="profile-row">

                                <span>
                                    Kode Role
                                </span>

                                <span>
                                    <?php echo e($kode_role ?: '-'); ?>
                                </span>

                            </div>


                            <div class="profile-row">

                                <span>
                                    Status
                                </span>

                                <span style="color:#15935f;">
                                    <i class="bi bi-check-circle-fill"></i>
                                    <?php echo e($status_aktif); ?>
                                </span>

                            </div>


                        </div>

                    </div>

                </div>


                <!-- AKSI CEPAT -->

                <div class="panel" style="margin-top:18px;">

                    <div class="panel-header">

                        <h3>
                            Akses Cepat
                        </h3>

                    </div>


                    <div class="quick-grid">

                        <a href="#" class="quick-action">
                            <i class="bi bi-file-earmark-plus"></i>
                            Pengajuan Judul
                        </a>

                        <a href="#" class="quick-action">
                            <i class="bi bi-calendar-plus"></i>
                            Jadwal Seminar
                        </a>

                        <a href="#" class="quick-action">
                            <i class="bi bi-people"></i>
                            Data Pembimbing
                        </a>

                        <a href="#" class="quick-action">
                            <i class="bi bi-folder2-open"></i>
                            Dokumen
                        </a>

                    </div>

                </div>


                <!-- AKTIVITAS -->

                <div class="panel" style="margin-top:18px;">

                    <div class="panel-header">

                        <h3>
                            Aktivitas Terbaru
                        </h3>

                        <a href="#">
                            Semua
                        </a>

                    </div>


                    <div class="activity-list">


                        <div class="activity">

                            <div class="activity-icon">
                                <i class="bi bi-person-plus"></i>
                            </div>

                            <div class="activity-info">

                                <strong>
                                    Data pengguna berhasil terautentikasi
                                </strong>

                                <span>
                                    Melalui Web API SIM Mahasiswa
                                </span>

                            </div>

                        </div>


                        <div class="activity">

                            <div class="activity-icon">
                                <i class="bi bi-shield-check"></i>
                            </div>

                            <div class="activity-info">

                                <strong>
                                    Status akun terverifikasi aktif
                                </strong>

                                <span>
                                    Autentikasi berhasil
                                </span>

                            </div>

                        </div>


                        <div class="activity">

                            <div class="activity-icon">
                                <i class="bi bi-clock-history"></i>
                            </div>

                            <div class="activity-info">

                                <strong>
                                    Sesi login dibuat
                                </strong>

                                <span>
                                    <?php
                                    echo date(
                                        'd-m-Y H:i',
                                        $_SESSION['login_time'] ?? time()
                                    );
                                    ?>
                                </span>

                            </div>

                        </div>


                    </div>

                </div>

            </div>

        </section>


    </div>


    <!-- FOOTER -->

    <footer class="footer">

        SIMSKRIPSI &copy;
        <?php echo date('Y'); ?>
        Universitas Muhammadiyah Bengkulu
        &nbsp;•&nbsp;
        Sistem Informasi Manajemen Skripsi

    </footer>


</main>


<script>

    /*
    |--------------------------------------------------------------------------
    | SIDEBAR MOBILE
    |--------------------------------------------------------------------------
    */
    const sidebarToggle =
        document.getElementById('sidebarToggle');

    const body =
        document.body;

    const overlay =
        document.querySelector('.sidebar-overlay');


    sidebarToggle.addEventListener(
        'click',
        function () {

            body.classList.toggle(
                'sidebar-open'
            );

        }
    );


    overlay.addEventListener(
        'click',
        function () {

            body.classList.remove(
                'sidebar-open'
            );

        }
    );


    /*
    |--------------------------------------------------------------------------
    | TUTUP SIDEBAR SETELAH MENU DIPILIH DI MOBILE
    |--------------------------------------------------------------------------
    */
    document
        .querySelectorAll('.menu-item')
        .forEach(function (item) {

            item.addEventListener(
                'click',
                function () {

                    if (window.innerWidth <= 850) {

                        body.classList.remove(
                            'sidebar-open'
                        );

                    }

                }
            );

        });

</script>


</body>
</html>
