<?php
/*
 * SIMSKRIPSI - Landing Page
 * Universitas Muhammadiyah Bengkulu
 * File: index.php
 *
 * Versi awal: landing page mandiri.
 * CSS sengaja ditempatkan di file ini agar mudah dipelajari,
 * dipindahkan, dan dikembangkan ke struktur project berikutnya.
 */
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>SIMSKRIPSI | Universitas Muhammadiyah Bengkulu</title>
    <meta name="description" content="Sistem Informasi Manajemen Skripsi Universitas Muhammadiyah Bengkulu">

    <!-- Google Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        :root {
            --primary: #0866d8;
            --primary-dark: #064aa1;
            --primary-light: #eaf4ff;
            --navy: #14213d;
            --text: #26334d;
            --muted: #667085;
            --border: #e4eaf2;
            --white: #ffffff;
            --bg: #f7faff;
            --success: #18a66a;
            --shadow: 0 15px 40px rgba(20, 45, 85, .10);
            --radius: 18px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: "Poppins", sans-serif;
            color: var(--text);
            background: var(--white);
            line-height: 1.6;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        img {
            max-width: 100%;
            display: block;
        }

        .container {
            width: min(1180px, calc(100% - 40px));
            margin: auto;
        }

        /* =========================
           NAVBAR
        ========================= */
        .navbar {
            position: sticky;
            top: 0;
            z-index: 1000;
            background: rgba(255,255,255,.96);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(228,234,242,.8);
        }

        .nav-inner {
            min-height: 72px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 30px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 11px;
            min-width: 270px;
        }

        .brand-logo {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: #0c8c51;
            border: 3px solid #f0c419;
            display: grid;
            place-items: center;
            color: #fff;
            font-size: 21px;
            box-shadow: 0 4px 12px rgba(0,0,0,.10);
        }

        .brand-text strong {
            display: block;
            font-size: 27px;
            line-height: 1;
            letter-spacing: -.8px;
            color: var(--navy);
        }

        .brand-text strong span {
            color: var(--primary);
        }

        .brand-text small {
            display: block;
            margin-top: 3px;
            font-size: 11px;
            font-weight: 500;
            color: var(--navy);
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 32px;
        }

        .nav-links a {
            position: relative;
            font-size: 14px;
            font-weight: 500;
            color: #17223a;
            padding: 25px 0;
        }

        .nav-links a:hover,
        .nav-links a.active {
            color: var(--primary);
        }

        .nav-links a.active::after {
            content: "";
            position: absolute;
            height: 3px;
            border-radius: 4px;
            left: 0;
            right: 0;
            bottom: 16px;
            background: var(--primary);
        }

        .login-btn {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            color: #fff;
            background: var(--primary);
            border-radius: 11px;
            padding: 11px 19px;
            font-size: 14px;
            font-weight: 600;
            transition: .2s ease;
            box-shadow: 0 7px 18px rgba(8,102,216,.18);
        }

        .login-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }

        .mobile-toggle {
            display: none;
            border: 0;
            background: transparent;
            color: var(--navy);
            font-size: 27px;
            cursor: pointer;
        }

        /* =========================
           HERO
        ========================= */
        .hero {
            position: relative;
            overflow: hidden;
            min-height: 460px;
            background:
                radial-gradient(circle at 80% 25%, rgba(255,255,255,.95), transparent 28%),
                linear-gradient(100deg, #f3f9ff 0%, #eef7ff 54%, #dceefe 100%);
        }

        .hero::before {
            content: "";
            position: absolute;
            width: 480px;
            height: 480px;
            border-radius: 50%;
            background: rgba(255,255,255,.35);
            right: -160px;
            top: -180px;
        }

        .hero-inner {
            min-height: 460px;
            display: grid;
            grid-template-columns: 1.03fr .97fr;
            align-items: center;
            gap: 35px;
            position: relative;
            z-index: 2;
        }

        .eyebrow {
            display: inline-block;
            color: #55719d;
            font-size: 12px;
            letter-spacing: 2px;
            font-weight: 600;
            margin-bottom: 7px;
        }

        .hero h1 {
            font-size: clamp(48px, 6vw, 72px);
            line-height: .98;
            letter-spacing: -2px;
            color: var(--navy);
            font-weight: 800;
        }

        .hero h1 span {
            color: var(--primary);
        }

        .hero h2 {
            font-size: 24px;
            line-height: 1.25;
            margin: 9px 0 12px;
            color: var(--navy);
        }

        .hero p {
            max-width: 590px;
            font-size: 16px;
            color: #42506a;
            line-height: 1.75;
        }

        .hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
            margin-top: 25px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            min-height: 52px;
            padding: 0 23px;
            border-radius: 11px;
            font-weight: 600;
            font-size: 14px;
            transition: .2s ease;
        }

        .btn-primary {
            color: #fff;
            background: var(--primary);
            box-shadow: 0 9px 22px rgba(8,102,216,.22);
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn-outline {
            background: rgba(255,255,255,.65);
            border: 1px solid #8eb3df;
            color: #174c90;
        }

        .btn-outline:hover {
            background: #fff;
            transform: translateY(-2px);
        }

        .hero-visual {
            position: relative;
            min-height: 430px;
            display: flex;
            align-items: flex-end;
            justify-content: center;
        }

        .campus-card {
            position: absolute;
            right: -35px;
            top: 48px;
            width: 570px;
            height: 360px;
            overflow: hidden;
            border-radius: 0 0 0 110px;
            background:
                linear-gradient(145deg, rgba(255,255,255,.25), rgba(38,112,184,.18)),
                linear-gradient(135deg, #dbeeff, #a7d1f3 45%, #e9f6ff);
            box-shadow: -25px 30px 60px rgba(31,87,143,.14);
        }

        .campus-building {
            position: absolute;
            right: 45px;
            top: 40px;
            width: 370px;
            height: 250px;
            background: linear-gradient(145deg, #fff, #dfeaf4);
            clip-path: polygon(9% 35%, 50% 0, 91% 35%, 100% 100%, 0 100%);
            box-shadow: 0 18px 25px rgba(20,65,105,.14);
        }

        .campus-building::before {
            content: "UNIVERSITAS";
            position: absolute;
            left: 100px;
            top: 82px;
            color: #35638c;
            font-size: 16px;
            font-weight: 700;
            letter-spacing: 1px;
        }

        .campus-building::after {
            content: "MUHAMMADIYAH BENGKULU";
            position: absolute;
            left: 77px;
            top: 107px;
            color: #35638c;
            font-size: 14px;
            font-weight: 700;
        }

        .students {
            position: absolute;
            left: 15px;
            bottom: 0;
            width: 360px;
            height: 310px;
        }

        .person {
            position: absolute;
            bottom: 0;
            border-radius: 45% 45% 0 0;
        }

        .person.one {
            left: 115px;
            width: 130px;
            height: 250px;
            background: linear-gradient(120deg, #17233e 0 40%, #cbd6e6 41% 100%);
        }

        .person.one::before,
        .person.two::before,
        .person.three::before {
            content: "";
            position: absolute;
            border-radius: 50%;
            width: 78px;
            height: 78px;
            top: -58px;
            left: 27px;
            background: #d5a27d;
        }

        .person.two {
            left: 195px;
            width: 105px;
            height: 225px;
            background: linear-gradient(110deg, #e5d4c7 0 30%, #bda88e 31% 60%, #f4f1ed 61%);
            z-index: 2;
        }

        .person.two::before {
            width: 72px;
            height: 72px;
            top: -53px;
            left: 16px;
            background: #bd886d;
        }

        .person.three {
            left: 270px;
            width: 82px;
            height: 200px;
            background: linear-gradient(110deg, #202b3d 0 55%, #1b5d91 56%);
            z-index: 3;
        }

        .person.three::before {
            width: 65px;
            height: 65px;
            top: -48px;
            left: 9px;
            background: #6f493e;
        }

        .quote-card {
            position: absolute;
            right: 0;
            bottom: 35px;
            background: #fff;
            padding: 15px 20px;
            width: 235px;
            border-radius: 14px;
            box-shadow: var(--shadow);
            font-size: 13px;
            font-weight: 500;
            color: #1c3559;
        }

        .quote-card i {
            color: var(--primary);
            margin-right: 7px;
        }

        /* =========================
           FEATURES
        ========================= */
        .features {
            padding: 18px 0 0;
            position: relative;
            z-index: 5;
        }

        .feature-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 12px;
        }

        .feature-card {
            min-height: 145px;
            padding: 20px 13px;
            text-align: center;
            border-radius: 12px;
            border: 1px solid rgba(255,255,255,.6);
            transition: .25s ease;
        }

        .feature-card:nth-child(1) { background: #eef7ff; }
        .feature-card:nth-child(2) { background: #effbf5; }
        .feature-card:nth-child(3) { background: #fff5ea; }
        .feature-card:nth-child(4) { background: #f5efff; }
        .feature-card:nth-child(5) { background: #fff0f2; }
        .feature-card:nth-child(6) { background: #eafaff; }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow);
        }

        .feature-icon {
            width: 42px;
            height: 42px;
            margin: 0 auto 9px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            background: rgba(255,255,255,.75);
            color: var(--primary);
            font-size: 21px;
        }

        .feature-card h3 {
            font-size: 14px;
            color: #18335b;
            margin-bottom: 4px;
        }

        .feature-card p {
            font-size: 11px;
            line-height: 1.45;
            color: #5b6677;
        }

        /* =========================
           STATS
        ========================= */
        .stats {
            margin-top: 15px;
            padding: 19px 30px;
            background: #f1f5fa;
            border-radius: 13px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
        }

        .stat {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            border-right: 1px solid #d6dee9;
        }

        .stat:last-child {
            border-right: 0;
        }

        .stat i {
            font-size: 29px;
            color: var(--primary);
        }

        .stat strong {
            display: block;
            font-size: 23px;
            line-height: 1.1;
            color: var(--primary);
        }

        .stat span {
            display: block;
            color: #536073;
            font-size: 12px;
        }

        /* =========================
           WHY SECTION
        ========================= */
        .why {
            padding: 30px 0 55px;
        }

        .why-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: center;
        }

        .dashboard-wrap {
            position: relative;
            padding: 30px 25px 0;
        }

        .laptop {
            background: #17213a;
            border-radius: 15px 15px 5px 5px;
            padding: 10px;
            box-shadow: 0 20px 50px rgba(17,41,76,.18);
        }

        .laptop-screen {
            background: #f5f8fc;
            min-height: 275px;
            border-radius: 8px;
            overflow: hidden;
            display: grid;
            grid-template-columns: 105px 1fr;
        }

        .mock-sidebar {
            background: #17213a;
            color: #fff;
            padding: 14px 9px;
        }

        .mock-logo {
            font-size: 11px;
            font-weight: 700;
            margin-bottom: 22px;
            color: #fff;
        }

        .mock-logo span {
            color: #2f91ff;
        }

        .mock-menu {
            display: flex;
            gap: 7px;
            align-items: center;
            font-size: 8px;
            padding: 7px 4px;
            border-radius: 5px;
            margin-bottom: 3px;
            color: #c8d2e3;
        }

        .mock-menu.active {
            background: #0a67da;
            color: #fff;
        }

        .mock-main {
            padding: 17px;
        }

        .mock-title {
            font-size: 13px;
            font-weight: 700;
            color: #253553;
            margin-bottom: 13px;
        }

        .welcome {
            background: #fff;
            border-radius: 8px;
            padding: 11px;
            margin-bottom: 11px;
            font-size: 9px;
            color: #647089;
            box-shadow: 0 3px 12px rgba(20,45,85,.05);
        }

        .welcome strong {
            display: block;
            font-size: 12px;
            color: #253553;
        }

        .mock-cards {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 7px;
        }

        .mock-card {
            background: #fff;
            border-radius: 7px;
            padding: 10px 6px;
            font-size: 8px;
            text-align: center;
            color: #3f4c61;
        }

        .mock-card i {
            display: block;
            font-size: 16px;
            margin-bottom: 4px;
            color: var(--primary);
        }

        .progress-box {
            margin-top: 10px;
            background: #fff;
            border-radius: 7px;
            padding: 10px;
            font-size: 8px;
            color: #667085;
        }

        .progress {
            height: 8px;
            background: #e6edf5;
            border-radius: 20px;
            overflow: hidden;
            margin-top: 5px;
        }

        .progress span {
            display: block;
            height: 100%;
            width: 60%;
            background: var(--primary);
            border-radius: 20px;
        }

        .laptop-base {
            width: 112%;
            margin-left: -6%;
            height: 13px;
            border-radius: 0 0 25px 25px;
            background: #b8c1cd;
        }

        .why-content .eyebrow {
            margin-bottom: 8px;
        }

        .why-content h2 {
            font-size: 31px;
            line-height: 1.25;
            color: var(--navy);
            margin-bottom: 13px;
            letter-spacing: -.8px;
        }

        .why-content > p {
            font-size: 14px;
            line-height: 1.75;
            color: #59667b;
        }

        .check-list {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 11px 25px;
            margin-top: 18px;
        }

        .check {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            font-size: 12px;
            color: #49566a;
        }

        .check i {
            color: var(--success);
            font-size: 15px;
            flex-shrink: 0;
        }

        /* =========================
           CTA
        ========================= */
        .cta {
            background: linear-gradient(120deg, #0866d8, #084ca2);
            padding: 38px 0;
            color: #fff;
        }

        .cta-inner {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 25px;
        }

        .cta h2 {
            font-size: 27px;
            margin-bottom: 5px;
        }

        .cta p {
            font-size: 13px;
            opacity: .85;
        }

        .cta .btn {
            background: #fff;
            color: var(--primary);
            white-space: nowrap;
        }

        /* =========================
           FOOTER
        ========================= */
        footer {
            background: #17213a;
            color: #c7d0df;
            padding: 25px 0;
        }

        .footer-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 30px;
        }

        .footer-brand {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .footer-logo {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #0c8c51;
            border: 2px solid #f0c419;
            display: grid;
            place-items: center;
            color: #fff;
        }

        .footer-brand strong {
            display: block;
            color: #fff;
            font-size: 16px;
        }

        .footer-brand strong span {
            color: #2f91ff;
        }

        .footer-brand small {
            display: block;
            font-size: 8px;
        }

        .footer-links {
            display: flex;
            gap: 25px;
            font-size: 11px;
        }

        .footer-links a:hover {
            color: #fff;
        }

        .socials {
            display: flex;
            align-items: center;
            gap: 9px;
        }

        .socials a {
            width: 31px;
            height: 31px;
            border-radius: 50%;
            border: 1px solid #43506a;
            display: grid;
            place-items: center;
            font-size: 13px;
            transition: .2s;
        }

        .socials a:hover {
            background: var(--primary);
            border-color: var(--primary);
            color: #fff;
        }

        .copyright {
            font-size: 10px;
            padding-left: 20px;
            border-left: 1px solid #43506a;
            white-space: nowrap;
        }

        /* =========================
           RESPONSIVE
        ========================= */
        @media (max-width: 1100px) {
            .nav-links {
                gap: 18px;
            }

            .feature-grid {
                grid-template-columns: repeat(3, 1fr);
            }

            .campus-card {
                right: -120px;
            }
        }

        @media (max-width: 900px) {
            .nav-links,
            .nav-inner > .login-btn {
                display: none;
            }

            .mobile-toggle {
                display: block;
            }

            .hero-inner {
                grid-template-columns: 1fr;
                padding-top: 45px;
            }

            .hero {
                min-height: auto;
            }

            .hero-visual {
                min-height: 350px;
            }

            .campus-card {
                right: 50%;
                transform: translateX(50%);
                width: 620px;
            }

            .why-grid {
                grid-template-columns: 1fr;
                gap: 25px;
            }

            .dashboard-wrap {
                max-width: 650px;
                margin: auto;
            }

            .cta-inner {
                flex-direction: column;
                align-items: flex-start;
            }
        }

        @media (max-width: 650px) {
            .container {
                width: min(100% - 26px, 1180px);
            }

            .brand-text strong {
                font-size: 22px;
            }

            .brand-text small {
                font-size: 9px;
            }

            .brand-logo {
                width: 42px;
                height: 42px;
            }

            .hero h1 {
                font-size: 49px;
            }

            .hero h2 {
                font-size: 19px;
            }

            .hero p {
                font-size: 14px;
            }

            .hero-actions {
                flex-direction: column;
            }

            .hero-actions .btn {
                width: 100%;
            }

            .hero-visual {
                min-height: 280px;
            }

            .campus-card {
                width: 520px;
                height: 280px;
                right: 50%;
                top: 20px;
            }

            .campus-building {
                transform: scale(.78);
                transform-origin: right top;
                right: 5px;
            }

            .students {
                transform: scale(.72);
                transform-origin: left bottom;
            }

            .quote-card {
                right: 5px;
                bottom: 12px;
                width: 190px;
                font-size: 10px;
            }

            .feature-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .stats {
                padding: 15px;
            }

            .stats-grid {
                grid-template-columns: 1fr 1fr;
                gap: 15px 0;
            }

            .stat:nth-child(2) {
                border-right: 0;
            }

            .stat:nth-child(-n+2) {
                padding-bottom: 12px;
                border-bottom: 1px solid #d6dee9;
            }

            .stat:nth-child(3),
            .stat:nth-child(4) {
                padding-top: 12px;
            }

            .check-list {
                grid-template-columns: 1fr;
            }

            .why-content h2 {
                font-size: 25px;
            }

            .laptop-screen {
                grid-template-columns: 75px 1fr;
            }

            .mock-cards {
                grid-template-columns: repeat(2, 1fr);
            }

            .footer-inner {
                flex-direction: column;
                align-items: flex-start;
            }

            .footer-links {
                flex-wrap: wrap;
                gap: 14px;
            }

            .copyright {
                padding-left: 0;
                border-left: 0;
            }
        }
    </style>
</head>

<body>

<!-- =========================
     NAVBAR
========================= -->
<header class="navbar">
    <div class="container nav-inner">

        <a href="#" class="brand" aria-label="SIMSKRIPSI">
            <div class="brand-logo">
                <i class="bi bi-mortarboard-fill"></i>
            </div>

            <div class="brand-text">
                <strong>SIM<span>SKRIPSI</span></strong>
                <small>Universitas Muhammadiyah Bengkulu</small>
            </div>
        </a>

        <nav class="nav-links" aria-label="Navigasi utama">
            <a href="#beranda" class="active">Beranda</a>
            <a href="#tentang">Tentang</a>
            <a href="#alur">Alur</a>
            <a href="#panduan">Panduan</a>
            <a href="#berita">Berita</a>
            <a href="#kontak">Kontak</a>
        </nav>

        <a href="login.php" class="login-btn">
            <i class="bi bi-person"></i>
            Login
        </a>

        <button class="mobile-toggle" type="button" aria-label="Buka menu">
            <i class="bi bi-list"></i>
        </button>

    </div>
</header>


<!-- =========================
     HERO
========================= -->
<section class="hero" id="beranda">
    <div class="container hero-inner">

        <div class="hero-content">
            <span class="eyebrow">SISTEM INFORMASI MANAJEMEN SKRIPSI</span>

            <h1>SIM<span>SKRIPSI</span></h1>

            <h2>Universitas Muhammadiyah Bengkulu</h2>

            <p>
                Platform digital untuk mengelola seluruh proses skripsi mahasiswa
                secara terintegrasi, transparan dan akuntabel.
            </p>

            <div class="hero-actions">
                <a href="login.php?role=mahasiswa" class="btn btn-primary">
                    <i class="bi bi-mortarboard-fill"></i>
                    Login Mahasiswa
                    <i class="bi bi-arrow-right"></i>
                </a>

                <a href="login.php?role=dosen" class="btn btn-outline">
                    <i class="bi bi-people-fill"></i>
                    Login Dosen/Petugas
                    <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>

        <div class="hero-visual">

            <div class="campus-card">
                <div class="campus-building"></div>

                <div class="students">
                    <div class="person one"></div>
                    <div class="person two"></div>
                    <div class="person three"></div>
                </div>
            </div>

            <div class="quote-card">
                <i class="bi bi-mortarboard-fill"></i>
                “Skripsi lebih terarah dengan sistem yang terintegrasi”
            </div>

        </div>

    </div>
</section>


<!-- =========================
     FEATURES
========================= -->
<section class="features" id="alur">
    <div class="container">

        <div class="feature-grid">

            <article class="feature-card">
                <div class="feature-icon">
                    <i class="bi bi-file-earmark-text"></i>
                </div>
                <h3>Pengajuan Judul</h3>
                <p>Ajukan dan kelola judul skripsi secara online.</p>
            </article>

            <article class="feature-card">
                <div class="feature-icon">
                    <i class="bi bi-people"></i>
                </div>
                <h3>Pembimbing</h3>
                <p>Penetapan pembimbing dan monitoring bimbingan.</p>
            </article>

            <article class="feature-card">
                <div class="feature-icon">
                    <i class="bi bi-file-earmark-check"></i>
                </div>
                <h3>Seminar Proposal</h3>
                <p>Jadwal, penilaian dan berita acara seminar.</p>
            </article>

            <article class="feature-card">
                <div class="feature-icon">
                    <i class="bi bi-book"></i>
                </div>
                <h3>Sidang Skripsi</h3>
                <p>Pengelolaan ujian akhir secara sistematis.</p>
            </article>

            <article class="feature-card">
                <div class="feature-icon">
                    <i class="bi bi-bar-chart-line"></i>
                </div>
                <h3>Monitoring Progres</h3>
                <p>Pantau seluruh tahapan skripsi secara real-time.</p>
            </article>

            <article class="feature-card">
                <div class="feature-icon">
                    <i class="bi bi-file-earmark-zip"></i>
                </div>
                <h3>Laporan & Arsip</h3>
                <p>Dokumen tersimpan rapi dan mudah diakses.</p>
            </article>

        </div>


        <!-- STATISTIK -->
        <div class="stats">
            <div class="stats-grid">

                <div class="stat">
                    <i class="bi bi-mortarboard-fill"></i>
                    <div>
                        <strong>1.250+</strong>
                        <span>Mahasiswa Aktif</span>
                    </div>
                </div>

                <div class="stat">
                    <i class="bi bi-people-fill"></i>
                    <div>
                        <strong>150+</strong>
                        <span>Dosen Pembimbing</span>
                    </div>
                </div>

                <div class="stat">
                    <i class="bi bi-file-earmark-text-fill"></i>
                    <div>
                        <strong>980+</strong>
                        <span>Judul Skripsi</span>
                    </div>
                </div>

                <div class="stat">
                    <i class="bi bi-check-circle"></i>
                    <div>
                        <strong>850+</strong>
                        <span>Skripsi Selesai</span>
                    </div>
                </div>

            </div>
        </div>

    </div>
</section>


<!-- =========================
     WHY SIMSKRIPSI
========================= -->
<section class="why" id="tentang">
    <div class="container why-grid">

        <div class="dashboard-wrap">

            <div class="laptop">

                <div class="laptop-screen">

                    <aside class="mock-sidebar">
                        <div class="mock-logo">
                            SIM<span>SKRIPSI</span>
                        </div>

                        <div class="mock-menu active">
                            <i class="bi bi-house-fill"></i>
                            Dashboard
                        </div>

                        <div class="mock-menu">
                            <i class="bi bi-file-earmark"></i>
                            Pengajuan Judul
                        </div>

                        <div class="mock-menu">
                            <i class="bi bi-people"></i>
                            Bimbingan
                        </div>

                        <div class="mock-menu">
                            <i class="bi bi-calendar-event"></i>
                            Seminar Proposal
                        </div>

                        <div class="mock-menu">
                            <i class="bi bi-journal-check"></i>
                            Sidang Skripsi
                        </div>

                        <div class="mock-menu">
                            <i class="bi bi-folder"></i>
                            Dokumen
                        </div>
                    </aside>


                    <main class="mock-main">

                        <div class="mock-title">
                            Dashboard Mahasiswa
                        </div>

                        <div class="welcome">
                            Selamat datang,
                            <strong>Mahasiswa UMB</strong>
                        </div>

                        <div class="mock-cards">

                            <div class="mock-card">
                                <i class="bi bi-file-earmark-check"></i>
                                Pengajuan Judul
                            </div>

                            <div class="mock-card">
                                <i class="bi bi-people"></i>
                                Bimbingan
                            </div>

                            <div class="mock-card">
                                <i class="bi bi-file-earmark"></i>
                                Seminar Proposal
                            </div>

                            <div class="mock-card">
                                <i class="bi bi-journal-check"></i>
                                Sidang Skripsi
                            </div>

                        </div>

                        <div class="progress-box">
                            Progres Skripsi
                            <div class="progress">
                                <span></span>
                            </div>
                            <strong style="float:right;">60%</strong>
                        </div>

                    </main>

                </div>

            </div>

            <div class="laptop-base"></div>

        </div>


        <div class="why-content">

            <span class="eyebrow">MENGAPA SIMSKRIPSI?</span>

            <h2>
                Mendukung Proses Skripsi yang
                Lebih Mudah dan Terstruktur
            </h2>

            <p>
                SIMSKRIPSI membantu mahasiswa, dosen pembimbing, dan pengelola
                program studi dalam mengelola proses skripsi secara efektif,
                mulai dari pengajuan judul hingga arsip dokumen akhir.
            </p>

            <div class="check-list">

                <div class="check">
                    <i class="bi bi-check-circle-fill"></i>
                    <span>Proses online dan transparan</span>
                </div>

                <div class="check">
                    <i class="bi bi-check-circle-fill"></i>
                    <span>Dokumen tersimpan dengan aman</span>
                </div>

                <div class="check">
                    <i class="bi bi-check-circle-fill"></i>
                    <span>Notifikasi setiap tahapan</span>
                </div>

                <div class="check">
                    <i class="bi bi-check-circle-fill"></i>
                    <span>Mendukung akreditasi program studi</span>
                </div>

                <div class="check">
                    <i class="bi bi-check-circle-fill"></i>
                    <span>Integrasi dengan data mahasiswa</span>
                </div>

                <div class="check">
                    <i class="bi bi-check-circle-fill"></i>
                    <span>Mudah digunakan di berbagai perangkat</span>
                </div>

            </div>

        </div>

    </div>
</section>


<!-- =========================
     CTA
========================= -->
<section class="cta" id="panduan">
    <div class="container cta-inner">

        <div>
            <h2>Siap Mengelola Skripsi Lebih Terstruktur?</h2>
            <p>Gunakan SIMSKRIPSI untuk mengikuti setiap tahapan skripsi secara digital.</p>
        </div>

        <a href="login.php" class="btn">
            <i class="bi bi-box-arrow-in-right"></i>
            Masuk ke SIMSKRIPSI
        </a>

    </div>
</section>


<!-- =========================
     FOOTER
========================= -->
<footer id="kontak">
    <div class="container footer-inner">

        <div class="footer-brand">

            <div class="footer-logo">
                <i class="bi bi-mortarboard-fill"></i>
            </div>

            <div>
                <strong>SIM<span>SKRIPSI</span></strong>
                <small>Universitas Muhammadiyah Bengkulu</small>
            </div>

        </div>

        <nav class="footer-links">
            <a href="#beranda">Beranda</a>
            <a href="#tentang">Tentang</a>
            <a href="#panduan">Panduan</a>
            <a href="#kontak">Kontak</a>
        </nav>

        <div class="socials">
            <a href="#" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
            <a href="#" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
            <a href="#" aria-label="YouTube"><i class="bi bi-youtube"></i></a>
        </div>

        <div class="copyright">
            &copy; <?php echo date('Y'); ?> SIMSKRIPSI UMB. All rights reserved.
        </div>

    </div>
</footer>


<script>
    /*
     * Menu mobile sederhana.
     * Pada tahap berikutnya dapat dikembangkan menjadi navbar
     * yang mengambil menu dari konfigurasi/database.
     */
    const toggle = document.querySelector('.mobile-toggle');
    const nav = document.querySelector('.nav-links');

    if (toggle) {
        toggle.addEventListener('click', function () {
            const isOpen = nav.style.display === 'flex';

            if (isOpen) {
                nav.style.display = '';
            } else {
                nav.style.display = 'flex';
                nav.style.position = 'absolute';
                nav.style.left = '13px';
                nav.style.right = '13px';
                nav.style.top = '72px';
                nav.style.padding = '12px 18px';
                nav.style.background = '#fff';
                nav.style.borderRadius = '0 0 14px 14px';
                nav.style.boxShadow = '0 12px 30px rgba(20,45,85,.12)';
                nav.style.flexDirection = 'column';
                nav.style.alignItems = 'flex-start';
                nav.style.gap = '0';
            }
        });
    }

    /*
     * Tutup menu mobile setelah link diklik.
     */
    document.querySelectorAll('.nav-links a').forEach(function (link) {
        link.addEventListener('click', function () {
            if (window.innerWidth <= 900) {
                nav.style.display = '';
            }
        });
    });
</script>

</body>
</html>
