<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: index.php');
    exit;
}

$message = '';

// Proses Tambah User
if (isset($_POST['tambah'])) {
    $username = clean($_POST['username']);
    $password = MD5($_POST['password']);
    $nama_lengkap = clean($_POST['nama_lengkap']);
    $role = clean($_POST['role']);
    
    // Cek username duplikat
    $cek = mysqli_query($conn, "SELECT * FROM users WHERE username = '$username'");
    if (mysqli_num_rows($cek) > 0) {
        $message = "<div class='alert alert-danger'>Username sudah digunakan!</div>";
    } else {
        $query = "INSERT INTO users (username, password, nama_lengkap, role) 
                  VALUES ('$username', '$password', '$nama_lengkap', '$role')";
        
        if (mysqli_query($conn, $query)) {
            $message = "<div class='alert alert-success'>User berhasil ditambahkan!</div>";
        }
    }
}

// Proses Edit User
if (isset($_POST['edit'])) {
    $id = intval($_POST['id']);
    $username = clean($_POST['username']);
    $nama_lengkap = clean($_POST['nama_lengkap']);
    $role = clean($_POST['role']);
    
    $query = "UPDATE users SET 
              username = '$username',
              nama_lengkap = '$nama_lengkap',
              role = '$role'
              WHERE id = $id";
    
    if (mysqli_query($conn, $query)) {
        $message = "<div class='alert alert-success'>User berhasil diupdate!</div>";
    }
}

// Proses Reset Password
if (isset($_POST['reset_password'])) {
    $id = intval($_POST['id']);
    $password_baru = MD5($_POST['password_baru']);
    
    $query = "UPDATE users SET password = '$password_baru' WHERE id = $id";
    
    if (mysqli_query($conn, $query)) {
        $message = "<div class='alert alert-success'>Password berhasil direset!</div>";
    }
}

// Proses Toggle Status
if (isset($_GET['toggle'])) {
    $id = intval($_GET['toggle']);
    $query = "UPDATE users SET status = IF(status='aktif', 'nonaktif', 'aktif') WHERE id = $id";
    mysqli_query($conn, $query);
    header('Location: user.php');
    exit;
}

// Ambil data user
$query = "SELECT * FROM users ORDER BY id ASC";
$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen User - Sistem Kasir</title>
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
            <a href="tutup_kasir.php">🔒 Tutup Kasir</a>
            <a href="laporan.php">📈 Laporan</a>
            <hr style="border-color: rgba(255,255,255,0.1); margin: 10px 0;">
            <a href="pegawai.php">👥 Pegawai</a>
            <a href="absensi.php">📝 Absensi</a>
            <a href="setting_gaji.php">⚙️ Setting Gaji</a>
            <a href="penggajian.php">💵 Penggajian</a>
            <hr style="border-color: rgba(255,255,255,0.1); margin: 10px 0;">
            <a href="user.php" class="active">👤 User</a>
            <a href="logout.php" style="color: #e74c3c;">🚪 Logout</a>
        </nav>
    </div>
    
    <div class="main-content">
        <div class="top-bar">
            <h1>👥 Manajemen User</h1>
            <div class="top-bar-right">
                <span><?php echo tanggal_indonesia(date('Y-m-d')); ?></span>
            </div>
        </div>
        
        <div class="content">
            <?php echo $message; ?>
            
            <div class="card">
                <div class="card-header">
                    <h3>Tambah User Baru</h3>
                </div>
                
                <form method="POST">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Username *</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Password *</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Nama Lengkap *</label>
                            <input type="text" name="nama_lengkap" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Role *</label>
                            <select name="role" class="form-control" required>
                                <option value="kasir">Kasir</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                    </div>
                    
                    <button type="submit" name="tambah" class="btn btn-primary">➕ Tambah User</button>
                </form>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3>Daftar User</h3>
                </div>
                
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Nama Lengkap</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td><strong><?php echo $row['username']; ?></strong></td>
                                <td><?php echo $row['nama_lengkap']; ?></td>
                                <td>
                                    <span class="badge <?php echo $row['role'] == 'admin' ? 'badge-red' : 'badge-blue'; ?>">
                                        <?php echo strtoupper($row['role']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($row['status'] == 'aktif'): ?>
                                        <span class="badge badge-green">Aktif</span>
                                    <?php else: ?>
                                        <span class="badge badge-red">Nonaktif</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($row['id'] != $_SESSION['user_id']): ?>
                                        <a href="?toggle=<?php echo $row['id']; ?>" class="btn btn-warning btn-sm">
                                            <?php echo $row['status'] == 'aktif' ? '⏸️' : '▶️'; ?>
                                        </a>
                                        <button onclick="editUser(<?php echo htmlspecialchars(json_encode($row)); ?>)" class="btn btn-primary btn-sm">✏️ Edit</button>
                                        <button onclick="resetPassword(<?php echo $row['id']; ?>, '<?php echo $row['username']; ?>')" class="btn btn-danger btn-sm">🔑 Reset</button>
                                    <?php else: ?>
                                        <span class="badge badge-blue">Akun Anda</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Edit User -->
    <div id="modalEdit" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div style="background: white; width: 90%; max-width: 600px; margin: 50px auto; padding: 30px; border-radius: 10px;">
            <h3 style="margin-bottom: 20px;">Edit User</h3>
            <form method="POST">
                <input type="hidden" name="id" id="edit_id">
                
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" id="edit_username" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" name="nama_lengkap" id="edit_nama" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>Role</label>
                    <select name="role" id="edit_role" class="form-control" required>
                        <option value="kasir">Kasir</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" name="edit" class="btn btn-primary">💾 Simpan</button>
                    <button type="button" onclick="closeModal('modalEdit')" class="btn btn-danger">❌ Batal</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal Reset Password -->
    <div id="modalReset" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div style="background: white; width: 90%; max-width: 500px; margin: 50px auto; padding: 30px; border-radius: 10px;">
            <h3 style="margin-bottom: 20px;">Reset Password</h3>
            <form method="POST">
                <input type="hidden" name="id" id="reset_id">
                
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" id="reset_username" class="form-control" readonly>
                </div>
                
                <div class="form-group">
                    <label>Password Baru</label>
                    <input type="password" name="password_baru" class="form-control" required>
                </div>
                
                <div class="alert alert-info">
                    ⚠️ Password akan direset. Pastikan untuk memberitahu user password barunya.
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" name="reset_password" class="btn btn-danger">🔑 Reset Password</button>
                    <button type="button" onclick="closeModal('modalReset')" class="btn btn-warning">❌ Batal</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function editUser(data) {
            document.getElementById('edit_id').value = data.id;
            document.getElementById('edit_username').value = data.username;
            document.getElementById('edit_nama').value = data.nama_lengkap;
            document.getElementById('edit_role').value = data.role;
            document.getElementById('modalEdit').style.display = 'block';
        }
        
        function resetPassword(id, username) {
            document.getElementById('reset_id').value = id;
            document.getElementById('reset_username').value = username;
            document.getElementById('modalReset').style.display = 'block';
        }
        
        function closeModal(id) {
            document.getElementById(id).style.display = 'none';
        }
    </script>
</body>
</html>
