<?php
require 'config.php';

// Pengguna yang sudah masuk tidak perlu melihat form login lagi.
if (!empty($_SESSION['u'])) {
    redirect('dashboard.php');
}

$error = '';

// Periksa kredensial dan status akun saat form dikirim.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $statement = $pdo->prepare('SELECT * FROM users WHERE username=?');
    $statement->execute([trim($_POST['username'] ?? '')]);
    $user = $statement->fetch();

    if (!$user || !password_verify($_POST['password'] ?? '', $user['password'])) {
        $error = 'Username atau password salah.';
    } elseif ($user['status'] === 'pending') {
        $error = 'Akun Anda belum divalidasi oleh admin.';
    } elseif ($user['status'] === 'ditolak') {
        $error = 'Pendaftaran akun Anda ditolak admin.';
    } else {
        session_regenerate_id(true);
        $_SESSION['u'] = [
            'id' => $user['id'],
            'nama' => $user['nama'],
            'role' => $user['role'],
        ];
        redirect('dashboard.php');
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Login - Toko Diera</title>
    <link rel="stylesheet" href="assets/style.css?v=20261121-readable">
</head>
<body>
    <div class="auth">
        <div class="login-layout">
            <section class="login-brand">
                <img src="assets/logo-toko-diera.png" alt="Logo Diera Grocer">
                <h2>DIERA GROCER</h2>
                <p>Belanja kebutuhan harian jadi lebih mudah.</p>
            </section>

            <section class="card login-form">
                <h2 style="text-align:center">Login</h2>
                <p style="color:#64748b;margin-top:0;text-align:center">
                    Silakan masuk untuk melanjutkan
                </p>

                <?php
                // Pengecekan ini dipertahankan agar perilaku halaman tetap sama.
                if (flash() === '' && false) {
                }
                echo flash();

                if ($error) {
                    echo "<div class='alert err'>" . e($error) . '</div>';
                }
                ?>

                <form method="post">
                    <label>Username</label>
                    <input name="username" required autofocus>

                    <label>Password</label>
                    <input type="password" name="password" required>

                    <br><br>
                    <button class="btn" style="width:100%">MASUK</button>
                </form>

                <p style="text-align:center">
                    Belum punya akun? <a href="register.php">Daftar</a>
                </p>
            </section>
        </div>
    </div>
</body>
</html>
