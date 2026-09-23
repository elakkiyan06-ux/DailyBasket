<?php
// orders.php - Customer Order History Page
$pageTitle = "My Orders";
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/header.php';

$user = currentUser();
$db = getDB();

// Fetch customer's orders
$stmt = $db->prepare("
    SELECT o.*, COUNT(oi.id) as item_count 
    FROM orders o 
    LEFT JOIN order_items oi ON o.id = oi.order_id 
    WHERE o.user_id = ? 
    GROUP BY o.id 
    ORDER BY o.created_at DESC
");
$stmt->execute([$user['id']]);
$orders = $stmt->fetchAll();
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="<?= baseUrl('index.php') ?>">Home</a></li>
            <li class="breadcrumb-item active" aria-current="page">My Orders</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <h1 class="h2 mb-1">My Orders</h1>
            <p class="text-muted small mb-0">Track and review all your previous grocery purchases</p>
        </div>
        <a href="<?= baseUrl('shop.php') ?>" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-cart-plus me-1"></i>New Order
        </a>
    </div>

    <?php if (empty($orders)): ?>
        <div class="fc-empty-state bg-white border rounded-3 shadow-sm my-4 py-5">
            <i class="bi bi-bag-x"></i>
            <h3 class="fw-bold mb-2">No Orders Placed Yet</h3>
            <p class="text-muted max-w-500 mx-auto mb-4">
                You have not placed any grocery orders yet. Fresh fruits, vegetables, dairy, and everyday staples are waiting for you!
            </p>
            <a href="<?= baseUrl('shop.php') ?>" class="btn btn-primary px-4 shadow-sm">
                <i class="bi bi-basket me-2"></i>Shop Groceries
            </a>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($orders as $order): ?>
                <div class="col-12">
                    <div class="card border rounded-3 p-3 p-md-4 bg-white shadow-sm fc-card">
                        <div class="row align-items-center g-3">
                            <div class="col-md-3">
                                <span class="text-muted small d-block">Order Reference</span>
                                <h5 class="fw-bold mb-1 text-dark">#FC<?= escape($order['id']) ?></h5>
                                <span class="small text-muted">
                                    <i class="bi bi-calendar3 me-1"></i><?= date('d M Y, h:i A', strtotime($order['created_at'])) ?>
                                </span>
                            </div>

                            <div class="col-md-3">
                                <span class="text-muted small d-block">Delivery To</span>
                                <span class="fw-semibold text-dark small d-block text-truncate"><?= escape($order['delivery_name']) ?></span>
                                <span class="text-muted small text-truncate d-block"><?= escape($order['delivery_city']) ?> - <?= escape($order['delivery_pincode']) ?></span>
                            </div>

                            <div class="col-md-2">
                                <span class="text-muted small d-block">Total Amount</span>
                                <span class="fw-bold text-success fs-5"><?= formatPrice($order['total_amount']) ?></span>
                                <small class="text-muted d-block"><?= (int)$order['item_count'] ?> items</small>
                            </div>

                            <div class="col-md-2">
                                <span class="text-muted small d-block mb-1">Status</span>
                                <div><?= getStatusBadge($order['status']) ?></div>
                            </div>

                            <div class="col-md-2 text-md-end">
                                <a href="<?= baseUrl('order-details.php?id=' . $order['id']) ?>" class="btn btn-sm btn-outline-primary w-100 w-md-auto">
                                    <i class="bi bi-eye me-1"></i>View Details
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
