<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: index.php');
    exit;
}

$message = '';

// POTONGAN GAJI
if (isset($_POST['tambah_potongan'])) {
    $nama = clean($_POST['nama_potongan']);
    $jenis = clean($_POST['jenis_ketidakhadiran']);
    $nominal = floatval($_POST['nominal_potongan']);
    $keterangan = clean($_POST['keterangan']);
    
    $query = "INSERT INTO setting_potongan (nama_potongan, jenis_ketidakhadiran, nominal_potongan, keterangan) 
              VALUES ('$nama', '$jenis', $nominal, '$keterangan')";
    
    if (mysqli_query($conn, $query)) {
        $message = "<div class='alert alert-success'>Setting potongan berhasil ditambahkan!</div>";
    }
}

if (isset($_POST['edit_potongan'])) {
    $id = intval($_POST['id']);
    $nama = clean($_POST['nama_potongan']);
    $jenis = clean($_POST['jenis_ketidakhadiran']);
    $nominal = floatval($_POST['nominal_potongan']);
    $keterangan = clean($_POST['keterangan']);
    
    $query = "UPDATE setting_potongan SET 
              nama_potongan = '$nama',
              jenis_ketidakhadiran = '$jenis',
              nominal_potongan = $nominal,
              keterangan = '$keterangan'
              WHERE id = $id";
    
    if (mysqli_query($conn, $query)) {
        $message = "<div class='alert alert-success'>Setting potongan berhasil diupdate!</div>";
    }
}

// BONUS PENJUALAN
if (isset($_POST['tambah_bonus'])) {
    $minimal = floatval($_POST['minimal_penjualan']);
    $nominal = floatval($_POST['nominal_bonus']);
    $keterangan = clean($_POST['keterangan']);
    
    $query = "INSERT INTO setting_bonus (minimal_penjualan, nominal_bonus, keterangan) 
              VALUES ($minimal, $nominal, '$keterangan')";
    
    if (mysqli_query($conn, $query)) {
        $message = "<div class='alert alert-success'>Setting bonus berhasil ditambahkan!</div>";
    }
}

if (isset($_POST['edit_bonus'])) {
    $id = intval($_POST['id']);
    $minimal = floatval($_POST['minimal_penjualan']);
    $nominal = floatval($_POST['nominal_bonus']);
    $keterangan = clean($_POST['keterangan']);
    
    $query = "UPDATE setting_bonus SET 
              minimal_penjualan = $minimal,
              nominal_bonus = $nominal,
              keterangan = '$keterangan'
              WHERE id = $id";
    
    if (mysqli_query($conn, $query)) {
        $message = "<div class='alert alert-success'>Setting bonus berhasil diupdate!</div>";
    }
}

// Toggle status
if (isset($_GET['toggle_potongan'])) {
    $id = intval($_GET['toggle_potongan']);
    mysqli_query($conn, "UPDATE setting_potongan SET status = IF(status='aktif', 'nonaktif', 'aktif') WHERE id = $id");
    header('Location: setting_gaji.php');
    exit;
}

if (isset($_GET['toggle_bonus'])) {
    $id = intval($_GET['toggle_bonus']);
    mysqli_query($conn, "UPDATE setting_bonus SET status = IF(status='aktif', 'nonaktif', 'aktif') WHERE id = $id");
    header('Location: setting_gaji.php');
    exit;
}

$potongan = mysqli_query($conn, "SELECT * FROM setting_potongan ORDER BY jenis_ketidakhadiran ASC");
$bonus = mysqli_query($conn, "SELECT * FROM setting_bonus ORDER BY minimal_penjualan ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setting Gaji - Sistem Kasir</title>
    <link rel="stylesheet" href="assets/css/style.css">
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
            <a href="pegawai.php">👥 Pegawai</a>
            <a href="absensi.php">📝 Absensi</a>
            <a href="setting_gaji.php" class="active">⚙️ Setting Gaji</a>
            <a href="penggajian.php">💵 Penggajian</a>
            <hr style="border-color: rgba(255,255,255,0.1);">
            <a href="user.php">👤 User</a>
            <a href="logout.php" style="color: #e74c3c;">🚪 Logout</a>
        </nav>
    </div>
    
    <div class="main-content">
        <div class="top-bar">
            <h1>⚙️ Setting Gaji & Bonus</h1>
        </div>
        
        <div class="content">
            <?php echo $message; ?>
            
            <!-- POTONGAN GAJI -->
            <div class="card">
                <div class="card-header">
                    <h3>➖ Setting Potongan Gaji</h3>
                </div>
                
                <div class="alert alert-info">
                    💡 <strong>Info:</strong> Potongan gaji akan dikalkulasi otomatis berdasarkan jumlah hari ALFA atau IZIN. SAKIT dengan surat tidak dikenakan potongan.
                </div>
                
                <form method="POST">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Nama Potongan *</label>
                            <input type="text" name="nama_potongan" class="form-control" required placeholder="Potongan Alfa">
                        </div>
                        
                        <div class="form-group">
                            <label>Jenis Ketidakhadiran *</label>
                            <select name="jenis_ketidakhadiran" class="form-control" required>
                                <option value="ALFA">ALFA (Tanpa Keterangan)</option>
                                <option value="IZIN">IZIN</option>
                                <option value="SAKIT">SAKIT</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Nominal Potongan (per hari) *</label>
                            <input type="number" name="nominal_potongan" class="form-control" step="1000" required placeholder="100000">
                        </div>
                        
                        <div class="form-group">
                            <label>Keterangan</label>
                            <input type="text" name="keterangan" class="form-control" placeholder="Potongan per hari">
                        </div>
                    </div>
                    
                    <button type="submit" name="tambah_potongan" class="btn btn-primary">➕ Tambah Potongan</button>
                </form>
                
                <div class="table-responsive" style="margin-top: 20px;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>Jenis</th>
                                <th>Nominal</th>
                                <th>Keterangan</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            mysqli_data_seek($potongan, 0);
                            while ($row = mysqli_fetch_assoc($potongan)): 
                            ?>
                            <tr>
                                <td><?php echo $row['nama_potongan']; ?></td>
                                <td><span class="badge badge-red"><?php echo $row['jenis_ketidakhadiran']; ?></span></td>
                                <td><strong><?php echo rupiah($row['nominal_potongan']); ?></strong></td>
                                <td><?php echo $row['keterangan']; ?></td>
                                <td>
                                    <span class="badge <?php echo $row['status'] == 'aktif' ? 'badge-green' : 'badge-red'; ?>">
                                        <?php echo strtoupper($row['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="?toggle_potongan=<?php echo $row['id']; ?>" class="btn btn-warning btn-sm">
                                        <?php echo $row['status'] == 'aktif' ? '⏸️' : '▶️'; ?>
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- BONUS PENJUALAN -->
            <div class="card">
                <div class="card-header">
                    <h3>➕ Setting Bonus Penjualan</h3>
                </div>
                
                <div class="alert alert-info">
                    💡 <strong>Info:</strong> Bonus penjualan dihitung otomatis berdasarkan total transaksi yang dikerjakan pegawai dalam 1 bulan. Sistem akan memilih bonus terbesar yang memenuhi syarat.
                </div>
                
                <form method="POST">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Minimal Penjualan *</label>
                            <input type="number" name="minimal_penjualan" class="form-control" step="1000000" required placeholder="5000000">
                        </div>
                        
                        <div class="form-group">
                            <label>Nominal Bonus *</label>
                            <input type="number" name="nominal_bonus" class="form-control" step="10000" required placeholder="200000">
                        </div>
                        
                        <div class="form-group">
                            <label>Keterangan</label>
                            <input type="text" name="keterangan" class="form-control" placeholder="Bonus untuk penjualan min 5 juta">
                        </div>
                    </div>
                    
                    <button type="submit" name="tambah_bonus" class="btn btn-success">➕ Tambah Bonus</button>
                </form>
                
                <div class="table-responsive" style="margin-top: 20px;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Minimal Penjualan</th>
                                <th>Nominal Bonus</th>
                                <th>Keterangan</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            mysqli_data_seek($bonus, 0);
                            while ($row = mysqli_fetch_assoc($bonus)): 
                            ?>
                            <tr>
                                <td><strong><?php echo rupiah($row['minimal_penjualan']); ?></strong></td>
                                <td><span class="badge badge-green"><?php echo rupiah($row['nominal_bonus']); ?></span></td>
                                <td><?php echo $row['keterangan']; ?></td>
                                <td>
                                    <span class="badge <?php echo $row['status'] == 'aktif' ? 'badge-green' : 'badge-red'; ?>">
                                        <?php echo strtoupper($row['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="?toggle_bonus=<?php echo $row['id']; ?>" class="btn btn-warning btn-sm">
                                        <?php echo $row['status'] == 'aktif' ? '⏸️' : '▶️'; ?>
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Simulasi Perhitungan -->
            <div class="card">
                <div class="card-header">
                    <h3>🧮 Simulasi Perhitungan Gaji</h3>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Gaji Pokok</label>
                        <input type="number" id="sim_gaji" class="form-control" value="3000000" step="100000">
                    </div>
                    
                    <div class="form-group">
                        <label>Jumlah Alfa</label>
                        <input type="number" id="sim_alfa" class="form-control" value="0" min="0">
                    </div>
                    
                    <div class="form-group">
                        <label>Jumlah Izin</label>
                        <input type="number" id="sim_izin" class="form-control" value="0" min="0">
                    </div>
                    
                    <div class="form-group">
                        <label>Total Penjualan</label>
                        <input type="number" id="sim_penjualan" class="form-control" value="0" step="100000">
                    </div>
                </div>
                
                <button onclick="simulasiGaji()" class="btn btn-primary">🧮 Hitung</button>
                
                <div id="hasil_simulasi" style="margin-top: 20px; padding: 20px; background: #f8f9fa; border-radius: 5px; display: none;"></div>
            </div>
        </div>
    </div>
    
    <script>
        function simulasiGaji() {
            const gajiPokok = parseFloat(document.getElementById('sim_gaji').value) || 0;
            const jumlahAlfa = parseInt(document.getElementById('sim_alfa').value) || 0;
            const jumlahIzin = parseInt(document.getElementById('sim_izin').value) || 0;
            const totalPenjualan = parseFloat(document.getElementById('sim_penjualan').value) || 0;
            
            // Get potongan aktif
            <?php
            mysqli_data_seek($potongan, 0);
            $potongan_alfa = 0;
            $potongan_izin = 0;
            while ($p = mysqli_fetch_assoc($potongan)) {
                if ($p['status'] == 'aktif') {
                    if ($p['jenis_ketidakhadiran'] == 'ALFA') {
                        $potongan_alfa = $p['nominal_potongan'];
                    } elseif ($p['jenis_ketidakhadiran'] == 'IZIN') {
                        $potongan_izin = $p['nominal_potongan'];
                    }
                }
            }
            echo "const potonganAlfa = $potongan_alfa;\n";
            echo "const potonganIzin = $potongan_izin;\n";
            
            // Get bonus aktif
            mysqli_data_seek($bonus, 0);
            echo "const bonusList = " . json_encode(mysqli_fetch_all(mysqli_query($conn, "SELECT * FROM setting_bonus WHERE status = 'aktif' ORDER BY minimal_penjualan DESC"), MYSQLI_ASSOC)) . ";\n";
            ?>
            
            // Hitung potongan
            const totalPotongan = (jumlahAlfa * potonganAlfa) + (jumlahIzin * potonganIzin);
            
            // Hitung bonus
            let bonusPenjualan = 0;
            for (let b of bonusList) {
                if (totalPenjualan >= parseFloat(b.minimal_penjualan)) {
                    bonusPenjualan = parseFloat(b.nominal_bonus);
                    break;
                }
            }
            
            // Gaji bersih
            const gajiBersih = gajiPokok - totalPotongan + bonusPenjualan;
            
            // Tampilkan hasil
            let html = '<h4>Hasil Perhitungan:</h4>';
            html += '<table class="data-table">';
            html += '<tr><td><strong>Gaji Pokok:</strong></td><td>' + formatRupiah(gajiPokok) + '</td></tr>';
            html += '<tr><td><strong>Potongan Alfa (' + jumlahAlfa + ' hari):</strong></td><td style="color: #e74c3c;">- ' + formatRupiah(jumlahAlfa * potonganAlfa) + '</td></tr>';
            html += '<tr><td><strong>Potongan Izin (' + jumlahIzin + ' hari):</strong></td><td style="color: #e74c3c;">- ' + formatRupiah(jumlahIzin * potonganIzin) + '</td></tr>';
            html += '<tr><td><strong>Bonus Penjualan:</strong></td><td style="color: #06beb6;">+ ' + formatRupiah(bonusPenjualan) + '</td></tr>';
            html += '<tr style="background: #667eea; color: white; font-size: 18px;"><td><strong>GAJI BERSIH:</strong></td><td><strong>' + formatRupiah(gajiBersih) + '</strong></td></tr>';
            html += '</table>';
            
            document.getElementById('hasil_simulasi').innerHTML = html;
            document.getElementById('hasil_simulasi').style.display = 'block';
        }
        
        function formatRupiah(angka) {
            return 'Rp ' + angka.toLocaleString('id-ID');
        }
    </script>
</body>
</html>
