<?php 
include 'includes/header.php';

$search = isset($_GET['search']) ? $_GET['search'] : '';
$where = '';
if ($search) {
    $where = "WHERE nrp LIKE :search 
              OR nama_lengkap LIKE :search 
              OR pangkat LIKE :search 
              OR korps LIKE :search
              OR jabatan LIKE :search";
}

$query = "SELECT * FROM anggota $where 
          ORDER BY 
          CASE 
              WHEN pangkat = 'Jenderal TNI' THEN 1
              WHEN pangkat = 'Letnan Jenderal TNI' THEN 2
              WHEN pangkat = 'Mayor Jenderal TNI' THEN 3
              WHEN pangkat = 'Brigadir Jenderal TNI' THEN 4
              WHEN pangkat = 'Kolonel' THEN 5
              WHEN pangkat = 'Letnan Kolonel' THEN 6
              WHEN pangkat = 'Mayor' THEN 7
              WHEN pangkat = 'Kapten' THEN 8
              WHEN pangkat = 'Letnan Satu' THEN 9
              WHEN pangkat = 'Letnan Dua' THEN 10
              WHEN pangkat = 'Pembantu Letnan Satu' THEN 11
              WHEN pangkat = 'Pembantu Letnan Dua' THEN 12
              WHEN pangkat = 'Sersan Mayor' THEN 13
              WHEN pangkat = 'Sersan Kepala' THEN 14
              WHEN pangkat = 'Sersan Satu' THEN 15
              WHEN pangkat = 'Sersan Dua' THEN 16
              WHEN pangkat = 'Kopral Kepala' THEN 17
              WHEN pangkat = 'Kopral Satu' THEN 18
              WHEN pangkat = 'Kopral Dua' THEN 19
              WHEN pangkat = 'Prajurit Kepala' THEN 20
              WHEN pangkat = 'Prajurit Satu' THEN 21
              WHEN pangkat = 'Prajurit Dua' THEN 22
              WHEN pangkat = 'Pembina Utama IV/E' THEN 23
              WHEN pangkat = 'Pembina Utama Madya IV/D' THEN 24
              WHEN pangkat = 'Pembina Utama Muda IV/C' THEN 25
              WHEN pangkat = 'Pembina Tingkat 1 IV/B' THEN 26
              WHEN pangkat = 'Pembina IV/A' THEN 27
              WHEN pangkat = 'Penata Tingkat 1 III/D' THEN 28
              WHEN pangkat = 'Penata III/C' THEN 29
              WHEN pangkat = 'Penata Muda Tingkat 1 III/B' THEN 30
              WHEN pangkat = 'Penata Muda III/A' THEN 31
              WHEN pangkat = 'Pengatur Tingkat 1 II/D' THEN 32
              WHEN pangkat = 'Pengatur II/C' THEN 33
              WHEN pangkat = 'Pengatur Muda Tingkat 1 II/B' THEN 34
              WHEN pangkat = 'Pengatur Muda II/A' THEN 35
              WHEN pangkat = 'Juru Tingkat 1 I/D' THEN 36
              WHEN pangkat = 'Juru I/C' THEN 37
              WHEN pangkat = 'Juru Muda Tingkat 1 I/B' THEN 38
              WHEN pangkat = 'Juru Muda I/A' THEN 39
              ELSE 40
          END, created_at DESC";

$stmt = $pdo->prepare($query);
if ($search) {
    $stmt->bindValue(':search', "%$search%");
}
$stmt->execute();
$anggota = $stmt->fetchAll();

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

if (isset($_GET['success']) || isset($_GET['error'])) {
    $_SESSION['success'] = isset($_GET['success']) ? $_GET['success'] : '';
    $_SESSION['error'] = isset($_GET['error']) ? $_GET['error'] : '';
    
    $redirect_url = 'anggota.php';
    if ($search) {
        $redirect_url .= '?search=' . urlencode($search);
    }
    header("Location: $redirect_url");
    exit();
}
?>

<div class="content-wrapper">
    <!-- judul -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-3 mt-2">
                <div class="col-sm-6">
                    <h2 class="ms-4">Data Anggota</h2>
                </div>
                <div class="col-sm-6">
                    <!-- button -->
                    <div class="me-4 d-flex justify-content-end">
                        <a href="#" class="btn btn-info text-white" data-bs-toggle="modal" data-bs-target="#importModal">
                            <i class='bx bx-import'></i> Import Data
                        </a>
                        <a href="anggota_form.php" class="btn btn-primary ms-2">
                            <i class='bx bx-plus'></i> Tambah Anggota
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <section class="content">
        <div class="container-fluid">
            
            <!-- Form Pencarian -->
            <div class="card mb-1">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-10">
                            <input type="text" name="search" class="form-control" placeholder="Cari berdasarkan NRP, Nama, Pangkat, Korps, Jabatan" value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Cari</button>
                        </div>
                    </form>
                </div>
            </div>
        
            <!-- Tabel Data Anggota -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover" id="pinjamanTable">
                            <thead class="table-primary"">
                                <tr>
                                    <th>No</th>
                                    <th>NRP</th>
                                    <th>Nama Lengkap</th>
                                    <th>Pangkat</th>
                                    <th>Korps</th>
                                    <th>Jabatan</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $no = 1;
                                foreach ($anggota as $row): ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><?php echo htmlspecialchars($row['nrp']); ?></td>
                                    <td><?php echo htmlspecialchars($row['nama_lengkap']); ?></td>
                                    <td><?php echo htmlspecialchars($row['pangkat']); ?></td>
                                    <td><?php echo htmlspecialchars($row['korps']); ?></td>
                                    <td><?php echo htmlspecialchars($row['jabatan']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $row['status'] == 'aktif' ? 'success' : 'danger'; ?>">
                                            <?php echo ucfirst($row['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="anggota_detail.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-info text-white mb-1" title="Detail">
                                            <i class='bx bx-detail'></i>
                                        </a>
                                        <a href="anggota_form.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning text-white mb-1" title="Edit">
                                            <i class='bx bx-edit'></i>
                                        </a>
                                        <button onclick="confirmDelete(<?php echo $row['id']; ?>)" class="btn btn-sm btn-danger mb-1" title="Hapus">
                                            <i class='bx bx-trash'></i>
                                        </button>
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

<!-- Modal Import -->
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Import Data Anggota</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form action="anggota_import.php" method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">File CSV</label>
                        <input type="file" name="file" class="form-control" accept=".csv" required>
                    </div>
                    <div class="mb-3">
                        <a href="download_template.php" class="text-decoration-none">
                            <i class='bx bx-download'></i> Download Template CSV
                        </a>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">
                            Catatan:<br>
                            - File harus dalam format CSV (Comma Separated Values)<br>
                            - Gunakan template yang disediakan<br>
                            - Pastikan format tanggal dd/mm/yyyy (misal: 20/01/2025)<br>
                            - Status bisa diisi 'aktif' atau 'nonaktif'
                        </small>
                    </div>
                    <button type="submit" class="btn btn-primary">Import</button>
                </form>
            </div>
        </div>
    </div>
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
                Apakah Anda yakin ingin menghapus data anggota ini?
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


<!-- Script untuk konfirmasi hapus -->
<script>
    function confirmDelete(id) {
        const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
        document.getElementById('deleteLink').href = `anggota_delete.php?id=${id}`;
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