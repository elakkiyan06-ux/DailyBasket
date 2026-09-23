<?php
// order-success.php - Order Confirmation Screen
$pageTitle = "Order Confirmed";
require_once __DIR__ . '/includes/auth.php';

$orderId = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
$user = currentUser();
$db = getDB();

$stmt = $db->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->execute([$orderId, $user['id']]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: ' . baseUrl('orders.php'));
    exit;
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7 text-center">
            <div class="card border rounded-3 p-4 p-md-5 bg-white shadow-sm">
                <!-- Success Animated/Styled Icon -->
                <div class="rounded-circle bg-success-subtle text-success mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                    <i class="bi bi-check-circle-fill" style="font-size: 3rem;"></i>
                </div>

                <h1 class="h2 fw-bold text-success mb-2">Order Placed Successfully!</h1>
                <p class="text-muted mb-4">Thank you for your order. We have received it and are preparing your fresh groceries.</p>

                <!-- Order Receipt Card Details -->
                <div class="p-3 bg-light rounded-3 border text-start mb-4">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <span class="text-muted small d-block">Order Reference:</span>
                            <strong class="fs-6 text-dark">#FC<?= escape($order['id']) ?></strong>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted small d-block">Order Date:</span>
                            <strong class="text-dark"><?= date('d F Y, h:i A', strtotime($order['created_at'])) ?></strong>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted small d-block">Order Status:</span>
                            <div><?= getStatusBadge($order['status']) ?></div>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted small d-block">Total Paid / Payable:</span>
                            <strong class="text-success fs-5"><?= formatPrice($order['total_amount']) ?></strong>
                        </div>
                        <div class="col-12 border-top pt-2 mt-2">
                            <span class="text-muted small d-block">Delivery Address:</span>
                            <div class="small text-dark">
                                <strong><?= escape($order['delivery_name']) ?></strong> (<?= escape($order['delivery_phone']) ?>)<br>
                                <?= nl2br(escape($order['delivery_address'])) ?>, <?= escape($order['delivery_city']) ?> - <?= escape($order['delivery_pincode']) ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Navigation Buttons -->
                <div class="d-flex flex-wrap justify-content-center gap-3">
                    <a href="<?= baseUrl('order-details.php?id=' . $order['id']) ?>" class="btn btn-primary px-4">
                        <i class="bi bi-geo-alt me-1"></i>Track Order
                    </a>
                    <a href="<?= baseUrl('orders.php') ?>" class="btn btn-outline-secondary px-4">
                        <i class="bi bi-bag-check me-1"></i>View My Orders
                    </a>
                    <a href="<?= baseUrl('shop.php') ?>" class="btn btn-outline-primary px-4">
                        <i class="bi bi-arrow-right me-1"></i>Continue Shopping
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
