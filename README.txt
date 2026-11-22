TOKO DIERA - Sistem Informasi POS
================================

TEKNOLOGI
- PHP Native
- MySQL
- HTML, CSS, dan JavaScript

MENJALANKAN APLIKASI
1. Salin folder proyek ke folder htdocs XAMPP, misalnya:
   C:\xampp\htdocs\toko_diera
2. Jalankan Apache dan MySQL dari XAMPP.
3. Buka phpMyAdmin, lalu impor file database.sql.
   Perhatian: file tersebut menghapus dan membuat ulang tabel aplikasi.
4. Sesuaikan DB_USER dan DB_PASS di config.php jika pengaturan MySQL berbeda.
5. Buka http://localhost/toko_diera di browser.

AKUN CONTOH
- Admin:   admin / admin123
- Kasir:   kasir / kasir123
- Pemilik: pemilik / pemilik123

Pengguna baru yang mendaftar menunggu validasi admin sebelum dapat login.

HAK AKSES
- Admin: mengelola barang, pengguna, penjualan, dan retur.
- Kasir: melihat barang, membuat transaksi, mencetak struk, dan mengajukan retur.
- Pemilik: melihat laporan penjualan, stok, dan retur.

Baca README.md untuk panduan struktur file dan cara menjelaskan alur aplikasi.
