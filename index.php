<?php
session_start();
require_once 'config.php';

// Cek login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Ambil data statistik
$total_produk = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM produk WHERE status='aktif'"))['total'];
$total_transaksi_hari_ini = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM transaksi WHERE DATE(tanggal) = CURDATE()"))['total'];
$total_penjualan_hari_ini = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(total_bayar), 0) as total FROM transaksi WHERE DATE(tanggal) = CURDATE()"))['total'];

$produk_stok_rendah = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM produk WHERE stok < 10 AND status='aktif'"))['total'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistem Kasir</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <?php include_once 'speed-insights.php'; ?>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>Kasir Mini</h2>
            <p><?php echo $_SESSION['nama_lengkap']; ?></p>
            <small><?php echo ucfirst($_SESSION['role']); ?></small>
        </div>
        
        <nav class="sidebar-menu">
            <a href="index.php" class="active">📊 Dashboard</a>
            <?php if ($_SESSION['role'] != 'pegawai'): ?>
            <a href="kasir.php">💰 Kasir</a>
            <a href="produk.php">📦 Produk</a>
            <a href="diskon.php">🎁 Diskon</a>
            <a href="transaksi.php">📋 Transaksi</a>
            <a href="tutup_kasir.php">🔒 Tutup Kasir</a>
            <?php endif; ?>
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
            <h1>Dashboard</h1>
            <div class="top-bar-right">
                <span><?php echo tanggal_indonesia(date('Y-m-d')); ?></span>
                <span id="jam"></span>
            </div>
        </div>
        
        <div class="content">
            <div class="stats-grid">
                <div class="stat-card blue">
                    <div class="stat-icon">📦</div>
                    <div class="stat-details">
                        <h3><?php echo $total_produk; ?></h3>
                        <p>Total Produk Aktif</p>
                    </div>
                </div>
                
                <div class="stat-card green">
                    <div class="stat-icon">💳</div>
                    <div class="stat-details">
                        <h3><?php echo $total_transaksi_hari_ini; ?></h3>
                        <p>Transaksi Hari Ini</p>
                    </div>
                </div>
                
                <div class="stat-card orange">
                    <div class="stat-icon">💰</div>
                    <div class="stat-details">
                        <h3><?php echo rupiah($total_penjualan_hari_ini); ?></h3>
                        <p>Penjualan Hari Ini</p>
                    </div>
                </div>
                
                <div class="stat-card red">
                    <div class="stat-icon">⚠️</div>
                    <div class="stat-details">
                        <h3><?php echo $produk_stok_rendah; ?></h3>
                        <p>Stok Rendah</p>
                    </div>
                </div>
            </div>
            
            <div class="dashboard-grid">
                <div class="dashboard-card">
                    <h3>Transaksi Terakhir</h3>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Kode</th>
                                    <th>Tanggal</th>
                                    <th>Total</th>
                                    <th>Metode</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $query = "SELECT * FROM transaksi ORDER BY id DESC LIMIT 10";
                                $result = mysqli_query($conn, $query);
                                while ($row = mysqli_fetch_assoc($result)):
                                ?>
                                <tr>
                                    <td><?php echo $row['kode_transaksi']; ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($row['tanggal'])); ?></td>
                                    <td><?php echo rupiah($row['total_bayar']); ?></td>
                                    <td><span class="badge badge-<?php echo $row['metode_pembayaran']; ?>"><?php echo strtoupper($row['metode_pembayaran']); ?></span></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="dashboard-card">
                    <h3>Produk Stok Rendah</h3>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Nama Produk</th>
                                    <th>Stok</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $query = "SELECT * FROM produk WHERE stok < 10 AND status='aktif' ORDER BY stok ASC LIMIT 10";
                                $result = mysqli_query($conn, $query);
                                while ($row = mysqli_fetch_assoc($result)):
                                ?>
                                <tr>
                                    <td><?php echo $row['nama_produk']; ?></td>
                                    <td><span class="badge badge-red"><?php echo $row['stok']; ?> <?php echo $row['satuan']; ?></span></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Update jam real-time
        function updateJam() {
            const now = new Date();
            const jam = String(now.getHours()).padStart(2, '0');
            const menit = String(now.getMinutes()).padStart(2, '0');
            const detik = String(now.getSeconds()).padStart(2, '0');
            document.getElementById('jam').textContent = `${jam}:${menit}:${detik}`;
        }
        
        setInterval(updateJam, 1000);
        updateJam();
    </script>
</body>
</html>
