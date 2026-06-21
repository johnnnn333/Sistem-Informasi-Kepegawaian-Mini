<?php
// bootstrap.php
// File ini di-include di paling atas SETIAP halaman.
// Tugasnya: load semua config, koneksi DB, class, dan session
// dengan urutan yang benar.

// 1. Session HARUS di-init paling pertama, sebelum ada output apapun
require_once __DIR__ . '/includes/session_init.php';

// 2. Konfigurasi aplikasi (konstanta, timezone, error reporting)
require_once __DIR__ . '/config/app.php';

// 3. Koneksi database — menghasilkan variabel $koneksi
require_once __DIR__ . '/config/database.php';

// 4. Class-class inti
require_once __DIR__ . '/classes/Auth.php';
require_once __DIR__ . '/classes/CSRF.php';
require_once __DIR__ . '/classes/RateLimiter.php';
require_once __DIR__ . '/classes/Pegawai.php';
require_once __DIR__ . '/classes/Absensi.php';

// 5. Helper functions
require_once __DIR__ . '/includes/functions.php';

// Alias supaya konsisten dipakai sebagai $db di file lain
$db = $koneksi;
