<?php
// logout.php - Sign out handler
require_once __DIR__ . '/includes/functions.php';

unset($_SESSION['user']);
setFlash('info', 'You have been successfully signed out.');

$redirect = $_GET['redirect'] ?? baseUrl('login.php');
header('Location: ' . $redirect);
exit;
