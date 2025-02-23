<?php
include 'includes/header.php';

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

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$where = '';
if (!empty($search)) {
    $where = "AND (
        CASE 
            WHEN p.anggota_id IS NULL THEN 'Dinas' 
            ELSE ang.nrp 
        END LIKE :search 
        OR 
        CASE 
            WHEN p.anggota_id IS NULL THEN 'Dinas' 
            ELSE ang.nama_lengkap 
        END LIKE :search
    )";
}

$query = "
    SELECT 
        p.id as pinjaman_id,
        CASE WHEN p.anggota_id IS NULL THEN 'Dinas' ELSE ang.nrp END as nrp,
        CASE WHEN p.anggota_id IS NULL THEN 'Dinas' ELSE ang.nama_lengkap END as nama_lengkap,
        p.jumlah_pinjaman,
        p.anggota_id,
        p.bunga_persen,
        p.bunga_per_bulan,
        p.jangka_waktu,
        p.jenis_pinjaman,
        p.tanggal_pengajuan as tanggal_bayar,
        COALESCE(SUM(a.jumlah_bayar), 0) as total_bayar,
        COUNT(a.id) as total_angsuran,
        MAX(a.id) as angsuran_id,
        p.status
    FROM pinjaman p
    LEFT JOIN anggota ang ON p.anggota_id = ang.id
    LEFT JOIN angsuran a ON a.pinjaman_id = p.id
    WHERE p.status = 'disetujui' $where
    GROUP BY p.id
    ORDER BY p.tanggal_pengajuan DESC
";

$stmt = $pdo->prepare($query);
if (!empty($search)) {
    $stmt->bindValue(':search', "%$search%");
}
$stmt->execute();
$angsuran_list = $stmt->fetchAll();

// Fungsi untuk menghitung total pinjaman dengan bunga
function getTotalPinjaman($pinjaman) {
    if (!$pinjaman['anggota_id']) {
        return $pinjaman['jumlah_pinjaman']; // Untuk dinas, tanpa bunga
    }
    
    if ($pinjaman['jenis_pinjaman'] === 'lama') {
        // Untuk pinjaman lama
        $bunga_bulanan = $pinjaman['bunga_per_bulan'];
        $total = $pinjaman['jumlah_pinjaman'];
        
        // Tambahkan bunga untuk setiap bulan termasuk bulan pertama
        for ($i = 0; $i <= $pinjaman['total_angsuran']; $i++) {
            $total += $bunga_bulanan;
        }
        
        return $total;
    }
    
    // Untuk pinjaman baru
    $bunga_bulanan = ($pinjaman['jumlah_pinjaman'] * $pinjaman['bunga_persen'] / 100);
    $total = $pinjaman['jumlah_pinjaman'] + $bunga_bulanan;
    
    // Tambahkan bunga untuk setiap angsuran yang sudah dibayar
    if ($pinjaman['total_angsuran'] > 0) {
        for ($i = 0; $i < $pinjaman['total_angsuran']; $i++) {
            $total += $bunga_bulanan;
        }
    }
    
    return $total;
}
?>

<div class="content-wrapper">
    <!-- judul -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-3 mt-2">
                <div class="col-sm-6">
                    <h2 class="ms-4">Data Angsuran</h2>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <!-- Form Pencarian -->
            <div class="card mb-1">
                <div class="card-header">
                    <form method="GET" class="row g-3">
                        <div class="col-md-10">
                            <input type="text" name="search" class="form-control" 
                                   placeholder="Cari berdasarkan NRP atau Nama Anggota..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Cari</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- table -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover" id="angsuranTable">
                            <thead class="table-primary">
                                <tr>
                                    <th>No</th>
                                    <th>NRP</th>
                                    <th>Nama Anggota</th>
                                    <th>Tanggal Pinjaman</th>
                                    <th>Jumlah Bayar</th>
                                    <th>Total Pinjaman</th>
                                    <th>Sisa Pinjaman</th>
                                    <th>Keterangan</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($angsuran_list as $index => $angsuran): 
                                    $total_pinjaman = getTotalPinjaman($angsuran);
                                    $sisa_pinjaman = $total_pinjaman - $angsuran['total_bayar'];
                                    $keterangan = "Angsuran ke-" . $angsuran['total_angsuran'];
                                ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td><?php echo htmlspecialchars($angsuran['nrp']); ?></td>
                                        <td><?php echo htmlspecialchars($angsuran['nama_lengkap']); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($angsuran['tanggal_bayar'])); ?></td>
                                        <td>Rp <?php echo number_format($angsuran['total_bayar']); ?></td>
                                        <td>Rp <?php echo number_format($total_pinjaman); ?></td>
                                        <td>Rp <?php echo number_format($sisa_pinjaman); ?></td>
                                        <td><?php echo htmlspecialchars($keterangan); ?></td>
                                        <td>
                                            <a href="angsuran_detail.php?id=<?php echo $angsuran['pinjaman_id']; ?>" 
                                               class="btn btn-info btn-sm mb-1 text-white">
                                                <i class="bx bx-detail"></i> Detail
                                            </a>    
                                            <a href="angsuran_form.php?pinjaman_id=<?php echo $angsuran['pinjaman_id']; ?>" 
                                               class="btn btn-primary btn-sm mb-1">
                                                <i class="bx bx-money"></i> Angsur
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
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


<script>
$(document).ready(function() {
    $('#angsuranTable').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Indonesian.json"
        }
    });
});

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

    @media (max-width: 768px) {
        body {
            background-image: url('images/bg2.png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
        }
    }

    /* Style untuk animasi alert */
    .alert {
        transition: opacity 0.5s ease-in-out;
    }

    .alert.fade-out {
        opacity: 0;
    }
</style>