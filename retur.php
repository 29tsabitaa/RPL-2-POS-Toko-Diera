<?php
require 'config.php';
auth(['admin', 'kasir']);

$isAdmin = $_SESSION['u']['role'] === 'admin';
$userId = $_SESSION['u']['id'];

// Sisa barang yang masih dapat diretur, tidak termasuk pengajuan yang ditolak.
$itemsQuery = "SELECT d.*, d.qty-COALESCE((SELECT SUM(rd.qty) "
    . 'FROM retur_detail rd JOIN retur r ON r.id=rd.retur_id '
    . "WHERE r.penjualan_id=d.penjualan_id AND rd.barang_id=d.barang_id "
    . "AND r.status<>'ditolak'),0) AS sisa "
    . 'FROM penjualan_detail d WHERE d.penjualan_id=?';

// Proses pengajuan retur oleh kasir atau keputusan retur oleh admin.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['aksi'] ?? '';

    try {
        if (!$isAdmin && $action === 'ajukan') {
            $saleId = (int) $_POST['penjualan_id'];
            $selectedItems = $_POST['pilih'] ?? [];
            $quantities = $_POST['qty'] ?? [];
            $reason = trim($_POST['alasan'] ?? '');

            if (!$selectedItems) {
                throw new Exception('Pilih minimal satu barang yang diretur.');
            }

            if ($reason === '') {
                throw new Exception('Alasan retur wajib dipilih.');
            }

            $statement = $pdo->prepare($itemsQuery);
            $statement->execute([$saleId]);
            $itemsById = [];

            foreach ($statement->fetchAll() as $item) {
                $itemsById[$item['id']] = $item;
            }

            $pdo->beginTransaction();
            $subtotal = 0;
            $totalItems = 0;
            $returnDetails = [];

            foreach ($selectedItems as $detailId) {
                $item = $itemsById[$detailId] ?? null;
                $quantity = (int) ($quantities[$detailId] ?? 0);

                if (!$item || $quantity < 1 || $quantity > $item['sisa']) {
                    $itemName = $item['nama_barang'] ?? '';
                    $remaining = $item['sisa'] ?? 0;
                    throw new Exception(
                        "Jumlah retur $itemName tidak valid (maks $remaining)."
                    );
                }

                $returnDetails[] = [$item, $quantity];
                $subtotal += $item['harga'] * $quantity;
                $totalItems += $quantity;
            }

            $discount = max(0, min((int) ($_POST['diskon'] ?? 0), $subtotal));
            $returnNumber = 'RTR' . date('ymdHis') . rand(10, 99);
            $statement = $pdo->prepare(
                'INSERT INTO retur'
                . '(no_retur,penjualan_id,user_id,total_item,diskon,total,alasan,keterangan) '
                . 'VALUES(?,?,?,?,?,?,?,?)'
            );
            $statement->execute([
                $returnNumber,
                $saleId,
                $userId,
                $totalItems,
                $discount,
                $subtotal - $discount,
                $reason,
                trim($_POST['keterangan'] ?? ''),
            ]);
            $returnId = $pdo->lastInsertId();

            foreach ($returnDetails as [$item, $quantity]) {
                $statement = $pdo->prepare(
                    'INSERT INTO retur_detail'
                    . '(retur_id,barang_id,nama_barang,harga,qty,subtotal) '
                    . 'VALUES(?,?,?,?,?,?)'
                );
                $statement->execute([
                    $returnId,
                    $item['barang_id'],
                    $item['nama_barang'],
                    $item['harga'],
                    $quantity,
                    $item['harga'] * $quantity,
                ]);
            }

            $pdo->commit();
            flash("Pengajuan retur $returnNumber berhasil disimpan, menunggu persetujuan admin.");
        } elseif ($isAdmin) {
            $returnId = (int) $_POST['id'];

            if ($action === 'setujui') {
                // Kunci pengajuan agar stok hanya dikembalikan satu kali.
                $pdo->beginTransaction();
                $statement = $pdo->prepare('SELECT status FROM retur WHERE id=? FOR UPDATE');
                $statement->execute([$returnId]);

                if ($statement->fetchColumn() === 'diajukan') {
                    $details = $pdo->prepare(
                        'SELECT barang_id,qty FROM retur_detail WHERE retur_id=?'
                    );
                    $details->execute([$returnId]);

                    foreach ($details->fetchAll() as $item) {
                        $statement = $pdo->prepare(
                            'UPDATE barang SET stok=stok+? WHERE id=?'
                        );
                        $statement->execute([$item['qty'], $item['barang_id']]);
                    }

                    $statement = $pdo->prepare(
                        "UPDATE retur SET status='disetujui' WHERE id=?"
                    );
                    $statement->execute([$returnId]);
                }

                $pdo->commit();
                flash('Retur disetujui, stok barang dikembalikan.');
            } elseif ($action === 'tolak') {
                $statement = $pdo->prepare(
                    "UPDATE retur SET status='ditolak' WHERE id=? AND status='diajukan'"
                );
                $statement->execute([$returnId]);
                flash('Retur ditolak.');
            } elseif ($action === 'hapus') {
                $statement = $pdo->prepare(
                    "DELETE FROM retur WHERE id=? AND status<>'disetujui'"
                );
                $statement->execute([$returnId]);
                flash('Data retur dihapus (retur disetujui tidak bisa dihapus).');
            }
        }
    } catch (Exception $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        flash($exception->getMessage(), 'err');
    }

    redirect('retur.php');
}

page_header($isAdmin ? 'Data Retur' : 'Pengajuan Retur', 'Retur');

if (!$isAdmin) {
    // Kasir mencari transaksi menggunakan nomor pada struk.
    $transactionNumber = trim($_GET['no'] ?? '');
    $sale = null;

    echo "<div class='card'><form class='row'>";
    echo "<input name='no' placeholder='Cari nomor struk / nomor transaksi' ";
    echo "value='" . e($transactionNumber) . "'>";
    echo "<button class='btn'>Cari</button></form></div>";

    if ($transactionNumber !== '') {
        $statement = $pdo->prepare('SELECT * FROM penjualan WHERE no_transaksi=?');
        $statement->execute([$transactionNumber]);
        $sale = $statement->fetch();

        if (!$sale) {
            echo "<div class='alert err'>Transaksi tidak ditemukan.</div>";
        }
    }

    if ($sale) {
        $statement = $pdo->prepare($itemsQuery);
        $statement->execute([$sale['id']]);
        $saleItems = $statement->fetchAll();

        echo "<div class='card'><b>No. Transaksi:</b> "
            . e($sale['no_transaksi'])
            . ' &nbsp; <b>Tanggal:</b> ' . tgl($sale['tanggal'])
            . ' &nbsp; <b>Total Pembelian:</b> ' . rp($sale['total'])
            . '</div>';

        echo "<form method='post' class='pos'>" . csrf_field();
        echo "<input type='hidden' name='aksi' value='ajukan'>";
        echo "<input type='hidden' name='penjualan_id' value='{$sale['id']}'>";
        echo "<div class='card'><b>Pilih Barang yang Diretur</b>";
        echo "<div class='table-wrap'><table><tr><th></th><th>Nama Barang</th>";
        echo '<th>Harga</th><th>Dibeli</th><th>Bisa Diretur</th>';
        echo '<th>Jumlah Retur</th><th>Subtotal</th></tr>';

        foreach ($saleItems as $item) {
            $disabled = $item['sisa'] < 1 ? 'disabled' : '';

            echo "<tr><td><input type='checkbox' name='pilih[]' ";
            echo "value='{$item['id']}' class='ck' $disabled style='width:auto'></td>";
            echo '<td>' . e($item['nama_barang']) . '</td>';
            echo '<td>' . rp($item['harga']) . '</td>';
            echo '<td>' . $item['qty'] . '</td>';
            echo '<td>' . $item['sisa'] . '</td>';
            echo "<td><input type='number' name='qty[{$item['id']}]' class='jq' ";
            echo "data-h='{$item['harga']}' min='1' max='{$item['sisa']}' ";
            echo "value='0' disabled style='width:70px'></td>";
            echo "<td class='st'>Rp0</td></tr>";
        }

        echo '</table></div><label>Alasan Retur</label>';
        echo "<select name='alasan' required><option value=''>Pilih alasan retur</option>";
        echo '<option>Barang rusak / cacat</option><option>Salah beli</option>';
        echo '<option>Tidak sesuai</option><option>Kadaluarsa</option><option>Lainnya</option>';
        echo '</select><label>Keterangan (opsional)</label>';
        echo "<textarea name='keterangan' rows='3' ";
        echo "placeholder='Tambahkan keterangan jika diperlukan...'></textarea><br>";
        echo "<button class='btn'>💾 Simpan Pengajuan Retur</button> ";
        echo "<a class='btn gray' href='retur.php'>Reset</a></div>";

        echo "<div class='card'><b>Ringkasan Retur</b><br>";
        echo '<small>Total Item</small><div class="big" id="rI">0</div>';
        echo '<small>Total Retur</small><div class="big" id="rQ">0</div>';
        echo '<small>Subtotal</small><div class="big" id="rS">Rp0</div>';
        echo '<label>Diskon (Rp)</label><input type="number" name="diskon" id="rD" min="0" value="0">';
        echo '<small>TOTAL</small><div class="big" id="rT">Rp0</div></div></form>';
        ?>
        <script>
            const formatRupiah = (amount) =>
                'Rp' + Number(amount).toLocaleString('id-ID');

            function updateReturnSummary() {
                let itemCount = 0;
                let quantityTotal = 0;
                let subtotal = 0;

                document.querySelectorAll('tr').forEach((row) => {
                    const checkbox = row.querySelector('.ck');
                    if (!checkbox) return;

                    const quantityInput = row.querySelector('.jq');
                    quantityInput.disabled = !checkbox.checked;

                    if (checkbox.checked) {
                        if (+quantityInput.value < 1) {
                            quantityInput.value = 1;
                        }

                        const quantity = Math.min(
                            +quantityInput.value,
                            +quantityInput.max
                        );
                        quantityInput.value = quantity;
                        itemCount++;
                        quantityTotal += quantity;
                        subtotal += quantity * quantityInput.dataset.h;
                        row.querySelector('.st').textContent =
                            formatRupiah(quantity * quantityInput.dataset.h);
                    } else {
                        quantityInput.value = 0;
                        row.querySelector('.st').textContent = 'Rp0';
                    }
                });

                const discount = Math.min(+document.getElementById('rD').value || 0, subtotal);
                document.getElementById('rI').textContent = itemCount;
                document.getElementById('rQ').textContent = quantityTotal;
                document.getElementById('rS').textContent = formatRupiah(subtotal);
                document.getElementById('rT').textContent =
                    formatRupiah(subtotal - discount);
            }

            document.querySelectorAll('.ck, .jq, #rD').forEach((element) => {
                element.addEventListener('input', updateReturnSummary);
            });
        </script>
        <?php
    }
}

// Admin melihat seluruh retur; kasir hanya melihat pengajuan miliknya.
$whereClause = $isAdmin ? '' : 'WHERE r.user_id=' . (int) $userId;
$rows = $pdo->query(
    "SELECT r.*,p.no_transaksi,u.nama kasir,"
    . "(SELECT GROUP_CONCAT(CONCAT(nama_barang,' x',qty) SEPARATOR ', ') "
    . "FROM retur_detail WHERE retur_id=r.id) items "
    . 'FROM retur r JOIN penjualan p ON p.id=r.penjualan_id '
    . "JOIN users u ON u.id=r.user_id $whereClause ORDER BY r.id DESC"
)->fetchAll();

echo "<div class='card'><b>"
    . ($isAdmin ? 'Daftar Retur' : 'Riwayat Pengajuan Retur')
    . '</b><div class="table-wrap"><table>';
echo '<tr><th>No. Retur</th><th>Tanggal</th><th>Transaksi</th><th>Barang</th>';
echo '<th>Total</th><th>Alasan</th><th>Status</th>';
echo $isAdmin ? '<th>Aksi</th>' : '';
echo '</tr>';

foreach ($rows as $return) {
    echo '<tr><td>' . e($return['no_retur']) . '</td>';
    echo '<td>' . tgl($return['tanggal']) . '</td>';
    echo '<td>' . e($return['no_transaksi']) . '<br><small>'
        . e($return['kasir']) . '</small></td>';
    echo '<td>' . e($return['items']) . '</td>';
    echo '<td>' . rp($return['total']) . '</td>';
    echo '<td>' . e($return['alasan']) . '<br><small>'
        . e($return['keterangan']) . '</small></td>';
    echo "<td><span class='badge b-{$return['status']}'>{$return['status']}</span></td>";

    if ($isAdmin) {
        echo '<td>';

        // Form tindakan dibuat lokal agar setiap tombol membawa ID retur.
        $actionForm = fn($action, $label, $class, $confirmation = '') =>
            "<form method='post' style='display:inline' "
            . ($confirmation ? "data-confirm='$confirmation'" : '')
            . '>' . csrf_field()
            . "<input type='hidden' name='aksi' value='$action'>"
            . "<input type='hidden' name='id' value='{$return['id']}'>"
            . "<button class='btn sm $class'>$label</button></form> ";

        if ($return['status'] === 'diajukan') {
            echo $actionForm(
                'setujui',
                'Setujui',
                'green',
                'Setujui retur? Stok akan bertambah.'
            );
            echo $actionForm('tolak', 'Tolak', 'gray');
        }

        if ($return['status'] !== 'disetujui') {
            echo $actionForm('hapus', 'Hapus', 'red', 'Hapus data retur?');
        }

        echo '</td>';
    }

    echo '</tr>';
}

if (!$rows) {
    echo "<tr><td colspan='8'>Belum ada data retur.</td></tr>";
}

echo '</table></div></div>';
page_footer();
