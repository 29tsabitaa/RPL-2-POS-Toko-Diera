TOKO DIERA - Sistem Informasi POS (PHP Native + MySQL)

CARA INSTAL (XAMPP / Laragon):
1. Salin folder ini ke htdocs (mis. C:\xampp\htdocs\toko_diera).
2. Jalankan Apache & MySQL, buka phpMyAdmin > Import > pilih database.sql.
3. Sesuaikan DB_USER/DB_PASS di config.php bila perlu.
4. Buka http://localhost/toko_diera

AKUN AWAL:  admin/admin123  |  kasir/kasir123  |  pemilik/pemilik123
Pengguna baru lewat halaman Daftar berstatus "pending" dan baru bisa login setelah divalidasi admin (menu Data Pengguna).

HAK AKSES:
- Admin   : kelola barang, pengguna (validasi akun), penjualan, retur (setujui/tolak)
- Kasir   : lihat barang, transaksi + cetak struk, pengajuan retur
- Pemilik : laporan penjualan, stok, retur
Logout memakai dialog konfirmasi. Butuh PHP 7.4+ (disarankan 8+).
