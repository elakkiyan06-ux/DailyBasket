<?php
// admin/includes/admin-header.php - Admin Panel Global Header & Navigation
require_once __DIR__ . '/../../includes/admin-auth.php';

$user = currentUser();
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($adminTitle) ? escape($adminTitle) . ' - DailyBasket Admin' : 'DailyBasket Administrator' ?></title>
    
    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Chart.js CDN for visual reports -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= baseUrl('assets/css/style.css') ?>">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?= baseUrl('assets/images/dailybasket-logo.png') ?>">
</head>
<body>

<div class="admin-wrapper">
    <!-- Admin Sidebar Navigation -->
    <aside class="admin-sidebar">
        <a href="<?= baseUrl('admin/index.php') ?>" class="sidebar-brand text-decoration-none d-flex align-items-center gap-2">
            <img src="<?= baseUrl('assets/images/dailybasket-logo.png') ?>" alt="DailyBasket Logo" style="height: 38px; width: auto; object-fit: contain; background: white; border-radius: 6px; padding: 2px;">
            <span>DailyBasket <span class="badge bg-success text-white small fs-6 ms-1">Admin</span></span>
        </a>

        <div class="admin-nav">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'index.php' ? 'active' : '' ?>" href="<?= baseUrl('admin/index.php') ?>">
                        <i class="bi bi-speedometer2"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= in_array($currentPage, ['products.php', 'add-product.php', 'edit-product.php']) ? 'active' : '' ?>" href="<?= baseUrl('admin/products.php') ?>">
                        <i class="bi bi-box-seam"></i>
                        <span>Products</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'categories.php' ? 'active' : '' ?>" href="<?= baseUrl('admin/categories.php') ?>">
                        <i class="bi bi-grid"></i>
                        <span>Categories</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= in_array($currentPage, ['orders.php', 'order-details.php']) ? 'active' : '' ?>" href="<?= baseUrl('admin/orders.php') ?>">
                        <i class="bi bi-receipt"></i>
                        <span>Orders</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'customers.php' ? 'active' : '' ?>" href="<?= baseUrl('admin/customers.php') ?>">
                        <i class="bi bi-people"></i>
                        <span>Customers</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'reports.php' ? 'active' : '' ?>" href="<?= baseUrl('admin/reports.php') ?>">
                        <i class="bi bi-graph-up-arrow"></i>
                        <span>Reports</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'settings.php' ? 'active' : '' ?>" href="<?= baseUrl('admin/settings.php') ?>">
                        <i class="bi bi-gear"></i>
                        <span>Settings</span>
                    </a>
                </li>
            </ul>
        </div>

        <div class="p-3 border-top border-secondary border-opacity-25 mt-auto">
            <div class="d-flex align-items-center justify-content-between">
                <div class="small">
                    <div class="text-white fw-bold text-truncate" style="max-width: 140px;"><?= escape($user['name']) ?></div>
                    <div class="text-muted" style="font-size: 0.75rem;">Administrator</div>
                </div>
                <a href="<?= baseUrl('admin/logout.php') ?>" class="btn btn-sm btn-outline-danger" title="Sign Out">
                    <i class="bi bi-box-arrow-right"></i>
                </a>
            </div>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="admin-main">
        <!-- Topbar -->
        <header class="admin-topbar">
            <div class="d-flex align-items-center gap-3">
                <h5 class="mb-0 fw-bold text-dark"><?= isset($adminTitle) ? escape($adminTitle) : 'Dashboard' ?></h5>
            </div>

            <div class="d-flex align-items-center gap-3">
                <a href="<?= baseUrl('index.php') ?>" target="_blank" class="btn btn-sm btn-outline-success">
                    <i class="bi bi-shop me-1"></i>View Storefront
                </a>
                <div class="dropdown">
                    <button class="btn btn-light btn-sm border dropdown-toggle d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle text-success"></i>
                        <span class="small fw-semibold"><?= escape($user['name']) ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        <li><a class="dropdown-item" href="<?= baseUrl('admin/settings.php') ?>"><i class="bi bi-gear me-2"></i>Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="<?= baseUrl('admin/logout.php') ?>"><i class="bi bi-box-arrow-right me-2"></i>Sign Out</a></li>
                    </ul>
                </div>
            </div>
        </header>

        <!-- Flash Alert Messages inside Admin -->
        <div class="px-4 pt-3">
            <?php foreach (getFlashMessages() as $msg): ?>
                <div class="alert alert-<?= escape($msg['type']) ?> alert-dismissible fade show shadow-sm" role="alert">
                    <?= escape($msg['message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="admin-content">
