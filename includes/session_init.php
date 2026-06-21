<?php
// includes/session_init.php
// Konfigurasi session yang lebih aman, harus di-include
// SEBELUM session_start() dan sebelum ada output apapun.

// Cegah pemanggilan dobel kalau file ini di-include 2x
if (session_status() === PHP_SESSION_NONE) {

    // Cegah session ID dikirim lewat URL (?PHPSESSID=...)
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_strict_mode', '1');

    // Cookie session tidak bisa diakses lewat JavaScript (anti XSS cookie theft)
    ini_set('session.cookie_httponly', '1');

    // Catatan: 'session.cookie_secure' di-set ke 1 HANYA kalau pakai HTTPS.
    // Kalau development di localhost (HTTP biasa), biarkan 0 dulu,
    // supaya cookie tetap terkirim.
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    ini_set('session.cookie_secure', $isHttps ? '1' : '0');

    // SameSite mencegah cookie dikirim dari request cross-site (anti CSRF tambahan)
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'samesite' => 'Lax',
        'httponly' => true,
        'secure'   => $isHttps,
    ]);

    session_start();

    // Auto logout kalau idle terlalu lama (30 menit)
    $timeoutSeconds = 30 * 60;
    if (!empty($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeoutSeconds) {
        $_SESSION = [];
        session_destroy();
        session_start();
    }
    $_SESSION['last_activity'] = time();
}
