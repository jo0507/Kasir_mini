<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Filter
$tanggal_dari = isset($_GET['dari']) ? $_GET['dari'] : date('Y-m-d');
$tanggal_sampai = isset($_GET['sampai']) ? $_GET['sampai'] : date('Y-m-d');
$metode = isset($_GET['metode']) ? $_GET['metode'] : '';

$where = "WHERE DATE(t.tanggal) BETWEEN '$tanggal_dari' AND '$tanggal_sampai'";
if ($metode) {
    $where .= " AND t.metode_pembayaran = '$metode'";
}

// Ambil data transaksi
$query = "SELECT t.*, u.nama_lengkap as kasir 
          FROM transaksi t 
          LEFT JOIN users u ON t.user_id = u.id 
          $where 
          ORDER BY t.id DESC";
$result = mysqli_query($conn, $query);

// Hitung total
$query_total = "SELECT 
                COUNT(*) as total_transaksi,
                SUM(total_bayar) as total_penjualan
                FROM transaksi t $where";
$total = mysqli_fetch_assoc(mysqli_query($conn, $query_total));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Transaksi - Sistem Kasir</title>
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
            <a href="transaksi.php" class="active">📋 Transaksi</a>
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
            <h1>📋 Riwayat Transaksi</h1>
            <div class="top-bar-right">
                <span><?php echo tanggal_indonesia(date('Y-m-d')); ?></span>
            </div>
        </div>
        
        <div class="content">
            <div class="card">
                <div class="card-header">
                    <h3>Filter Transaksi</h3>
                </div>
                
                <form method="GET" style="display: flex; gap: 15px; align-items: end; flex-wrap: wrap;">
                    <div class="form-group" style="margin: 0;">
                        <label>Dari Tanggal</label>
                        <input type="date" name="dari" class="form-control" value="<?php echo $tanggal_dari; ?>">
                    </div>
                    
                    <div class="form-group" style="margin: 0;">
                        <label>Sampai Tanggal</label>
                        <input type="date" name="sampai" class="form-control" value="<?php echo $tanggal_sampai; ?>">
                    </div>
                    
                    <div class="form-group" style="margin: 0;">
                        <label>Metode Pembayaran</label>
                        <select name="metode" class="form-control">
                            <option value="">Semua Metode</option>
                            <option value="cash" <?php echo $metode == 'cash' ? 'selected' : ''; ?>>Cash</option>
                            <option value="qris" <?php echo $metode == 'qris' ? 'selected' : ''; ?>>QRIS</option>
                            <option value="transfer" <?php echo $metode == 'transfer' ? 'selected' : ''; ?>>Transfer</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">🔍 Filter</button>
                    <a href="transaksi.php" class="btn btn-warning">🔄 Reset</a>
                </form>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card blue">
                    <div class="stat-icon">📊</div>
                    <div class="stat-details">
                        <h3><?php echo $total['total_transaksi']; ?></h3>
                        <p>Total Transaksi</p>
                    </div>
                </div>
                
                <div class="stat-card green">
                    <div class="stat-icon">💰</div>
                    <div class="stat-details">
                        <h3><?php echo rupiah($total['total_penjualan'] ?? 0); ?></h3>
                        <p>Total Penjualan</p>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3>Daftar Transaksi</h3>
                </div>
                
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Kode Transaksi</th>
                                <th>Tanggal & Waktu</th>
                                <th>Kasir</th>
                                <th>Total</th>
                                <th>Metode</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><strong><?php echo $row['kode_transaksi']; ?></strong></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($row['tanggal'])); ?></td>
                                <td><?php echo $row['kasir']; ?></td>
                                <td><strong><?php echo rupiah($row['total_bayar']); ?></strong></td>
                                <td>
                                    <span class="badge badge-<?php echo $row['metode_pembayaran']; ?>">
                                        <?php echo strtoupper($row['metode_pembayaran']); ?>
                                    </span>
                                </td>
                                <td>
                                    <button onclick="lihatDetail(<?php echo $row['id']; ?>)" class="btn btn-primary btn-sm">👁️ Detail</button>
                                    <a href="struk.php?id=<?php echo $row['id']; ?>" target="_blank" class="btn btn-success btn-sm">🖨️ Struk</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Detail Transaksi -->
    <div id="modalDetail" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; overflow-y: auto;">
        <div style="background: white; width: 90%; max-width: 800px; margin: 50px auto; padding: 30px; border-radius: 10px;">
            <h3 style="margin-bottom: 20px;">Detail Transaksi</h3>
            <div id="detail_content"></div>
            <button onclick="closeModal()" class="btn btn-danger" style="margin-top: 20px;">❌ Tutup</button>
        </div>
    </div>
    
    <script>
        function lihatDetail(id) {
            fetch('get_detail_transaksi.php?id=' + id)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('detail_content').innerHTML = html;
                    document.getElementById('modalDetail').style.display = 'block';
                });
        }
        
        function closeModal() {
            document.getElementById('modalDetail').style.display = 'none';
        }
    </script>
</body>
</html>
