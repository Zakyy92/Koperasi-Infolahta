<?php 
include 'includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$tahun = isset($_GET['tahun']) ? $_GET['tahun'] : date('Y');
$tanggal_awal = isset($_GET['tanggal_awal']) ? $_GET['tanggal_awal'] : date("$tahun-01-01");
$tanggal_akhir = isset($_GET['tanggal_akhir']) ? $_GET['tanggal_akhir'] : date("$tahun-12-31");

// Periksa apakah kolom tanggal di tabel pinjaman benar
// $kolom_tanggal = 'approved_at'; // hapus atau comment baris ini

$stmt = $pdo->prepare("SELECT * FROM shu WHERE tahun = ?");
$stmt->execute([$tahun]);
$shu = $stmt->fetch();

$query = "
    SELECT 
        a.id,
        a.nrp,
        a.nama_lengkap,
        COALESCE(SUM(p.jumlah_pinjaman), 0) as total_pinjaman,
        
        /* Total bunga sesuai angsuran */
        COALESCE(SUM(
            CASE 
                WHEN p.anggota_id IS NOT NULL THEN
                    CASE 
                        WHEN p.jenis_pinjaman = 'baru' THEN
                            /* Untuk pinjaman baru */
                            (p.jumlah_pinjaman * (p.bunga_persen/100)) * 
                            (SELECT COUNT(*) FROM angsuran WHERE pinjaman_id = p.id)
                        ELSE
                            /* Untuk pinjaman lama */
                            p.bunga_per_bulan * 
                            (SELECT COUNT(*) FROM angsuran WHERE pinjaman_id = p.id)
                    END
                ELSE 0
            END
        ), 0) +
        
        /* Tambahan bunga satu bulan jika lunas sebelum tenggat waktu */
        COALESCE(SUM(
            CASE 
                WHEN p.status = 'lunas' THEN
                    CASE 
                        WHEN (
                            /* Total angsuran yang dibayar */
                            (SELECT COUNT(*) FROM angsuran WHERE pinjaman_id = p.id) 
                            < p.jangka_waktu  /* Dibandingkan dengan total jangka waktu */
                        ) THEN
                            /* Tambah bunga satu bulan */
                            CASE 
                                WHEN p.jenis_pinjaman = 'baru' THEN
                                    (p.jumlah_pinjaman * (p.bunga_persen/100))
                                ELSE
                                    p.bunga_per_bulan
                            END
                        ELSE 0
                    END
                ELSE 0
            END
        ), 0) as total_bunga
        
    FROM anggota a
    LEFT JOIN pinjaman p ON a.id = p.anggota_id 
    WHERE p.status IN ('disetujui', 'lunas')
    AND p.tanggal_pengajuan BETWEEN ? AND ?
    GROUP BY a.id, a.nrp, a.nama_lengkap
    ORDER BY a.nama_lengkap
";


$stmt = $pdo->prepare($query);
$stmt->execute([$tanggal_awal, $tanggal_akhir]);
$anggota_list = $stmt->fetchAll();

// Hitung total bunga dari seluruh pinjaman dalam periode
$total_bunga = array_sum(array_column($anggota_list, 'total_bunga'));

// Hitung total SHU dalam periode
$total_shu_pinjaman = $total_bunga;
?>

<div class="container py-2">
    <h2 class="mb-4 ms-3">Jasa Pinjaman</h2>
    <!-- Filter Periode -->
    <div class="card">
        <div class="card-body">
            <form method="GET" class="row g-3 mb-4">
                <div class="col-md-3">
                    <label for="tanggal_awal" class="form-label ms-2">Dari Tanggal:</label>
                    <input type="date" name="tanggal_awal" id="tanggal_awal" class="form-control" value="<?php echo $tanggal_awal; ?>" required>
                </div>
                <div class="col-md-3">
                    <label for="tanggal_akhir" class="form-label ms-2">Sampai Tanggal:</label>
                    <input type="date" name="tanggal_akhir" id="tanggal_akhir" class="form-control" value="<?php echo $tanggal_akhir; ?>" min="<?php echo $tanggal_awal; ?>" required>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary">Cari</button>
                </div>
            </form>
            <!-- Ringkasan SHU -->
            <table class="table table-bordered table-striped mb-4">
                <thead class="table-primary">
                    <tr>
                        <th>Total Jasa Pinjaman</th>
                        <th class="text-end"><?php echo number_format($total_shu_pinjaman, 0, ',', '.'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Jasa Pinjaman</td>
                        <td class="text-end"><?php echo number_format($total_shu_pinjaman, 0, ',', '.'); ?></td>
                    </tr>
                </tbody>
            </table>
            <!-- Tabel Pembagian SHU -->
            <table class="table table-bordered table-striped">
                <h5 class="ms-2">Pembagian Jasa Pinjaman</h5>
                <thead class="table-primary">
                    <tr>
                        <th>No</th>
                        <th>Nama Anggota</th>
                        <th class="text-end">Pinjaman</th>
                        <th class="text-end">Total Bunga</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    foreach ($anggota_list as $anggota): 
                        // Hitung proporsi SHU anggota berdasarkan kontribusi bunga
                        $proporsi = ($total_bunga > 0) ? ($anggota['total_bunga'] / $total_bunga) : 0;
                        $shu_anggota = $proporsi * $total_shu_pinjaman;
                    ?>
                    <tr>
                        <td class="no-column"><?php echo $no++; ?></td>
                        <td><?php echo htmlspecialchars($anggota['nama_lengkap']); ?></td>
                        <td class="text-end"><?php echo number_format($anggota['total_pinjaman'], 0, ',', '.'); ?></td>
                        <td class="text-end"><?php echo number_format($anggota['total_bunga'], 0, ',', '.'); ?></td>
                    </tr>
                    <?php 
                    endforeach; 
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script>
document.addEventListener("DOMContentLoaded", function() {
    let tanggalAwal = document.getElementById("tanggal_awal");
    let tanggalAkhir = document.getElementById("tanggal_akhir");

    // Update min date untuk tanggal_akhir setiap kali tanggal_awal berubah
    tanggalAwal.addEventListener("change", function() {
        tanggalAkhir.min = tanggalAwal.value;
        
        // Jika tanggal_akhir sebelumnya lebih kecil dari tanggal_awal, reset ke tanggal_awal
        if (tanggalAkhir.value < tanggalAwal.value) {
            tanggalAkhir.value = tanggalAwal.value;
        }
    });
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
    .no-column {
        width: 50px; 
        text-align: center; 
    }
    .card {
        background-color: rgba(255, 255, 255, 0.9);
    }

    @media (max-width: 768px) {
        body {
            background-image: url('images/bg2.png');
        }
    }
</style>