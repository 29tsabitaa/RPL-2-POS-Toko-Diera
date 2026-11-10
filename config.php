<?php
// ===== Konfigurasi database =====
define('DB_HOST','localhost'); define('DB_NAME','toko_diera'); define('DB_USER','root'); define('DB_PASS','');
session_start();
date_default_timezone_set('Asia/Jakarta');
try {
  $pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASS,
    [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
} catch (Exception $e) { die('Koneksi database gagal. Import database.sql dan cek config.php'); }

function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function rp($n){ return 'Rp'.number_format((float)$n,0,',','.'); }
function tgl($d){ return date('d M Y H:i', strtotime($d)); }
function redirect($u){ header("Location: $u"); exit; }
function flash($msg=null,$type='ok'){
  if($msg!==null){ $_SESSION['flash']=[$msg,$type]; return; }
  if(!empty($_SESSION['flash'])){ [$m,$t]=$_SESSION['flash']; unset($_SESSION['flash']); return "<div class='alert $t'>".e($m)."</div>"; }
  return '';
}
function csrf(){ if(empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(16)); return $_SESSION['csrf']; }
function csrf_field(){ return "<input type='hidden' name='csrf' value='".csrf()."'>"; }
function csrf_check(){ if(($_POST['csrf']??'')!==($_SESSION['csrf']??'x')){ http_response_code(400); die('Token tidak valid'); } }
function auth(array $roles=[]){
  global $pdo;
  if(empty($_SESSION['u'])) redirect('login.php');
  $s=$pdo->prepare('SELECT status FROM users WHERE id=?'); $s->execute([$_SESSION['u']['id']]);
  if($s->fetchColumn()!=='aktif'){ session_destroy(); redirect('login.php'); }
  if($roles && !in_array($_SESSION['u']['role'],$roles)){ http_response_code(403); die('Akses ditolak untuk peran Anda.'); }
}
function menu(){
  $r=$_SESSION['u']['role'];
  $m=['dashboard.php'=>['Dashboard','🏠']];
  if($r==='admin') $m+=['barang.php'=>['Data Barang','📦'],'pengguna.php'=>['Data Pengguna','👥'],'penjualan.php'=>['Data Penjualan','🧾'],'retur.php'=>['Data Retur','↩️']];
  if($r==='kasir') $m+=['barang.php'=>['Data Barang','📦'],'transaksi.php'=>['Transaksi','🛒'],'retur.php'=>['Pengajuan Retur','↩️']];
  if($r==='pemilik') $m+=['laporan.php?jenis=penjualan'=>['Laporan Penjualan','📈'],'laporan.php?jenis=stok'=>['Laporan Stok','📦'],'laporan.php?jenis=retur'=>['Laporan Retur','↩️']];
  return $m;
}
function page_header($title,$crumb=''){
  $cur=basename($_SERVER['SCRIPT_NAME']); $q=$_GET['jenis']??'';
  echo "<!DOCTYPE html><html lang='id'><head><meta charset='utf-8'><meta name='viewport' content='width=device-width,initial-scale=1'><title>".e($title)." - Toko Diera</title><link rel='stylesheet' href='assets/style.css'></head><body>";
  echo "<aside class='side' id='side'><div class='brand'>🛍️ TOKO DIERA</div><nav>";
  foreach(menu() as $u=>[$n,$i]){ $on = ($cur===strtok($u,'?') && (!str_contains($u,'jenis=') || str_contains($u,"jenis=$q"))) ? 'on':''; echo "<a class='$on' href='$u'>$i $n</a>"; }
  echo "</nav><a href='#' class='btn-logout' data-logout>🚪 Logout</a></aside><div class='main'>";
  echo "<header class='top'><button class='burger' onclick=\"document.getElementById('side').classList.toggle('show')\">☰</button><span class='user'>👤 ".e($_SESSION['u']['nama'])." (".ucfirst($_SESSION['u']['role']).")</span></header><main>";
  echo "<h1>".e($title)."</h1><div class='crumb'>Dashboard".($crumb?' / '.e($crumb):'')."</div>".flash();
}
function page_footer(){
  echo "</main><footer>© ".date('Y')." TOKO DIERA. All rights reserved.</footer></div>
  <div class='modal' id='logoutModal'><div class='box'><div class='q'>?</div><h3>Konfirmasi Logout</h3><p>Apakah Anda yakin ingin logout dari TOKO DIERA?</p><div class='act'><button class='btn gray' id='lgBatal'>BATAL</button><a class='btn' href='logout.php'>YA, LOGOUT</a></div></div></div>
  <script src='assets/app.js'></script></body></html>";
}
