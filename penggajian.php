<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$message = '';
$is_admin = ($_SESSION['role'] == 'admin');

// Get pegawai data
$pegawai_data = getPegawaiByUserId($_SESSION['user_id']);

// Proses hitung gaji (admin only)
if ($is_admin && isset($_POST['hitung_gaji'])) {
    $pegawai_id = intval($_POST['pegawai_id']);
    $bulan = intval($_POST['bulan']);
    $tahun = intval($_POST['tahun']);
    
    // Cek sudah ada atau belum
    $cek = mysqli_query($conn, "SELECT * FROM penggajian WHERE pegawai_id = $pegawai_id AND periode_bulan = $bulan AND periode_tahun = $tahun");
    if (mysqli_num_rows($cek) > 0) {
        $message = "<div class='alert alert-danger'>Gaji untuk periode ini sudah dihitung!</div>";
    } else {
        // Get data pegawai
        $peg = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM pegawai WHERE id = $pegawai_id"));
        $gaji_pokok = $peg['gaji_pokok'];
        
        // Hitung absensi
        $query_absen = "SELECT 
                        SUM(CASE WHEN status = 'HADIR' THEN 1 ELSE 0 END) as hadir,
                        SUM(CASE WHEN status = 'TERLAMBAT' THEN 1 ELSE 0 END) as terlambat,
                        SUM(CASE WHEN status = 'IZIN' THEN 1 ELSE 0 END) as izin,
                        SUM(CASE WHEN status = 'SAKIT' THEN 1 ELSE 0 END) as sakit,
                        SUM(CASE WHEN status = 'ALFA' THEN 1 ELSE 0 END) as alfa
                        FROM absensi
                        WHERE pegawai_id = $pegawai_id 
                        AND MONTH(tanggal) = $bulan 
                        AND YEAR(tanggal) = $tahun";
        $absen = mysqli_fetch_assoc(mysqli_query($conn, $query_absen));
        
        // Hitung potongan
        $total_potongan = 0;
        
        // Potongan ALFA
        $pot_alfa = mysqli_fetch_assoc(mysqli_query($conn, "SELECT nominal_potongan FROM setting_potongan WHERE jenis_ketidakhadiran = 'ALFA' AND status = 'aktif' ORDER BY id DESC LIMIT 1"));
        if ($pot_alfa && $absen['alfa'] > 0) {
            $total_potongan += ($pot_alfa['nominal_potongan'] * $absen['alfa']);
        }
        
        // Potongan IZIN
        $pot_izin = mysqli_fetch_assoc(mysqli_query($conn, "SELECT nominal_potongan FROM setting_potongan WHERE jenis_ketidakhadiran = 'IZIN' AND status = 'aktif' ORDER BY id DESC LIMIT 1"));
        if ($pot_izin && $absen['izin'] > 0) {
            $total_potongan += ($pot_izin['nominal_potongan'] * $absen['izin']);
        }
        
        // Hitung total penjualan (dari transaksi yang dikerjakan pegawai)
        $query_penjualan = "SELECT COALESCE(SUM(total_bayar), 0) as total
                           FROM transaksi t
                           JOIN users u ON t.user_id = u.id
                           JOIN pegawai p ON u.id = p.user_id
                           WHERE p.id = $pegawai_id
                           AND MONTH(t.tanggal) = $bulan
                           AND YEAR(t.tanggal) = $tahun";
        $penjualan = mysqli_fetch_assoc(mysqli_query($conn, $query_penjualan));
        $total_penjualan = $penjualan['total'];
        
        // Hitung bonus penjualan (pilih bonus terbesar yang memenuhi syarat)
        $bonus_penjualan = 0;
        $query_bonus = "SELECT * FROM setting_bonus WHERE status = 'aktif' AND minimal_penjualan <= $total_penjualan ORDER BY nominal_bonus DESC LIMIT 1";
        $bonus = mysqli_fetch_assoc(mysqli_query($conn, $query_bonus));
        if ($bonus) {
            $bonus_penjualan = $bonus['nominal_bonus'];
        }
        
        // Hitung gaji bersih
        $gaji_bersih = $gaji_pokok - $total_potongan + $bonus_penjualan;
        
        // Simpan ke database
        $query_insert = "INSERT INTO penggajian (
                         pegawai_id, periode_bulan, periode_tahun, gaji_pokok,
                         total_hadir, total_terlambat, total_izin, total_sakit, total_alfa,
                         total_potongan, total_penjualan, bonus_penjualan, gaji_bersih
                         ) VALUES (
                         $pegawai_id, $bulan, $tahun, $gaji_pokok,
                         {$absen['hadir']}, {$absen['terlambat']}, {$absen['izin']}, {$absen['sakit']}, {$absen['alfa']},
                         $total_potongan, $total_penjualan, $bonus_penjualan, $gaji_bersih
                         )";
        
        if (mysqli_query($conn, $query_insert)) {
            $message = "<div class='alert alert-success'>Gaji berhasil dihitung! Gaji Bersih: " . rupiah($gaji_bersih) . "</div>";
        } else {
            $message = "<div class='alert alert-danger'>Error: " . mysqli_error($conn) . "</div>";
        }
    }
}

// Proses bayar gaji
if ($is_admin && isset($_POST['bayar_gaji'])) {
    $id = intval($_POST['id']);
    $tanggal_bayar = date('Y-m-d');
    
    $query = "UPDATE penggajian SET status_pembayaran = 'dibayar', tanggal_dibayar = '$tanggal_bayar' WHERE id = $id";
    
    if (mysqli_query($conn, $query)) {
        $message = "<div class='alert alert-success'>Status pembayaran berhasil diupdate!</div>";
    }
}

// Filter
$bulan_filter = isset($_GET['bulan']) ? intval($_GET['bulan']) : date('n');
$tahun_filter = isset($_GET['tahun']) ? intval($_GET['tahun']) : date('Y');
$pegawai_filter = isset($_GET['pegawai']) ? intval($_GET['pegawai']) : 0;

// Query penggajian
$where = "WHERE pg.periode_bulan = $bulan_filter AND pg.periode_tahun = $tahun_filter";
if (!$is_admin && $pegawai_data) {
    $where .= " AND pg.pegawai_id = {$pegawai_data['id']}";
} elseif ($pegawai_filter > 0) {
    $where .= " AND pg.pegawai_id = $pegawai_filter";
}

$query = "SELECT pg.*, p.nip, p.jabatan, u.nama_lengkap
          FROM penggajian pg
          JOIN pegawai p ON pg.pegawai_id = p.id
          JOIN users u ON p.user_id = u.id
          $where
          ORDER BY pg.id DESC";
$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_admin ? 'Penggajian' : 'Slip Gaji'; ?> - Sistem Kasir</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>🏪 Kasir Mini</h2>
            <p><?php echo $_SESSION['nama_lengkap']; ?></p>
            <small><?php echo ucfirst($_SESSION['role']); ?></small>
        </div>
        <nav class="sidebar-menu">
            <a href="index.php">📊 Dashboard</a>
            <?php if ($_SESSION['role'] != 'pegawai'): ?>
            <a href="kasir.php">💰 Kasir</a>
            <a href="produk.php">📦 Produk</a>
            <a href="diskon.php">🎁 Diskon</a>
            <a href="transaksi.php">📋 Transaksi</a>
            <a href="tutup_kasir.php">🔒 Tutup Kasir</a>
            <?php endif; ?>
            <?php if ($_SESSION['role'] == 'admin'): ?>
            <a href="laporan.php">📈 Laporan</a>
            <hr style="border-color: rgba(255,255,255,0.1);">
            <a href="pegawai.php">👥 Pegawai</a>
            <?php endif; ?>
            <a href="absensi.php">📝 Absensi</a>
            <?php if ($_SESSION['role'] == 'admin'): ?>
            <a href="setting_gaji.php">⚙️ Setting Gaji</a>
            <?php endif; ?>
            <a href="penggajian.php" class="active">💵 <?php echo $is_admin ? 'Penggajian' : 'Slip Gaji'; ?></a>
            <?php if ($_SESSION['role'] == 'admin'): ?>
            <hr style="border-color: rgba(255,255,255,0.1);">
            <a href="user.php">👤 User</a>
            <?php endif; ?>
            <a href="logout.php" style="color: #e74c3c;">🚪 Logout</a>
        </nav>
    </div>
    
    <div class="main-content">
        <div class="top-bar">
            <h1>💵 <?php echo $is_admin ? 'Penggajian Pegawai' : 'Slip Gaji Saya'; ?></h1>
        </div>
        
        <div class="content">
            <?php echo $message; ?>
            
            <?php if ($is_admin): ?>
            <div class="card">
                <div class="card-header">
                    <h3>🧮 Hitung Gaji Pegawai</h3>
                </div>
                
                <div class="alert alert-info">
                    💡 <strong>Info:</strong> Gaji dihitung otomatis berdasarkan:<br>
                    • Gaji Pokok<br>
                    • Potongan (Alfa & Izin dari absensi)<br>
                    • Bonus Penjualan (dari total transaksi pegawai)<br>
                    <strong>Formula: Gaji Bersih = Gaji Pokok - Potongan + Bonus</strong>
                </div>
                
                <form method="POST">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Pilih Pegawai *</label>
                            <select name="pegawai_id" class="form-control" required>
                                <option value="">-- Pilih Pegawai --</option>
                                <?php
                                $q_peg = mysqli_query($conn, "SELECT p.id, p.nip, u.nama_lengkap, p.gaji_pokok FROM pegawai p JOIN users u ON p.user_id = u.id WHERE p.status_kerja = 'aktif'");
                                while ($peg = mysqli_fetch_assoc($q_peg)):
                                ?>
                                <option value="<?php echo $peg['id']; ?>">
                                    <?php echo $peg['nama_lengkap']; ?> - <?php echo rupiah($peg['gaji_pokok']); ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Bulan *</label>
                            <select name="bulan" class="form-control" required>
                                <?php for($i=1; $i<=12; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php echo $i == date('n') ? 'selected' : ''; ?>>
                                    <?php echo bulan_tahun($i, date('Y')); ?>
                                </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Tahun *</label>
                            <select name="tahun" class="form-control" required>
                                <?php for($y=date('Y'); $y>=date('Y')-2; $y--): ?>
                                <option value="<?php echo $y; ?>"><?php echo $y; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    
                    <button type="submit" name="hitung_gaji" class="btn btn-primary">🧮 Hitung Gaji</button>
                </form>
            </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <h3>Filter Data Gaji</h3>
                </div>
                
                <form method="GET" style="display: flex; gap: 10px; align-items: end; flex-wrap: wrap;">
                    <div class="form-group" style="margin: 0;">
                        <label>Bulan</label>
                        <select name="bulan" class="form-control">
                            <?php for($i=1; $i<=12; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo $i == $bulan_filter ? 'selected' : ''; ?>>
                                <?php echo bulan_tahun($i, 2025); ?>
                            </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <div class="form-group" style="margin: 0;">
                        <label>Tahun</label>
                        <select name="tahun" class="form-control">
                            <?php for($y=date('Y'); $y>=date('Y')-2; $y--): ?>
                            <option value="<?php echo $y; ?>" <?php echo $y == $tahun_filter ? 'selected' : ''; ?>><?php echo $y; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <?php if ($is_admin): ?>
                    <div class="form-group" style="margin: 0;">
                        <label>Pegawai</label>
                        <select name="pegawai" class="form-control">
                            <option value="0">Semua Pegawai</option>
                            <?php
                            $q_peg = mysqli_query($conn, "SELECT p.id, u.nama_lengkap FROM pegawai p JOIN users u ON p.user_id = u.id WHERE p.status_kerja = 'aktif'");
                            while ($peg = mysqli_fetch_assoc($q_peg)):
                            ?>
                            <option value="<?php echo $peg['id']; ?>" <?php echo $peg['id'] == $pegawai_filter ? 'selected' : ''; ?>>
                                <?php echo $peg['nama_lengkap']; ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    
                    <button type="submit" class="btn btn-primary">🔍 Filter</button>
                </form>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3>Data Penggajian - <?php echo bulan_tahun($bulan_filter, $tahun_filter); ?></h3>
                </div>
                
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <?php if ($is_admin): ?>
                                <th>NIP</th>
                                <th>Nama</th>
                                <?php endif; ?>
                                <th>Periode</th>
                                <th>Gaji Pokok</th>
                                <th>Potongan</th>
                                <th>Bonus</th>
                                <th>Gaji Bersih</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if (mysqli_num_rows($result) == 0): ?>
                            <tr>
                                <td colspan="<?php echo $is_admin ? '9' : '7'; ?>" style="text-align: center; padding: 40px;">
                                    Belum ada data penggajian untuk periode ini
                                </td>
                            </tr>
                            <?php else:
                            while ($row = mysqli_fetch_assoc($result)): 
                            ?>
                            <tr>
                                <?php if ($is_admin): ?>
                                <td><?php echo $row['nip']; ?></td>
                                <td><?php echo $row['nama_lengkap']; ?></td>
                                <?php endif; ?>
                                <td><?php echo bulan_tahun($row['periode_bulan'], $row['periode_tahun']); ?></td>
                                <td><?php echo rupiah($row['gaji_pokok']); ?></td>
                                <td style="color: #e74c3c;"><?php echo rupiah($row['total_potongan']); ?></td>
                                <td style="color: #06beb6;"><?php echo rupiah($row['bonus_penjualan']); ?></td>
                                <td><strong><?php echo rupiah($row['gaji_bersih']); ?></strong></td>
                                <td>
                                    <?php if ($row['status_pembayaran'] == 'dibayar'): ?>
                                    <span class="badge badge-green">DIBAYAR</span>
                                    <?php else: ?>
                                    <span class="badge badge-red">PENDING</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button onclick="lihatDetail(<?php echo $row['id']; ?>)" class="btn btn-primary btn-sm">👁️ Detail</button>
                                    <?php if ($is_admin && $row['status_pembayaran'] == 'pending'): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                        <button type="submit" name="bayar_gaji" class="btn btn-success btn-sm" onclick="return confirm('Konfirmasi pembayaran gaji?')">✅ Bayar</button>
                                    </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php 
                            endwhile;
                            endif;
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Detail Slip Gaji -->
    <div id="modalDetail" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; overflow-y: auto;">
        <div style="background: white; width: 90%; max-width: 600px; margin: 50px auto; padding: 30px; border-radius: 10px;">
            <h3 style="margin-bottom: 20px;">📄 Slip Gaji</h3>
            <div id="detail_content"></div>
            <div style="margin-top: 20px; display: flex; gap: 10px;">
                <button onclick="printSlip()" class="btn btn-primary">🖨️ Cetak</button>
                <button onclick="closeModal()" class="btn btn-danger">❌ Tutup</button>
            </div>
        </div>
    </div>
    
    <script>
        function lihatDetail(id) {
            fetch('get_detail_gaji.php?id=' + id)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('detail_content').innerHTML = html;
                    document.getElementById('modalDetail').style.display = 'block';
                });
        }
        
        function closeModal() {
            document.getElementById('modalDetail').style.display = 'none';
        }
        
        function printSlip() {
            const content = document.getElementById('detail_content').innerHTML;
            const printWindow = window.open('', '', 'width=800,height=600');
            printWindow.document.write('<html><head><title>Slip Gaji</title>');
            printWindow.document.write('<style>body{font-family: Arial; padding: 20px;} table{width: 100%; border-collapse: collapse;} td{padding: 8px; border-bottom: 1px solid #ddd;}</style>');
            printWindow.document.write('</head><body>');
            printWindow.document.write(content);
            printWindow.document.write('</body></html>');
            printWindow.document.close();
            printWindow.print();
        }
    </script>
</body>
</html>
