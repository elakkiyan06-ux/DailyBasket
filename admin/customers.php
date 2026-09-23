<?php
// admin/customers.php - Administrator Customer Directory
$adminTitle = "Customer Directory";
require_once __DIR__ . '/includes/admin-header.php';

$db = getDB();
$search = trim($_GET['search'] ?? '');

$whereClause = "WHERE u.role = 'customer'";
$params = [];

if ($search !== '') {
    $whereClause .= " AND (u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ? OR u.city LIKE ?)";
    $like = '%' . $search . '%';
    $params = [$like, $like, $like, $like];
}

$sql = "
    SELECT 
        u.id, u.name, u.email, u.phone, u.city, u.pincode, u.created_at,
        COUNT(o.id) as total_orders,
        COALESCE(SUM(o.total_amount), 0) as total_spent
    FROM users u
    LEFT JOIN orders o ON u.id = o.user_id AND o.status != 'Cancelled'
    $whereClause
    GROUP BY u.id
    ORDER BY u.created_at DESC
";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$customers = $stmt->fetchAll();
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h4 class="fw-bold mb-1">Registered Customers</h4>
        <p class="text-muted small mb-0">Browse shoppers, review order activity, and manage directory records</p>
    </div>
</div>

<!-- Search Bar -->
<div class="admin-card p-3 mb-4">
    <form method="GET" action="<?= baseUrl('admin/customers.php') ?>" class="row g-2 align-items-center">
        <div class="col-md-6">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                <input type="text" name="search" class="form-control" placeholder="Search by name, email, phone, or city..." value="<?= escape($search) ?>">
            </div>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-sm btn-outline-secondary w-100">Search</button>
        </div>
        <?php if ($search !== ''): ?>
            <div class="col-md-2">
                <a href="<?= baseUrl('admin/customers.php') ?>" class="btn btn-sm btn-light border text-danger">Reset</a>
            </div>
        <?php endif; ?>
    </form>
</div>

<!-- Customers Table -->
<div class="admin-card p-0 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small">
            <thead class="table-light">
                <tr>
                    <th>Customer ID</th>
                    <th>Full Name</th>
                    <th>Email Address</th>
                    <th>Phone Number</th>
                    <th>Location</th>
                    <th>Joined On</th>
                    <th>Orders Placed</th>
                    <th class="text-end">Total Spent</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($customers)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-people fs-1 d-block mb-2 text-muted"></i>
                            No registered customers found.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($customers as $c): ?>
                        <tr>
                            <td><code>#<?= $c['id'] ?></code></td>
                            <td>
                                <strong class="text-dark d-block"><?= escape($c['name']) ?></strong>
                            </td>
                            <td>
                                <a href="mailto:<?= escape($c['email']) ?>"><?= escape($c['email']) ?></a>
                            </td>
                            <td><?= escape($c['phone']) ?></td>
                            <td>
                                <span class="text-secondary"><?= escape($c['city'] ?: 'Not provided') ?></span>
                            </td>
                            <td><?= date('d M Y', strtotime($c['created_at'])) ?></td>
                            <td>
                                <span class="badge bg-light text-dark border"><?= (int)$c['total_orders'] ?> orders</span>
                            </td>
                            <td class="text-end fw-bold text-success">
                                <?= formatPrice($c['total_spent']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
