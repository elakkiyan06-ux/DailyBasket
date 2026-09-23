<?php
// product.php - Product Details Page
$productId = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
require_once __DIR__ . '/includes/header.php';

$db = getDB();

// Fetch product with category
$stmt = $db->prepare("
    SELECT p.*, c.name as category_name, c.id as category_id
    FROM products p 
    JOIN categories c ON p.category_id = c.id 
    WHERE p.id = ? AND p.status != 'inactive'
");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    echo '<div class="container py-5 text-center">';
    echo '<div class="fc-empty-state bg-white border rounded-3 p-5 my-4">';
    echo '<i class="bi bi-exclamation-triangle text-warning fs-1 mb-3"></i>';
    echo '<h3>Product Not Found</h3>';
    echo '<p class="text-muted">The grocery item you requested is unavailable or has been removed.</p>';
    echo '<a href="' . baseUrl('shop.php') . '" class="btn btn-primary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back to Shop</a>';
    echo '</div></div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$pageTitle = $product['name'];

// Related products
$relStmt = $db->prepare("
    SELECT p.*, c.name as category_name 
    FROM products p 
    JOIN categories c ON p.category_id = c.id 
    WHERE p.category_id = ? AND p.id != ? AND p.status != 'inactive' 
    LIMIT 4
");
$relStmt->execute([$product['category_id'], $product['id']]);
$relatedProducts = $relStmt->fetchAll();
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="<?= baseUrl('index.php') ?>">Home</a></li>
            <li class="breadcrumb-item"><a href="<?= baseUrl('shop.php') ?>">Shop</a></li>
            <li class="breadcrumb-item"><a href="<?= baseUrl('shop.php?category=' . $product['category_id']) ?>"><?= escape($product['category_name']) ?></a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= escape($product['name']) ?></li>
        </ol>
    </nav>

    <!-- Product Card Details -->
    <div class="card border shadow-sm rounded-3 p-4 mb-5 bg-white">
        <div class="row g-5 align-items-center">
            <!-- Product Large Image -->
            <div class="col-lg-5 text-center">
                <div class="rounded-3 overflow-hidden bg-light border p-2 position-relative">
                    <img src="<?= getProductImageUrl($product['image']) ?>" alt="<?= escape($product['name']) ?>" class="img-fluid rounded-3" style="max-height: 420px; width: 100%; object-fit: cover;">
                    <div class="position-absolute top-0 end-0 p-3">
                        <?= getStockBadge((int)$product['stock'], $product['status']) ?>
                    </div>
                </div>
            </div>

            <!-- Product Specs & Cart Action -->
            <div class="col-lg-7">
                <span class="text-success text-uppercase fw-bold small"><?= escape($product['category_name']) ?></span>
                <h1 class="h2 fw-bold mt-1 mb-2"><?= escape($product['name']) ?></h1>
                
                <div class="d-flex align-items-baseline gap-2 mb-3">
                    <span class="fs-2 fw-bolder text-dark"><?= formatPrice($product['price']) ?></span>
                    <span class="text-muted fw-semibold fs-5">/ <?= escape($product['unit']) ?></span>
                </div>

                <div class="mb-4">
                    <h6 class="fw-bold text-muted small text-uppercase">Description</h6>
                    <p class="text-secondary leading-relaxed">
                        <?= nl2br(escape($product['description'])) ?>
                    </p>
                </div>

                <div class="p-3 bg-light rounded-3 mb-4 border">
                    <div class="row g-3 small">
                        <div class="col-6">
                            <span class="text-muted d-block">Availability:</span>
                            <?php if ($product['stock'] > 0): ?>
                                <strong class="text-success"><i class="bi bi-check-circle-fill me-1"></i>In Stock (<?= (int)$product['stock'] ?> <?= escape($product['unit']) ?> available)</strong>
                            <?php else: ?>
                                <strong class="text-danger"><i class="bi bi-x-circle-fill me-1"></i>Currently Out of Stock</strong>
                            <?php endif; ?>
                        </div>
                        <div class="col-6">
                            <span class="text-muted d-block">Unit Measurement:</span>
                            <strong>Per <?= escape($product['unit']) ?></strong>
                        </div>
                    </div>
                </div>

                <!-- Add to Cart Form -->
                <?php if ($product['stock'] > 0 && $product['status'] === 'active'): ?>
                    <form action="<?= baseUrl('cart-action.php') ?>" method="POST" class="mb-4">
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">

                        <div class="row align-items-center g-3">
                            <div class="col-auto">
                                <label class="form-label small fw-bold text-muted mb-1 d-block">Select Quantity:</label>
                                <div class="fc-qty-group">
                                    <button type="button" class="fc-qty-btn" data-action="dec">-</button>
                                    <input type="number" name="quantity" class="fc-qty-input" value="1" min="1" max="<?= $product['stock'] ?>">
                                    <button type="button" class="fc-qty-btn" data-action="inc">+</button>
                                </div>
                            </div>
                            <div class="col-auto">
                                <label class="form-label d-none d-sm-block mb-1">&nbsp;</label>
                                <button type="submit" class="btn btn-primary px-4 d-flex align-items-center gap-2">
                                    <i class="bi bi-cart-plus fs-5"></i>
                                    <span>Add to Cart</span>
                                </button>
                            </div>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="alert alert-danger d-inline-flex align-items-center gap-2 mb-4" role="alert">
                        <i class="bi bi-exclamation-octagon-fill fs-5"></i>
                        <div>This grocery item is currently out of stock. Please check back later.</div>
                    </div>
                <?php endif; ?>

                <div class="d-flex align-items-center gap-3 pt-3 border-top">
                    <a href="<?= baseUrl('shop.php') ?>" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-left me-1"></i>Back to Shop
                    </a>
                    <span class="text-muted small"><i class="bi bi-shield-check text-success me-1"></i>Quality guaranteed by DailyBasket</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Related Products -->
    <?php if (!empty($relatedProducts)): ?>
        <div class="mb-5">
            <h4 class="fw-bold mb-3">Related Groceries</h4>
            <div class="row g-4 row-cols-1 row-cols-sm-2 row-cols-lg-4">
                <?php foreach ($relatedProducts as $rel): ?>
                    <div class="col">
                        <div class="fc-card">
                            <div class="fc-card-img-wrapper">
                                <img src="<?= getProductImageUrl($rel['image']) ?>" alt="<?= escape($rel['name']) ?>" class="fc-card-img">
                            </div>
                            <div class="fc-card-body">
                                <div class="fc-category-tag"><?= escape($rel['category_name']) ?></div>
                                <h6 class="fc-product-title">
                                    <a href="<?= baseUrl('product.php?id=' . $rel['id']) ?>"><?= escape($rel['name']) ?></a>
                                </h6>
                                <div class="d-flex align-items-baseline gap-1 mt-auto">
                                    <span class="fc-product-price"><?= formatPrice($rel['price']) ?></span>
                                    <span class="fc-product-unit">/ <?= escape($rel['unit']) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
