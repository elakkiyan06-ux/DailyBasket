<?php
// admin/logout.php - Administrator Sign out handler
require_once __DIR__ . '/../includes/functions.php';

unset($_SESSION['user']);
setFlash('info', 'Administrator signed out successfully.');
header('Location: ' . baseUrl('admin/login.php'));
exit;
