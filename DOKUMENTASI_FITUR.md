# DOKUMENTASI FITUR LENGKAP
## SISTEM KASIR MINIMARKET

---

## 🎯 RINGKASAN SISTEM

Sistem Kasir Minimarket adalah aplikasi Point of Sale (POS) profesional yang dirancang khusus untuk kebutuhan toko retail/minimarket dengan fitur lengkap dan mudah digunakan.

---

## 📱 FITUR-FITUR UTAMA

### 1. SISTEM LOGIN & KEAMANAN

**Fitur:**
- Multi-user dengan role berbeda (Admin & Kasir)
- Session management untuk keamanan
- Password terenkripsi (MD5/Hash)
- Auto logout saat inaktif
- Validasi akses berdasarkan role

**Cara Penggunaan:**
1. Buka aplikasi di browser
2. Masukkan username dan password
3. Sistem akan redirect sesuai role:
   - Admin: Akses penuh ke semua menu
   - Kasir: Akses terbatas (kasir, produk, transaksi)

---

### 2. DASHBOARD

**Fitur:**
- Statistik real-time
- Total produk aktif
- Transaksi hari ini
- Penjualan hari ini
- Alert produk stok rendah
- Transaksi terakhir
- Grafik singkat

**Informasi yang Ditampilkan:**
- Ringkasan penjualan harian
- Produk dengan stok < 10
- 10 transaksi terakhir
- Update jam real-time

---

### 3. TRANSAKSI KASIR (INTI SISTEM)

**Fitur Utama:**
✅ Scan barcode produk (USB Scanner)
✅ Input manual barcode
✅ Keranjang belanja dinamis
✅ Tambah/kurang qty produk
✅ Hapus item dari keranjang
✅ Perhitungan otomatis subtotal
✅ Diskon otomatis berdasarkan total belanja
✅ 3 metode pembayaran: Cash, QRIS, Transfer
✅ Validasi stok real-time
✅ Perhitungan kembalian otomatis (cash)

**Alur Transaksi:**
1. Kasir login
2. Klik menu "Kasir"
3. Scan/input barcode produk
4. Produk masuk keranjang otomatis
5. Atur qty dengan tombol +/-
6. Sistem hitung total + diskon
7. Pilih metode pembayaran:
   - **Cash**: Input uang yang dibayar → sistem hitung kembalian
   - **QRIS**: Customer scan QR → langsung proses
   - **Transfer**: Customer transfer → langsung proses
8. Klik "Proses Pembayaran"
9. Struk otomatis muncul
10. Print struk
11. Transaksi tersimpan
12. Stok otomatis berkurang

**Validasi:**
- Cek produk exists
- Cek stok tersedia
- Cek uang cash cukup
- Prevent double submit

---

### 4. MANAJEMEN PRODUK

**Fitur CRUD:**
- ➕ Tambah produk baru
- ✏️ Edit data produk
- 📥 Tambah stok
- 🗑️ Hapus produk (soft delete)
- 🔍 Cari produk

**Data Produk:**
- Barcode (unique)
- Nama produk
- Kategori
- Harga beli
- Harga jual
- Stok
- Satuan (pcs, box, kg, dll)
- Status (aktif/nonaktif)

**Manajemen Stok:**
- Input stok awal
- Tambah stok (barang datang)
- Stok keluar otomatis saat transaksi
- Log stok (IN/OUT) tersimpan
- Alert stok rendah (<10)

**Fitur Tambahan:**
- Filter/search produk
- Badge stok rendah (warna merah)
- Validasi barcode duplikat

---

### 5. DISKON BERDASARKAN MINIMAL BELANJA

**Konsep:**
Diskon TIDAK berdasarkan hari, tetapi berdasarkan TOTAL BELANJA.

**Cara Kerja:**
1. Admin set diskon dengan rule:
   - Minimal belanja: Rp 50.000
   - Diskon: 5%
   
2. Saat transaksi:
   - Total belanja Rp 60.000
   - Memenuhi minimal Rp 50.000
   - Dapat diskon 5% = Rp 3.000
   - Total bayar = Rp 57.000

**Fitur:**
- Multiple diskon tier
- Sistem pilih diskon terbesar otomatis
- Simulasi diskon (test sebelum apply)
- Aktifkan/nonaktifkan diskon
- Edit diskon kapan saja

**Contoh Implementasi:**
```
Diskon 5%  → Min. belanja Rp 50.000
Diskon 10% → Min. belanja Rp 100.000
Diskon 15% → Min. belanja Rp 200.000

Pelanggan belanja Rp 120.000:
- Memenuhi 3 diskon
- Sistem pilih terbesar: 10%
- Potongan: Rp 12.000
- Bayar: Rp 108.000
```

---

### 6. METODE PEMBAYARAN

**A. CASH (Tunai)**
- Input uang yang dibayar
- Sistem hitung kembalian
- Validasi uang harus >= total
- Tersimpan untuk rekonsiliasi

**B. QRIS**
- Customer scan QR code
- Konfirmasi pembayaran
- Langsung proses
- Tidak ada kembalian

**C. TRANSFER BANK**
- Customer transfer ke rekening toko
- Konfirmasi transfer
- Langsung proses
- Tidak ada kembalian

**Tracking:**
Semua metode tercatat untuk:
- Laporan harian
- Tutup kasir
- Analisis penjualan

---

### 7. STRUK BELANJA

**Informasi di Struk:**
- Logo/nama toko
- Alamat & telepon
- Nomor transaksi (auto generate)
- Tanggal & waktu transaksi
- Nama kasir
- Daftar produk:
  - Nama produk
  - Qty × Harga
  - Subtotal
- Subtotal
- Diskon (% dan nominal)
- **TOTAL BAYAR**
- Metode pembayaran
- Uang dibayar (cash)
- Kembalian (cash)
- Footer/ucapan terima kasih

**Fitur Print:**
- Print via browser (Ctrl+P)
- Support thermal printer
- Format A4/thermal 58mm/80mm
- Print preview
- Cetak ulang kapan saja

---

### 8. TUTUP KASIR & REKONSILIASI

**Tujuan:**
Memastikan uang fisik di laci = uang di sistem

**Proses:**
1. Kasir klik "Tutup Kasir"
2. Sistem tampilkan rekap:
   - Total transaksi hari ini
   - Penjualan Cash
   - Penjualan QRIS
   - Penjualan Transfer
   - Total semua penjualan

3. Kasir hitung uang fisik di laci
4. Input jumlah uang fisik
5. Sistem hitung selisih:
   ```
   Selisih = Uang Fisik - Penjualan Cash
   ```

**Hasil Selisih:**
- **PLUS (+)**: Uang lebih → Cek ada uang tambahan
- **MINUS (-)**: Uang kurang → Cek kesalahan/kehilangan
- **PAS (0)**: Sesuai → Perfect!

**Data yang Tersimpan:**
- Tanggal tutup kasir
- User yang tutup
- Total transaksi
- Breakdown per metode
- Uang fisik
- Selisih
- Keterangan
- Waktu tutup

**Aturan:**
- Hanya bisa tutup 1× per hari per kasir
- Setelah tutup, tidak bisa edit
- Riwayat tersimpan permanen

---

### 9. RIWAYAT TRANSAKSI

**Fitur:**
- Lihat semua transaksi
- Filter by tanggal
- Filter by metode pembayaran
- Lihat detail transaksi
- Cetak ulang struk
- Export data

**Detail Transaksi:**
- Kode transaksi
- Tanggal & waktu
- Kasir
- Daftar item
- Total, diskon, bayar
- Metode pembayaran

**Filter:**
- Hari ini
- Minggu ini
- Bulan ini
- Custom range
- Per metode pembayaran

---

### 10. LAPORAN (ADMIN ONLY)

**Jenis Laporan:**

**A. Laporan Penjualan**
- Total transaksi
- Total penjualan
- Total diskon
- Rata-rata transaksi
- Breakdown per metode

**B. Grafik Penjualan**
- Penjualan harian (line chart)
- Per metode pembayaran (pie chart)
- Trend penjualan

**C. Produk Terlaris**
- Top 10 produk
- Jumlah terjual
- Total pendapatan

**Filter Periode:**
- Hari ini
- Minggu ini
- Bulan ini
- Custom (dari-sampai)

---

### 11. MANAJEMEN USER (ADMIN ONLY)

**Fitur:**
- Tambah user baru
- Edit data user
- Reset password user
- Aktifkan/nonaktifkan user
- Set role (Admin/Kasir)

**Role & Hak Akses:**

**ADMIN:**
- Full access semua menu
- Kelola produk
- Kelola diskon
- Kelola user
- Lihat laporan lengkap
- Bisa transaksi kasir

**KASIR:**
- Transaksi kasir
- Lihat produk
- Tutup kasir
- Lihat transaksi (terbatas)

---

## 🔐 KEAMANAN SISTEM

### Proteksi yang Diterapkan:

1. **Session Management**
   - Auto redirect jika belum login
   - Session timeout
   - Validasi user aktif

2. **SQL Injection Prevention**
   - Clean function untuk input
   - mysqli_real_escape_string
   - Prepared statements

3. **XSS Protection**
   - htmlspecialchars untuk output
   - Filter input user

4. **Password Security**
   - Hash password (MD5/bcrypt)
   - Reset password aman

5. **Access Control**
   - Role-based access
   - Menu restriction
   - Function-level security

---

## 📊 TRACKING & AUDIT

**Log Stok:**
- Setiap perubahan stok tercatat
- Stok masuk (IN)
- Stok keluar (OUT)
- User yang input
- Keterangan
- Timestamp

**Log Transaksi:**
- Semua transaksi tersimpan
- Detail item
- User yang input
- Waktu transaksi

**Log Tutup Kasir:**
- Riwayat tutup kasir
- Selisih uang
- Keterangan
- User responsible

---

## 💡 TIPS & BEST PRACTICE

### Untuk Kasir:
1. Scan barcode dengan cepat
2. Cek total sebelum proses
3. Hitung kembalian dengan benar
4. Cetak struk untuk customer
5. Tutup kasir setiap akhir shift

### Untuk Admin:
1. Update stok secara berkala
2. Set diskon yang masuk akal
3. Monitor laporan harian
4. Backup database rutin
5. Ganti password default
6. Training kasir sebelum operasi

### Untuk Maintenance:
1. Backup database setiap hari
2. Bersihkan data lama
3. Optimasi database
4. Update stok sesuai fisik
5. Audit transaksi berkala

---

## 🛠️ KUSTOMISASI

Sistem dapat dikustomisasi:

1. **Tampilan:**
   - Ganti logo toko
   - Ubah warna tema
   - Custom layout struk

2. **Fitur:**
   - Tambah kategori produk
   - Tambah metode pembayaran
   - Custom laporan

3. **Rule Bisnis:**
   - Atur minimal belanja
   - Set diskon custom
   - Atur margin harga

---

## 📈 SKALABILITAS

Sistem dapat dikembangkan untuk:
- Multi-outlet/cabang
- Integrasi payment gateway
- Mobile app untuk kasir
- Dashboard analytics advanced
- Integrasi e-commerce
- API untuk third-party
- Notifikasi WhatsApp/Email
- Loyalty program

---

## 🎓 KESIMPULAN

Sistem Kasir Minimarket ini adalah solusi lengkap untuk:
- Toko retail kecil-menengah
- Minimarket
- Warung/kios
- Toko kelontong
- Dan bisnis retail lainnya

Dengan fitur lengkap, mudah digunakan, dan dapat dikustomisasi sesuai kebutuhan.

---

**Happy Selling! 🛒💰**
