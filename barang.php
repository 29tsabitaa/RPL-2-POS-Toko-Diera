<?php
require 'config.php'; auth(['admin','kasir']);
$admin=$_SESSION['u']['role']==='admin';
if($admin && $_SERVER['REQUEST_METHOD']==='POST'){
  csrf_check(); $a=$_POST['aksi']??'';
  try{
    if($a==='simpan'){
      $d=[trim($_POST['kode']),trim($_POST['nama']),trim($_POST['kategori']),max(0,(int)$_POST['harga']),max(0,(int)$_POST['stok'])];
      if(!$d[0]||!$d[1]||!$d[2]) throw new Exception('Semua field wajib diisi.');
      if(!empty($_POST['id'])){ $d[]=(int)$_POST['id']; $pdo->prepare('UPDATE barang SET kode=?,nama=?,kategori=?,harga=?,stok=? WHERE id=?')->execute($d); flash('Barang diperbarui.'); }
      else { $pdo->prepare('INSERT INTO barang(kode,nama,kategori,harga,stok) VALUES(?,?,?,?,?)')->execute($d); flash('Barang ditambahkan.'); }
    } elseif($a==='hapus'){ $pdo->prepare('DELETE FROM barang WHERE id=?')->execute([(int)$_POST['id']]); flash('Barang dihapus.'); }
  }catch(Exception $ex){ flash(str_contains($ex->getMessage(),'Duplicate')?'Kode barang sudah ada.':(str_contains($ex->getMessage(),'foreign key')?'Barang sudah dipakai di transaksi, tidak bisa dihapus.':$ex->getMessage()),'err'); }
  redirect('barang.php');
}
$edit=null; if($admin && isset($_GET['edit'])){ $s=$pdo->prepare('SELECT * FROM barang WHERE id=?'); $s->execute([(int)$_GET['edit']]); $edit=$s->fetch(); }
$cari=trim($_GET['q']??''); $s=$pdo->prepare('SELECT * FROM barang WHERE nama LIKE ? OR kode LIKE ? OR kategori LIKE ? ORDER BY id DESC'); $s->execute(["%$cari%","%$cari%","%$cari%"]); $rows=$s->fetchAll();
page_header($admin?'Data Barang':'Daftar Barang','Data Barang');
if($admin){
  $nk=$edit['kode']??'BRG-'.str_pad((int)$pdo->query('SELECT COALESCE(MAX(id),0)+1 FROM barang')->fetchColumn(),4,'0',STR_PAD_LEFT);
  echo "<div class='card'><b>".($edit?'Edit':'Tambah')." Barang</b><form method='post'>".csrf_field()."<input type='hidden' name='aksi' value='simpan'><input type='hidden' name='id' value='".e($edit['id']??'')."'><div class='row'>
  <div><label>Kode</label><input name='kode' required value='".e($nk)."'></div><div><label>Nama Barang</label><input name='nama' required value='".e($edit['nama']??'')."'></div>
  <div><label>Kategori</label><input name='kategori' required value='".e($edit['kategori']??'')."'></div><div><label>Harga (Rp)</label><input type='number' min='0' name='harga' required value='".e($edit['harga']??'')."'></div>
  <div><label>Stok</label><input type='number' min='0' name='stok' required value='".e($edit['stok']??'')."'></div><button class='btn'>SIMPAN</button>".($edit?"<a class='btn gray' href='barang.php'>BATAL</a>":'')."</div></form></div>";
}
echo "<div class='card'><form class='row'><input name='q' placeholder='Cari kode / nama / kategori' value='".e($cari)."'><button class='btn'>Cari</button></form><br><div class='table-wrap'><table><tr><th>No</th><th>Kode</th><th>Nama</th><th>Kategori</th><th>Harga</th><th>Stok</th>".($admin?'<th>Aksi</th>':'')."</tr>";
foreach($rows as $i=>$b){ echo "<tr><td>".($i+1)."</td><td>".e($b['kode'])."</td><td>".e($b['nama'])."</td><td>".e($b['kategori'])."</td><td>".rp($b['harga'])."</td><td>".($b['stok']<=10?"<b style='color:#dc2626'>{$b['stok']}</b>":$b['stok'])."</td>";
  if($admin) echo "<td><a class='btn sm' href='?edit={$b['id']}'>Edit</a> <form method='post' style='display:inline' data-confirm='Hapus barang ini?'>".csrf_field()."<input type='hidden' name='aksi' value='hapus'><input type='hidden' name='id' value='{$b['id']}'><button class='btn sm red'>Hapus</button></form></td>";
  echo "</tr>"; }
if(!$rows) echo "<tr><td colspan='7'>Tidak ada data.</td></tr>";
echo "</table></div></div>"; page_footer();
