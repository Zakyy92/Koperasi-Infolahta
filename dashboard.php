<?php 
include 'includes/header.php';

$stmt = $pdo->query("SELECT COUNT(*) as total FROM anggota WHERE status = 'aktif'");
$totalAnggota = $stmt->fetch()['total'];

$stmt = $pdo->query("
    SELECT 
        (SELECT COALESCE(SUM(jumlah), 0) FROM kas WHERE jenis_kas = 'masuk') -
        (SELECT COALESCE(SUM(jumlah), 0) FROM kas WHERE jenis_kas = 'keluar') AS saldo
");
$totalSaldoKas = $stmt->fetch()['saldo'];

$stmt = $pdo->query("SELECT SUM(jumlah_pinjaman) as total FROM pinjaman WHERE status = 'disetujui'");
$totalPinjaman = $stmt->fetch()['total'] ?? 0;
?>

<div class="content-wrapper">
    <!-- judul -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-3 mt-2">
                <div class="col-sm-6">
                    <h2 class="ms-4">Dashboard</h2>
                </div>  
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <!-- ringkasan -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card border-primary text-primary">
                        <div class="card-body">
                            <h5 class="card-title">Total Anggota</h5>
                            <h3 class="card-text"><?php echo number_format($totalAnggota); ?></h3>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card border-dark text-dark">
                        <div class="card-body">
                            <h5 class="card-title">Total Saldo Kas</h5>
                            <h3 class="card-text">Rp <?php echo number_format($totalSaldoKas, 0, ',', '.'); ?></h3>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card border-danger text-danger">
                        <div class="card-body">
                            <h5 class="card-title">Total Pinjaman</h5>
                            <h3 class="card-text">Rp <?php echo number_format($totalPinjaman, 0, ',', '.'); ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- pinjaman terbaru -->
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Pinjaman Terbaru</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover table-bordered">
                                    <thead class="table-primary">
                                        <tr>
                                            <th>Tanggal</th>
                                            <th>Anggota</th>
                                            <th>Jumlah</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $stmt = $pdo->query("
                                            SELECT p.*, a.nama_lengkap 
                                            FROM pinjaman p 
                                            JOIN anggota a ON p.anggota_id = a.id 
                                            ORDER BY p.created_at DESC 
                                            LIMIT 5
                                        ");
                                        while ($row = $stmt->fetch()) {
                                            echo "<tr>";
                                            echo "<td>" . date('d/m/Y', strtotime($row['created_at'])) . "</td>";
                                            echo "<td>" . htmlspecialchars($row['nama_lengkap']) . "</td>";
                                            echo "<td>Rp " . number_format($row['jumlah_pinjaman'], 0, ',', '.') . "</td>";
                                            echo "<td><span class='badge bg-" . 
                                                ($row['status'] == 'pending' ? 'warning' : 
                                                ($row['status'] == 'disetujui' ? 'success' : 
                                                ($row['status'] == 'ditolak' ? 'danger' : 'primary'))) . 
                                                "'>" . ucfirst($row['status']) . "</span></td>";
                                            echo "</tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- kas terbaru -->
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Kas Terbaru</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover table-bordered">
                                    <thead class="table-primary">
                                        <tr>
                                            <th>Tanggal</th>
                                            <th>Keterangan</th>
                                            <th>Jenis</th>
                                            <th>Jumlah</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $stmt = $pdo->query("
                                            SELECT * FROM kas 
                                            ORDER BY tanggal DESC, id DESC 
                                            LIMIT 5
                                        ");
                                        while ($row = $stmt->fetch()) {
                                            echo "<tr>";
                                            echo "<td>" . date('d/m/Y', strtotime($row['tanggal'])) . "</td>";
                                            echo "<td>" . htmlspecialchars($row['keterangan']) . "</td>";
                                            echo "<td><span class='badge bg-" . 
                                                ($row['jenis_kas'] == 'masuk' ? 'success' : 'danger') . 
                                                "'>" . ucfirst($row['jenis_kas']) . "</span></td>";
                                            echo "<td>Rp " . number_format($row['jumlah'], 0, ',', '.') . "</td>";
                                            echo "</tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<style>
    body {
        background-image: url('images/bg1.png');
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
    }

    .card {
        background-color: rgba(255, 255, 255, 0.9);
    }

    @media (max-width: 768px) {
        body {
            background-image: url('images/bg2.png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }
    }

</style>