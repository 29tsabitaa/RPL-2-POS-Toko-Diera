<?php
require 'config.php';
auth(['pemilik', 'admin']);

// Pilih salah satu jenis laporan yang tersedia.
$validTypes = ['penjualan', 'stok', 'retur'];
$reportType = in_array($_GET['jenis'] ?? '', $validTypes)
    ? $_GET['jenis']
    : 'penjualan';
$fromDate = $_GET['dari'] ?? date('Y-m-01');
$toDate = $_GET['sampai'] ?? date('Y-m-d');

page_header('Laporan ' . ucfirst($reportType), 'Laporan ' . ucfirst($reportType));

// Kontrol tanggal dan tombol cetak tidak ikut muncul di hasil cetak.
echo "<div class='card noprint'><form class='row'>";
echo "<input type='hidden' name='jenis' value='$reportType'>";

if ($reportType !== 'stok') {
    echo "<div><label>Dari</label><input type='date' name='dari' value='$fromDate'></div>";
    echo "<div><label>Sampai</label><input type='date' name='sampai' value='$toDate'></div>";
    echo "<button class='btn'>Tampilkan</button>";
}

echo "<button type='button' class='btn green' onclick='window.print()'>🖨 Cetak</button>";
echo '</form></div>';

echo "<div class='card'><div style='text-align:center'><b>TOKO DIERA</b><br>";
echo 'Laporan ' . ucfirst($reportType);
echo $reportType !== 'stok'
    ? "<br><small>Periode "
        . date('d/m/Y', strtotime($fromDate))
        . ' - '
        . date('d/m/Y', strtotime($toDate))
        . '</small>'
    : "<br><small>Per " . date('d/m/Y H:i') . '</small>';
echo '</div><br>';

if ($reportType === 'penjualan') {
    // Ringkasan penjualan berdasarkan rentang tanggal.
    $statement = $pdo->prepare(
        'SELECT p.*,u.nama kasir FROM penjualan p '
        . 'JOIN users u ON u.id=p.user_id '
        . 'WHERE DATE(p.tanggal) BETWEEN ? AND ? ORDER BY p.tanggal'
    );
    $statement->execute([$fromDate, $toDate]);
    $sales = $statement->fetchAll();

    $totalSales = array_sum(array_column($sales, 'total'));
    $totalItems = array_sum(array_column($sales, 'total_item'));
    $transactionCount = count($sales);

    echo "<div class='grid'>";
    echo "<div class='card stat'><small>Total Penjualan</small><b>" . rp($totalSales) . '</b></div>';
    echo "<div class='card stat'><small>Jumlah Transaksi</small><b>"
        . $transactionCount . '</b></div>';
    echo "<div class='card stat'><small>Total Item Terjual</small><b>"
        . $totalItems . '</b></div>';
    echo "<div class='card stat'><small>Rata-rata Transaksi</small><b>"
        . rp($transactionCount ? $totalSales / $transactionCount : 0)
        . '</b></div></div>';

    echo "<div class='table-wrap'><table>";
    echo '<tr><th>No</th><th>No. Transaksi</th><th>Tanggal</th><th>Item</th>';
    echo '<th>Diskon</th><th>Total</th><th>Kasir</th></tr>';

    foreach ($sales as $index => $sale) {
        echo '<tr><td>' . ($index + 1) . '</td>';
        echo '<td>' . e($sale['no_transaksi']) . '</td>';
        echo '<td>' . tgl($sale['tanggal']) . '</td>';
        echo '<td>' . $sale['total_item'] . '</td>';
        echo '<td>' . rp($sale['diskon']) . '</td>';
        echo '<td>' . rp($sale['total']) . '</td>';
        echo '<td>' . e($sale['kasir']) . '</td></tr>';
    }

    if (!$sales) {
        echo "<tr><td colspan='7'>Tidak ada data.</td></tr>";
    }

    echo '</table></div>';

    // Tampilkan lima barang dengan jumlah penjualan tertinggi.
    $statement = $pdo->prepare(
        'SELECT d.nama_barang,SUM(d.qty) q,SUM(d.subtotal) s '
        . 'FROM penjualan_detail d JOIN penjualan p ON p.id=d.penjualan_id '
        . 'WHERE DATE(p.tanggal) BETWEEN ? AND ? '
        . 'GROUP BY d.barang_id,d.nama_barang ORDER BY q DESC LIMIT 5'
    );
    $statement->execute([$fromDate, $toDate]);
    $bestSellingItems = $statement->fetchAll();

    if ($bestSellingItems) {
        $maxQuantity = max(array_column($bestSellingItems, 'q'));
        echo '<br><b>5 Barang Terlaris</b><table>';

        foreach ($bestSellingItems as $item) {
            $barWidth = round($item['q'] / $maxQuantity * 100);
            echo "<tr><td width='30%'>" . e($item['nama_barang']) . '</td>';
            echo "<td><div class='bar' style='width:$barWidth%'></div></td>";
            echo "<td width='25%'>{$item['q']} pcs / " . rp($item['s']) . '</td></tr>';
        }

        echo '</table>';
    }
} elseif ($reportType === 'stok') {
    // Nilai stok dihitung dari harga dikali jumlah barang saat ini.
    $items = $pdo->query('SELECT * FROM barang ORDER BY stok ASC, nama')->fetchAll();
    $stockValue = 0;

    echo "<div class='table-wrap'><table>";
    echo '<tr><th>No</th><th>Kode</th><th>Nama</th><th>Kategori</th>';
    echo '<th>Harga</th><th>Stok</th><th>Nilai Stok</th><th>Status</th></tr>';

    foreach ($items as $index => $item) {
        $stockValue += $item['harga'] * $item['stok'];
        $status = $item['stok'] == 0
            ? ['Habis', 'ditolak']
            : ($item['stok'] <= 10 ? ['Menipis', 'pending'] : ['Aman', 'aktif']);

        echo '<tr><td>' . ($index + 1) . '</td>';
        echo '<td>' . e($item['kode']) . '</td>';
        echo '<td>' . e($item['nama']) . '</td>';
        echo '<td>' . e($item['kategori']) . '</td>';
        echo '<td>' . rp($item['harga']) . '</td>';
        echo '<td>' . $item['stok'] . '</td>';
        echo '<td>' . rp($item['harga'] * $item['stok']) . '</td>';
        echo "<td><span class='badge b-{$status[1]}'>{$status[0]}</span></td></tr>";
    }

    echo "<tr><th colspan='5'>Total</th>";
    echo '<th>' . array_sum(array_column($items, 'stok')) . '</th>';
    echo '<th colspan="2">' . rp($stockValue) . '</th></tr>';
    echo '</table></div>';
} else {
    // Laporan retur menampilkan pengajuan serta jumlah yang disetujui.
    $statement = $pdo->prepare(
        "SELECT r.*,p.no_transaksi,u.nama kasir,"
        . "(SELECT GROUP_CONCAT(CONCAT(nama_barang,' x',qty) SEPARATOR ', ') "
        . 'FROM retur_detail WHERE retur_id=r.id) items '
        . 'FROM retur r JOIN penjualan p ON p.id=r.penjualan_id '
        . 'JOIN users u ON u.id=r.user_id '
        . 'WHERE DATE(r.tanggal) BETWEEN ? AND ? ORDER BY r.tanggal'
    );
    $statement->execute([$fromDate, $toDate]);
    $returns = $statement->fetchAll();
    $approvedReturns = array_filter(
        $returns,
        fn($return) => $return['status'] === 'disetujui'
    );

    echo "<div class='grid'>";
    echo "<div class='card stat'><small>Total Pengajuan</small><b>"
        . count($returns) . '</b></div>';
    echo "<div class='card stat'><small>Disetujui</small><b>"
        . count($approvedReturns) . '</b></div>';
    echo "<div class='card stat'><small>Nilai Retur Disetujui</small><b>"
        . rp(array_sum(array_column($approvedReturns, 'total')))
        . '</b></div></div>';

    echo "<div class='table-wrap'><table>";
    echo '<tr><th>No</th><th>No. Retur</th><th>Tanggal</th><th>Transaksi</th>';
    echo '<th>Barang</th><th>Alasan</th><th>Total</th><th>Status</th></tr>';

    foreach ($returns as $index => $return) {
        echo '<tr><td>' . ($index + 1) . '</td>';
        echo '<td>' . e($return['no_retur']) . '</td>';
        echo '<td>' . tgl($return['tanggal']) . '</td>';
        echo '<td>' . e($return['no_transaksi']) . '</td>';
        echo '<td>' . e($return['items']) . '</td>';
        echo '<td>' . e($return['alasan']) . '</td>';
        echo '<td>' . rp($return['total']) . '</td>';
        echo "<td><span class='badge b-{$return['status']}'>{$return['status']}</span></td></tr>";
    }

    if (!$returns) {
        echo "<tr><td colspan='8'>Tidak ada data.</td></tr>";
    }

    echo '</table></div>';
}

echo '</div>';
page_footer();
