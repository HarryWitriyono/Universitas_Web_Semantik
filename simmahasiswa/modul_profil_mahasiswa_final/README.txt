MODUL PROFIL MAHASISWA - SIM MAHASISWA RBAC

File:
1. mahasiswa_profil.php
   - Hanya untuk role MAHASISWA.
   - Menampilkan data akademik, pribadi, kontak, dan akun.
   - Data diambil berdasarkan $_SESSION['id_pengguna'].
   - Tidak menggunakan id mahasiswa dari URL sehingga mahasiswa tidak dapat memilih profil mahasiswa lain.

2. mahasiswa_profil_edit.php
   - Hanya untuk role MAHASISWA.
   - Mahasiswa dapat mengubah:
     * Nama mahasiswa
     * Jenis kelamin
     * Tempat lahir
     * Tanggal lahir
     * Alamat
     * Email
     * No. HP
   - Mahasiswa TIDAK dapat mengubah:
     * NPM
     * Program Studi
     * Fakultas
     * Tanggal Masuk
     * Status Mahasiswa
   - Update tabel mahasiswa dan pengguna menggunakan transaksi database.
   - CSRF menggunakan csrf.php dan csrf_verify().
   - Prepared statement digunakan untuk query database.

INSTALASI:
- Salin kedua file ke folder utama aplikasi SIM Mahasiswa, sejajar dengan auth_check.php, koneksi.php, csrf.php, topbar.php, sideleftbar.php, dan style.css.
- Tambahkan link menu Profil Saya di sideleftbar.php jika belum ada:
  <a href="mahasiswa_profil.php" class="sidebar-link">Profil Saya</a>

CATATAN:
- Modul ini mengikuti pola modul CRUD Mahasiswa yang sudah bekerja.
- Tidak membutuhkan footer.php.
- Password tidak diubah di modul ini. Modul Ganti Password dapat dibuat terpisah agar lebih aman dan mudah dikelola.
