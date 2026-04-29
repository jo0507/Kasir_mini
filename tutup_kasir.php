<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$message = '';
$show_form = true;

// Cek apakah sudah tutup kasir hari ini
$cek_tutup = mysqli_query($conn, "SELECT * FROM tutup_kasir WHERE DATE(waktu_tutup) = CURDATE() AND user_id = {$_SESSION['user_id']}");
if (mysqli_num_rows($cek_tutup) > 0) {
    $show_form = false;
    $data_tutup = mysqli_fetch_assoc($cek_tutup);
}

// Hitung penjualan hari ini
$query_cash = "SELECT COALESCE(SUM(total_bayar), 0) as total FROM transaksi WHERE DATE(tanggal) = CURDATE() AND metode_pembayaran = 'cash'";
$total_cash = mysqli_fetch_assoc(mysqli_query($conn, $query_cash))['total'];

$query_qris = "SELECT COALESCE(SUM(total_bayar), 0) as total FROM transaksi WHERE DATE(tanggal) = CURDATE() AND metode_pembayaran = 'qris'";
$total_qris = mysqli_fetch_assoc(mysqli_query($conn, $query_qris))['total'];

$query_transfer = "SELECT COALESCE(SUM(total_bayar), 0) as total FROM transaksi WHERE DATE(tanggal) = CURDATE() AND metode_pembayaran = 'transfer'";
$total_transfer = mysqli_fetch_assoc(mysqli_query($conn, $query_transfer))['total'];

$total_transaksi = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM transaksi WHERE DATE(tanggal) = CURDATE()"))['total'];

$total_semua = $total_cash + $total_qris + $total_transfer;

// Proses tutup kasir
if (isset($_POST['tutup_kasir'])) {
    $uang_fisik = floatval($_POST['uang_fisik']);
    $selisih = $uang_fisik - $total_cash;
    $keterangan = clean($_POST['keterangan']);
    
    $query = "INSERT INTO tutup_kasir (tanggal, user_id, total_transaksi, total_penjualan_cash, total_penjualan_qris, total_penjualan_transfer, total_penjualan_semua, uang_fisik, selisih, keterangan) 
              VALUES (CURDATE(), {$_SESSION['user_id']}, $total_transaksi, $total_cash, $total_qris, $total_transfer, $total_semua, $uang_fisik, $selisih, '$keterangan')";
    
    if (mysqli_query($conn, $query)) {
        $message = "<div class='alert alert-success'>✅ Kasir berhasil ditutup! Selisih: " . rupiah($selisih) . "</div>";
        $show_form = false;
        
        // Reload data
        $cek_tutup = mysqli_query($conn, "SELECT * FROM tutup_kasir WHERE DATE(waktu_tutup) = CURDATE() AND user_id = {$_SESSION['user_id']}");
        $data_tutup = mysqli_fetch_assoc($cek_tutup);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tutup Kasir - Sistem Kasir</title>
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
            <a href="tutup_kasir.php" class="active">🔒 Tutup Kasir</a>
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
            <h1>🔒 Tutup Kasir - Rekonsiliasi Uang</h1>
            <div class="top-bar-right">
                <span><?php echo tanggal_indonesia(date('Y-m-d')); ?></span>
                <span id="jam"></span>
            </div>
        </div>
        
        <div class="content">
            <?php echo $message; ?>
            
            <?php if (!$show_form): ?>
                <div class="alert alert-info">
                    ℹ️ Kasir untuk hari ini sudah ditutup. Anda dapat melihat riwayat tutup kasir di bawah.
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3>Rekap Tutup Kasir Hari Ini</h3>
                    </div>
                    
                    <div style="padding: 20px;">
                        <div class="stats-grid">
                            <div class="stat-card blue">
                                <div class="stat-icon">💳</div>
                                <div class="stat-details">
                                    <h3><?php echo $data_tutup['total_transaksi']; ?></h3>
                                    <p>Total Transaksi</p>
                                </div>
                            </div>
                            
                            <div class="stat-card green">
                                <div class="stat-icon">💰</div>
                                <div class="stat-details">
                                    <h3><?php echo rupiah($data_tutup['total_penjualan_semua']); ?></h3>
                                    <p>Total Penjualan</p>
                                </div>
                            </div>
                        </div>
                        
                        <table class="data-table" style="margin-top: 20px;">
                            <tr>
                                <td><strong>Penjualan Cash:</strong></td>
                                <td><?php echo rupiah($data_tutup['total_penjualan_cash']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Penjualan QRIS:</strong></td>
                                <td><?php echo rupiah($data_tutup['total_penjualan_qris']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Penjualan Transfer:</strong></td>
                                <td><?php echo rupiah($data_tutup['total_penjualan_transfer']); ?></td>
                            </tr>
                            <tr style="background: #f8f9fa;">
                                <td><strong>Uang Fisik di Laci:</strong></td>
                                <td><strong><?php echo rupiah($data_tutup['uang_fisik']); ?></strong></td>
                            </tr>
                            <tr style="background: <?php echo $data_tutup['selisih'] >= 0 ? '#d4edda' : '#f8d7da'; ?>;">
                                <td><strong>Selisih:</strong></td>
                                <td>
                                    <strong style="color: <?php echo $data_tutup['selisih'] >= 0 ? '#155724' : '#721c24'; ?>;">
                                        <?php echo rupiah($data_tutup['selisih']); ?>
                                        <?php if ($data_tutup['selisih'] > 0): ?>
                                            (Uang Lebih)
                                        <?php elseif ($data_tutup['selisih'] < 0): ?>
                                            (Uang Kurang)
                                        <?php else: ?>
                                            (Pas)
                                        <?php endif; ?>
                                    </strong>
                                </td>
                            </tr>
                            <?php if ($data_tutup['keterangan']): ?>
                            <tr>
                                <td><strong>Keterangan:</strong></td>
                                <td><?php echo $data_tutup['keterangan']; ?></td>
                            </tr>
                            <?php endif; ?>
                            <tr>
                                <td><strong>Waktu Tutup:</strong></td>
                                <td><?php echo date('d/m/Y H:i:s', strtotime($data_tutup['waktu_tutup'])); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="card-header">
                        <h3>Rekap Penjualan Hari Ini</h3>
                    </div>
                    
                    <div class="stats-grid">
                        <div class="stat-card blue">
                            <div class="stat-icon">💳</div>
                            <div class="stat-details">
                                <h3><?php echo $total_transaksi; ?></h3>
                                <p>Total Transaksi</p>
                            </div>
                        </div>
                        
                        <div class="stat-card green">
                            <div class="stat-icon">💵</div>
                            <div class="stat-details">
                                <h3><?php echo rupiah($total_cash); ?></h3>
                                <p>Penjualan Cash</p>
                            </div>
                        </div>
                        
                        <div class="stat-card orange">
                            <div class="stat-icon">📱</div>
                            <div class="stat-details">
                                <h3><?php echo rupiah($total_qris); ?></h3>
                                <p>Penjualan QRIS</p>
                            </div>
                        </div>
                        
                        <div class="stat-card red">
                            <div class="stat-icon">🏦</div>
                            <div class="stat-details">
                                <h3><?php echo rupiah($total_transfer); ?></h3>
                                <p>Penjualan Transfer</p>
                            </div>
                        </div>
                    </div>
                    
                    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 10px; text-align: center; margin: 20px;">
                        <h2 style="font-size: 18px; margin-bottom: 10px;">TOTAL PENJUALAN HARI INI</h2>
                        <h1 style="font-size: 42px; font-weight: 700;"><?php echo rupiah($total_semua); ?></h1>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3>Rekonsiliasi Uang Fisik</h3>
                    </div>
                    
                    <div class="alert alert-info">
                        <strong>📌 Catatan Penting:</strong><br>
                        • Hitung uang fisik (cash) yang ada di laci kasir<br>
                        • Sistem akan membandingkan dengan total penjualan cash dari sistem<br>
                        • Selisih akan dihitung otomatis (Plus = Uang lebih, Minus = Uang kurang)
                    </div>
                    
                    <form method="POST">
                        <div class="form-group">
                            <label><strong>Total Penjualan Cash (Sistem):</strong></label>
                            <input type="text" class="form-control" value="<?php echo rupiah($total_cash); ?>" readonly style="background: #f8f9fa; font-weight: 700; font-size: 18px; color: #667eea;">
                        </div>
                        
                        <div class="form-group">
                            <label><strong>Uang Fisik di Laci (Hitung Manual): *</strong></label>
                            <input type="number" name="uang_fisik" class="form-control" step="1000" required id="uang_fisik" style="font-size: 18px; font-weight: 700;">
                            <small style="color: #666;">Masukkan jumlah uang cash yang ada di laci kasir</small>
                        </div>
                        
                        <div id="selisih_preview" style="display: none; padding: 20px; border-radius: 5px; margin: 20px 0;">
                            <h4>Selisih:</h4>
                            <h2 id="selisih_text" style="font-size: 32px; margin-top: 10px;"></h2>
                        </div>
                        
                        <div class="form-group">
                            <label>Keterangan (Opsional):</label>
                            <textarea name="keterangan" class="form-control" rows="3" placeholder="Catatan tambahan jika ada selisih..."></textarea>
                        </div>
                        
                        <button type="submit" name="tutup_kasir" class="btn btn-primary" style="font-size: 18px; padding: 15px 40px;" onclick="return confirm('Yakin ingin menutup kasir? Proses ini tidak dapat dibatalkan!')">
                            🔒 TUTUP KASIR
                        </button>
                    </form>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <h3>Riwayat Tutup Kasir (7 Hari Terakhir)</h3>
                </div>
                
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Kasir</th>
                                <th>Total Transaksi</th>
                                <th>Total Penjualan</th>
                                <th>Uang Fisik</th>
                                <th>Selisih</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $query_riwayat = "SELECT tk.*, u.nama_lengkap as kasir 
                                            FROM tutup_kasir tk 
                                            LEFT JOIN users u ON tk.user_id = u.id 
                                            WHERE tk.tanggal >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                                            ORDER BY tk.id DESC";
                            $riwayat = mysqli_query($conn, $query_riwayat);
                            while ($row = mysqli_fetch_assoc($riwayat)):
                            ?>
                            <tr>
                                <td><?php echo tanggal_indonesia($row['tanggal']); ?></td>
                                <td><?php echo $row['kasir']; ?></td>
                                <td><?php echo $row['total_transaksi']; ?> transaksi</td>
                                <td><?php echo rupiah($row['total_penjualan_semua']); ?></td>
                                <td><?php echo rupiah($row['uang_fisik']); ?></td>
                                <td>
                                    <span class="badge <?php echo $row['selisih'] >= 0 ? 'badge-green' : 'badge-red'; ?>">
                                        <?php echo rupiah($row['selisih']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Update jam
        function updateJam() {
            const now = new Date();
            const jam = String(now.getHours()).padStart(2, '0');
            const menit = String(now.getMinutes()).padStart(2, '0');
            const detik = String(now.getSeconds()).padStart(2, '0');
            if (document.getElementById('jam')) {
                document.getElementById('jam').textContent = `${jam}:${menit}:${detik}`;
            }
        }
        setInterval(updateJam, 1000);
        updateJam();
        
        // Hitung selisih otomatis
        const uangFisikInput = document.getElementById('uang_fisik');
        if (uangFisikInput) {
            uangFisikInput.addEventListener('input', function() {
                const totalCash = <?php echo $total_cash; ?>;
                const uangFisik = parseFloat(this.value) || 0;
                const selisih = uangFisik - totalCash;
                
                const previewDiv = document.getElementById('selisih_preview');
                const selisihText = document.getElementById('selisih_text');
                
                if (uangFisik > 0) {
                    let statusText = '';
                    let color = '';
                    
                    if (selisih > 0) {
                        statusText = '(Uang Lebih)';
                        color = '#155724';
                        previewDiv.style.background = '#d4edda';
                    } else if (selisih < 0) {
                        statusText = '(Uang Kurang)';
                        color = '#721c24';
                        previewDiv.style.background = '#f8d7da';
                    } else {
                        statusText = '(Pas / Sesuai)';
                        color = '#004085';
                        previewDiv.style.background = '#d1ecf1';
                    }
                    
                    selisihText.innerHTML = 'Rp ' + selisih.toLocaleString('id-ID') + ' <span style="font-size: 18px;">' + statusText + '</span>';
                    selisihText.style.color = color;
                    previewDiv.style.display = 'block';
                } else {
                    previewDiv.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>
