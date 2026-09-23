<?php
// includes/admin-auth.php - Administrator authentication guard

require_once __DIR__ . '/functions.php';

if (!isAdmin()) {
    setFlash('danger', 'Administrator access required. Please log in with an admin account.');
    header('Location: ' . baseUrl('admin/login.php'));
    exit;
}
