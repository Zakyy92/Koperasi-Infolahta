<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once('lib/tcpdf/tcpdf.php');

class MYPDF extends TCPDF {
    public function Header() {
        // Logo
        $image_file = 'images/logo.jpg';
        if (file_exists($image_file)) {
            $this->Image($image_file, 20, 10, 20, 30, 'JPG');
        }
        
        // Set font
        $this->SetFont('helvetica', 'B', 12);
        
        // Title
        $this->Ln(10);
        $this->Cell(0, 5, 'INFOLAHTADAM IV / DIPONEGORO', 0, 1, 'C');
        
        // Subtitle
        $this->Ln(3);
        $this->SetFont('helvetica', 'B', 12);
        $this->Cell(0, 5, 'DETAIL PINJAMAN', 0, 1, 'C');
    }

    public function Footer() {
        $this->SetY(-15);
        // font
        $this->SetFont('helvetica', 'I', 8);
       
    }
}

try {
    $pdo = new PDO("mysql:host=localhost;dbname=koperasi", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $pinjaman_id = isset($_GET['id']) ? $_GET['id'] : die('ID Pinjaman tidak valid');

    $stmt = $pdo->prepare("
        SELECT p.*, a.nrp, a.nama_lengkap, a.pangkat, a.jabatan, u.nama_lengkap as approved_by_name
        FROM pinjaman p 
        JOIN anggota a ON p.anggota_id = a.id
        LEFT JOIN users u ON p.approved_by = u.id
        WHERE p.id = ?
    ");
    $stmt->execute([$pinjaman_id]);
    $pinjaman = $stmt->fetch();

    $stmt = $pdo->prepare("
        SELECT 
            a.*,
            u.nama_lengkap as petugas,
            (SELECT COUNT(*) 
             FROM angsuran a2 
             WHERE a2.pinjaman_id = a.pinjaman_id 
             AND (a2.tanggal_bayar < a.tanggal_bayar 
                  OR (a2.tanggal_bayar = a.tanggal_bayar AND a2.id <= a.id))
            ) as angsuran_ke,
            (
                SELECT (p.total_angsuran - COALESCE(SUM(a3.jumlah_bayar), 0))
                FROM pinjaman p
                LEFT JOIN angsuran a3 ON a3.pinjaman_id = p.id
                WHERE p.id = a.pinjaman_id
                AND (a3.id IS NULL OR a3.id <= a.id)
            ) as sisa_pinjaman
        FROM angsuran a
        JOIN users u ON a.created_by = u.id
        WHERE a.pinjaman_id = ?
        ORDER BY a.tanggal_bayar ASC, a.id ASC
    ");
    $stmt->execute([$pinjaman_id]);
    $angsuran = $stmt->fetchAll();

    $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Koperasi');
    $pdf->SetTitle('Detail Pinjaman');
    $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, PDF_HEADER_STRING);

    $pdf->SetMargins(15, 30, 15);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

    $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

    $pdf->AddPage();

    // Set font
    $pdf->SetFont('helvetica', '', 11);

    // Set posisi X untuk label dan nilai
    $x_label = 25;
    $x_separator = 70;
    $x_value = 78;

    $pdf->Ln(15);
    
    $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY()); // Garis pertama
    $pdf->Ln(1); 
    $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY()); // Garis kedua
    $pdf->Ln(3); 

    // Data Anggota
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell(0, 7, 'Data Anggota:', 0, 1);
    $pdf->SetFont('helvetica', '', 11);

    // Format data anggota
    $pdf->SetX($x_label);
    $pdf->Cell(50, 7, 'NRP', 0);
    $pdf->SetX($x_separator);
    $pdf->Cell(5, 7, ':', 0);
    $pdf->SetX($x_value);
    $pdf->Cell(0, 7, $pinjaman['nrp'], 0, 1);

    $pdf->SetX($x_label);
    $pdf->Cell(50, 7, 'Nama Lengkap', 0);
    $pdf->SetX($x_separator);
    $pdf->Cell(5, 7, ':', 0);
    $pdf->SetX($x_value);
    $pdf->Cell(0, 7, $pinjaman['nama_lengkap'], 0, 1);

    $pdf->SetX($x_label);
    $pdf->Cell(50, 7, 'Pangkat', 0);
    $pdf->SetX($x_separator);
    $pdf->Cell(5, 7, ':', 0);
    $pdf->SetX($x_value);
    $pdf->Cell(0, 7, $pinjaman['pangkat'], 0, 1);

    $pdf->SetX($x_label);
    $pdf->Cell(50, 7, 'Jabatan', 0);
    $pdf->SetX($x_separator);
    $pdf->Cell(5, 7, ':', 0);
    $pdf->SetX($x_value);
    $pdf->Cell(0, 7, $pinjaman['jabatan'], 0, 1);

    // Detail Pinjaman
    $pdf->Ln(5);
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell(0, 7, 'Detail Pinjaman:', 0, 1);
    $pdf->SetFont('helvetica', '', 11);

    $pdf->SetX($x_label);
    $pdf->Cell(50, 7, 'Jumlah Pinjaman', 0);
    $pdf->SetX($x_separator);
    $pdf->Cell(5, 7, ':', 0);
    $pdf->SetX($x_value);
    $pdf->Cell(0, 7, 'Rp ' . number_format($pinjaman['jumlah_pinjaman'], 0, ',', '.'), 0, 1);

    $pdf->SetX($x_label);
    $pdf->Cell(50, 7, 'Tanggal Pinjam', 0);
    $pdf->SetX($x_separator);
    $pdf->Cell(5, 7, ':', 0);
    $pdf->SetX($x_value);
    $pdf->Cell(0, 7, date('d/m/Y', strtotime($pinjaman['tanggal_pengajuan'])), 0, 1);

    // Riwayat Angsuran
    $pdf->Ln(5);
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell(0, 7, 'Riwayat Angsuran:', 0, 1);
    $pdf->SetFont('helvetica', '', 10);

    // Header tabel
    $pdf->SetFillColor(200, 200, 200);
    $header = array('No', 'Tanggal', 'Angsuran Ke', 'Jumlah Bayar', 'Sisa');
    $w = array(10, 40, 30, 50, 50);

    // Colors, line width and bold font
    $pdf->SetFillColor(200, 200, 200);
    $pdf->SetTextColor(0);
    $pdf->SetDrawColor(0);
    $pdf->SetLineWidth(0.3);
    $pdf->SetFont('', 'B');

    // Header
    for($i=0;$i<count($header);$i++)
        $pdf->Cell($w[$i], 7, $header[$i], 1, 0, 'C', true);
    $pdf->Ln();

    // Data
    $pdf->SetFont('helvetica', '', 10);
    $fill = false;
    $no = 1;
    $running_total = $pinjaman['total_angsuran'];
    
    foreach($angsuran as $row) {
        $running_total -= $row['jumlah_bayar'];
        
        $pdf->Cell($w[0], 6, $no, 1, 0, 'C', $fill);
        $pdf->Cell($w[1], 6, date('d/m/Y', strtotime($row['tanggal_bayar'])), 1, 0, 'C', $fill);
        $pdf->Cell($w[2], 6, $row['angsuran_ke'], 1, 0, 'C', $fill);
        $pdf->Cell($w[3], 6, 'Rp ' . number_format($row['jumlah_bayar'], 0, ',', '.'), 1, 0, 'R', $fill);
        $pdf->Cell($w[4], 6, 'Rp ' . number_format($running_total, 0, ',', '.'), 1, 0, 'R', $fill);
        $pdf->Ln();
        $no++;
        $fill = !$fill;
    }

    $pdf->Cell(array_sum($w), 0, '', 'T');

    // Tanda tangan
    $pdf->Ln(20);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 5, date('d F Y'), 0, 1, 'R');
    $pdf->Cell(0, 5, 'Petugas,', 0, 1, 'R');
    $pdf->Ln(15);
    $pdf->SetFont('helvetica', 'B', 10);
    
    $nama_petugas = isset($_SESSION['nama_lengkap']) ? $_SESSION['nama_lengkap'] : 'Petugas';
    $pdf->Cell(0, 5, $nama_petugas, 0, 1, 'R');

    $pdf->Output('detail_pinjaman_'.$pinjaman_id.'_'.date('Ymd').'.pdf', 'I');

} catch (Exception $e) {
    die('Error: ' . $e->getMessage());
}
?> 