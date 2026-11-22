# Toko Diera POS

Aplikasi kasir berbasis PHP Native dan MySQL untuk mengelola barang, transaksi
penjualan, retur, akun pengguna, dan laporan toko.

## Menjalankan aplikasi

1. Salin proyek ke folder web server, misalnya `C:\xampp\htdocs\toko_diera`.
2. Jalankan Apache dan MySQL dari XAMPP.
3. Impor `database.sql` melalui phpMyAdmin.
   **Perhatian:** skrip ini menghapus lalu membuat ulang tabel aplikasi.
4. Sesuaikan `DB_USER` dan `DB_PASS` di `config.php` jika perlu.
5. Buka `http://localhost/toko_diera` di browser.

Aplikasi membutuhkan PHP 8 atau lebih baru dan MySQL.

## Akun contoh

| Peran | Username | Password |
| --- | --- | --- |
| Admin | `admin` | `admin123` |
| Kasir | `kasir` | `kasir123` |
| Pemilik | `pemilik` | `pemilik123` |

Akun yang dibuat melalui halaman daftar berstatus `pending`. Admin perlu
memvalidasi akun sebelum pengguna tersebut dapat login.

## Peran pengguna

- **Admin:** mengelola barang dan pengguna, memvalidasi akun, melihat penjualan,
  serta menyetujui atau menolak retur.
- **Kasir:** melihat barang, membuat transaksi, mencetak struk, dan mengajukan
  retur.
- **Pemilik:** melihat laporan penjualan, stok, dan retur.

## Peta file

### Halaman

- `index.php` memilih halaman awal berdasarkan status login.
- `katalog.php` menampilkan katalog barang untuk pengunjung.
- `login.php` dan `register.php` menangani masuk serta pendaftaran akun.
- `dashboard.php` menampilkan ringkasan yang disesuaikan dengan peran.
- `barang.php` mengelola dan mencari data barang.
- `pengguna.php` mengelola akun dan validasi oleh admin.
- `transaksi.php` menangani transaksi kasir dan penyimpanan penjualan.
- `struk.php` menampilkan serta mencetak bukti transaksi.
- `penjualan.php` menampilkan riwayat penjualan untuk admin.
- `retur.php` menangani pengajuan dan keputusan retur.
- `laporan.php` menampilkan laporan penjualan, stok, atau retur.
- `logout.php` mengakhiri sesi pengguna.

### Pendukung

- `config.php` membuka koneksi database, menyediakan helper, memeriksa akses,
  dan menggambar kerangka halaman internal.
- `database.sql` membuat tabel dan data awal.
- `assets/style.css` mengatur tampilan dan tata letak halaman.
- `assets/app.js` menangani dialog logout dan konfirmasi form.
- `assets/logo-toko-diera.png` dan `assets/banner-*.svg` adalah aset gambar.

## Alur aplikasi untuk dijelaskan

1. Pengunjung membuka katalog atau masuk melalui halaman login.
2. Fungsi `auth()` di `config.php` memeriksa sesi, status akun, dan peran.
3. Kasir memilih barang; `transaksi.php` memeriksa stok dan menyimpan penjualan
   beserta rincian barang dalam satu transaksi database.
4. Stok barang berkurang setelah penjualan berhasil disimpan.
5. Kasir dapat mengajukan retur berdasarkan barang yang dibeli.
6. Admin memeriksa pengajuan. Jika disetujui, stok barang dikembalikan.
7. Pemilik dapat membaca ringkasan penjualan, stok, dan retur dari laporan.

## Contoh penjelasan singkat

> Toko Diera POS adalah aplikasi kasir berbasis PHP dan MySQL. Admin mengatur
> barang serta akun, kasir mencatat penjualan dan retur, sedangkan pemilik
> melihat laporan. Sistem menyimpan data transaksi di MySQL dan memperbarui stok
> saat penjualan atau retur disetujui.
