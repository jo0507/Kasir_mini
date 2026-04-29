<?php
require_once 'config.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$query = "SELECT pg.*, p.nip, p.jabatan, u.nama_lengkap, u.username
          FROM penggajian pg
          JOIN pegawai p ON pg.pegawai_id = p.id
          JOIN users u ON p.user_id = u.id
          WHERE pg.id = $id";
$gaji = mysqli_fetch_assoc(mysqli_query($conn, $query));

if (!$gaji) {
    echo "Data tidak ditemukan";
    exit;
}
?>

<div style="border: 2px solid #333; padding: 20px; font-family: monospace;">
    <div style="text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 15px;">
        <h2 style="margin: 0;">SLIP GAJI PEGAWAI</h2>
        <p style="margin: 5px 0;">MINIMART ABADI</p>
        <p style="margin: 5px 0; font-size: 12px;">Jl. Raya Utama No. 123</p>
    </div>
    
    <table style="width: 100%; margin-bottom: 15px; font-size: 14px;">
        <tr>
            <td width="30%"><strong>NIP</strong></td>
            <td width="5%">:</td>
            <td><?php echo $gaji['nip']; ?></td>
        </tr>
        <tr>
            <td><strong>Nama</strong></td>
            <td>:</td>
            <td><?php echo $gaji['nama_lengkap']; ?></td>
        </tr>
        <tr>
            <td><strong>Jabatan</strong></td>
            <td>:</td>
            <td><?php echo $gaji['jabatan']; ?></td>
        </tr>
        <tr>
            <td><strong>Periode</strong></td>
            <td>:</td>
            <td><strong><?php echo bulan_tahun($gaji['periode_bulan'], $gaji['periode_tahun']); ?></strong></td>
        </tr>
    </table>
    
    <div style="border-top: 1px solid #333; padding-top: 15px; margin-top: 15px;">
        <h4 style="margin-bottom: 10px;">REKAPITULASI ABSENSI</h4>
        <table style="width: 100%; font-size: 13px;">
            <tr>
                <td width="50%">Hadir</td>
                <td width="5%">:</td>
                <td><?php echo $gaji['total_hadir']; ?> hari</td>
            </tr>
            <tr>
                <td>Terlambat</td>
                <td>:</td>
                <td><?php echo $gaji['total_terlambat']; ?> hari</td>
            </tr>
            <tr>
                <td>Izin</td>
                <td>:</td>
                <td><?php echo $gaji['total_izin']; ?> hari</td>
            </tr>
            <tr>
                <td>Sakit</td>
                <td>:</td>
                <td><?php echo $gaji['total_sakit']; ?> hari</td>
            </tr>
            <tr>
                <td>Alfa</td>
                <td>:</td>
                <td><?php echo $gaji['total_alfa']; ?> hari</td>
            </tr>
        </table>
    </div>
    
    <div style="border-top: 1px solid #333; padding-top: 15px; margin-top: 15px;">
        <h4 style="margin-bottom: 10px;">PERHITUNGAN GAJI</h4>
        <table style="width: 100%; font-size: 14px;">
            <tr>
                <td width="50%"><strong>Gaji Pokok</strong></td>
                <td width="5%">:</td>
                <td style="text-align: right;"><strong><?php echo rupiah($gaji['gaji_pokok']); ?></strong></td>
            </tr>
            <tr style="color: #e74c3c;">
                <td>Potongan (Alfa + Izin)</td>
                <td>:</td>
                <td style="text-align: right;">- <?php echo rupiah($gaji['total_potongan']); ?></td>
            </tr>
            <tr style="color: #06beb6;">
                <td>Bonus Penjualan</td>
                <td>:</td>
                <td style="text-align: right;">+ <?php echo rupiah($gaji['bonus_penjualan']); ?></td>
            </tr>
        </table>
    </div>
    
    <div style="border-top: 2px solid #333; padding-top: 15px; margin-top: 15px; background: #f5f5f5; padding: 15px; margin: 15px -20px -20px -20px;">
        <table style="width: 100%; font-size: 18px;">
            <tr>
                <td width="50%"><strong>GAJI BERSIH</strong></td>
                <td width="5%">:</td>
                <td style="text-align: right;"><strong><?php echo rupiah($gaji['gaji_bersih']); ?></strong></td>
            </tr>
        </table>
    </div>
    
    <?php if ($gaji['total_penjualan'] > 0): ?>
    <div style="margin-top: 15px; font-size: 12px; color: #666; padding: 10px; background: #f9f9f9;">
        <em>Total Penjualan Anda: <?php echo rupiah($gaji['total_penjualan']); ?></em>
    </div>
    <?php endif; ?>
    
    <div style="margin-top: 30px; font-size: 11px; color: #666; text-align: center; border-top: 1px dashed #999; padding-top: 15px;">
        <p>Slip gaji ini dicetak otomatis oleh sistem</p>
        <p>Tanggal cetak: <?php echo date('d/m/Y H:i'); ?></p>
    </div>
</div>
