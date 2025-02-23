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

if ($_SESSION['level'] != 'admin') {
    header("Location: dashboard.php");
    exit();
}

$stmt = $pdo->query("SELECT * FROM users ORDER BY level, nama_lengkap");
$users = $stmt->fetchAll();

$stmt = $pdo->query("SELECT * FROM pengaturan LIMIT 1");
$pengaturan = $stmt->fetch();

if (isset($_POST['update_settings'])) {
    try {
        $stmt = $pdo->prepare("UPDATE pengaturan SET 
            nama_koperasi = ?,
            alamat = ?,
            telepon = ?,
            email = ?
            WHERE id = 1");
            
        $stmt->execute([
            $_POST['nama_koperasi'],
            $_POST['alamat'],
            $_POST['telepon'],
            $_POST['email']
        ]);
        
        $success = "Pengaturan berhasil disimpan!";
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Proses backup database
if (isset($_POST['backup_db'])) {
    try {
        $backup_file = 'backup/koperasi_' . date('Y-m-d_H-i-s') . '.sql';
        
        // Pastikan direktori backup ada
        if (!file_exists('backup')) {
            mkdir('backup', 0777, true);
        }
        
        // backup database
        $command = sprintf(
            'mysqldump -h %s -u %s %s %s > %s',
            escapeshellarg('localhost'),
            escapeshellarg('root'),
            escapeshellarg(''), 
            escapeshellarg('koperasi_tni'),
            escapeshellarg($backup_file)
        );
        
        exec($command, $output, $return_var);
        
        if ($return_var === 0) {
            $success = "Database berhasil dibackup: " . $backup_file;
        } else {
            throw new Exception("Gagal melakukan backup database");
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<div class="container py-2">
    <h2 class="mb-4 ms-3">Pengaturan Sistem</h2>
    <div class="card">
        <div class="card-body">
            <div class="row">
                <!-- Manajemen User -->
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Manajemen User</h5>
                            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addUserModal">
                                <i class='bx bx-plus'></i> Tambah User
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover table-striped">
                                    <thead class="table-primary">
                                        <tr>
                                            <th>Username</th>
                                            <th>Nama</th>
                                            <th>Level</th>
                                            <th>Status</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                                            <td><?php echo htmlspecialchars($user['nama_lengkap']); ?></td>
                                            <td><?php echo ucfirst($user['level']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $user['status'] ? 'success' : 'danger'; ?>">
                                                    <?php echo $user['status'] ? 'Aktif' : 'Nonaktif'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-warning text-white mb-1" onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)">
                                                    <i class='bx bx-edit'></i>
                                                </button>
                                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                <button class="btn btn-sm btn-danger mb-1" onclick="deleteUser(<?php echo $user['id']; ?>)">
                                                    <i class='bx bx-trash'></i>
                                                </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
        
                <!-- Backup Database -->
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Backup & Restore Database</h5>
                        </div>
                        <div class="card-body">
                            <form action="backup_db.php" method="POST" id="backupForm">
                                <button type="submit" name="backup" class="btn btn-primary mb-3">
                                    <i class='bx bx-download'></i> Backup Database
                                </button>
                            </form>
                            <form method="POST" enctype="multipart/form-data" action="restore_db.php">
                                <div class="mb-2 mt-2">
                                    <label class="form-label ms-2">Pilih File Backup (.sql)</label>
                                    <input type="file" name="backup_file" class="form-control" accept=".sql" required>
                                    <small class="text-muted ms-2">Hanya file .sql yang diizinkan</small>
                                </div>
                                <button type="submit" class="btn btn-warning text-white" onclick="return confirm('Anda yakin ingin merestore database? Data yang ada akan diganti dengan data dari file backup.')">
                                    <i class='bx bx-upload'></i> Restore Database
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Notifikasi Pop-up Modal -->
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

<!-- Modal Tambah User -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah User Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="user_process.php" method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Username *</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password *</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap *</label>
                        <input type="text" name="nama_lengkap" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Level *</label>
                        <select name="level" class="form-select" required>
                            <option value="admin">Admin</option>
                            <option value="operator">Operator</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="add_user" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit User -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="user_process.php" method="POST">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Username *</label>
                        <input type="text" name="username" id="edit_username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password (Kosongkan jika tidak diubah)</label>
                        <input type="password" name="password" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap *</label>
                        <input type="text" name="nama_lengkap" id="edit_nama_lengkap" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Level *</label>
                        <select name="level" id="edit_level" class="form-select" required>
                            <option value="operator">Operator</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" id="edit_status" class="form-select" required>
                            <option value="1">Aktif</option>
                            <option value="0">Nonaktif</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="edit_user" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editUser(user) {
    document.getElementById('edit_id').value = user.id;
    document.getElementById('edit_username').value = user.username;
    document.getElementById('edit_nama_lengkap').value = user.nama_lengkap;
    document.getElementById('edit_level').value = user.level;
    document.getElementById('edit_status').value = user.status;
    
    new bootstrap.Modal(document.getElementById('editUserModal')).show();
}

function deleteUser(id) {
    if (confirm('Apakah Anda yakin ingin menghapus user ini?')) {
        window.location.href = 'user_process.php?action=delete&id=' + id;
    }
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

document.getElementById('backupForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    fetch('backup_db.php', {
        method: 'POST'
    })
    .then(response => {
        // Tampilkan modal notifikasi sukses
        const notificationModal = new bootstrap.Modal(document.getElementById('notificationModal'));
        const modalBody = document.querySelector('#notificationModal .modal-body');
        modalBody.innerHTML = `
            <div class="alert alert-success" role="alert">
                Database berhasil dibackup
            </div>
        `;
        notificationModal.show();
        
        // Clear session messages
        fetch('clear_messages.php');
        
        // Download file
        return response.blob();
    })
    .then(blob => {
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'backup_koperasi_tni_' + new Date().toISOString().slice(0,19).replace(/[:]/g, '-') + '.sql';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
    })
    .catch(error => {
        // Tampilkan modal notifikasi error
        const notificationModal = new bootstrap.Modal(document.getElementById('notificationModal'));
        const modalBody = document.querySelector('#notificationModal .modal-body');
        modalBody.innerHTML = `
            <div class="alert alert-danger" role="alert">
                Gagal membackup database
            </div>
        `;
        notificationModal.show();
        
        // Clear session messages
        fetch('clear_messages.php');
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