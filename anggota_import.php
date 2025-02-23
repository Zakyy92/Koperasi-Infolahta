<?php
require_once 'config/database.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        if (!isset($_FILES['file']['tmp_name'])) {
            $_SESSION['error'] = 'Pilih file CSV terlebih dahulu!';
            header("Location: anggota.php");
            exit;
        }

        $file = $_FILES['file']['tmp_name'];
        
        // Baca file CSV 
        $handle = fopen($file, "r");
        if ($handle === FALSE) {
            $_SESSION['error'] = 'Gagal membuka file CSV!';
            header("Location: anggota.php");
            exit;
        }
        
        
        fgetcsv($handle, 1000, ";");
        
        $pdo->beginTransaction();
        $check_nrp = $pdo->prepare("SELECT COUNT(*) FROM anggota WHERE nrp = ?");
        $stmt = $pdo->prepare("
            INSERT INTO anggota (
                nrp, nama_lengkap, pangkat, korps, 
                jabatan, status, tanggal_bergabung,
                created_at
            ) VALUES (
                ?, ?, ?, ?, 
                ?, ?, ?,
                NOW()
            )
        ");

        $success = 0;
        $duplicate_nrp = 0;
        $errors = [];
        while (($row = fgetcsv($handle, 1000, ";")) !== FALSE) {
            try {
                if (empty($row[0])) continue; 
            
                $row = array_map('trim', $row);
                
                $check_nrp->execute([$row[0]]);
                if ($check_nrp->fetchColumn() > 0) {
                    $duplicate_nrp++;
                    continue; 
                }
                
                $status = !empty($row[5]) ? strtolower($row[5]) : 'aktif';
                
                $tanggal = !empty($row[6]) ? $row[6] : date('Y-m-d');
                if (!empty($row[6])) {
                    // format tanggal dd/mm/yyyy
                    if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $row[6], $matches)) {
                        $tanggal = $matches[3] . '-' . $matches[2] . '-' . $matches[1];
                    }
                    // Cek format tanggal yyyy-mm-dd
                    elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $row[6])) {
                        throw new Exception("Format tanggal tidak valid pada baris " . ($success + 2) . ". Gunakan format dd/mm/yyyy atau yyyy-mm-dd");
                    }
                }
                
                $stmt->execute([
                    $row[0], // NRP
                    $row[1], // Nama Lengkap
                    $row[2], // Pangkat
                    $row[3], // Korps
                    $row[4], // Jabatan
                    $status,
                    $tanggal
                ]);
                $success++;
            } catch (PDOException $e) {
                $errors[] = "Baris " . ($success + $duplicate_nrp + 2) . ": " . $e->getMessage();
            } catch (Exception $e) {
                $errors[] = $e->getMessage();
            }
        }

        fclose($handle);
        
        if ($success > 0 || $duplicate_nrp > 0) {
            $pdo->commit();
            $message = [];
            
            if ($success > 0) {
                $message[] = "Berhasil mengimport $success data";
            }
            if ($duplicate_nrp > 0) {
                $message[] = "$duplicate_nrp data NRP sudah ada";
            }
            if (!empty($errors)) {
                $message[] = "Error pada:\n" . implode("\n", $errors);
            }
            
            $_SESSION['success'] = implode(". ", $message);
        } else {
            $pdo->rollBack();
            $_SESSION['error'] = 'Tidak ada data yang berhasil diimport!';
        }
        
    } catch (Exception $e) {
        if (isset($pdo)) {
            $pdo->rollBack();
        }
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
    
    header("Location: anggota.php");
    exit;
}

header("Location: anggota.php");
exit; 