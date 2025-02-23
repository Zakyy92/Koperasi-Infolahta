<?php 
include 'includes/header.php';

$id = isset($_GET['id']) ? $_GET['id'] : null;
$anggota = null;
$title = "Tambah Anggota Baru";

// Inisialisasi nilai default
$default_anggota = [
    'korps' => '',
    'pangkat' => '',
    'status' => 'aktif',
    'nrp' => '',
    'nama_lengkap' => '',
    'jabatan' => '',
    'tanggal_bergabung' => date('Y-m-d')
];


$anggota = $default_anggota;

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM anggota WHERE id = ?");
    $stmt->execute([$id]);
    $result = $stmt->fetch();
    if ($result) {
        $title = "Edit Data Anggota";
        
        $anggota = array_merge($default_anggota, $result);
    }
}

// Proses form 
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $nrp = $_POST['nrp'];
        $nama_lengkap = $_POST['nama_lengkap'];
        $pangkat = $_POST['pangkat'];
        $korps = $_POST['korps'];
        $jabatan = $_POST['jabatan'];
        $status = $_POST['status'];
        $tanggal_bergabung = $_POST['tanggal_bergabung'];

        if ($id) {
            // Update data
            $stmt = $pdo->prepare("
                UPDATE anggota SET 
                    nrp = ?,
                    nama_lengkap = ?,
                    pangkat = ?,
                    korps = ?,
                    jabatan = ?,
                    status = ?,
                    tanggal_bergabung = ?
                WHERE id = ?
            ");
            $stmt->execute([$nrp, $nama_lengkap, $pangkat, $korps, $jabatan, $status, $tanggal_bergabung, $id]);
            $_SESSION['success'] = "Data anggota berhasil diperbarui!";
        } else {
            // Insert data 
            $stmt = $pdo->prepare("
                INSERT INTO anggota (nrp, nama_lengkap, pangkat, korps, jabatan, status, tanggal_bergabung)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$nrp, $nama_lengkap, $pangkat, $korps, $jabatan, $status, $tanggal_bergabung]);
            $_SESSION['success'] = "Data anggota berhasil ditambahkan!";
        }
        header("Location: anggota.php");
        exit();
    } catch (PDOException $e) {
        $error = "Terjadi kesalahan: " . $e->getMessage();
        header("Location: anggota.php?error=" . urlencode($error));
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
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label ms-2">NRP / NIP *</label>
                            <input type="text" name="nrp" class="form-control" required 
                                value="<?php echo htmlspecialchars($anggota['nrp']); ?>" 
                                inputmode="numeric" pattern="[0-9]*" oninput="this.value = this.value.replace(/[^0-9]/g, '');">
                        </div>
                        <div class="mb-3">
                            <label class="form-label ms-2">Nama Lengkap *</label>
                            <input type="text" name="nama_lengkap" class="form-control" required
                                value="<?php echo htmlspecialchars($anggota['nama_lengkap']); ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label ms-2">Pangkat *</label>
                            <select name="pangkat" class="form-select" required>
                                <option value="">Pilih Pangkat</option>
                                <optgroup label="TNI">
                                    <option value="Jenderal TNI" <?php echo ($anggota['pangkat'] == 'Jenderal TNI') ? 'selected' : ''; ?>>Jenderal TNI</option>
                                    <option value="Letnan Jenderal TNI" <?php echo ($anggota['pangkat'] == 'Letnan Jenderal TNI') ? 'selected' : ''; ?>>Letnan Jenderal TNI</option>
                                    <option value="Mayor Jenderal TNI" <?php echo ($anggota['pangkat'] == 'Mayor Jenderal TNI') ? 'selected' : ''; ?>>Mayor Jenderal TNI</option>
                                    <option value="Brigadir Jenderal TNI" <?php echo ($anggota['pangkat'] == 'Brigadir Jenderal TNI') ? 'selected' : ''; ?>>Brigadir Jenderal TNI</option>
                                    <option value="Kolonel" <?php echo ($anggota['pangkat'] == 'Kolonel') ? 'selected' : ''; ?>>Kolonel</option>
                                    <option value="Letnan Kolonel" <?php echo ($anggota['pangkat'] == 'Letnan Kolonel') ? 'selected' : ''; ?>>Letnan Kolonel</option>
                                    <option value="Mayor" <?php echo ($anggota['pangkat'] == 'Mayor') ? 'selected' : ''; ?>>Mayor</option>
                                    <option value="Kapten" <?php echo ($anggota['pangkat'] == 'Kapten') ? 'selected' : ''; ?>>Kapten</option>
                                    <option value="Letnan Satu" <?php echo ($anggota['pangkat'] == 'Letnan Satu') ? 'selected' : ''; ?>>Letnan Satu</option>
                                    <option value="Letnan Dua" <?php echo ($anggota['pangkat'] == 'Letnan Dua') ? 'selected' : ''; ?>>Letnan Dua</option>
                                    <option value="Pembantu Letnan Satu" <?php echo ($anggota['pangkat'] == 'Pembantu Letnan Satu') ? 'selected' : ''; ?>>Pembantu Letnan Satu</option>
                                    <option value="Pembantu Letnan Dua" <?php echo ($anggota['pangkat'] == 'Pembantu Letnan Dua') ? 'selected' : ''; ?>>Pembantu Letnan Dua</option>
                                    <option value="Sersan Mayor" <?php echo ($anggota['pangkat'] == 'Sersan Mayor') ? 'selected' : ''; ?>>Sersan Mayor</option>
                                    <option value="Sersan Kepala" <?php echo ($anggota['pangkat'] == 'Sersan Kepala') ? 'selected' : ''; ?>>Sersan Kepala</option>
                                    <option value="Sersan Satu" <?php echo ($anggota['pangkat'] == 'Sersan Satu') ? 'selected' : ''; ?>>Sersan Satu</option>
                                    <option value="Sersan Dua" <?php echo ($anggota['pangkat'] == 'Sersan Dua') ? 'selected' : ''; ?>>Sersan Dua</option>
                                    <option value="Kopral Kepala" <?php echo ($anggota['pangkat'] == 'Kopral Kepala') ? 'selected' : ''; ?>>Kopral Kepala</option>
                                    <option value="Kopral Satu" <?php echo ($anggota['pangkat'] == 'Kopral Satu') ? 'selected' : ''; ?>>Kopral Satu</option>
                                    <option value="Kopral Dua" <?php echo ($anggota['pangkat'] == 'Kopral Dua') ? 'selected' : ''; ?>>Kopral Dua</option>
                                    <option value="Prajurit Kepala" <?php echo ($anggota['pangkat'] == 'Prajurit Kepala') ? 'selected' : ''; ?>>Prajurit Kepala</option>
                                    <option value="Prajurit Satu" <?php echo ($anggota['pangkat'] == 'Prajurit Satu') ? 'selected' : ''; ?>>Prajurit Satu</option>
                                    <option value="Prajurit Dua" <?php echo ($anggota['pangkat'] == 'Prajurit Dua') ? 'selected' : ''; ?>>Prajurit Dua</option>
                                </optgroup>
                                <optgroup label="PNS">
                                    <option value="Pembina Utama IV/E" <?php echo ($anggota['pangkat'] == 'Pembina Utama IV/E') ? 'selected' : ''; ?>>Pembina Utama IV/E</option>
                                    <option value="Pembina Utama Madya IV/D" <?php echo ($anggota['pangkat'] == 'Pembina Utama Madya IV/D') ? 'selected' : ''; ?>>Pembina Utama Madya IV/D</option>
                                    <option value="Pembina Utama Muda IV/C" <?php echo ($anggota['pangkat'] == 'Pembina Utama Muda IV/C') ? 'selected' : ''; ?>>Pembina Utama Muda IV/C</option>
                                    <option value="Pembina Tingkat 1 IV/B" <?php echo ($anggota['pangkat'] == 'Pembina Tingkat 1 IV/B') ? 'selected' : ''; ?>>Pembina Tingkat 1 IV/B</option>
                                    <option value="Pembina IV/A" <?php echo ($anggota['pangkat'] == 'Pembina IV/A') ? 'selected' : ''; ?>>Pembina IV/A</option>
                                    <option value="Penata Tingkat 1 III/D" <?php echo ($anggota['pangkat'] == 'Penata Tingkat 1 III/D') ? 'selected' : ''; ?>>Penata Tingkat 1 III/D</option>
                                    <option value="Penata III/C" <?php echo ($anggota['pangkat'] == 'Penata III/C') ? 'selected' : ''; ?>>Penata III/C</option>
                                    <option value="Penata Muda Tingkat 1 III/B" <?php echo ($anggota['pangkat'] == 'Penata Muda Tingkat 1 III/B') ? 'selected' : ''; ?>>Penata Muda Tingkat 1 III/B</option>
                                    <option value="Penata Muda III/A" <?php echo ($anggota['pangkat'] == 'Penata Muda III/A') ? 'selected' : ''; ?>>Penata Muda III/A</option>
                                    <option value="Pengatur Tingkat 1 II/D" <?php echo ($anggota['pangkat'] == 'Pengatur Tingkat 1 II/D') ? 'selected' : ''; ?>>Pengatur Tingkat 1 II/D</option>
                                    <option value="Pengatur II/C" <?php echo ($anggota['pangkat'] == 'Pengatur II/C') ? 'selected' : ''; ?>>Pengatur II/C</option>
                                    <option value="Pengatur Muda Tingkat 1 II/B" <?php echo ($anggota['pangkat'] == 'Pengatur Muda Tingkat 1 II/B') ? 'selected' : ''; ?>>Pengatur Muda Tingkat 1 II/B</option>
                                    <option value="Pengatur Muda II/A" <?php echo ($anggota['pangkat'] == 'Pengatur Muda II/A') ? 'selected' : ''; ?>>Pengatur Muda II/A</option>
                                    <option value="Juru Tingkat 1 I/D" <?php echo ($anggota['pangkat'] == 'Juru Tingkat 1 I/D') ? 'selected' : ''; ?>>Juru Tingkat 1 I/D</option>
                                    <option value="Juru I/C" <?php echo ($anggota['pangkat'] == 'Juru I/C') ? 'selected' : ''; ?>>Juru I/C</option>
                                    <option value="Juru Muda Tingkat 1 I/B" <?php echo ($anggota['pangkat'] == 'Juru Muda Tingkat 1 I/B') ? 'selected' : ''; ?>>Juru Muda Tingkat 1 I/B</option>
                                    <option value="Juru Muda I/A" <?php echo ($anggota['pangkat'] == 'Juru Muda I/A') ? 'selected' : ''; ?>>Juru Muda I/A</option>
                                </optgroup>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label ms-2">Korps</label>
                            <select name="korps" class="form-select">
                                <option value="">Pilih Korps</option>
                                <option value=" Inf" <?php echo ($anggota['korps'] == ' Inf') ? 'selected' : ''; ?>>Inf</option>
                                <option value=" Caj" <?php echo ($anggota['korps'] == ' Caj') ? 'selected' : ''; ?>>Caj</option>
                                <option value=" Czi" <?php echo ($anggota['korps'] == ' Czi') ? 'selected' : ''; ?>>Czi</option>
                                <option value=" Cke" <?php echo ($anggota['korps'] == ' Cke') ? 'selected' : ''; ?>>Cke</option>
                                <option value=" Cku" <?php echo ($anggota['korps'] == ' Cku') ? 'selected' : ''; ?>>Cku</option>
                                <option value=" Arh" <?php echo ($anggota['korps'] == ' Arh') ? 'selected' : ''; ?>>Arh</option>
                                <option value=" Arm" <?php echo ($anggota['korps'] == ' Arm') ? 'selected' : ''; ?>>Arm</option>
                                <option value=" Ckm" <?php echo ($anggota['korps'] == ' Ckm') ? 'selected' : ''; ?>>Ckm</option>
                                <option value=" Cpl" <?php echo ($anggota['korps'] == ' Cpl') ? 'selected' : ''; ?>>Cpl</option>
                                <option value=" Cpn" <?php echo ($anggota['korps'] == ' Cpn') ? 'selected' : ''; ?>>Cpn</option>
                                <option value=" Cpm" <?php echo ($anggota['korps'] == ' Cpm') ? 'selected' : ''; ?>>Cpm</option>
                                <option value=" Cba" <?php echo ($anggota['korps'] == ' Cba') ? 'selected' : ''; ?>>Cba</option>
                                <option value=" Ctp" <?php echo ($anggota['korps'] == ' Ctp') ? 'selected' : ''; ?>>Ctp</option>
                                <option value=" Chk" <?php echo ($anggota['korps'] == ' Chk') ? 'selected' : ''; ?>>Chk</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label ms-2">Jabatan *</label>
                            <input type="text" name="jabatan" class="form-control" required 
                                value="<?php echo htmlspecialchars($anggota['jabatan']); ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label ms-2">Status *</label>
                            <select name="status" class="form-select" required>
                                <option value="aktif" <?php echo ($anggota['status'] == 'aktif') ? 'selected' : ''; ?>>Aktif</option>
                                <option value="nonaktif" <?php echo ($anggota['status'] == 'nonaktif') ? 'selected' : ''; ?>>Non-Aktif</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label ms-2">Tanggal Bergabung *</label>
                            <input type="date" name="tanggal_bergabung" class="form-control" required
                                value="<?php echo $anggota['tanggal_bergabung']; ?>">
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Simpan</button>
                            <a href="anggota.php" class="btn btn-secondary">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>