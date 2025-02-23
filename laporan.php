<?php 
include 'includes/header.php';

function formatTanggal($dateStr) {
    $bulanIndo = [
        '01' => 'Januari',
        '02' => 'Februari',
        '03' => 'Maret',
        '04' => 'April',
        '05' => 'Mei', 
        '06' => 'Juni', 
        '07' => 'Juli', 
        '08' => 'Agustus', 
        '09' => 'September', 
        '10' => 'Oktober', 
        '11' => 'November', 
        '12' => 'Desember'
    ];
    $tgl = date('d', strtotime($dateStr));
    $bln = date('m', strtotime($dateStr));
    $thn = date('Y', strtotime($dateStr));
    return $tgl . ' ' . $bulanIndo[$bln] . ' ' . $thn;
}

$jenis_laporan = isset($_GET['jenis']) ? $_GET['jenis'] : 'kas_masuk';
$tanggal_awal = isset($_GET['tanggal_awal']) ? $_GET['tanggal_awal'] : date('Y-m-01');
$tanggal_akhir = isset($_GET['tanggal_akhir']) ? $_GET['tanggal_akhir'] : date('Y-m-t');
$search = isset($_GET['search']) ? $_GET['search'] : ''; 

switch ($jenis_laporan) {
    case 'kas_masuk': 
        $query = "
            SELECT 
                tanggal, 
                nomor, 
                keterangan, 
                jumlah AS kas_masuk 
            FROM kas 
            WHERE jenis_kas = 'masuk' AND tanggal BETWEEN ? AND ?
            AND (nomor LIKE ? OR keterangan LIKE ?)
            ORDER BY tanggal ASC
        ";
        break;

    case 'kas_keluar':
        $query = "
            SELECT 
                tanggal, 
                nomor, 
                keterangan, 
                jumlah AS kas_keluar 
            FROM kas 
            WHERE jenis_kas = 'keluar' AND tanggal BETWEEN ? AND ?
            AND (nomor LIKE ? OR keterangan LIKE ?)
            ORDER BY tanggal ASC
        ";
        break;

    case 'kas_masuk_keluar':
        $query = "
            SELECT 
                tanggal, 
                nomor, 
                keterangan, 
                jenis_kas, 
                jumlah 
            FROM kas 
            WHERE tanggal BETWEEN ? AND ?
            AND (nomor LIKE ? OR keterangan LIKE ?)
            ORDER BY tanggal ASC
        ";
        break;
}

$stmt = $pdo->prepare($query);
$searchParam = '%' . $search . '%'; 
$stmt->execute([$tanggal_awal, $tanggal_akhir, $searchParam, $searchParam]);
$data = $stmt->fetchAll();
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-3 mt-2">
                <div class="col-sm-6">
                    <h2 class="ms-4">Laporan <?php echo ($jenis_laporan == 'kas_masuk') ? 'Kas Masuk' : (($jenis_laporan == 'kas_keluar') ? 'Kas Keluar' : 'Kas Keluar Masuk'); ?></h2>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="card mb-1 no-print">
                <div class="card-header">
                    <form method="GET" class="row g-2">
                        <div class="col-md-2">
                            <select name="jenis" class="form-select" onchange="this.form.submit()">
                                <option value="kas_masuk" <?php echo $jenis_laporan == 'kas_masuk' ? 'selected' : ''; ?>>Kas Masuk</option>
                                <option value="kas_keluar" <?php echo $jenis_laporan == 'kas_keluar' ? 'selected' : ''; ?>>Kas Keluar</option>
                                <option value="kas_masuk_keluar" <?php echo $jenis_laporan == 'kas_masuk_keluar' ? 'selected' : ''; ?>>Kas Keluar Masuk</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <input type="date" id="tanggal_awal" name="tanggal_awal" class="form-control" value="<?php echo $tanggal_awal; ?>" onchange="updateMinDate()">
                        </div>
                        <div class="col-md-2">
                            <input type="date" id="tanggal_akhir" name="tanggal_akhir" class="form-control" value="<?php echo $tanggal_akhir; ?>" onchange="this.form.submit()">
                        </div>
                        <div class="col-md-2">
                            <input type="text" name="search" class="form-control" placeholder="Cari..." value="<?php echo htmlspecialchars($search); ?>" onchange="this.form.submit()">
                        </div>
                        <div class="col-md-1">
                            <button type="submit" class="btn btn-primary">Cari</button>
                        </div>
                        <div class="col-md-2">
                            <a href="generate_pdf.php?jenis=<?php echo ($jenis_laporan == 'kas_masuk' ? 'kas_masuk' : 
                                ($jenis_laporan == 'kas_keluar' ? 'kas_keluar' : 'kas_masuk_keluar')); ?>&tanggal_awal=<?php echo $tanggal_awal; ?>&tanggal_akhir=<?php echo $tanggal_akhir; ?>" class="btn btn-secondary" target="_blank">
                                <i class='bx bx-printer'></i> Cetak
                            </a>
                        </div>
                    </form>
                </div>
            </div>
    
            <div class="card">
                <div class="card-body">
                    <div class="text-center mb-4">
                        <h4>INFOLAHTADAM IV / DIPONEGORO</h4>
                        <h5>Laporan <?php echo ($jenis_laporan == 'kas_masuk') ? 'Kas Masuk' : (($jenis_laporan == 'kas_keluar') ? 'Kas Keluar' : 'Kas Keluar Masuk'); ?></h5>
                        <p>Periode: <?php echo formatTanggal($tanggal_awal); ?> s/d <?php echo formatTanggal($tanggal_akhir); ?></p>
                    </div>

                    <!-- Tabel laporan -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover">
                            <?php if ($jenis_laporan == 'kas_masuk'): ?>
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Tanggal</th>
                                    <th>Keterangan</th>
                                    <th>Nomor</th>
                                    <th>Kas Masuk</th>
                                    <th>Saldo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $no = 1;
                                $total_kas_masuk = 0;
                                $saldo = 0;
                                foreach ($data as $row): 
                                    $total_kas_masuk += $row['kas_masuk']; 
                                    $saldo += $row['kas_masuk'];
                                ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($row['tanggal'])); ?></td>
                                    <td><?php echo htmlspecialchars($row['keterangan']); ?></td>
                                    <td><?php echo htmlspecialchars($row['nomor']); ?></td>
                                    <td>Rp <?php echo number_format($row['kas_masuk']); ?></td>
                                    <td>Rp <?php echo number_format($saldo); ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <tr class="table-primary">
                                    <td colspan="4" class="text-end"><strong>Total Bulan <?php echo date('d F Y', strtotime($tanggal_awal)) . ' - ' . date('d F Y', strtotime($tanggal_akhir)); ?></strong></td>
                                    <td><strong>Rp <?php echo number_format($total_kas_masuk); ?></strong></td>
                                    <td><strong>Rp <?php echo number_format($saldo); ?></strong></td>
                                </tr>
                            </tbody>
    
                            <?php elseif ($jenis_laporan == 'kas_masuk_keluar'): ?>
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Tanggal</th>
                                    <th>Keterangan</th>
                                    <th>Nomor</th>
                                    <th>Kas Masuk</th>
                                    <th>Kas Keluar</th>
                                    <th>Saldo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $no = 1;
                                $total_kas_masuk = 0;
                                $total_kas_keluar = 0;
                                $saldo = 0;
                                foreach ($data as $kas): 
                                    if ($kas['jenis_kas'] == 'masuk') {
                                        $total_kas_masuk += $kas['jumlah'];
                                        $saldo += $kas['jumlah'];
                                    } else {
                                        $total_kas_keluar += $kas['jumlah'];
                                        $saldo -= $kas['jumlah'];
                                    }
                                ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($kas['tanggal'])); ?></td>
                                    <td><?php echo htmlspecialchars($kas['keterangan']); ?></td>
                                    <td><?php echo htmlspecialchars($kas['nomor']); ?></td>
                                    <td><?php echo $kas['jenis_kas'] == 'masuk' ? 'Rp ' . number_format($kas['jumlah'], 0, ',', '.') : '-'; ?></td>
                                    <td><?php echo $kas['jenis_kas'] == 'keluar' ? 'Rp ' . number_format($kas['jumlah'], 0, ',', '.') : '-'; ?></td>
                                    <td>Rp <?php echo ($saldo < 0) ? number_format(abs($saldo)) : number_format($saldo); ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <tr class="table-primary">
                                    <td colspan="4" class="text-end"><strong>Total Bulan <?php echo date('d F Y', strtotime($tanggal_awal)) . ' - ' . date('d F Y', strtotime($tanggal_akhir)); ?></strong></td>
                                    <td><strong>Rp <?php echo number_format($total_kas_masuk, 0, ',', '.'); ?></strong></td>
                                    <td><strong>Rp <?php echo number_format($total_kas_keluar, 0, ',', '.'); ?></strong></td>
                                    <td><strong>Rp <?php echo number_format(abs($total_kas_masuk - $total_kas_keluar), 0, ',', '.'); ?></strong></td>
                                </tr>
                            </tbody>
                            <?php elseif ($jenis_laporan == 'kas_keluar'): ?>
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Tanggal</th>
                                    <th>Keterangan</th>
                                    <th>Nomor</th>
                                    <th>Kas Keluar</th>
                                    <th>Saldo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $no = 1;
                                $total_kas_keluar = 0;
                                $saldo = 0;
                                foreach ($data as $kas_keluar): 
                                    $total_kas_keluar += $kas_keluar['kas_keluar']; 
                                    $saldo -= $kas_keluar['kas_keluar'];
                                ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($kas_keluar['tanggal'])); ?></td>
                                    <td><?php echo htmlspecialchars($kas_keluar['keterangan']); ?></td>
                                    <td><?php echo htmlspecialchars($kas_keluar['nomor']); ?></td>
                                    <td>Rp <?php echo number_format($kas_keluar['kas_keluar']); ?></td>
                                    <td>Rp <?php echo ($saldo < 0) ? number_format(abs($saldo)) : number_format($saldo); ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <tr class="table-primary">
                                    <td colspan="4" class="text-end"><strong>Total Bulan <?php echo date('d F Y', strtotime($tanggal_awal)) . ' - ' . date('d F Y', strtotime($tanggal_akhir)); ?></strong></td>
                                    <td><strong>Rp <?php echo number_format($total_kas_keluar); ?></strong></td>
                                    <td><strong>Rp <?php echo number_format(abs($saldo)); ?></strong></td>
                                </tr>
                            </tbody>
                            <?php else: ?>
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Tanggal</th>
                                    <th>NRP</th>
                                    <th>Nama</th>
                                    <th>Pangkat</th>
                                    <th>Jumlah Bayar</th>
                                    <th>Petugas</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $no = 1;
                                $total = 0;
                                foreach ($data as $row): 
                                    $total += $row['jumlah_bayar'];
                                ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($row['tanggal_bayar'])); ?></td>
                                    <td><?php echo $row['nrp']; ?></td>
                                    <td><?php echo $row['nama_lengkap']; ?></td>
                                    <td><?php echo $row['pangkat']; ?></td>
                                    <td>Rp <?php echo number_format($row['jumlah_bayar']); ?></td>
                                    <td><?php echo $row['petugas']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <tr>
                                    <td colspan="5" class="text-end"><strong>Total</strong></td>
                                    <td colspan="2"><strong>Rp <?php echo number_format($total); ?></strong></td>
                                </tr>
                            </tbody>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
<script>
    function updateMinDate() {
        var tanggalAwal = document.getElementById('tanggal_awal').value;
        var tanggalAkhir = document.getElementById('tanggal_akhir');

        tanggalAkhir.min = tanggalAwal;

        if (tanggalAkhir.value < tanggalAwal) {
            tanggalAkhir.value = tanggalAwal;
        }
    }

    document.getElementById('tanggal_awal').addEventListener('change', updateMinDate);
</script>
<style>
    body {
        background-image: url('images/bg1.png');
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        background-attachment: fixed;
    }

    .card {
        background-color: rgba(255, 255, 255, 1); 
    }

    .table {
        background-color: #fff; 
    }

    .table th {
        background-color: #f8f9fa; 
    }

    .table-bordered {
        border: 1px solid #dee2e6;
    }

    .table-bordered td, 
    .table-bordered th {
        border: 1px solid #dee2e6;
    }

    @media (max-width: 768px) {
        body {
            background-image: url('images/bg2.png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
        }
    }

    @media print {
        .no-print {
            display: none;
        }
        .container {
            width: 100%;
            max-width: none;
        }
        .card {
            background-color: #fff;
            border: none;
        }
        .table {
            background-color: #fff;
        }
    }
</style>