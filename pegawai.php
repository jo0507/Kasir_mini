<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: index.php');
    exit;
}

$message = '';

// Proses Tambah Pegawai
if (isset($_POST['tambah'])) {
    $user_id = intval($_POST['user_id']);
    $nip = clean($_POST['nip']);
    $rfid_uid = clean($_POST['rfid_uid']);
    $fingerprint_id = !empty($_POST['fingerprint_id']) ? intval($_POST['fingerprint_id']) : NULL;
    $jabatan = clean($_POST['jabatan']);
    $gaji_pokok = floatval($_POST['gaji_pokok']);
    $tanggal_bergabung = clean($_POST['tanggal_bergabung']);
    
    // Cek NIP duplikat
    $cek = mysqli_query($conn, "SELECT * FROM pegawai WHERE nip = '$nip'");
    if (mysqli_num_rows($cek) > 0) {
        $message = "<div class='alert alert-danger'>NIP sudah digunakan!</div>";
    } else {
        $fp_query = $fingerprint_id ? ", fingerprint_id = $fingerprint_id" : "";
        $query = "INSERT INTO pegawai (user_id, nip, rfid_uid, fingerprint_id, jabatan, gaji_pokok, tanggal_bergabung) 
                  VALUES ($user_id, '$nip', '$rfid_uid', " . ($fingerprint_id ?: "NULL") . ", '$jabatan', $gaji_pokok, '$tanggal_bergabung')";
        
        if (mysqli_query($conn, $query)) {
            $message = "<div class='alert alert-success'>Data pegawai berhasil ditambahkan!</div>";
        }
    }
}

// Proses Edit Pegawai
if (isset($_POST['edit'])) {
    $id = intval($_POST['id']);
    $nip = clean($_POST['nip']);
    $rfid_uid = clean($_POST['rfid_uid']);
    $fingerprint_id = !empty($_POST['fingerprint_id']) ? intval($_POST['fingerprint_id']) : NULL;
    $jabatan = clean($_POST['jabatan']);
    $gaji_pokok = floatval($_POST['gaji_pokok']);
    $tanggal_bergabung = clean($_POST['tanggal_bergabung']);
    
    $query = "UPDATE pegawai SET 
              nip = '$nip',
              rfid_uid = '$rfid_uid',
              fingerprint_id = " . ($fingerprint_id ?: "NULL") . ",
              jabatan = '$jabatan',
              gaji_pokok = $gaji_pokok,
              tanggal_bergabung = '$tanggal_bergabung'
              WHERE id = $id";
    
    if (mysqli_query($conn, $query)) {
        $message = "<div class='alert alert-success'>Data pegawai berhasil diupdate!</div>";
    }
}

// Ambil data pegawai
$query = "SELECT p.*, u.username, u.nama_lengkap, u.role, u.status as user_status 
          FROM pegawai p 
          JOIN users u ON p.user_id = u.id 
          WHERE p.status_kerja = 'aktif'
          ORDER BY p.id DESC";
$result = mysqli_query($conn, $query);

// Ambil user yang belum jadi pegawai
$query_users = "SELECT u.* FROM users u 
                LEFT JOIN pegawai p ON u.id = p.user_id 
                WHERE p.id IS NULL AND u.status = 'aktif'";
$result_users = mysqli_query($conn, $query_users);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Pegawai - Sistem Kasir</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <?php include_once 'speed-insights.php'; ?>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>🏪 Kasir Mini</h2>
            <p><?php echo $_SESSION['nama_lengkap']; ?></p>
        </div>
        <nav class="sidebar-menu">
            <a href="index.php">📊 Dashboard</a>
            <a href="kasir.php">💰 Kasir</a>
            <a href="produk.php">📦 Produk</a>
            <a href="diskon.php">🎁 Diskon</a>
            <a href="transaksi.php">📋 Transaksi</a>
            <a href="tutup_kasir.php">🔒 Tutup Kasir</a>
            <a href="laporan.php">📈 Laporan</a>
            <hr style="border-color: rgba(255,255,255,0.1);">
            <a href="pegawai.php" class="active">👥 Pegawai</a>
            <a href="absensi.php">📝 Absensi</a>
            <a href="setting_gaji.php">⚙️ Setting Gaji</a>
            <a href="penggajian.php">💵 Penggajian</a>
            <hr style="border-color: rgba(255,255,255,0.1);">
            <a href="user.php">👤 User</a>
            <a href="logout.php" style="color: #e74c3c;">🚪 Logout</a>
        </nav>
    </div>
    
    <div class="main-content">
        <div class="top-bar">
            <h1>👥 Manajemen Pegawai</h1>
            <div class="top-bar-right">
                <span><?php echo tanggal_indonesia(date('Y-m-d')); ?></span>
            </div>
        </div>
        
        <div class="content">
            <?php echo $message; ?>
            
            <div class="card">
                <div class="card-header">
                    <h3>Tambah Pegawai Baru</h3>
                </div>
                
                <form method="POST">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Pilih User *</label>
                            <select name="user_id" class="form-control" required>
                                <option value="">-- Pilih User --</option>
                                <?php 
                                mysqli_data_seek($result_users, 0);
                                while ($user = mysqli_fetch_assoc($result_users)): 
                                ?>
                                <option value="<?php echo $user['id']; ?>">
                                    <?php echo $user['nama_lengkap']; ?> (<?php echo $user['username']; ?>)
                                </option>
                                <?php endwhile; ?>
                            </select>
                            <small style="color: #666;">User yang sudah menjadi pegawai tidak akan muncul</small>
                        </div>
                        
                        <div class="form-group">
                            <label>NIP *</label>
                            <input type="text" name="nip" class="form-control" required placeholder="Contoh: NIP001">
                        </div>
                        
                        <div class="form-group">
                            <label>RFID UID *</label>
                            <input type="text" name="rfid_uid" class="form-control" required placeholder="Contoh: A1B2C3D4">
                            <small style="color: #666;">UID kartu RFID untuk absensi</small>
                        </div>
                        
                        <div class="form-group">
                            <label>Fingerprint ID (Opsional)</label>
                            <input type="number" name="fingerprint_id" class="form-control" placeholder="1-127">
                            <small style="color: #666;">ID sidik jari (jika ada)</small>
                        </div>
                        
                        <div class="form-group">
                            <label>Jabatan *</label>
                            <input type="text" name="jabatan" class="form-control" required placeholder="Contoh: Kasir">
                        </div>
                        
                        <div class="form-group">
                            <label>Gaji Pokok *</label>
                            <input type="number" name="gaji_pokok" class="form-control" step="1000" required placeholder="3000000">
                        </div>
                        
                        <div class="form-group">
                            <label>Tanggal Bergabung *</label>
                            <input type="date" name="tanggal_bergabung" class="form-control" required value="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>
                    
                    <button type="submit" name="tambah" class="btn btn-primary">➕ Tambah Pegawai</button>
                </form>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3>Daftar Pegawai</h3>
                </div>
                
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>NIP</th>
                                <th>Nama</th>
                                <th>Jabatan</th>
                                <th>RFID UID</th>
                                <th>Fingerprint</th>
                                <th>Gaji Pokok</th>
                                <th>Bergabung</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            mysqli_data_seek($result, 0);
                            while ($row = mysqli_fetch_assoc($result)): 
                            ?>
                            <tr>
                                <td><strong><?php echo $row['nip']; ?></strong></td>
                                <td><?php echo $row['nama_lengkap']; ?></td>
                                <td><?php echo $row['jabatan']; ?></td>
                                <td><code><?php echo $row['rfid_uid']; ?></code></td>
                                <td><?php echo $row['fingerprint_id'] ? '#' . $row['fingerprint_id'] : '-'; ?></td>
                                <td><?php echo rupiah($row['gaji_pokok']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($row['tanggal_bergabung'])); ?></td>
                                <td>
                                    <button onclick="editPegawai(<?php echo htmlspecialchars(json_encode($row)); ?>)" class="btn btn-primary btn-sm">✏️ Edit</button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Edit Pegawai -->
    <div id="modalEdit" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; overflow-y: auto;">
        <div style="background: white; width: 90%; max-width: 800px; margin: 50px auto; padding: 30px; border-radius: 10px;">
            <h3 style="margin-bottom: 20px;">Edit Data Pegawai</h3>
            <form method="POST">
                <input type="hidden" name="id" id="edit_id">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>NIP</label>
                        <input type="text" name="nip" id="edit_nip" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label>RFID UID</label>
                        <input type="text" name="rfid_uid" id="edit_rfid" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Fingerprint ID</label>
                        <input type="number" name="fingerprint_id" id="edit_fingerprint" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label>Jabatan</label>
                        <input type="text" name="jabatan" id="edit_jabatan" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Gaji Pokok</label>
                        <input type="number" name="gaji_pokok" id="edit_gaji" class="form-control" step="1000" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Tanggal Bergabung</label>
                        <input type="date" name="tanggal_bergabung" id="edit_tanggal" class="form-control" required>
                    </div>
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" name="edit" class="btn btn-primary">💾 Simpan</button>
                    <button type="button" onclick="closeModal()" class="btn btn-danger">❌ Batal</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function editPegawai(data) {
            document.getElementById('edit_id').value = data.id;
            document.getElementById('edit_nip').value = data.nip;
            document.getElementById('edit_rfid').value = data.rfid_uid;
            document.getElementById('edit_fingerprint').value = data.fingerprint_id || '';
            document.getElementById('edit_jabatan').value = data.jabatan;
            document.getElementById('edit_gaji').value = data.gaji_pokok;
            document.getElementById('edit_tanggal').value = data.tanggal_bergabung;
            document.getElementById('modalEdit').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('modalEdit').style.display = 'none';
        }
    </script>
</body>
</html>
