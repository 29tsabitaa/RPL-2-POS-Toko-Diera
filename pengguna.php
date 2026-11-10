<?php
require 'config.php'; auth(['admin']);
if($_SERVER['REQUEST_METHOD']==='POST'){
  csrf_check(); $a=$_POST['aksi']; $id=(int)($_POST['id']??0); $me=$_SESSION['u']['id'];
  try{
    if($a==='tambah'){
      if(strlen($_POST['password'])<6) throw new Exception('Password minimal 6 karakter.');
      $pdo->prepare("INSERT INTO users(nama,username,password,role,status) VALUES(?,?,?,?, 'aktif')")->execute([trim($_POST['nama']),trim($_POST['username']),password_hash($_POST['password'],PASSWORD_DEFAULT),$_POST['role']]); flash('Pengguna ditambahkan.');
    } elseif($a==='aktif'||$a==='ditolak'){ $pdo->prepare('UPDATE users SET status=? WHERE id=?')->execute([$a,$id]); flash($a==='aktif'?'Akun divalidasi (aktif).':'Akun ditolak.'); }
    elseif($a==='reset'){ $pdo->prepare('UPDATE users SET password=? WHERE id=?')->execute([password_hash('123456',PASSWORD_DEFAULT),$id]); flash('Password direset menjadi 123456.'); }
    elseif($a==='hapus'){ if($id===$me) throw new Exception('Tidak bisa menghapus akun sendiri.'); $pdo->prepare('DELETE FROM users WHERE id=?')->execute([$id]); flash('Pengguna dihapus.'); }
  }catch(Exception $ex){ flash(str_contains($ex->getMessage(),'Duplicate')?'Username sudah ada.':(str_contains($ex->getMessage(),'foreign key')?'Pengguna sudah punya transaksi, tidak bisa dihapus (tolak/nonaktifkan saja).':$ex->getMessage()),'err'); }
  redirect('pengguna.php');
}
$rows=$pdo->query("SELECT * FROM users ORDER BY FIELD(status,'pending','aktif','ditolak'), id DESC")->fetchAll();
page_header('Data Pengguna','Pengguna');
echo "<div class='card'><b>Tambah Pengguna</b><form method='post'>".csrf_field()."<input type='hidden' name='aksi' value='tambah'><div class='row'><div><label>Nama</label><input name='nama' required></div><div><label>Username</label><input name='username' required></div><div><label>Password</label><input type='password' name='password' required></div><div><label>Peran</label><select name='role'><option>kasir</option><option>pemilik</option><option>admin</option></select></div><button class='btn'>TAMBAH</button></div></form></div>";
echo "<div class='card'><div class='table-wrap'><table><tr><th>No</th><th>Nama</th><th>Username</th><th>Peran</th><th>Status</th><th>Aksi</th></tr>";
foreach($rows as $i=>$u){
  echo "<tr><td>".($i+1)."</td><td>".e($u['nama'])."</td><td>".e($u['username'])."</td><td>".ucfirst($u['role'])."</td><td><span class='badge b-{$u['status']}'>{$u['status']}</span></td><td>";
  $f=fn($aksi,$label,$cls,$cf='')=>"<form method='post' style='display:inline' ".($cf?"data-confirm='$cf'":'').">".csrf_field()."<input type='hidden' name='aksi' value='$aksi'><input type='hidden' name='id' value='{$u['id']}'><button class='btn sm $cls'>$label</button></form> ";
  if($u['status']!=='aktif') echo $f('aktif','Validasi','green');
  if($u['status']!=='ditolak' && $u['id']!=$_SESSION['u']['id']) echo $f('ditolak','Tolak','gray');
  echo $f('reset','Reset PW','gray','Reset password ke 123456?');
  if($u['id']!=$_SESSION['u']['id']) echo $f('hapus','Hapus','red','Hapus pengguna ini?');
  echo "</td></tr>"; }
echo "</table></div></div>"; page_footer();
