<?php
// admin/order-details.php - Administrator Order Processing & Status Updater
$orderId = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
$adminTitle = "Manage Order #FC" . $orderId;
require_once __DIR__ . '/includes/admin-header.php';

$db = getDB();

// Handle Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $newStatus = trim($_POST['status'] ?? '');
    $validStatuses = ['Pending', 'Confirmed', 'Preparing', 'Ready', 'Delivered', 'Cancelled'];

    if (in_array($newStatus, $validStatuses)) {
        $upd = $db->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
        $upd->execute([$newStatus, $orderId]);
        setFlash('success', "Order #FC{$orderId} status successfully updated to '{$newStatus}'.");
        header('Location: ' . baseUrl('admin/order-details.php?id=' . $orderId));
        exit;
    } else {
        setFlash('danger', 'Invalid order status selected.');
    }
}

// Fetch Order
$stmt = $db->prepare("
    SELECT o.*, u.name as user_account_name, u.email as user_account_email
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    WHERE o.id = ?
");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    echo '<div class="admin-card text-center py-5">';
    echo '<h4>Order Not Found</h4>';
    echo '<p class="text-muted">The requested order does not exist.</p>';
    echo '<a href="' . baseUrl('admin/orders.php') . '" class="btn btn-primary btn-sm">Back to Orders</a>';
    echo '</div>';
    require_once __DIR__ . '/includes/admin-footer.php';
    exit;
}

// Fetch Order Items
$itemsStmt = $db->prepare("
    SELECT oi.*, p.image, p.unit, p.stock as current_inventory_stock
    FROM order_items oi
    LEFT JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
$itemsStmt->execute([$orderId]);
$orderItems = $itemsStmt->fetchAll();
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <a href="<?= baseUrl('admin/orders.php') ?>" class="small text-decoration-none text-secondary d-inline-block mb-1">
            <i class="bi bi-arrow-left me-1"></i>Back to All Orders
        </a>
        <h4 class="fw-bold mb-0">Order #FC<?= escape($order['id']) ?></h4>
        <span class="text-muted small">Placed on <?= date('d F Y \a\t h:i A', strtotime($order['created_at'])) ?></span>
    </div>

    <!-- Live Status Updater Form -->
    <div class="admin-card p-2 mb-0 d-flex align-items-center gap-2">
        <span class="small fw-bold text-muted text-nowrap">Current Status:</span>
        <div><?= getStatusBadge($order['status']) ?></div>
        
        <form method="POST" action="<?= baseUrl('admin/order-details.php?id=' . $order['id']) ?>" class="d-flex align-items-center gap-2 ms-2">
            <input type="hidden" name="action" value="update_status">
            <select name="status" class="form-select form-select-sm" style="width: auto;">
                <?php 
                $statuses = ['Pending', 'Confirmed', 'Preparing', 'Ready', 'Delivered', 'Cancelled'];
                foreach ($statuses as $st):
                ?>
                    <option value="<?= $st ?>" <?= $order['status'] === $st ? 'selected' : '' ?>><?= $st ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-sm btn-primary">
                <i class="bi bi-arrow-clockwise me-1"></i>Update
            </button>
        </form>
    </div>
</div>

<div class="row g-4">
    <!-- Ordered Items Table -->
    <div class="col-lg-8">
        <div class="admin-card p-0 overflow-hidden mb-4">
            <div class="p-3 bg-light border-bottom">
                <h6 class="fw-bold mb-0"><i class="bi bi-basket-fill text-success me-2"></i>Order Items Breakdown</h6>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0 small">
                    <thead class="table-light">
                        <tr>
                            <th>Product Snapshot</th>
                            <th>Unit Price</th>
                            <th>Quantity</th>
                            <th class="text-end">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $calcSubtotal = 0;
                        foreach ($orderItems as $item): 
                            $calcSubtotal += (float)$item['subtotal'];
                        ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <img src="<?= escape(getProductImageUrl($item['image'] ?? '')) ?>" alt="<?= escape($item['product_name']) ?>" class="rounded border p-1 bg-white" style="width: 44px; height: 44px; object-fit: contain;">
                                        <div>
                                            <strong class="text-dark d-block"><?= escape($item['product_name']) ?></strong>
                                            <span class="text-muted" style="font-size: 0.75rem;">
                                                Current live stock: <?= isset($item['current_inventory_stock']) ? $item['current_inventory_stock'] : 'N/A' ?> <?= escape($item['unit'] ?? '') ?>
                                            </span>
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

        <!-- Invoice Calculation Card -->
        <div class="admin-card">
            <h6 class="fw-bold mb-3 pb-2 border-bottom"><i class="bi bi-calculator text-success me-2"></i>Invoice Breakdown</h6>
            <div class="row justify-content-end">
                <div class="col-md-6">
                    <div class="d-flex justify-content-between mb-2 small">
                        <span class="text-muted">Items Subtotal:</span>
                        <span class="fw-bold text-dark"><?= formatPrice($calcSubtotal) ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2 small">
                        <span class="text-muted">Delivery Charges:</span>
                        <span class="fw-bold text-dark"><?= formatPrice($order['delivery_charge']) ?></span>
                    </div>
                    <div class="d-flex justify-content-between pt-2 border-top">
                        <span class="fw-bold text-dark">Grand Total:</span>
                        <span class="fw-bold text-success fs-5"><?= formatPrice($order['total_amount']) ?></span>
                    </div>
                    <div class="mt-2 text-muted small text-end">
                        Payment Mode: Cash / UPI on Delivery
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Customer & Delivery Sidebar -->
    <div class="col-lg-4">
        <!-- Customer Account Details -->
        <div class="admin-card mb-4">
            <h6 class="fw-bold mb-3 pb-2 border-bottom"><i class="bi bi-person text-success me-2"></i>Customer Account</h6>
            <div class="small">
                <div class="mb-2">
                    <span class="text-muted d-block">Account Name:</span>
                    <strong><?= escape($order['user_account_name'] ?? 'N/A') ?></strong>
                </div>
                <div class="mb-2">
                    <span class="text-muted d-block">Account Email:</span>
                    <a href="mailto:<?= escape($order['user_account_email']) ?>"><?= escape($order['user_account_email'] ?? 'N/A') ?></a>
                </div>
                <div>
                    <span class="text-muted d-block">User ID:</span>
                    <code>#<?= $order['user_id'] ?></code>
                </div>
            </div>
        </div>

        <!-- Shipping & Recipient Details -->
        <div class="admin-card">
            <h6 class="fw-bold mb-3 pb-2 border-bottom"><i class="bi bi-geo-alt text-success me-2"></i>Delivery Recipient</h6>
            <div class="small">
                <div class="mb-2">
                    <span class="text-muted d-block">Recipient Name:</span>
                    <strong class="fs-6 text-dark"><?= escape($order['delivery_name']) ?></strong>
                </div>
                <div class="mb-2">
                    <span class="text-muted d-block">Phone Number:</span>
                    <a href="tel:<?= escape($order['delivery_phone']) ?>" class="fw-bold text-decoration-none">
                        <i class="bi bi-telephone-fill me-1 text-success"></i><?= escape($order['delivery_phone']) ?>
                    </a>
                </div>
                <div class="mb-2">
                    <span class="text-muted d-block">Shipping Address:</span>
                    <p class="text-dark mb-0 bg-light p-2 rounded border">
                        <?= nl2br(escape($order['delivery_address'])) ?><br>
                        <strong><?= escape($order['delivery_city']) ?> - <?= escape($order['delivery_pincode']) ?></strong>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
