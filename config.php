<?php
// Konfigurasi Database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'kasir_minimarket');

// Koneksi Database
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Cek koneksi
if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// Set charset UTF-8
mysqli_set_charset($conn, "utf8");

// Fungsi untuk mencegah SQL Injection
function clean($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    $data = mysqli_real_escape_string($conn, $data);
    return $data;
}

// Fungsi format rupiah
function rupiah($angka) {
    return "Rp " . number_format($angka, 0, ',', '.');
}

// Fungsi format tanggal Indonesia
function tanggal_indonesia($tanggal) {
    $bulan = array(
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    );
    $pecah = explode('-', date('Y-m-d', strtotime($tanggal)));
    return $pecah[2] . ' ' . $bulan[(int)$pecah[1]] . ' ' . $pecah[0];
}

// Fungsi format bulan tahun
function bulan_tahun($bulan, $tahun) {
    $nama_bulan = array(
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    );
    return $nama_bulan[(int)$bulan] . ' ' . $tahun;
}

// Fungsi generate kode transaksi
function generateKodeTransaksi() {
    global $conn;
    $tanggal = date('Ymd');
    $query = "SELECT kode_transaksi FROM transaksi WHERE DATE(tanggal) = CURDATE() ORDER BY id DESC LIMIT 1";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $lastKode = $row['kode_transaksi'];
        $lastNumber = (int)substr($lastKode, -4);
        $newNumber = $lastNumber + 1;
    } else {
        $newNumber = 1;
    }
    
    return 'TRX' . $tanggal . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
}

// Fungsi cek status absensi berdasarkan jam masuk
function cekStatusAbsensi($jam_masuk, $batas_terlambat = '08:30:00') {
    if (strtotime($jam_masuk) > strtotime($batas_terlambat)) {
        return 'TERLAMBAT';
    }
    return 'HADIR';
}

// Fungsi hitung durasi kerja (dalam jam)
function hitungDurasiKerja($jam_masuk, $jam_pulang) {
    if (!$jam_masuk || !$jam_pulang) return 0;
    
    $masuk = strtotime($jam_masuk);
    $pulang = strtotime($jam_pulang);
    
    $durasi_detik = $pulang - $masuk;
    $durasi_jam = $durasi_detik / 3600;
    
    return round($durasi_jam, 2);
}

// Fungsi get data pegawai by user_id
function getPegawaiByUserId($user_id) {
    global $conn;
    $query = "SELECT p.*, u.username, u.nama_lengkap, u.role 
              FROM pegawai p 
              JOIN users u ON p.user_id = u.id 
              WHERE p.user_id = $user_id AND p.status_kerja = 'aktif'";
    $result = mysqli_query($conn, $query);
    return mysqli_fetch_assoc($result);
}

// Fungsi get pegawai by RFID
function getPegawaiByRFID($rfid_uid) {
    global $conn;
    $rfid_uid = clean($rfid_uid);
    $query = "SELECT p.*, u.username, u.nama_lengkap 
              FROM pegawai p 
              JOIN users u ON p.user_id = u.id 
              WHERE p.rfid_uid = '$rfid_uid' AND p.status_kerja = 'aktif'";
    $result = mysqli_query($conn, $query);
    return mysqli_fetch_assoc($result);
}

// Fungsi get pegawai by fingerprint
function getPegawaiByFingerprint($fingerprint_id) {
    global $conn;
    $fingerprint_id = intval($fingerprint_id);
    $query = "SELECT p.*, u.username, u.nama_lengkap 
              FROM pegawai p 
              JOIN users u ON p.user_id = u.id 
              WHERE p.fingerprint_id = $fingerprint_id AND p.status_kerja = 'aktif'";
    $result = mysqli_query($conn, $query);
    return mysqli_fetch_assoc($result);
}
?>
