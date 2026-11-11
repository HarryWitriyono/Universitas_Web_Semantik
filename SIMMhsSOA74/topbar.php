<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once __DIR__ . '/csrf.php';

$namaPengguna = $_SESSION['nama_lengkap'] ?? 'Pengguna';
$namaRole = $_SESSION['nama_role'] ?? 'Pengguna';
?>
<header class="topbar">
    <div class="topbar-left">
        <button
            type="button"
            class="sidebar-toggle"
            onclick="toggleSidebar()"
            aria-label="Buka atau tutup menu navigasi"
            title="Menu navigasi"
        >☰</button>

        <div>
            <strong>SIM Mahasiswa</strong>
            <span>Universitas Muhammadiyah Bengkulu</span>
        </div>
    </div>

    <div class="topbar-right">
        <div class="user-menu">
            <div class="avatar">
                <?= htmlspecialchars(strtoupper(substr($namaPengguna, 0, 1)), ENT_QUOTES, 'UTF-8') ?>
            </div>

            <div class="user-info">
                <strong><?= htmlspecialchars($namaPengguna, ENT_QUOTES, 'UTF-8') ?></strong>
                <small><?= htmlspecialchars($namaRole, ENT_QUOTES, 'UTF-8') ?></small>
            </div>
        </div>

        <form
            action="logout.php"
            method="post"
            class="logout-form"
            onsubmit="return confirm('Apakah Anda yakin ingin keluar?');"
        >
            <?= csrf_field() ?>
            <button type="submit" class="logout-link logout-button">
                Keluar
            </button>
        </form>
    </div>
</header>

<script>
(function () {
    function isMobileSidebarMode() {
        return window.innerWidth <= 800;
    }

    window.toggleSidebar = function () {
        const sidebar = document.querySelector('.sidebar');

        if (!sidebar) {
            return;
        }

        if (isMobileSidebarMode()) {
            // Tablet/mobile: buka-tutup sidebar overlay.
            sidebar.classList.toggle('show');
            return;
        }

        // Desktop: collapse/expand sidebar tanpa overlay.
        const collapsed = document.body.classList.toggle('sidebar-collapsed');
        sidebar.setAttribute('data-collapsed', collapsed ? '1' : '0');
    };

    function closeMobileSidebar() {
        const sidebar = document.querySelector('.sidebar');

        if (sidebar && isMobileSidebarMode()) {
            sidebar.classList.remove('show');
        }
    }

    // Di desktop, klik menu TIDAK menutup sidebar.
    // Di tablet/mobile, setelah memilih menu, sidebar ditutup.
    document.addEventListener('click', function (event) {
        const link = event.target.closest('.sidebar-link');

        if (link && isMobileSidebarMode()) {
            closeMobileSidebar();
        }
    });

    // Ketika berpindah dari mobile ke desktop, bersihkan state mobile.
    // State desktop dikembalikan ke terbuka agar halaman selalu konsisten.
    window.addEventListener('resize', function () {
        const sidebar = document.querySelector('.sidebar');

        if (!sidebar) {
            return;
        }

        if (window.innerWidth > 800) {
            sidebar.classList.remove('show');
        } else {
            document.body.classList.remove('sidebar-collapsed');
        }
    });
})();
</script>
