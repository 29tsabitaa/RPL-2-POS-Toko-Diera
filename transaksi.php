<?php
require 'config.php'; auth(['kasir']);
if(isset($_GET['save'])){
  header('Content-Type: application/json');
  $in=json_decode(file_get_contents('php://input'),true);
  if(($in['csrf']??'')!==csrf()){ echo json_encode(['ok'=>false,'msg'=>'Token tidak valid']); exit; }
  try{
    $pdo->beginTransaction(); $items=$in['items']??[]; if(!$items) throw new Exception('Keranjang masih kosong.');
    $sub=0;$ti=0;$det=[];
    foreach($items as $it){
      $s=$pdo->prepare('SELECT * FROM barang WHERE id=? FOR UPDATE'); $s->execute([(int)$it['id']]); $b=$s->fetch(); $q=(int)$it['qty'];
      if(!$b||$q<1) throw new Exception('Data barang tidak valid.');
      if($b['stok']<$q) throw new Exception("Stok {$b['nama']} tidak cukup (sisa {$b['stok']}).");
      $det[]=[$b,$q]; $sub+=$b['harga']*$q; $ti+=$q;
    }
    $dis=max(0,min((int)($in['diskon']??0),$sub)); $tot=$sub-$dis; $bayar=(int)($in['bayar']??0);
    if($bayar<$tot) throw new Exception('Uang bayar kurang dari total.');
    $no='TRX'.date('ymdHis').rand(10,99);
    $pdo->prepare('INSERT INTO penjualan(no_transaksi,user_id,total_item,subtotal,diskon,total,bayar,kembali) VALUES(?,?,?,?,?,?,?,?)')->execute([$no,$_SESSION['u']['id'],$ti,$sub,$dis,$tot,$bayar,$bayar-$tot]);
    $pid=$pdo->lastInsertId();
    foreach($det as [$b,$q]){
      $pdo->prepare('INSERT INTO penjualan_detail(penjualan_id,barang_id,nama_barang,harga,qty,subtotal) VALUES(?,?,?,?,?,?)')->execute([$pid,$b['id'],$b['nama'],$b['harga'],$q,$b['harga']*$q]);
      $pdo->prepare('UPDATE barang SET stok=stok-? WHERE id=?')->execute([$q,$b['id']]);
    }
    $pdo->commit(); echo json_encode(['ok'=>true,'id'=>$pid]);
  }catch(Exception $e){ if($pdo->inTransaction())$pdo->rollBack(); echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]); }
  exit;
}
$barang=$pdo->query('SELECT id,kode,nama,harga,stok FROM barang WHERE stok>0 ORDER BY nama')->fetchAll();
page_header('Transaksi Penjualan','Transaksi');
?>
<div class="pos">
 <div class="card">
  <input id="cari" placeholder="Scan / input kode atau nama barang lalu Enter" autocomplete="off" autofocus>
  <div id="hasil" style="max-height:140px;overflow:auto"></div><br>
  <div class="table-wrap"><table><thead><tr><th>No</th><th>Nama Barang</th><th>Harga</th><th>Qty</th><th>Subtotal</th><th></th></tr></thead><tbody id="cart"></tbody></table></div>
  <p id="kosong" style="color:#64748b">Belum ada barang.</p>
  <button class="btn red sm" onclick="if(confirm('Batalkan transaksi?')){cart=[];draw()}">BATAL TRANSAKSI</button>
 </div>
 <div class="card">
  <small>Total Item</small><div class="big" id="ti">0</div><small>Subtotal</small><div id="sub" style="font-weight:700">Rp0</div>
  <label>Diskon (Rp)</label><input type="number" id="dis" min="0" value="0">
  <small>TOTAL</small><div class="big" id="tot">Rp0</div>
  <label>Bayar (Rp)</label><input type="number" id="bayar" min="0" value="0">
  <small>Kembali</small><div class="big" style="color:#16a34a" id="kem">Rp0</div><br>
  <button class="btn" style="width:100%" id="btnBayar">BAYAR & CETAK STRUK</button>
 </div>
</div>
<script>
const DATA=<?=json_encode($barang)?>, CSRF=<?=json_encode(csrf())?>; let cart=[];
const $=id=>document.getElementById(id), rp=n=>'Rp'+Number(n).toLocaleString('id-ID');
function add(id){const b=DATA.find(x=>x.id==id);if(!b)return;let c=cart.find(x=>x.id==id);
 if(c){if(c.qty<b.stok)c.qty++;else alert('Stok maksimal tercapai');}else cart.push({id:b.id,nama:b.nama,harga:b.harga,qty:1,stok:b.stok});
 $('cari').value='';$('hasil').innerHTML='';draw();$('cari').focus();}
function setQty(i,v){v=parseInt(v)||1;cart[i].qty=Math.max(1,Math.min(v,cart[i].stok));draw();}
function draw(){
 $('cart').innerHTML=cart.map((c,i)=>`<tr><td>${i+1}</td><td>${c.nama}</td><td>${rp(c.harga)}</td><td><div class="qty"><button class="btn gray sm" onclick="setQty(${i},${c.qty-1})">-</button><input value="${c.qty}" onchange="setQty(${i},this.value)"><button class="btn gray sm" onclick="setQty(${i},${c.qty+1})">+</button></div></td><td>${rp(c.harga*c.qty)}</td><td><button class="btn red sm" onclick="cart.splice(${i},1);draw()">🗑</button></td></tr>`).join('');
 $('kosong').style.display=cart.length?'none':'block'; calc();}
function calc(){const sub=cart.reduce((a,c)=>a+c.harga*c.qty,0),ti=cart.reduce((a,c)=>a+c.qty,0);
 const dis=Math.min(Math.max(0,+$('dis').value||0),sub),tot=sub-dis,by=+$('bayar').value||0;
 $('ti').textContent=ti;$('sub').textContent=rp(sub);$('tot').textContent=rp(tot);$('kem').textContent=rp(Math.max(0,by-tot));}
$('dis').oninput=$('bayar').oninput=calc;
$('cari').addEventListener('input',()=>{const q=$('cari').value.toLowerCase().trim();
 $('hasil').innerHTML=q?DATA.filter(b=>b.nama.toLowerCase().includes(q)||b.kode.toLowerCase().includes(q)).slice(0,8).map(b=>`<div style="padding:6px;cursor:pointer;border-bottom:1px solid #eee" onclick="add(${b.id})">${b.kode} — ${b.nama} (${rp(b.harga)}, stok ${b.stok})</div>`).join(''):'';});
$('cari').addEventListener('keydown',e=>{if(e.key!=='Enter')return;e.preventDefault();const q=$('cari').value.toLowerCase().trim();
 const b=DATA.find(x=>x.kode.toLowerCase()===q)||DATA.find(x=>x.nama.toLowerCase().includes(q));if(b)add(b.id);else alert('Barang tidak ditemukan / stok habis');});
$('btnBayar').onclick=async()=>{
 if(!cart.length)return alert('Keranjang kosong');
 const r=await fetch('transaksi.php?save=1',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({csrf:CSRF,items:cart.map(c=>({id:c.id,qty:c.qty})),diskon:+$('dis').value||0,bayar:+$('bayar').value||0})});
 const j=await r.json(); if(j.ok){location.href='struk.php?id='+j.id+'&baru=1';}else alert(j.msg);};
draw();
</script>
<?php page_footer();
