<?php
// admin/products.php - Administrator Product Inventory Management
$adminTitle = "Products Inventory";
require_once __DIR__ . '/includes/admin-header.php';

$db = getDB();

// Handle quick delete action
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    $delStmt = $db->prepare("DELETE FROM products WHERE id = ?");
    $delStmt->execute([$delId]);
    setFlash('success', 'Product #ID ' . $delId . ' deleted successfully.');
    header('Location: ' . baseUrl('admin/products.php'));
    exit;
}

// Handle quick stock update action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quick_stock_update'])) {
    $pId = (int)$_POST['product_id'];
    $newStock = max(0, (int)$_POST['stock']);
    
    // Status update logic
    $newStatus = ($newStock > 0) ? 'active' : 'out_of_stock';
    $uStmt = $db->prepare("UPDATE products SET stock = ?, status = ? WHERE id = ?");
    $uStmt->execute([$newStock, $newStatus, $pId]);
    setFlash('success', 'Stock updated for product #' . $pId . '.');
    header('Location: ' . baseUrl('admin/products.php'));
    exit;
}

// Filters
$search     = trim($_GET['search'] ?? '');
$categoryId = isset($_GET['category']) && is_numeric($_GET['category']) ? (int)$_GET['category'] : null;
$stockFilter= trim($_GET['stock_filter'] ?? '');

$whereClauses = ["1=1"];
$params = [];

if ($search !== '') {
    $whereClauses[] = "(p.name LIKE ? OR p.description LIKE ?)";
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
}

if ($categoryId) {
    $whereClauses[] = "p.category_id = ?";
    $params[] = $categoryId;
}

if ($stockFilter === 'out_of_stock') {
    $whereClauses[] = "p.stock <= 0";
} elseif ($stockFilter === 'low_stock') {
    $whereClauses[] = "p.stock > 0 AND p.stock <= 5";
} elseif ($stockFilter === 'in_stock') {
    $whereClauses[] = "p.stock > 5";
}

$sql = "
    SELECT p.*, c.name as category_name 
    FROM products p 
    JOIN categories c ON p.category_id = c.id 
    WHERE " . implode(' AND ', $whereClauses) . "
    ORDER BY p.id DESC
";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Categories for filter
$categories = $db->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h4 class="fw-bold mb-1">Grocery Products Management</h4>
        <p class="text-muted small mb-0">Total <?= count($products) ?> product<?= count($products) !== 1 ? 's' : '' ?> matching current filter criteria</p>
    </div>
    <a href="<?= baseUrl('admin/add-product.php') ?>" class="btn btn-primary btn-sm shadow-sm">
        <i class="bi bi-plus-lg me-1"></i>Add New Product
    </a>
</div>

<!-- Filter Bar -->
<div class="admin-card p-3 mb-4">
    <form method="GET" action="<?= baseUrl('admin/products.php') ?>" class="row g-2 align-items-center">
        <div class="col-md-4">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                <input type="text" name="search" class="form-control" placeholder="Search by product name..." value="<?= escape($search) ?>">
            </div>
        </div>
        <div class="col-md-3">
            <select name="category" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= $categoryId == $cat['id'] ? 'selected' : '' ?>>
                        <?= escape($cat['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <select name="stock_filter" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">All Stock Levels</option>
                <option value="in_stock" <?= $stockFilter === 'in_stock' ? 'selected' : '' ?>>In Stock (&gt; 5)</option>
                <option value="low_stock" <?= $stockFilter === 'low_stock' ? 'selected' : '' ?>>Low Stock (&le; 5)</option>
                <option value="out_of_stock" <?= $stockFilter === 'out_of_stock' ? 'selected' : '' ?>>Out of Stock (0)</option>
            </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-sm btn-outline-secondary w-100">Filter</button>
            <?php if ($search !== '' || $categoryId !== null || $stockFilter !== ''): ?>
                <a href="<?= baseUrl('admin/products.php') ?>" class="btn btn-sm btn-light border" title="Reset Filters"><i class="bi bi-x-lg"></i></a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Products Table -->
<div class="admin-card p-0 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small">
            <thead class="table-light">
                <tr>
                    <th style="width: 70px;">Image</th>
                    <th>Product Name</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Stock / Unit</th>
                    <th>Status</th>
                    <th class="text-end" style="min-width: 160px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="bi bi-box-seam fs-1 d-block mb-2 text-muted"></i>
                            No grocery products match your search/filter criteria.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($products as $p): ?>
                        <tr class="<?= $p['stock'] <= 0 ? 'table-danger-subtle' : ($p['stock'] <= 5 ? 'table-warning-subtle' : '') ?>">
                            <td>
                                <img src="<?= escape(getProductImageUrl($p['image'])) ?>" alt="<?= escape($p['name']) ?>" class="rounded border p-1 bg-white" style="width: 48px; height: 48px; object-fit: contain;">
                            </td>
                            <td>
                                <span class="fw-bold text-dark d-block"><?= escape($p['name']) ?></span>
                                <span class="text-muted" style="font-size: 0.75rem;">ID: #<?= $p['id'] ?></span>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border"><?= escape($p['category_name']) ?></span>
                            </td>
                            <td>
                                <strong class="text-dark"><?= formatPrice($p['price']) ?></strong>
                            </td>
                            <td>
                                <!-- Quick Restock inline form -->
                                <form method="POST" action="<?= baseUrl('admin/products.php') ?>" class="d-flex align-items-center gap-1">
                                    <input type="hidden" name="quick_stock_update" value="1">
                                    <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                    <input type="number" name="stock" value="<?= $p['stock'] ?>" min="0" max="9999" class="form-control form-control-sm text-center" style="width: 70px;">
                                    <span class="text-muted small"><?= escape($p['unit']) ?></span>
                                    <button type="submit" class="btn btn-sm btn-light border py-0 px-2" title="Save stock changes">
                                        <i class="bi bi-check text-success"></i>
                                    </button>
                                </form>
                            </td>
                            <td>
                                <?= getStockBadge((int)$p['stock'], $p['status']) ?>
                            </td>
                            <td class="text-end">
                                <a href="<?= baseUrl('admin/edit-product.php?id=' . $p['id']) ?>" class="btn btn-sm btn-outline-primary py-1 px-2 me-1" title="Edit Product">
                                    <i class="bi bi-pencil me-1"></i>Edit
                                </a>
                                <a href="<?= baseUrl('admin/products.php?delete=' . $p['id']) ?>" class="btn btn-sm btn-outline-danger py-1 px-2" title="Delete Product" data-confirm="Are you sure you want to delete '<?= escape($p['name']) ?>'? This action cannot be undone.">
                                    <i class="bi bi-trash"></i>
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
