<?php
require 'config.php';

// Baca kata kunci dan kategori dari filter katalog.
$q = trim($_GET['q'] ?? '');
$kat = trim($_GET['kategori'] ?? '');
$sql = 'SELECT nama,kategori,harga,stok FROM barang WHERE (nama LIKE ? OR kode LIKE ?)';
$p = ["%$q%", "%$q%"];
if ($kat !== '') {
    $sql .= ' AND kategori=?';
    $p[] = $kat;
}
$s = $pdo->prepare($sql.' ORDER BY nama');
$s->execute($p);
$rows = $s->fetchAll();

// Data ringkasan ditampilkan di atas daftar produk.
$cats = $pdo->query('SELECT DISTINCT kategori FROM barang ORDER BY kategori')->fetchAll(PDO::FETCH_COLUMN);
$totalBarang = $pdo->query('SELECT COUNT(*) FROM barang')->fetchColumn();
$stokTersedia = $pdo->query('SELECT COUNT(*) FROM barang WHERE stok>10')->fetchColumn();
$stokMenipis = $pdo->query('SELECT COUNT(*) FROM barang WHERE stok>0 AND stok<=10')->fetchColumn();
$stokHabis = $pdo->query('SELECT COUNT(*) FROM barang WHERE stok<=0')->fetchColumn();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Dashboard Katalog - Toko Diera</title>
  <link rel="stylesheet" href="assets/style.css?v=20261121-readable">
  <style>
    body {
      background: #eef3f9;
    }

    .catalog-top {
      height: 72px;
      padding: 0 34px;
      background: #08265e;
      color: #fff;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .catalog-brand {
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 18px;
      font-weight: 800;
    }

    .catalog-brand img {
      width: 48px;
      height: 48px;
      object-fit: contain;
      border-radius: 50%;
      background: #fff;
    }

    .catalog-actions {
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .catalog-actions .btn {
      font-size: 13px;
    }

    .catalog-actions .btn-outline {
      background: transparent;
      border: 1px solid #a9c2ed;
      color: #fff;
    }

    .catalog-main {
      max-width: 1180px;
      margin: auto;
      padding: 28px 22px 40px;
    }

    .welcome {
      margin-bottom: 18px;
      display: flex;
      align-items: end;
      justify-content: space-between;
      gap: 16px;
    }

    .welcome h1 {
      font-size: 25px;
      margin-bottom: 3px;
    }

    .welcome p {
      margin: 0;
      color: #64748b;
    }

    /* Promosi berganti otomatis melalui JavaScript di bawah. */
    .catalog-slider {
      position: relative;
      height: 240px;
      margin-bottom: 18px;
      border-radius: 18px;
      overflow: hidden;
      background: #08265e;
      box-shadow: 0 10px 28px #183b7122;
    }

    .catalog-slide {
      position: absolute;
      inset: 0;
      opacity: 0;
      visibility: hidden;
      transition: opacity .55s ease, visibility .55s ease;
    }

    .catalog-slide.active {
      opacity: 1;
      visibility: visible;
    }

    .catalog-slide img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }

    .slider-overlay {
      position: absolute;
      inset: 0;
      display: flex;
      flex-direction: column;
      justify-content: center;
      padding: 38px 42px;
      max-width: 520px;
      color: #fff;
      z-index: 2;
    }

    .slider-overlay span {
      font-size: 12px;
      font-weight: 800;
      letter-spacing: 1.4px;
      text-transform: uppercase;
      margin-bottom: 8px;
      opacity: .86;
    }

    .slider-overlay h2 {
      font-size: 31px;
      line-height: 1.15;
      margin: 0 0 8px;
    }

    .slider-overlay p {
      margin: 0;
      line-height: 1.6;
      opacity: .9;
    }

    .slider-control {
      position: absolute;
      z-index: 3;
      top: 50%;
      transform: translateY(-50%);
      width: 38px;
      height: 38px;
      border: 0;
      border-radius: 50%;
      background: #fff;
      color: #0a2a66;
      font-size: 20px;
      font-weight: 800;
      cursor: pointer;
      box-shadow: 0 4px 12px #0002;
    }

    .slider-prev {
      left: 14px;
    }

    .slider-next {
      right: 14px;
    }

    .slider-dots {
      position: absolute;
      z-index: 3;
      left: 50%;
      bottom: 12px;
      transform: translateX(-50%);
      display: flex;
      gap: 7px;
    }

    .slider-dot {
      width: 8px;
      height: 8px;
      border: 0;
      border-radius: 50%;
      padding: 0;
      background: #fff;
      opacity: .45;
      cursor: pointer;
    }

    .slider-dot.active {
      width: 24px;
      border-radius: 99px;
      opacity: 1;
    }

    /* Ringkasan jumlah barang berdasarkan kategori stoknya. */
    .catalog-stats {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 14px;
      margin-bottom: 18px;
    }

    .catalog-stat {
      background: #fff;
      border: 1px solid #e4eaf3;
      border-radius: 12px;
      padding: 17px 18px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      box-shadow: 0 4px 14px #183b7110;
    }

    .catalog-stat small {
      display: block;
      color: #64748b;
      margin-bottom: 5px;
    }

    .catalog-stat b {
      font-size: 24px;
      color: #0a2a66;
    }

    .stat-icon {
      width: 43px;
      height: 43px;
      border-radius: 12px;
      display: grid;
      place-items: center;
      background: #edf4ff;
      font-size: 21px;
    }

    /* Filter pencarian dan tampilan kartu produk. */
    .filter-card {
      background: #fff;
      border: 1px solid #e4eaf3;
      border-radius: 12px;
      padding: 14px;
      margin-bottom: 18px;
      box-shadow: 0 4px 14px #183b7108;
    }

    .catalog-filter {
      display: flex;
      gap: 10px;
      align-items: center;
    }

    .catalog-filter input,
    .catalog-filter select {
      height: 42px;
    }

    .catalog-filter input {
      flex: 1;
      min-width: 220px;
    }

    .catalog-filter select {
      flex: 0 0 190px;
    }

    .catalog-filter .btn {
      height: 42px;
      min-width: 90px;
    }

    .section-title {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin: 20px 0 12px;
    }

    .section-title h2 {
      margin: 0;
      font-size: 18px;
    }

    .section-title span {
      color: #64748b;
      font-size: 13px;
    }

    .items {
      display: grid;
      gap: 14px;
      grid-template-columns: repeat(4, minmax(0, 1fr));
    }

    .it {
      background: #fff;
      border: 1px solid #e4eaf3;
      border-radius: 12px;
      padding: 16px;
      box-shadow: 0 4px 14px #183b7110;
      transition: .2s;
    }

    .it:hover {
      transform: translateY(-3px);
      box-shadow: 0 10px 24px #183b7118;
    }

    .it-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 13px;
    }

    .it .ic {
      width: 48px;
      height: 48px;
      border-radius: 12px;
      background: #eef4ff;
      display: grid;
      place-items: center;
      font-size: 27px;
    }

    .it h3 {
      margin: 0 0 4px;
      font-size: 15px;
      color: #172033;
    }

    .it small {
      color: #64748b;
    }

    .it-bottom {
      display: flex;
      align-items: end;
      justify-content: space-between;
      gap: 8px;
      margin-top: 14px;
    }

    .it .h {
      font-size: 17px;
      font-weight: 800;
      color: #0a2a66;
    }

    .empty-state {
      grid-column: 1 / -1;
      background: #fff;
      border-radius: 12px;
      padding: 42px;
      text-align: center;
      color: #64748b;
    }

    .empty-state-icon {
      font-size: 38px;
      margin-bottom: 8px;
    }

    @media (max-width: 900px) {
      .catalog-stats {
        grid-template-columns: repeat(2, 1fr);
      }

      .items {
        grid-template-columns: repeat(2, 1fr);
      }
    }

    @media (max-width: 600px) {
      .catalog-top {
        height: auto;
        padding: 14px 16px;
      }

      .catalog-brand {
        font-size: 16px;
      }

      .catalog-main {
        padding: 20px 14px;
      }

      .welcome {
        align-items: start;
      }

      .catalog-slider {
        height: 210px;
      }

      .slider-overlay {
        padding: 26px 54px;
      }

      .slider-overlay h2 {
        font-size: 22px;
      }

      .slider-overlay p {
        font-size: 12px;
      }

      .catalog-stats {
        grid-template-columns: 1fr 1fr;
      }

      .catalog-filter {
        flex-wrap: wrap;
      }

      .catalog-filter input {
        flex: 1 1 100%;
        min-width: 0;
      }

      .catalog-filter select {
        flex: 1;
      }

      .items {
        grid-template-columns: 1fr;
      }

      .catalog-actions .btn-outline {
        display: none;
      }
    }
  </style>
</head>
<body>
  <header class="catalog-top">
    <div class="catalog-brand"><img src="assets/logo-toko-diera.png" alt="Logo Diera Grocer"> DIERA GROCER</div>
    <div class="catalog-actions">
      <?php if (!empty($_SESSION['u'])): ?>
        <a class="btn btn-outline" href="dashboard.php">Dashboard</a>
        <a class="btn" style="background:#fff;color:#0a2a66" href="logout.php">Logout</a>
      <?php else: ?>
        <a class="btn btn-outline" href="login.php">Login</a>
        <a class="btn" style="background:#fff;color:#0a2a66" href="register.php">Daftar</a>
      <?php endif; ?>
    </div>
  </header>

  <main class="catalog-main">
    <div class="welcome">
      <div>
        <h1>Dashboard Katalog</h1>
        <p>Temukan dan pantau ketersediaan produk Toko Diera</p>
      </div>
    </div>

    <section class="catalog-slider" id="catalogSlider" aria-label="Promosi Toko Diera">
      <div class="catalog-slide active">
        <img src="assets/banner-1.svg" alt="Produk lengkap Toko Diera">
        <div class="slider-overlay">
          <span>Belanja Lebih Mudah</span>
          <h2>Temukan Semua Kebutuhan Anda</h2>
          <p>Lengkap, mudah dicari, dan siap melayani kebutuhan toko Anda.</p>
        </div>
      </div>
      <div class="catalog-slide">
        <img src="assets/banner-2.svg" alt="Pantau stok barang">
        <div class="slider-overlay">
          <span>Pantau Stok</span>
          <h2>Stok Terupdate dan Terpercaya</h2>
          <p>Informasi ketersediaan barang dapat dipantau dengan cepat.</p>
        </div>
      </div>
      <div class="catalog-slide">
        <img src="assets/banner-3.svg" alt="Kategori produk beragam">
        <div class="slider-overlay">
          <span>Pilihan Lengkap</span>
          <h2>Beragam Produk dalam Satu Tempat</h2>
          <p>Gunakan pencarian dan filter untuk menemukan barang yang tepat.</p>
        </div>
      </div>
      <button class="slider-control slider-prev" type="button" aria-label="Slide sebelumnya">&#8249;</button>
      <button class="slider-control slider-next" type="button" aria-label="Slide berikutnya">&#8250;</button>
      <div class="slider-dots" aria-label="Pilih slide">
        <button class="slider-dot active" type="button" aria-label="Slide 1"></button>
        <button class="slider-dot" type="button" aria-label="Slide 2"></button>
        <button class="slider-dot" type="button" aria-label="Slide 3"></button>
      </div>
    </section>

    <section class="catalog-stats">
      <div class="catalog-stat">
        <div><small>Total Barang</small><b><?=e($totalBarang)?></b></div>
        <div class="stat-icon">📦</div>
      </div>
      <div class="catalog-stat">
        <div><small>Stok Tersedia</small><b><?=e($stokTersedia)?></b></div>
        <div class="stat-icon">✅</div>
      </div>
      <div class="catalog-stat">
        <div><small>Stok Menipis</small><b><?=e($stokMenipis)?></b></div>
        <div class="stat-icon">⚠️</div>
      </div>
      <div class="catalog-stat">
        <div><small>Stok Habis</small><b><?=e($stokHabis)?></b></div>
        <div class="stat-icon">🚫</div>
      </div>
    </section>

    <form class="filter-card catalog-filter" method="get">
      <input name="q" placeholder="Cari nama atau kode barang..." value="<?=e($q)?>">
      <select name="kategori">
        <option value="">Semua Kategori</option>
        <?php foreach ($cats as $c): ?>
          <option value="<?=e($c)?>" <?=$c === $kat ? 'selected' : ''?>><?=e($c)?></option>
        <?php endforeach; ?>
      </select>
      <button class="btn" type="submit">Cari</button>
    </form>

    <div class="section-title">
      <h2>Daftar Barang</h2>
      <span><?=count($rows)?> barang ditemukan</span>
    </div>

    <section class="items">
      <?php foreach ($rows as $b): ?>
        <?php
          $status = $b['stok'] <= 0
            ? ['Habis', 'ditolak']
            : ($b['stok'] <= 10 ? ['Terbatas', 'pending'] : ['Tersedia', 'aktif']);
        ?>
        <article class="it">
          <div class="it-head">
            <div class="ic">📦</div>
            <span class="badge b-<?=$status[1]?>"><?=$status[0]?></span>
          </div>
          <h3><?=e($b['nama'])?></h3>
          <small><?=e($b['kategori'])?></small>
          <div class="it-bottom">
            <div class="h"><?=rp($b['harga'])?></div>
            <small>Stok: <?=e($b['stok'])?></small>
          </div>
        </article>
      <?php endforeach; ?>
      <?php if (!$rows): ?>
        <div class="empty-state">
          <div class="empty-state-icon">🔎</div>
          <b>Barang tidak ditemukan</b>
          <p>Coba ubah kata pencarian atau kategori.</p>
        </div>
      <?php endif; ?>
    </section>
  </main>

  <footer>© <?=date('Y')?> TOKO DIERA. All rights reserved.</footer>
  <script>
    (function () {
      const slider = document.getElementById('catalogSlider');
      if (!slider) return;

      const slides = Array.from(slider.querySelectorAll('.catalog-slide'));
      const dots = Array.from(slider.querySelectorAll('.slider-dot'));
      let current = 0;
      let timer;

      function show(index) {
        current = (index + slides.length) % slides.length;
        slides.forEach((slide, i) => slide.classList.toggle('active', i === current));
        dots.forEach((dot, i) => dot.classList.toggle('active', i === current));
      }

      function restart() {
        window.clearInterval(timer);
        timer = window.setInterval(() => show(current + 1), 5000);
      }

      slider.querySelector('.slider-prev').addEventListener('click', () => {
        show(current - 1);
        restart();
      });
      slider.querySelector('.slider-next').addEventListener('click', () => {
        show(current + 1);
        restart();
      });
      dots.forEach((dot, i) => dot.addEventListener('click', () => {
        show(i);
        restart();
      }));

      restart();
    })();
  </script>
</body>
</html>
