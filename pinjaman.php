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

$search = isset($_GET['search']) ? $_GET['search'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$where = 'WHERE 1=1';

if ($search) {
    $where .= " AND (a.nrp LIKE :search OR a.nama_lengkap LIKE :search)";
}
if ($status) {
    $where .= " AND p.status = :status";
}

$query = "SELECT p.*, 
          CASE 
              WHEN p.anggota_id IS NULL THEN 'Dinas'
              ELSE a.nrp 
          END as nrp,
          CASE 
              WHEN p.anggota_id IS NULL THEN 'Dinas'
              ELSE a.nama_lengkap 
          END as nama_lengkap,
          u.nama_lengkap as petugas 
          FROM pinjaman p 
          LEFT JOIN anggota a ON p.anggota_id = a.id 
          LEFT JOIN users u ON p.approved_by = u.id 
          $where 
          ORDER BY p.created_at DESC";

$stmt = $pdo->prepare($query);
if ($search) {
    $stmt->bindValue(':search', "%$search%");
}
if ($status) {
    $stmt->bindValue(':status', $status);
}
$stmt->execute();
$pinjaman = $stmt->fetchAll();

// Hitung total pinjaman
$stmt = $pdo->query("SELECT 
    COUNT(*) as total_pengajuan,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as total_pending,
    SUM(CASE WHEN status = 'disetujui' THEN jumlah_pinjaman ELSE 0 END) as total_pinjaman
FROM pinjaman");
$statistik = $stmt->fetch();

// saldo kas
$stmt = $pdo->query("
    SELECT 
        (SELECT COALESCE(SUM(jumlah), 0) FROM kas WHERE jenis_kas = 'masuk') -
        (SELECT COALESCE(SUM(jumlah), 0) FROM kas WHERE jenis_kas = 'keluar') AS saldo
");
$totalSaldoKas = $stmt->fetch()['saldo'];

?>

<div class="content-wrapper">
    <!-- judul -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-3 mt-2">
                <div class="col-sm-6">
                    <h2 class="ms-4">Data Pinjaman</h2>
                </div>
                <div class="col-sm-6">
                    <!-- buton -->
                    <div class="me-4 d-flex justify-content-end">
                        <a href="pinjaman_form.php" class="btn btn-primary">
                            <i class='bx bx-plus'></i> Tambah Pinjaman
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
                <div class="col-md-3">
                    <div class="card border-primary text-primary">
                        <div class="card-body">
                            <h5 class="card-title">Total Pengajuan</h5>
                            <h3 class="card-text"><?php echo number_format($statistik['total_pengajuan']); ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-warning text-warning">
                        <div class="card-body">
                            <h5 class="card-title">Menunggu Persetujuan</h5>
                            <h3 class="card-text"><?php echo number_format($statistik['total_pending']); ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-dark text-dark">
                        <div class="card-body">
                            <h5 class="card-title">Total Saldo Kas</h5>
                            <h3 class="card-text">Rp <?php echo number_format($totalSaldoKas, 0, ',', '.'); ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-danger text-danger">
                        <div class="card-body">
                            <h5 class="card-title">Total Pinjaman Disetujui</h5>
                            <h3 class="card-text">Rp <?php echo number_format($statistik['total_pinjaman'], 0, ',', '.'); ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Pencarian dan Filter -->
            <div class="card mb-1">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-6">
                            <input type="text" name="search" class="form-control" placeholder="Cari berdasarkan NRP atau Nama..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-4">
                            <select name="status" class="form-select">
                                <option value="">Semua Status</option>
                                <option value="pending" <?php echo $status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="disetujui" <?php echo $status == 'disetujui' ? 'selected' : ''; ?>>Disetujui</option>
                                <option value="ditolak" <?php echo $status == 'ditolak' ? 'selected' : ''; ?>>Ditolak</option>
                                <option value="lunas" <?php echo $status == 'lunas' ? 'selected' : ''; ?>>Lunas</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Cari</button>
                        </div>
                    </form>
                </div>
            </div>
    
            <!-- Tabel Data Pinjaman -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover" id="pinjamanTable">
                            <thead class="table-primary">
                                <tr>
                                    <th>No</th>
                                    <th>Tanggal</th>
                                    <th>NRP</th>
                                    <th>Nama Anggota</th>
                                    <th>Jumlah</th>
                                    <th>Lama</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                
                                <?php 
                                $no = 1;
                                foreach ($pinjaman as $row): ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($row['tanggal_pengajuan'])); ?></td>
                                    <td><?php echo htmlspecialchars($row['nrp']); ?></td>
                                    <td><?php echo htmlspecialchars($row['nama_lengkap']); ?></td>
                                    <td>Rp <?php echo number_format($row['jumlah_pinjaman'], 0, ',', '.'); ?></td>
                                    <td><?php echo $row['jangka_waktu']; ?> bulan</td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $row['status'] == 'pending' ? 'warning' : 
                                                ($row['status'] == 'disetujui' ? 'success' : 
                                                ($row['status'] == 'ditolak' ? 'danger' : 'primary')); 
                                        ?>">
                                            <?php echo ucfirst($row['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="pinjaman_detail.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-info text-white" title="Detail">
                                            <i class='bx bx-detail'></i>
                                        </a>
                                        <?php if ($_SESSION['level'] == 'admin' && $row['status'] == 'pending'): ?>
                                        <a href="pinjaman_approval.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-success" title="Proses">
                                            <i class='bx bx-check'></i>
                                        </a>
                                        <?php endif; ?>
                                        <?php if ($_SESSION['level'] == 'admin' && $row['status'] == 'ditolak'): ?>
                                        <a href="javascript:void(0)" onclick="confirmDelete(<?php echo $row['id']; ?>)" class="btn btn-sm btn-danger" title="Hapus">
                                            <i class='bx bx-trash'></i>
                                        </a>
                                        <?php endif; ?>
                                        <a href="<?php echo $row['anggota_id'] ? 'cetak_detail_pinjaman.php' : 'cetak_detail_pinjaman_dinas.php'; ?>?id=<?php echo $row['id']; ?>" 
                                           class="btn btn-secondary btn-sm" 
                                           target="_blank">
                                            <i class='bx bx-printer'></i>
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

<!-- Modal Konfirmasi Hapus -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Konfirmasi Hapus</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Apakah Anda yakin ingin menghapus data pinjaman ini?
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

<script>
function confirmDelete(id) {
    // Dapatkan elemen modal
    var deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    
    // Update href pada tombol hapus di dalam modal
    document.getElementById('deleteLink').href = 'pinjaman_delete.php?id=' + id;
    
    deleteModal.show();
}

document.addEventListener('DOMContentLoaded', function() {
    function fadeOutAndRemove(element) {
        element.style.transition = 'opacity 0.5s';
        element.style.opacity = '0';
        setTimeout(() => {
            element.remove();
        }, 500);
    }

    const successAlert = document.getElementById('successAlert');
    const errorAlert = document.getElementById('errorAlert');

    if (successAlert) {
        setTimeout(() => {
            fadeOutAndRemove(successAlert);
        }, 3000);
    }

    if (errorAlert) {
        setTimeout(() => {
            fadeOutAndRemove(errorAlert);
        }, 3000);
    }
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
        background-color: rgba(255, 255, 255, 0.8); 
        
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

    .alert {
        transition: opacity 0.5s ease-in-out;
    }

    .alert.fade-out {
        opacity: 0;
    }
</style>