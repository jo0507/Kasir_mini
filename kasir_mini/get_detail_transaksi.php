<?php
require_once 'config.php';

$transaksi_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Ambil data transaksi
$query = "SELECT t.*, u.nama_lengkap as kasir 
          FROM transaksi t 
          LEFT JOIN users u ON t.user_id = u.id 
          WHERE t.id = $transaksi_id";
$transaksi = mysqli_fetch_assoc(mysqli_query($conn, $query));

if (!$transaksi) {
    echo "Transaksi tidak ditemukan";
    exit;
}

// Ambil detail item
$query_detail = "SELECT * FROM detail_transaksi WHERE transaksi_id = $transaksi_id";
$detail = mysqli_query($conn, $query_detail);
?>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
    <div>
        <p><strong>Kode Transaksi:</strong> <?php echo $transaksi['kode_transaksi']; ?></p>
        <p><strong>Tanggal:</strong> <?php echo date('d/m/Y H:i:s', strtotime($transaksi['tanggal'])); ?></p>
        <p><strong>Kasir:</strong> <?php echo $transaksi['kasir']; ?></p>
    </div>
    <div>
        <p><strong>Metode Pembayaran:</strong> <span class="badge badge-<?php echo $transaksi['metode_pembayaran']; ?>"><?php echo strtoupper($transaksi['metode_pembayaran']); ?></span></p>
        <?php if ($transaksi['metode_pembayaran'] == 'cash'): ?>
        <p><strong>Uang Dibayar:</strong> <?php echo rupiah($transaksi['uang_dibayar']); ?></p>
        <p><strong>Kembalian:</strong> <?php echo rupiah($transaksi['uang_kembalian']); ?></p>
        <?php endif; ?>
    </div>
</div>

<h4 style="margin-bottom: 15px; border-bottom: 2px solid #f5f6fa; padding-bottom: 10px;">Daftar Item</h4>

<table class="data-table">
    <thead>
        <tr>
            <th>Produk</th>
            <th>Harga</th>
            <th>Qty</th>
            <th>Subtotal</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($item = mysqli_fetch_assoc($detail)): ?>
        <tr>
            <td><?php echo $item['nama_produk']; ?></td>
            <td><?php echo rupiah($item['harga_satuan']); ?></td>
            <td><?php echo $item['qty']; ?></td>
            <td><?php echo rupiah($item['subtotal']); ?></td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<div style="margin-top: 20px; padding: 20px; background: #f8f9fa; border-radius: 5px;">
    <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
        <span>Subtotal:</span>
        <span><strong><?php echo rupiah($transaksi['subtotal']); ?></strong></span>
    </div>
    
    <?php if ($transaksi['diskon_persen'] > 0): ?>
    <div style="display: flex; justify-content: space-between; margin-bottom: 10px; color: #e74c3c;">
        <span>Diskon (<?php echo $transaksi['diskon_persen']; ?>%):</span>
        <span><strong>- <?php echo rupiah($transaksi['diskon_nominal']); ?></strong></span>
    </div>
    <?php endif; ?>
    
    <div style="display: flex; justify-content: space-between; font-size: 18px; padding-top: 10px; border-top: 2px solid #2c3e50;">
        <span><strong>TOTAL BAYAR:</strong></span>
        <span style="color: #667eea;"><strong><?php echo rupiah($transaksi['total_bayar']); ?></strong></span>
    </div>
</div>
