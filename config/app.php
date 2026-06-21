<?php
// config/app.php
// Konfigurasi umum aplikasi: nama app, base url, timezone, dll.

define('APP_NAME', 'SIMPEG Mini');
define('APP_VERSION', '1.0.0');

// Sesuaikan dengan folder project di htdocs/www kamu
// Contoh: kalau folder project ada di htdocs/simpeg-mini -> '/simpeg-mini'
define('BASE_URL', '/simpeg-mini');

// Timezone Indonesia
date_default_timezone_set('Asia/Jakarta');

// Mode debug — set false kalau sudah production
define('APP_DEBUG', true);

if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}
