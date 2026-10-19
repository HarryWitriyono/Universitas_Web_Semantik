<?php
/*
 * Landing Page SIM Dosen UMB
 * Mockup awal untuk bahan praktikum mahasiswa.
 * PHP Native; halaman ini belum terhubung ke database.
 */
$nama_aplikasi = "SIM Dosen";
$nama_universitas = "Universitas Muhammadiyah Bengkulu";
$tahun = date("Y");
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($nama_aplikasi) ?> | <?= htmlspecialchars($nama_universitas) ?></title>
    <meta name="description" content="Sistem Informasi Manajemen Dosen Universitas Muhammadiyah Bengkulu.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --umb-blue: #1458b8;
            --umb-blue-dark: #0b2d59;
            --umb-blue-soft: #edf4ff;
            --umb-gold: #f2b544;
            --text-main: #182538;
            --text-muted: #65748a;
        }
        html { scroll-behavior: smooth; }
        body {
            font-family: Inter, system-ui, -apple-system, "Segoe UI", sans-serif;
            color: var(--text-main);
            background: #fff;
        }
        .navbar {
            background: rgba(255,255,255,.96);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid #e9eef5;
        }
        .brand-mark {
            display: inline-grid; place-items: center;
            width: 44px; height: 44px; border-radius: 13px;
            color: white; background: var(--umb-blue);
            font-size: 1.35rem;
        }
        .navbar-brand { font-weight: 800; letter-spacing: -.03em; color: var(--umb-blue-dark); }
        .navbar-brand small { display: block; font-size: .68rem; font-weight: 500; letter-spacing: 0; color: var(--text-muted); }
        .nav-link { font-weight: 600; color: #40516a; }
        .nav-link:hover, .nav-link.active { color: var(--umb-blue); }
        .btn-primary {
            --bs-btn-bg: var(--umb-blue); --bs-btn-border-color: var(--umb-blue);
            --bs-btn-hover-bg: #104a9b; --bs-btn-hover-border-color: #104a9b;
            border-radius: 10px; font-weight: 700; padding: .78rem 1.15rem;
        }
        .btn-outline-primary {
            --bs-btn-color: var(--umb-blue); --bs-btn-border-color: #b9cdef;
            --bs-btn-hover-bg: var(--umb-blue); --bs-btn-hover-border-color: var(--umb-blue);
            border-radius: 10px; font-weight: 700; padding: .78rem 1.15rem;
        }
        .hero {
            position: relative; overflow: hidden;
            padding: 6.5rem 0 5rem;
            background:
              radial-gradient(circle at 82% 20%, rgba(79,145,236,.17), transparent 27rem),
              linear-gradient(135deg, #f7faff 0%, #edf4ff 56%, #fff 100%);
        }
        .hero::after {
            content: ""; position: absolute; width: 360px; height: 360px;
            border: 1px solid rgba(20,88,184,.12); border-radius: 50%;
            right: -140px; bottom: -210px;
        }
        .eyebrow {
            display: inline-flex; align-items: center; gap: .5rem;
            padding: .48rem .8rem; border-radius: 999px;
            color: var(--umb-blue); background: #e1edff;
            font-size: .83rem; font-weight: 800;
        }
        .hero h1 {
            max-width: 680px; margin: 1.3rem 0 1rem;
            font-size: clamp(2.5rem, 5vw, 4.4rem);
            line-height: 1.04; font-weight: 850; letter-spacing: -.055em;
            color: var(--umb-blue-dark);
        }
        .hero h1 span { color: var(--umb-blue); }
        .hero-copy { max-width: 590px; color: var(--text-muted); font-size: 1.08rem; line-height: 1.8; }
        .hero-visual {
            min-height: 380px; height: 100%; position: relative;
            display: flex; align-items: center; justify-content: center;
        }
        .visual-circle {
            width: min(100%, 390px); aspect-ratio: 1/1; border-radius: 50%;
            background: linear-gradient(145deg, #d8e8ff, #f5f9ff);
            border: 1px solid #d5e5fc; position: absolute;
        }
        .visual-card {
            z-index: 1; width: min(90%, 340px); padding: 1.6rem;
            border-radius: 22px; background: rgba(255,255,255,.94);
            border: 1px solid rgba(255,255,255,.9);
            box-shadow: 0 24px 70px rgba(24,64,119,.15);
        }
        .visual-icon {
            width: 72px; height: 72px; display: grid; place-items: center;
            border-radius: 20px; color: white; background: var(--umb-blue);
            font-size: 2.2rem; margin-bottom: 1.1rem;
        }
        .mini-stat { background: #f4f8ff; border-radius: 12px; padding: .85rem; }
        .mini-stat strong { display: block; color: var(--umb-blue-dark); font-size: 1.2rem; }
        .mini-stat span { color: var(--text-muted); font-size: .76rem; }
        .section-space { padding: 5rem 0; }
        .section-kicker { color: var(--umb-blue); text-transform: uppercase; letter-spacing: .12em; font-size: .75rem; font-weight: 850; }
        .section-title { color: var(--umb-blue-dark); font-weight: 850; letter-spacing: -.035em; }
        .section-subtitle { max-width: 680px; margin-inline: auto; color: var(--text-muted); line-height: 1.8; }
        .feature-card {
            height: 100%; padding: 1.6rem; border: 1px solid #e7edf5;
            border-radius: 18px; background: #fff; transition: .2s ease;
        }
        .feature-card:hover { transform: translateY(-4px); box-shadow: 0 16px 35px rgba(26,58,100,.08); }
        .feature-icon {
            width: 52px; height: 52px; display: grid; place-items: center;
            border-radius: 15px; font-size: 1.35rem; margin-bottom: 1.2rem;
        }
        .icon-blue { color: #1458b8; background: #e8f1ff; }
        .icon-green { color: #13836b; background: #e3f7f0; }
        .icon-orange { color: #bd6a0b; background: #fff1dc; }
        .icon-purple { color: #7950bd; background: #f0e8ff; }
        .feature-card h3 { font-size: 1.08rem; font-weight: 800; }
        .feature-card p { margin-bottom: 0; color: var(--text-muted); line-height: 1.7; font-size: .94rem; }
        .integration { background: #f6f9fd; }
        .integration-box {
            height: 100%; border: 1px solid #e1eaf5; background: #fff;
            border-radius: 16px; padding: 1.35rem;
        }
        .integration-box .step {
            display: inline-grid; place-items: center; width: 34px; height: 34px;
            border-radius: 10px; color: #fff; background: var(--umb-blue); font-weight: 800;
        }
        .cta-panel {
            padding: clamp(2rem, 5vw, 3.5rem); border-radius: 24px;
            color: #fff; background: linear-gradient(120deg, #0b2d59, #1458b8);
        }
        .cta-panel p { color: rgba(255,255,255,.78); }
        footer { background: #091f3d; color: rgba(255,255,255,.72); }
        footer a { color: rgba(255,255,255,.85); text-decoration: none; }
        footer a:hover { color: #fff; }
        @media (max-width: 991.98px) {
            .hero { padding-top: 4.5rem; }
            .hero-visual { min-height: 330px; margin-top: 2rem; }
        }
        @media (max-width: 575.98px) {
            .hero { padding: 3.5rem 0; }
            .hero-visual { min-height: 300px; }
            .section-space { padding: 3.7rem 0; }
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg sticky-top">
    <div class="container py-2">
        <a class="navbar-brand d-flex align-items-center gap-2" href="#beranda">
            <span class="brand-mark"><i class="bi bi-mortarboard-fill"></i></span>
            <span>SIM DOSEN<small>Universitas Muhammadiyah Bengkulu</small></span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuUtama"
                aria-controls="menuUtama" aria-expanded="false" aria-label="Buka navigasi">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="menuUtama">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                <li class="nav-item"><a class="nav-link active" href="#beranda">Beranda</a></li>
                <li class="nav-item"><a class="nav-link" href="#fitur">Fitur</a></li>
                <li class="nav-item"><a class="nav-link" href="#integrasi">Integrasi API</a></li>
                <li class="nav-item"><a class="nav-link" href="#tentang">Tentang</a></li>
                <li class="nav-item ms-lg-2"><a class="btn btn-primary" href="login.php"><i class="bi bi-box-arrow-in-right me-1"></i> Login Sistem</a></li>
            </ul>
        </div>
    </div>
</nav>

<main>
    <section class="hero" id="beranda">
        <div class="container position-relative">
            <div class="row align-items-center g-4">
                <div class="col-lg-7">
                    <span class="eyebrow"><i class="bi bi-stars"></i> Sistem Informasi Akademik Terintegrasi</span>
                    <h1>Kenali dan Kelola Data <span>Dosen</span> Lebih Mudah.</h1>
                    <p class="hero-copy">
                        <?= htmlspecialchars($nama_aplikasi) ?> merupakan rancangan sistem untuk membantu pengelolaan
                        data dosen dan penempatan program studi, sekaligus menyediakan layanan data bagi aplikasi akademik lain.
                    </p>
                    <div class="d-flex flex-wrap gap-3 mt-4">
                        <a class="btn btn-primary" href="login.php"><i class="bi bi-lock-fill me-2"></i>Login Sekarang</a>
                        <a class="btn btn-outline-primary" href="#fitur"><i class="bi bi-grid me-2"></i>Lihat Fitur</a>
                    </div>
                    <div class="d-flex flex-wrap gap-4 mt-4 small text-secondary">
                        <span><i class="bi bi-check-circle-fill text-primary me-1"></i>Data terpusat</span>
                        <span><i class="bi bi-check-circle-fill text-primary me-1"></i>Berbasis web</span>
                        <span><i class="bi bi-check-circle-fill text-primary me-1"></i>Siap integrasi API</span>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="hero-visual">
                        <div class="visual-circle"></div>
                        <div class="visual-card">
                            <div class="visual-icon"><i class="bi bi-mortarboard-fill"></i></div>
                            <div class="small text-primary fw-bold mb-2">PORTAL AKADEMIK UMB</div>
                            <h2 class="h4 fw-bold mb-2">Data Dosen Terintegrasi</h2>
                            <p class="text-secondary small mb-4">Satu sumber data untuk mendukung layanan akademik dan pengembangan aplikasi.</p>
                            <div class="row g-2">
                                <div class="col-6"><div class="mini-stat"><strong><i class="bi bi-person-vcard"></i></strong><span>Profil Dosen</span></div></div>
                                <div class="col-6"><div class="mini-stat"><strong><i class="bi bi-diagram-3"></i></strong><span>Data Penempatan</span></div></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="section-space" id="fitur">
        <div class="container">
            <div class="text-center mb-5">
                <div class="section-kicker mb-2">Fitur Utama</div>
                <h2 class="section-title display-6">Satu Portal untuk Pengelolaan Dosen</h2>
                <p class="section-subtitle mt-3">Fitur berikut merupakan rancangan awal yang dapat dikembangkan bertahap dalam praktikum PHP Native, MySQLi, Bootstrap, dan REST API.</p>
            </div>
            <div class="row g-4">
                <div class="col-sm-6 col-xl-3">
                    <article class="feature-card">
                        <div class="feature-icon icon-blue"><i class="bi bi-person-vcard-fill"></i></div>
                        <h3>Data Dosen</h3>
                        <p>Pengelolaan profil dosen, identitas, dan informasi dasar melalui proses CRUD.</p>
                    </article>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <article class="feature-card">
                        <div class="feature-icon icon-green"><i class="bi bi-diagram-3-fill"></i></div>
                        <h3>Penempatan Dosen</h3>
                        <p>Mengelola program studi tempat dosen bernaung sesuai penempatan yang berlaku.</p>
                    </article>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <article class="feature-card">
                        <div class="feature-icon icon-orange"><i class="bi bi-building"></i></div>
                        <h3>Referensi Akademik</h3>
                        <p>Mengacu pada data fakultas dan program studi yang disediakan oleh API SIM Mahasiswa.</p>
                    </article>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <article class="feature-card">
                        <div class="feature-icon icon-purple"><i class="bi bi-braces"></i></div>
                        <h3>REST API</h3>
                        <p>Menyediakan data dosen dalam format JSON agar dapat digunakan aplikasi lain, termasuk SIM Skripsi.</p>
                    </article>
                </div>
            </div>
        </div>
    </section>

    <section class="integration section-space" id="integrasi">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-5">
                    <div class="section-kicker mb-2">Arsitektur Sistem</div>
                    <h2 class="section-title display-6">Dirancang untuk Saling Terhubung</h2>
                    <p class="text-secondary lh-lg mt-3">SIM Dosen menjadi sumber utama data dosen. Sistem lain mengakses data melalui API sehingga data dosen tidak perlu disalin ke banyak database.</p>
                    <p class="text-secondary small"><i class="bi bi-info-circle me-1"></i>Diagram ini menggambarkan rancangan integrasi, bukan koneksi API yang sudah aktif.</p>
                </div>
                <div class="col-lg-7">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="integration-box">
                                <span class="step mb-3">1</span>
                                <h3 class="h6 fw-bold">API SIM Mahasiswa</h3>
                                <p class="small text-secondary mb-0">Menyediakan referensi fakultas dan program studi yang dipakai SIM Dosen.</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="integration-box">
                                <span class="step mb-3">2</span>
                                <h3 class="h6 fw-bold">SIM Dosen</h3>
                                <p class="small text-secondary mb-0">Mengelola data dosen dan menyediakan endpoint REST API berformat JSON.</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="integration-box">
                                <span class="step mb-3">3</span>
                                <h3 class="h6 fw-bold">Database SIM Dosen</h3>
                                <p class="small text-secondary mb-0">Menyimpan data dosen dan referensi ID dari layanan SIM Mahasiswa.</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="integration-box">
                                <span class="step mb-3">4</span>
                                <h3 class="h6 fw-bold">SIM Skripsi</h3>
                                <p class="small text-secondary mb-0">Mengonsumsi API SIM Dosen untuk memilih atau menampilkan data dosen pembimbing.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="section-space" id="tentang">
        <div class="container">
            <div class="cta-panel">
                <div class="row align-items-center g-4">
                    <div class="col-lg-8">
                        <div class="small text-uppercase fw-bold mb-2" style="letter-spacing:.12em;color:#bcd7ff">Mulai dari dasar</div>
                        <h2 class="display-6 fw-bold mb-3">Dari halaman web menjadi layanan data.</h2>
                        <p class="mb-0">Mahasiswa dapat mempelajari pengembangan secara bertahap: landing page, autentikasi, CRUD, database, REST API, lalu integrasi antaraplikasi.</p>
                    </div>
                    <div class="col-lg-4 text-lg-end">
                        <a href="login.php" class="btn btn-light fw-bold px-4 py-3" style="border-radius:10px;color:#104a9b">
                            Masuk ke Sistem <i class="bi bi-arrow-right ms-2"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<footer class="py-4">
    <div class="container d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
        <div class="fw-semibold"><i class="bi bi-mortarboard-fill me-2"></i>SIM Dosen UMB</div>
        <div class="small">© <?= $tahun ?> <?= htmlspecialchars($nama_universitas) ?> · Mockup pengembangan akademik</div>
    </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
