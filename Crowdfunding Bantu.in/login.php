<?php
session_start();
require 'koneksi.php';

$error = "";


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];


    $query = "SELECT * FROM users WHERE username = ? AND password = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['nama']    = $user['nama'];
        $_SESSION['role']    = $user['role'];


        header("Location: index.php");
        exit;
    } else {
        $error = "Username atau password salah!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Bantu.in</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="home-header">
        <div class="home-header-inner">
            <a href="index.php" class="home-logo" style="text-decoration: none;">
                <span class="home-logo-icon">♥︎</span>
                <span class="home-logo-text">Bantu.in</span>
            </a>
            <nav class="home-navbar">
                <a href="index.php" class="home-nav-link">Beranda</a>
                <a href="index.php#daftar-kampanye" class="home-nav-link">Kampanye</a>
                <a href="#" class="home-nav-link">Tentang Kami</a>
                <a href="login.php" class="home-btn-login">Login</a>
            </nav>
        </div>
    </header>
    
    <main class="loginContainer">
        <div class="loginPage">
            <div class="login-header">
                <span class="login-logo-icon">♥︎</span>
                <h2 id="tulisanLogin">LOGIN</h2>
                <p class="login-sub">Masuk ke akun Bantu.in kamu</p>
            </div>


            <?php if(!empty($error)): ?>
                <p style="color: red; text-align: center; margin-bottom: 15px; font-weight: bold;"><?= $error ?></p>
            <?php endif; ?>


            <form action="" method="POST">
                <div class="login-field">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" placeholder="Masukkan username" required>
                </div>

                <div class="login-field">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Masukkan password" required>
                </div>

                <button id="login" type="submit">Login</button>
            </form>
        </div>
    </main>

    <footer class="footer">
        <p>&copy; 2026 Bantu.in &mdash; Platform Crowdfunding Sosial Indonesia</p>
    </footer>
</body>
</html>