<?php 
include 'includes/header.php';

$id = isset($_GET['id']) ? $_GET['id'] : null;
if (!$id) {
    header("Location: anggota.php");
    exit();
}

// Ambil data anggota
$stmt = $pdo->prepare("SELECT * FROM anggota WHERE id = ?");
$stmt->execute([$id]);
$anggota = $stmt->fetch();

if (!$anggota) {
    header("Location: anggota.php");
    exit();
}

// Ambil data pinjaman
$stmt = $pdo->prepare("SELECT * FROM pinjaman WHERE anggota_id = ? ORDER BY tanggal_pengajuan DESC");
$stmt->execute([$id]);
$pinjaman = $stmt->fetchAll();
?>

<div class="container py-2">
    <div class="row justify-content-center">
        <!-- Data anggota -->
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title mb-0">Data Anggota</h3>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <td>NRP</td>
                            <td>: <?php echo htmlspecialchars($anggota['nrp']); ?></td>
                        </tr>
                        <tr>
                            <td>Nama Lengkap</td>
                            <td>: <?php echo htmlspecialchars($anggota['nama_lengkap']); ?></td>
                        </tr>
                        <tr>
                            <td>Pangkat</td>
                            <td>: <?php echo htmlspecialchars($anggota['pangkat']); ?></td>
                        </tr>
                        <tr>
                            <td>Korps</td>
                            <td>: <?php echo htmlspecialchars($anggota['korps']); ?></td>
                        </tr>
                        <tr>
                            <td>Jabatan</td>
                            <td>: <?php echo htmlspecialchars($anggota['jabatan']); ?></td>
                        </tr>
                        <tr>
                            <td>Status</td>
                            <td>: <span class="badge bg-<?php echo $anggota['status'] == 'aktif' ? 'success' : 'danger'; ?>">
                                <?php echo ucfirst($anggota['status']); ?>
                            </span></td>
                        </tr>
                        <tr>
                            <td>Tanggal Bergabung</td>
                            <td>: <?php echo date('d/m/Y', strtotime($anggota['tanggal_bergabung'])); ?></td>
                        </tr>
                    </table>
                    
                    <div class="d-grid gap-2">
                        <a href="anggota_form.php?id=<?php echo $anggota['id']; ?>" class="btn btn-warning text-white">
                            <i class='bx bx-edit'></i> Edit Data
                        </a>
                        <a href="anggota.php" class="btn btn-secondary">
                            <i class='bx bx-arrow-back'></i> Kembali
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Riwayat Pinjaman -->
        <div class="col-md-7">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">Riwayat Pinjaman</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped ">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Jumlah</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pinjaman as $p): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($p['tanggal_pengajuan'])); ?></td>
                                    <td>Rp <?php echo number_format($p['jumlah_pinjaman']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $p['status'] == 'pending' ? 'warning' : 
                                                ($p['status'] == 'disetujui' ? 'success' : 
                                                ($p['status'] == 'ditolak' ? 'danger' : 'primary')); 
                                        ?>">
                                            <?php echo ucfirst($p['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

