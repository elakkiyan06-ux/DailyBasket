<?php
// order-details.php - Order Details and Live Tracking Page
$orderId = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
$pageTitle = "Order Tracking #FC" . $orderId;
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/header.php';

$user = currentUser();
$db = getDB();

// Fetch order
$stmt = $db->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->execute([$orderId, $user['id']]);
$order = $stmt->fetch();

if (!$order) {
    echo '<div class="container py-5 text-center">';
    echo '<div class="fc-empty-state bg-white border rounded-3 p-5 my-4">';
    echo '<i class="bi bi-exclamation-triangle text-danger fs-1 mb-3"></i>';
    echo '<h3>Order Not Found</h3>';
    echo '<p class="text-muted">The requested order does not exist or you do not have permission to view it.</p>';
    echo '<a href="' . baseUrl('orders.php') . '" class="btn btn-primary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back to My Orders</a>';
    echo '</div></div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// Fetch order items with historical snapshots
$itemsStmt = $db->prepare("
    SELECT oi.*, p.image, p.unit 
    FROM order_items oi 
    LEFT JOIN products p ON oi.product_id = p.id 
    WHERE oi.order_id = ?
");
$itemsStmt->execute([$orderId]);
$orderItems = $itemsStmt->fetchAll();

// 5-Stage Tracker States
$stages = ['Pending', 'Confirmed', 'Preparing', 'Ready', 'Delivered'];
$currentStatus = $order['status'];
$isCancelled = ($currentStatus === 'Cancelled');

$currentStageIndex = array_search($currentStatus, $stages);
if ($currentStageIndex === false) {
    $currentStageIndex = 0;
}

$progressPercentage = $isCancelled ? 0 : ($currentStageIndex / (count($stages) - 1)) * 100;
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="<?= baseUrl('index.php') ?>">Home</a></li>
            <li class="breadcrumb-item"><a href="<?= baseUrl('orders.php') ?>">My Orders</a></li>
            <li class="breadcrumb-item active" aria-current="page">Order #FC<?= escape($order['id']) ?></li>
        </ol>
    </nav>

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-2 border-bottom gap-3">
        <div>
            <h1 class="h2 mb-1">Order #FC<?= escape($order['id']) ?></h1>
            <p class="text-muted small mb-0">
                Placed on <?= date('l, d F Y \a\t h:i A', strtotime($order['created_at'])) ?>
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="text-muted small">Current Status:</span>
            <?= getStatusBadge($order['status']) ?>
        </div>
    </div>

    <!-- Visual Order Tracker -->
    <div class="card border rounded-3 p-4 bg-white shadow-sm mb-4">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h5 class="fw-bold mb-0"><i class="bi bi-truck text-success me-2"></i>Live Order Progress</h5>
            <small class="text-muted"><i class="bi bi-arrow-repeat me-1"></i>Reflects live store status updates</small>
        </div>

        <?php if ($isCancelled): ?>
            <div class="alert alert-danger d-flex align-items-center gap-3 my-4" role="alert">
                <i class="bi bi-x-octagon-fill fs-2"></i>
                <div>
                    <h6 class="fw-bold mb-1">This Order Has Been Cancelled</h6>
                    <p class="mb-0 small">This order was cancelled. If you have any inquiries, please contact our store support.</p>
                </div>
            </div>
        <?php else: ?>
            <div class="fc-tracker">
                <div class="fc-tracker-progress" style="width: <?= $progressPercentage ?>%;"></div>
                
                <?php 
                $stageIcons = [
                    'Pending'   => 'bi-clock-history',
                    'Confirmed' => 'bi-check2-circle',
                    'Preparing' => 'bi-box-seam',
                    'Ready'     => 'bi-bag-check',
                    'Delivered' => 'bi-house-door-fill',
                ];

                foreach ($stages as $idx => $stage): 
                    $isCompleted = ($idx < $currentStageIndex);
                    $isActive    = ($idx === $currentStageIndex);
                    $stepClass   = $isCompleted ? 'completed' : ($isActive ? 'active' : '');
                ?>
                    <div class="fc-tracker-step <?= $stepClass ?>">
                        <div class="fc-tracker-icon">
                            <i class="bi <?= $stageIcons[$stage] ?>"></i>
                        </div>
                        <div class="fc-tracker-title"><?= $stage ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="row g-4">
        <!-- Order Items List -->
        <div class="col-lg-8">
            <div class="card border rounded-3 bg-white shadow-sm overflow-hidden mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="fw-bold mb-0"><i class="bi bi-basket me-2 text-success"></i>Purchased Groceries (<?= count($orderItems) ?> items)</h6>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light small text-uppercase">
                            <tr>
                                <th style="min-width: 200px;">Product Name</th>
                                <th>Unit Price</th>
                                <th>Quantity</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $itemsSum = 0;
                            foreach ($orderItems as $item): 
                                $itemsSum += (float)$item['subtotal'];
                            ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            <img src="<?= getProductImageUrl($item['image'] ?? null) ?>" alt="<?= escape($item['product_name']) ?>" class="rounded border" style="width: 48px; height: 48px; object-fit: cover;">
                                            <div>
                                                <div class="fw-bold text-dark"><?= escape($item['product_name']) ?></div>
                                                <small class="text-muted"><?= escape($item['unit'] ?? 'unit') ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?= formatPrice($item['price']) ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= (int)$item['quantity'] ?></span></td>
                                    <td class="text-end fw-bold text-dark"><?= formatPrice($item['subtotal']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <a href="<?= baseUrl('orders.php') ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Back to All Orders
            </a>
        </div>

        <!-- Delivery & Price Breakdown Summary -->
        <div class="col-lg-4">
            <!-- Delivery Info Card -->
            <div class="card border rounded-3 p-3 bg-white shadow-sm mb-4">
                <h6 class="fw-bold pb-2 border-bottom mb-3"><i class="bi bi-geo-alt-fill text-success me-2"></i>Delivery Address</h6>
                <div class="small">
                    <strong class="d-block mb-1 text-dark fs-6"><?= escape($order['delivery_name']) ?></strong>
                    <div class="text-secondary mb-2">
                        <i class="bi bi-telephone me-1"></i><?= escape($order['delivery_phone']) ?>
                    </div>
                    <p class="text-muted mb-0">
                        <?= nl2br(escape($order['delivery_address'])) ?><br>
                        <?= escape($order['delivery_city']) ?> - <?= escape($order['delivery_pincode']) ?>
                    </p>
                </div>
            </div>

            <!-- Cost Summary -->
            <div class="fc-summary-box">
                <h6 class="fw-bold pb-2 border-bottom mb-3"><i class="bi bi-receipt text-success me-2"></i>Bill Details</h6>

                <div class="fc-summary-item">
                    <span>Items Subtotal</span>
                    <span class="fw-semibold text-dark"><?= formatPrice($itemsSum) ?></span>
                </div>

                <div class="fc-summary-item">
                    <span>Delivery Fee</span>
                    <?php if ($order['delivery_charge'] == 0): ?>
                        <span class="text-success fw-bold">FREE</span>
                    <?php else: ?>
                        <span class="fw-semibold text-dark"><?= formatPrice($order['delivery_charge']) ?></span>
                    <?php endif; ?>
                </div>

                <div class="fc-summary-total">
                    <span>Total Amount</span>
                    <span class="text-success"><?= formatPrice($order['total_amount']) ?></span>
                </div>

                <div class="mt-3 p-2 bg-light rounded text-center small text-muted">
                    <i class="bi bi-cash me-1"></i>Payment Mode: Cash / UPI on Delivery
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
