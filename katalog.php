<?php
require 'config.php';
$q=trim($_GET['q']??''); $kat=trim($_GET['kategori']??'');
$sql='SELECT nama,kategori,harga,stok FROM barang WHERE (nama LIKE ? OR kode LIKE ?)'; $p=["%$q%","%$q%"];
if($kat!==''){ $sql.=' AND kategori=?'; $p[]=$kat; }
$s=$pdo->prepare($sql.' ORDER BY nama'); $s->execute($p); $rows=$s->fetchAll();
$cats=$pdo->query('SELECT DISTINCT kategori FROM barang ORDER BY kategori')->fetchAll(PDO::FETCH_COLUMN);
?><!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Katalog - Toko Diera</title><link rel="stylesheet" href="assets/style.css">
<style>.hero{background:linear-gradient(135deg,#0a2a66,#1d5fd6);color:#fff;padding:14px 22px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px}
.hero b{font-size:20px}.wrap{max-width:1000px;margin:0 auto;padding:22px}.items{display:grid;gap:14px;grid-template-columns:repeat(auto-fill,minmax(210px,1fr))}
.it{background:#fff;border-radius:10px;padding:16px;box-shadow:0 1px 4px #0001}.it .ic{font-size:34px}.it h3{margin:8px 0 2px;font-size:15px}.it small{color:#64748b}.it .h{font-size:18px;font-weight:700;color:#0a2a66;margin:8px 0}</style></head><body>
<div class="hero"><b>🛍️ TOKO DIERA</b><div><a class="btn gray sm" href="login.php">Login</a> <a class="btn sm" style="background:#fff;color:#0a2a66" href="register.php">Daftar</a></div></div>
<div class="wrap"><h1>Katalog Barang</h1><div class="crumb">Daftar produk yang tersedia di Toko Diera</div>
<form class="row card"><input name="q" placeholder="Cari nama barang..." value="<?=e($q)?>"><select name="kategori"><option value="">Semua Kategori</option><?php foreach($cats as $c) echo "<option ".($c===$kat?'selected':'').">".e($c)."</option>"; ?></select><button class="btn">Cari</button></form>
<div class="items"><?php foreach($rows as $b){ $st=$b['stok']<=0?['Habis','ditolak']:($b['stok']<=10?['Terbatas','pending']:['Tersedia','aktif']);
 echo "<div class='it'><div class='ic'>📦</div><h3>".e($b['nama'])."</h3><small>".e($b['kategori'])."</small><div class='h'>".rp($b['harga'])."</div><span class='badge b-{$st[1]}'>{$st[0]}</span></div>"; }
 if(!$rows) echo "<p>Barang tidak ditemukan.</p>"; ?></div></div>
<footer>© <?=date('Y')?> TOKO DIERA. All rights reserved.</footer></body></html>
