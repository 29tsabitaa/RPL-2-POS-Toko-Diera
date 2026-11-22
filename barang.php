<?php
require 'config.php';
auth(['admin', 'kasir']);

$isAdmin = $_SESSION['u']['role'] === 'admin';

// Hanya admin yang dapat menambah, mengubah, dan menghapus barang.
if ($isAdmin && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['aksi'] ?? '';

    try {
        if ($action === 'simpan') {
            $data = [
                trim($_POST['kode']),
                trim($_POST['nama']),
                trim($_POST['kategori']),
                max(0, (int) $_POST['harga']),
                max(0, (int) $_POST['stok']),
            ];

            if (!$data[0] || !$data[1] || !$data[2]) {
                throw new Exception('Semua field wajib diisi.');
            }

            if (!empty($_POST['id'])) {
                $data[] = (int) $_POST['id'];
                $statement = $pdo->prepare(
                    'UPDATE barang SET kode=?,nama=?,kategori=?,harga=?,stok=? WHERE id=?'
                );
                $statement->execute($data);
                flash('Barang diperbarui.');
            } else {
                $statement = $pdo->prepare(
                    'INSERT INTO barang(kode,nama,kategori,harga,stok) VALUES(?,?,?,?,?)'
                );
                $statement->execute($data);
                flash('Barang ditambahkan.');
            }
        } elseif ($action === 'hapus') {
            $statement = $pdo->prepare('DELETE FROM barang WHERE id=?');
            $statement->execute([(int) $_POST['id']]);
            flash('Barang dihapus.');
        }
    } catch (Exception $exception) {
        $message = $exception->getMessage();

        if (str_contains($message, 'Duplicate')) {
            $message = 'Kode barang sudah ada.';
        } elseif (str_contains($message, 'foreign key')) {
            $message = 'Barang sudah dipakai di transaksi, tidak bisa dihapus.';
        }

        flash($message, 'err');
    }

    redirect('barang.php');
}

// Muat data barang yang akan diedit, jika parameter edit tersedia.
$edit = null;
if ($isAdmin && isset($_GET['edit'])) {
    $statement = $pdo->prepare('SELECT * FROM barang WHERE id=?');
    $statement->execute([(int) $_GET['edit']]);
    $edit = $statement->fetch();
}

// Pencarian mencocokkan nama, kode, dan kategori.
$search = trim($_GET['q'] ?? '');
$statement = $pdo->prepare(
    'SELECT * FROM barang '
    . 'WHERE nama LIKE ? OR kode LIKE ? OR kategori LIKE ? '
    . 'ORDER BY id DESC'
);
$statement->execute(["%$search%", "%$search%", "%$search%"]);
$rows = $statement->fetchAll();

page_header($isAdmin ? 'Data Barang' : 'Daftar Barang', 'Data Barang');

if ($isAdmin) {
    $nextCode = $edit['kode'] ?? 'BRG-' . str_pad(
        (int) $pdo->query('SELECT COALESCE(MAX(id),0)+1 FROM barang')->fetchColumn(),
        4,
        '0',
        STR_PAD_LEFT
    );

    echo "<div class='card'><b>" . ($edit ? 'Edit' : 'Tambah') . " Barang</b>";
    echo "<form method='post'>" . csrf_field();
    echo "<input type='hidden' name='aksi' value='simpan'>";
    echo "<input type='hidden' name='id' value='" . e($edit['id'] ?? '') . "'>";
    echo "<div class='row'>";
    echo "<div><label>Kode</label><input name='kode' required value='" . e($nextCode) . "'></div>";
    echo "<div><label>Nama Barang</label><input name='nama' required value='"
        . e($edit['nama'] ?? '') . "'></div>";
    echo "<div><label>Kategori</label><input name='kategori' required value='"
        . e($edit['kategori'] ?? '') . "'></div>";
    echo "<div><label>Harga (Rp)</label><input type='number' min='0' name='harga' required value='"
        . e($edit['harga'] ?? '') . "'></div>";
    echo "<div><label>Stok</label><input type='number' min='0' name='stok' required value='"
        . e($edit['stok'] ?? '') . "'></div>";
    echo "<button class='btn'>SIMPAN</button>";

    if ($edit) {
        echo "<a class='btn gray' href='barang.php'>BATAL</a>";
    }

    echo '</div></form></div>';
}

// Tabel barang dipakai oleh admin dan kasir, tetapi aksi hanya untuk admin.
echo "<div class='card'><form class='row'>";
echo "<input name='q' placeholder='Cari kode / nama / kategori' value='" . e($search) . "'>";
echo "<button class='btn'>Cari</button></form><br><div class='table-wrap'><table>";
echo '<tr><th>No</th><th>Kode</th><th>Nama</th><th>Kategori</th><th>Harga</th><th>Stok</th>';
echo $isAdmin ? '<th>Aksi</th>' : '';
echo '</tr>';

foreach ($rows as $index => $item) {
    echo '<tr><td>' . ($index + 1) . '</td>';
    echo '<td>' . e($item['kode']) . '</td>';
    echo '<td>' . e($item['nama']) . '</td>';
    echo '<td>' . e($item['kategori']) . '</td>';
    echo '<td>' . rp($item['harga']) . '</td>';
    echo '<td>' . ($item['stok'] <= 10
        ? "<b style='color:#dc2626'>{$item['stok']}</b>"
        : $item['stok']) . '</td>';

    if ($isAdmin) {
        echo "<td><a class='btn sm' href='?edit={$item['id']}'>Edit</a> ";
        echo "<form method='post' style='display:inline' data-confirm='Hapus barang ini?'>";
        echo csrf_field();
        echo "<input type='hidden' name='aksi' value='hapus'>";
        echo "<input type='hidden' name='id' value='{$item['id']}'>";
        echo "<button class='btn sm red'>Hapus</button></form></td>";
    }

    echo '</tr>';
}

if (!$rows) {
    echo "<tr><td colspan='7'>Tidak ada data.</td></tr>";
}

echo '</table></div></div>';
page_footer();
