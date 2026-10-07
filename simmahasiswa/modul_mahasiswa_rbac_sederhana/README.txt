MODUL CRUD MAHASISWA - SIM MAHASISWA UMB
========================================

Modul ini dibuat ulang mengikuti pola CRUD Fakultas yang sudah berjalan pada aplikasi.
Tujuannya menghindari helper lama yang memanggil auth.php secara langsung.
Semua halaman yang membutuhkan login menggunakan auth_check.php.

FILE:
- mahasiswa.php          : daftar mahasiswa + pencarian/filter
- mahasiswa_tambah.php  : tambah mahasiswa
- mahasiswa_edit.php    : edit/detail mahasiswa
- mahasiswa_hapus.php   : hapus mahasiswa

RELASI DATABASE:
- mahasiswa.id_program_studi -> program_studi.id_program_studi
- mahasiswa.id_pengguna -> pengguna.id_pengguna

HAK AKSES:
- ADMIN          : lihat, tambah, edit, hapus seluruh mahasiswa
- OPERATOR_PRODI : lihat, tambah, edit mahasiswa pada Prodi sendiri; tidak dapat hapus
- DEKANAT        : lihat/detail mahasiswa pada Fakultas sendiri
- REKTORAT       : lihat/detail mahasiswa pada Universitas sendiri

CATATAN:
- NPM tidak dapat diubah pada halaman edit.
- Status mahasiswa menggunakan ENUM sesuai database: Aktif, Cuti, Lulus,
  Mengundurkan Diri, Drop Out, Tidak Aktif.
- Akun pengguna dengan role MAHASISWA dapat dihubungkan ke data mahasiswa.
- Tidak ada require auth.php pada modul CRUD; autentikasi halaman menggunakan auth_check.php.

INSTALASI:
1. Backup file mahasiswa.php/mahasiswa_tambah.php/mahasiswa_edit.php/mahasiswa_hapus.php lama.
2. Salin 4 file PHP modul ini ke folder aplikasi /simmamahasiswa/.
3. Pastikan auth_check.php, koneksi.php, csrf.php, topbar.php, sideleftbar.php,
   dan style.css berada di folder yang sama.
4. Login kembali sebagai Admin lalu buka Data Mahasiswa.
