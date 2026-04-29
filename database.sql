-- Database Sistem Kasir Minimarket
-- Dibuat untuk XAMPP/MySQL

CREATE DATABASE IF NOT EXISTS kasir_minimarket;
USE kasir_minimarket;

-- Tabel User (Admin & Kasir)
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    role ENUM('admin', 'kasir') DEFAULT 'kasir',
    status ENUM('aktif', 'nonaktif') DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Produk
CREATE TABLE produk (
    id INT PRIMARY KEY AUTO_INCREMENT,
    barcode VARCHAR(50) UNIQUE NOT NULL,
    nama_produk VARCHAR(200) NOT NULL,
    kategori VARCHAR(50),
    harga_beli DECIMAL(10,2) NOT NULL,
    harga_jual DECIMAL(10,2) NOT NULL,
    stok INT DEFAULT 0,
    satuan VARCHAR(20) DEFAULT 'pcs',
    status ENUM('aktif', 'nonaktif') DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabel Diskon Berdasarkan Minimal Belanja
CREATE TABLE diskon_belanja (
    id INT PRIMARY KEY AUTO_INCREMENT,
    minimal_belanja DECIMAL(10,2) NOT NULL,
    persentase_diskon DECIMAL(5,2) NOT NULL,
    keterangan VARCHAR(200),
    status ENUM('aktif', 'nonaktif') DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Transaksi
CREATE TABLE transaksi (
    id INT PRIMARY KEY AUTO_INCREMENT,
    kode_transaksi VARCHAR(50) UNIQUE NOT NULL,
    tanggal DATETIME DEFAULT CURRENT_TIMESTAMP,
    user_id INT,
    subtotal DECIMAL(12,2) NOT NULL,
    diskon_id INT NULL,
    diskon_persen DECIMAL(5,2) DEFAULT 0,
    diskon_nominal DECIMAL(10,2) DEFAULT 0,
    total_bayar DECIMAL(12,2) NOT NULL,
    metode_pembayaran ENUM('cash', 'qris', 'transfer') NOT NULL,
    uang_dibayar DECIMAL(12,2),
    uang_kembalian DECIMAL(12,2),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (diskon_id) REFERENCES diskon_belanja(id)
);

-- Tabel Detail Transaksi
CREATE TABLE detail_transaksi (
    id INT PRIMARY KEY AUTO_INCREMENT,
    transaksi_id INT NOT NULL,
    produk_id INT NOT NULL,
    nama_produk VARCHAR(200) NOT NULL,
    harga_satuan DECIMAL(10,2) NOT NULL,
    qty INT NOT NULL,
    subtotal DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (transaksi_id) REFERENCES transaksi(id) ON DELETE CASCADE,
    FOREIGN KEY (produk_id) REFERENCES produk(id)
);

-- Tabel Log Stok
CREATE TABLE log_stok (
    id INT PRIMARY KEY AUTO_INCREMENT,
    produk_id INT NOT NULL,
    jenis ENUM('masuk', 'keluar') NOT NULL,
    qty INT NOT NULL,
    keterangan VARCHAR(255),
    user_id INT,
    transaksi_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (produk_id) REFERENCES produk(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (transaksi_id) REFERENCES transaksi(id)
);

-- Tabel Tutup Kasir
CREATE TABLE tutup_kasir (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tanggal DATE NOT NULL,
    user_id INT NOT NULL,
    total_transaksi INT DEFAULT 0,
    total_penjualan_cash DECIMAL(12,2) DEFAULT 0,
    total_penjualan_qris DECIMAL(12,2) DEFAULT 0,
    total_penjualan_transfer DECIMAL(12,2) DEFAULT 0,
    total_penjualan_semua DECIMAL(12,2) DEFAULT 0,
    uang_fisik DECIMAL(12,2) NOT NULL,
    selisih DECIMAL(12,2) NOT NULL,
    keterangan TEXT,
    waktu_tutup TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Insert Data Default
-- Password: admin123 dan kasir123 (sudah di-hash dengan md5 untuk contoh, gunakan password_hash() untuk produksi)
INSERT INTO users (username, password, nama_lengkap, role) VALUES
('admin', MD5('admin123'), 'Administrator', 'admin'),
('kasir1', MD5('kasir123'), 'Kasir 1', 'kasir');

-- Insert Data Produk Contoh
INSERT INTO produk (barcode, nama_produk, kategori, harga_beli, harga_jual, stok, satuan) VALUES
('8992761154028', 'Indomie Goreng', 'Makanan', 2500, 3000, 100, 'pcs'),
('8993175580489', 'Teh Botol Sosro 450ml', 'Minuman', 3500, 5000, 50, 'pcs'),
('8992696010201', 'Beng Beng', 'Makanan', 1500, 2000, 80, 'pcs'),
('8888888888888', 'Air Mineral 600ml', 'Minuman', 2000, 3000, 100, 'pcs'),
('7777777777777', 'Mie Sedaap Goreng', 'Makanan', 2500, 3000, 75, 'pcs'),
('6666666666666', 'Kopi ABC Susu Kaleng', 'Minuman', 4500, 6500, 40, 'pcs'),
('5555555555555', 'Chitato Rasa Sapi Panggang', 'Snack', 8000, 10000, 30, 'pcs'),
('4444444444444', 'Silverqueen Coklat', 'Snack', 6000, 8000, 25, 'pcs'),
('3333333333333', 'Ultra Milk Coklat 250ml', 'Minuman', 4000, 5500, 60, 'pcs'),
('2222222222222', 'Roma Kelapa', 'Snack', 2000, 3000, 90, 'pcs');

-- Insert Data Diskon Berdasarkan Minimal Belanja
INSERT INTO diskon_belanja (minimal_belanja, persentase_diskon, keterangan, status) VALUES
(50000, 5, 'Diskon 5% untuk belanja min Rp50.000', 'aktif'),
(100000, 10, 'Diskon 10% untuk belanja min Rp100.000', 'aktif'),
(200000, 15, 'Diskon 15% untuk belanja min Rp200.000', 'aktif');

-- Tabel Pegawai (Extended dari users untuk data kepegawaian)
CREATE TABLE pegawai (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    nip VARCHAR(50) UNIQUE,
    rfid_uid VARCHAR(50) UNIQUE,
    fingerprint_id INT UNIQUE,
    jabatan VARCHAR(100),
    gaji_pokok DECIMAL(12,2) DEFAULT 0,
    tanggal_bergabung DATE,
    status_kerja ENUM('aktif', 'nonaktif', 'resign') DEFAULT 'aktif',
    foto_profile VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabel Absensi
CREATE TABLE absensi (
    id INT PRIMARY KEY AUTO_INCREMENT,
    pegawai_id INT NOT NULL,
    tanggal DATE NOT NULL,
    jam_masuk TIME,
    jam_pulang TIME,
    status ENUM('HADIR', 'TERLAMBAT', 'IZIN', 'SAKIT', 'ALFA') DEFAULT 'HADIR',
    keterangan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (pegawai_id) REFERENCES pegawai(id) ON DELETE CASCADE,
    UNIQUE KEY unique_pegawai_tanggal (pegawai_id, tanggal)
);

-- Tabel Setting Potongan Gaji
CREATE TABLE setting_potongan (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nama_potongan VARCHAR(100) NOT NULL,
    jenis_ketidakhadiran ENUM('ALFA', 'IZIN', 'SAKIT'),
    nominal_potongan DECIMAL(10,2) NOT NULL,
    keterangan TEXT,
    status ENUM('aktif', 'nonaktif') DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Setting Bonus Penjualan
CREATE TABLE setting_bonus (
    id INT PRIMARY KEY AUTO_INCREMENT,
    minimal_penjualan DECIMAL(12,2) NOT NULL,
    nominal_bonus DECIMAL(10,2) NOT NULL,
    persentase_bonus DECIMAL(5,2) DEFAULT 0,
    keterangan TEXT,
    status ENUM('aktif', 'nonaktif') DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Penggajian
CREATE TABLE penggajian (
    id INT PRIMARY KEY AUTO_INCREMENT,
    pegawai_id INT NOT NULL,
    periode_bulan INT NOT NULL,
    periode_tahun INT NOT NULL,
    gaji_pokok DECIMAL(12,2) NOT NULL,
    total_hadir INT DEFAULT 0,
    total_terlambat INT DEFAULT 0,
    total_izin INT DEFAULT 0,
    total_sakit INT DEFAULT 0,
    total_alfa INT DEFAULT 0,
    total_potongan DECIMAL(12,2) DEFAULT 0,
    total_penjualan DECIMAL(12,2) DEFAULT 0,
    bonus_penjualan DECIMAL(12,2) DEFAULT 0,
    gaji_bersih DECIMAL(12,2) NOT NULL,
    status_pembayaran ENUM('pending', 'dibayar') DEFAULT 'pending',
    tanggal_dibayar DATE,
    keterangan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pegawai_id) REFERENCES pegawai(id) ON DELETE CASCADE,
    UNIQUE KEY unique_pegawai_periode (pegawai_id, periode_bulan, periode_tahun)
);

-- Tabel Log Absensi (untuk tracking device IoT)
CREATE TABLE log_absensi_iot (
    id INT PRIMARY KEY AUTO_INCREMENT,
    rfid_uid VARCHAR(50),
    fingerprint_id INT,
    device_id VARCHAR(50),
    device_ip VARCHAR(50),
    action ENUM('masuk', 'pulang'),
    status ENUM('success', 'failed', 'unknown'),
    message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert Data Pegawai untuk User yang Sudah Ada
INSERT INTO pegawai (user_id, nip, jabatan, gaji_pokok, tanggal_bergabung) VALUES
(1, 'NIP001', 'Manager', 5000000, '2024-01-01'),
(2, 'NIP002', 'Kasir', 3000000, '2024-01-15');

-- Insert Setting Potongan Default
INSERT INTO setting_potongan (nama_potongan, jenis_ketidakhadiran, nominal_potongan, keterangan) VALUES
('Potongan Alfa', 'ALFA', 100000, 'Potongan per hari alfa tanpa keterangan'),
('Potongan Izin', 'IZIN', 50000, 'Potongan per hari izin'),
('Potongan Sakit', 'SAKIT', 0, 'Tidak ada potongan untuk sakit dengan surat');

-- Insert Setting Bonus Default
INSERT INTO setting_bonus (minimal_penjualan, nominal_bonus, persentase_bonus, keterangan) VALUES
(5000000, 200000, 0, 'Bonus untuk penjualan minimal 5 juta'),
(10000000, 500000, 0, 'Bonus untuk penjualan minimal 10 juta'),
(20000000, 1000000, 0, 'Bonus untuk penjualan minimal 20 juta');

-- Index untuk performa
CREATE INDEX idx_barcode ON produk(barcode);
CREATE INDEX idx_transaksi_tanggal ON transaksi(tanggal);
CREATE INDEX idx_tutup_kasir_tanggal ON tutup_kasir(tanggal);
CREATE INDEX idx_absensi_tanggal ON absensi(tanggal);
CREATE INDEX idx_absensi_pegawai ON absensi(pegawai_id);
CREATE INDEX idx_penggajian_periode ON penggajian(periode_bulan, periode_tahun);
