<?php
// admin/reports.php - Sales Analytics, Low Stock Alerts, and Performance Reports
$adminTitle = "Reports & Analytics";
require_once __DIR__ . '/includes/admin-header.php';

$db = getDB();

// 1. Overall Financial & Operational Summary
$summary = $db->query("
    SELECT 
        COUNT(*) as total_orders,
        COALESCE(SUM(CASE WHEN status != 'Cancelled' THEN total_amount ELSE 0 END), 0) as total_sales,
        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending_orders,
        SUM(CASE WHEN status = 'Delivered' THEN 1 ELSE 0 END) as delivered_orders,
        SUM(CASE WHEN status = 'Cancelled' THEN 1 ELSE 0 END) as cancelled_orders,
        AVG(CASE WHEN status != 'Cancelled' THEN total_amount ELSE NULL END) as avg_order_value
    FROM orders
")->fetch();

// 2. Top-Selling Products (by quantity and revenue)
$topProducts = $db->query("
    SELECT 
        oi.product_name,
        SUM(oi.quantity) as total_qty_sold,
        SUM(oi.subtotal) as total_revenue,
        COUNT(DISTINCT oi.order_id) as order_frequency
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.id
    WHERE o.status != 'Cancelled'
    GROUP BY oi.product_name
    ORDER BY total_qty_sold DESC
    LIMIT 5
")->fetchAll();

// 3. Low-Stock and Depleted Products Alert List (stock <= 5)
$lowStockProducts = $db->query("
    SELECT p.*, c.name as category_name
    FROM products p
    JOIN categories c ON p.category_id = c.id
    WHERE p.stock <= 5 OR p.status = 'out_of_stock'
    ORDER BY p.stock ASC
")->fetchAll();

// 4. Category-wise sales revenue
$categorySales = $db->query("
    SELECT 
        c.name as category_name,
        COALESCE(SUM(oi.subtotal), 0) as category_revenue
    FROM categories c
    LEFT JOIN products p ON c.id = p.category_id
    LEFT JOIN order_items oi ON p.id = oi.product_id
    LEFT JOIN orders o ON oi.order_id = o.id AND o.status != 'Cancelled'
    GROUP BY c.id
    ORDER BY category_revenue DESC
")->fetchAll();
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h4 class="fw-bold mb-1">Sales &amp; Inventory Reports</h4>
        <p class="text-muted small mb-0">Live performance analytics, bestsellers, and stock deficit notifications</p>
    </div>
</div>

<!-- 4 Key Performance Metrics -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="admin-card mb-0">
            <span class="text-muted small fw-bold">Total Sales (Completed/Active)</span>
            <h3 class="fw-bolder text-success mt-2 mb-1"><?= formatPrice($summary['total_sales']) ?></h3>
            <span class="small text-muted">Avg: <?= formatPrice($summary['avg_order_value'] ?? 0) ?> / order</span>
        </div>
    </div>
    <div class="col-md-3">
        <div class="admin-card mb-0">
            <span class="text-muted small fw-bold">Total Orders Placed</span>
            <h3 class="fw-bolder mt-2 mb-1"><?= (int)$summary['total_orders'] ?></h3>
            <span class="small text-muted"><?= (int)$summary['delivered_orders'] ?> marked delivered</span>
        </div>
    </div>
    <div class="col-md-3">
        <div class="admin-card mb-0">
            <span class="text-muted small fw-bold">Pending Orders</span>
            <h3 class="fw-bolder text-warning mt-2 mb-1"><?= (int)$summary['pending_orders'] ?></h3>
            <span class="small text-muted">Awaiting processing</span>
        </div>
    </div>
    <div class="col-md-3">
        <div class="admin-card mb-0">
            <span class="text-muted small fw-bold">Low-Stock Warnings</span>
            <h3 class="fw-bolder text-danger mt-2 mb-1"><?= count($lowStockProducts) ?></h3>
            <span class="small text-muted">Items need restocking</span>
        </div>
    </div>
</div>

<!-- Category Revenue Chart & Top-Sellers -->
<div class="row g-4 mb-4">
    <!-- Category Revenue Chart -->
    <div class="col-lg-6">
        <div class="admin-card mb-0">
            <h6 class="fw-bold mb-3 pb-2 border-bottom">
                <i class="bi bi-pie-chart-fill text-success me-2"></i>Sales Revenue by Category
            </h6>
            <div style="height: 280px;">
                <canvas id="catSalesChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Top-Selling Products Table -->
    <div class="col-lg-6">
        <div class="admin-card mb-0 p-0 overflow-hidden">
            <div class="p-3 bg-light border-bottom">
                <h6 class="fw-bold mb-0"><i class="bi bi-trophy-fill text-warning me-2"></i>Top-Selling Grocery Items</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="table-light">
                        <tr>
                            <th>Product Name</th>
                            <th>Units Sold</th>
                            <th>Orders</th>
                            <th class="text-end">Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($topProducts)): ?>
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">No sales recorded yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($topProducts as $idx => $tp): ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-light text-dark border me-1">#<?= $idx + 1 ?></span>
                                        <strong class="text-dark"><?= escape($tp['product_name']) ?></strong>
                                    </td>
                                    <td><span class="badge bg-success-subtle text-success border border-success-subtle"><?= (int)$tp['total_qty_sold'] ?> units</span></td>
                                    <td><?= (int)$tp['order_frequency'] ?> orders</td>
                                    <td class="text-end fw-bold text-dark"><?= formatPrice($tp['total_revenue']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Low Stock & Out of Stock Alerts Table -->
<div class="admin-card p-0 overflow-hidden mb-4 border-danger-subtle">
    <div class="p-3 bg-danger-subtle border-bottom d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0 text-danger">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>Low-Stock &amp; Out-of-Stock Deficit Alert
        </h6>
        <span class="badge bg-danger"><?= count($lowStockProducts) ?> Alert<?= count($lowStockProducts) !== 1 ? 's' : '' ?></span>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small">
            <thead class="table-light">
                <tr>
                    <th>Product Name</th>
                    <th>Category</th>
                    <th>Unit Price</th>
                    <th>Remaining Stock</th>
                    <th>Status</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($lowStockProducts)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-success">
                            <i class="bi bi-check-circle fs-3 d-block mb-1"></i>
                            All grocery products have healthy inventory levels!
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($lowStockProducts as $lp): ?>
                        <tr class="<?= $lp['stock'] <= 0 ? 'table-danger' : 'table-warning' ?>">
                            <td>
                                <strong class="text-dark d-block"><?= escape($lp['name']) ?></strong>
                                <span class="text-muted" style="font-size: 0.75rem;">ID: #<?= $lp['id'] ?></span>
                            </td>
                            <td><?= escape($lp['category_name']) ?></td>
                            <td><?= formatPrice($lp['price']) ?></td>
                            <td>
                                <strong class="text-danger fs-6"><?= (int)$lp['stock'] ?></strong> <?= escape($lp['unit']) ?>
                            </td>
                            <td>
                                <?= getStockBadge((int)$lp['stock'], $lp['status']) ?>
                            </td>
                            <td class="text-end">
                                <a href="<?= baseUrl('admin/edit-product.php?id=' . $lp['id']) ?>" class="btn btn-sm btn-primary py-1 px-3">
                                    <i class="bi bi-box-arrow-in-down me-1"></i>Restock
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const catSales = <?= json_encode($categorySales) ?>;
    const labels = catSales.map(c => c.category_name);
    const data = catSales.map(c => parseFloat(c.category_revenue));

    const ctx = document.getElementById('catSalesChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Revenue (₹)',
                data: data,
                backgroundColor: 'rgba(22, 163, 74, 0.7)',
                borderColor: 'rgb(22, 163, 74)',
                borderWidth: 1,
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
});
</script>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
