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
        
        // Hapus data anggota
        $stmt = $pdo->prepare("DELETE FROM anggota WHERE id = ?");
        $stmt->execute([$id]);
        
        $_SESSION['success'] = "Data anggota berhasil dihapus!";
        header("Location: anggota.php");
    } catch (PDOException $e) {
        $_SESSION['error'] = "Gagal menghapus data";
        header("Location: anggota.php");
    }
} else {
    $_SESSION['error'] = "Gagal menghapus data";
    header("Location: anggota.php");
}
exit(); 