<?php
require 'config.php';
auth(['admin']);

$currentUserId = $_SESSION['u']['id'];

// Proses tindakan admin pada akun pengguna.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['aksi'];
    $userId = (int) ($_POST['id'] ?? 0);

    try {
        if ($action === 'tambah') {
            if (strlen($_POST['password']) < 6) {
                throw new Exception('Password minimal 6 karakter.');
            }

            $statement = $pdo->prepare(
                "INSERT INTO users(nama,username,password,role,status)
                 VALUES(?,?,?,?, 'aktif')"
            );
            $statement->execute([
                trim($_POST['nama']),
                trim($_POST['username']),
                password_hash($_POST['password'], PASSWORD_DEFAULT),
                $_POST['role'],
            ]);
            flash('Pengguna ditambahkan.');
        } elseif ($action === 'aktif' || $action === 'ditolak') {
            $statement = $pdo->prepare('UPDATE users SET status=? WHERE id=?');
            $statement->execute([$action, $userId]);
            flash($action === 'aktif' ? 'Akun divalidasi (aktif).' : 'Akun ditolak.');
        } elseif ($action === 'reset') {
            $passwordHash = password_hash('123456', PASSWORD_DEFAULT);
            $statement = $pdo->prepare('UPDATE users SET password=? WHERE id=?');
            $statement->execute([$passwordHash, $userId]);
            flash('Password direset menjadi 123456.');
        } elseif ($action === 'hapus') {
            if ($userId === $currentUserId) {
                throw new Exception('Tidak bisa menghapus akun sendiri.');
            }

            $statement = $pdo->prepare('DELETE FROM users WHERE id=?');
            $statement->execute([$userId]);
            flash('Pengguna dihapus.');
        }
    } catch (Exception $exception) {
        $message = $exception->getMessage();

        if (str_contains($message, 'Duplicate')) {
            $message = 'Username sudah ada.';
        } elseif (str_contains($message, 'foreign key')) {
            $message = 'Pengguna sudah punya transaksi, tidak bisa dihapus (tolak/nonaktifkan saja).';
        }

        flash($message, 'err');
    }

    redirect('pengguna.php');
}

// Akun pending ditampilkan lebih dahulu agar mudah divalidasi.
$rows = $pdo->query(
    "SELECT * FROM users ORDER BY FIELD(status,'pending','aktif','ditolak'), id DESC"
)->fetchAll();

page_header('Data Pengguna', 'Pengguna');

echo "<div class='card'><b>Tambah Pengguna</b><form method='post'>";
echo csrf_field() . "<input type='hidden' name='aksi' value='tambah'><div class='row'>";
echo "<div><label>Nama</label><input name='nama' required></div>";
echo "<div><label>Username</label><input name='username' required></div>";
echo "<div><label>Password</label><input type='password' name='password' required></div>";
echo "<div><label>Peran</label><select name='role'>";
echo '<option>kasir</option><option>pemilik</option><option>admin</option>';
echo "</select></div><button class='btn'>TAMBAH</button></div></form></div>";

echo "<div class='card'><div class='table-wrap'><table>";
echo '<tr><th>No</th><th>Nama</th><th>Username</th><th>Peran</th><th>Status</th><th>Aksi</th></tr>';

foreach ($rows as $index => $user) {
    echo '<tr><td>' . ($index + 1) . '</td>';
    echo '<td>' . e($user['nama']) . '</td>';
    echo '<td>' . e($user['username']) . '</td>';
    echo '<td>' . ucfirst($user['role']) . '</td>';
    echo "<td><span class='badge b-{$user['status']}'>{$user['status']}</span></td><td>";

    // Tombol-tombol tindakan dibuat sebagai form POST yang menyertakan token CSRF.
    $actionForm = fn($action, $label, $class, $confirmation = '') =>
        "<form method='post' style='display:inline' "
        . ($confirmation ? "data-confirm='$confirmation'" : '')
        . '>' . csrf_field()
        . "<input type='hidden' name='aksi' value='$action'>"
        . "<input type='hidden' name='id' value='{$user['id']}'>"
        . "<button class='btn sm $class'>$label</button></form> ";

    if ($user['status'] !== 'aktif') {
        echo $actionForm('aktif', 'Validasi', 'green');
    }

    if ($user['status'] !== 'ditolak' && $user['id'] != $currentUserId) {
        echo $actionForm('ditolak', 'Tolak', 'gray');
    }

    echo $actionForm('reset', 'Reset PW', 'gray', 'Reset password ke 123456?');

    if ($user['id'] != $currentUserId) {
        echo $actionForm('hapus', 'Hapus', 'red', 'Hapus pengguna ini?');
    }

    echo '</td></tr>';
}

echo '</table></div></div>';
page_footer();
