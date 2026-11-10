-- Database Toko Diera POS
CREATE DATABASE IF NOT EXISTS toko_diera CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE toko_diera;

DROP TABLE IF EXISTS retur_detail, retur, penjualan_detail, penjualan, barang, users;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(100) NOT NULL,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin','kasir','pemilik') NOT NULL DEFAULT 'kasir',
  status ENUM('pending','aktif','ditolak') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE barang (
  id INT AUTO_INCREMENT PRIMARY KEY,
  kode VARCHAR(20) NOT NULL UNIQUE,
  nama VARCHAR(100) NOT NULL,
  kategori VARCHAR(50) NOT NULL,
  harga INT NOT NULL DEFAULT 0,
  stok INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE penjualan (
  id INT AUTO_INCREMENT PRIMARY KEY,
  no_transaksi VARCHAR(30) NOT NULL UNIQUE,
  tanggal DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  user_id INT NOT NULL,
  total_item INT NOT NULL,
  subtotal INT NOT NULL,
  diskon INT NOT NULL DEFAULT 0,
  total INT NOT NULL,
  bayar INT NOT NULL,
  kembali INT NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE penjualan_detail (
  id INT AUTO_INCREMENT PRIMARY KEY,
  penjualan_id INT NOT NULL,
  barang_id INT NOT NULL,
  nama_barang VARCHAR(100) NOT NULL,
  harga INT NOT NULL,
  qty INT NOT NULL,
  subtotal INT NOT NULL,
  FOREIGN KEY (penjualan_id) REFERENCES penjualan(id) ON DELETE CASCADE,
  FOREIGN KEY (barang_id) REFERENCES barang(id)
) ENGINE=InnoDB;

CREATE TABLE retur (
  id INT AUTO_INCREMENT PRIMARY KEY,
  no_retur VARCHAR(30) NOT NULL UNIQUE,
  penjualan_id INT NOT NULL,
  tanggal DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  user_id INT NOT NULL,
  total_item INT NOT NULL,
  diskon INT NOT NULL DEFAULT 0,
  total INT NOT NULL,
  alasan VARCHAR(100) NOT NULL,
  keterangan TEXT,
  status ENUM('diajukan','disetujui','ditolak') NOT NULL DEFAULT 'diajukan',
  FOREIGN KEY (penjualan_id) REFERENCES penjualan(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE retur_detail (
  id INT AUTO_INCREMENT PRIMARY KEY,
  retur_id INT NOT NULL,
  barang_id INT NOT NULL,
  nama_barang VARCHAR(100) NOT NULL,
  harga INT NOT NULL,
  qty INT NOT NULL,
  subtotal INT NOT NULL,
  FOREIGN KEY (retur_id) REFERENCES retur(id) ON DELETE CASCADE,
  FOREIGN KEY (barang_id) REFERENCES barang(id)
) ENGINE=InnoDB;

-- Data awal (password: admin123 / kasir123 / pemilik123)
INSERT INTO users (nama,username,password,role,status) VALUES
('Administrator','admin','$2y$12$AQyyDbGEvnLI3oOwXUppYecxDF92ytmgb9LsnmVP/ZahtgtqGGlmK','admin','aktif'),
('Kasir Toko','kasir','$2y$12$4hLhpdHL3PrvA7jyORVTmeJrTtU0imhrLAndMPfx6efa4vPb0CxjG','kasir','aktif'),
('Pemilik Toko','pemilik','$2y$12$IvJan46D9dpzQr8xcp/2cuLQEfmP0bM0SjzgTiIhxFNZXJzxGeA6W','pemilik','aktif');

INSERT INTO barang (kode,nama,kategori,harga,stok) VALUES
('BRG-0001','Pensil 2B','Alat Tulis',2000,100),
('BRG-0002','Buku Tulis','Buku',5000,80),
('BRG-0003','Penghapus','Alat Tulis',1500,120),
('BRG-0004','Pulpen Hitam','Alat Tulis',3000,150),
('BRG-0005','Penggaris 30cm','Aksesoris',4000,60);
