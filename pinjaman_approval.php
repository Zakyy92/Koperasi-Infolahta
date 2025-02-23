<?php
include 'includes/header.php';

function getSaldoKas($pdo) {
    $stmt = $pdo->query("
        SELECT 
            (SELECT COALESCE(SUM(jumlah), 0) FROM kas WHERE jenis_kas = 'masuk') -
            (SELECT COALESCE(SUM(jumlah), 0) FROM kas WHERE jenis_kas = 'keluar') AS saldo
    ");
    return $stmt->fetch()['saldo'];
}

function getKasNumber($pdo, $jenis) {
    $bulan_ini = date('Y-m'); // Menggunakan bulan dan tahun saat ini
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

if ($_SESSION['level'] != 'admin') {
    header("Location: dashboard.php");
    exit();
}

$id = isset($_GET['id']) ? $_GET['id'] : null;
if (!$id) {
    header("Location: pinjaman.php");
    exit();
}

$stmt = $pdo->prepare("
    SELECT p.*, 
    CASE 
        WHEN p.anggota_id IS NULL THEN 'Dinas'
        ELSE a.nrp 
    END as nrp,
    CASE 
        WHEN p.anggota_id IS NULL THEN 'Dinas'
        ELSE a.nama_lengkap 
    END as nama_lengkap,
    CASE 
        WHEN p.anggota_id IS NULL THEN '-'
        ELSE a.pangkat 
    END as pangkat
    FROM pinjaman p 
    LEFT JOIN anggota a ON p.anggota_id = a.id 
    WHERE p.id = ? AND p.status = 'pending'
");
$stmt->execute([$id]);
$pinjaman = $stmt->fetch();

if (!$pinjaman) {
    header("Location: pinjaman.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $status = $_POST['status'];
        $catatan = $_POST['catatan'];
        $tanggal_approval = date('Y-m-d H:i:s');

        // Periksa saldo kas
        $saldo_kas = getSaldoKas($pdo);
        if ($status == 'disetujui' && $pinjaman['jumlah_pinjaman'] > $saldo_kas) {
            throw new Exception("Saldo kas tidak mencukupi untuk menyetujui pinjaman ini.");
        }

        // Update status pinjaman
        $stmt = $pdo->prepare("
            UPDATE pinjaman SET 
                status = ?,
                catatan = ?,
                approved_by = ?,
                approved_at = ?
            WHERE id = ?
        ");
        $stmt->execute([$status, $catatan, $_SESSION['user_id'], $tanggal_approval, $id]);
        
        // Setelah update status pinjaman
        if ($status == 'disetujui') {
            $stmt = $pdo->prepare("
                INSERT INTO kas (nomor, tanggal, keterangan, jenis_kas, jumlah, created_by)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $nomor = getKasNumber($pdo, 'keluar'); // Fungsi untuk mendapatkan nomor kas
            $keterangan = "Pinjaman untuk " . $pinjaman['nama_lengkap'] . " - " . $pinjaman['keterangan'];
            $stmt->execute([$nomor, $tanggal_approval, $keterangan, 'keluar', $pinjaman['jumlah_pinjaman'], $_SESSION['user_id']]);
            $_SESSION['success'] = "Pinjaman disetujui";
        } else {
            $_SESSION['success'] = "Pinjaman ditolak";
        }
        
        header("Location: pinjaman.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = "Gagal memproses pinjaman saldo kas tidak mencukupi";
        header("Location: pinjaman.php");
        exit();
    }
}
?>

<div class="container py-2">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Proses Pengajuan Pinjaman</h3>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <!-- Informasi Pinjaman -->
                    <div class="alert alert-info">
                        <h5>Informasi Peminjam:</h5>
                        <table class="table table-borderless mb-0">
                            <tr>
                                <td>Tipe Peminjam</td>
                                <td>: <?php echo $pinjaman['anggota_id'] ? 'Anggota' : 'Dinas'; ?></td>
                            </tr>
                            <?php if ($pinjaman['anggota_id']): ?>
                            <tr>
                                <td>NRP</td>
                                <td>: <?php echo htmlspecialchars($pinjaman['nrp']); ?></td>
                            </tr>
                            <tr>
                                <td>Nama Anggota</td>
                                <td>: <?php echo htmlspecialchars($pinjaman['nama_lengkap']); ?></td>
                            </tr>
                            <tr>
                                <td>Pangkat</td>
                                <td>: <?php echo htmlspecialchars($pinjaman['pangkat']); ?></td>
                            </tr>
                            <?php else: ?>
                            <tr>
                                <td>Peminjam</td>
                                <td>: Dinas</td>
                            </tr>
                            <?php endif; ?>
                            <tr>
                                <td>Jumlah Pinjaman</td>
                                <td>: Rp <?php echo number_format($pinjaman['jumlah_pinjaman']); ?></td>
                            </tr>
                            <tr>
                                <td>Jangka Waktu</td>
                                <td>: <?php echo $pinjaman['jangka_waktu']; ?> bulan</td>
                            </tr>
                            <tr>
                                <td>Bunga</td>
                                <td>: <?php echo $pinjaman['bunga_persen']; ?>% (Rp <?php echo number_format($pinjaman['jumlah_pinjaman'] * ($pinjaman['bunga_persen'] / 100), 0, ',', '.'); ?>)</td>
                            </tr>
                            <tr>
                                <td>Angsuran per Bulan</td>
                                <td>: Rp <?php echo number_format($pinjaman['angsuran_per_bulan']); ?></td>
                            </tr>
                            <?php if ($pinjaman['keterangan']): ?>
                            <tr>
                                <td>Keterangan</td>
                                <td>: <?php echo htmlspecialchars($pinjaman['keterangan']); ?></td>
                            </tr>
                            <?php endif; ?>
                        </table>
                    </div>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Status Pengajuan *</label>
                            <select name="status" class="form-select" required>
                                <option value="">Pilih Status</option>
                                <option value="disetujui">Disetujui</option>
                                <option value="ditolak">Ditolak</option>
                            </select>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Simpan</button>
                            <a href="pinjaman.php" class="btn btn-secondary">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>