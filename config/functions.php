<?php
// Format angka ke format rupiah
function formatRupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

function formatTanggal($tanggal) {
    return date('d/m/Y', strtotime($tanggal));
}

// Fungsi untuk membersihkan input
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Fungsi untuk mengecek apakah request adalah POST
function isPost() {
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

// Fungsi untuk mengambil nilai POST dengan aman
function getPost($key, $default = '') {
    return isset($_POST[$key]) ? cleanInput($_POST[$key]) : $default;
}

// Fungsi untuk mengambil nilai GET dengan aman
function getGet($key, $default = '') {
    return isset($_GET[$key]) ? cleanInput($_GET[$key]) : $default;
}

// Fungsi untuk menampilkan pesan error
function showError($message) {
    $_SESSION['error'] = $message;
}

// Fungsi untuk menampilkan pesan sukses
function showSuccess($message) {
    $_SESSION['success'] = $message;
}

// Fungsi untuk generate nomor otomatis
function generateNomor($prefix, $table, $column, $padding = 4) {
    global $pdo;
    
    $query = $pdo->query("SELECT MAX(CAST(SUBSTRING($column, " . (strlen($prefix) + 1) . ") AS UNSIGNED)) as max_num FROM $table WHERE $column LIKE '$prefix%'");
    $result = $query->fetch();
    
    $next_num = ($result['max_num'] ?? 0) + 1;
    return $prefix . str_pad($next_num, $padding, '0', STR_PAD_LEFT);
} 