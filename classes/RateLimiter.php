<?php
// classes/RateLimiter.php
// Mencegah brute-force login: kalau user salah password
// berkali-kali, akun dikunci sementara.

class RateLimiter
{
    private mysqli $db;
    private int $maxAttempts = 5;     // maksimal 5x salah
    private int $lockMinutes = 15;    // dikunci 15 menit

    public function __construct(mysqli $db)
    {
        $this->db = $db;
    }

    // Cek apakah username ini sedang dikunci
    public function isLocked(string $username): bool
    {
        $stmt = $this->db->prepare(
            "SELECT locked_until FROM users WHERE username = ? LIMIT 1"
        );
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        if (!$row || empty($row['locked_until'])) {
            return false;
        }

        return strtotime($row['locked_until']) > time();
    }

    // Berapa menit lagi sebelum bisa coba login lagi
    public function getRemainingLockTime(string $username): int
    {
        $stmt = $this->db->prepare(
            "SELECT locked_until FROM users WHERE username = ? LIMIT 1"
        );
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row || empty($row['locked_until'])) {
            return 0;
        }

        $diff = strtotime($row['locked_until']) - time();
        return $diff > 0 ? (int) ceil($diff / 60) : 0;
    }

    // Dipanggil setiap kali login GAGAL
    public function recordFailedAttempt(string $username): void
    {
        $stmt = $this->db->prepare(
            "UPDATE users SET failed_attempts = failed_attempts + 1 WHERE username = ?"
        );
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $stmt->close();

        // Cek apakah sudah mencapai batas maksimal
        $stmt = $this->db->prepare(
            "SELECT failed_attempts FROM users WHERE username = ? LIMIT 1"
        );
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($row && $row['failed_attempts'] >= $this->maxAttempts) {
            $lockedUntil = date('Y-m-d H:i:s', time() + ($this->lockMinutes * 60));
            $stmt = $this->db->prepare(
                "UPDATE users SET locked_until = ? WHERE username = ?"
            );
            $stmt->bind_param('ss', $lockedUntil, $username);
            $stmt->execute();
            $stmt->close();
        }
    }

    // Dipanggil setiap kali login BERHASIL — reset counter
    public function resetAttempts(string $username): void
    {
        $stmt = $this->db->prepare(
            "UPDATE users SET failed_attempts = 0, locked_until = NULL WHERE username = ?"
        );
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $stmt->close();
    }
}