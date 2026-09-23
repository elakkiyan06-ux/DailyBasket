<?php
// shop.php - Grocery Catalog and Filter Page
$pageTitle = "Shop Groceries";
require_once __DIR__ . '/includes/header.php';

$db = getDB();

// Input parameters
$search       = trim($_GET['search'] ?? '');
$categoryId   = isset($_GET['category']) && is_numeric($_GET['category']) ? (int)$_GET['category'] : null;
$availability = trim($_GET['availability'] ?? '');
$minPrice     = isset($_GET['min_price']) && is_numeric($_GET['min_price']) ? (float)$_GET['min_price'] : null;
$maxPrice     = isset($_GET['max_price']) && is_numeric($_GET['max_price']) ? (float)$_GET['max_price'] : null;
$sort         = trim($_GET['sort'] ?? 'newest');

// Fetch all categories for filter dropdown / pills
$catStmt = $db->query("
    SELECT c.*, COUNT(p.id) as product_count 
    FROM categories c 
    LEFT JOIN products p ON c.id = p.category_id AND p.status != 'inactive'
    GROUP BY c.id 
    ORDER BY c.name ASC
");
$allCategories = $catStmt->fetchAll();

// Build query
$whereClauses = ["p.status != 'inactive'"];
$params = [];

if ($search !== '') {
    $whereClauses[] = "(p.name LIKE ? OR p.description LIKE ? OR c.name LIKE ?)";
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($categoryId) {
    $whereClauses[] = "p.category_id = ?";
    $params[] = $categoryId;
}

if ($availability === 'in_stock') {
    $whereClauses[] = "p.stock > 0";
} elseif ($availability === 'out_of_stock') {
    $whereClauses[] = "p.stock <= 0";
}

if ($minPrice !== null && $minPrice > 0) {
    $whereClauses[] = "p.price >= ?";
    $params[] = $minPrice;
}

if ($maxPrice !== null && $maxPrice > 0) {
    $whereClauses[] = "p.price <= ?";
    $params[] = $maxPrice;
}

// Sorting logic
$orderBy = match($sort) {
    'price_asc'  => 'p.price ASC',
    'price_desc' => 'p.price DESC',
    'name_asc'   => 'p.name ASC',
    'name_desc'  => 'p.name DESC',
    'oldest'     => 'p.id ASC',
    default      => 'p.id DESC', // newest
};

$sql = "
    SELECT p.*, c.name as category_name 
    FROM products p 
    JOIN categories c ON p.category_id = c.id 
    WHERE " . implode(' AND ', $whereClauses) . "
    ORDER BY $orderBy
";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();
$totalProducts = count($products);
?>

<div class="container py-4">
    <!-- Breadcrumb & Header -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="<?= baseUrl('index.php') ?>">Home</a></li>
            <li class="breadcrumb-item active" aria-current="page">Shop Groceries</li>
        </ol>
    </nav>

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-2 border-bottom gap-3">
        <div>
            <h1 class="h2 mb-1">Shop Groceries</h1>
            <p class="text-muted small mb-0">Showing <?= $totalProducts ?> available item<?= $totalProducts !== 1 ? 's' : '' ?></p>
        </div>

        <!-- Quick Sort Dropdown Form -->
        <div class="d-flex align-items-center gap-2">
            <label for="sortSelect" class="small fw-bold text-muted text-nowrap">Sort by:</label>
            <select id="sortSelect" class="form-select form-select-sm" style="width: auto;" onchange="updateSort(this.value)">
                <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest Arrivals</option>
                <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price: Low to High</option>
                <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price: High to Low</option>
                <option value="name_asc" <?= $sort === 'name_asc' ? 'selected' : '' ?>>Name: A to Z</option>
                <option value="name_desc" <?= $sort === 'name_desc' ? 'selected' : '' ?>>Name: Z to A</option>
            </select>
        </div>
    </div>

    <div class="row g-4">
        <!-- Sidebar Filters -->
        <div class="col-lg-3">
            <div class="bg-white p-3 rounded-3 border shadow-sm sticky-top" style="top: 85px;">
                <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                    <h6 class="fw-bold mb-0"><i class="bi bi-funnel me-1 text-success"></i> Filters</h6>
                    <a href="<?= baseUrl('shop.php') ?>" class="text-danger small fw-semibold">Reset All</a>
                </div>

                <form method="GET" action="<?= baseUrl('shop.php') ?>">
                    <input type="hidden" name="sort" value="<?= escape($sort) ?>">

                    <!-- Search Input -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Keyword Search</label>
                        <div class="input-group input-group-sm">
                            <input type="text" name="search" class="form-control" placeholder="Product name or keyword..." value="<?= escape($search) ?>">
                            <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
                        </div>
                    </div>

                    <!-- Category Filter -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Category</label>
                        <select name="category" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">All Categories</option>
                            <?php foreach ($allCategories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= $categoryId == $cat['id'] ? 'selected' : '' ?>>
                                    <?= escape($cat['name']) ?> (<?= $cat['product_count'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Availability Filter -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Stock Availability</label>
                        <div class="form-check small">
                            <input class="form-check-input" type="radio" name="availability" id="avail_all" value="" <?= $availability === '' ? 'checked' : '' ?> onchange="this.form.submit()">
                            <label class="form-check-label" for="avail_all">All Items</label>
                        </div>
                        <div class="form-check small">
                            <input class="form-check-input" type="radio" name="availability" id="avail_in" value="in_stock" <?= $availability === 'in_stock' ? 'checked' : '' ?> onchange="this.form.submit()">
                            <label class="form-check-label" for="avail_in">In Stock Only</label>
                        </div>
                        <div class="form-check small">
                            <input class="form-check-input" type="radio" name="availability" id="avail_out" value="out_of_stock" <?= $availability === 'out_of_stock' ? 'checked' : '' ?> onchange="this.form.submit()">
                            <label class="form-check-label" for="avail_out">Out of Stock</label>
                        </div>
                    </div>

                    <!-- Price Filter -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Price Range (₹)</label>
                        <div class="d-flex align-items-center gap-2">
                            <input type="number" name="min_price" class="form-control form-control-sm" placeholder="Min" value="<?= $minPrice !== null ? $minPrice : '' ?>">
                            <span class="text-muted">-</span>
                            <input type="number" name="max_price" class="form-control form-control-sm" placeholder="Max" value="<?= $maxPrice !== null ? $maxPrice : '' ?>">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-sm w-100 mt-2">
                        <i class="bi bi-filter me-1"></i>Apply Filters
                    </button>
                </form>
            </div>
        </div>

        <!-- Products Grid Area -->
        <div class="col-lg-9">
            <!-- Active Filter Badges -->
            <?php if ($search !== '' || $categoryId !== null || $availability !== '' || $minPrice !== null || $maxPrice !== null): ?>
                <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                    <span class="small text-muted fw-bold">Active Filters:</span>
                    <?php if ($search !== ''): ?>
                        <span class="badge bg-light text-dark border">Search: "<?= escape($search) ?>"</span>
                    <?php endif; ?>
                    <?php if ($categoryId !== null): 
                        $cName = '';
                        foreach ($allCategories as $c) { if ($c['id'] == $categoryId) { $cName = $c['name']; break; } }
                    ?>
                        <span class="badge bg-light text-dark border">Category: <?= escape($cName) ?></span>
                    <?php endif; ?>
                    <?php if ($availability === 'in_stock'): ?>
                        <span class="badge bg-light text-dark border">In Stock Only</span>
                    <?php endif; ?>
                    <a href="<?= baseUrl('shop.php') ?>" class="small text-danger ms-2"><i class="bi bi-x-circle me-1"></i>Clear All</a>
                </div>
            <?php endif; ?>

            <?php if (empty($products)): ?>
                <!-- Empty State -->
                <div class="fc-empty-state bg-white border rounded-3 shadow-sm my-4">
                    <i class="bi bi-search"></i>
                    <h4 class="fw-bold">No Products Found</h4>
                    <p class="text-muted">We couldn't find any grocery items matching your current filters or search terms.</p>
                    <a href="<?= baseUrl('shop.php') ?>" class="btn btn-outline-primary btn-sm mt-2">
                        <i class="bi bi-arrow-counterclockwise me-1"></i>Clear Filters & View All
                    </a>
                </div>
            <?php else: ?>
                <div class="row g-4 row-cols-1 row-cols-sm-2 row-cols-xl-3">
                    <?php foreach ($products as $product): ?>
                        <div class="col">
                            <div class="fc-card">
                                <div class="fc-card-img-wrapper">
                                    <img src="<?= getProductImageUrl($product['image']) ?>" alt="<?= escape($product['name']) ?>" class="fc-card-img" loading="lazy">
                                    <div class="position-absolute top-0 end-0 p-2">
                                        <?= getStockBadge((int)$product['stock'], $product['status']) ?>
                                    </div>
                                </div>
                                <div class="fc-card-body">
                                    <div class="fc-category-tag"><?= escape($product['category_name']) ?></div>
                                    <h5 class="fc-product-title">
                                        <a href="<?= baseUrl('product.php?id=' . $product['id']) ?>"><?= escape($product['name']) ?></a>
                                    </h5>
                                    
                                    <div class="d-flex align-items-baseline gap-1 mb-3">
                                        <span class="fc-product-price"><?= formatPrice($product['price']) ?></span>
                                        <span class="fc-product-unit">/ <?= escape($product['unit']) ?></span>
                                    </div>

                                    <div class="mt-auto">
                                        <?php if ($product['stock'] > 0 && $product['status'] === 'active'): ?>
                                            <form action="<?= baseUrl('cart-action.php') ?>" method="POST" class="d-flex align-items-center gap-2">
                                                <input type="hidden" name="action" value="add">
                                                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                                
                                                <div class="fc-qty-group">
                                                    <button type="button" class="fc-qty-btn" data-action="dec">-</button>
                                                    <input type="number" name="quantity" class="fc-qty-input" value="1" min="1" max="<?= $product['stock'] ?>">
                                                    <button type="button" class="fc-qty-btn" data-action="inc">+</button>
                                                </div>

                                                <button type="submit" class="btn btn-sm btn-primary flex-grow-1">
                                                    <i class="bi bi-cart-plus me-1"></i>Add
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-secondary w-100 disabled" disabled>
                                                <i class="bi bi-x-circle me-1"></i>Out of Stock
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function updateSort(sortValue) {
    const url = new URL(window.location.href);
    url.searchParams.set('sort', sortValue);
    window.location.href = url.toString();
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
