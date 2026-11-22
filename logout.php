<?php
require 'config.php';

// Hapus sesi lama lalu siapkan pesan untuk halaman katalog.
$_SESSION = [];
session_destroy();

session_start();
flash('Anda berhasil logout.');
redirect('katalog.php');
