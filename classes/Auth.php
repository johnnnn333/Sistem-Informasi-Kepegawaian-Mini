<?php
// classes/Auth.php
// Menangani proses login, logout, dan pengecekan akses
// berdasarkan role (admin, manager, karyawan).

class Auth
{
    private mysqli $db;

    public function __construct(mysqli $db)
    {
        $this->db = $db;
    }

    /**
     * Cek username + password.
     * Return array data user kalau cocok, atau null kalau gagal.
     */
    public function attemptLogin(string $username, string $password): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT id, username, password, nama_lengkap, role
             FROM users WHERE username = ? LIMIT 1"
        );
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if (!$user) {
            return null;
        }

        // Verifikasi password dengan hash bcrypt
        if (!password_verify($password, $user['password'])) {
            return null;
        }

        // Jangan kembalikan hash password ke session
        unset($user['password']);
        return $user;
    }

    /**
     * Simpan data user ke session setelah login berhasil.
     * regenerate_id() dipanggil supaya session ID baru —
     * mencegah session fixation attack.
     */
    public function login(array $user): void
    {
        session_regenerate_id(true);

        $_SESSION['user_id']      = $user['id'];
        $_SESSION['username']     = $user['username'];
        $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
        $_SESSION['role']         = $user['role'];
        $_SESSION['login_time']   = time();
    }

    /**
     * Hapus semua data session (logout).
     */
    public function logout(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
    }

    public static function isLoggedIn(): bool
    {
        return !empty($_SESSION['user_id']);
    }

    /**
     * Buat token "Remember Me", simpan hash-nya ke DB,
     * dan kirim token asli ke cookie browser.
     * Hanya hash yang disimpan di DB — kalau DB bocor,
     * token asli tetap tidak diketahui (sama prinsip seperti password).
     */
    public function rememberMe(int $userId): void
    {
        $token = bin2hex(random_bytes(32));
        $hash = hash('sha256', $token);
        $expires = date('Y-m-d H:i:s', time() + (30 * 24 * 60 * 60)); // 30 hari

        $stmt = $this->db->prepare(
            "UPDATE users SET remember_token = ?, remember_expires = ? WHERE id = ?"
        );
        $stmt->bind_param('ssi', $hash, $expires, $userId);
        $stmt->execute();
        $stmt->close();

        setcookie('remember_token', $userId . ':' . $token, [
            'expires'  => time() + (30 * 24 * 60 * 60),
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    /**
     * Cek cookie "remember_token" di browser.
     * Kalau valid, kembalikan data user dan otomatis login.
     */
    public function loginViaRememberToken(): ?array
    {
        if (empty($_COOKIE['remember_token'])) {
            return null;
        }

        [$userId, $token] = array_pad(explode(':', $_COOKIE['remember_token'], 2), 2, null);
        if (!$userId || !$token) {
            return null;
        }

        $hash = hash('sha256', $token);

        $stmt = $this->db->prepare(
            "SELECT id, username, nama_lengkap, role
             FROM users
             WHERE id = ? AND remember_token = ? AND remember_expires > NOW()
             LIMIT 1"
        );
        $stmt->bind_param('is', $userId, $hash);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $user ?: null;
    }

    /** Hapus remember token (dipanggil saat logout) */
    public function clearRememberToken(int $userId): void
    {
        $stmt = $this->db->prepare(
            "UPDATE users SET remember_token = NULL, remember_expires = NULL WHERE id = ?"
        );
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->close();

        setcookie('remember_token', '', [
            'expires'  => time() - 3600,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    public static function getRole(): ?string
    {
        return $_SESSION['role'] ?? null;
    }

    public static function hasRole(string ...$roles): bool
    {
        return in_array(self::getRole(), $roles, true);
    }

    /**
     * Panggil di awal halaman yang butuh login.
     * Kalau belum login, redirect ke halaman login.
     */
    public static function requireLogin(): void
    {
        if (!self::isLoggedIn()) {
            header('Location: ' . BASE_URL . '/modules/auth/login.php');
            exit;
        }
    }

    /**
     * Panggil di awal halaman yang butuh role tertentu.
     * Contoh: Auth::requireRole('admin', 'manager');
     */
    public static function requireRole(string ...$roles): void
    {
        self::requireLogin();
        if (!self::hasRole(...$roles)) {
            http_response_code(403);
            die('Akses ditolak. Anda tidak memiliki izin untuk halaman ini.');
        }
    }
}
