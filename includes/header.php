<?php
ob_start();
session_start();
require_once 'config/database.php';

// Cek session untuk halaman yang membutuhkan login
if (!isset($_SESSION['user_id']) && basename($_SERVER['PHP_SELF']) != 'login.php') {
    header("Location: login.php");
    exit();
}

// Menghitung jumlah pinjaman yang belum lunas
$stmt = $pdo->query("SELECT COUNT(*) as total FROM pinjaman WHERE status = 'disetujui' AND status != 'lunas'");
$unpaid_loans_count = $stmt->fetch()['total'];

// Menghitung jumlah pinjaman yang belum disetujui (pending)
$stmt = $pdo->query("SELECT COUNT(*) as total FROM pinjaman WHERE status = 'pending'");
$pending_loans_count = $stmt->fetch()['total'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Koperasi Infolahtadam IV / Diponegoro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@2.0.7/css/boxicons.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="images/logo1.png">

    <style>
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100%;
            width: 250px;
            background: linear-gradient(135deg, black 30%, #000080 );
            padding-top: 20px;
            color: white;
        }
        
        .sidebar .navbar-brand {
            color: white;
            padding: 10px 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.25rem;
            text-decoration: none;
            margin-bottom: 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.5);
            padding-bottom: 15px;
        }
        
        .sidebar .nav-link {
            color: rgba(255,255,255,.8);
            padding: 10px 20px;
            display: flex;
            align-items: center;
            gap: 10px;  
        }
        
        .sidebar .nav-link:hover {
            color: white;
            background-color: rgba(255, 255, 255, 0.25);
        }
        
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        
        .user-profile {
            padding: 10px 20px;
            border-top: 1px solid rgba(255,255,255,.1);
            margin-top: auto;
        }
        
        .sidebar-nav {
            height: calc(100% - 70px);
            display: flex;
            flex-direction: column;
        }
        
        .sidebar-logo {
            width: 35px;
            height: 35px;
            object-fit: contain;
        }
        
        .user-menu {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .user-menu .nav-link {
            padding: 8px 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: rgba(255,255,255,.8);
            text-decoration: none;
        }

        /* Hapus hover effect untuk user profile, tapi tetap pertahankan untuk logout */
        .user-menu a:not([href]) {
            cursor: default;
        }
        
        .user-menu a:not([href]):hover {
            color: rgba(255,255,255,.8);
            background-color: transparent;
        }

        .user-menu a[href]:hover {
            color: white;
            background-color: #495057;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: auto;
                padding: 0;
                z-index: 1030;
            }
            
            .sidebar .navbar-brand {
                padding: 15px;
                margin-bottom: 0;
                display: flex;
                align-items: center;
                justify-content: space-between;
                width: 100%;
                background-color: #000000;
            }

            .sidebar-nav {
                display: none;
                width: 100%;
                background: linear-gradient(135deg, black 25%, #000080 );
                position: fixed;
                top: 60px;
                left: 0;
                right: 0;
                bottom: 0;
                overflow-y: auto;
                z-index: 1040;
                padding-bottom: 60px;
            }

            .sidebar.show .sidebar-nav {
                display: block;
            }

            .main-content {
                margin-left: 0;
                padding-top: 70px;
            }

            /* Tambahkan style untuk button container di mobile */
            .d-flex.justify-content-between > div {
                display: flex;
                flex-direction: column;
                gap: 10px;
                margin-bottom: 1.5rem;
            }

            /* Buat button full width di mobile */
            .d-flex.justify-content-between > div .btn {
                width: 100%;
            }

            .nav-item {
                border-bottom: 1px solid rgba(255,255,255,.1);
            }

            .nav-link {
                padding: 15px;
                color: rgba(255,255,255,.8) !important;
            }

            .nav-link:hover {
                background-color: rgba(255,255,255,.1);
                color: white !important;
            }

            .user-profile {
                border-top: 1px solid rgba(255,255,255,.1);
                margin-top: 0;
                background-color: rgba(0,0,0,.2);
                position: fixed;
                bottom: 0;
                width: 100%;
            }

            .sidebar-toggle {
                display: flex;
                align-items: center;
                justify-content: center;
                width: 40px;
                height: 40px;
                padding: 0;
                border: none;
                background: transparent;
                color: white;
                font-size: 24px;
                cursor: pointer;
            }

            .sidebar-logo {
                width: 30px;
                height: 30px;
            }

            /*  style untuk menu mobile */
            .mobile-menu-active {
                overflow: hidden;
            }

            .mobile-menu-active .sidebar-nav {
                display: block;
            }
        }

        /* Style untuk menu aktif */
        .sidebar .nav-link.active {
            color: white;
            background-color: rgba(255, 255, 255, 0.20);
        }
    </style>
</head>
<body>
    <?php if (isset($_SESSION['user_id'])): ?>
    <div class="sidebar">
        <div class="navbar-brand">
            <div class="d-flex align-items-center">
                <img src="images/logo1.png" alt="Logo" class="sidebar-logo">
                <strong class="ms-2">INFOLAHTA</strong>
            </div>
            <button class="sidebar-toggle d-md-none">
                <i class='bx bx-menu'></i>
            </button>
        </div>
        <div class="sidebar-nav">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                        <i class='bx bx-home'></i>
                        Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'anggota.php' ? 'active' : ''; ?>" href="anggota.php">
                        <i class='bx bx-user'></i>
                        Anggota
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'kas.php' ? 'active' : ''; ?>" href="kas.php">
                        <i class='bx bx-plus'></i>
                        Kas
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'pinjaman.php' ? 'active' : ''; ?>" href="pinjaman.php">
                        <i class='bx bx-credit-card'></i>
                        Pinjaman
                        <?php if ($_SESSION['level'] == 'admin' && $pending_loans_count > 0): ?>
                            <span class="badge bg-danger"><?php echo $pending_loans_count; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'angsuran.php' ? 'active' : ''; ?>" href="angsuran.php">
                        <i class='bx bx-money'></i>
                        Angsuran
                        <?php if ($unpaid_loans_count > 0): ?>
                            <span class="badge bg-danger"><?php echo $unpaid_loans_count; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'laporan.php' ? 'active' : ''; ?>" href="laporan.php">
                        <i class='bx bx-file'></i>
                        Laporan
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'shu.php' ? 'active' : ''; ?>" href="shu.php">
                        <i class='bx bx-chart'></i>
                        Jasa Pinjaman
                    </a>
                </li>
                <?php if ($_SESSION['level'] == 'admin'): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'pengaturan.php' ? 'active' : ''; ?>" href="pengaturan.php">
                        <i class='bx bx-cog'></i>
                        Pengaturan
                    </a>
                </li>
                <?php endif; ?>
            </ul>
            
            <div class="user-profile mt-auto">
                <div class="user-menu">
                    <a class="nav-link">
                        <i class='bx bx-user-circle'></i>
                        <?php echo $_SESSION['username']; ?>
                    </a>
                    <a class="nav-link" href="logout.php">
                        <i class='bx bx-log-out'></i>
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="main-content">
    <?php endif; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toggleBtn = document.querySelector('.sidebar-toggle');
            const sidebar = document.querySelector('.sidebar');
            
            if (toggleBtn && sidebar) {
                toggleBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    sidebar.classList.toggle('show');
                    document.body.classList.toggle('mobile-menu-active');
                });

                // Menutup menu saat klik link menu
                const navLinks = document.querySelectorAll('.nav-link');
                navLinks.forEach(link => {
                    link.addEventListener('click', function() {
                        if (window.innerWidth <= 768) {
                            sidebar.classList.remove('show');
                            document.body.classList.remove('mobile-menu-active');
                        }
                    });
                });

                // Menutup menu saat klik di luar
                document.addEventListener('click', function(event) {
                    if (window.innerWidth <= 768) {
                        const isClickInside = sidebar.contains(event.target);
                        if (!isClickInside && sidebar.classList.contains('show')) {
                            sidebar.classList.remove('show');
                            document.body.classList.remove('mobile-menu-active');
                        }
                    }
                });
            }
        });
    </script>
</body>
</html>