<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['level'] != 'admin') {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id'])) {
    try {
        $id = $_GET['id'];
        
        $stmt = $pdo->prepare("SELECT pinjaman_id FROM angsuran WHERE id = ?");
        $stmt->execute([$id]);
        $pinjaman_id = $stmt->fetch()['pinjaman_id'];
        
        $stmt = $pdo->prepare("DELETE FROM angsuran WHERE id = ?");
        $stmt->execute([$id]);
        
        $stmt = $pdo->prepare("
            SELECT 
                p.total_angsuran,
                COALESCE(SUM(a.jumlah_bayar), 0) as total_bayar
            FROM pinjaman p
            LEFT JOIN angsuran a ON p.id = a.pinjaman_id
            WHERE p.id = ?
            GROUP BY p.id, p.total_angsuran
        ");
        $stmt->execute([$pinjaman_id]);
        $result = $stmt->fetch();
        
        if ($result['total_bayar'] < $result['total_angsuran']) {
            $stmt = $pdo->prepare("UPDATE pinjaman SET status = 'disetujui' WHERE id = ?");
            $stmt->execute([$pinjaman_id]);
        }
        
        header("Location: angsuran.php?success=Data angsuran berhasil dihapus");
    } catch (PDOException $e) {
        header("Location: angsuran.php?error=Gagal menghapus data angsuran");
    }
} else {
    header("Location: angsuran.php");
}
exit(); 