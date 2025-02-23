<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['level'] != 'admin') {
    header('Location: login.php');
    exit();
}

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    try {
        $stmt = $pdo->prepare("SELECT status FROM pinjaman WHERE id = ?");
        $stmt->execute([$id]);
        $pinjaman = $stmt->fetch();
        
        if ($pinjaman && $pinjaman['status'] == 'ditolak') {
            // Hapus pinjaman
            $stmt = $pdo->prepare("DELETE FROM pinjaman WHERE id = ?");
            $stmt->execute([$id]);
            
            $_SESSION['success'] = "Berhasil menghapus pinjaman";
        } else {
            $_SESSION['error'] = "Gagal menghapus pinjaman";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Gagal menghapus pinjaman";
    }
}

header('Location: pinjaman.php');
exit();