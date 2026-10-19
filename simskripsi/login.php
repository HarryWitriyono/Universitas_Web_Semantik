<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/api.php';

if (!empty($_SESSION['user']['id_pengguna'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$old_username = '';
$account_type = 'mahasiswa';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    csrf_verify();
    $account_type = (string)($_POST['account_type'] ?? 'mahasiswa');
    $old_username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if (!in_array($account_type, ['admin', 'dosen', 'mahasiswa'], true)) {
        $error = 'Jenis akun tidak valid.';
    } elseif ($old_username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } else {
        try {
            if ($account_type === 'admin') {
                $db = simskripsi_db();
                $stmt = $db->prepare('SELECT id_admin, nama_lengkap, username, password_hash, status_aktif FROM admin_lokal WHERE username = ? LIMIT 1');
                $stmt->bind_param('s', $old_username);
                $stmt->execute();
                $admin = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                $db->close();

                if ($admin && $admin['status_aktif'] === 'aktif' && password_verify($password, $admin['password_hash'])) {
                    session_regenerate_id(true);
                    $_SESSION['auth_source'] = 'local_admin';
                    $_SESSION['user'] = [
                        'id_pengguna' => 'LOCAL_ADMIN_' . (string)$admin['id_admin'],
                        'id_role' => 'ADMIN_SIMSKRIPSI',
                        'nama_lengkap' => $admin['nama_lengkap'],
                        'username' => $admin['username'],
                        'email' => '',
                        'kode_role' => 'ADMIN_SIMSKRIPSI',
                        'nama_role' => 'Admin SIMSKRIPSI',
                        'status_aktif' => 'aktif'
                    ];
                    $_SESSION['login_time'] = time();
                    unset($_SESSION['_csrf_token']);
                    header('Location: dashboard.php');
                    exit;
                }
                $error = 'Login admin gagal. Periksa username, password, atau status akun.';
            } elseif ($account_type === 'dosen') {
                $db = simskripsi_db();
                $stmt = $db->prepare("SELECT a.id_akun_dosen, a.username, a.password_hash, a.status_aktif AS status_akun, d.id_dosen, d.nidn, d.nama_dosen, d.email, d.status AS status_dosen FROM akun_dosen a INNER JOIN dosen d ON d.id_dosen = a.id_dosen WHERE a.username = ? LIMIT 1");
                $stmt->bind_param('s', $old_username);
                $stmt->execute();
                $dosen = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                $db->close();

                if ($dosen && $dosen['status_akun'] === 'aktif' && $dosen['status_dosen'] === 'aktif' && password_verify($password, $dosen['password_hash'])) {
                    session_regenerate_id(true);
                    $_SESSION['auth_source'] = 'local_dosen';
                    $_SESSION['dosen'] = [
                        'id_dosen' => (int)$dosen['id_dosen'],
                        'nidn' => $dosen['nidn'],
                        'nama_dosen' => $dosen['nama_dosen'],
                        'email' => $dosen['email'] ?? '',
                        'username' => $dosen['username']
                    ];
                    $_SESSION['user'] = [
                        'id_pengguna' => 'DOSEN_' . (string)$dosen['id_dosen'],
                        'id_role' => 'DOSEN',
                        'nama_lengkap' => $dosen['nama_dosen'],
                        'username' => $dosen['username'],
                        'email' => $dosen['email'] ?? '',
                        'kode_role' => 'DOSEN',
                        'nama_role' => 'Dosen',
                        'status_aktif' => 'aktif'
                    ];
                    $_SESSION['login_time'] = time();
                    unset($_SESSION['_csrf_token']);
                    header('Location: dashboard.php');
                    exit;
                }
                $error = 'Login dosen gagal. Periksa username, password, atau status akun.';
            } else {
                $apiLoginUrl = simskripsi_api_login_url();
                if ($apiLoginUrl === '') {
                    $error = 'Alamat API belum dikonfigurasi. Hubungi Admin SIMSKRIPSI.';
                } elseif (!function_exists('curl_init')) {
                    $error = 'Ekstensi cURL PHP belum aktif pada server.';
                } else {
                    $payload = json_encode(['username' => $old_username, 'password' => $password], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
                    $ch = curl_init($apiLoginUrl);
                    curl_setopt_array($ch, [
                        CURLOPT_POST => true,
                        CURLOPT_POSTFIELDS => $payload,
                        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'],
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_CONNECTTIMEOUT => 8,
                        CURLOPT_TIMEOUT => 15,
                        CURLOPT_FOLLOWLOCATION => false,
                        CURLOPT_SSL_VERIFYPEER => true,
                        CURLOPT_SSL_VERIFYHOST => 2
                    ]);
                    $apiResult = curl_exec($ch);
                    $curlError = curl_error($ch);
                    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);

                    if ($apiResult === false) {
                        $error = 'Web API SIM Mahasiswa tidak dapat dihubungi. Periksa konfigurasi API dan koneksi server.';
                    } else {
                        $apiData = json_decode($apiResult, true);
                        if ($httpCode >= 200 && $httpCode < 300 && is_array($apiData) && ($apiData['success'] ?? false) === true && isset($apiData['data']) && is_array($apiData['data']) && !empty($apiData['data']['id_pengguna'])) {
                            $apiUser = $apiData['data'];
                            $role = strtoupper((string)($apiUser['kode_role'] ?? $apiUser['role_code'] ?? ''));
                            // API login hanya untuk mahasiswa dan akun induk yang memang diizinkan.
                            if ($role === '' || in_array($role, ['ADMIN_SIMSKRIPSI', 'DOSEN'], true)) {
                                $error = 'Peran akun dari API tidak valid untuk jalur login ini.';
                            } else {
                                session_regenerate_id(true);
                                $_SESSION['auth_source'] = 'simmahasiswa_api';
                                $_SESSION['user'] = $apiUser;
                                $_SESSION['login_time'] = time();
                                unset($_SESSION['_csrf_token']);
                                header('Location: dashboard.php');
                                exit;
                            }
                        } elseif (!is_array($apiData)) {
                            $error = 'Respons Web API tidak valid. Periksa endpoint login SIM Mahasiswa.';
                        } else {
                            $error = (string)($apiData['message'] ?? 'Login gagal. Periksa username dan password.');
                        }
                    }
                }
            }
        } catch (Throwable $e) {
            // Detail teknis dicatat server; jangan tampilkan query/kredensial kepada pengguna.
            error_log('SIMSKRIPSI login error: ' . $e->getMessage());
            $error = 'Terjadi kesalahan saat memproses login. Periksa konfigurasi aplikasi atau hubungi administrator.';
        }
    }
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

    <title>Login | SIMSKRIPSI UMB</title>

    <meta
        name="description"
        content="Login SIMSKRIPSI Universitas Muhammadiyah Bengkulu"
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
            --muted: #667085;
            --border: #dfe6ef;
            --danger: #dc3545;
            --bg: #f4f8fd;
        }


        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }


        body {
            min-height: 100vh;
            font-family: "Poppins", sans-serif;
            color: var(--text);
            background:
                radial-gradient(
                    circle at 15% 10%,
                    rgba(8,102,216,.10),
                    transparent 30%
                ),
                linear-gradient(
                    135deg,
                    #f7fbff 0%,
                    #eef6ff 50%,
                    #f8fbff 100%
                );

            display: flex;
            align-items: center;
            justify-content: center;

            padding: 25px;
        }


        .login-wrapper {
            width: min(100%, 950px);

            display: grid;
            grid-template-columns: 1fr 430px;

            background: #fff;

            border-radius: 25px;

            overflow: hidden;

            box-shadow:
                0 25px 70px rgba(20,45,85,.13);
        }


        /*
        |--------------------------------------------------------------------------
        | PANEL KIRI
        |--------------------------------------------------------------------------
        */
        .login-info {
            position: relative;

            min-height: 610px;

            padding: 55px;

            display: flex;
            flex-direction: column;
            justify-content: center;

            overflow: hidden;

            color: #fff;

            background:
                linear-gradient(
                    145deg,
                    #0866d8,
                    #064da8
                );
        }


        .login-info::before {
            content: "";

            position: absolute;

            width: 400px;
            height: 400px;

            border-radius: 50%;

            background: rgba(255,255,255,.06);

            right: -180px;
            top: -130px;
        }


        .login-info::after {
            content: "";

            position: absolute;

            width: 320px;
            height: 320px;

            border-radius: 50%;

            background: rgba(255,255,255,.05);

            left: -180px;
            bottom: -170px;
        }


        .info-content {
            position: relative;
            z-index: 2;
        }


        .umb-logo {
            width: 62px;
            height: 62px;

            border-radius: 50%;

            display: grid;
            place-items: center;

            background: #0c8c51;

            border: 3px solid #f0c419;

            margin-bottom: 23px;

            font-size: 27px;
        }


        .info-content .eyebrow {
            display: block;

            font-size: 11px;

            letter-spacing: 2px;

            font-weight: 600;

            opacity: .8;

            margin-bottom: 8px;
        }


        .info-content h1 {
            font-size: 49px;

            line-height: 1;

            letter-spacing: -2px;

            margin-bottom: 10px;
        }


        .info-content h1 span {
            color: #8bc2ff;
        }


        .info-content h2 {
            font-size: 21px;

            line-height: 1.35;

            margin-bottom: 17px;
        }


        .info-content p {
            max-width: 470px;

            font-size: 13px;

            line-height: 1.8;

            color: rgba(255,255,255,.82);
        }


        .benefits {
            list-style: none;

            margin-top: 28px;

            display: grid;

            gap: 13px;
        }


        .benefits li {
            display: flex;

            align-items: center;

            gap: 10px;

            font-size: 12px;

            color: rgba(255,255,255,.9);
        }


        .benefits i {
            color: #7ee3b1;

            font-size: 17px;
        }


        /*
        |--------------------------------------------------------------------------
        | PANEL LOGIN
        |--------------------------------------------------------------------------
        */
        .login-panel {
            padding: 48px 42px;

            display: flex;

            flex-direction: column;

            justify-content: center;
        }


        .login-heading {
            margin-bottom: 30px;
        }


        .login-heading .icon {
            width: 52px;
            height: 52px;

            border-radius: 14px;

            display: grid;
            place-items: center;

            color: var(--primary);

            background: #eaf4ff;

            font-size: 23px;

            margin-bottom: 15px;
        }


        .login-heading h2 {
            font-size: 25px;

            color: var(--navy);

            margin-bottom: 5px;
        }


        .login-heading p {
            font-size: 12px;

            color: var(--muted);
        }


        .alert {
            display: flex;

            align-items: flex-start;

            gap: 9px;

            padding: 12px 13px;

            margin-bottom: 18px;

            border-radius: 10px;

            color: #842029;

            background: #f8d7da;

            border: 1px solid #f1aeb5;

            font-size: 11px;

            line-height: 1.5;
        }


        .alert i {
            font-size: 15px;

            margin-top: 1px;
        }


        .form-group {
            margin-bottom: 17px;
        }


        .form-group label {
            display: block;

            font-size: 12px;

            font-weight: 600;

            color: #35435b;

            margin-bottom: 7px;
        }


        .input-wrap {
            position: relative;
        }


        .input-wrap > i {
            position: absolute;

            left: 14px;

            top: 50%;

            transform: translateY(-50%);

            color: #7d899b;

            font-size: 16px;
        }


        .form-control {
            width: 100%;

            height: 48px;

            border: 1px solid var(--border);

            border-radius: 10px;

            padding: 0 42px;

            outline: none;

            font-family: inherit;

            font-size: 13px;

            color: var(--text);

            background: #fbfdff;

            transition: .2s ease;
        }


        .form-control:focus {
            border-color: #7bb1ed;

            background: #fff;

            box-shadow:
                0 0 0 4px rgba(8,102,216,.08);
        }


        .password-toggle {
            position: absolute;

            right: 13px;

            top: 50%;

            transform: translateY(-50%);

            border: 0;

            background: transparent;

            color: #7d899b;

            cursor: pointer;

            font-size: 16px;
        }


        .login-submit {
            width: 100%;

            height: 49px;

            border: 0;

            border-radius: 10px;

            color: #fff;

            background: var(--primary);

            font-family: inherit;

            font-size: 13px;

            font-weight: 600;

            cursor: pointer;

            box-shadow:
                0 8px 20px rgba(8,102,216,.20);

            transition: .2s ease;
        }


        .login-submit:hover {
            background: var(--primary-dark);

            transform: translateY(-1px);
        }


        .back-home {
            text-align: center;

            margin-top: 20px;

            font-size: 11px;

            color: #68758a;
        }


        .back-home a {
            color: var(--primary);

            font-weight: 600;
        }


        .security-note {
            display: flex;

            align-items: center;

            justify-content: center;

            gap: 6px;

            margin-top: 28px;

            padding-top: 17px;

            border-top: 1px solid #edf0f4;

            color: #8a94a4;

            font-size: 9px;
        }


        .security-note i {
            color: #18a66a;
        }


        @media (max-width: 800px) {

            body {
                padding: 15px;
            }


            .login-wrapper {
                grid-template-columns: 1fr;

                max-width: 500px;
            }


            .login-info {
                min-height: auto;

                padding: 35px;
            }


            .info-content h1 {
                font-size: 40px;
            }


            .info-content h2 {
                font-size: 18px;
            }


            .benefits {
                grid-template-columns: 1fr 1fr;
            }


            .login-panel {
                padding: 35px;
            }
        }


        @media (max-width: 480px) {

            .login-info {
                padding: 28px;
            }


            .login-panel {
                padding: 30px 23px;
            }


            .benefits {
                grid-template-columns: 1fr;
            }


            .info-content h1 {
                font-size: 36px;
            }
        }

    </style>

</head>


<body>

<div class="login-wrapper">


    <!-- =====================================================
         PANEL INFORMASI
    ====================================================== -->
    <section class="login-info">

        <div class="info-content">

            <div class="umb-logo">
                <i class="bi bi-mortarboard-fill"></i>
            </div>

            <span class="eyebrow">
                SISTEM INFORMASI MANAJEMEN SKRIPSI
            </span>

            <h1>
                SIM<span>SKRIPSI</span>
            </h1>

            <h2>
                Universitas Muhammadiyah Bengkulu
            </h2>

            <p>
                Kelola proses skripsi secara terintegrasi,
                mulai dari pengajuan judul, pembimbing,
                seminar proposal, bimbingan, hingga sidang skripsi.
            </p>


            <ul class="benefits">

                <li>
                    <i class="bi bi-check-circle-fill"></i>
                    Pengajuan judul secara online
                </li>

                <li>
                    <i class="bi bi-check-circle-fill"></i>
                    Monitoring proses bimbingan
                </li>

                <li>
                    <i class="bi bi-check-circle-fill"></i>
                    Pengelolaan seminar dan sidang
                </li>

                <li>
                    <i class="bi bi-check-circle-fill"></i>
                    Arsip dokumen skripsi terintegrasi
                </li>

            </ul>

        </div>

    </section>


    <!-- =====================================================
         PANEL FORM LOGIN
    ====================================================== -->
    <section class="login-panel">

        <div class="login-heading">

            <div class="icon">
                <i class="bi bi-person-lock"></i>
            </div>

            <h2>Masuk ke SIMSKRIPSI</h2>

            <p>
                Silakan masukkan username dan password Anda.
            </p>

        </div>


        <?php if ($error !== ''): ?>

            <div class="alert">
                <i class="bi bi-exclamation-triangle-fill"></i>

                <span>
                    <?php echo htmlspecialchars(
                        $error,
                        ENT_QUOTES,
                        'UTF-8'
                    ); ?>
                </span>
            </div>

        <?php endif; ?>


        <form
            method="POST"
            action=""
            autocomplete="on"
        >

            <?php echo csrf_field(); ?>

            <div class="form-group">
                <label for="account_type">Jenis Akun</label>
                <div class="input-wrap">
                    <i class="bi bi-person-badge"></i>
                    <select id="account_type" name="account_type" class="form-control" required style="padding-right:14px;">
                        <option value="mahasiswa" <?php echo $account_type === 'mahasiswa' ? 'selected' : ''; ?>>Mahasiswa / Pengguna SIM Mahasiswa</option>
                        <option value="dosen" <?php echo $account_type === 'dosen' ? 'selected' : ''; ?>>Dosen</option>
                        <option value="admin" <?php echo $account_type === 'admin' ? 'selected' : ''; ?>>Admin SIMSKRIPSI</option>
                    </select>
                </div>
            </div>

            <!-- USERNAME -->
            <div class="form-group">

                <label for="username">
                    Username
                </label>

                <div class="input-wrap">

                    <i class="bi bi-person"></i>

                    <input
                        type="text"
                        id="username"
                        name="username"
                        class="form-control"
                        value="<?php echo htmlspecialchars(
                            $old_username,
                            ENT_QUOTES,
                            'UTF-8'
                        ); ?>"
                        placeholder="Masukkan username"
                        autocomplete="username"
                        required
                        autofocus
                    >

                </div>

            </div>


            <!-- PASSWORD -->
            <div class="form-group">

                <label for="password">
                    Password
                </label>

                <div class="input-wrap">

                    <i class="bi bi-lock"></i>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control"
                        placeholder="Masukkan password"
                        autocomplete="current-password"
                        required
                    >

                    <button
                        type="button"
                        class="password-toggle"
                        id="togglePassword"
                        aria-label="Tampilkan password"
                    >
                        <i class="bi bi-eye"></i>
                    </button>

                </div>

            </div>


            <!-- SUBMIT -->
            <button
                type="submit"
                class="login-submit"
            >
                <i class="bi bi-box-arrow-in-right"></i>
                &nbsp; Masuk
            </button>

        </form>


        <div class="back-home">

            <a href="index.php">
                <i class="bi bi-arrow-left"></i>
                Kembali ke halaman utama
            </a>

        </div>


        <div class="security-note">

            <i class="bi bi-shield-check"></i>

            Admin dan dosen: login lokal · Mahasiswa: Web API SIM Mahasiswa

        </div>

    </section>

</div>


<script>

    /*
    |--------------------------------------------------------------------------
    | SHOW / HIDE PASSWORD
    |--------------------------------------------------------------------------
    */
    const togglePassword =
        document.getElementById('togglePassword');

    const password =
        document.getElementById('password');


    togglePassword.addEventListener(
        'click',
        function () {

            const type =
                password.getAttribute('type') === 'password'
                    ? 'text'
                    : 'password';


            password.setAttribute(
                'type',
                type
            );


            this.innerHTML =
                type === 'password'
                    ? '<i class="bi bi-eye"></i>'
                    : '<i class="bi bi-eye-slash"></i>';

        }
    );

</script>

</body>
</html>
