<?php
// includes/header.php - Global Customer Header for DailyBasket
require_once __DIR__ . '/functions.php';

$cartCount = getCartCount();
$user = currentUser();
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? escape($pageTitle) . ' - DailyBasket' : 'DailyBasket – Daily Essentials, Delivered Fresh' ?></title>
    
    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    
    <!-- Custom Theme CSS -->
    <link rel="stylesheet" href="<?= baseUrl('assets/css/style.css') ?>">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?= baseUrl('assets/images/dailybasket-logo.png') ?>">
</head>
<body>

<!-- Navigation Bar -->
<nav class="navbar navbar-expand-lg fc-navbar sticky-top">
    <div class="container">
        <!-- Logo with Uploaded DailyBasket Branding -->
        <a class="navbar-brand fc-brand d-flex align-items-center gap-2 py-0" href="<?= baseUrl('index.php') ?>">
            <img src="<?= baseUrl('assets/images/dailybasket-logo.png') ?>" alt="DailyBasket" style="height: 52px; width: auto; object-fit: contain;">
        </a>

        <!-- Mobile Toggler -->
        <div class="d-flex align-items-center gap-2 d-lg-none">
            <a href="<?= baseUrl('cart.php') ?>" class="fc-cart-btn btn btn-sm">
                <i class="bi bi-cart3"></i>
                <?php if ($cartCount > 0): ?>
                    <span class="fc-badge"><?= $cartCount ?></span>
                <?php endif; ?>
            </a>
            <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#fcNavbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
        </div>

        <div class="collapse navbar-collapse" id="fcNavbarNav">
            <!-- Navigation Links -->
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-3">
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'index.php' ? 'active' : '' ?>" href="<?= baseUrl('index.php') ?>">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'shop.php' ? 'active' : '' ?>" href="<?= baseUrl('shop.php') ?>">Shop Groceries</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'shop.php' && isset($_GET['category']) ? 'active' : '' ?>" href="<?= baseUrl('shop.php') ?>">Categories</a>
                </li>
                <?php if (isLoggedIn()): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'orders.php' || $currentPage === 'order-details.php' ? 'active' : '' ?>" href="<?= baseUrl('orders.php') ?>">My Orders</a>
                    </li>
                <?php endif; ?>
            </ul>

            <!-- Search Form -->
            <form class="d-flex me-lg-3 my-2 my-lg-0" action="<?= baseUrl('shop.php') ?>" method="GET" style="max-width: 320px; width: 100%;">
                <div class="input-group">
                    <input type="text" name="search" class="form-control form-control-sm border-end-0 bg-light" placeholder="Search groceries, milk, rice..." value="<?= escape($_GET['search'] ?? '') ?>">
                    <button class="btn btn-sm btn-outline-secondary border-start-0 bg-light" type="submit">
                        <i class="bi bi-search text-muted"></i>
                    </button>
                </div>
            </form>

            <!-- Right Actions: Cart & Account -->
            <div class="d-flex align-items-center gap-2">
                <!-- Cart Icon Button (Desktop) -->
                <a href="<?= baseUrl('cart.php') ?>" class="fc-cart-btn btn d-none d-lg-inline-flex align-items-center gap-2">
                    <i class="bi bi-cart3 fs-5"></i>
                    <span>Cart</span>
                    <?php if ($cartCount > 0): ?>
                        <span class="badge bg-danger rounded-pill"><?= $cartCount ?></span>
                    <?php endif; ?>
                </a>

                <?php if (isLoggedIn()): ?>
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary btn-sm dropdown-toggle d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle fs-5 text-success"></i>
                            <span class="fw-semibold"><?= escape(explode(' ', $user['name'])[0]) ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                            <li><h6 class="dropdown-header">Signed in as <strong><?= escape($user['email']) ?></strong></h6></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?= baseUrl('profile.php') ?>"><i class="bi bi-person me-2"></i>My Profile</a></li>
                            <li><a class="dropdown-item" href="<?= baseUrl('orders.php') ?>"><i class="bi bi-bag-check me-2"></i>My Orders</a></li>
                            <?php if (isAdmin()): ?>
                                <li><a class="dropdown-item text-success fw-bold" href="<?= baseUrl('admin/index.php') ?>"><i class="bi bi-speedometer2 me-2"></i>Admin Dashboard</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?= baseUrl('logout.php') ?>"><i class="bi bi-box-arrow-right me-2"></i>Sign Out</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="<?= baseUrl('login.php') ?>" class="btn btn-sm btn-outline-primary">Sign In</a>
                    <a href="<?= baseUrl('register.php') ?>" class="btn btn-sm btn-primary">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<!-- Flash Alerts Container -->
<div class="container mt-3">
    <?php foreach (getFlashMessages() as $msg): ?>
        <div class="alert alert-<?= escape($msg['type']) ?> alert-dismissible fade show shadow-sm" role="alert">
            <?= escape($msg['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endforeach; ?>
</div>

<main>
