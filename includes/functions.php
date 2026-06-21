<?php
// includes/functions.php
// Kumpulan fungsi helper yang dipakai berulang di berbagai halaman.

/**
 * Bersihkan output supaya aman dari XSS saat ditampilkan di HTML.
 * Selalu pakai ini saat mencetak data yang berasal dari user/database.
 */
function e(?string $text): string
{
    return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect ke URL lain lalu hentikan eksekusi script.
 */
function redirect(string $path): void
{
    header('Location: ' . BASE_URL . $path);
    exit;
}

/**
 * Simpan pesan flash (notifikasi sekali tampil) ke session.
 * Dipakai misal setelah submit form: "Data berhasil disimpan".
 */
function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Ambil pesan flash lalu hapus dari session (sekali pakai).
 */
function getFlash(): ?array
{
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Format tanggal ke format Indonesia: 21 Juni 2026
 */
function formatTanggalIndo(?string $date): string
{
    if (empty($date)) {
        return '-';
    }
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    $timestamp = strtotime($date);
    return date('d', $timestamp) . ' ' . $bulan[(int) date('n', $timestamp)] . ' ' . date('Y', $timestamp);
}

/**
 * Catat aktivitas user ke tabel activity_log (audit trail).
 */
function logActivity(mysqli $db, ?int $userId, string $aktivitas): void
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $stmt = $db->prepare(
        "INSERT INTO activity_log (user_id, aktivitas, ip_address) VALUES (?, ?, ?)"
    );
    $stmt->bind_param('iss', $userId, $aktivitas, $ip);
    $stmt->execute();
    $stmt->close();
}
