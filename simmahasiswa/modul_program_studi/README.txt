MODUL PROGRAM STUDI - SIM MAHASISWA UMB

File:
- program_studi.php          : daftar data Program Studi
- program_studi_tambah.php   : tambah data
- program_studi_edit.php     : edit data
- program_studi_hapus.php    : hapus data

Dependensi file aplikasi utama:
- auth_check.php
- koneksi.php
- csrf.php
- topbar.php
- sideleftbar.php
- footer.php

Hak akses:
- ADMIN

Catatan:
1. auth.php digunakan sebagai proses login, bukan sebagai proteksi halaman.
2. Halaman CRUD menggunakan auth_check.php.
3. koneksi.php dipanggil langsung oleh setiap file yang membutuhkan $koneksi.
4. Semua operasi POST menggunakan CSRF token.
5. Kode Program Studi dicek agar tidak duplikat pada fakultas yang sama.
6. Penghapusan akan ditolak database jika data masih direferensikan oleh tabel lain.
