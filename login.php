<?php
session_start();
require_once 'config/database.php';

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND status = 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
        $_SESSION['level'] = $user['level'];
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Username atau password salah!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Infolahta Kodam IV Diponegoro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="images/logo1.png">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(-45deg, #000000, #000000, #000080, #0000ff, #000080, #000000, #000000);
            background-size: 400% 400%;
            animation: gradientBG 10s ease infinite;
            height: 100vh;
            margin: 0;
        }

        @keyframes gradientBG {
            0% {
                background-position: 0% 50%;
            }
            50% {
                background-position: 100% 50%;
            }
            100% {
                background-position: 0% 50%;
            }
        }

        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-card {
            background-color: #f0f8ff;
            border-radius: 20px;
            padding: 3rem 2.5rem;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.1);
            transition: transform 0.3s ease;
        }
        .login-card:hover {
            transform: translateY(-5px);
        }
        .login-title {
            color: #002060;
            text-align: center;
            margin-bottom: 0.5rem;
            font-weight: 700;
            font-size: 2rem;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .login-subtitle {
            color: #1a3c40;
            text-align: center;
            font-size: 1.1rem;
            margin-bottom: 2.5rem;
            font-weight: 500;
            letter-spacing: 0.5px;
        }
        .form-control {
            padding: 0.8rem 1.2rem;
            margin-bottom: 1.5rem;
            border-radius: 10px;
            border: 2px solid #e1e1e1;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: rgba(255,255,255,0.9);
        }
        .form-control:focus {
            border-color: #002060;
            box-shadow: 0 0 0 0.2rem rgba(0,32,96,0.15);
        }
        .btn-login {
            background-color: #002080;
            border: none;
            padding: 0.8rem;
            font-weight: 600;
            font-size: 1.1rem;
            border-radius: 10px;
            letter-spacing: 1px;
            text-transform: uppercase;
            transition: all 0.3s ease;
        }
        .btn-login:hover {
            background-color: #0000cd   ;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .alert {
            border-radius: 10px;
            font-weight: 500;
            border: none;
            background-color: rgba(220,53,69,0.1);
            border-left: 4px solid #dc3545;
            color: #dc3545;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .login-card {
            animation: fadeIn 0.5s ease-out;
        }
        .logo {
            display: block;
            margin: 0 auto 20px; 
            width: 100px; 
            height: auto; 
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <h3 class="login-title">Infolahta</h3>
            <img src="images/logo1.png" alt="Logo" class="logo">
            <h5 class="login-subtitle">KODAM IV/DIPONEGORO</h5>

            <?php if ($error): ?>
                <div class="alert alert-danger mb-4"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <input type="text" name="username" class="form-control" 
                           placeholder="Username" required autocomplete="off">
                </div>
                <div class="mb-4">
                    <input type="password" name="password" class="form-control" 
                           placeholder="Password" required>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-login">
                        Masuk
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>