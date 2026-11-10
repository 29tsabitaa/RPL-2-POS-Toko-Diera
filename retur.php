<?php
require 'config.php'; auth(['admin','kasir']);
$admin=$_SESSION['u']['role']==='admin'; $uid=$_SESSION['u']['id'];
$sqlItems="SELECT d.*, d.qty-COALESCE((SELECT SUM(rd.qty) FROM retur_detail rd JOIN retur r ON r.id=rd.retur_id WHERE r.penjualan_id=d.penjualan_id AND rd.barang_id=d.barang_id AND r.status<>'ditolak'),0) AS sisa FROM penjualan_detail d WHERE d.penjualan_id=?";
if($_SERVER['REQUEST_METHOD']==='POST'){
  csrf_check(); $a=$_POST['aksi']??'';
  try{
    if(!$admin && $a==='ajukan'){
      $pid=(int)$_POST['penjualan_id']; $pilih=$_POST['pilih']??[]; $qty=$_POST['qty']??[]; $alasan=trim($_POST['alasan']??'');
      if(!$pilih) throw new Exception('Pilih minimal satu barang yang diretur.'); if($alasan==='') throw new Exception('Alasan retur wajib dipilih.');
      $s=$pdo->prepare($sqlItems); $s->execute([$pid]); $map=[]; foreach($s->fetchAll() as $x) $map[$x['id']]=$x;
      $pdo->beginTransaction(); $sub=0;$ti=0;$det=[];
      foreach($pilih as $did){ $x=$map[$did]??null; $q=(int)($qty[$did]??0);
        if(!$x||$q<1||$q>$x['sisa']) throw new Exception('Jumlah retur '.($x['nama_barang']??'').' tidak valid (maks '.($x['sisa']??0).').');
        $det[]=[$x,$q]; $sub+=$x['harga']*$q; $ti+=$q; }
      $dis=max(0,min((int)($_POST['diskon']??0),$sub));
      $no='RTR'.date('ymdHis').rand(10,99);
      $pdo->prepare('INSERT INTO retur(no_retur,penjualan_id,user_id,total_item,diskon,total,alasan,keterangan) VALUES(?,?,?,?,?,?,?,?)')->execute([$no,$pid,$uid,$ti,$dis,$sub-$dis,$alasan,trim($_POST['keterangan']??'')]);
      $rid=$pdo->lastInsertId();
      foreach($det as [$x,$q]) $pdo->prepare('INSERT INTO retur_detail(retur_id,barang_id,nama_barang,harga,qty,subtotal) VALUES(?,?,?,?,?,?)')->execute([$rid,$x['barang_id'],$x['nama_barang'],$x['harga'],$q,$x['harga']*$q]);
      $pdo->commit(); flash("Pengajuan retur $no berhasil disimpan, menunggu persetujuan admin.");
    } elseif($admin){
      $id=(int)$_POST['id'];
      if($a==='setujui'){
        $pdo->beginTransaction(); $st=$pdo->prepare('SELECT status FROM retur WHERE id=? FOR UPDATE'); $st->execute([$id]);
        if($st->fetchColumn()==='diajukan'){
          $d=$pdo->prepare('SELECT barang_id,qty FROM retur_detail WHERE retur_id=?'); $d->execute([$id]);
          foreach($d->fetchAll() as $x) $pdo->prepare('UPDATE barang SET stok=stok+? WHERE id=?')->execute([$x['qty'],$x['barang_id']]);
          $pdo->prepare("UPDATE retur SET status='disetujui' WHERE id=?")->execute([$id]);
        } $pdo->commit(); flash('Retur disetujui, stok barang dikembalikan.');
      } elseif($a==='tolak'){ $pdo->prepare("UPDATE retur SET status='ditolak' WHERE id=? AND status='diajukan'")->execute([$id]); flash('Retur ditolak.'); }
      elseif($a==='hapus'){ $pdo->prepare("DELETE FROM retur WHERE id=? AND status<>'disetujui'")->execute([$id]); flash('Data retur dihapus (retur disetujui tidak bisa dihapus).'); }
    }
  }catch(Exception $ex){ if($pdo->inTransaction())$pdo->rollBack(); flash($ex->getMessage(),'err'); }
  redirect('retur.php');
}
page_header($admin?'Data Retur':'Pengajuan Retur','Retur');
if(!$admin){
  $no=trim($_GET['no']??''); $p=null;
  echo "<div class='card'><form class='row'><input name='no' placeholder='Cari nomor struk / nomor transaksi' value='".e($no)."'><button class='btn'>Cari</button></form></div>";
  if($no!==''){
    $s=$pdo->prepare('SELECT * FROM penjualan WHERE no_transaksi=?'); $s->execute([$no]); $p=$s->fetch();
    if(!$p) echo "<div class='alert err'>Transaksi tidak ditemukan.</div>";
  }
  if($p){
    $s=$pdo->prepare($sqlItems); $s->execute([$p['id']]); $items=$s->fetchAll();
    echo "<div class='card'><b>No. Transaksi:</b> ".e($p['no_transaksi'])." &nbsp; <b>Tanggal:</b> ".tgl($p['tanggal'])." &nbsp; <b>Total Pembelian:</b> ".rp($p['total'])."</div>";
    echo "<form method='post' class='pos'>".csrf_field()."<input type='hidden' name='aksi' value='ajukan'><input type='hidden' name='penjualan_id' value='{$p['id']}'><div class='card'><b>Pilih Barang yang Diretur</b><div class='table-wrap'><table><tr><th></th><th>Nama Barang</th><th>Harga</th><th>Dibeli</th><th>Bisa Diretur</th><th>Jumlah Retur</th><th>Subtotal</th></tr>";
    foreach($items as $x){ $dis=$x['sisa']<1?'disabled':'';
      echo "<tr><td><input type='checkbox' name='pilih[]' value='{$x['id']}' class='ck' $dis style='width:auto'></td><td>".e($x['nama_barang'])."</td><td>".rp($x['harga'])."</td><td>{$x['qty']}</td><td>{$x['sisa']}</td><td><input type='number' name='qty[{$x['id']}]' class='jq' data-h='{$x['harga']}' min='1' max='{$x['sisa']}' value='0' disabled style='width:70px'></td><td class='st'>Rp0</td></tr>"; }
    echo "</table></div><label>Alasan Retur</label><select name='alasan' required><option value=''>Pilih alasan retur</option><option>Barang rusak / cacat</option><option>Salah beli</option><option>Tidak sesuai</option><option>Kadaluarsa</option><option>Lainnya</option></select>
    <label>Keterangan (opsional)</label><textarea name='keterangan' rows='3' placeholder='Tambahkan keterangan jika diperlukan...'></textarea><br><button class='btn'>💾 Simpan Pengajuan Retur</button> <a class='btn gray' href='retur.php'>Reset</a></div>
    <div class='card'><b>Ringkasan Retur</b><br><small>Total Item</small><div class='big' id='rI'>0</div><small>Total Retur</small><div class='big' id='rQ'>0</div><small>Subtotal</small><div class='big' id='rS'>Rp0</div><label>Diskon (Rp)</label><input type='number' name='diskon' id='rD' min='0' value='0'><small>TOTAL</small><div class='big' id='rT'>Rp0</div></div></form>
    <script>const f=n=>'Rp'+Number(n).toLocaleString('id-ID');function hit(){let i=0,q=0,s=0;document.querySelectorAll('tr').forEach(tr=>{const c=tr.querySelector('.ck');if(!c)return;const j=tr.querySelector('.jq');j.disabled=!c.checked;if(c.checked){if(+j.value<1)j.value=1;const v=Math.min(+j.value,+j.max);j.value=v;i++;q+=v;s+=v*j.dataset.h;tr.querySelector('.st').textContent=f(v*j.dataset.h)}else{j.value=0;tr.querySelector('.st').textContent='Rp0'}});const d=Math.min(+document.getElementById('rD').value||0,s);rI.textContent=i;rQ.textContent=q;rS.textContent=f(s);rT.textContent=f(s-d)}document.querySelectorAll('.ck,.jq,#rD').forEach(e=>e.addEventListener('input',hit));</script>";
  }
}
$w=$admin?'':'WHERE r.user_id='.(int)$uid;
$rows=$pdo->query("SELECT r.*,p.no_transaksi,u.nama kasir,(SELECT GROUP_CONCAT(CONCAT(nama_barang,' x',qty) SEPARATOR ', ') FROM retur_detail WHERE retur_id=r.id) items FROM retur r JOIN penjualan p ON p.id=r.penjualan_id JOIN users u ON u.id=r.user_id $w ORDER BY r.id DESC")->fetchAll();
echo "<div class='card'><b>".($admin?'Daftar Retur':'Riwayat Pengajuan Retur')."</b><div class='table-wrap'><table><tr><th>No. Retur</th><th>Tanggal</th><th>Transaksi</th><th>Barang</th><th>Total</th><th>Alasan</th><th>Status</th>".($admin?'<th>Aksi</th>':'')."</tr>";
foreach($rows as $r){ echo "<tr><td>".e($r['no_retur'])."</td><td>".tgl($r['tanggal'])."</td><td>".e($r['no_transaksi'])."<br><small>".e($r['kasir'])."</small></td><td>".e($r['items'])."</td><td>".rp($r['total'])."</td><td>".e($r['alasan'])."<br><small>".e($r['keterangan'])."</small></td><td><span class='badge b-{$r['status']}'>{$r['status']}</span></td>";
  if($admin){ echo "<td>"; $f=fn($a,$l,$c,$cf='')=>"<form method='post' style='display:inline' ".($cf?"data-confirm='$cf'":'').">".csrf_field()."<input type='hidden' name='aksi' value='$a'><input type='hidden' name='id' value='{$r['id']}'><button class='btn sm $c'>$l</button></form> ";
    if($r['status']==='diajukan') echo $f('setujui','Setujui','green','Setujui retur? Stok akan bertambah.').$f('tolak','Tolak','gray');
    if($r['status']!=='disetujui') echo $f('hapus','Hapus','red','Hapus data retur?'); echo "</td>"; }
  echo "</tr>"; }
if(!$rows) echo "<tr><td colspan='8'>Belum ada data retur.</td></tr>";
echo "</table></div></div>"; page_footer();
