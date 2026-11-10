<?php
require 'config.php'; auth(['admin']);
if($_SERVER['REQUEST_METHOD']==='POST'){
  csrf_check(); $id=(int)$_POST['id'];
  $pdo->beginTransaction();
  $d=$pdo->prepare('SELECT barang_id,qty FROM penjualan_detail WHERE penjualan_id=?'); $d->execute([$id]);
  foreach($d->fetchAll() as $x) $pdo->prepare('UPDATE barang SET stok=stok+? WHERE id=?')->execute([$x['qty'],$x['barang_id']]);
  $pdo->prepare('DELETE FROM penjualan WHERE id=?')->execute([$id]); $pdo->commit();
  flash('Transaksi dihapus dan stok dikembalikan.'); redirect('penjualan.php');
}
$dari=$_GET['dari']??date('Y-m-01'); $sampai=$_GET['sampai']??date('Y-m-d');
$s=$pdo->prepare('SELECT p.*,u.nama kasir FROM penjualan p JOIN users u ON u.id=p.user_id WHERE DATE(p.tanggal) BETWEEN ? AND ? ORDER BY p.id DESC'); $s->execute([$dari,$sampai]); $rows=$s->fetchAll();
page_header('Data Penjualan','Data Penjualan');
echo "<div class='card'><form class='row'><div><label>Dari</label><input type='date' name='dari' value='$dari'></div><div><label>Sampai</label><input type='date' name='sampai' value='$sampai'></div><button class='btn'>Tampilkan</button></form><br><div class='table-wrap'><table><tr><th>No</th><th>No. Transaksi</th><th>Tanggal</th><th>Item</th><th>Total</th><th>Kasir</th><th>Aksi</th></tr>";
$sum=0; foreach($rows as $i=>$p){ $sum+=$p['total'];
  echo "<tr><td>".($i+1)."</td><td>".e($p['no_transaksi'])."</td><td>".tgl($p['tanggal'])."</td><td>{$p['total_item']}</td><td>".rp($p['total'])."</td><td>".e($p['kasir'])."</td><td><a class='btn sm' href='struk.php?id={$p['id']}'>Struk</a> <form method='post' style='display:inline' data-confirm='Hapus transaksi ini? Stok akan dikembalikan.'>".csrf_field()."<input type='hidden' name='id' value='{$p['id']}'><button class='btn sm red'>Hapus</button></form></td></tr>"; }
if(!$rows) echo "<tr><td colspan='7'>Tidak ada data.</td></tr>";
echo "<tr><th colspan='4'>Total</th><th colspan='3'>".rp($sum)."</th></tr></table></div></div>"; page_footer();
