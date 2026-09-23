<?php
// admin/orders.php - Administrator Order Processing & History
$adminTitle = "Orders Management";
require_once __DIR__ . '/includes/admin-header.php';

$db = getDB();

$statusFilter = trim($_GET['status'] ?? '');
$search       = trim($_GET['search'] ?? '');

$whereClauses = ["1=1"];
$params = [];

if ($statusFilter !== '') {
    $whereClauses[] = "o.status = ?";
    $params[] = $statusFilter;
}

if ($search !== '') {
    $whereClauses[] = "(o.id LIKE ? OR o.delivery_name LIKE ? OR o.delivery_phone LIKE ?)";
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$sql = "
    SELECT o.*, u.email as customer_email, COUNT(oi.id) as total_items
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    LEFT JOIN order_items oi ON o.id = oi.order_id
    WHERE " . implode(' AND ', $whereClauses) . "
    GROUP BY o.id
    ORDER BY o.created_at DESC
";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Get counts by status for quick pill badges
$statusCounts = $db->query("
    SELECT status, COUNT(*) as count 
    FROM orders 
    GROUP BY status
")->fetchAll(PDO::FETCH_KEY_PAIR);
$allCount = array_sum($statusCounts);
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h4 class="fw-bold mb-1">Customer Orders</h4>
        <p class="text-muted small mb-0">Review, fulfill, and update live order statuses</p>
    </div>
</div>

<!-- Status Filter Pills -->
<div class="d-flex flex-wrap gap-2 mb-3">
    <a href="<?= baseUrl('admin/orders.php') ?>" class="btn btn-sm <?= $statusFilter === '' ? 'btn-dark' : 'btn-outline-secondary' ?>">
        All Orders <span class="badge bg-secondary ms-1"><?= $allCount ?></span>
    </a>
    <?php 
    $availableStatuses = ['Pending', 'Confirmed', 'Preparing', 'Ready', 'Delivered', 'Cancelled'];
    foreach ($availableStatuses as $st):
        $count = $statusCounts[$st] ?? 0;
        $activeClass = ($statusFilter === $st) ? 'btn-primary' : 'btn-outline-secondary';
    ?>
        <a href="<?= baseUrl('admin/orders.php?status=' . urlencode($st)) ?>" class="btn btn-sm <?= $activeClass ?>">
            <?= $st ?> <span class="badge bg-light text-dark border ms-1"><?= $count ?></span>
        </a>
    <?php endforeach; ?>
</div>

<!-- Search Bar -->
<div class="admin-card p-3 mb-4">
    <form method="GET" action="<?= baseUrl('admin/orders.php') ?>" class="row g-2 align-items-center">
        <?php if ($statusFilter !== ''): ?>
            <input type="hidden" name="status" value="<?= escape($statusFilter) ?>">
        <?php endif; ?>
        <div class="col-md-6">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                <input type="text" name="search" class="form-control" placeholder="Search by Order ID, customer name, or phone..." value="<?= escape($search) ?>">
            </div>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-sm btn-outline-secondary w-100">Search</button>
        </div>
        <?php if ($search !== '' || $statusFilter !== ''): ?>
            <div class="col-md-2">
                <a href="<?= baseUrl('admin/orders.php') ?>" class="btn btn-sm btn-light border text-danger">Reset Filters</a>
            </div>
        <?php endif; ?>
    </form>
</div>

<!-- Orders Table -->
<div class="admin-card p-0 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small">
            <thead class="table-light">
                <tr>
                    <th>Order ID</th>
                    <th>Customer Name</th>
                    <th>Date Placed</th>
                    <th>Delivery City</th>
                    <th>Items</th>
                    <th>Total Amount</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-receipt fs-1 d-block mb-2 text-muted"></i>
                            No orders found matching the filter criteria.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>
                                <a href="<?= baseUrl('admin/order-details.php?id=' . $order['id']) ?>" class="fw-bold text-dark text-decoration-none">
                                    #FC<?= escape($order['id']) ?>
                                </a>
                            </td>
                            <td>
                                <strong class="d-block text-dark"><?= escape($order['delivery_name']) ?></strong>
                                <span class="text-muted" style="font-size: 0.75rem;"><?= escape($order['delivery_phone']) ?></span>
                            </td>
                            <td>
                                <?= date('d M Y', strtotime($order['created_at'])) ?><br>
                                <span class="text-muted" style="font-size: 0.75rem;"><?= date('h:i A', strtotime($order['created_at'])) ?></span>
                            </td>
                            <td>
                                <span class="text-secondary"><?= escape($order['delivery_city']) ?></span>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border"><?= (int)$order['total_items'] ?> items</span>
                            </td>
                            <td>
                                <strong class="text-dark fs-6"><?= formatPrice($order['total_amount']) ?></strong>
                            </td>
                            <td>
                                <?= getStatusBadge($order['status']) ?>
                            </td>
                            <td class="text-end">
                                <a href="<?= baseUrl('admin/order-details.php?id=' . $order['id']) ?>" class="btn btn-sm btn-primary py-1 px-3">
                                    Manage <i class="bi bi-chevron-right ms-1"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
