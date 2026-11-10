<?php
require 'config.php'; auth(['admin','kasir']);
$s=$pdo->prepare('SELECT p.*,u.nama kasir FROM penjualan p JOIN users u ON u.id=p.user_id WHERE p.id=?'); $s->execute([(int)($_GET['id']??0)]); $p=$s->fetch();
if(!$p) die('Transaksi tidak ditemukan');
$d=$pdo->prepare('SELECT * FROM penjualan_detail WHERE penjualan_id=?'); $d->execute([$p['id']]); $items=$d->fetchAll();
?><!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><title>Struk <?=e($p['no_transaksi'])?></title>
<style>body{font-family:monospace;background:#eee;margin:0}.s{width:300px;background:#fff;margin:14px auto;padding:14px;font-size:13px}.c{text-align:center}hr{border:0;border-top:1px dashed #000}table{width:100%}td{padding:2px 0;vertical-align:top}.r{text-align:right}
.b{text-align:center;margin:10px}button,a.k{padding:8px 14px;border:0;background:#1d5fd6;color:#fff;border-radius:6px;cursor:pointer;text-decoration:none;font-size:13px}@media print{.b{display:none}body{background:#fff}.s{margin:0}}</style></head><body>
<div class="s"><div class="c"><b>TOKO DIERA</b><br>Struk Penjualan</div><hr>
<table><tr><td>No</td><td class="r"><?=e($p['no_transaksi'])?></td></tr><tr><td>Tanggal</td><td class="r"><?=tgl($p['tanggal'])?></td></tr><tr><td>Kasir</td><td class="r"><?=e($p['kasir'])?></td></tr></table><hr>
<table><?php foreach($items as $i): ?><tr><td colspan="2"><?=e($i['nama_barang'])?></td></tr><tr><td><?=$i['qty']?> x <?=rp($i['harga'])?></td><td class="r"><?=rp($i['subtotal'])?></td></tr><?php endforeach; ?></table><hr>
<table><tr><td>Subtotal</td><td class="r"><?=rp($p['subtotal'])?></td></tr><tr><td>Diskon</td><td class="r"><?=rp($p['diskon'])?></td></tr><tr><td><b>TOTAL</b></td><td class="r"><b><?=rp($p['total'])?></b></td></tr><tr><td>Bayar</td><td class="r"><?=rp($p['bayar'])?></td></tr><tr><td>Kembali</td><td class="r"><?=rp($p['kembali'])?></td></tr></table><hr>
<div class="c">Terima kasih atas kunjungan Anda<br>Barang yang dibeli dapat diretur dengan struk ini</div></div>
<div class="b"><button onclick="window.print()">🖨 Cetak Struk</button> <a class="k" href="<?=$_SESSION['u']['role']==='kasir'?'transaksi.php':'penjualan.php'?>">Kembali</a></div>
<?php if(isset($_GET['baru'])) echo "<script>window.onload=()=>window.print()</script>"; ?></body></html>
