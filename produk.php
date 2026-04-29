<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$message = '';

// Proses Tambah Produk
if (isset($_POST['tambah'])) {
    $barcode = clean($_POST['barcode']);
    $nama = clean($_POST['nama_produk']);
    $kategori = clean($_POST['kategori']);
    $harga_beli = floatval($_POST['harga_beli']);
    $harga_jual = floatval($_POST['harga_jual']);
    $stok = intval($_POST['stok']);
    $satuan = clean($_POST['satuan']);
    
    // Cek barcode duplikat
    $cek = mysqli_query($conn, "SELECT * FROM produk WHERE barcode = '$barcode'");
    if (mysqli_num_rows($cek) > 0) {
        $message = "<div class='alert alert-danger'>Barcode sudah ada!</div>";
    } else {
        $query = "INSERT INTO produk (barcode, nama_produk, kategori, harga_beli, harga_jual, stok, satuan) 
                  VALUES ('$barcode', '$nama', '$kategori', $harga_beli, $harga_jual, $stok, '$satuan')";
        
        if (mysqli_query($conn, $query)) {
            $produk_id = mysqli_insert_id($conn);
            
            // Log stok masuk
            $query_log = "INSERT INTO log_stok (produk_id, jenis, qty, keterangan, user_id) 
                         VALUES ($produk_id, 'masuk', $stok, 'Stok awal produk baru', {$_SESSION['user_id']})";
            mysqli_query($conn, $query_log);
            
            $message = "<div class='alert alert-success'>Produk berhasil ditambahkan!</div>";
        }
    }
}

// Proses Edit Produk
if (isset($_POST['edit'])) {
    $id = intval($_POST['id']);
    $barcode = clean($_POST['barcode']);
    $nama = clean($_POST['nama_produk']);
    $kategori = clean($_POST['kategori']);
    $harga_beli = floatval($_POST['harga_beli']);
    $harga_jual = floatval($_POST['harga_jual']);
    $satuan = clean($_POST['satuan']);
    
    $query = "UPDATE produk SET 
              barcode = '$barcode',
              nama_produk = '$nama',
              kategori = '$kategori',
              harga_beli = $harga_beli,
              harga_jual = $harga_jual,
              satuan = '$satuan'
              WHERE id = $id";
    
    if (mysqli_query($conn, $query)) {
        $message = "<div class='alert alert-success'>Produk berhasil diupdate!</div>";
    }
}

// Proses Tambah Stok
if (isset($_POST['tambah_stok'])) {
    $id = intval($_POST['id']);
    $qty = intval($_POST['qty_tambah']);
    $keterangan = clean($_POST['keterangan']);
    
    $query = "UPDATE produk SET stok = stok + $qty WHERE id = $id";
    mysqli_query($conn, $query);
    
    // Log stok masuk
    $query_log = "INSERT INTO log_stok (produk_id, jenis, qty, keterangan, user_id) 
                 VALUES ($id, 'masuk', $qty, '$keterangan', {$_SESSION['user_id']})";
    mysqli_query($conn, $query_log);
    
    $message = "<div class='alert alert-success'>Stok berhasil ditambahkan!</div>";
}

// Proses Hapus Produk
if (isset($_GET['hapus'])) {
    $id = intval($_GET['hapus']);
    $query = "UPDATE produk SET status = 'nonaktif' WHERE id = $id";
    
    if (mysqli_query($conn, $query)) {
        $message = "<div class='alert alert-success'>Produk berhasil dihapus!</div>";
    }
}

// Ambil data produk
$search = isset($_GET['search']) ? clean($_GET['search']) : '';
$where = "WHERE status = 'aktif'";
if ($search) {
    $where .= " AND (nama_produk LIKE '%$search%' OR barcode LIKE '%$search%')";
}

$query = "SELECT * FROM produk $where ORDER BY id DESC";
$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Produk - Sistem Kasir</title>
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
            <a href="produk.php" class="active">📦 Produk</a>
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
            <h1>📦 Manajemen Produk</h1>
            <div class="top-bar-right">
                <span><?php echo tanggal_indonesia(date('Y-m-d')); ?></span>
            </div>
        </div>
        
        <div class="content">
            <?php echo $message; ?>
            
            <div class="card">
                <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                    <h3>Tambah Produk Baru</h3>
                </div>
                
                <form method="POST">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Barcode *</label>
                            <input type="text" name="barcode" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Nama Produk *</label>
                            <input type="text" name="nama_produk" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Kategori</label>
                            <input type="text" name="kategori" class="form-control" placeholder="Makanan, Minuman, dll">
                        </div>
                        
                        <div class="form-group">
                            <label>Satuan</label>
                            <select name="satuan" class="form-control">
                                <option value="pcs">Pcs</option>
                                <option value="box">Box</option>
                                <option value="lusin">Lusin</option>
                                <option value="kg">Kg</option>
                                <option value="liter">Liter</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Harga Beli *</label>
                            <input type="number" name="harga_beli" class="form-control" step="100" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Harga Jual *</label>
                            <input type="number" name="harga_jual" class="form-control" step="100" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Stok Awal *</label>
                            <input type="number" name="stok" class="form-control" value="0" required>
                        </div>
                    </div>
                    
                    <button type="submit" name="tambah" class="btn btn-primary">➕ Tambah Produk</button>
                </form>
            </div>
            
            <div class="card">
                <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                    <h3>Daftar Produk</h3>
                    <form method="GET" style="display: flex; gap: 10px;">
                        <input type="text" name="search" class="form-control" placeholder="Cari produk..." value="<?php echo $search; ?>" style="width: 300px;">
                        <button type="submit" class="btn btn-primary">🔍 Cari</button>
                        <?php if ($search): ?>
                        <a href="produk.php" class="btn btn-warning">Reset</a>
                        <?php endif; ?>
                    </form>
                </div>
                
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Barcode</th>
                                <th>Nama Produk</th>
                                <th>Kategori</th>
                                <th>Harga Beli</th>
                                <th>Harga Jual</th>
                                <th>Stok</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><?php echo $row['barcode']; ?></td>
                                <td><strong><?php echo $row['nama_produk']; ?></strong></td>
                                <td><?php echo $row['kategori']; ?></td>
                                <td><?php echo rupiah($row['harga_beli']); ?></td>
                                <td><?php echo rupiah($row['harga_jual']); ?></td>
                                <td>
                                    <span class="badge <?php echo $row['stok'] < 10 ? 'badge-red' : 'badge-green'; ?>">
                                        <?php echo $row['stok']; ?> <?php echo $row['satuan']; ?>
                                    </span>
                                </td>
                                <td>
                                    <button onclick="editProduk(<?php echo htmlspecialchars(json_encode($row)); ?>)" class="btn btn-warning btn-sm">✏️ Edit</button>
                                    <button onclick="tambahStok(<?php echo $row['id']; ?>, '<?php echo $row['nama_produk']; ?>')" class="btn btn-success btn-sm">📥 Stok</button>
                                    <a href="?hapus=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin hapus produk ini?')">🗑️</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Edit Produk -->
    <div id="modalEdit" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div style="background: white; width: 90%; max-width: 600px; margin: 50px auto; padding: 30px; border-radius: 10px; max-height: 90vh; overflow-y: auto;">
            <h3 style="margin-bottom: 20px;">Edit Produk</h3>
            <form method="POST">
                <input type="hidden" name="id" id="edit_id">
                
                <div class="form-group">
                    <label>Barcode</label>
                    <input type="text" name="barcode" id="edit_barcode" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>Nama Produk</label>
                    <input type="text" name="nama_produk" id="edit_nama" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>Kategori</label>
                    <input type="text" name="kategori" id="edit_kategori" class="form-control">
                </div>
                
                <div class="form-group">
                    <label>Satuan</label>
                    <select name="satuan" id="edit_satuan" class="form-control">
                        <option value="pcs">Pcs</option>
                        <option value="box">Box</option>
                        <option value="lusin">Lusin</option>
                        <option value="kg">Kg</option>
                        <option value="liter">Liter</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Harga Beli</label>
                    <input type="number" name="harga_beli" id="edit_harga_beli" class="form-control" step="100" required>
                </div>
                
                <div class="form-group">
                    <label>Harga Jual</label>
                    <input type="number" name="harga_jual" id="edit_harga_jual" class="form-control" step="100" required>
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" name="edit" class="btn btn-primary">💾 Simpan</button>
                    <button type="button" onclick="closeModal('modalEdit')" class="btn btn-danger">❌ Batal</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal Tambah Stok -->
    <div id="modalStok" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div style="background: white; width: 90%; max-width: 500px; margin: 50px auto; padding: 30px; border-radius: 10px;">
            <h3 style="margin-bottom: 20px;">Tambah Stok</h3>
            <form method="POST">
                <input type="hidden" name="id" id="stok_id">
                
                <div class="form-group">
                    <label>Produk</label>
                    <input type="text" id="stok_nama" class="form-control" readonly>
                </div>
                
                <div class="form-group">
                    <label>Jumlah Tambah Stok</label>
                    <input type="number" name="qty_tambah" class="form-control" min="1" required>
                </div>
                
                <div class="form-group">
                    <label>Keterangan</label>
                    <input type="text" name="keterangan" class="form-control" placeholder="Misal: Barang datang dari supplier" required>
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" name="tambah_stok" class="btn btn-success">📥 Tambah Stok</button>
                    <button type="button" onclick="closeModal('modalStok')" class="btn btn-danger">❌ Batal</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function editProduk(data) {
            document.getElementById('edit_id').value = data.id;
            document.getElementById('edit_barcode').value = data.barcode;
            document.getElementById('edit_nama').value = data.nama_produk;
            document.getElementById('edit_kategori').value = data.kategori;
            document.getElementById('edit_satuan').value = data.satuan;
            document.getElementById('edit_harga_beli').value = data.harga_beli;
            document.getElementById('edit_harga_jual').value = data.harga_jual;
            document.getElementById('modalEdit').style.display = 'block';
        }
        
        function tambahStok(id, nama) {
            document.getElementById('stok_id').value = id;
            document.getElementById('stok_nama').value = nama;
            document.getElementById('modalStok').style.display = 'block';
        }
        
        function closeModal(id) {
            document.getElementById(id).style.display = 'none';
        }
    </script>
</body>
</html>
