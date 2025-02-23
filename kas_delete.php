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
        
        $stmt = $pdo->prepare("SELECT jenis_kas FROM kas WHERE id = ?");
        $stmt->execute([$id]);
        $kas = $stmt->fetch();
        
        if ($kas) {
            // Hapus data kas
            $stmt = $pdo->prepare("DELETE FROM kas WHERE id = ?");
            $stmt->execute([$id]);
            
            $_SESSION['success'] = "Berhasil menghapus kas";
        } else {
            $_SESSION['error'] = "Gagal menghapus kas";
        }
        
    } catch (PDOException $e) {
        $_SESSION['error'] = "Gagal menghapus kas";
    }
} else {
    $_SESSION['error'] = "Gagal menghapus kas";
}

header("Location: kas.php");
exit(); 