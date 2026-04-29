<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$transaksi_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Ambil data transaksi
$query = "SELECT t.*, u.nama_lengkap as kasir 
          FROM transaksi t 
          LEFT JOIN users u ON t.user_id = u.id 
          WHERE t.id = $transaksi_id";
$transaksi = mysqli_fetch_assoc(mysqli_query($conn, $query));

if (!$transaksi) {
    die("Transaksi tidak ditemukan");
}

// Ambil detail transaksi
$query_detail = "SELECT * FROM detail_transaksi WHERE transaksi_id = $transaksi_id";
$detail = mysqli_query($conn, $query_detail);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk - <?php echo $transaksi['kode_transaksi']; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Courier New', monospace;
            padding: 20px;
            background: #f5f6fa;
        }
        
        .struk-container {
            max-width: 400px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border: 2px dashed #333;
        }
        
        .struk-header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 1px dashed #333;
            padding-bottom: 15px;
        }
        
        .struk-header h1 {
            font-size: 24px;
            margin-bottom: 5px;
        }
        
        .struk-header p {
            font-size: 12px;
            margin: 2px 0;
        }
        
        .struk-info {
            margin-bottom: 15px;
            font-size: 12px;
            border-bottom: 1px dashed #333;
            padding-bottom: 10px;
        }
        
        .struk-info table {
            width: 100%;
        }
        
        .struk-items {
            margin-bottom: 15px;
            border-bottom: 1px dashed #333;
            padding-bottom: 10px;
        }
        
        .item-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 12px;
        }
        
        .item-qty {
            margin-left: 10px;
            color: #666;
        }
        
        .struk-total {
            font-size: 12px;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        
        .total-row.final {
            font-size: 16px;
            font-weight: bold;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #333;
        }
        
        .struk-footer {
            text-align: center;
            margin-top: 20px;
            font-size: 12px;
            border-top: 1px dashed #333;
            padding-top: 15px;
        }
        
        .btn-container {
            text-align: center;
            margin-top: 20px;
        }
        
        .btn {
            padding: 12px 30px;
            margin: 0 5px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-print {
            background: #667eea;
            color: white;
        }
        
        .btn-back {
            background: #7f8c8d;
            color: white;
        }
        
        @media print {
            body {
                padding: 0;
                background: white;
            }
            
            .struk-container {
                border: none;
                max-width: 100%;
            }
            
            .btn-container {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="struk-container">
        <div class="struk-header">
            <h1>MINIMART ABADI</h1>
            <p>Jl. Raya Utama No. 123</p>
            <p>Telp: (021) 12345678</p>
        </div>
        
        <div class="struk-info">
            <table>
                <tr>
                    <td>No. Transaksi</td>
                    <td>: <?php echo $transaksi['kode_transaksi']; ?></td>
                </tr>
                <tr>
                    <td>Tanggal</td>
                    <td>: <?php echo date('d/m/Y H:i:s', strtotime($transaksi['tanggal'])); ?></td>
                </tr>
                <tr>
                    <td>Kasir</td>
                    <td>: <?php echo $transaksi['kasir']; ?></td>
                </tr>
                <tr>
                    <td>Pembayaran</td>
                    <td>: <?php echo strtoupper($transaksi['metode_pembayaran']); ?></td>
                </tr>
            </table>
        </div>
        
        <div class="struk-items">
            <?php while ($item = mysqli_fetch_assoc($detail)): ?>
            <div class="item-row">
                <div>
                    <strong><?php echo $item['nama_produk']; ?></strong>
                    <span class="item-qty"><?php echo $item['qty']; ?> x <?php echo rupiah($item['harga_satuan']); ?></span>
                </div>
                <div><?php echo rupiah($item['subtotal']); ?></div>
            </div>
            <?php endwhile; ?>
        </div>
        
        <div class="struk-total">
            <div class="total-row">
                <span>Subtotal:</span>
                <span><?php echo rupiah($transaksi['subtotal']); ?></span>
            </div>
            
            <?php if ($transaksi['diskon_persen'] > 0): ?>
            <div class="total-row">
                <span>Diskon (<?php echo $transaksi['diskon_persen']; ?>%):</span>
                <span>- <?php echo rupiah($transaksi['diskon_nominal']); ?></span>
            </div>
            <?php endif; ?>
            
            <div class="total-row final">
                <span>TOTAL BAYAR:</span>
                <span><?php echo rupiah($transaksi['total_bayar']); ?></span>
            </div>
            
            <?php if ($transaksi['metode_pembayaran'] == 'cash'): ?>
            <div class="total-row">
                <span>Uang Dibayar:</span>
                <span><?php echo rupiah($transaksi['uang_dibayar']); ?></span>
            </div>
            <div class="total-row">
                <span>Kembalian:</span>
                <span><?php echo rupiah($transaksi['uang_kembalian']); ?></span>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="struk-footer">
            <p>== TERIMA KASIH ==</p>
            <p>Barang yang sudah dibeli</p>
            <p>tidak dapat ditukar/dikembalikan</p>
        </div>
    </div>
    
    <div class="btn-container">
        <button onclick="window.print()" class="btn btn-print">🖨️ Cetak Struk</button>
        <a href="kasir.php" class="btn btn-back">← Kembali ke Kasir</a>
    </div>
</body>
</html>
