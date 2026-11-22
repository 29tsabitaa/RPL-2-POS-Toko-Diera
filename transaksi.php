<?php
require 'config.php';
auth(['kasir']);

// Endpoint JSON menyimpan transaksi yang dikirim dari keranjang kasir.
if (isset($_GET['save'])) {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true);

    if (($input['csrf'] ?? '') !== csrf()) {
        echo json_encode(['ok' => false, 'msg' => 'Token tidak valid']);
        exit;
    }

    try {
        $pdo->beginTransaction();
        $items = $input['items'] ?? [];

        if (!$items) {
            throw new Exception('Keranjang masih kosong.');
        }

        // Kunci data barang selama transaksi untuk mencegah stok terjual ganda.
        $subtotal = 0;
        $totalItems = 0;
        $details = [];

        foreach ($items as $item) {
            $statement = $pdo->prepare('SELECT * FROM barang WHERE id=? FOR UPDATE');
            $statement->execute([(int) $item['id']]);
            $product = $statement->fetch();
            $quantity = (int) $item['qty'];

            if (!$product || $quantity < 1) {
                throw new Exception('Data barang tidak valid.');
            }

            if ($product['stok'] < $quantity) {
                throw new Exception(
                    "Stok {$product['nama']} tidak cukup (sisa {$product['stok']})."
                );
            }

            $details[] = [$product, $quantity];
            $subtotal += $product['harga'] * $quantity;
            $totalItems += $quantity;
        }

        $discount = max(0, min((int) ($input['diskon'] ?? 0), $subtotal));
        $total = $subtotal - $discount;
        $payment = (int) ($input['bayar'] ?? 0);

        if ($payment < $total) {
            throw new Exception('Uang bayar kurang dari total.');
        }

        $transactionNumber = 'TRX' . date('ymdHis') . rand(10, 99);
        $statement = $pdo->prepare(
            'INSERT INTO penjualan'
            . '(no_transaksi,user_id,total_item,subtotal,diskon,total,bayar,kembali) '
            . 'VALUES(?,?,?,?,?,?,?,?)'
        );
        $statement->execute([
            $transactionNumber,
            $_SESSION['u']['id'],
            $totalItems,
            $subtotal,
            $discount,
            $total,
            $payment,
            $payment - $total,
        ]);
        $saleId = $pdo->lastInsertId();

        foreach ($details as [$product, $quantity]) {
            $statement = $pdo->prepare(
                'INSERT INTO penjualan_detail'
                . '(penjualan_id,barang_id,nama_barang,harga,qty,subtotal) '
                . 'VALUES(?,?,?,?,?,?)'
            );
            $statement->execute([
                $saleId,
                $product['id'],
                $product['nama'],
                $product['harga'],
                $quantity,
                $product['harga'] * $quantity,
            ]);

            $statement = $pdo->prepare('UPDATE barang SET stok=stok-? WHERE id=?');
            $statement->execute([$quantity, $product['id']]);
        }

        $pdo->commit();
        echo json_encode(['ok' => true, 'id' => $saleId]);
    } catch (Exception $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        echo json_encode(['ok' => false, 'msg' => $exception->getMessage()]);
    }

    exit;
}

$products = $pdo->query(
    'SELECT id,kode,nama,harga,stok FROM barang WHERE stok>0 ORDER BY nama'
)->fetchAll();
page_header('Transaksi Penjualan', 'Transaksi');
?>
<div class="pos">
    <div class="card">
        <input id="cari" placeholder="Scan / input kode atau nama barang lalu Enter" autocomplete="off" autofocus>
        <div id="hasil" style="max-height:140px;overflow:auto"></div>
        <br>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Barang</th>
                        <th>Harga</th>
                        <th>Qty</th>
                        <th>Subtotal</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="cart"></tbody>
            </table>
        </div>
        <p id="kosong" style="color:#64748b">Belum ada barang.</p>
        <button class="btn red sm" onclick="if(confirm('Batalkan transaksi?')){cart=[];draw()}">
            BATAL TRANSAKSI
        </button>
    </div>

    <div class="card">
        <small>Total Item</small>
        <div class="big" id="ti">0</div>
        <small>Subtotal</small>
        <div id="sub" style="font-weight:700">Rp0</div>
        <label>Diskon (Rp)</label>
        <input type="number" id="dis" min="0" value="0">
        <small>TOTAL</small>
        <div class="big" id="tot">Rp0</div>
        <label>Bayar (Rp)</label>
        <input type="number" id="bayar" min="0" value="0">
        <small>Kembali</small>
        <div class="big" style="color:#16a34a" id="kem">Rp0</div>
        <br>
        <button class="btn" style="width:100%" id="btnBayar">BAYAR & CETAK STRUK</button>
    </div>
</div>

<script>
    const DATA = <?=json_encode($products)?>;
    const CSRF = <?=json_encode(csrf())?>;
    let cart = [];

    const getElement = (id) => document.getElementById(id);
    const formatRupiah = (amount) => 'Rp' + Number(amount).toLocaleString('id-ID');

    // Tambahkan produk ke keranjang, selama stok masih mencukupi.
    function add(id) {
        const product = DATA.find((item) => item.id == id);
        if (!product) return;

        const cartItem = cart.find((item) => item.id == id);
        if (cartItem) {
            if (cartItem.qty < product.stok) {
                cartItem.qty++;
            } else {
                alert('Stok maksimal tercapai');
            }
        } else {
            cart.push({
                id: product.id,
                nama: product.nama,
                harga: product.harga,
                qty: 1,
                stok: product.stok,
            });
        }

        getElement('cari').value = '';
        getElement('hasil').innerHTML = '';
        draw();
        getElement('cari').focus();
    }

    function setQty(index, value) {
        const quantity = parseInt(value) || 1;
        cart[index].qty = Math.max(1, Math.min(quantity, cart[index].stok));
        draw();
    }

    // Gambar ulang tabel keranjang dan perbarui ringkasan total.
    function draw() {
        getElement('cart').innerHTML = cart.map((item, index) => `
            <tr>
                <td>${index + 1}</td>
                <td>${item.nama}</td>
                <td>${formatRupiah(item.harga)}</td>
                <td>
                    <div class="qty">
                        <button class="btn gray sm" onclick="setQty(${index},${item.qty - 1})">-</button>
                        <input value="${item.qty}" onchange="setQty(${index},this.value)">
                        <button class="btn gray sm" onclick="setQty(${index},${item.qty + 1})">+</button>
                    </div>
                </td>
                <td>${formatRupiah(item.harga * item.qty)}</td>
                <td><button class="btn red sm" onclick="cart.splice(${index},1);draw()">🗑</button></td>
            </tr>
        `).join('');

        getElement('kosong').style.display = cart.length ? 'none' : 'block';
        calculateTotals();
    }

    function calculateTotals() {
        const subtotal = cart.reduce((sum, item) => sum + item.harga * item.qty, 0);
        const totalItems = cart.reduce((sum, item) => sum + item.qty, 0);
        const discount = Math.min(Math.max(0, +getElement('dis').value || 0), subtotal);
        const total = subtotal - discount;
        const payment = +getElement('bayar').value || 0;

        getElement('ti').textContent = totalItems;
        getElement('sub').textContent = formatRupiah(subtotal);
        getElement('tot').textContent = formatRupiah(total);
        getElement('kem').textContent = formatRupiah(Math.max(0, payment - total));
    }

    getElement('dis').oninput = getElement('bayar').oninput = calculateTotals;

    // Tampilkan hasil pencarian barang yang cocok dengan kode atau nama.
    getElement('cari').addEventListener('input', () => {
        const query = getElement('cari').value.toLowerCase().trim();
        getElement('hasil').innerHTML = query
            ? DATA.filter((item) =>
                item.nama.toLowerCase().includes(query)
                || item.kode.toLowerCase().includes(query)
            )
                .slice(0, 8)
                .map((item) =>
                    `<div style="padding:6px;cursor:pointer;border-bottom:1px solid #eee" `
                    + `onclick="add(${item.id})">${item.kode} — ${item.nama} `
                    + `(${formatRupiah(item.harga)}, stok ${item.stok})</div>`
                )
                .join('')
            : '';
    });

    // Enter memilih kecocokan kode terlebih dahulu, lalu kecocokan nama.
    getElement('cari').addEventListener('keydown', (event) => {
        if (event.key !== 'Enter') return;

        event.preventDefault();
        const query = getElement('cari').value.toLowerCase().trim();
        const product = DATA.find((item) => item.kode.toLowerCase() === query)
            || DATA.find((item) => item.nama.toLowerCase().includes(query));

        if (product) {
            add(product.id);
        } else {
            alert('Barang tidak ditemukan / stok habis');
        }
    });

    // Kirim transaksi yang sudah dihitung server-side untuk disimpan.
    getElement('btnBayar').onclick = async () => {
        if (!cart.length) return alert('Keranjang kosong');

        const response = await fetch('transaksi.php?save=1', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                csrf: CSRF,
                items: cart.map((item) => ({id: item.id, qty: item.qty})),
                diskon: +getElement('dis').value || 0,
                bayar: +getElement('bayar').value || 0,
            }),
        });
        const result = await response.json();

        if (result.ok) {
            location.href = 'struk.php?id=' + result.id + '&baru=1';
        } else {
            alert(result.msg);
        }
    };

    draw();
</script>
<?php page_footer(); ?>
