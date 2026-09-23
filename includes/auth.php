<?php
// includes/auth.php - Customer authentication guard

require_once __DIR__ . '/functions.php';

if (!isLoggedIn()) {
    setFlash('warning', 'Please sign in to access this page.');
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header('Location: ' . baseUrl('login.php'));
    exit;
}
