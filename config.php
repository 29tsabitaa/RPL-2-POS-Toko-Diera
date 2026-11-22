<?php
// Pengaturan koneksi database dan waktu aplikasi.
define('DB_HOST', 'localhost');
define('DB_NAME', 'toko_diera');
define('DB_USER', 'root');
define('DB_PASS', '');

session_start();
date_default_timezone_set('Asia/Jakarta');

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (Exception $e) {
    die('Koneksi database gagal. Import database.sql dan cek config.php');
}

// Helper tampilan agar data aman saat dimasukkan ke HTML.
function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function rp($value)
{
    return 'Rp' . number_format((float) $value, 0, ',', '.');
}

function tgl($date)
{
    return date('d M Y H:i', strtotime($date));
}

// Arahkan pengguna ke halaman lain lalu hentikan proses saat ini.
function redirect($url)
{
    header("Location: $url");
    exit;
}

// Simpan pesan untuk permintaan berikutnya atau tampilkan pesan yang tersimpan.
function flash($message = null, $type = 'ok')
{
    if ($message !== null) {
        $_SESSION['flash'] = [$message, $type];
        return;
    }

    if (!empty($_SESSION['flash'])) {
        [$text, $messageType] = $_SESSION['flash'];
        unset($_SESSION['flash']);

        return "<div class='alert $messageType'>" . e($text) . '</div>';
    }

    return '';
}

// Token CSRF melindungi form yang mengubah data.
function csrf()
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }

    return $_SESSION['csrf'];
}

function csrf_field()
{
    return "<input type='hidden' name='csrf' value='" . csrf() . "'>";
}

function csrf_check()
{
    if (($_POST['csrf'] ?? '') !== ($_SESSION['csrf'] ?? 'x')) {
        http_response_code(400);
        die('Token tidak valid');
    }
}

// Pastikan pengguna sudah login, aktif, dan memiliki peran yang diizinkan.
function auth(array $roles = [])
{
    global $pdo;

    if (empty($_SESSION['u'])) {
        redirect('login.php');
    }

    $statement = $pdo->prepare('SELECT status FROM users WHERE id=?');
    $statement->execute([$_SESSION['u']['id']]);

    if ($statement->fetchColumn() !== 'aktif') {
        session_destroy();
        redirect('login.php');
    }

    if ($roles && !in_array($_SESSION['u']['role'], $roles)) {
        http_response_code(403);
        die('Akses ditolak untuk peran Anda.');
    }
}

// Menu sidebar menyesuaikan peran pengguna yang sedang aktif.
function menu()
{
    $role = $_SESSION['u']['role'];
    $items = [
        'dashboard.php' => ['Dashboard', '🏠'],
    ];

    if ($role === 'admin') {
        $items += [
            'barang.php' => ['Data Barang', '📦'],
            'pengguna.php' => ['Data Pengguna', '👥'],
            'penjualan.php' => ['Data Penjualan', '🧾'],
            'retur.php' => ['Data Retur', '↩️'],
        ];
    }

    if ($role === 'kasir') {
        $items += [
            'barang.php' => ['Data Barang', '📦'],
            'transaksi.php' => ['Transaksi', '🛒'],
            'retur.php' => ['Pengajuan Retur', '↩️'],
        ];
    }

    if ($role === 'pemilik') {
        $items += [
            'laporan.php?jenis=penjualan' => ['Laporan Penjualan', '📈'],
            'laporan.php?jenis=stok' => ['Laporan Stok', '📦'],
            'laporan.php?jenis=retur' => ['Laporan Retur', '↩️'],
        ];
    }

    return $items;
}

// Header bersama untuk halaman internal yang memerlukan autentikasi.
function page_header($title, $crumb = '')
{
    $currentPage = basename($_SERVER['SCRIPT_NAME']);
    $reportType = $_GET['jenis'] ?? '';

    echo "<!DOCTYPE html><html lang='id'><head>";
    echo "<meta charset='utf-8'>";
    echo "<meta name='viewport' content='width=device-width,initial-scale=1'>";
    echo '<title>' . e($title) . ' - Toko Diera</title>';
    echo "<link rel='stylesheet' href='assets/style.css?v=20261121-readable'>";
    echo '</head><body>';

    echo "<aside class='side' id='side'>";
    echo "<div class='brand'><img src='assets/logo-toko-diera.png' alt='Logo Diera Grocer'>";
    echo '<span>DIERA GROCER</span></div><nav>';

    foreach (menu() as $url => [$label, $icon]) {
        $isCurrent = $currentPage === strtok($url, '?')
            && (!str_contains($url, 'jenis=') || str_contains($url, "jenis=$reportType"));
        $activeClass = $isCurrent ? 'on' : '';

        echo "<a class='$activeClass' href='$url'>$icon $label</a>";
    }

    echo "</nav><a href='#' class='btn-logout' data-logout>🚪 Logout</a></aside>";
    echo "<div class='main'><header class='top'>";
    echo "<button class='burger' onclick=\"document.getElementById('side').classList.toggle('show')\">☰</button>";
    echo "<span class='user'>👤 " . e($_SESSION['u']['nama']);
    echo ' (' . ucfirst($_SESSION['u']['role']) . ')</span></header><main>';
    echo '<h1>' . e($title) . '</h1>';
    echo "<div class='crumb'>Dashboard" . ($crumb ? ' / ' . e($crumb) : '') . '</div>';
    echo flash();
}

// Footer bersama, termasuk dialog konfirmasi logout.
function page_footer()
{
    echo '</main><footer>© ' . date('Y') . ' TOKO DIERA. All rights reserved.</footer></div>';
    echo "<div class='modal' id='logoutModal'><div class='box'><div class='q'>?</div>";
    echo '<h3>Konfirmasi Logout</h3>';
    echo '<p>Apakah Anda yakin ingin logout dari TOKO DIERA?</p>';
    echo "<div class='act'><button class='btn gray' id='lgBatal'>BATAL</button>";
    echo "<a class='btn' href='logout.php'>YA, LOGOUT</a></div></div></div>";
    echo "<script src='assets/app.js'></script></body></html>";
}
