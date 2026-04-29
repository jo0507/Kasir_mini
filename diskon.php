<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$message = '';

// Proses Tambah Diskon
if (isset($_POST['tambah'])) {
    $minimal_belanja = floatval($_POST['minimal_belanja']);
    $persentase = floatval($_POST['persentase_diskon']);
    $keterangan = clean($_POST['keterangan']);
    
    $query = "INSERT INTO diskon_belanja (minimal_belanja, persentase_diskon, keterangan) 
              VALUES ($minimal_belanja, $persentase, '$keterangan')";
    
    if (mysqli_query($conn, $query)) {
        $message = "<div class='alert alert-success'>Diskon berhasil ditambahkan!</div>";
    }
}

// Proses Edit Diskon
if (isset($_POST['edit'])) {
    $id = intval($_POST['id']);
    $minimal_belanja = floatval($_POST['minimal_belanja']);
    $persentase = floatval($_POST['persentase_diskon']);
    $keterangan = clean($_POST['keterangan']);
    
    $query = "UPDATE diskon_belanja SET 
              minimal_belanja = $minimal_belanja,
              persentase_diskon = $persentase,
              keterangan = '$keterangan'
              WHERE id = $id";
    
    if (mysqli_query($conn, $query)) {
        $message = "<div class='alert alert-success'>Diskon berhasil diupdate!</div>";
    }
}

// Proses Toggle Status
if (isset($_GET['toggle'])) {
    $id = intval($_GET['toggle']);
    $query = "UPDATE diskon_belanja SET status = IF(status='aktif', 'nonaktif', 'aktif') WHERE id = $id";
    mysqli_query($conn, $query);
    header('Location: diskon.php');
    exit;
}

// Proses Hapus Diskon
if (isset($_GET['hapus'])) {
    $id = intval($_GET['hapus']);
    $query = "DELETE FROM diskon_belanja WHERE id = $id";
    
    if (mysqli_query($conn, $query)) {
        $message = "<div class='alert alert-success'>Diskon berhasil dihapus!</div>";
    }
}

// Ambil data diskon
$query = "SELECT * FROM diskon_belanja ORDER BY minimal_belanja ASC";
$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Diskon - Sistem Kasir</title>
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
            <a href="diskon.php" class="active">🎁 Diskon</a>
            <a href="transaksi.php">📋 Transaksi</a>
            <a href="tutup_kasir.php">🔒 Tutup Kasir</a>
            <?php if ($_SESSION['role'] == 'admin'): ?>
            <a href="laporan.php">📈 Laporan</a>
            <hr style="border-color: rgba(255,255,255,0.1); margin: 10px 0;">
            <a href="pegawai.php">👥 Pegawai</a>
            <?php endif; ?>
            <a href="absensi.php">📝 Absensi</a>
            <?php if ($_SESSION['role'] == 'admin'): ?>
            <a href="setting_gaji.php">⚙️ Setting Gaji</a>
            <?php endif; ?>
            <a href="penggajian.php">💵 <?php echo $_SESSION['role'] == 'admin' ? 'Penggajian' : 'Slip Gaji'; ?></a>
            <?php if ($_SESSION['role'] == 'admin'): ?>
            <hr style="border-color: rgba(255,255,255,0.1); margin: 10px 0;">
            <a href="user.php">👤 User</a>
            <?php endif; ?>
            <a href="logout.php" style="color: #e74c3c;">🚪 Logout</a>
        </nav>
    </div>
    
    <div class="main-content">
        <div class="top-bar">
            <h1>🎁 Manajemen Diskon</h1>
            <div class="top-bar-right">
                <span><?php echo tanggal_indonesia(date('Y-m-d')); ?></span>
            </div>
        </div>
        
        <div class="content">
            <?php echo $message; ?>
            
            <div class="alert alert-info">
                <strong>ℹ️ Informasi:</strong> Diskon diberikan berdasarkan minimal total belanja. Sistem akan otomatis memilih diskon terbesar yang memenuhi syarat.
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3>Tambah Diskon Baru</h3>
                </div>
                
                <form method="POST">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Minimal Belanja *</label>
                            <input type="number" name="minimal_belanja" class="form-control" step="1000" placeholder="Contoh: 50000" required>
                            <small style="color: #666;">Minimal total belanja untuk mendapat diskon</small>
                        </div>
                        
                        <div class="form-group">
                            <label>Persentase Diskon (%) *</label>
                            <input type="number" name="persentase_diskon" class="form-control" step="0.1" min="0" max="100" placeholder="Contoh: 5" required>
                            <small style="color: #666;">Persentase diskon yang diberikan</small>
                        </div>
                        
                        <div class="form-group">
                            <label>Keterangan</label>
                            <input type="text" name="keterangan" class="form-control" placeholder="Contoh: Diskon 5% untuk belanja min 50rb">
                        </div>
                    </div>
                    
                    <button type="submit" name="tambah" class="btn btn-primary">➕ Tambah Diskon</button>
                </form>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3>Daftar Diskon</h3>
                </div>
                
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Minimal Belanja</th>
                                <th>Diskon</th>
                                <th>Keterangan</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            while ($row = mysqli_fetch_assoc($result)): 
                            ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><strong><?php echo rupiah($row['minimal_belanja']); ?></strong></td>
                                <td>
                                    <span class="badge badge-green"><?php echo $row['persentase_diskon']; ?>%</span>
                                </td>
                                <td><?php echo $row['keterangan']; ?></td>
                                <td>
                                    <?php if ($row['status'] == 'aktif'): ?>
                                        <span class="badge badge-green">Aktif</span>
                                    <?php else: ?>
                                        <span class="badge badge-red">Nonaktif</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="?toggle=<?php echo $row['id']; ?>" class="btn btn-warning btn-sm">
                                        <?php echo $row['status'] == 'aktif' ? '⏸️ Nonaktifkan' : '▶️ Aktifkan'; ?>
                                    </a>
                                    <button onclick="editDiskon(<?php echo htmlspecialchars(json_encode($row)); ?>)" class="btn btn-primary btn-sm">✏️ Edit</button>
                                    <a href="?hapus=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin hapus diskon ini?')">🗑️</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3>Simulasi Diskon</h3>
                </div>
                
                <div class="form-group">
                    <label>Total Belanja</label>
                    <input type="number" id="simulasi_belanja" class="form-control" step="1000" placeholder="Masukkan total belanja...">
                </div>
                
                <div id="hasil_simulasi" style="margin-top: 20px; padding: 20px; background: #f8f9fa; border-radius: 5px; display: none;">
                    <h4>Hasil Simulasi:</h4>
                    <div id="hasil_content"></div>
                </div>
                
                <script>
                    document.getElementById('simulasi_belanja').addEventListener('input', function() {
                        const belanja = parseFloat(this.value) || 0;
                        const hasilDiv = document.getElementById('hasil_simulasi');
                        const hasilContent = document.getElementById('hasil_content');
                        
                        if (belanja > 0) {
                            <?php
                            mysqli_data_seek($result, 0);
                            $diskon_array = array();
                            while ($d = mysqli_fetch_assoc($result)) {
                                if ($d['status'] == 'aktif') {
                                    $diskon_array[] = $d;
                                }
                            }
                            echo "const diskonList = " . json_encode($diskon_array) . ";";
                            ?>
                            
                            let diskonTerpilih = null;
                            for (let diskon of diskonList) {
                                if (belanja >= parseFloat(diskon.minimal_belanja)) {
                                    if (!diskonTerpilih || parseFloat(diskon.persentase_diskon) > parseFloat(diskonTerpilih.persentase_diskon)) {
                                        diskonTerpilih = diskon;
                                    }
                                }
                            }
                            
                            let html = '<p><strong>Total Belanja:</strong> Rp ' + belanja.toLocaleString('id-ID') + '</p>';
                            
                            if (diskonTerpilih) {
                                const diskonNominal = (belanja * parseFloat(diskonTerpilih.persentase_diskon)) / 100;
                                const totalBayar = belanja - diskonNominal;
                                
                                html += '<p style="color: #e74c3c;"><strong>Diskon:</strong> ' + diskonTerpilih.persentase_diskon + '% = Rp ' + diskonNominal.toLocaleString('id-ID') + '</p>';
                                html += '<p style="color: #667eea; font-size: 20px;"><strong>Total Bayar:</strong> Rp ' + totalBayar.toLocaleString('id-ID') + '</p>';
                                html += '<p><em>' + diskonTerpilih.keterangan + '</em></p>';
                            } else {
                                html += '<p style="color: #7f8c8d;">Tidak ada diskon yang berlaku</p>';
                                html += '<p style="color: #667eea; font-size: 20px;"><strong>Total Bayar:</strong> Rp ' + belanja.toLocaleString('id-ID') + '</p>';
                            }
                            
                            hasilContent.innerHTML = html;
                            hasilDiv.style.display = 'block';
                        } else {
                            hasilDiv.style.display = 'none';
                        }
                    });
                </script>
            </div>
        </div>
    </div>
    
    <!-- Modal Edit Diskon -->
    <div id="modalEdit" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div style="background: white; width: 90%; max-width: 600px; margin: 50px auto; padding: 30px; border-radius: 10px;">
            <h3 style="margin-bottom: 20px;">Edit Diskon</h3>
            <form method="POST">
                <input type="hidden" name="id" id="edit_id">
                
                <div class="form-group">
                    <label>Minimal Belanja</label>
                    <input type="number" name="minimal_belanja" id="edit_minimal" class="form-control" step="1000" required>
                </div>
                
                <div class="form-group">
                    <label>Persentase Diskon (%)</label>
                    <input type="number" name="persentase_diskon" id="edit_persentase" class="form-control" step="0.1" min="0" max="100" required>
                </div>
                
                <div class="form-group">
                    <label>Keterangan</label>
                    <input type="text" name="keterangan" id="edit_keterangan" class="form-control">
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" name="edit" class="btn btn-primary">💾 Simpan</button>
                    <button type="button" onclick="closeModal()" class="btn btn-danger">❌ Batal</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function editDiskon(data) {
            document.getElementById('edit_id').value = data.id;
            document.getElementById('edit_minimal').value = data.minimal_belanja;
            document.getElementById('edit_persentase').value = data.persentase_diskon;
            document.getElementById('edit_keterangan').value = data.keterangan;
            document.getElementById('modalEdit').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('modalEdit').style.display = 'none';
        }
    </script>
</body>
</html>
