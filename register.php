<?php
require 'config.php';
$err='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $nama=trim($_POST['nama']); $un=trim($_POST['username']); $pw=$_POST['password']; $role=in_array($_POST['role'],['kasir','pemilik'])?$_POST['role']:'kasir';
  if(strlen($nama)<3||strlen($un)<4) $err='Nama minimal 3 dan username minimal 4 karakter.';
  elseif(!preg_match('/^[A-Za-z0-9_]+$/',$un)) $err='Username hanya huruf, angka, dan underscore.';
  elseif(strlen($pw)<6) $err='Password minimal 6 karakter.';
  elseif($pw!==$_POST['password2']) $err='Konfirmasi password tidak cocok.';
  else {
    $c=$pdo->prepare('SELECT COUNT(*) FROM users WHERE username=?'); $c->execute([$un]);
    if($c->fetchColumn()) $err='Username sudah digunakan.';
    else { $pdo->prepare("INSERT INTO users(nama,username,password,role,status) VALUES(?,?,?,?, 'pending')")->execute([$nama,$un,password_hash($pw,PASSWORD_DEFAULT),$role]);
      flash('Pendaftaran berhasil. Tunggu validasi akun oleh admin sebelum login.'); redirect('login.php'); }
  }
}
?><!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Daftar - Toko Diera</title><link rel="stylesheet" href="assets/style.css"></head><body>
<div class="auth"><div><div class="logo">🛍️ TOKO DIERA</div><div class="card"><h2>Daftar Pengguna</h2>
<?php if($err) echo "<div class='alert err'>".e($err)."</div>"; ?>
<form method="post"><label>Nama Lengkap</label><input name="nama" required value="<?=e($_POST['nama']??'')?>"><label>Username</label><input name="username" required value="<?=e($_POST['username']??'')?>">
<label>Peran</label><select name="role"><option value="kasir">Kasir</option><option value="pemilik">Pemilik</option></select>
<label>Password</label><input type="password" name="password" required><label>Ulangi Password</label><input type="password" name="password2" required>
<br><br><button class="btn" style="width:100%">DAFTAR</button></form><p style="text-align:center">Sudah punya akun? <a href="login.php">Login</a></p></div></div></div></body></html>
