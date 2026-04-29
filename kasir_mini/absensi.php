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
$pegawai_id_filter = $is_admin ? 0 : ($pegawai_data ? $pegawai_data['id'] : 0);

// Proses input absensi manual (admin only)
if ($is_admin && isset($_POST['input_manual'])) {
    $pegawai_id = intval($_POST['pegawai_id']);
    $tanggal = clean($_POST['tanggal']);
    $jam_masuk = clean($_POST['jam_masuk']);
    $jam_pulang = clean($_POST['jam_pulang']);
    $status = clean($_POST['status']);
    $keterangan = clean($_POST['keterangan']);
    
    // Cek duplikat
    $cek = mysqli_query($conn, "SELECT * FROM absensi WHERE pegawai_id = $pegawai_id AND tanggal = '$tanggal'");
    if (mysqli_num_rows($cek) > 0) {
        $message = "<div class='alert alert-danger'>Absensi untuk tanggal tersebut sudah ada!</div>";
    } else {
        $query = "INSERT INTO absensi (pegawai_id, tanggal, jam_masuk, jam_pulang, status, keterangan) 
                  VALUES ($pegawai_id, '$tanggal', " . ($jam_masuk ? "'$jam_masuk'" : "NULL") . ", " . ($jam_pulang ? "'$jam_pulang'" : "NULL") . ", '$status', '$keterangan')";
        
        if (mysqli_query($conn, $query)) {
            $message = "<div class='alert alert-success'>Absensi berhasil ditambahkan!</div>";
        }
    }
}

// Filter
$tanggal_dari = isset($_GET['dari']) ? $_GET['dari'] : date('Y-m-01');
$tanggal_sampai = isset($_GET['sampai']) ? $_GET['sampai'] : date('Y-m-d');
$filter_pegawai = isset($_GET['pegawai']) ? intval($_GET['pegawai']) : 0;

// Query absensi
$where = "WHERE a.tanggal BETWEEN '$tanggal_dari' AND '$tanggal_sampai'";
if (!$is_admin && $pegawai_id_filter) {
    $where .= " AND a.pegawai_id = $pegawai_id_filter";
} elseif ($filter_pegawai > 0) {
    $where .= " AND a.pegawai_id = $filter_pegawai";
}

$query = "SELECT a.*, p.nip, p.jabatan, u.nama_lengkap 
          FROM absensi a
          JOIN pegawai p ON a.pegawai_id = p.id
          JOIN users u ON p.user_id = u.id
          $where
          ORDER BY a.tanggal DESC, a.id DESC";
$result = mysqli_query($conn, $query);

// Statistik
$query_stats = "SELECT 
                COUNT(*) as total_absensi,
                SUM(CASE WHEN status = 'HADIR' THEN 1 ELSE 0 END) as total_hadir,
                SUM(CASE WHEN status = 'TERLAMBAT' THEN 1 ELSE 0 END) as total_terlambat,
                SUM(CASE WHEN status = 'IZIN' THEN 1 ELSE 0 END) as total_izin,
                SUM(CASE WHEN status = 'SAKIT' THEN 1 ELSE 0 END) as total_sakit,
                SUM(CASE WHEN status = 'ALFA' THEN 1 ELSE 0 END) as total_alfa
                FROM absensi a $where";
$stats = mysqli_fetch_assoc(mysqli_query($conn, $query_stats));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Absensi Pegawai - Sistem Kasir</title>
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
            <a href="absensi.php" class="active">📝 Absensi</a>
            <?php if ($_SESSION['role'] == 'admin'): ?>
            <a href="setting_gaji.php">⚙️ Setting Gaji</a>
            <?php endif; ?>
            <a href="penggajian.php">💵 <?php echo $_SESSION['role'] == 'admin' ? 'Penggajian' : 'Slip Gaji'; ?></a>
            <?php if ($_SESSION['role'] == 'admin'): ?>
            <hr style="border-color: rgba(255,255,255,0.1);">
            <a href="user.php">👤 User</a>
            <?php endif; ?>
            <a href="logout.php" style="color: #e74c3c;">🚪 Logout</a>
        </nav>
    </div>
    
    <div class="main-content">
        <div class="top-bar">
            <h1>📝 Absensi Pegawai</h1>
            <div class="top-bar-right">
                <span><?php echo tanggal_indonesia(date('Y-m-d')); ?></span>
            </div>
        </div>
        
        <div class="content">
            <?php echo $message; ?>
            
            <?php if ($is_admin): ?>
            <div class="alert alert-info">
                ℹ️ <strong>Sistem Absensi IoT:</strong> Absensi dilakukan otomatis melalui RFID atau fingerprint. 
                Input manual hanya untuk koreksi atau data pegawai yang izin/sakit.
            </div>
            <?php endif; ?>
            
            <div class="stats-grid">
                <div class="stat-card blue">
                    <div class="stat-icon">✅</div>
                    <div class="stat-details">
                        <h3><?php echo $stats['total_hadir']; ?></h3>
                        <p>Hadir</p>
                    </div>
                </div>
                
                <div class="stat-card orange">
                    <div class="stat-icon">⏰</div>
                    <div class="stat-details">
                        <h3><?php echo $stats['total_terlambat']; ?></h3>
                        <p>Terlambat</p>
                    </div>
                </div>
                
                <div class="stat-card green">
                    <div class="stat-icon">📋</div>
                    <div class="stat-details">
                        <h3><?php echo $stats['total_izin']; ?></h3>
                        <p>Izin</p>
                    </div>
                </div>
                
                <div class="stat-card red">
                    <div class="stat-icon">❌</div>
                    <div class="stat-details">
                        <h3><?php echo $stats['total_alfa']; ?></h3>
                        <p>Alfa</p>
                    </div>
                </div>
            </div>
            
            <?php if ($is_admin): ?>
            <div class="card">
                <div class="card-header">
                    <h3>Input Absensi Manual</h3>
                </div>
                
                <form method="POST">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Pegawai *</label>
                            <select name="pegawai_id" class="form-control" required>
                                <option value="">-- Pilih Pegawai --</option>
                                <?php
                                $q_peg = mysqli_query($conn, "SELECT p.id, p.nip, u.nama_lengkap FROM pegawai p JOIN users u ON p.user_id = u.id WHERE p.status_kerja = 'aktif'");
                                while ($peg = mysqli_fetch_assoc($q_peg)):
                                ?>
                                <option value="<?php echo $peg['id']; ?>"><?php echo $peg['nama_lengkap']; ?> (<?php echo $peg['nip']; ?>)</option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Tanggal *</label>
                            <input type="date" name="tanggal" class="form-control" required value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Jam Masuk</label>
                            <input type="time" name="jam_masuk" class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label>Jam Pulang</label>
                            <input type="time" name="jam_pulang" class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label>Status *</label>
                            <select name="status" class="form-control" required>
                                <option value="HADIR">HADIR</option>
                                <option value="TERLAMBAT">TERLAMBAT</option>
                                <option value="IZIN">IZIN</option>
                                <option value="SAKIT">SAKIT</option>
                                <option value="ALFA">ALFA</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Keterangan</label>
                            <input type="text" name="keterangan" class="form-control" placeholder="Opsional">
                        </div>
                    </div>
                    
                    <button type="submit" name="input_manual" class="btn btn-primary">➕ Tambah Absensi</button>
                </form>
            </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <h3>Filter Data Absensi</h3>
                </div>
                
                <form method="GET" style="display: flex; gap: 10px; align-items: end; flex-wrap: wrap;">
                    <div class="form-group" style="margin: 0;">
                        <label>Dari Tanggal</label>
                        <input type="date" name="dari" class="form-control" value="<?php echo $tanggal_dari; ?>">
                    </div>
                    
                    <div class="form-group" style="margin: 0;">
                        <label>Sampai Tanggal</label>
                        <input type="date" name="sampai" class="form-control" value="<?php echo $tanggal_sampai; ?>">
                    </div>
                    
                    <?php if ($is_admin): ?>
                    <div class="form-group" style="margin: 0;">
                        <label>Pegawai</label>
                        <select name="pegawai" class="form-control">
                            <option value="0">Semua Pegawai</option>
                            <?php
                            $q_peg = mysqli_query($conn, "SELECT p.id, p.nip, u.nama_lengkap FROM pegawai p JOIN users u ON p.user_id = u.id WHERE p.status_kerja = 'aktif'");
                            while ($peg = mysqli_fetch_assoc($q_peg)):
                            ?>
                            <option value="<?php echo $peg['id']; ?>" <?php echo $filter_pegawai == $peg['id'] ? 'selected' : ''; ?>>
                                <?php echo $peg['nama_lengkap']; ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    
                    <button type="submit" class="btn btn-primary">🔍 Filter</button>
                    <a href="absensi.php" class="btn btn-warning">🔄 Reset</a>
                </form>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3>Data Absensi</h3>
                </div>
                
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <?php if ($is_admin): ?>
                                <th>NIP</th>
                                <th>Nama</th>
                                <?php endif; ?>
                                <th>Tanggal</th>
                                <th>Jam Masuk</th>
                                <th>Jam Pulang</th>
                                <th>Durasi</th>
                                <th>Status</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <?php if ($is_admin): ?>
                                <td><?php echo $row['nip']; ?></td>
                                <td><?php echo $row['nama_lengkap']; ?></td>
                                <?php endif; ?>
                                <td><?php echo date('d/m/Y', strtotime($row['tanggal'])); ?></td>
                                <td><?php echo $row['jam_masuk'] ? date('H:i', strtotime($row['jam_masuk'])) : '-'; ?></td>
                                <td><?php echo $row['jam_pulang'] ? date('H:i', strtotime($row['jam_pulang'])) : '-'; ?></td>
                                <td>
                                    <?php 
                                    if ($row['jam_masuk'] && $row['jam_pulang']) {
                                        echo hitungDurasiKerja($row['jam_masuk'], $row['jam_pulang']) . ' jam';
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    $badge_class = 'badge-green';
                                    if ($row['status'] == 'TERLAMBAT') $badge_class = 'badge-blue';
                                    if ($row['status'] == 'IZIN') $badge_class = 'badge-blue';
                                    if ($row['status'] == 'SAKIT') $badge_class = 'badge-blue';
                                    if ($row['status'] == 'ALFA') $badge_class = 'badge-red';
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?>"><?php echo $row['status']; ?></span>
                                </td>
                                <td><?php echo $row['keterangan'] ?: '-'; ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
