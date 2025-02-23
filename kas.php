<?php
include 'includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$success_msg = '';
$error_msg = '';
if (isset($_SESSION['success'])) {
    $success_msg = $_SESSION['success'];
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    $error_msg = $_SESSION['error'];
    unset($_SESSION['error']);
}

$query_bulan = "
    SELECT DISTINCT DATE_FORMAT(tanggal, '%Y-%m') as bulan 
    FROM kas 
    ORDER BY tanggal DESC
";
$stmt = $pdo->query($query_bulan);
$daftar_bulan = $stmt->fetchAll(PDO::FETCH_COLUMN);

$selected_bulan = isset($_GET['bulan']) ? $_GET['bulan'] : date('m');
$selected_tahun = isset($_GET['tahun']) ? $_GET['tahun'] : date('Y');

$bulan_list = [
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

$tahun_list = range(date('Y'), 2025);
$query = "
    SELECT 
        id,
        nomor,
        tanggal,
        keterangan,
        jenis_kas,
        jumlah
    FROM kas
    WHERE MONTH(tanggal) = ? AND YEAR(tanggal) = ?
    ORDER BY tanggal ASC, id ASC
";
$stmt = $pdo->prepare($query);
$stmt->execute([$selected_bulan, $selected_tahun]);
$kas_list = $stmt->fetchAll();

function getKasNumber($pdo, $jenis) {
    $bulan_ini = date('Y-m', strtotime($_POST['tanggal'])); // Menggunakan tanggal dari form
    $prefix = ($jenis == 'masuk') ? 'KM' : 'KK';
    
    $query = "SELECT COUNT(*) as total FROM kas 
              WHERE jenis_kas = ? 
              AND DATE_FORMAT(tanggal, '%Y-%m') = ?";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$jenis, $bulan_ini]);
    $result = $stmt->fetch();
    
    // Format: KM 1 atau KK 1 
    return $prefix . ' ' . ($result['total'] + 1);
}

function getNamaBulan($bulan) {
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
    return $bulanIndo[$bulan];
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $keterangan = $_POST['keterangan'];
        $jenis_kas = $_POST['jenis_kas'];
        $jumlah = str_replace(',', '', $_POST['jumlah']);
        $tanggal = $_POST['tanggal'];
        $nomor = getKasNumber($pdo, $jenis_kas);

        $stmt = $pdo->prepare("
            INSERT INTO kas (nomor, tanggal, keterangan, jenis_kas, jumlah, created_by)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$nomor, $tanggal, $keterangan, $jenis_kas, $jumlah, $_SESSION['user_id']]);

        header("Location: kas.php?success=Data kas berhasil ditambahkan");
        exit();
    } catch (PDOException $e) {
        $error = "Gagal menambahkan data kas";
    }
}

// Hitung total keseluruhan
$total_masuk = 0;
$total_keluar = 0;
foreach ($kas_list as $kas) {
    if ($kas['jenis_kas'] == 'masuk') {
        $total_masuk += $kas['jumlah'];
    } else {
        $total_keluar += $kas['jumlah'];
    }
}
$saldo = $total_masuk - $total_keluar;
?>

<div class="content-wrapper">
    <!-- judul -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-3 mt-2">
                <div class="col-sm-6">
                    <h2 class="ms-4">Data Kas</h2>
                </div>
                <div class="col-sm-6">
                    <!-- buton -->
                    <div class="me-4 d-flex justify-content-end">
                        <a href="kas_form.php" class="btn btn-primary">
                            <i class="bx bx-plus"></i> Tambah Kas
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <!-- Ringkasan -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card border-success text-success">
                        <div class="card-body">
                            <h5 class="card-title">Total Kas Masuk</h5>
                            <h3 class="card-text">Rp <?php echo number_format($total_masuk, 0, ',', '.'); ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-danger text-danger">
                        <div class="card-body">
                            <h5 class="card-title">Total Kas Keluar</h5>
                            <h3 class="card-text">Rp <?php echo number_format($total_keluar, 0, ',', '.'); ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-dark text-dark">
                        <div class="card-body">
                            <h5 class="card-title">Saldo Kas</h5>
                            <h3 class="card-text">Rp <?php echo number_format($saldo, 0, ',', '.'); ?></h3>
                        </div>
                    </div>
                </div>
            </div>
            
            
            <!-- filter bulan dan tahun -->
            <div class="card mb-1">
                <div class="card-header">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <select name="bulan" class="form-select" onchange="this.form.submit()">
                                <?php foreach ($bulan_list as $key => $value): ?>
                                    <option value="<?php echo $key; ?>" <?php echo $selected_bulan == $key ? 'selected' : ''; ?>>
                                        <?php echo $value; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select name="tahun" class="form-select" onchange="this.form.submit()">
                                <?php foreach ($tahun_list as $t): ?>
                                    <option value="<?php echo $t; ?>" <?php echo $selected_tahun == $t ? 'selected' : ''; ?>>
                                        <?php echo $t; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </form>   
                </div>
            </div>

            <!-- tabel data kas -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover" id="kasTable">
                            <thead class="table-primary">
                                <tr>
                                    <th>No</th>
                                    <th>Tanggal</th>
                                    <th>Nomor</th>
                                    <th>Keterangan</th>
                                    <th>Kas Masuk</th>
                                    <th>Kas Keluar</th>
                                    <th>Saldo</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $no = 1;
                                $saldo_berjalan = 0;
                                foreach ($kas_list as $kas): 
                                    if ($kas['jenis_kas'] == 'masuk') {
                                        $saldo_berjalan += $kas['jumlah'];
                                    } else {
                                        $saldo_berjalan -= $kas['jumlah'];
                                    }
                                ?>
                                    <tr>
                                        <td><?php echo $no++; ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($kas['tanggal'])); ?></td>
                                        <td><?php echo htmlspecialchars($kas['nomor']); ?></td>
                                        <td><?php echo htmlspecialchars($kas['keterangan']); ?></td>
                                        <td><?php echo $kas['jenis_kas'] == 'masuk' ? 'Rp ' . number_format($kas['jumlah'], 0, ',', '.') : '-'; ?></td>
                                        <td><?php echo $kas['jenis_kas'] == 'keluar' ? 'Rp ' . number_format($kas['jumlah'], 0, ',', '.') : '-'; ?></td>
                                        <td>Rp <?php echo number_format($saldo_berjalan, 0, ',', '.'); ?></td>
                                        <td>
                                            <?php if ($_SESSION['level'] == 'admin'): ?>
                                            <button type="button" class="btn btn-danger btn-sm" onclick="confirmDelete(<?php echo $kas['id']; ?>)"><i class="bx bx-trash"></i></button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-total table-primary">
                                    <td colspan="4" class="text-end"><strong>Total Bulan <?php echo getNamaBulan($selected_bulan) . ' ' . $selected_tahun; ?></strong></td>
                                    <td><strong>
                                        <?php 
                                        $total_masuk_bulan = array_sum(array_map(function($item) {
                                            return $item['jenis_kas'] == 'masuk' ? $item['jumlah'] : 0;
                                        }, $kas_list));
                                        echo 'Rp ' . number_format($total_masuk_bulan, 0, ',', '.');
                                        ?>
                                    </strong></td>
                                    <td><strong>
                                        <?php 
                                        $total_keluar_bulan = array_sum(array_map(function($item) {
                                            return $item['jenis_kas'] == 'keluar' ? $item['jumlah'] : 0;
                                        }, $kas_list));
                                        echo 'Rp ' . number_format($total_keluar_bulan, 0, ',', '.');
                                        ?>
                                    </strong></td>
                                    <td colspan="2"><strong>
                                        Rp <?php echo number_format($total_masuk_bulan - $total_keluar_bulan, 0, ',', '.'); ?>
                                    </strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </section>
</div>

<!-- Modal Konfirmasi Hapus -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Konfirmasi Hapus</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Apakah Anda yakin ingin menghapus data kas ini?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <a href="#" id="deleteLink" class="btn btn-danger">Hapus</a>
            </div>
        </div>
    </div>
</div>

<!-- Modal Notifikasi -->
<div class="modal fade" id="notificationModal" tabindex="-1" aria-labelledby="notificationModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="notificationModalLabel">Notifikasi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php if ($success_msg): ?>
                    <div class="alert alert-success" role="alert">
                        <?php echo htmlspecialchars($success_msg); ?>
                    </div>
                <?php endif; ?>
                <?php if ($error_msg): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo htmlspecialchars($error_msg); ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>


<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.10.24/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    $('.kas-table').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Indonesian.json"
        },
        "pageLength": 10,
        "ordering": false
    });
});

function formatNumber(input) {
    let value = input.value.replace(/\D/g, '');
    input.value = new Intl.NumberFormat('id-ID').format(value);
}

function confirmDelete(id) {
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    document.getElementById('deleteLink').href = `kas_delete.php?id=${id}`;
    deleteModal.show();
}

document.addEventListener('DOMContentLoaded', function() {
        const successMsg = "<?php echo $success_msg; ?>";
        const errorMsg = "<?php echo $error_msg; ?>";
        if (successMsg !== '' || errorMsg !== '') {
            const notificationModal = new bootstrap.Modal(document.getElementById('notificationModal'));
            notificationModal.show();
        }
    });
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
        background-color: rgba(255, 255, 255, 0.9);
    }

    .table-total {
        background-color: rgba(0, 191, 255, 0.2)
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

    .accordion-button:not(.collapsed) {
        background-color: #f8f9fa;
        color: #333;
    }
    .kas-table {
        margin-bottom: 0;
    }

    /* Style untuk animasi alert */
    .alert {
        transition: opacity 0.5s ease-in-out;
    }

    .alert.fade-out {
        opacity: 0;
    }
</style> 
