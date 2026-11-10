<?php
require 'config.php';
if(!empty($_SESSION['u'])) redirect('dashboard.php');
$err='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $s=$pdo->prepare('SELECT * FROM users WHERE username=?'); $s->execute([trim($_POST['username']??'')]); $u=$s->fetch();
  if(!$u || !password_verify($_POST['password']??'',$u['password'])) $err='Username atau password salah.';
  elseif($u['status']==='pending') $err='Akun Anda belum divalidasi oleh admin.';
  elseif($u['status']==='ditolak') $err='Pendaftaran akun Anda ditolak admin.';
  else { session_regenerate_id(true); $_SESSION['u']=['id'=>$u['id'],'nama'=>$u['nama'],'role'=>$u['role']]; redirect('dashboard.php'); }
}
?><!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Login - Toko Diera</title><link rel="stylesheet" href="assets/style.css"></head><body>
<div class="auth"><div><div class="logo">🛍️ TOKO DIERA<br><small style="font-weight:400;font-size:13px">Sistem Informasi POS</small></div>
<div class="card"><h2>Login</h2><p style="color:#64748b;margin-top:0">Silakan masuk untuk melanjutkan</p>
<?php if(flash()===''&&false){} echo flash(); if($err) echo "<div class='alert err'>".e($err)."</div>"; ?>
<form method="post"><label>Username</label><input name="username" required autofocus><label>Password</label><input type="password" name="password" required>
<br><br><button class="btn" style="width:100%">MASUK</button></form>
<p style="text-align:center">Belum punya akun? <a href="register.php">Daftar</a></p></div></div></div></body></html>
