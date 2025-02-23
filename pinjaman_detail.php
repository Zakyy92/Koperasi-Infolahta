<?php
include 'includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$id = isset($_GET['id']) ? $_GET['id'] : null;
if (!$id) {
    header("Location: pinjaman.php");
    exit();
}

$stmt = $pdo->prepare("
    SELECT p.*, 
           CASE WHEN p.anggota_id IS NULL THEN 'Dinas' ELSE a.nrp END as nrp,
           CASE WHEN p.anggota_id IS NULL THEN 'Dinas' ELSE a.nama_lengkap END as nama_lengkap,
           CASE WHEN p.anggota_id IS NULL THEN '-' ELSE a.pangkat END as pangkat,
           u.nama_lengkap as approved_by_name,
           p.jenis_pinjaman
    FROM pinjaman p 
    LEFT JOIN anggota a ON p.anggota_id = a.id
    LEFT JOIN users u ON p.approved_by = u.id
    WHERE p.id = ?
");
$stmt->execute([$id]);
$pinjaman = $stmt->fetch();

if (!$pinjaman) {
    header("Location: pinjaman.php");
    exit();
}

$stmt = $pdo->prepare("
    SELECT a.*, u.nama_lengkap as petugas
    FROM angsuran a
    JOIN users u ON a.created_by = u.id
    WHERE a.pinjaman_id = ?
    ORDER BY a.tanggal_bayar DESC
");
$stmt->execute([$id]);
$angsuran = $stmt->fetchAll();

// Ambil data angsuran
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as jumlah_angsuran,
        COALESCE(SUM(jumlah_bayar), 0) as total_bayar 
    FROM angsuran 
    WHERE pinjaman_id = ?
");
$stmt->execute([$id]);
$angsuran_data = $stmt->fetch();
$jumlah_angsuran = $angsuran_data['jumlah_angsuran'];
$total_bayar = $angsuran_data['total_bayar'];

// Hitung total pinjaman dan bunga berdasarkan jenis pinjaman
if (!$pinjaman['anggota_id']) {
    // Untuk peminjam dinas - tanpa bunga
    $bunga_bulanan = 0;
    $total_bunga = 0;
    $total_angsuran = $pinjaman['jumlah_pinjaman'];
} else if ($pinjaman['jenis_pinjaman'] === 'baru') {
    // Perhitungan untuk pinjaman baru anggota
    $bunga_bulanan = ($pinjaman['jumlah_pinjaman'] * 0.8 / 100); // Tetapkan bunga 0.8%
    
    // Cek apakah ini pelunasan sebelum jatuh tempo
    if ($pinjaman['status'] == 'lunas' && $jumlah_angsuran < $pinjaman['jangka_waktu']) {
        // Tambahkan bunga sebulan ke depan
        $total_bunga = $bunga_bulanan * ($jumlah_angsuran + 1);
    } else {
        $total_bunga = $bunga_bulanan * $jumlah_angsuran;
        // Jika belum ada angsuran, set total bunga ke bunga bulanan
        if ($jumlah_angsuran == 0) {
            $total_bunga = $bunga_bulanan;
        }
    }
    
    $total_angsuran = $pinjaman['jumlah_pinjaman'] + $total_bunga;
} else {
    // Perhitungan untuk pinjaman lama anggota
    $bunga_bulanan = $pinjaman['bunga_per_bulan'];
    
    // Cek apakah ini pelunasan sebelum jatuh tempo
    if ($pinjaman['status'] == 'lunas' && $jumlah_angsuran < $pinjaman['jangka_waktu']) {
        // Tambahkan bunga sebulan ke depan
        $total_bunga = $bunga_bulanan * ($jumlah_angsuran + 1);
    } else {
        $total_bunga = $bunga_bulanan * $jumlah_angsuran;
        // Jika belum ada angsuran, set total bunga ke bunga bulanan
        if ($jumlah_angsuran == 0) {
            $total_bunga = $bunga_bulanan;
        }
    }
    
    $total_angsuran = $pinjaman['jumlah_pinjaman'] + $total_bunga;
}

// Hitung sisa pembayaran
$sisa_pembayaran = $pinjaman['status'] == 'lunas' ? 0 : $total_angsuran - $total_bayar;
?>

<div class="container py-2">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card mb-1">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Detail Pinjaman</h5>
                </div>
                <div class="card-body">
                    <!-- Informasi Pinjaman -->
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td width="180">NRP</td>
                                    <td>: <?php echo htmlspecialchars($pinjaman['nrp']); ?></td>
                                </tr>
                                <tr>
                                    <td>Nama</td>
                                    <td>: <?php echo htmlspecialchars($pinjaman['nama_lengkap']); ?></td>
                                </tr>
                                <?php if ($pinjaman['nrp'] !== 'Dinas'): ?>
                                <tr>
                                    <td>Pangkat</td>
                                    <td>: <?php echo htmlspecialchars($pinjaman['pangkat']); ?></td>
                                </tr>
                                <?php endif; ?>
                                <tr>
                                    <td>
                                        <!-- untuk spasi jarak -->
                                    </td>
                                </tr>
                                <tr>
                                    <td>Total Pinjaman</td>
                                    <td>: Rp <?php echo number_format($pinjaman['jumlah_pinjaman'], 0, ',', '.'); ?></td>
                                </tr>
                                <tr>
                                    <td width="180">Bunga</td>
                                    <td>:<?php if ($pinjaman['jenis_pinjaman'] === 'baru'): ?>
                                            <?php echo $pinjaman['bunga_persen']; ?>%
                                        <?php else: ?>
                                            Rp <?php echo number_format($pinjaman['bunga_per_bulan'], 0, ',', '.'); ?> per bulan
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Total Bunga</td>
                                    <td>: Rp <?php echo number_format($total_bunga, 0, ',', '.'); ?></td>
                                </tr>
                                <tr>
                                    <td>Angsuran per Bulan</td>
                                    <td>: Rp <?php echo number_format($pinjaman['angsuran_per_bulan'], 0, ',', '.'); ?></td>
                                </tr>
                                <tr>
                                    <td>Total Angsuran</td>
                                    <td>: Rp <?php echo number_format($total_angsuran, 0, ',', '.'); ?></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td width="150">Status</td>
                                    <td>: <span class="badge bg-<?php 
                                        echo $pinjaman['status'] == 'pending' ? 'warning' : 
                                            ($pinjaman['status'] == 'disetujui' ? 'success' : 
                                            ($pinjaman['status'] == 'ditolak' ? 'danger' : 'primary')); 
                                    ?>"><?php echo ucfirst($pinjaman['status']); ?></span></td>
                                </tr>
                                <tr>
                                    <td>Tanggal Pengajuan</td>
                                    <td>: <?php echo date('d/m/Y', strtotime($pinjaman['tanggal_pengajuan'])); ?></td>
                                </tr>
                                <?php if ($pinjaman['approved_by']): ?>
                                <tr>
                                    <td>Disetujui Oleh</td>
                                    <td>: <?php echo htmlspecialchars($pinjaman['approved_by_name']); ?></td>
                                </tr>
                                <?php endif; ?>
                            </table>
                        </div>
                    </div>
                    <a href="pinjaman.php" class="btn btn-secondary">
                        <i class='bx bx-arrow-back'></i> Kembali
                    </a>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Riwayat Angsuran</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <div class="row">
                            <div class="col-md-3">
                                <strong>Total Angsuran:</strong><br>
                                Rp <?php echo number_format($total_angsuran, 0, ',', '.'); ?>
                            </div>
                            <div class="col-md-3">
                                <strong>Sudah Dibayar:</strong><br>
                                Rp <?php echo number_format($total_bayar, 0, ',', '.'); ?>
                            </div>
                            <div class="col-md-3">
                                <strong>Sisa Pembayaran:</strong><br>
                                Rp <?php echo number_format($sisa_pembayaran, 0, ',', '.'); ?>
                            </div>
                            <div class="col-md-3">
                                <strong>Total Bunga:</strong><br>
                                Rp <?php echo number_format($total_bunga, 0, ',', '.'); ?>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead >
                                <tr>
                                    <th>No</th>
                                    <th>Tanggal</th>
                                    <th>Jumlah Bayar</th>
                                    <th>Keterangan</th>
                                    <th>Petugas</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($angsuran as $index => $row): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($row['tanggal_bayar'])); ?></td>
                                    <td>Rp <?php echo number_format($row['jumlah_bayar']); ?></td>
                                    <td>Angsuran ke-<?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($row['petugas']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($angsuran)): ?>
                                <tr>
                                    <td colspan="5" class="text-center">Belum ada data angsuran</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>