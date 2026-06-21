<?php
// classes/CSRF.php
// CSRF = Cross-Site Request Forgery.
// Tujuannya: mencegah orang lain ngirim form POST atas nama
// user yang sedang login, dari website lain.
//
// Caranya: setiap form dapat "token rahasia" unik per session.
// Saat form di-submit, token itu dicocokkan. Kalau gak cocok
// (atau gak ada), request ditolak.

class CSRF
{
    // Bikin token baru, simpan di session, kembalikan nilainya
    public static function generateToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    // Cocokkan token yang dikirim form dengan yang di session
    public static function validateToken(?string $token): bool
    {
        if (empty($token) || empty($_SESSION['csrf_token'])) {
            return false;
        }
        // hash_equals mencegah "timing attack" dibanding pakai ==
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    // Helper buat dipanggil langsung di form, contoh: echo CSRF::tokenField();
    public static function tokenField(): string
    {
        $token = self::generateToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
    }
}