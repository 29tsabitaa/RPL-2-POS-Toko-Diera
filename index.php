<?php
require 'config.php';

// Arahkan pengguna ke halaman awal sesuai status login.
if (empty($_SESSION['u'])) {
    redirect('katalog.php');
}

redirect('dashboard.php');
