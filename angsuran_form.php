<?php 
include 'includes/header.php';

function getKasNumber($pdo, $jenis) {
    $tanggal_input = $_POST['tanggal_bayar']; 
    $bulan_ini = date('Y-m', strtotime($tanggal_input));
    $prefix = ($jenis == 'masuk') ? 'KM' : 'KK';
    
    $query = "SELECT MAX(CAST(SUBSTRING_INDEX(nomor, ' ', -1) AS SIGNED)) as last_number 
              FROM kas 
              WHERE jenis_kas = ? 
              AND DATE_FORMAT(tanggal, '%Y-%m') = ?";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$jenis, $bulan_ini]);
    $result = $stmt->fetch();
    
    $next_number = ($result['last_number'] === null) ? 1 : $result['last_number'] + 1;
    
    // Format: KM 1 atau KK 1 
    return $prefix . ' ' . $next_number;
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$pinjaman_id = isset($_GET['pinjaman_id']) ? $_GET['pinjaman_id'] : null;
if (!$pinjaman_id) {
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
    END as nama_lengkap
    FROM pinjaman p 
    LEFT JOIN anggota a ON p.anggota_id = a.id 
    WHERE p.id = ? AND p.status = 'disetujui'
");
$stmt->execute([$pinjaman_id]);
$pinjaman = $stmt->fetch();

if (!$pinjaman) {
    header("Location: pinjaman.php");
    exit();
}

// Hitung bunga berdasarkan tipe peminjam
$bunga_persen = 0.8;
$bunga_bulanan = 0; // Default untuk dinas

// Hanya hitung bunga jika peminjam adalah anggota
if ($pinjaman['anggota_id']) {
    $bunga_bulanan = ($pinjaman['jumlah_pinjaman'] * $bunga_persen / 100);
}

// Hitung total yang sudah dibayar
$stmt = $pdo->prepare("SELECT COALESCE(SUM(jumlah_bayar), 0) as total_bayar FROM angsuran WHERE pinjaman_id = ?");
$stmt->execute([$pinjaman_id]);
$total_bayar = $stmt->fetch()['total_bayar'];

$stmt = $pdo->prepare("SELECT COUNT(*) as angsuran_ke FROM angsuran WHERE pinjaman_id = ?");
$stmt->execute([$pinjaman_id]);
$angsuran_ke = $stmt->fetch()['angsuran_ke'] + 1;

// Hitung total pinjaman berdasarkan jenis pinjaman
$total_pinjaman = $pinjaman['jumlah_pinjaman'];
$bunga_bulanan = 0;
$total_bunga = 0;

// Tambahkan bunga berdasarkan jenis pinjaman
if ($pinjaman['anggota_id']) {
    if ($pinjaman['jenis_pinjaman'] === 'baru') {
        // Untuk pinjaman baru, hitung bunga per bulan
        $bunga_bulanan = ($pinjaman['jumlah_pinjaman'] * $pinjaman['bunga_persen'] / 100);
        $total_pinjaman += $bunga_bulanan;
        
        for ($i = 1; $i < $angsuran_ke; $i++) {
            $total_pinjaman += $bunga_bulanan;
        }
        $total_bunga = $bunga_bulanan * $pinjaman['jangka_waktu'];
    } else {
        // Untuk pinjaman lama, gunakan bunga per bulan yang sudah diinput
        $bunga_bulanan = $pinjaman['bunga_per_bulan'];
        
        // Hitung total pinjaman dengan bunga yang bertambah per bulan
        $total_pinjaman = $pinjaman['jumlah_pinjaman'];
        for ($i = 0; $i < $angsuran_ke; $i++) {
            $total_pinjaman += $bunga_bulanan;
        }
        
        // Total bunga yang akan dibayar untuk sisa periode
        $sisa_periode = $pinjaman['jangka_waktu'] - $angsuran_ke + 1;
        $total_bunga = $bunga_bulanan * $sisa_periode;
    }
}

$sisa_pinjaman = $total_pinjaman - $total_bayar;

// Ubah perhitungan total_pelunasan untuk pinjaman lama
$total_pelunasan = $sisa_pinjaman;
if ($pinjaman['jenis_pinjaman'] === 'baru') {
    // Untuk pinjaman baru, tambahkan bunga sisa bulan
    $bulan_berjalan = floor((strtotime(date('Y-m-d')) - strtotime($pinjaman['tanggal_pengajuan'])) / (30 * 24 * 60 * 60));
    $sisa_bulan = min($pinjaman['jangka_waktu'] - $bulan_berjalan - 1, 1);
    if ($sisa_bulan > 0) {
        $tambahan_bunga = $bunga_bulanan * $sisa_bulan;
        $total_pelunasan += $tambahan_bunga;
    }
} else {
    // Untuk pinjaman lama, tambahkan bunga 1 bulan ke depan saja
    $total_pelunasan = $sisa_pinjaman + $bunga_bulanan;
}

// Cek apakah sudah mencapai bulan terakhir
$stmt = $pdo->prepare("SELECT COUNT(*) as jumlah_angsuran FROM angsuran WHERE pinjaman_id = ?");
$stmt->execute([$pinjaman_id]);
$angsuran_data = $stmt->fetch();
$jumlah_angsuran = $angsuran_data['jumlah_angsuran'];

// Jika jumlah angsuran sudah sama dengan jangka waktu - 1, berarti ini bulan terakhir
$is_bulan_terakhir = ($jumlah_angsuran >= $pinjaman['jangka_waktu'] - 1);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $jumlah_bayar = str_replace(['.', ','], ['', ''], $_POST['jumlah_bayar']);
        $jumlah_bayar = (float)$jumlah_bayar; 

        $tanggal_bayar = $_POST['tanggal_bayar'];
        $keterangan = $_POST['keterangan'];

        // Cek batas pembayaran berdasarkan tipe peminjam
        if ($pinjaman['anggota_id']) {
            // Untuk anggota, cek termasuk bunga
            if ($jumlah_bayar > $sisa_pinjaman + $bunga_bulanan) {
                throw new Exception("Jumlah pembayaran melebihi sisa pinjaman dan bunga!");
            }
        } else {
            // Untuk dinas, cek tanpa bunga
            if ($jumlah_bayar > $sisa_pinjaman) {
                throw new Exception("Jumlah pembayaran melebihi sisa pinjaman!");
            }
        }

        // Cek keterlambatan hanya untuk anggota
        if ($pinjaman['anggota_id']) {
            $tanggal_jatuh_tempo = date('Y-m-d', strtotime($pinjaman['tanggal_pengajuan'] . ' + ' . $pinjaman['jangka_waktu'] . ' months'));
            if ($tanggal_bayar > $tanggal_jatuh_tempo) {
                $jumlah_bayar == $bunga_bulanan;
            }
        }

        $keterangan = "Angsuran ke-$angsuran_ke " . $keterangan;

        $stmt = $pdo->prepare("
            INSERT INTO angsuran (pinjaman_id, jumlah_bayar, tanggal_bayar, keterangan, created_by)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$pinjaman_id, $jumlah_bayar, $tanggal_bayar, $keterangan, $_SESSION['user_id']]);

        // Update total angsuran hanya jika peminjam adalah anggota
        if ($pinjaman['anggota_id']) {
            $new_total = $total_pinjaman + $bunga_bulanan;
            $stmt = $pdo->prepare("UPDATE pinjaman SET total_angsuran = ? WHERE id = ?");
            $stmt->execute([$new_total, $pinjaman_id]);
        }

        $total_bayar += $jumlah_bayar;
        if ($total_bayar >= $total_pinjaman) {
            $stmt = $pdo->prepare("UPDATE pinjaman SET status = 'lunas' WHERE id = ?");
            $stmt->execute([$pinjaman_id]);
            $_SESSION['success'] = "Angsuran lunas";
        } else {
            $_SESSION['success'] = "Angsuran berhasil";
        }

        // Insert ke kas
        $stmt = $pdo->prepare("
            INSERT INTO kas (nomor, tanggal, keterangan, jenis_kas, jumlah, created_by)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $nomor = getKasNumber($pdo, 'masuk');
        $keterangan = "Angsuran dari " . $pinjaman['nama_lengkap'] . " - " . $keterangan;
        $stmt->execute([$nomor, $tanggal_bayar, $keterangan, 'masuk', $jumlah_bayar, $_SESSION['user_id']]);

        header("Location: angsuran.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = "Angsuran gagal: " . $e->getMessage();
        header("Location: angsuran.php");
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
                    <h5 class="card-title">Pembayaran Angsuran</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                <!-- Informasi Pinjaman -->
                <div class="alert alert-info">
                    <h5>Informasi Pinjaman:</h5>
                    <table class="table table-borderless mb-0">
                        <tr>
                            <td width="200">Tipe Peminjam</td>
                            <td>: <?php echo $pinjaman['anggota_id'] ? 'Anggota' : 'Dinas'; ?></td>
                        </tr>
                        <tr>
                            <td>Jenis Pinjaman</td>
                            <td>: <?php echo ucfirst($pinjaman['jenis_pinjaman']); ?></td>
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
                        <?php else: ?>
                        <tr>
                            <td>Peminjam</td>
                            <td>: Dinas</td>
                        </tr>
                        <?php endif; ?>
                        <tr>
                            <td>Total Pinjaman</td>
                            <td>: Rp <?php echo number_format($total_pinjaman); ?></td>
                        </tr>
                        <tr>
                            <td>Bunga</td>
                            <td>: <?php if ($pinjaman['jenis_pinjaman'] === 'baru'): ?>
                                    <?php echo $pinjaman['bunga_persen']; ?>%
                                <?php else: ?>
                                    Rp <?php echo number_format($pinjaman['bunga_per_bulan'], 0, ',', '.'); ?> per bulan
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td>Angsuran per Bulan</td>
                            <td>: Rp <?php echo number_format($pinjaman['angsuran_per_bulan']); ?></td>
                        </tr>
                        <tr>
                            <td>Sudah Dibayar</td>
                            <td>: Rp <?php echo number_format($total_bayar); ?></td>
                        </tr>
                        <tr>
                            <td>Sisa Pembayaran</td>
                            <td>: Rp <?php echo number_format($sisa_pinjaman); ?></td>
                        </tr>
                    </table>
                </div>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Jumlah Bayar (Rp) *</label>
                        <input type="text" name="jumlah_bayar" id="jumlahBayar" class="form-control" required
                            value="<?php echo number_format($pinjaman['angsuran_per_bulan'], 0, ',', '.'); ?>"
                            onkeyup="formatNumber(this);">
                        
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" id="pelunasanCheck" onchange="hitungPelunasan()">
                            <label class="form-check-label" for="pelunasanCheck">
                                Bayar Lunas 
                                <?php if ($pinjaman['jenis_pinjaman'] === 'lama'): ?>
                                    (termasuk bunga bulan depan Rp <?php echo number_format($bunga_bulanan); ?>)
                                <?php elseif ($sisa_bulan > 0): ?>
                                    (akan ditambah bunga <?php echo $sisa_bulan; ?> bulan = Rp <?php echo number_format($tambahan_bunga); ?>)
                                <?php endif; ?>
                            </label>
                        </div>
                        <div id="totalPelunasanInfo" class="text-info mt-2" style="display:none;">
                            Total yang harus dibayar: Rp <span id="totalPelunasanText"><?php echo number_format($total_pelunasan); ?></span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tanggal Bayar *</label>
                        <input type="date" name="tanggal_bayar" class="form-control" required
                            value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Simpan Pembayaran</button>
                        <a href="angsuran.php" class="btn btn-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function formatNumber(input) {
    let value = input.value.replace(/\D/g, '');
    input.value = new Intl.NumberFormat('id-ID').format(value);
}

// Nonaktifkan checkbox saat halaman dimuat jika sudah bulan terakhir
document.addEventListener('DOMContentLoaded', function() {
    const checkbox = document.getElementById('pelunasanCheck');
    const isLastMonth = <?php echo $is_bulan_terakhir ? 'true' : 'false'; ?>;
    
    if (isLastMonth) {
        checkbox.disabled = true;
        checkbox.checked = false; // Pastikan checkbox tidak tercentang
        checkbox.title = "Pelunasan hanya tersedia sebelum bulan terakhir";
    }
});

// Override fungsi hitungPelunasan untuk mencegah interaksi jika bulan terakhir
function hitungPelunasan() {
    const checkbox = document.getElementById('pelunasanCheck');
    const isLastMonth = <?php echo $is_bulan_terakhir ? 'true' : 'false'; ?>;
    
    if (isLastMonth) {
        checkbox.checked = false;
        return false;
    }
    
    const jumlahBayarInput = document.getElementById('jumlahBayar');
    const totalPelunasanInfo = document.getElementById('totalPelunasanInfo');
    
    if (checkbox.checked) {
        totalPelunasanInfo.style.display = 'block';
        jumlahBayarInput.value = '<?php echo number_format($total_pelunasan, 0, ',', '.'); ?>';
    } else {
        totalPelunasanInfo.style.display = 'none';
        jumlahBayarInput.value = '<?php echo number_format($pinjaman['angsuran_per_bulan'], 0, ',', '.'); ?>';
    }
}
</script>