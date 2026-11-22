<?php
require 'config.php';
auth(['admin']);

// Hapus transaksi dan kembalikan stok semua barang yang dijual.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $saleId = (int) $_POST['id'];

    $pdo->beginTransaction();

    $details = $pdo->prepare(
        'SELECT barang_id,qty FROM penjualan_detail WHERE penjualan_id=?'
    );
    $details->execute([$saleId]);

    foreach ($details->fetchAll() as $item) {
        $statement = $pdo->prepare('UPDATE barang SET stok=stok+? WHERE id=?');
        $statement->execute([$item['qty'], $item['barang_id']]);
    }

    $statement = $pdo->prepare('DELETE FROM penjualan WHERE id=?');
    $statement->execute([$saleId]);
    $pdo->commit();

    flash('Transaksi dihapus dan stok dikembalikan.');
    redirect('penjualan.php');
}

// Ambil transaksi dalam rentang tanggal yang diminta.
$fromDate = $_GET['dari'] ?? date('Y-m-01');
$toDate = $_GET['sampai'] ?? date('Y-m-d');
$statement = $pdo->prepare(
    'SELECT p.*,u.nama kasir FROM penjualan p '
    . 'JOIN users u ON u.id=p.user_id '
    . 'WHERE DATE(p.tanggal) BETWEEN ? AND ? ORDER BY p.id DESC'
);
$statement->execute([$fromDate, $toDate]);
$rows = $statement->fetchAll();

page_header('Data Penjualan', 'Data Penjualan');

echo "<div class='card'><form class='row'>";
echo "<div><label>Dari</label><input type='date' name='dari' value='$fromDate'></div>";
echo "<div><label>Sampai</label><input type='date' name='sampai' value='$toDate'></div>";
echo "<button class='btn'>Tampilkan</button></form><br><div class='table-wrap'><table>";
echo '<tr><th>No</th><th>No. Transaksi</th><th>Tanggal</th><th>Item</th>';
echo '<th>Total</th><th>Kasir</th><th>Aksi</th></tr>';

$sum = 0;
foreach ($rows as $index => $sale) {
    $sum += $sale['total'];

    echo '<tr><td>' . ($index + 1) . '</td>';
    echo '<td>' . e($sale['no_transaksi']) . '</td>';
    echo '<td>' . tgl($sale['tanggal']) . '</td>';
    echo '<td>' . $sale['total_item'] . '</td>';
    echo '<td>' . rp($sale['total']) . '</td>';
    echo '<td>' . e($sale['kasir']) . '</td>';
    echo "<td><a class='btn sm' href='struk.php?id={$sale['id']}'>Struk</a> ";
    echo "<form method='post' style='display:inline' ";
    echo "data-confirm='Hapus transaksi ini? Stok akan dikembalikan.'>";
    echo csrf_field();
    echo "<input type='hidden' name='id' value='{$sale['id']}'>";
    echo "<button class='btn sm red'>Hapus</button></form></td></tr>";
}

if (!$rows) {
    echo "<tr><td colspan='7'>Tidak ada data.</td></tr>";
}

echo '<tr><th colspan="4">Total</th><th colspan="3">' . rp($sum) . '</th></tr>';
echo '</table></div></div>';
page_footer();
