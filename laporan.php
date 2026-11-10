<?php
require 'config.php'; auth(['pemilik','admin']);
$jenis=in_array($_GET['jenis']??'',['penjualan','stok','retur'])?$_GET['jenis']:'penjualan';
$dari=$_GET['dari']??date('Y-m-01'); $sampai=$_GET['sampai']??date('Y-m-d');
page_header('Laporan '.ucfirst($jenis),'Laporan '.ucfirst($jenis));
echo "<div class='card noprint'><form class='row'><input type='hidden' name='jenis' value='$jenis'>";
if($jenis!=='stok') echo "<div><label>Dari</label><input type='date' name='dari' value='$dari'></div><div><label>Sampai</label><input type='date' name='sampai' value='$sampai'></div><button class='btn'>Tampilkan</button>";
echo "<button type='button' class='btn green' onclick='window.print()'>🖨 Cetak</button></form></div>";
echo "<div class='card'><div style='text-align:center'><b>TOKO DIERA</b><br>Laporan ".ucfirst($jenis).($jenis!=='stok'?"<br><small>Periode ".date('d/m/Y',strtotime($dari))." - ".date('d/m/Y',strtotime($sampai))."</small>":"<br><small>Per ".date('d/m/Y H:i')."</small>")."</div><br>";
if($jenis==='penjualan'){
  $s=$pdo->prepare('SELECT p.*,u.nama kasir FROM penjualan p JOIN users u ON u.id=p.user_id WHERE DATE(p.tanggal) BETWEEN ? AND ? ORDER BY p.tanggal'); $s->execute([$dari,$sampai]); $r=$s->fetchAll();
  $tot=array_sum(array_column($r,'total')); $ti=array_sum(array_column($r,'total_item')); $n=count($r);
  echo "<div class='grid'><div class='card stat'><small>Total Penjualan</small><b>".rp($tot)."</b></div><div class='card stat'><small>Jumlah Transaksi</small><b>$n</b></div><div class='card stat'><small>Total Item Terjual</small><b>$ti</b></div><div class='card stat'><small>Rata-rata Transaksi</small><b>".rp($n?$tot/$n:0)."</b></div></div>";
  echo "<div class='table-wrap'><table><tr><th>No</th><th>No. Transaksi</th><th>Tanggal</th><th>Item</th><th>Diskon</th><th>Total</th><th>Kasir</th></tr>";
  foreach($r as $i=>$p) echo "<tr><td>".($i+1)."</td><td>".e($p['no_transaksi'])."</td><td>".tgl($p['tanggal'])."</td><td>{$p['total_item']}</td><td>".rp($p['diskon'])."</td><td>".rp($p['total'])."</td><td>".e($p['kasir'])."</td></tr>";
  if(!$r) echo "<tr><td colspan='7'>Tidak ada data.</td></tr>"; echo "</table></div>";
  $t=$pdo->prepare('SELECT d.nama_barang,SUM(d.qty) q,SUM(d.subtotal) s FROM penjualan_detail d JOIN penjualan p ON p.id=d.penjualan_id WHERE DATE(p.tanggal) BETWEEN ? AND ? GROUP BY d.barang_id,d.nama_barang ORDER BY q DESC LIMIT 5'); $t->execute([$dari,$sampai]); $t=$t->fetchAll();
  if($t){ $mx=max(array_column($t,'q')); echo "<br><b>5 Barang Terlaris</b><table>"; foreach($t as $x) echo "<tr><td width='30%'>".e($x['nama_barang'])."</td><td><div class='bar' style='width:".round($x['q']/$mx*100)."%'></div></td><td width='25%'>{$x['q']} pcs / ".rp($x['s'])."</td></tr>"; echo "</table>"; }
} elseif($jenis==='stok'){
  $r=$pdo->query('SELECT * FROM barang ORDER BY stok ASC, nama')->fetchAll(); $nilai=0;
  echo "<div class='table-wrap'><table><tr><th>No</th><th>Kode</th><th>Nama</th><th>Kategori</th><th>Harga</th><th>Stok</th><th>Nilai Stok</th><th>Status</th></tr>";
  foreach($r as $i=>$b){ $nilai+=$b['harga']*$b['stok']; $st=$b['stok']==0?['Habis','ditolak']:($b['stok']<=10?['Menipis','pending']:['Aman','aktif']);
    echo "<tr><td>".($i+1)."</td><td>".e($b['kode'])."</td><td>".e($b['nama'])."</td><td>".e($b['kategori'])."</td><td>".rp($b['harga'])."</td><td>{$b['stok']}</td><td>".rp($b['harga']*$b['stok'])."</td><td><span class='badge b-{$st[1]}'>{$st[0]}</span></td></tr>"; }
  echo "<tr><th colspan='5'>Total</th><th>".array_sum(array_column($r,'stok'))."</th><th colspan='2'>".rp($nilai)."</th></tr></table></div>";
} else {
  $s=$pdo->prepare("SELECT r.*,p.no_transaksi,u.nama kasir,(SELECT GROUP_CONCAT(CONCAT(nama_barang,' x',qty) SEPARATOR ', ') FROM retur_detail WHERE retur_id=r.id) items FROM retur r JOIN penjualan p ON p.id=r.penjualan_id JOIN users u ON u.id=r.user_id WHERE DATE(r.tanggal) BETWEEN ? AND ? ORDER BY r.tanggal"); $s->execute([$dari,$sampai]); $r=$s->fetchAll();
  $ok=array_filter($r,fn($x)=>$x['status']==='disetujui');
  echo "<div class='grid'><div class='card stat'><small>Total Pengajuan</small><b>".count($r)."</b></div><div class='card stat'><small>Disetujui</small><b>".count($ok)."</b></div><div class='card stat'><small>Nilai Retur Disetujui</small><b>".rp(array_sum(array_column($ok,'total')))."</b></div></div>";
  echo "<div class='table-wrap'><table><tr><th>No</th><th>No. Retur</th><th>Tanggal</th><th>Transaksi</th><th>Barang</th><th>Alasan</th><th>Total</th><th>Status</th></tr>";
  foreach($r as $i=>$x) echo "<tr><td>".($i+1)."</td><td>".e($x['no_retur'])."</td><td>".tgl($x['tanggal'])."</td><td>".e($x['no_transaksi'])."</td><td>".e($x['items'])."</td><td>".e($x['alasan'])."</td><td>".rp($x['total'])."</td><td><span class='badge b-{$x['status']}'>{$x['status']}</span></td></tr>";
  if(!$r) echo "<tr><td colspan='8'>Tidak ada data.</td></tr>"; echo "</table></div>";
}
echo "</div>"; page_footer();
