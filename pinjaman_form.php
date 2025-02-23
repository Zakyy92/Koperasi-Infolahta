<?php 
include 'includes/header.php';

$id = isset($_GET['id']) ? $_GET['id'] : null;
$anggota_id = isset($_GET['anggota_id']) ? $_GET['anggota_id'] : null;
$pinjaman = null;
$title = "Pengajuan Pinjaman ";
$tipe_peminjam = null;

if ($id) {
    $stmt = $pdo->prepare("SELECT p.*, a.nama_lengkap, a.nrp FROM pinjaman p JOIN anggota a ON p.anggota_id = a.id WHERE p.id = ?");
    $stmt->execute([$id]);
    $pinjaman = $stmt->fetch();
    if ($pinjaman) {
        $title = "Edit Data Pinjaman";
        $anggota_id = $pinjaman['anggota_id'];
        $tipe_peminjam = "anggota"; 
    }
}

// Ambil data anggota untuk dropdown
$stmt = $pdo->query("SELECT id, nrp, nama_lengkap FROM anggota WHERE status = 'aktif' ORDER BY nama_lengkap");
$anggota_list = $stmt->fetchAll();

// Proses form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $tipe_peminjam = $_POST['tipe_peminjam'];
        $jenis_pinjaman = $_POST['jenis_pinjaman'];
        $anggota_id = isset($_POST['anggota_id']) ? (int)$_POST['anggota_id'] : null;

        // Validasi anggota_id
        if ($tipe_peminjam === 'anggota' && !$anggota_id) {
            throw new Exception("Silakan pilih anggota terlebih dahulu!");
        }

        if ($anggota_id) {
            $check_anggota = $pdo->prepare("SELECT id FROM anggota WHERE id = ?");
            $check_anggota->execute([$anggota_id]);
            if (!$check_anggota->fetch()) {
                throw new Exception("Data anggota tidak ditemukan!");
            }
        }

        // Pindahkan pengambilan jangka waktu ke sini
        $jangka_waktu = (int)$_POST['jangka_waktu'];

        // Proses jumlah pinjaman
        $jumlah_pinjaman = str_replace('.', '', $_POST['jumlah_pinjaman']);
        $jumlah_pinjaman = str_replace(',', '', $jumlah_pinjaman);
        $jumlah_pinjaman = (float)$jumlah_pinjaman;

        if ($jenis_pinjaman === 'lama') {
            $bunga_per_bulan = str_replace('.', '', $_POST['bunga_nominal']);
            $bunga_per_bulan = str_replace(',', '', $bunga_per_bulan);
            $bunga_per_bulan = (float)$bunga_per_bulan;
            $bunga_nominal = $bunga_per_bulan * $jangka_waktu; // Total bunga
            $bunga_persen = 0;
            $status = 'disetujui';

            // Hitung total angsuran dan angsuran per bulan untuk pinjaman lama
            $total_angsuran = $jumlah_pinjaman + ($bunga_per_bulan * $jangka_waktu);
            $angsuran_per_bulan = ($jumlah_pinjaman + $bunga_per_bulan * $jangka_waktu) / $jangka_waktu;
        } else {
            $bunga_persen = ($tipe_peminjam === 'dinas') ? 0 : (float)$_POST['bunga_persen'];
            $bunga_per_bulan = 0;
            $bunga_nominal = 0;
            $status = 'pending';

            // Hitung bunga dan angsuran untuk pinjaman baru
            $bunga = $jumlah_pinjaman * ($bunga_persen / 100) * $jangka_waktu;
            $total_angsuran = $jumlah_pinjaman + $bunga;
            $angsuran_per_bulan = $total_angsuran / $jangka_waktu;
        }

        $tanggal_pengajuan = $_POST['tanggal_pengajuan'];
        $keterangan = $_POST['keterangan'];

        if ($id) {
            $stmt = $pdo->prepare("
                UPDATE pinjaman SET 
                    anggota_id = ?,
                    jumlah_pinjaman = ?,
                    bunga_persen = ?,
                    bunga_nominal = ?,
                    bunga_per_bulan = ?,
                    jangka_waktu = ?,
                    total_angsuran = ?,
                    angsuran_per_bulan = ?,
                    tanggal_pengajuan = ?,
                    keterangan = ?,
                    jenis_pinjaman = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $tipe_peminjam === 'dinas' ? null : $anggota_id, 
                $jumlah_pinjaman, $bunga_persen, $bunga_nominal, $bunga_per_bulan,
                $jangka_waktu, $total_angsuran, $angsuran_per_bulan,
                $tanggal_pengajuan, $keterangan, $jenis_pinjaman, $id
            ]);
            $_SESSION['success'] = "Berhasil mengubah pinjaman";
        } else {
            // Insert data baru
            $stmt = $pdo->prepare("
                INSERT INTO pinjaman (
                    anggota_id, jumlah_pinjaman, bunga_persen, bunga_nominal,
                    bunga_per_bulan, jangka_waktu, total_angsuran, angsuran_per_bulan,
                    tanggal_pengajuan, keterangan, status, jenis_pinjaman
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $tipe_peminjam === 'dinas' ? null : $anggota_id, 
                $jumlah_pinjaman, $bunga_persen, $bunga_nominal, $bunga_per_bulan,
                $jangka_waktu, $total_angsuran, $angsuran_per_bulan,
                $tanggal_pengajuan, $keterangan, $status, $jenis_pinjaman
            ]);
            $_SESSION['success'] = "Berhasil menambahkan pinjaman";
        }

        header("Location: pinjaman.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = "Gagal menambahkan pinjaman";
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
                    <h5 class="card-title"><?php echo $title; ?></h5>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form method="POST" id="pinjamanForm">
                        <div class="mb-3">
                            <label class="form-label ms-2">Jenis Pinjaman *</label>
                            <select name="jenis_pinjaman" class="form-select" required onchange="toggleJenisPinjaman(this.value)">
                                <option value="baru" <?php echo (!isset($pinjaman['jenis_pinjaman']) || $pinjaman['jenis_pinjaman'] == 'baru') ? 'selected' : ''; ?>>Pinjaman Baru</option>
                                <option value="lama" <?php echo (isset($pinjaman['jenis_pinjaman']) && $pinjaman['jenis_pinjaman'] == 'lama') ? 'selected' : ''; ?>>Pinjaman Lama</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label ms-2">Tipe Peminjam *</label>
                            <select name="tipe_peminjam" class="form-select" required onchange="toggleFields(this.value); updateBunga();">
                                <option value="anggota" <?php echo ($tipe_peminjam == 'anggota' || !$tipe_peminjam) ? 'selected' : ''; ?>>Anggota</option>
                                <option value="dinas" <?php echo ($tipe_peminjam == 'dinas') ? 'selected' : ''; ?>>Dinas</option>
                            </select>
                        </div>

                        <div id="anggotaField" class="<?php echo ($tipe_peminjam == 'dinas') ? 'd-none' : ''; ?>">
                            <div class="mb-3">
                                <label class="form-label ms-2">Anggota *</label>
                                <select name="anggota_id" class="form-select ms-2" required <?php echo $anggota_id ? 'disabled' : ''; ?> id="anggotaSelect">
                                    <option value="">Pilih Anggota</option>
                                    <?php foreach ($anggota_list as $a): ?>
                                        <option value="<?php echo $a['id']; ?>" <?php echo ($anggota_id == $a['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($a['nrp'] . ' - ' . $a['nama_lengkap']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if ($anggota_id): ?>
                                    <input type="hidden" name="anggota_id" value="<?php echo $anggota_id; ?>">
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label ms-2" id="labelJumlahPinjaman">Jumlah Pinjaman (Rp) *</label>
                            <input type="text" 
                                name="jumlah_pinjaman" 
                                class="form-control" 
                                required
                                value="<?php echo $pinjaman ? number_format($pinjaman['jumlah_pinjaman'], 0, ',', '.') : ''; ?>"
                                onkeyup="formatNumber(this); hitungAngsuran();">
                        </div>

                        <div class="mb-3">
                            <label class="form-label ms-2">Bunga *</label>
                            <div class="input-group">
                                <input type="text" name="bunga_nominal" class="form-control" 
                                    value="<?php echo $pinjaman ? number_format($pinjaman['bunga_persen'], 0, ',', '.') : '0'; ?>"
                                    onkeyup="formatNumber(this); hitungAngsuranLama();" style="display: none;">
                                <input type="number" name="bunga_persen" class="form-control" required step="0.01"
                                    value="<?php echo $pinjaman ? $pinjaman['bunga_persen'] : '0.8'; ?>"
                                    onchange="hitungAngsuran();" readonly>
                                <span class="input-group-text bunga-label">%</span>
                            </div>
                        </div>

                        <div class="mb-3" id="jangkaWaktuField">
                            <label class="form-label ms-2" id="labelJangkaWaktu">Jangka Waktu (Bulan) *</label>
                            <input type="number" name="jangka_waktu" class="form-control" required min="1"
                                value="<?php echo $pinjaman ? $pinjaman['jangka_waktu'] : '12'; ?>"
                                onchange="hitungAngsuran();">
                        </div>

                        <div class="mb-3">
                            <label class="form-label ms-2">Angsuran per Bulan</label>
                            <input type="text" id="angsuran_per_bulan" class="form-control" readonly>
                        </div>

                        <div class="mb-3">
                            <label class="form-label ms-2">Tanggal Pengajuan *</label>
                            <input type="date" name="tanggal_pengajuan" class="form-control" required
                                value="<?php echo $pinjaman ? $pinjaman['tanggal_pengajuan'] : date('Y-m-d'); ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label ms-2">Keterangan</label>
                            <textarea name="keterangan" class="form-control" rows="3"><?php echo $pinjaman ? htmlspecialchars($pinjaman['keterangan']) : ''; ?></textarea>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-primary" onclick="document.getElementById('pinjamanForm').submit();">Simpan</button>
                            <a href="<?php echo $anggota_id ? "anggota_detail.php?id=$anggota_id" : 'pinjaman.php'; ?>" class="btn btn-secondary">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
<script>
function formatNumber(input) {
    let value = input.value.replace(/\D/g, '');
    input.value = new Intl.NumberFormat('id-ID').format(value);
}

function hitungAngsuran() {
    let jumlah = document.getElementsByName('jumlah_pinjaman')[0].value.replace(/\D/g, '');
    let bunga = document.getElementsByName('bunga_persen')[0].value || 0; // Bunga 0 jika dinas
    let jangka = document.getElementsByName('jangka_waktu')[0].value;
    
    if (jumlah && jangka) {
        let totalBunga = jumlah * (bunga / 100) * jangka; // Total bunga untuk jangka waktu
        let total = parseInt(jumlah) + parseInt(totalBunga); // Total angsuran
        let angsuran = total / jangka; // Angsuran per bulan
        
        document.getElementById('angsuran_per_bulan').value = 
            'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(angsuran));
    }
}

function toggleFields(tipe) {
    const anggotaField = document.getElementById('anggotaField');
    const anggotaSelect = document.getElementsByName('anggota_id')[0];
    const bungaInput = document.getElementsByName('bunga_persen')[0];

    if (tipe === 'dinas') {
        anggotaField.classList.add('d-none'); 
        anggotaSelect.value = ''; 
        anggotaSelect.disabled = true; 
        bungaInput.value = 0; 
        bungaInput.disabled = true; 
    } else {
        anggotaField.classList.remove('d-none'); 
        anggotaSelect.disabled = false; 
        bungaInput.disabled = false; 
    }
}

function toggleJenisPinjaman(jenis) {
    const bungaPersen = document.getElementsByName('bunga_persen')[0];
    const bungaNominal = document.getElementsByName('bunga_nominal')[0];
    const bungaLabel = document.querySelector('.bunga-label');
    const labelJumlahPinjaman = document.getElementById('labelJumlahPinjaman');
    const labelJangkaWaktu = document.getElementById('labelJangkaWaktu');
    const jangkaWaktuInput = document.getElementsByName('jangka_waktu')[0];
    
    if (jenis === 'lama') {
        bungaPersen.style.display = 'none';
        bungaNominal.style.display = 'block';
        bungaLabel.textContent = 'Rp';
        labelJumlahPinjaman.textContent = 'Sisa Pinjaman (Rp) *';
        labelJangkaWaktu.textContent = 'Sisa Jangka Waktu (Bulan) *';
        jangkaWaktuInput.value = 1;
        // Tambahkan event listener untuk pinjaman lama
        document.getElementsByName('jumlah_pinjaman')[0].onkeyup = function() {
            formatNumber(this);
            hitungAngsuranLama();
        };
        document.getElementsByName('bunga_nominal')[0].onkeyup = function() {
            formatNumber(this);
            hitungAngsuranLama();
        };
        jangkaWaktuInput.onchange = hitungAngsuranLama;
        // Hitung angsuran awal
        hitungAngsuranLama();
    } else {
        bungaPersen.style.display = 'block';
        bungaNominal.style.display = 'none';
        bungaLabel.textContent = '%';
        labelJumlahPinjaman.textContent = 'Jumlah Pinjaman (Rp) *';
        labelJangkaWaktu.textContent = 'Jangka Waktu (Bulan) *';
        jangkaWaktuInput.value = 12;
        // Kembalikan event listener untuk pinjaman baru
        document.getElementsByName('jumlah_pinjaman')[0].onkeyup = function() {
            formatNumber(this);
            hitungAngsuran();
        };
        jangkaWaktuInput.onchange = hitungAngsuran;
        updateBunga();
    }
}

function hitungAngsuranLama() {
    let sisaPinjaman = document.getElementsByName('jumlah_pinjaman')[0].value.replace(/\D/g, '');
    let bungaPerBulan = document.getElementsByName('bunga_nominal')[0].value.replace(/\D/g, '');
    let sisaJangkaWaktu = document.getElementsByName('jangka_waktu')[0].value;
    
    if (sisaPinjaman && sisaJangkaWaktu) {
        sisaPinjaman = parseInt(sisaPinjaman);
        bungaPerBulan = parseInt(bungaPerBulan || 0);
        sisaJangkaWaktu = parseInt(sisaJangkaWaktu);
        
        // Total pinjaman = Sisa pinjaman + (bunga per bulan × sisa jangka waktu)
        let totalBunga = bungaPerBulan * sisaJangkaWaktu;
        let totalPinjaman = sisaPinjaman + totalBunga;
        let angsuranPerBulan = totalPinjaman / sisaJangkaWaktu;
        
        document.getElementById('angsuran_per_bulan').value = 
            'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(angsuranPerBulan));
    }
}

function updateBunga() {
    const tipePeminjam = document.getElementsByName('tipe_peminjam')[0].value;
    const jenisPinjaman = document.getElementsByName('jenis_pinjaman')[0].value;
    const bungaInput = document.getElementsByName('bunga_persen')[0];
    const bungaNominal = document.getElementsByName('bunga_nominal')[0];

    if (jenisPinjaman === 'baru') {
        if (tipePeminjam === 'anggota') {
            bungaInput.value = '0.8';
            bungaInput.disabledbungaInput.disabled = false;
        } else {
            bungaInput.value = '0';
            bungaInput.disabled = true;
        }
        hitungAngsuran();
    }
}

$(document).ready(function() {
    $('#anggotaSelect').select2({
        placeholder: "Pilih Anggota",
        allowClear: true
    });
    
    $('select[name="tipe_peminjam"]').change(function() {
        const tipe = $(this).val();
        if (tipe === 'anggota') {
            $('#anggotaSelect').prop('disabled', false);
        } else {
            $('#anggotaSelect').prop('disabled', true).val(null).trigger('change');
        }
    });

    // Inisialisasi tampilan pada saat load
    toggleFields(document.getElementsByName('tipe_peminjam')[0].value);
    toggleJenisPinjaman(document.getElementsByName('jenis_pinjaman')[0].value);
    
    // Tambahkan event listener untuk perubahan jenis pinjaman
    document.getElementsByName('jenis_pinjaman')[0].onchange = function() {
        toggleJenisPinjaman(this.value);
    };
});
</script>