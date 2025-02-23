<?php
require_once 'config/database.php';
session_start();

// Cek admin
if (!isset($_SESSION['user_id']) || $_SESSION['level'] != 'admin') {
    header("Location: login.php");
    exit();
}

try {
    $host = 'localhost';
    $user = 'root'; 
    $pass = '';     
    $db   = 'koperasi_tni';

    $filename = 'backup_koperasi_tni_' . date('Y-m-d_H-i-s') . '.sql';
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    function getTableStructure($pdo, $table) {
        $stmt = $pdo->query("SHOW CREATE TABLE $table");
        $row = $stmt->fetch(PDO::FETCH_NUM);
        return $row[1] . ";\n\n";
    }

    function getTableData($pdo, $table) {
        $output = '';
        $stmt = $pdo->query("SELECT * FROM $table");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($rows as $row) {
            $fields = array_map(function($value) use ($pdo) {
                if ($value === null) return 'NULL';
                return $pdo->quote($value);
            }, $row);
            
            $output .= "INSERT INTO `$table` VALUES (" . implode(", ", $fields) . ");\n";
        }
        
        return $output . "\n";
    }

    echo "-- Backup Database Koperasi TNI\n";
    echo "-- Tanggal: " . date('Y-m-d H:i:s') . "\n\n";
    
    echo "SET FOREIGN_KEY_CHECKS=0;\n\n";
    
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($tables as $table) {
        echo "DROP TABLE IF EXISTS `$table`;\n";
        echo getTableStructure($pdo, $table);
        echo getTableData($pdo, $table);
    }
    
    echo "SET FOREIGN_KEY_CHECKS=1;\n";

    $_SESSION['success'] = "Database berhasil dibackup";

} catch (Exception $e) {
    $_SESSION['error'] = "Gagal membackup database";
    header("Location: pengaturan.php");
    exit();
} 