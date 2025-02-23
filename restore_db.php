<?php
require_once 'config/database.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['level'] != 'admin') {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['backup_file'])) {
    try {
        $file = $_FILES['backup_file'];
        
        // Validasi file
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Error saat upload file');
        }

        // Cek ekstensi file
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'sql') {
            throw new Exception('File harus berformat .sql');
        }

        // Baca isi file SQL
        $sql_content = file_get_contents($file['tmp_name']);
        if ($sql_content === false) {
            throw new Exception('Gagal membaca file SQL');
        }

        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

        // Split file SQL menjadi query-query terpisah
        $queries = array_filter(
            explode(';', $sql_content),
            function($query) {
                return trim($query) != '';
            }
        );

        foreach ($queries as $query) {
            $query = trim($query);
            if (!empty($query)) {
                try {
                    $pdo->exec($query);
                } catch (PDOException $e) {
                    continue; 
                }
            }
        }

        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

        $_SESSION['success'] = "Database berhasil direstore!";
        header("Location: pengaturan.php");
        exit();

    } catch (Exception $e) {
        $_SESSION['error'] = "Gagal melakukan restore database";
        header("Location: pengaturan.php");
        exit();
    }
}

header("Location: pengaturan.php");
exit();