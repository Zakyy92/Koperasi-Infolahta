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
           u.nama_lengkap as approved_by_name
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
    SELECT a.*, u.nama_lengkap as petugas,
           @running_total := @running_total + a.jumlah_bayar as total_bayar_kumulatif,
           @bunga_total := @bunga_total + CASE 
               WHEN ? = 'baru' THEN
                   (? * ? / 100)
               ELSE 
                   ?
           END as total_bunga_kumulatif
    FROM (SELECT @running_total := 0, @bunga_total := 0) r,
    angsuran a
    JOIN users u ON a.created_by = u.id
    WHERE a.pinjaman_id = ?
    ORDER BY a.tanggal_bayar ASC
");

// Bind parameters untuk perhitungan bunga
$stmt->execute([
    $pinjaman['jenis_pinjaman'],
    $pinjaman['jumlah_pinjaman'],
    $pinjaman['bunga_persen'],
    $pinjaman['bunga_per_bulan'],
    $id
]);
$angsuran = $stmt->fetchAll();

// Hitung total yang sudah dibayar
$stmt = $pdo->prepare("SELECT COALESCE(SUM(jumlah_bayar), 0) as total_bayar FROM angsuran WHERE pinjaman_id = ?");
$stmt->execute([$id]);
$total_bayar = $stmt->fetch()['total_bayar'];

// Hitung angsuran ke berapa
$stmt = $pdo->prepare("SELECT COUNT(*) as angsuran_ke FROM angsuran WHERE pinjaman_id = ?");
$stmt->execute([$id]);
$angsuran_ke = $stmt->fetch()['angsuran_ke'] + 1;

// Hitung total pinjaman dan bunga
$total_pinjaman = $pinjaman['jumlah_pinjaman'];
$bunga_bulanan = 0;

// Hitung bunga berdasarkan jenis pinjaman
if ($pinjaman['anggota_id']) {
    if ($pinjaman['jenis_pinjaman'] === 'baru') {
        $bunga_bulanan = ($pinjaman['jumlah_pinjaman'] * $pinjaman['bunga_persen'] / 100);
    } else {
        $bunga_bulanan = $pinjaman['bunga_per_bulan'];
    }
}

// Total bunga hanya untuk 1 kali angsuran
$total_bunga = $bunga_bulanan;

// Total pinjaman termasuk bunga
$total_pinjaman = $pinjaman['jumlah_pinjaman'] + $total_bunga;

// Hitung sisa pembayaran
$sisa_pembayaran = $total_pinjaman - $total_bayar;
?>

<div class="container py-2">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Detail Angsuran</h5>
                </div>
                <div class="card-body">
                    <!-- Informasi Pinjaman -->
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td width="150">NRP</td>
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
                                        <!-- untuk spasi kosong -->
                                    </td>
                                </tr>
                                <tr>
                                    <td>Jangka Waktu</td>
                                    <td>: <?php echo htmlspecialchars($pinjaman['jangka_waktu']); ?> bulan</td>
                                </tr>
                                <tr>
                                    <td>Bunga</td>
                                    <td>: <?php if ($pinjaman['jenis_pinjaman'] === 'baru'): ?>
                                            <?php echo $pinjaman['bunga_persen']; ?>%
                                        <?php else: ?>
                                            Rp <?php echo number_format($pinjaman['bunga_per_bulan'], 0, ',', '.'); ?> per bulan
                                        <?php endif; ?>
                                    </td>
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
                        <div class="alert alert-info">
                            <div class="row">
                                <div class="col-md-3">
                                    <strong>Total Angsuran:</strong><br>
                                    Rp <?php echo number_format($total_pinjaman, 0, ',', '.'); ?>
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
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Tanggal</th>
                                    <th>Jumlah Bayar</th>
                                    <th>Total Angsuran</th>
                                    <th>Total Bunga</th>
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
                                    <td>Rp <?php echo number_format($row['total_bayar_kumulatif']); ?></td>
                                    <td>Rp <?php echo number_format($row['total_bunga_kumulatif']); ?></td>
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
                    <a href="angsuran.php" class="btn btn-secondary">
                        <i class='bx bx-arrow-back'></i> Kembali
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>