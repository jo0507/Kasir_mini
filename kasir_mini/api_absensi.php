<?php
/**
 * API Endpoint untuk IoT Device (ESP32/ESP8266)
 * Endpoint: api_absensi.php
 * Method: POST
 * Parameters: 
 *   - rfid_uid (optional)
 *   - fingerprint_id (optional)
 *   - device_id (optional)
 */

require_once 'config.php';

// Set header untuk JSON response
header('Content-Type: application/json');

// Fungsi untuk response JSON
function jsonResponse($success, $message, $data = null) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit;
}

// Cek method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Method not allowed. Use POST method.');
}

// Get POST data
$rfid_uid = isset($_POST['rfid_uid']) ? clean($_POST['rfid_uid']) : '';
$fingerprint_id = isset($_POST['fingerprint_id']) ? intval($_POST['fingerprint_id']) : 0;
$device_id = isset($_POST['device_id']) ? clean($_POST['device_id']) : 'unknown';

// Get IP address
$device_ip = $_SERVER['REMOTE_ADDR'];

// Validasi input
if (empty($rfid_uid) && empty($fingerprint_id)) {
    // Log failed attempt
    $query_log = "INSERT INTO log_absensi_iot (device_id, device_ip, status, message) 
                  VALUES ('$device_id', '$device_ip', 'failed', 'No RFID or Fingerprint provided')";
    mysqli_query($conn, $query_log);
    
    jsonResponse(false, 'RFID UID atau Fingerprint ID harus diisi');
}

// Cari pegawai berdasarkan RFID atau Fingerprint
$pegawai = null;
if (!empty($rfid_uid)) {
    $pegawai = getPegawaiByRFID($rfid_uid);
    $identifier = "RFID: $rfid_uid";
} else {
    $pegawai = getPegawaiByFingerprint($fingerprint_id);
    $identifier = "Fingerprint: $fingerprint_id";
}

// Jika pegawai tidak ditemukan
if (!$pegawai) {
    // Log unknown device
    $log_message = "Unknown identifier: $identifier";
    $query_log = "INSERT INTO log_absensi_iot (rfid_uid, fingerprint_id, device_id, device_ip, status, message) 
                  VALUES (" . ($rfid_uid ? "'$rfid_uid'" : "NULL") . ", " . ($fingerprint_id ? $fingerprint_id : "NULL") . ", '$device_id', '$device_ip', 'unknown', '$log_message')";
    mysqli_query($conn, $query_log);
    
    jsonResponse(false, 'Pegawai tidak ditemukan. Silakan hubungi admin.');
}

$pegawai_id = $pegawai['id'];
$nama_pegawai = $pegawai['nama_lengkap'];
$tanggal = date('Y-m-d');
$waktu = date('H:i:s');

// Cek apakah sudah absen hari ini
$query_cek = "SELECT * FROM absensi WHERE pegawai_id = $pegawai_id AND tanggal = '$tanggal'";
$result_cek = mysqli_query($conn, $query_cek);

if (mysqli_num_rows($result_cek) == 0) {
    // ABSEN MASUK (belum ada record hari ini)
    $status = cekStatusAbsensi($waktu, '08:30:00'); // Batas terlambat jam 08:30
    
    $query_absen = "INSERT INTO absensi (pegawai_id, tanggal, jam_masuk, status) 
                    VALUES ($pegawai_id, '$tanggal', '$waktu', '$status')";
    
    if (mysqli_query($conn, $query_absen)) {
        // Log success
        $query_log = "INSERT INTO log_absensi_iot (rfid_uid, fingerprint_id, device_id, device_ip, action, status, message) 
                      VALUES (" . ($rfid_uid ? "'$rfid_uid'" : "NULL") . ", " . ($fingerprint_id ? $fingerprint_id : "NULL") . ", '$device_id', '$device_ip', 'masuk', 'success', 'Absen masuk berhasil: $nama_pegawai')";
        mysqli_query($conn, $query_log);
        
        jsonResponse(true, "Absen MASUK berhasil! Selamat bekerja, $nama_pegawai", [
            'nama' => $nama_pegawai,
            'nip' => $pegawai['nip'],
            'action' => 'MASUK',
            'jam' => $waktu,
            'status' => $status
        ]);
    } else {
        jsonResponse(false, 'Gagal menyimpan data absensi');
    }
    
} else {
    // Sudah ada record, cek apakah sudah pulang
    $absensi = mysqli_fetch_assoc($result_cek);
    
    if ($absensi['jam_pulang']) {
        // Sudah absen pulang
        jsonResponse(false, "Anda sudah absen pulang hari ini pada " . $absensi['jam_pulang'], [
            'nama' => $nama_pegawai,
            'jam_masuk' => $absensi['jam_masuk'],
            'jam_pulang' => $absensi['jam_pulang']
        ]);
    } else {
        // ABSEN PULANG
        $query_pulang = "UPDATE absensi SET jam_pulang = '$waktu' WHERE id = {$absensi['id']}";
        
        if (mysqli_query($conn, $query_pulang)) {
            $durasi = hitungDurasiKerja($absensi['jam_masuk'], $waktu);
            
            // Log success
            $query_log = "INSERT INTO log_absensi_iot (rfid_uid, fingerprint_id, device_id, device_ip, action, status, message) 
                          VALUES (" . ($rfid_uid ? "'$rfid_uid'" : "NULL") . ", " . ($fingerprint_id ? $fingerprint_id : "NULL") . ", '$device_id', '$device_ip', 'pulang', 'success', 'Absen pulang berhasil: $nama_pegawai')";
            mysqli_query($conn, $query_log);
            
            jsonResponse(true, "Absen PULANG berhasil! Hati-hati di jalan, $nama_pegawai", [
                'nama' => $nama_pegawai,
                'nip' => $pegawai['nip'],
                'action' => 'PULANG',
                'jam_masuk' => $absensi['jam_masuk'],
                'jam_pulang' => $waktu,
                'durasi_kerja' => $durasi . ' jam'
            ]);
        } else {
            jsonResponse(false, 'Gagal menyimpan data absensi pulang');
        }
    }
}
?>
