<?php
session_start();

$db_host = 'localhost';
$db_name = 'koperasi';
$db_user = 'root';
$db_pass = '';

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['level']) && $_SESSION['level'] == 'admin';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

function requireAdmin() {
    if (!isAdmin()) {
        header('Location: index.php');
        exit();
    }
}

// Timezone
date_default_timezone_set('Asia/Jakarta'); 