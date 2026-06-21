<?php
// config/database.php — koneksi ke MySQL
$host   = 'localhost';
$dbuser = 'root';
$dbpass = '';
$dbname = 'simpeg_mini';

$koneksi = new mysqli($host, $dbuser, $dbpass, $dbname);

if ($koneksi->connect_error) {
    die('Koneksi database gagal: ' . $koneksi->connect_error);
}

$koneksi->set_charset('utf8mb4');