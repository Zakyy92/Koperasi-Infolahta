<?php
include 'includes/header.php';
?>

<div class="container py-2">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Cetak Laporan PDF</h5>
                </div>
                <div class="card-body">
                    <form action="generate_pdf.php" method="post" target="_blank">
                        <div class="mb-3">
                            <label for="jenis_laporan" class="form-label">Jenis Laporan</label>
                            <select class="form-select" name="jenis_laporan" id="jenis_laporan" required>
                                <option value="">Pilih Jenis Laporan</option>
                                <option value="anggota">Laporan Anggota</option>
                                <option value="pinjaman">Laporan Pinjaman</option>
                                <option value="shu">Laporan SHU</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="tanggal_mulai" class="form-label">Tanggal Mulai</label>
                            <input type="date" class="form-control" name="tanggal_mulai" id="tanggal_mulai" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="tanggal_akhir" class="form-label">Tanggal Akhir</label>
                            <input type="date" class="form-control" name="tanggal_akhir" id="tanggal_akhir" required>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class='bx bx-printer'></i> Cetak PDF
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

