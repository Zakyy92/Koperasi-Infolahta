<?php
ob_start();
error_reporting(0);
session_start();

require_once 'config/database.php';
require_once 'lib/tcpdf/tcpdf.php'; 

try {
    $jenis = $_GET['jenis'] ?? '';
    $id = $_GET['id'] ?? null;
    $bulan = $_GET['bulan'] ?? date('m');
    $tahun = $_GET['tahun'] ?? date('Y');
    $tanggal_awal = $_GET['tanggal_awal'] ?? date('Y-m-01');
    $tanggal_akhir = $_GET['tanggal_akhir'] ?? date('Y-m-t');

    // TCPDF
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    $pdf->SetCreator('INFOLAHTA');
    $pdf->SetAuthor('Admin Koperasi');

    $judul_laporan = '';
    switch ($jenis) {
        case 'kas_masuk_keluar':
            $judul_laporan = 'Laporan Kas Masuk Keluar';
            break;
        case 'kas_masuk':
            $judul_laporan = 'Laporan Kas Masuk';
            break;
        case 'kas_keluar':
            $judul_laporan = 'Laporan Kas Keluar';
            break;
        default:
            $judul_laporan = 'Laporan Kas';
    }

    $pdf->SetTitle($judul_laporan);

    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);

    $pdf->SetMargins(15, 15, 15); //  margin kiri dan kanan
    $pdf->AddPage('P', 'A4');

    // Setelah setup awal PDF, tambahkan:
    $pdf->SetAutoPageBreak(true, 15); // Enable auto page break dengan margin 15mm di bawah

    // Tambahkan fungsi formatTanggal
    function formatTanggal($dateStr) {
        $bulanIndo = [
            '01' => 'Januari',
            '02' => 'Februari',
            '03' => 'Maret',
            '04' => 'April',
            '05' => 'Mei', 
            '06' => 'Juni', 
            '07' => 'Juli', 
            '08' => 'Agustus', 
            '09' => 'September', 
            '10' => 'Oktober', 
            '11' => 'November', 
            '12' => 'Desember'
        ];
        $tgl = date('d', strtotime($dateStr));
        $bln = date('m', strtotime($dateStr));
        $thn = date('Y', strtotime($dateStr));
        return $tgl . ' ' . $bulanIndo[$bln] . ' ' . $thn;
    }

    // Fungsi untuk mencetak header tabel
    function printTableHeader($pdf, $type = 'kas_masuk_keluar', $isFirstPage = true) {
        if ($isFirstPage) {
            // Set margin dan posisi awal
            $left_margin = 20;
            $pdf->SetY(10);
            
            // Logo dengan ukuran dan posisi yang disesuaikan
            $logo_width = 18;
            $logo_height = 25; // Sesuaikan dengan aspek rasio logo
            $pdf->Image('images/logo.jpg', $left_margin, 10, $logo_width, $logo_height);
            
            // Judul-judul dengan posisi yang disejajarkan
            $pdf->SetFont('helvetica', 'B', 12);
            
            // Judul Utama - posisi y disesuaikan dengan logo
            $pdf->SetY(12);
            $pdf->Cell(0, 6, 'INFOLAHTADAM IV / DIPONEGORO', 0, 1, 'C');
            
            // Sub Judul (Jenis Laporan)
            if ($type == 'kas_masuk') {
                $pdf->Cell(0, 6, 'Laporan Kas Masuk', 0, 1, 'C');
            } else if ($type == 'kas_keluar') {
                $pdf->Cell(0, 6, 'Laporan Kas Keluar', 0, 1, 'C');
            } else {
                $pdf->Cell(0, 6, 'Laporan Kas Masuk Keluar', 0, 1, 'C');
            }
            
            // Periode dengan font yang lebih kecil
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Cell(0, 6, 'Periode: ' . formatTanggal($GLOBALS['tanggal_awal']) . ' s/d ' . formatTanggal($GLOBALS['tanggal_akhir']), 0, 1, 'C');
            $pdf->Ln(10);
        }

        // Header tabel
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetFillColor(200, 200, 200);
        
        // Definisikan lebar kolom
        $col_no = 8;
        $col_tanggal = 20;
        $col_keterangan = 90;
        $col_nomor = 16;
        $col_jumlah = 25;
        
        if ($type == 'kas_masuk_keluar') {
            $col_keterangan = 60;
            $col_km = 25;
            $col_kk = 25;
            $col_saldo = 25;
            
            $pdf->Cell($col_no, 6, 'No', 1, 0, 'C', true);
            $pdf->Cell($col_tanggal, 6, 'Tanggal', 1, 0, 'C', true);
            $pdf->Cell($col_keterangan, 6, 'Keterangan', 1, 0, 'C', true);
            $pdf->Cell($col_nomor, 6, 'Nomor', 1, 0, 'C', true);
            $pdf->Cell($col_km, 6, 'KM (Rp)', 1, 0, 'C', true);
            $pdf->Cell($col_kk, 6, 'KK (Rp)', 1, 0, 'C', true);
            $pdf->Cell($col_saldo, 6, 'Saldo (Rp)', 1, 1, 'C', true);
        } else {
            $pdf->Cell(10, 6, 'No', 1, 0, 'C', true);
            $pdf->Cell(25, 6, 'Tanggal', 1, 0, 'C', true);
            $pdf->Cell(100, 6, 'Keterangan', 1, 0, 'C', true);
            $pdf->Cell(19, 6, 'Nomor', 1, 0, 'C', true);
            $pdf->Cell(25, 6, 'Jumlah (Rp)', 1, 1, 'C', true);
        }

        // Set font ke normal setelah header
        $pdf->SetFont('helvetica', '', 10);
    }

    // Fungsi untuk mengecek apakah perlu halaman baru
    function checkPageBreak($pdf, $height, $type = 'kas_masuk_keluar') {
        $limit = $pdf->GetPageHeight() - 25; // Margin bawah ditambah
        if ($pdf->GetY() + $height > $limit) {
            $pdf->AddPage();
            printTableHeader($pdf, $type, false);
            return true;
        }
        return false;
    }

    // Pada bagian awal cetak tabel
    printTableHeader($pdf, $jenis, true);

    switch ($jenis) {
        case 'kas_masuk_keluar':
            $query = "
            SELECT tanggal, nomor, keterangan, jenis_kas, jumlah, @saldo := @saldo + IF(jenis_kas = 'masuk', jumlah, -jumlah) as saldo
            FROM kas, (SELECT @saldo := 0) as temp
            WHERE tanggal BETWEEN ? AND ?
            ORDER BY tanggal ASC, id ASC
            ";

            $stmt = $pdo->prepare($query);
            $stmt->execute([$tanggal_awal, $tanggal_akhir]);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Lebar kolom yang dioptimalkan
            $col_no = 8;
            $col_tanggal = 20;
            $col_keterangan = 60;
            $col_nomor = 16;
            $col_km = 25;
            $col_kk = 25;
            $col_saldo = 25;

            // Isi Tabel
            $pdf->SetFont('helvetica', '', 10);
            $no = 1;
            $total_km = 0;
            $total_kk = 0;

            foreach ($data as $row) {
                // Hitung tinggi yang dibutuhkan untuk keterangan
                $pdf->startTransaction();
                $temp_y = $pdf->GetY();
                $pdf->MultiCell($col_keterangan, 6, $row['keterangan'], 1, 'L');
                $height = $pdf->GetY() - $temp_y;
                $pdf->rollbackTransaction(true);

                // Cek apakah perlu halaman baru
                if (checkPageBreak($pdf, $height, $jenis)) {
                    // Jika halaman baru ditambahkan, header sudah dicetak oleh checkPageBreak
                }

                $start_y = $pdf->GetY();
                
                // Cetak baris data
                $pdf->Cell($col_no, $height, $no++, 1, 0, 'C');
                $pdf->Cell($col_tanggal, $height, date('d/m/Y', strtotime($row['tanggal'])), 1, 0, 'C');
                
                $x_after_date = $pdf->GetX();
                $y_after_date = $pdf->GetY();
                
                // Cetak keterangan
                $pdf->MultiCell($col_keterangan, 6, $row['keterangan'], 1, 'L');
                
                // Kembali ke posisi setelah keterangan
                $pdf->SetXY($x_after_date + $col_keterangan, $y_after_date);
                
                // Cetak kolom-kolom sisanya
                $pdf->Cell($col_nomor, $height, $row['nomor'], 1, 0, 'L');
                
                if ($row['jenis_kas'] == 'masuk') {
                    $pdf->Cell($col_km, $height, number_format($row['jumlah'], 0, ',', '.'), 1, 0, 'R');
                    $pdf->Cell($col_kk, $height, '-', 1, 0, 'C');
                    $total_km += $row['jumlah'];
                } else {
                    $pdf->Cell($col_km, $height, '-', 1, 0, 'C');
                    $pdf->Cell($col_kk, $height, number_format($row['jumlah'], 0, ',', '.'), 1, 0, 'R');
                    $total_kk += $row['jumlah'];
                }
                
                $pdf->Cell($col_saldo, $height, number_format($row['saldo'], 0, ',', '.'), 1, 1, 'R');
            }

            // Total di akhir
            if ($pdf->GetY() + 6 > ($pdf->GetPageHeight() - 25)) {
                $pdf->AddPage();
                printTableHeader($pdf);
            }
            
            $total_width = $col_no + $col_tanggal + $col_keterangan + $col_nomor;
            $pdf->SetFont('helvetica', 'B', 8);
            $pdf->Cell($total_width, 6, 'Total (Rp)', 1, 0, 'R', true);
            $pdf->Cell($col_km, 6, number_format($total_km, 0, ',', '.'), 1, 0, 'R', true);
            $pdf->Cell($col_kk, 6, number_format($total_kk, 0, ',', '.'), 1, 0, 'R', true);
            $pdf->Cell($col_saldo, 6, number_format($total_km - $total_kk, 0, ',', '.'), 1, 1, 'R', true);
            break;

        case 'kas_masuk':
            // Query untuk kas masuk
            $query = "
            SELECT tanggal, nomor, keterangan, jumlah, @saldo := @saldo + jumlah as saldo
            FROM kas, (SELECT @saldo := 0) as temp
            WHERE jenis_kas = 'masuk' AND tanggal BETWEEN ? AND ?
            ORDER BY tanggal ASC, id ASC
            ";

            $stmt = $pdo->prepare($query);
            $stmt->execute([$tanggal_awal, $tanggal_akhir]);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Lebar kolom yang dioptimalkan
            $col_no = 10;
            $col_tanggal = 25;
            $col_keterangan = 100;
            $col_nomor = 19;
            $col_jumlah = 25;

            // Isi Tabel
            $pdf->SetFont('helvetica', '', 10);
            $no = 1;
            $total = 0;

            foreach ($data as $row) {
                // Hitung tinggi yang dibutuhkan untuk keterangan
                $pdf->startTransaction();
                $temp_y = $pdf->GetY();
                $pdf->MultiCell($col_keterangan, 6, $row['keterangan'], 1, 'L');
                $height = $pdf->GetY() - $temp_y;
                $pdf->rollbackTransaction(true);

                // Cek apakah perlu halaman baru
                if (checkPageBreak($pdf, $height, $jenis)) {
                    // Jika halaman baru ditambahkan, header sudah dicetak oleh checkPageBreak
                }

                $start_y = $pdf->GetY();
                
                // Cetak baris data
                $pdf->Cell($col_no, $height, $no++, 1, 0, 'C');
                $pdf->Cell($col_tanggal, $height, date('d/m/Y', strtotime($row['tanggal'])), 1, 0, 'C');
                
                $x_after_date = $pdf->GetX();
                $y_after_date = $pdf->GetY();
                
                // Cetak keterangan
                $pdf->MultiCell($col_keterangan, 6, $row['keterangan'], 1, 'L');
                
                // Kembali ke posisi setelah keterangan
                $pdf->SetXY($x_after_date + $col_keterangan, $y_after_date);
                
                $pdf->Cell($col_nomor, $height, $row['nomor'], 1, 0, 'L');
                $pdf->Cell($col_jumlah, $height, number_format($row['jumlah'], 0, ',', '.'), 1, 1, 'R');
                $total += $row['jumlah'];
            }

            // Total
            $total_width = $col_no + $col_tanggal + $col_keterangan + $col_nomor;
            $pdf->SetFont('helvetica', 'B', 8);
            $pdf->Cell($total_width, 6, 'Total (Rp)', 1, 0, 'R', true);
            $pdf->Cell($col_jumlah, 6, number_format($total, 0, ',', '.'), 1, 1, 'R', true);
            break;

        case 'kas_keluar':
            // Query untuk kas keluar
            $query = "
            SELECT tanggal, nomor, keterangan, jumlah, @saldo := @saldo + jumlah as saldo
            FROM kas, (SELECT @saldo := 0) as temp
            WHERE jenis_kas = 'keluar' AND tanggal BETWEEN ? AND ?
            ORDER BY tanggal ASC, id ASC
            ";

            $stmt = $pdo->prepare($query);
            $stmt->execute([$tanggal_awal, $tanggal_akhir]);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Lebar kolom yang dioptimalkan
            $col_no = 10;
            $col_tanggal = 25;
            $col_keterangan = 100;
            $col_nomor = 19;
            $col_jumlah = 25;

            // Isi Tabel
            $pdf->SetFont('helvetica', '', 10);
            $no = 1;
            $total = 0;

            foreach ($data as $row) {
                $start_y = $pdf->GetY();
                
                $pdf->startTransaction();
                $temp_y = $pdf->GetY();
                $pdf->MultiCell($col_keterangan, 6, $row['keterangan'], 1, 'L');
                $height = $pdf->GetY() - $temp_y;
                $pdf->rollbackTransaction(true);
                
                // Cek apakah perlu halaman baru
                if (checkPageBreak($pdf, $height, $jenis)) {
                    $start_y = $pdf->GetY();
                }
                
                // Cetak baris data
                $pdf->SetY($start_y);
                $pdf->Cell($col_no, $height, $no++, 1, 0, 'C');
                $pdf->Cell($col_tanggal, $height, date('d/m/Y', strtotime($row['tanggal'])), 1, 0, 'C');
                
                $x_after_date = $pdf->GetX();
                $pdf->MultiCell($col_keterangan, 6, $row['keterangan'], 1, 'L');
                
                $pdf->SetXY($x_after_date + $col_keterangan, $start_y);
                $pdf->Cell($col_nomor, $height, $row['nomor'], 1, 0, 'L');
                $pdf->Cell($col_jumlah, $height, number_format($row['jumlah'], 0, ',', '.'), 1, 1, 'R');
                $total += $row['jumlah'];
            }

            // Total
            $total_width = $col_no + $col_tanggal + $col_keterangan + $col_nomor;
            $pdf->SetFont('helvetica', 'B', 8);
            $pdf->Cell($total_width, 6, 'Total (Rp)', 1, 0, 'R', true);
            $pdf->Cell($col_jumlah, 6, number_format($total, 0, ',', '.'), 1, 1, 'R', true);
            break;

        default:
            echo "Jenis laporan tidak valid.";
            exit;
    }

    ob_end_clean();

    // Output PDF
    $pdf->Output('laporan_' . $jenis . '_' . date('Ymd') . '.pdf', 'I');
} catch (Exception $e) {
    ob_end_clean();
    die('Error: ' . $e->getMessage());
}

exit;