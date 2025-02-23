<?php
include 'includes/header.php';

$id = isset($_GET['id']) ? $_GET['id'] : null;
$title = "Tambah Kas";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$default_kas = [
    'tanggal' => date('Y-m-d'),
    'jenis' => '',
    'keterangan' => '',
    'jumlah' => '0',
    'status' => 'masuk'
];

$kas = $default_kas;

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM kas WHERE id = ?");
    $stmt->execute([$id]);
    $result = $stmt->fetch();
    if ($result) {
        $title = "Edit Data Kas";
        $kas = array_merge($default_kas, $result);
    }
}

function getKasNumber($pdo, $jenis) {
    $bulan_ini = date('Y-m', strtotime($_POST['tanggal']));
    $prefix = ($jenis == 'masuk') ? 'KM' : 'KK';
    
    $query = "SELECT COUNT(*) as total FROM kas 
              WHERE jenis_kas = ? 
              AND DATE_FORMAT(tanggal, '%Y-%m') = ?";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$jenis, $bulan_ini]);
    $result = $stmt->fetch();
    
    return $prefix . ' ' . ($result['total'] + 1);
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

        $_SESSION['success'] = "Kas " . ($jenis_kas == 'masuk' ? 'masuk' : 'keluar') . " berhasil diinput";
        header("Location: kas.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = "Gagal menginput kas " . ($jenis_kas == 'masuk' ? 'masuk' : 'keluar');
        header("Location: kas.php");
        exit();
    }
}
?>

<div class="container py-2">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <!-- form kas -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0"><?php echo $title; ?></h5>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label ms-2">Jenis Kas *</label>
                            <select name="jenis_kas" class="form-control" required>
                                <option value="masuk">Kas Masuk</option>
                                <option value="keluar">Kas Keluar</option>
                            </select>
                        </div>
    
                        <div class="mb-3">
                            <label class="form-label ms-2">Jumlah *</label>
                            <input type="text" name="jumlah" id="jumlah" class="form-control" required 
                                value="<?php echo number_format($kas['jumlah'], 0, ',', '.'); ?>">
                        </div>
    
                        <div class="mb-3">
                            <label class="form-label ms-2">Tanggal *</label>
                            <input type="date" name="tanggal" class="form-control" required
                                   value="<?php echo date('Y-m-d'); ?>">
                        </div>
    
                        <div class="mb-3">
                            <label class="form-label ms-2">Keterangan *</label>
                            <textarea name="keterangan" class="form-control" required rows="3"></textarea>
                        </div>
    
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Simpan</button>
                            <a href="kas.php" class="btn btn-secondary">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/cleave.js/1.6.0/cleave.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Format input jumlah
        var jumlahInput = new Cleave('#jumlah', {
            numeral: true,
            numeralThousandsGroupStyle: 'thousand',
            numeralDecimalMark: ',',
            delimiter: '.'
        });

        // Saat form di-submit, hapus format ribuan
        document.querySelector('form').addEventListener('submit', function(e) {
            var jumlahInput = document.getElementById('jumlah');
            jumlahInput.value = jumlahInput.value.replace(/\./g, '');
        });
    });
</script>