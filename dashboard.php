<?php
require 'config.php';
auth();

$role = $_SESSION['u']['role'];
$queryValue = fn($sql) => $pdo->query($sql)->fetchColumn();

page_header('Dashboard');
echo "<div class='card'>Selamat datang, <b>"
    . e($_SESSION['u']['nama'])
    . '</b> (' . ucfirst($role) . ").</div><div class='grid'>";

// Ringkasan utama yang ditampilkan kepada setiap peran.
$stats = [
    [
        'Penjualan Hari Ini',
        rp($queryValue(
            'SELECT COALESCE(SUM(total),0) FROM penjualan WHERE DATE(tanggal)=CURDATE()'
        )),
    ],
    [
        'Transaksi Hari Ini',
        $queryValue('SELECT COUNT(*) FROM penjualan WHERE DATE(tanggal)=CURDATE()'),
    ],
    [
        'Jumlah Barang',
        $queryValue('SELECT COUNT(*) FROM barang'),
    ],
    [
        'Stok Menipis (≤10)',
        $queryValue('SELECT COUNT(*) FROM barang WHERE stok<=10'),
    ],
];

// Tambahkan statistik yang khusus untuk admin atau pemilik.
if ($role === 'admin') {
    $stats[] = [
        'Akun Menunggu Validasi',
        $queryValue("SELECT COUNT(*) FROM users WHERE status='pending'"),
    ];
    $stats[] = [
        'Retur Diajukan',
        $queryValue("SELECT COUNT(*) FROM retur WHERE status='diajukan'"),
    ];
}

if ($role === 'pemilik') {
    $stats[] = [
        'Total Penjualan Bulan Ini',
        rp($queryValue(
            'SELECT COALESCE(SUM(total),0) FROM penjualan '
            . 'WHERE MONTH(tanggal)=MONTH(CURDATE()) AND YEAR(tanggal)=YEAR(CURDATE())'
        )),
    ];
}

foreach ($stats as [$label, $value]) {
    echo "<div class='card stat'><small>"
        . e($label)
        . '</small><b>' . e($value) . '</b></div>';
}

echo '</div>';
page_footer();
