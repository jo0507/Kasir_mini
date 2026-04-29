# PANDUAN TROUBLESHOOTING
## SISTEM KASIR MINIMARKET

---

## 🔧 MASALAH UMUM & SOLUSI

### 1. DATABASE CONNECTION ERROR

**Gejala:**
```
Warning: mysqli_connect(): (HY000/1045): Access denied for user 'root'@'localhost'
```

**Penyebab:**
- MySQL belum running
- Username/password salah
- Database belum dibuat

**Solusi:**
1. Buka XAMPP Control Panel
2. Start MySQL
3. Buka phpMyAdmin: http://localhost/phpmyadmin
4. Cek database `kasir_minimarket` ada atau tidak
5. Jika belum, import `database.sql`
6. Edit `config.php`:
   ```php
   define('DB_USER', 'root');  // Sesuaikan
   define('DB_PASS', '');      // Sesuaikan
   ```

---

### 2. BLANK PAGE / WHITE SCREEN

**Gejala:**
Halaman putih kosong tanpa error

**Penyebab:**
- PHP error tidak ditampilkan
- Syntax error di code
- File corrupt

**Solusi:**
1. Enable error display:
   Edit `config.php`, tambahkan:
   ```php
   ini_set('display_errors', 1);
   error_reporting(E_ALL);
   ```

2. Cek PHP error log:
   ```
   C:\xampp\apache\logs\error.log
   ```

3. Cek syntax:
   ```
   php -l nama_file.php
   ```

---

### 3. PRODUK TIDAK MUNCUL SAAT SCAN

**Gejala:**
Alert "Produk tidak ditemukan"

**Penyebab:**
- Barcode tidak terdaftar
- Barcode typo
- Produk nonaktif
- Spasi di barcode

**Solusi:**
1. Cek barcode di database:
   ```sql
   SELECT * FROM produk WHERE barcode = 'BARCODE_ANDA';
   ```

2. Pastikan status = 'aktif'
3. Hapus spasi di barcode
4. Gunakan barcode yang benar
5. Scan ulang barcode

**Debug:**
Tambahkan di `kasir.php`:
```php
echo "Barcode: " . $barcode;
var_dump($result);
```

---

### 4. STOK TIDAK BERKURANG

**Gejala:**
Setelah transaksi, stok produk tetap

**Penyebab:**
- Query update stok error
- Transaksi rollback
- Foreign key constraint

**Solusi:**
1. Cek tabel `log_stok`:
   ```sql
   SELECT * FROM log_stok ORDER BY id DESC LIMIT 10;
   ```

2. Cek stok produk:
   ```sql
   SELECT * FROM produk WHERE id = PRODUK_ID;
   ```

3. Manual update stok:
   ```sql
   UPDATE produk SET stok = stok - QTY WHERE id = PRODUK_ID;
   ```

4. Cek error di PHP:
   ```php
   if (!mysqli_query($conn, $query_stok)) {
       echo "Error: " . mysqli_error($conn);
   }
   ```

---

### 5. DISKON TIDAK MUNCUL

**Gejala:**
Total belanja sudah memenuhi, tapi tidak dapat diskon

**Penyebab:**
- Diskon nonaktif
- Minimal belanja kurang
- Query diskon error

**Solusi:**
1. Cek status diskon:
   ```sql
   SELECT * FROM diskon_belanja WHERE status = 'aktif';
   ```

2. Cek minimal belanja:
   ```
   Total belanja >= minimal_belanja
   ```

3. Debug query diskon di `kasir.php`:
   ```php
   echo "Subtotal: " . $subtotal;
   var_dump($diskon_data);
   ```

4. Test manual:
   ```sql
   SELECT * FROM diskon_belanja 
   WHERE status = 'aktif' AND minimal_belanja <= 100000
   ORDER BY persentase_diskon DESC LIMIT 1;
   ```

---

### 6. TIDAK BISA LOGIN

**Gejala:**
Username/password benar tapi tidak bisa login

**Penyebab:**
- Password hash tidak cocok
- Status user nonaktif
- Session error

**Solusi:**
1. Cek user di database:
   ```sql
   SELECT * FROM users WHERE username = 'admin';
   ```

2. Cek password hash:
   ```sql
   SELECT MD5('admin123');
   -- Hasilnya harus sama dengan password di database
   ```

3. Reset password manual:
   ```sql
   UPDATE users SET password = MD5('admin123') WHERE username = 'admin';
   ```

4. Cek status:
   ```sql
   UPDATE users SET status = 'aktif' WHERE username = 'admin';
   ```

5. Clear session:
   Hapus cookies browser atau gunakan incognito

---

### 7. STRUK TIDAK TERCETAK

**Gejala:**
Klik print tapi tidak ada yang keluar

**Penyebab:**
- Printer tidak terdeteksi
- Driver printer belum install
- Browser block popup

**Solusi:**
1. Cek printer:
   - Control Panel → Devices & Printers
   - Pastikan printer online

2. Test print:
   - Print halaman test

3. Browser setting:
   - Allow popup untuk localhost
   - Cek print preview (Ctrl+P)

4. Thermal printer:
   - Set paper size: 58mm atau 80mm
   - Landscape/Portrait sesuai printer

---

### 8. TUTUP KASIR ERROR

**Gejala:**
Tidak bisa tutup kasir atau error saat submit

**Penyebab:**
- Sudah tutup kasir hari ini
- Input uang fisik kosong
- Database error

**Solusi:**
1. Cek sudah tutup atau belum:
   ```sql
   SELECT * FROM tutup_kasir 
   WHERE DATE(waktu_tutup) = CURDATE() AND user_id = USER_ID;
   ```

2. Jika perlu reset:
   ```sql
   DELETE FROM tutup_kasir WHERE id = TUTUP_KASIR_ID;
   ```

3. Validasi input:
   - Pastikan uang fisik terisi
   - Format angka benar

---

### 9. KERANJANG HILANG

**Gejala:**
Produk di keranjang tiba-tiba hilang

**Penyebab:**
- Session expired
- Browser closed
- Logout/login

**Solusi:**
1. Hindari refresh berlebihan
2. Jangan logout saat transaksi
3. Setting session timeout lebih lama:
   ```php
   // config.php
   ini_set('session.gc_maxlifetime', 3600);
   session_set_cookie_params(3600);
   ```

---

### 10. LAPORAN KOSONG

**Gejala:**
Halaman laporan tidak ada data

**Penyebab:**
- Tidak ada transaksi di periode tersebut
- Filter tanggal salah
- Query error

**Solusi:**
1. Cek ada transaksi atau tidak:
   ```sql
   SELECT COUNT(*) FROM transaksi WHERE DATE(tanggal) = CURDATE();
   ```

2. Cek filter tanggal:
   - Pastikan format: YYYY-MM-DD
   - Dari <= Sampai

3. Test query manual di phpMyAdmin

---

### 11. BARCODE SCANNER TIDAK BERFUNGSI

**Gejala:**
Scan barcode tidak input otomatis

**Penyebab:**
- Scanner tidak terdeteksi
- Focus tidak di input barcode
- Scanner mode salah

**Solusi:**
1. Cek koneksi USB scanner
2. Test scanner di notepad:
   - Buka notepad
   - Scan barcode
   - Harusnya muncul angka

3. Klik input barcode sebelum scan
4. Setting scanner ke keyboard mode
5. Cek enter otomatis after scan

---

### 12. PERMISSION DENIED

**Gejala:**
```
Warning: fopen(): Permission denied
```

**Penyebab:**
- Folder tidak writable
- User Apache tidak punya akses

**Solusi:**
1. Windows:
   - Klik kanan folder → Properties
   - Security tab
   - Full control untuk Everyone

2. Linux:
   ```bash
   chmod -R 755 sistem_kasir
   chown -R www-data:www-data sistem_kasir
   ```

---

### 13. QUERY TOO SLOW

**Gejala:**
Halaman loading lama, query lambat

**Penyebab:**
- Data terlalu banyak
- Tidak ada index
- Join kompleks

**Solusi:**
1. Buat index:
   ```sql
   CREATE INDEX idx_tanggal ON transaksi(tanggal);
   CREATE INDEX idx_barcode ON produk(barcode);
   ```

2. Limit data:
   ```sql
   SELECT * FROM transaksi ORDER BY id DESC LIMIT 100;
   ```

3. Optimasi query:
   - Gunakan WHERE yang efektif
   - Hindari SELECT *
   - Gunakan JOIN yang tepat

4. Clean old data:
   ```sql
   DELETE FROM log_stok WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR);
   ```

---

### 14. DUPLICATE ENTRY ERROR

**Gejala:**
```
Duplicate entry 'XXX' for key 'PRIMARY'
```

**Penyebab:**
- Insert data dengan ID yang sudah ada
- Unique constraint violation

**Solusi:**
1. Cek auto increment:
   ```sql
   SHOW TABLE STATUS LIKE 'nama_tabel';
   ```

2. Reset auto increment:
   ```sql
   ALTER TABLE nama_tabel AUTO_INCREMENT = 1;
   ```

3. Cek unique key:
   ```sql
   SHOW INDEX FROM nama_tabel;
   ```

---

### 15. SESSION ERROR

**Gejala:**
```
Warning: session_start(): Failed to read session data
```

**Penyebab:**
- Permission folder session
- Disk full

**Solusi:**
1. Clear session folder:
   ```
   C:\xampp\tmp\
   ```

2. Buat folder session baru:
   ```php
   session_save_path('C:/xampp/tmp');
   ```

3. Cek disk space

---

## 🔍 DEBUGGING TOOLS

### 1. PHP Error Log
Lokasi:
```
C:\xampp\apache\logs\error.log
```

### 2. MySQL Query Log
Enable di `my.ini`:
```ini
general_log = 1
general_log_file = "C:/xampp/mysql/data/mysql.log"
```

### 3. Browser Console
- F12 → Console
- Lihat JavaScript error

### 4. Network Tab
- F12 → Network
- Lihat request/response

### 5. Var Dump
```php
echo "<pre>";
var_dump($variable);
echo "</pre>";
die();
```

---

## 📝 CHECKLIST TROUBLESHOOTING

Jika ada masalah, cek secara berurutan:

- [ ] XAMPP Apache & MySQL running
- [ ] Database imported & exists
- [ ] Config.php sudah benar
- [ ] PHP error tidak ada
- [ ] Browser cache cleared
- [ ] Session cleared
- [ ] File permission OK
- [ ] Network connection OK

---

## 🆘 MASIH ERROR?

Jika masih error setelah semua solusi dicoba:

1. **Export database** untuk backup
2. **Drop database** dan re-import
3. **Re-extract** file dari ZIP
4. **Fresh install** dari awal
5. **Cek system requirements**:
   - PHP >= 7.0
   - MySQL >= 5.6
   - Apache >= 2.4

---

## 📞 GETTING HELP

Jika butuh bantuan:

1. **Dokumentasi:**
   - Baca README.md
   - Baca DOKUMENTASI_FITUR.md

2. **Error Message:**
   - Screenshot error
   - Copy paste error message
   - Cek error log

3. **Reproduce:**
   - Langkah untuk reproduce error
   - Kapan error terjadi
   - Kondisi saat error

---

## ✅ BEST PRACTICE

Untuk menghindari error:

1. **Backup rutin** database
2. **Testing** sebelum production
3. **Update** PHP & MySQL
4. **Monitor** error log
5. **Training** user sebelum pakai
6. **Documentation** setiap perubahan

---

**Good luck! 🚀**
