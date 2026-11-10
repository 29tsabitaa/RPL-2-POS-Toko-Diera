<?php
require 'config.php';
$_SESSION=[]; session_destroy();
session_start(); flash('Anda berhasil logout.'); redirect('login.php');
