# SISTEM KASIR MINIMARKET
## Aplikasi Kasir Berbasis Web dengan PHP & MySQL

---

## 📋 DESKRIPSI

Sistem Kasir Minimarket adalah aplikasi Point of Sale (POS) berbasis web yang dirancang untuk membantu pengelolaan transaksi penjualan di toko/minimarket. Aplikasi ini dilengkapi dengan fitur-fitur lengkap seperti:

✅ Transaksi kasir dengan scan barcode
✅ Manajemen produk & stok
✅ Diskon otomatis berdasarkan minimal belanja
✅ Metode pembayaran: Cash, QRIS, Transfer
✅ Cetak struk belanja
✅ Tutup kasir & rekonsiliasi uang
✅ Laporan transaksi lengkap

---

## 🛠️ TEKNOLOGI

- **Frontend**: HTML, CSS, JavaScript
- **Backend**: PHP (Native)
- **Database**: MySQL
- **Server**: XAMPP
- **Barcode Scanner**: USB Barcode Scanner (input keyboard)
- **Printer**: Thermal Printer / Browser Print

---

## 📦 INSTALASI

### 1. Persiapan

Pastikan Anda sudah menginstal:
- XAMPP (Apache & MySQL)
- Browser (Chrome/Firefox/Edge)
- Text Editor (VS Code/Sublime/Notepad++)

### 2. Ekstrak File

Ekstrak folder `sistem_kasir` ke dalam:
```
C:\xampp\htdocs\sistem_kasir
```

### 3. Import Database

1. Buka browser dan akses: `http://localhost/phpmyadmin`
2. Buat database baru dengan nama: `kasir_minimarket`
3. Klik database yang baru dibuat
4. Pilih menu "Import"
5. Pilih file `database.sql` dari folder sistem_kasir
6. Klik "Go" untuk import

### 4. Konfigurasi Database (Opsional)

Jika menggunakan username/password MySQL yang berbeda, edit file `config.php`:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');          // Sesuaikan
define('DB_PASS', '');              // Sesuaikan
define('DB_NAME', 'kasir_minimarket');
```

### 5. Jalankan Aplikasi

1. Start XAMPP (Apache & MySQL)
2. Buka browser
3. Akses: `http://localhost/sistem_kasir`
4. Login menggunakan kredensial default

---

## 🔐 LOGIN DEFAULT

### Admin
- Username: `admin`
- Password: `admin123`

### Kasir
- Username: `kasir1`
- Password: `kasir123`

---

## 📖 PANDUAN PENGGUNAAN

### A. TRANSAKSI KASIR

1. **Login** sebagai kasir atau admin
2. Klik menu **"Kasir"**
3. **Scan barcode** produk atau ketik barcode manual
4. Produk akan otomatis masuk ke keranjang
5. **Tambah/kurangi qty** dengan tombol +/-
6. Sistem akan otomatis menghitung **diskon** jika memenuhi syarat
7. Pilih **metode pembayaran**:
   - **Cash**: Input jumlah uang yang dibayar
   - **QRIS/Transfer**: Langsung proses
8. Klik **"PROSES PEMBAYARAN"**
9. **Struk otomatis** muncul dan bisa dicetak

### B. MANAJEMEN PRODUK

1. Klik menu **"Produk"**
2. **Tambah Produk Baru**:
   - Isi form: Barcode, Nama, Kategori, Harga
   - Klik "Tambah Produk"
3. **Edit Produk**:
   - Klik tombol "Edit" pada produk
   - Ubah data, klik "Simpan"
4. **Tambah Stok**:
   - Klik tombol "Stok" pada produk
   - Input jumlah stok yang ditambah
   - Isi keterangan (misal: "Barang datang dari supplier")
5. **Hapus Produk**:
   - Klik tombol "Hapus" (produk menjadi nonaktif)

### C. MANAJEMEN DISKON

1. Klik menu **"Diskon"**
2. **Tambah Diskon**:
   - Minimal Belanja: Contoh `50000` (Rp 50.000)
   - Persentase: Contoh `5` (5%)
   - Keterangan: Contoh "Diskon 5% untuk belanja min 50rb"
3. **Aktifkan/Nonaktifkan**: Klik tombol toggle status
4. **Simulasi**: Gunakan form simulasi untuk test diskon

**Cara Kerja Diskon:**
- Sistem otomatis memilih diskon **terbesar** yang memenuhi syarat
- Contoh: 
  - Belanja Rp 60.000 → Dapat diskon 5% (min 50rb)
  - Belanja Rp 120.000 → Dapat diskon 10% (min 100rb)
  - Belanja Rp 250.000 → Dapat diskon 15% (min 200rb)

### D. TUTUP KASIR

1. Di akhir hari/shift, klik menu **"Tutup Kasir"**
2. Sistem menampilkan rekap penjualan:
   - Total transaksi
   - Penjualan Cash, QRIS, Transfer
3. **Hitung uang fisik** di laci kasir
4. Input jumlah uang fisik di form
5. Sistem otomatis menghitung **selisih**:
   - **Plus**: Uang lebih (Rp XXX lebih)
   - **Minus**: Uang kurang (Rp XXX kurang)
   - **Pas**: Sesuai sistem
6. Isi keterangan jika ada selisih
7. Klik **"TUTUP KASIR"**

**Catatan:**
- Tutup kasir hanya bisa dilakukan **1x per hari** per kasir
- Setelah tutup kasir, data akan tersimpan di riwayat

### E. RIWAYAT TRANSAKSI

1. Klik menu **"Transaksi"**
2. **Filter** berdasarkan:
   - Tanggal (dari - sampai)
   - Metode pembayaran
3. **Lihat Detail**: Klik tombol "Detail" untuk melihat item yang dibeli
4. **Cetak Ulang Struk**: Klik tombol "Struk"

---

## 🔧 FITUR TAMBAHAN

### Barcode Scanner

1. Hubungkan USB Barcode Scanner ke komputer
2. Scanner akan otomatis terdeteksi sebagai keyboard
3. Fokus cursor di input barcode (halaman kasir)
4. Scan barcode produk
5. Produk otomatis masuk ke keranjang

### Thermal Printer

1. Install driver printer thermal
2. Di halaman struk, klik "Cetak Struk"
3. Pilih printer thermal Anda
4. Atur ukuran kertas sesuai thermal (biasanya 80mm)
5. Print

### Keyboard Shortcut

- **Enter** setelah scan: Otomatis tambah produk
- **Tab**: Pindah antar field
- **F5**: Refresh halaman

---

## 📊 STRUKTUR DATABASE

### Tabel Utama:

1. **users**: Data user (admin/kasir)
2. **produk**: Master produk & stok
3. **diskon_belanja**: Setting diskon
4. **transaksi**: Header transaksi
5. **detail_transaksi**: Detail item transaksi
6. **log_stok**: Riwayat stok masuk/keluar
7. **tutup_kasir**: Data rekonsiliasi

---

## 🚨 TROUBLESHOOTING

### Problem: Database tidak terkoneksi
**Solusi**: 
- Pastikan MySQL di XAMPP sudah running
- Cek konfigurasi di `config.php`
- Pastikan nama database: `kasir_minimarket`

### Problem: Produk tidak muncul saat scan barcode
**Solusi**:
- Pastikan barcode sudah terdaftar di database
- Cek status produk = 'aktif'
- Periksa input barcode (tidak ada spasi)

### Problem: Stok tidak berkurang setelah transaksi
**Solusi**:
- Periksa tabel `log_stok`
- Pastikan trigger stok berjalan
- Cek transaksi berhasil tersimpan

### Problem: Diskon tidak muncul
**Solusi**:
- Pastikan total belanja sudah memenuhi minimal belanja
- Cek status diskon = 'aktif'
- Periksa perhitungan di tabel `diskon_belanja`

### Problem: Tidak bisa tutup kasir
**Solusi**:
- Periksa apakah sudah tutup kasir hari ini
- Pastikan ada transaksi di hari tersebut
- Cek user_id yang login

---

## 💡 TIPS PENGGUNAAN

1. **Backup Database Rutin**: Export database setiap hari/minggu
2. **Gunakan Barcode Scanner**: Lebih cepat daripada input manual
3. **Tutup Kasir Setiap Hari**: Untuk rekonsiliasi yang akurat
4. **Update Stok Berkala**: Sesuaikan dengan barang fisik
5. **Cek Laporan**: Monitor penjualan dan stok secara berkala

---

## 🔒 KEAMANAN

- Gunakan **password yang kuat** untuk user
- Ganti password default setelah instalasi
- Backup database secara berkala
- Jangan expose database ke internet
- Gunakan HTTPS untuk deployment production

---

## 📝 CATATAN PENGEMBANGAN

Untuk pengembangan lebih lanjut, Anda bisa menambahkan:

- Laporan grafik penjualan
- Export laporan ke Excel/PDF
- Integrasi payment gateway
- Multi-user kasir
- Notifikasi stok rendah
- Manajemen supplier
- Return/retur barang
- Member/loyalty program

---

## 📞 SUPPORT

Jika mengalami kesulitan, silakan:
1. Baca dokumentasi ini dengan teliti
2. Cek troubleshooting di atas
3. Periksa log error di PHP
4. Review kode di file terkait

---

## 📄 LISENSI

Aplikasi ini dibuat untuk keperluan edukasi dan komersial.
Anda bebas memodifikasi sesuai kebutuhan.

---

## ✨ FITUR UNGGULAN

✅ **Real-time Calculation**: Total dan diskon dihitung otomatis
✅ **Stock Management**: Stok terupdate real-time
✅ **Multi Payment**: Cash, QRIS, Transfer
✅ **Auto Discount**: Diskon otomatis berdasarkan total belanja
✅ **Cash Reconciliation**: Rekonsiliasi uang fisik vs sistem
✅ **Print Receipt**: Cetak struk profesional
✅ **Transaction History**: Riwayat lengkap dengan detail
✅ **User Friendly**: Interface sederhana dan mudah digunakan

---

**Selamat menggunakan Sistem Kasir Minimarket! 🏪**

Developed with ❤️ for small business
