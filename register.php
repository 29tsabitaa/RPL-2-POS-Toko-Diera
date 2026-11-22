<?php
require 'config.php';

$error = '';

// Validasi data lalu simpan akun baru dengan status menunggu persetujuan.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['nama']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role = in_array($_POST['role'], ['kasir', 'pemilik'])
        ? $_POST['role']
        : 'kasir';

    if (strlen($name) < 3 || strlen($username) < 4) {
        $error = 'Nama minimal 3 dan username minimal 4 karakter.';
    } elseif (!preg_match('/^[A-Za-z0-9_]+$/', $username)) {
        $error = 'Username hanya huruf, angka, dan underscore.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } elseif ($password !== $_POST['password2']) {
        $error = 'Konfirmasi password tidak cocok.';
    } else {
        $statement = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username=?');
        $statement->execute([$username]);

        if ($statement->fetchColumn()) {
            $error = 'Username sudah digunakan.';
        } else {
            $statement = $pdo->prepare(
                "INSERT INTO users(nama,username,password,role,status)
                 VALUES(?,?,?,?, 'pending')"
            );
            $statement->execute([
                $name,
                $username,
                password_hash($password, PASSWORD_DEFAULT),
                $role,
            ]);

            flash('Pendaftaran berhasil. Tunggu validasi akun oleh admin sebelum login.');
            redirect('login.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Daftar - Toko Diera</title>
    <link rel="stylesheet" href="assets/style.css?v=20261121-readable">
</head>
<body>
    <div class="auth">
        <div class="login-layout">
            <section class="login-brand">
                <img src="assets/logo-toko-diera.png" alt="Logo Diera Grocer">
                <h2>DIERA GROCER</h2>
                <p>Daftar untuk mulai menggunakan layanan Toko Diera.</p>
            </section>

            <section class="card login-form">
                <h2 style="text-align:center">Daftar Pengguna</h2>
                <p style="color:#64748b;margin-top:0;text-align:center">
                    Lengkapi data untuk membuat akun
                </p>

                <?php if ($error): ?>
                    <div class="alert err"><?=e($error)?></div>
                <?php endif; ?>

                <form method="post">
                    <label>Nama Lengkap</label>
                    <input name="nama" required value="<?=e($_POST['nama'] ?? '')?>">

                    <label>Username</label>
                    <input name="username" required value="<?=e($_POST['username'] ?? '')?>">

                    <label>Peran</label>
                    <select name="role">
                        <option value="kasir">Kasir</option>
                        <option value="pemilik">Pemilik</option>
                    </select>

                    <label>Password</label>
                    <input type="password" name="password" required>

                    <label>Ulangi Password</label>
                    <input type="password" name="password2" required>

                    <br><br>
                    <button class="btn" style="width:100%">DAFTAR</button>
                </form>

                <p style="text-align:center">
                    Sudah punya akun? <a href="login.php">Login</a>
                </p>
            </section>
        </div>
    </div>
</body>
</html>
