<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: index.php');
    exit;
}

// Filter periode
$periode = isset($_GET['periode']) ? $_GET['periode'] : 'hari_ini';
$tanggal_dari = date('Y-m-d');
$tanggal_sampai = date('Y-m-d');

switch ($periode) {
    case 'hari_ini':
        $tanggal_dari = date('Y-m-d');
        $tanggal_sampai = date('Y-m-d');
        break;
    case 'minggu_ini':
        $tanggal_dari = date('Y-m-d', strtotime('monday this week'));
        $tanggal_sampai = date('Y-m-d');
        break;
    case 'bulan_ini':
        $tanggal_dari = date('Y-m-01');
        $tanggal_sampai = date('Y-m-d');
        break;
    case 'custom':
        $tanggal_dari = isset($_GET['dari']) ? $_GET['dari'] : date('Y-m-d');
        $tanggal_sampai = isset($_GET['sampai']) ? $_GET['sampai'] : date('Y-m-d');
        break;
}

// Query laporan
$where = "WHERE DATE(t.tanggal) BETWEEN '$tanggal_dari' AND '$tanggal_sampai'";

// Total penjualan
$query_total = "SELECT 
                COUNT(*) as total_transaksi,
                COALESCE(SUM(total_bayar), 0) as total_penjualan,
                COALESCE(SUM(diskon_nominal), 0) as total_diskon,
                COALESCE(SUM(CASE WHEN metode_pembayaran = 'cash' THEN total_bayar ELSE 0 END), 0) as penjualan_cash,
                COALESCE(SUM(CASE WHEN metode_pembayaran = 'qris' THEN total_bayar ELSE 0 END), 0) as penjualan_qris,
                COALESCE(SUM(CASE WHEN metode_pembayaran = 'transfer' THEN total_bayar ELSE 0 END), 0) as penjualan_transfer
                FROM transaksi t $where";
$total_data = mysqli_fetch_assoc(mysqli_query($conn, $query_total));

// Produk terlaris
$query_terlaris = "SELECT 
                   dt.nama_produk,
                   SUM(dt.qty) as total_terjual,
                   SUM(dt.subtotal) as total_pendapatan
                   FROM detail_transaksi dt
                   JOIN transaksi t ON dt.transaksi_id = t.id
                   $where
                   GROUP BY dt.produk_id, dt.nama_produk
                   ORDER BY total_terjual DESC
                   LIMIT 10";
$terlaris = mysqli_query($conn, $query_terlaris);

// Grafik penjualan per hari
$query_grafik = "SELECT 
                 DATE(t.tanggal) as tanggal,
                 COUNT(*) as jumlah_transaksi,
                 SUM(t.total_bayar) as total
                 FROM transaksi t
                 WHERE DATE(t.tanggal) BETWEEN '$tanggal_dari' AND '$tanggal_sampai'
                 GROUP BY DATE(t.tanggal)
                 ORDER BY tanggal ASC";
$grafik_data = mysqli_query($conn, $query_grafik);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan - Sistem Kasir</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <?php include_once 'speed-insights.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            <a href="laporan.php" class="active">📈 Laporan</a>
            <hr style="border-color: rgba(255,255,255,0.1); margin: 10px 0;">
            <a href="pegawai.php">👥 Pegawai</a>
            <a href="absensi.php">📝 Absensi</a>
            <a href="setting_gaji.php">⚙️ Setting Gaji</a>
            <a href="penggajian.php">💵 Penggajian</a>
            <hr style="border-color: rgba(255,255,255,0.1); margin: 10px 0;">
            <a href="user.php">👤 User</a>
            <a href="logout.php" style="color: #e74c3c;">🚪 Logout</a>
        </nav>
    </div>
    
    <div class="main-content">
        <div class="top-bar">
            <h1>📈 Laporan Penjualan</h1>
            <div class="top-bar-right">
                <span><?php echo tanggal_indonesia(date('Y-m-d')); ?></span>
            </div>
        </div>
        
        <div class="content">
            <div class="card">
                <div class="card-header">
                    <h3>Filter Periode</h3>
                </div>
                
                <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 20px;">
                    <a href="?periode=hari_ini" class="btn <?php echo $periode == 'hari_ini' ? 'btn-primary' : 'btn-warning'; ?>">Hari Ini</a>
                    <a href="?periode=minggu_ini" class="btn <?php echo $periode == 'minggu_ini' ? 'btn-primary' : 'btn-warning'; ?>">Minggu Ini</a>
                    <a href="?periode=bulan_ini" class="btn <?php echo $periode == 'bulan_ini' ? 'btn-primary' : 'btn-warning'; ?>">Bulan Ini</a>
                </div>
                
                <form method="GET" style="display: flex; gap: 10px; align-items: end;">
                    <input type="hidden" name="periode" value="custom">
                    <div class="form-group" style="margin: 0;">
                        <label>Dari Tanggal</label>
                        <input type="date" name="dari" class="form-control" value="<?php echo $tanggal_dari; ?>">
                    </div>
                    <div class="form-group" style="margin: 0;">
                        <label>Sampai Tanggal</label>
                        <input type="date" name="sampai" class="form-control" value="<?php echo $tanggal_sampai; ?>">
                    </div>
                    <button type="submit" class="btn btn-primary">🔍 Tampilkan</button>
                </form>
            </div>
            
            <div class="alert alert-info">
                📅 Menampilkan laporan periode: <strong><?php echo date('d/m/Y', strtotime($tanggal_dari)); ?> - <?php echo date('d/m/Y', strtotime($tanggal_sampai)); ?></strong>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card blue">
                    <div class="stat-icon">🛒</div>
                    <div class="stat-details">
                        <h3><?php echo $total_data['total_transaksi']; ?></h3>
                        <p>Total Transaksi</p>
                    </div>
                </div>
                
                <div class="stat-card green">
                    <div class="stat-icon">💰</div>
                    <div class="stat-details">
                        <h3><?php echo rupiah($total_data['total_penjualan']); ?></h3>
                        <p>Total Penjualan</p>
                    </div>
                </div>
                
                <div class="stat-card orange">
                    <div class="stat-icon">🎁</div>
                    <div class="stat-details">
                        <h3><?php echo rupiah($total_data['total_diskon']); ?></h3>
                        <p>Total Diskon</p>
                    </div>
                </div>
                
                <div class="stat-card red">
                    <div class="stat-icon">📊</div>
                    <div class="stat-details">
                        <h3><?php echo $total_data['total_transaksi'] > 0 ? rupiah($total_data['total_penjualan'] / $total_data['total_transaksi']) : 'Rp 0'; ?></h3>
                        <p>Rata-rata Transaksi</p>
                    </div>
                </div>
            </div>
            
            <div class="dashboard-grid">
                <div class="dashboard-card">
                    <h3>Penjualan per Metode Pembayaran</h3>
                    <canvas id="chartMetode" height="200"></canvas>
                </div>
                
                <div class="dashboard-card">
                    <h3>Produk Terlaris</h3>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Produk</th>
                                    <th>Terjual</th>
                                    <th>Pendapatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $no = 1;
                                while ($row = mysqli_fetch_assoc($terlaris)): 
                                ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><?php echo $row['nama_produk']; ?></td>
                                    <td><?php echo $row['total_terjual']; ?> pcs</td>
                                    <td><?php echo rupiah($row['total_pendapatan']); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3>Grafik Penjualan Harian</h3>
                </div>
                <canvas id="chartPenjualan" height="80"></canvas>
            </div>
        </div>
    </div>
    
    <script>
        // Chart Metode Pembayaran
        const ctxMetode = document.getElementById('chartMetode').getContext('2d');
        new Chart(ctxMetode, {
            type: 'doughnut',
            data: {
                labels: ['Cash', 'QRIS', 'Transfer'],
                datasets: [{
                    data: [
                        <?php echo $total_data['penjualan_cash']; ?>,
                        <?php echo $total_data['penjualan_qris']; ?>,
                        <?php echo $total_data['penjualan_transfer']; ?>
                    ],
                    backgroundColor: ['#06beb6', '#667eea', '#f5576c']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
        
        // Chart Penjualan Harian
        const ctxPenjualan = document.getElementById('chartPenjualan').getContext('2d');
        new Chart(ctxPenjualan, {
            type: 'line',
            data: {
                labels: [
                    <?php
                    mysqli_data_seek($grafik_data, 0);
                    while ($g = mysqli_fetch_assoc($grafik_data)) {
                        echo "'" . date('d/m', strtotime($g['tanggal'])) . "',";
                    }
                    ?>
                ],
                datasets: [{
                    label: 'Penjualan (Rp)',
                    data: [
                        <?php
                        mysqli_data_seek($grafik_data, 0);
                        while ($g = mysqli_fetch_assoc($grafik_data)) {
                            echo $g['total'] . ",";
                        }
                        ?>
                    ],
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'Rp ' + value.toLocaleString('id-ID');
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
