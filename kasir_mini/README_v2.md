# SISTEM KASIR + ABSENSI IoT + PENGGAJIAN
## Versi 2.0 - Complete Integrated System

---

## 🎯 RINGKASAN SISTEM

Sistem terintegrasi lengkap untuk operasional toko/minimarket yang menggabungkan:
1. **Sistem Kasir** (Point of Sale)
2. **Absensi IoT** (RFID/Fingerprint dengan ESP32/ESP8266)
3. **Penggajian Otomatis** (Berdasarkan Absensi & Penjualan)

---

## ✨ FITUR BARU VERSI 2.0

### 🆕 MODUL ABSENSI IoT

✅ **Absensi Otomatis tanpa Input Manual**
- Scan RFID atau sidik jari
- Perangkat: ESP32/ESP8266
- Komunikasi: HTTP POST ke server PHP
- Real-time processing

✅ **Logika Absensi Cerdas**
- Auto detect: Masuk atau Pulang
- Cegah duplikasi absensi
- Status otomatis: HADIR, TERLAMBAT (>08:30)
- Perhitungan durasi kerja

✅ **Multi-Method Absensi**
- RFID Card/Tag
- Fingerprint Sensor
- Kombinasi keduanya

✅ **Hak Akses Berbeda**
- **Admin**: Lihat semua, input manual, koreksi data
- **Pegawai**: Hanya lihat absensi sendiri (read-only)
- **Kasir**: Lihat pegawai yang hadir hari ini

### 🆕 MODUL PENGGAJIAN OTOMATIS

✅ **Perhitungan Gaji Otomatis**
```
Gaji Bersih = Gaji Pokok - Potongan + Bonus
```

✅ **Potongan Berdasarkan Absensi**
- ALFA → Potong gaji (setting admin)
- IZIN → Potong gaji (setting admin)
- SAKIT → Tidak dipotong (dengan surat)
- TERLAMBAT → Tercatat tapi tidak dipotong

✅ **Bonus Penjualan**
- Berdasarkan total transaksi pegawai
- Multiple tier bonus
- Sistem pilih bonus terbesar otomatis
- Contoh:
  - Penjualan ≥ 5 juta → Bonus 200k
  - Penjualan ≥ 10 juta → Bonus 500k
  - Penjualan ≥ 20 juta → Bonus 1 juta

✅ **Slip Gaji Digital**
- Detail absensi bulanan
- Rincian perhitungan transparan
- Cetak slip gaji
- Riwayat gaji tersimpan

✅ **Hak Akses Penggajian**
- **Admin**: Hitung gaji, bayar gaji, lihat semua
- **Pegawai**: Hanya lihat slip gaji sendiri

### 🆕 MANAJEMEN PEGAWAI

✅ **Data Kepegawaian Lengkap**
- NIP (Nomor Induk Pegawai)
- RFID UID
- Fingerprint ID
- Jabatan
- Gaji Pokok
- Tanggal Bergabung

✅ **Integrasi dengan User**
- Setiap pegawai punya akun user
- Role-based access control
- Multi-device login

---

## 🏗️ ARSITEKTUR SISTEM

```
┌─────────────────────────────────────────────────┐
│              HARDWARE LAYER                      │
│  [RFID Reader] [Fingerprint] [Barcode Scanner]  │
│         ↓              ↓              ↓          │
│     [ESP32/ESP8266]   [USB]      [USB]          │
└─────────────────────────────────────────────────┘
                        ↓
┌─────────────────────────────────────────────────┐
│            COMMUNICATION LAYER                   │
│      [WiFi]         [USB]         [USB]         │
└─────────────────────────────────────────────────┘
                        ↓
┌─────────────────────────────────────────────────┐
│             APPLICATION LAYER                    │
│  [PHP Server] - [MySQL Database] - [Session]   │
└─────────────────────────────────────────────────┘
                        ↓
┌─────────────────────────────────────────────────┐
│              BUSINESS LOGIC                      │
│ [Kasir] [Absensi] [Penggajian] [Laporan]       │
└─────────────────────────────────────────────────┘
                        ↓
┌─────────────────────────────────────────────────┐
│              PRESENTATION LAYER                  │
│  [Web Browser] - [Thermal Printer] - [Display] │
└─────────────────────────────────────────────────┘
```

---

## 📊 STRUKTUR DATABASE

### Tabel Baru:

1. **pegawai**
   - Data kepegawaian lengkap
   - Relasi ke tabel users
   - RFID UID & Fingerprint ID

2. **absensi**
   - Record absensi harian
   - Jam masuk & pulang
   - Status kehadiran
   - Unique: pegawai + tanggal

3. **setting_potongan**
   - Aturan potongan gaji
   - Per jenis ketidakhadiran
   - Status aktif/nonaktif

4. **setting_bonus**
   - Aturan bonus penjualan
   - Minimal penjualan & nominal
   - Multiple tier

5. **penggajian**
   - Gaji per bulan per pegawai
   - Rincian lengkap
   - Status pembayaran

6. **log_absensi_iot**
   - Tracking device IoT
   - Success/failed attempts
   - IP & device ID

---

## 🚀 INSTALASI SISTEM LENGKAP

### 1. Server (Sama seperti v1.0)
```bash
1. Install XAMPP
2. Extract ke C:\xampp\htdocs\sistem_kasir
3. Import database.sql
4. Start Apache & MySQL
```

### 2. IoT Device (BARU!)
```bash
1. Install Arduino IDE
2. Install library yang diperlukan
3. Upload code ke ESP32/ESP8266
4. Konfigurasi WiFi dan server URL
5. Registrasi RFID/Fingerprint pegawai
```

📖 **Panduan lengkap:** Baca `DOKUMENTASI_IOT.md`

---

## 🔐 LOGIN & HAK AKSES

### Role & Menu Access:

| Menu | Admin | Kasir | Pegawai |
|------|-------|-------|---------|
| Dashboard | ✅ | ✅ | ✅ |
| Kasir | ✅ | ✅ | ❌ |
| Produk | ✅ | ✅ | ❌ |
| Diskon | ✅ | ✅ | ❌ |
| Transaksi | ✅ | ✅ | ❌ |
| Tutup Kasir | ✅ | ✅ | ❌ |
| Laporan | ✅ | ❌ | ❌ |
| Pegawai | ✅ | ❌ | ❌ |
| Absensi (All) | ✅ | Lihat | Own |
| Setting Gaji | ✅ | ❌ | ❌ |
| Penggajian | ✅ | ❌ | Own |
| User | ✅ | ❌ | ❌ |

**Own** = Hanya data milik sendiri (read-only)

---

## 📝 WORKFLOW LENGKAP

### A. Operasional Harian

**Pagi Hari:**
1. Pegawai scan RFID/fingerprint → Absen MASUK
2. Sistem catat jam masuk
3. Status: HADIR atau TERLAMBAT (>08:30)

**Siang Hari:**
4. Kasir login
5. Transaksi penjualan seperti biasa
6. Sistem catat user_id kasir per transaksi

**Sore Hari:**
7. Pegawai scan RFID/fingerprint → Absen PULANG
8. Sistem catat jam pulang
9. Hitung durasi kerja

**Malam Hari:**
10. Kasir tutup kasir
11. Rekonsiliasi uang fisik vs sistem

### B. Akhir Bulan (Penggajian)

**Admin:**
1. Login sebagai admin
2. Buka menu "Penggajian"
3. Pilih pegawai & periode bulan
4. Klik "Hitung Gaji"
5. Sistem otomatis:
   - Hitung total hadir/izin/alfa
   - Kalkulasi potongan
   - Hitung total penjualan pegawai
   - Kalkulasi bonus
   - Hitung gaji bersih
6. Review slip gaji
7. Approve & bayar gaji
8. Cetak slip gaji

**Pegawai:**
1. Login sebagai pegawai
2. Buka menu "Slip Gaji"
3. Lihat gaji bulan ini
4. Download/cetak slip gaji

---

## 📖 FILE-FILE PENTING

### File PHP Utama:
- `api_absensi.php` - API endpoint untuk IoT
- `pegawai.php` - Manajemen data pegawai
- `absensi.php` - Halaman absensi (multi-role)
- `setting_gaji.php` - Setting potongan & bonus
- `penggajian.php` - Modul penggajian otomatis
- `get_detail_gaji.php` - AJAX slip gaji

### Dokumentasi:
- `DOKUMENTASI_IOT.md` - Panduan lengkap IoT setup
- `README.md` - File ini
- `QUICK_START.txt` - Panduan cepat
- `TROUBLESHOOTING.md` - Solusi masalah

### Code Arduino:
- Tersedia di `DOKUMENTASI_IOT.md`
- Code untuk ESP8266 (RFID)
- Code untuk ESP32 (RFID + Fingerprint)

---

## 🧪 TESTING SISTEM

### Test 1: Absensi Manual (Admin)
1. Login sebagai admin
2. Buka "Absensi"
3. Input absensi manual untuk testing
4. Cek data tersimpan di database

### Test 2: API Absensi
```bash
# Test dengan Postman atau curl
curl -X POST http://localhost/sistem_kasir/api_absensi.php \
  -d "rfid_uid=A1B2C3D4&device_id=TEST"
```

Expected response:
```json
{
  "success": true,
  "message": "Absen MASUK berhasil!",
  "timestamp": "2025-01-19 08:00:00"
}
```

### Test 3: Perhitungan Gaji
1. Login admin
2. Setting Gaji → Setting potongan & bonus
3. Input data absensi beberapa hari
4. Simulasi perhitungan gaji
5. Hitung gaji untuk pegawai
6. Cek hasil kalkulasi

---

## ⚙️ KONFIGURASI

### 1. Jam Kerja & Batas Terlambat
Edit di `config.php`:
```php
function cekStatusAbsensi($jam_masuk, $batas_terlambat = '08:30:00') {
    // Ubah '08:30:00' sesuai kebutuhan
}
```

### 2. Potongan & Bonus
Setting melalui admin panel:
- Menu "Setting Gaji"
- Tambah/edit potongan
- Tambah/edit bonus

### 3. IoT Device
Edit di Arduino code:
```cpp
const char* ssid = "YOUR_WIFI";
const char* password = "YOUR_PASSWORD";
const char* serverUrl = "http://IP_SERVER/sistem_kasir/api_absensi.php";
```

---

## 🔒 KEAMANAN

### Tambahan di v2.0:
✅ API authentication (device_id)
✅ Log semua request IoT
✅ Validasi RFID/Fingerprint
✅ Prevent duplicate absensi
✅ Role-based menu access
✅ Read-only untuk pegawai
✅ Audit trail lengkap

---

## 📊 LAPORAN YANG TERSEDIA

1. **Laporan Penjualan** (existing)
   - Total penjualan
   - Per metode pembayaran
   - Produk terlaris

2. **Laporan Absensi** (new)
   - Rekap harian
   - Rekap bulanan
   - Per pegawai
   - Statistik kehadiran

3. **Laporan Penggajian** (new)
   - Gaji per pegawai
   - Total gaji perusahaan
   - Rincian potongan
   - Rincian bonus

---

## 💡 TIPS PENGGUNAAN

### Untuk Admin:
1. Set potongan & bonus di awal
2. Registrasi RFID/fingerprint semua pegawai
3. Test IoT device sebelum deployment
4. Hitung gaji setiap akhir bulan
5. Backup database rutin

### Untuk Kasir:
1. Absen masuk sebelum kerja
2. Lakukan transaksi seperti biasa
3. Absen pulang setelah selesai
4. Tutup kasir di akhir hari
5. Cek slip gaji di akhir bulan

### Untuk Pegawai:
1. Scan RFID/fingerprint saat masuk
2. Scan RFID/fingerprint saat pulang
3. Cek riwayat absensi reguler
4. Download slip gaji setiap bulan

---

## 🚧 TROUBLESHOOTING UMUM

### Absensi tidak masuk:
- Cek RFID UID sudah terdaftar
- Cek device connect ke WiFi
- Cek IP server benar
- Lihat log_absensi_iot

### Gaji tidak sesuai:
- Cek data absensi lengkap
- Cek setting potongan aktif
- Cek setting bonus aktif
- Cek total penjualan pegawai

### Device tidak connect:
- Cek SSID & password WiFi
- Pastikan WiFi 2.4GHz
- Cek jarak ke router
- Reset device

📖 **Panduan lengkap:** Baca `TROUBLESHOOTING.md`

---

## 📈 UPGRADE PATH

### Dari v1.0 ke v2.0:
1. Backup database v1.0
2. Extract sistem v2.0
3. Import `database.sql` (akan add tabel baru)
4. Data lama tetap aman
5. Setup IoT device
6. Registrasi pegawai

---

## 🎓 TRAINING CHECKLIST

Sebelum go-live, pastikan:

- [ ] Admin paham cara setting potongan/bonus
- [ ] Admin bisa hitung gaji manual
- [ ] Kasir paham cara absensi
- [ ] Kasir tahu cara tutup kasir
- [ ] Pegawai tahu cara scan absensi
- [ ] IoT device sudah ditest
- [ ] Semua RFID/fingerprint terdaftar
- [ ] Backup system siap
- [ ] Emergency contact admin siap

---

## 📞 SUPPORT

### Dokumentasi:
- README.md - Overview
- QUICK_START.txt - Panduan cepat
- DOKUMENTASI_IOT.md - Setup IoT
- DOKUMENTASI_FITUR.md - Detail fitur
- TROUBLESHOOTING.md - Solusi masalah

### Database:
```sql
-- Cek absensi hari ini
SELECT * FROM absensi WHERE DATE(tanggal) = CURDATE();

-- Cek gaji bulan ini
SELECT * FROM penggajian WHERE periode_bulan = MONTH(NOW());

-- Cek log IoT
SELECT * FROM log_absensi_iot ORDER BY id DESC LIMIT 50;
```

---

## ✨ FITUR UNGGULAN v2.0

✅ **IoT Integration** - Absensi otomatis tanpa sentuh
✅ **Auto Payroll** - Gaji dihitung otomatis
✅ **Fair Calculation** - Transparansi perhitungan
✅ **Multi-Device** - Support RFID & Fingerprint
✅ **Real-time** - Data langsung masuk sistem
✅ **Role-based** - Hak akses sesuai jabatan
✅ **Audit Trail** - Semua tercatat
✅ **Mobile Friendly** - Akses dari smartphone
✅ **Scalable** - Siap untuk multi-cabang

---

## 🎯 ROADMAP v3.0 (Future)

- [ ] Mobile app untuk pegawai
- [ ] Push notification absensi
- [ ] Face recognition
- [ ] Multi-cabang support
- [ ] Export laporan Excel/PDF
- [ ] WhatsApp notification
- [ ] Dashboard analytics advanced
- [ ] API untuk third-party
- [ ] Cloud sync

---

## 📄 LISENSI

Sistem ini bebas digunakan untuk keperluan bisnis.
Modifikasi diperbolehkan sesuai kebutuhan.

---

**Sistem Kasir + Absensi IoT + Penggajian v2.0**

Developed with ❤️ for modern retail business

**Selamat menggunakan sistem terintegrasi! 🚀**
