<?php
require_once 'config/database.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['level'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Proses tambah user baru
if (isset($_POST['add_user'])) {
    try {
        $username = $_POST['username'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $nama_lengkap = $_POST['nama_lengkap'];
        $level = $_POST['level'];

        // Cek username sudah ada atau belum
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("Username sudah digunakan!");
        }

        // Insert user baru
        $stmt = $pdo->prepare("
            INSERT INTO users (username, password, nama_lengkap, level)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$username, $password, $nama_lengkap, $level]);

        $_SESSION['success'] = "User berhasil ditambahkan";
        header("Location: pengaturan.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header("Location: pengaturan.php");
        exit();
    }
}

// edit user
elseif (isset($_POST['edit_user'])) {
    try {
        $id = $_POST['id'];
        $username = $_POST['username'];
        $nama_lengkap = $_POST['nama_lengkap'];
        $level = $_POST['level'];
        $status = $_POST['status'];

        // Cek username sudah ada atau belum (kecuali untuk user yang sedang diedit)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? AND id != ?");
        $stmt->execute([$username, $id]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("Username sudah digunakan!");
        }

        // Update user
        if (!empty($_POST['password'])) {
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("
                UPDATE users SET 
                    username = ?,
                    password = ?,
                    nama_lengkap = ?,
                    level = ?,
                    status = ?
                WHERE id = ?
            ");
            $stmt->execute([$username, $password, $nama_lengkap, $level, $status, $id]);
        } else {
            $stmt = $pdo->prepare("
                UPDATE users SET 
                    username = ?,
                    nama_lengkap = ?,
                    level = ?,
                    status = ?
                WHERE id = ?
            ");
            $stmt->execute([$username, $nama_lengkap, $level, $status, $id]);
        }

        $_SESSION['success'] = "Data user berhasil diperbarui";
        header("Location: pengaturan.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header("Location: pengaturan.php");
        exit();
    }
}

// hapus user
elseif (isset($_GET['action']) && $_GET['action'] == 'delete') {
    try {
        $id = $_GET['id'];

        // Cek apakah user yang akan dihapus adalah user yang sedang login
        if ($id == $_SESSION['user_id']) {
            throw new Exception("Tidak dapat menghapus user yang sedang aktif!");
        }

        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);

        $_SESSION['success'] = "Berhasil menghapus data user";
        header("Location: pengaturan.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header("Location: pengaturan.php");
        exit();
    }
}

header("Location: pengaturan.php");
exit();