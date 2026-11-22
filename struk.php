<?php
require 'config.php';
auth(['admin', 'kasir']);

// Ambil transaksi dan rincian barang untuk dicetak sebagai struk.
$statement = $pdo->prepare(
    'SELECT p.*,u.nama kasir FROM penjualan p '
    . 'JOIN users u ON u.id=p.user_id WHERE p.id=?'
);
$statement->execute([(int) ($_GET['id'] ?? 0)]);
$sale = $statement->fetch();

if (!$sale) {
    die('Transaksi tidak ditemukan');
}

$details = $pdo->prepare('SELECT * FROM penjualan_detail WHERE penjualan_id=?');
$details->execute([$sale['id']]);
$items = $details->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Struk <?=e($sale['no_transaksi'])?></title>
    <style>
        body {
            font-family: monospace;
            background: #eee;
            margin: 0;
        }

        .receipt {
            width: 300px;
            background: #fff;
            margin: 14px auto;
            padding: 14px;
            font-size: 13px;
        }

        .center {
            text-align: center;
        }

        hr {
            border: 0;
            border-top: 1px dashed #000;
        }

        table {
            width: 100%;
        }

        td {
            padding: 2px 0;
            vertical-align: top;
        }

        .right {
            text-align: right;
        }

        .actions {
            text-align: center;
            margin: 10px;
        }

        button,
        a.back {
            padding: 8px 14px;
            border: 0;
            background: #1d5fd6;
            color: #fff;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            font-size: 13px;
        }

        @media print {
            .actions {
                display: none;
            }

            body {
                background: #fff;
            }

            .receipt {
                margin: 0;
            }
        }
    </style>
</head>
<body>
    <div class="receipt">
        <div class="center">
            <b>TOKO DIERA</b><br>
            Struk Penjualan
        </div>
        <hr>

        <table>
            <tr>
                <td>No</td>
                <td class="right"><?=e($sale['no_transaksi'])?></td>
            </tr>
            <tr>
                <td>Tanggal</td>
                <td class="right"><?=tgl($sale['tanggal'])?></td>
            </tr>
            <tr>
                <td>Kasir</td>
                <td class="right"><?=e($sale['kasir'])?></td>
            </tr>
        </table>
        <hr>

        <table>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td colspan="2"><?=e($item['nama_barang'])?></td>
                </tr>
                <tr>
                    <td><?=$item['qty']?> x <?=rp($item['harga'])?></td>
                    <td class="right"><?=rp($item['subtotal'])?></td>
                </tr>
            <?php endforeach; ?>
        </table>
        <hr>

        <table>
            <tr>
                <td>Subtotal</td>
                <td class="right"><?=rp($sale['subtotal'])?></td>
            </tr>
            <tr>
                <td>Diskon</td>
                <td class="right"><?=rp($sale['diskon'])?></td>
            </tr>
            <tr>
                <td><b>TOTAL</b></td>
                <td class="right"><b><?=rp($sale['total'])?></b></td>
            </tr>
            <tr>
                <td>Bayar</td>
                <td class="right"><?=rp($sale['bayar'])?></td>
            </tr>
            <tr>
                <td>Kembali</td>
                <td class="right"><?=rp($sale['kembali'])?></td>
            </tr>
        </table>
        <hr>

        <div class="center">
            Terima kasih atas kunjungan Anda<br>
            Barang yang dibeli dapat diretur dengan struk ini
        </div>
    </div>

    <div class="actions">
        <button onclick="window.print()">🖨 Cetak Struk</button>
        <a class="back" href="<?= $_SESSION['u']['role'] === 'kasir' ? 'transaksi.php' : 'penjualan.php' ?>">
            Kembali
        </a>
    </div>

    <?php if (isset($_GET['baru'])): ?>
        <script>window.onload = () => window.print();</script>
    <?php endif; ?>
</body>
</html>
