<?php require 'config.php'; redirect(empty($_SESSION['u']) ? 'katalog.php' : 'dashboard.php');
