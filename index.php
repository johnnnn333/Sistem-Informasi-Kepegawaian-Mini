<?php
// index.php
// Entry point utama. Cukup redirect berdasarkan status login.
require_once __DIR__ . '/bootstrap.php';

if (Auth::isLoggedIn()) {
    redirect('/modules/' . $_SESSION['role'] . '/dashboard.php');
} else {
    redirect('/modules/auth/login.php');
}
