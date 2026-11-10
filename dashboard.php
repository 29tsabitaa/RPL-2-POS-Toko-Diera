<?php
require 'config.php'; auth();
$r=$_SESSION['u']['role']; $q=fn($sql)=>$pdo->query($sql)->fetchColumn();
page_header('Dashboard');
echo "<div class='card'>Selamat datang, <b>".e($_SESSION['u']['nama'])."</b> (".ucfirst($r).").</div><div class='grid'>";
$st=[];
$st[]=['Penjualan Hari Ini',rp($q("SELECT COALESCE(SUM(total),0) FROM penjualan WHERE DATE(tanggal)=CURDATE()"))];
$st[]=['Transaksi Hari Ini',$q("SELECT COUNT(*) FROM penjualan WHERE DATE(tanggal)=CURDATE()")];
$st[]=['Jumlah Barang',$q("SELECT COUNT(*) FROM barang")];
$st[]=['Stok Menipis (≤10)',$q("SELECT COUNT(*) FROM barang WHERE stok<=10")];
if($r==='admin'){ $st[]=['Akun Menunggu Validasi',$q("SELECT COUNT(*) FROM users WHERE status='pending'")]; $st[]=['Retur Diajukan',$q("SELECT COUNT(*) FROM retur WHERE status='diajukan'")]; }
if($r==='pemilik'){ $st[]=['Total Penjualan Bulan Ini',rp($q("SELECT COALESCE(SUM(total),0) FROM penjualan WHERE MONTH(tanggal)=MONTH(CURDATE()) AND YEAR(tanggal)=YEAR(CURDATE())"))]; }
foreach($st as [$l,$v]) echo "<div class='card stat'><small>".e($l)."</small><b>".e($v)."</b></div>";
echo "</div>"; page_footer();
