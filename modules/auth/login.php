<?php
// modules/auth/login.php
require_once __DIR__ . '/../../bootstrap.php';

$auth = new Auth($db);
$rateLimiter = new RateLimiter($db);

// Kalau sudah login via session, langsung ke dashboard
if (Auth::isLoggedIn()) {
    redirect('/modules/' . $_SESSION['role'] . '/dashboard.php');
}

// Kalau belum login tapi ada cookie "remember me" yang valid, auto-login
$rememberedUser = $auth->loginViaRememberToken();
if ($rememberedUser) {
    $auth->login($rememberedUser);
    logActivity($db, $rememberedUser['id'], 'Login otomatis via Remember Me');
    redirect('/modules/' . $rememberedUser['role'] . '/dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!CSRF::validateToken($_POST['csrf_token'] ?? null)) {
        $error = 'Token keamanan tidak valid. Silakan refresh halaman dan coba lagi.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $rememberMe = !empty($_POST['remember_me']);

        if ($username === '' || $password === '') {
            $error = 'Username dan password wajib diisi.';
        } elseif ($rateLimiter->isLocked($username)) {
            $sisaMenit = $rateLimiter->getRemainingLockTime($username);
            $error = "Akun terkunci karena terlalu banyak percobaan gagal. Coba lagi dalam {$sisaMenit} menit.";
        } else {
            $user = $auth->attemptLogin($username, $password);

            if ($user) {
                $rateLimiter->resetAttempts($username);
                $auth->login($user);

                if ($rememberMe) {
                    $auth->rememberMe($user['id']);
                }

                logActivity($db, $user['id'], 'Login berhasil');
                redirect('/modules/' . $user['role'] . '/dashboard.php');
            } else {
                $rateLimiter->recordFailedAttempt($username);
                logActivity($db, null, "Login gagal untuk username: {$username}");
                $error = 'Username atau password salah.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>

<div class="login-wrapper">
    <div class="login-box">
        <h1><?= e(APP_NAME) ?></h1>
        <p class="subtitle">Sistem Informasi Kepegawaian</p>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <?= CSRF::tokenField() ?>

            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required autofocus
                       value="<?= e($_POST['username'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>

            <div class="form-group" style="display:flex; align-items:center; gap:8px;">
                <input type="checkbox" id="remember_me" name="remember_me" style="width:auto;">
                <label for="remember_me" style="margin:0;">Ingat saya selama 30 hari</label>
            </div>

            <button type="submit" class="btn">Masuk</button>
        </form>
    </div>
</div>

</body>
</html>
