<?php
// admin/index.php - Administrator Analytics & Overview Dashboard
$adminTitle = "Dashboard Overview";
require_once __DIR__ . '/includes/admin-header.php';

$db = getDB();

// 1. Metric: Total Products & Available Products
$prodMetrics = $db->query("
    SELECT 
        COUNT(*) as total_products,
        SUM(CASE WHEN stock > 0 AND status = 'active' THEN 1 ELSE 0 END) as available_products,
        SUM(CASE WHEN stock <= 0 THEN 1 ELSE 0 END) as out_of_stock_products
    FROM products
")->fetch();

// 2. Metric: Total Orders, Pending Orders, Total Sales
$orderMetrics = $db->query("
    SELECT 
        COUNT(*) as total_orders,
        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending_orders,
        SUM(CASE WHEN status = 'Delivered' THEN 1 ELSE 0 END) as delivered_orders,
        COALESCE(SUM(CASE WHEN status != 'Cancelled' THEN total_amount ELSE 0 END), 0) as total_sales
    FROM orders
")->fetch();

// 3. Metric: Total Customers
$custMetrics = $db->query("
    SELECT COUNT(*) as total_customers 
    FROM users 
    WHERE role = 'customer'
")->fetch();

// 4. Recent 6 Orders
$recentOrders = $db->query("
    SELECT o.*, u.name as customer_name, u.email as customer_email
    FROM orders o
    JOIN users u ON o.user_id = u.id
    ORDER BY o.created_at DESC
    LIMIT 6
")->fetchAll();

// 5. Order Status Distribution for Chart
$statusCounts = $db->query("
    SELECT status, COUNT(*) as count 
    FROM orders 
    GROUP BY status
")->fetchAll(PDO::FETCH_KEY_PAIR);

// 6. Category-wise product distribution
$catDistribution = $db->query("
    SELECT c.name, COUNT(p.id) as count 
    FROM categories c 
    LEFT JOIN products p ON c.id = p.category_id 
    GROUP BY c.id 
    ORDER BY count DESC
")->fetchAll();
?>

<!-- 6 Metric Cards Row -->
<div class="row g-3 mb-4">
    <!-- Total Products -->
    <div class="col-sm-6 col-xl-2">
        <div class="admin-card mb-0">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small fw-bold">Total Products</span>
                <div class="admin-metric-icon bg-primary-subtle text-primary" style="width: 38px; height: 38px; font-size: 1.1rem;">
                    <i class="bi bi-box-seam"></i>
                </div>
            </div>
            <h3 class="fw-bolder mb-1"><?= (int)$prodMetrics['total_products'] ?></h3>
            <span class="small text-muted">In inventory catalog</span>
        </div>
    </div>

    <!-- Available Products -->
    <div class="col-sm-6 col-xl-2">
        <div class="admin-card mb-0">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small fw-bold">In Stock</span>
                <div class="admin-metric-icon bg-success-subtle text-success" style="width: 38px; height: 38px; font-size: 1.1rem;">
                    <i class="bi bi-check2-circle"></i>
                </div>
            </div>
            <h3 class="fw-bolder mb-1 text-success"><?= (int)$prodMetrics['available_products'] ?></h3>
            <span class="small text-muted"><?= (int)$prodMetrics['out_of_stock_products'] ?> items out of stock</span>
        </div>
    </div>

    <!-- Total Orders -->
    <div class="col-sm-6 col-xl-2">
        <div class="admin-card mb-0">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small fw-bold">Total Orders</span>
                <div class="admin-metric-icon bg-info-subtle text-info" style="width: 38px; height: 38px; font-size: 1.1rem;">
                    <i class="bi bi-receipt"></i>
                </div>
            </div>
            <h3 class="fw-bolder mb-1"><?= (int)$orderMetrics['total_orders'] ?></h3>
            <span class="small text-muted"><?= (int)$orderMetrics['delivered_orders'] ?> delivered</span>
        </div>
    </div>

    <!-- Pending Orders -->
    <div class="col-sm-6 col-xl-2">
        <div class="admin-card mb-0">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small fw-bold">Pending Orders</span>
                <div class="admin-metric-icon bg-warning-subtle text-warning" style="width: 38px; height: 38px; font-size: 1.1rem;">
                    <i class="bi bi-clock-history"></i>
                </div>
            </div>
            <h3 class="fw-bolder mb-1 text-warning"><?= (int)$orderMetrics['pending_orders'] ?></h3>
            <span class="small text-muted">Awaiting fulfillment</span>
        </div>
    </div>

    <!-- Total Customers -->
    <div class="col-sm-6 col-xl-2">
        <div class="admin-card mb-0">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small fw-bold">Customers</span>
                <div class="admin-metric-icon bg-secondary-subtle text-secondary" style="width: 38px; height: 38px; font-size: 1.1rem;">
                    <i class="bi bi-people"></i>
                </div>
            </div>
            <h3 class="fw-bolder mb-1"><?= (int)$custMetrics['total_customers'] ?></h3>
            <span class="small text-muted">Registered shoppers</span>
        </div>
    </div>

    <!-- Total Sales -->
    <div class="col-sm-6 col-xl-2">
        <div class="admin-card mb-0">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small fw-bold">Total Revenue</span>
                <div class="admin-metric-icon bg-success-subtle text-success" style="width: 38px; height: 38px; font-size: 1.1rem;">
                    <i class="bi bi-cash-stack"></i>
                </div>
            </div>
            <h3 class="fw-bolder mb-1 text-success fs-5"><?= formatPrice($orderMetrics['total_sales']) ?></h3>
            <span class="small text-muted">All active orders</span>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row g-4 mb-4">
    <!-- Category Distribution Chart -->
    <div class="col-lg-8">
        <div class="admin-card mb-0">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0"><i class="bi bi-bar-chart-line text-success me-2"></i>Products by Category</h6>
                <span class="badge bg-light text-dark border">Catalog Breakdown</span>
            </div>
            <div style="height: 280px;">
                <canvas id="categoryChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Order Status Doughnut Chart -->
    <div class="col-lg-4">
        <div class="admin-card mb-0">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0"><i class="bi bi-pie-chart text-success me-2"></i>Order Status Ratio</h6>
                <span class="badge bg-light text-dark border"><?= (int)$orderMetrics['total_orders'] ?> Orders</span>
            </div>
            <div style="height: 280px; position: relative;">
                <canvas id="statusChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Recent Orders & Quick Actions -->
<div class="row g-4">
    <!-- Recent Orders Table -->
    <div class="col-lg-9">
        <div class="admin-card mb-0">
            <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                <h6 class="fw-bold mb-0"><i class="bi bi-clock-history text-success me-2"></i>Recent Orders</h6>
                <a href="<?= baseUrl('admin/orders.php') ?>" class="btn btn-sm btn-outline-primary">View All Orders</a>
            </div>

            <?php if (empty($recentOrders)): ?>
                <p class="text-muted small mb-0 py-3 text-center">No orders have been placed yet.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 small">
                        <thead class="table-light">
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentOrders as $ro): ?>
                                <tr>
                                    <td>
                                        <a href="<?= baseUrl('admin/order-details.php?id=' . $ro['id']) ?>" class="fw-bold text-dark">
                                            #FC<?= escape($ro['id']) ?>
                                        </a>
                                    </td>
                                    <td>
                                        <span class="fw-semibold d-block"><?= escape($ro['delivery_name']) ?></span>
                                        <span class="text-muted" style="font-size: 0.75rem;"><?= escape($ro['delivery_phone']) ?></span>
                                    </td>
                                    <td><?= date('d M Y, h:i A', strtotime($ro['created_at'])) ?></td>
                                    <td class="fw-bold text-dark"><?= formatPrice($ro['total_amount']) ?></td>
                                    <td><?= getStatusBadge($ro['status']) ?></td>
                                    <td class="text-end">
                                        <a href="<?= baseUrl('admin/order-details.php?id=' . $ro['id']) ?>" class="btn btn-sm btn-outline-primary py-1 px-2">
                                            Manage <i class="bi bi-chevron-right ms-1"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quick Shortcuts Card -->
    <div class="col-lg-3">
        <div class="admin-card mb-4">
            <h6 class="fw-bold mb-3 pb-2 border-bottom"><i class="bi bi-lightning-charge text-success me-2"></i>Quick Actions</h6>
            <div class="d-grid gap-2">
                <a href="<?= baseUrl('admin/add-product.php') ?>" class="btn btn-sm btn-primary text-start">
                    <i class="bi bi-plus-circle me-2"></i>Add New Product
                </a>
                <a href="<?= baseUrl('admin/categories.php') ?>" class="btn btn-sm btn-outline-secondary text-start">
                    <i class="bi bi-folder-plus me-2"></i>Manage Categories
                </a>
                <a href="<?= baseUrl('admin/orders.php?status=Pending') ?>" class="btn btn-sm btn-outline-warning text-dark text-start">
                    <i class="bi bi-hourglass-split me-2"></i>Process Pending Orders
                </a>
                <a href="<?= baseUrl('admin/reports.php') ?>" class="btn btn-sm btn-outline-info text-dark text-start">
                    <i class="bi bi-file-earmark-bar-graph me-2"></i>View Sales Reports
                </a>
            </div>
        </div>

        <div class="admin-card bg-light border">
            <h6 class="fw-bold mb-2 small"><i class="bi bi-info-circle text-primary me-1"></i>Inventory Status</h6>
            <p class="text-muted small mb-1">
                Currently tracking <strong><?= (int)$prodMetrics['total_products'] ?></strong> items across <strong><?= count($catDistribution) ?></strong> grocery categories.
            </p>
            <a href="<?= baseUrl('admin/reports.php') ?>" class="small text-danger fw-bold">
                Check <?= (int)$prodMetrics['out_of_stock_products'] ?> out-of-stock items &rarr;
            </a>
        </div>
    </div>
</div>

<!-- Chart initialization script -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Category Chart
    const catLabels = <?= json_encode(array_column($catDistribution, 'name')) ?>;
    const catCounts = <?= json_encode(array_column($catDistribution, 'count')) ?>;

    const ctxCat = document.getElementById('categoryChart').getContext('2d');
    new Chart(ctxCat, {
        type: 'bar',
        data: {
            labels: catLabels,
            datasets: [{
                label: 'Products',
                data: catCounts,
                backgroundColor: 'rgba(22, 163, 74, 0.75)',
                borderColor: 'rgb(22, 163, 74)',
                borderWidth: 1,
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 } }
            }
        }
    });

    // 2. Status Doughnut Chart
    const statusData = <?= json_encode($statusCounts) ?>;
    const sLabels = Object.keys(statusData);
    const sCounts = Object.values(statusData);

    const ctxStatus = document.getElementById('statusChart').getContext('2d');
    new Chart(ctxStatus, {
        type: 'doughnut',
        data: {
            labels: sLabels.length > 0 ? sLabels : ['No Orders'],
            datasets: [{
                data: sCounts.length > 0 ? sCounts : [1],
                backgroundColor: [
                    '#f59e0b', // Pending
                    '#06b6d4', // Confirmed
                    '#3b82f6', // Preparing
                    '#0d9488', // Ready
                    '#16a34a', // Delivered
                    '#ef4444'  // Cancelled
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });
});
</script>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
