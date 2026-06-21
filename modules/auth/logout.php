<?php
// modules/auth/logout.php
require_once __DIR__ . '/../../bootstrap.php';

if (Auth::isLoggedIn()) {
    logActivity($db, $_SESSION['user_id'], 'Logout');
    $auth = new Auth($db);
    $auth->clearRememberToken($_SESSION['user_id']);
    $auth->logout();
}

redirect('/modules/auth/login.php');
