<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Proses tambah produk ke keranjang
if (isset($_POST['tambah_produk'])) {
    $barcode = clean($_POST['barcode']);
    
    $query = "SELECT * FROM produk WHERE barcode = '$barcode' AND status = 'aktif'";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) > 0) {
        $produk = mysqli_fetch_assoc($result);
        
        if ($produk['stok'] > 0) {
            if (!isset($_SESSION['keranjang'])) {
                $_SESSION['keranjang'] = array();
            }
            
            // Cek apakah produk sudah ada di keranjang
            $found = false;
            foreach ($_SESSION['keranjang'] as $key => $item) {
                if ($item['id'] == $produk['id']) {
                    // Cek stok
                    if ($_SESSION['keranjang'][$key]['qty'] < $produk['stok']) {
                        $_SESSION['keranjang'][$key]['qty']++;
                        $found = true;
                    } else {
                        echo "<script>alert('Stok tidak mencukupi!');</script>";
                        $found = true;
                    }
                    break;
                }
            }
            
            if (!$found) {
                $_SESSION['keranjang'][] = array(
                    'id' => $produk['id'],
                    'barcode' => $produk['barcode'],
                    'nama' => $produk['nama_produk'],
                    'harga' => $produk['harga_jual'],
                    'qty' => 1
                );
            }
        } else {
            echo "<script>alert('Stok habis!');</script>";
        }
    } else {
        echo "<script>alert('Produk tidak ditemukan!');</script>";
    }
}

// Proses update qty
if (isset($_POST['update_qty'])) {
    $index = $_POST['index'];
    $action = $_POST['action'];
    
    if ($action == 'plus') {
        // Cek stok
        $produk_id = $_SESSION['keranjang'][$index]['id'];
        $stok = mysqli_fetch_assoc(mysqli_query($conn, "SELECT stok FROM produk WHERE id = $produk_id"))['stok'];
        
        if ($_SESSION['keranjang'][$index]['qty'] < $stok) {
            $_SESSION['keranjang'][$index]['qty']++;
        } else {
            echo "<script>alert('Stok tidak mencukupi!');</script>";
        }
    } else {
        if ($_SESSION['keranjang'][$index]['qty'] > 1) {
            $_SESSION['keranjang'][$index]['qty']--;
        }
    }
}

// Proses hapus item
if (isset($_POST['hapus_item'])) {
    $index = $_POST['index'];
    unset($_SESSION['keranjang'][$index]);
    $_SESSION['keranjang'] = array_values($_SESSION['keranjang']);
}

// Proses hapus semua
if (isset($_POST['hapus_semua'])) {
    unset($_SESSION['keranjang']);
}

// Hitung total dan diskon
$subtotal = 0;
if (isset($_SESSION['keranjang'])) {
    foreach ($_SESSION['keranjang'] as $item) {
        $subtotal += $item['harga'] * $item['qty'];
    }
}

// Cari diskon yang berlaku
$diskon_persen = 0;
$diskon_id = null;
$query_diskon = "SELECT * FROM diskon_belanja WHERE status = 'aktif' AND minimal_belanja <= $subtotal ORDER BY persentase_diskon DESC LIMIT 1";
$result_diskon = mysqli_query($conn, $query_diskon);
if (mysqli_num_rows($result_diskon) > 0) {
    $diskon_data = mysqli_fetch_assoc($result_diskon);
    $diskon_persen = $diskon_data['persentase_diskon'];
    $diskon_id = $diskon_data['id'];
}

$diskon_nominal = ($subtotal * $diskon_persen) / 100;
$total_bayar = $subtotal - $diskon_nominal;

// Proses pembayaran
if (isset($_POST['proses_bayar'])) {
    $metode = clean($_POST['metode_pembayaran']);
    $uang_dibayar = 0;
    $uang_kembalian = 0;
    
    if ($metode == 'cash') {
        $uang_dibayar = floatval($_POST['uang_dibayar']);
        if ($uang_dibayar < $total_bayar) {
            echo "<script>alert('Uang tidak cukup!');</script>";
        } else {
            $uang_kembalian = $uang_dibayar - $total_bayar;
            // Proses simpan transaksi
            simpanTransaksi($conn, $metode, $uang_dibayar, $uang_kembalian, $subtotal, $diskon_id, $diskon_persen, $diskon_nominal, $total_bayar);
        }
    } else {
        // QRIS atau Transfer
        simpanTransaksi($conn, $metode, $total_bayar, 0, $subtotal, $diskon_id, $diskon_persen, $diskon_nominal, $total_bayar);
    }
}

function simpanTransaksi($conn, $metode, $uang_dibayar, $uang_kembalian, $subtotal, $diskon_id, $diskon_persen, $diskon_nominal, $total_bayar) {
    $kode_transaksi = generateKodeTransaksi();
    $user_id = $_SESSION['user_id'];
    
    // Insert transaksi
    $query = "INSERT INTO transaksi (kode_transaksi, user_id, subtotal, diskon_id, diskon_persen, diskon_nominal, total_bayar, metode_pembayaran, uang_dibayar, uang_kembalian) 
              VALUES ('$kode_transaksi', $user_id, $subtotal, " . ($diskon_id ? $diskon_id : 'NULL') . ", $diskon_persen, $diskon_nominal, $total_bayar, '$metode', $uang_dibayar, $uang_kembalian)";
    
    if (mysqli_query($conn, $query)) {
        $transaksi_id = mysqli_insert_id($conn);
        
        // Insert detail transaksi dan update stok
        foreach ($_SESSION['keranjang'] as $item) {
            // Insert detail
            $query_detail = "INSERT INTO detail_transaksi (transaksi_id, produk_id, nama_produk, harga_satuan, qty, subtotal) 
                            VALUES ($transaksi_id, {$item['id']}, '{$item['nama']}', {$item['harga']}, {$item['qty']}, " . ($item['harga'] * $item['qty']) . ")";
            mysqli_query($conn, $query_detail);
            
            // Update stok
            $query_stok = "UPDATE produk SET stok = stok - {$item['qty']} WHERE id = {$item['id']}";
            mysqli_query($conn, $query_stok);
            
            // Log stok keluar
            $query_log = "INSERT INTO log_stok (produk_id, jenis, qty, keterangan, user_id, transaksi_id) 
                         VALUES ({$item['id']}, 'keluar', {$item['qty']}, 'Penjualan - $kode_transaksi', {$_SESSION['user_id']}, $transaksi_id)";
            mysqli_query($conn, $query_log);
        }
        
        // Redirect ke struk
        unset($_SESSION['keranjang']);
        header("Location: struk.php?id=$transaksi_id");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kasir - Sistem Kasir</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .kasir-container {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            height: calc(100vh - 100px);
        }
        
        .kasir-left {
            display: flex;
            flex-direction: column;
        }
        
        .scan-box {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .scan-input {
            display: flex;
            gap: 10px;
        }
        
        .scan-input input {
            flex: 1;
            padding: 15px;
            font-size: 18px;
            border: 2px solid #667eea;
            border-radius: 5px;
        }
        
        .keranjang-box {
            background: white;
            padding: 20px;
            border-radius: 10px;
            flex: 1;
            overflow-y: auto;
        }
        
        .keranjang-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .item-info {
            flex: 1;
        }
        
        .item-name {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .item-price {
            color: #667eea;
            font-size: 14px;
        }
        
        .item-qty {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .qty-btn {
            width: 30px;
            height: 30px;
            border: none;
            background: #667eea;
            color: white;
            border-radius: 5px;
            cursor: pointer;
            font-size: 18px;
        }
        
        .item-total {
            font-weight: 700;
            color: #2c3e50;
        }
        
        .kasir-right {
            background: white;
            padding: 20px;
            border-radius: 10px;
            display: flex;
            flex-direction: column;
        }
        
        .total-box {
            margin-bottom: 20px;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            font-size: 16px;
        }
        
        .total-row.final {
            border-top: 2px solid #2c3e50;
            margin-top: 10px;
            padding-top: 15px;
            font-size: 24px;
            font-weight: 700;
            color: #667eea;
        }
        
        .payment-form {
            flex: 1;
        }
        
        .btn-bayar {
            width: 100%;
            padding: 15px;
            font-size: 18px;
            font-weight: 700;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>🏪 Kasir Mini</h2>
            <p><?php echo $_SESSION['nama_lengkap']; ?></p>
        </div>
        <nav class="sidebar-menu">
            <a href="index.php">📊 Dashboard</a>
            <a href="kasir.php" class="active">💰 Kasir</a>
            <a href="produk.php">📦 Produk</a>
            <a href="diskon.php">🎁 Diskon</a>
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
            <h1>💰 Kasir</h1>
            <div class="top-bar-right">
                <span id="tanggal"><?php echo tanggal_indonesia(date('Y-m-d')); ?></span>
                <span id="jam"></span>
            </div>
        </div>
        
        <div class="content">
            <div class="kasir-container">
                <div class="kasir-left">
                    <div class="scan-box">
                        <h3 style="margin-bottom: 15px;">🔍 Scan Barcode</h3>
                        <form method="POST" class="scan-input">
                            <input type="text" name="barcode" id="barcode" placeholder="Scan atau ketik barcode..." autofocus autocomplete="off">
                            <button type="submit" name="tambah_produk" class="btn btn-primary">Tambah</button>
                        </form>
                    </div>
                    
                    <div class="keranjang-box">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                            <h3>🛒 Keranjang Belanja</h3>
                            <?php if (isset($_SESSION['keranjang']) && count($_SESSION['keranjang']) > 0): ?>
                            <form method="POST" style="display: inline;">
                                <button type="submit" name="hapus_semua" class="btn btn-danger btn-sm" onclick="return confirm('Hapus semua item?')">Hapus Semua</button>
                            </form>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (isset($_SESSION['keranjang']) && count($_SESSION['keranjang']) > 0): ?>
                            <?php foreach ($_SESSION['keranjang'] as $index => $item): ?>
                            <div class="keranjang-item">
                                <div class="item-info">
                                    <div class="item-name"><?php echo $item['nama']; ?></div>
                                    <div class="item-price"><?php echo rupiah($item['harga']); ?></div>
                                </div>
                                
                                <div class="item-qty">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="index" value="<?php echo $index; ?>">
                                        <input type="hidden" name="action" value="minus">
                                        <button type="submit" name="update_qty" class="qty-btn">-</button>
                                    </form>
                                    
                                    <span style="font-weight: 600; min-width: 30px; text-align: center;"><?php echo $item['qty']; ?></span>
                                    
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="index" value="<?php echo $index; ?>">
                                        <input type="hidden" name="action" value="plus">
                                        <button type="submit" name="update_qty" class="qty-btn">+</button>
                                    </form>
                                </div>
                                
                                <div class="item-total" style="min-width: 120px; text-align: right;">
                                    <?php echo rupiah($item['harga'] * $item['qty']); ?>
                                </div>
                                
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="index" value="<?php echo $index; ?>">
                                    <button type="submit" name="hapus_item" class="btn btn-danger btn-sm" style="margin-left: 10px;">🗑️</button>
                                </form>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="text-align: center; padding: 40px; color: #7f8c8d;">Keranjang masih kosong</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="kasir-right">
                    <h3 style="margin-bottom: 20px;">💳 Pembayaran</h3>
                    
                    <div class="total-box">
                        <div class="total-row">
                            <span>Subtotal:</span>
                            <span><?php echo rupiah($subtotal); ?></span>
                        </div>
                        <?php if ($diskon_persen > 0): ?>
                        <div class="total-row" style="color: #e74c3c;">
                            <span>Diskon (<?php echo $diskon_persen; ?>%):</span>
                            <span>- <?php echo rupiah($diskon_nominal); ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="total-row final">
                            <span>TOTAL:</span>
                            <span><?php echo rupiah($total_bayar); ?></span>
                        </div>
                    </div>
                    
                    <?php if (isset($_SESSION['keranjang']) && count($_SESSION['keranjang']) > 0): ?>
                    <form method="POST" class="payment-form">
                        <div class="form-group">
                            <label>Metode Pembayaran</label>
                            <select name="metode_pembayaran" class="form-control" id="metode" onchange="toggleCash()">
                                <option value="cash">Cash (Tunai)</option>
                                <option value="qris">QRIS</option>
                                <option value="transfer">Transfer Bank</option>
                            </select>
                        </div>
                        
                        <div id="cash-form">
                            <div class="form-group">
                                <label>Uang Dibayar</label>
                                <input type="number" name="uang_dibayar" class="form-control" step="1000" min="<?php echo $total_bayar; ?>">
                            </div>
                        </div>
                        
                        <button type="submit" name="proses_bayar" class="btn btn-success btn-bayar">✅ PROSES PEMBAYARAN</button>
                    </form>
                    <?php endif; ?>
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
            document.getElementById('jam').textContent = `${jam}:${menit}:${detik}`;
        }
        setInterval(updateJam, 1000);
        updateJam();
        
        // Toggle cash form
        function toggleCash() {
            const metode = document.getElementById('metode').value;
            const cashForm = document.getElementById('cash-form');
            
            if (metode === 'cash') {
                cashForm.style.display = 'block';
            } else {
                cashForm.style.display = 'none';
            }
        }
        
        // Auto focus barcode
        document.getElementById('barcode').focus();
        
        // Clear barcode after submit
        document.querySelector('form').addEventListener('submit', function() {
            setTimeout(() => {
                document.getElementById('barcode').value = '';
                document.getElementById('barcode').focus();
            }, 100);
        });
    </script>
</body>
</html>
