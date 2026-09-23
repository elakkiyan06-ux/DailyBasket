<?php
// index.php - DailyBasket Customer Home Page
$pageTitle = "Home";
require_once __DIR__ . '/includes/header.php';

$db = getDB();

// Fetch categories
$catStmt = $db->query("
    SELECT c.*, COUNT(p.id) as product_count 
    FROM categories c 
    LEFT JOIN products p ON c.id = p.category_id AND p.status != 'inactive'
    GROUP BY c.id 
    ORDER BY c.id ASC
");
$categories = $catStmt->fetchAll();

// Fetch featured products (8 products)
$prodStmt = $db->query("
    SELECT p.*, c.name as category_name 
    FROM products p 
    JOIN categories c ON p.category_id = c.id 
    WHERE p.status != 'inactive' 
    ORDER BY p.id ASC 
    LIMIT 8
");
$featuredProducts = $prodStmt->fetchAll();
?>

<!-- Hero Section -->
<section class="fc-hero">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill fw-bold mb-3 d-inline-flex align-items-center gap-1">
                    <i class="bi bi-patch-check-fill"></i> 100% Farm Fresh & Daily Essentials
                </span>
                <h1 class="fc-hero-title mb-3">Fresh Groceries, Delivered Simply.</h1>
                <p class="fc-hero-subtitle mb-4">
                    Shop everyday essentials at <strong>DailyBasket</strong>, add them to your cart, and place your order in just a few clicks. Guaranteed fresh fruits, vegetables, dairy, and pantry staples.
                </p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="<?= baseUrl('shop.php') ?>" class="btn btn-primary btn-lg shadow-sm">
                        <i class="bi bi-cart-plus me-2"></i>Shop Now
                    </a>
                    <a href="#categories-section" class="btn btn-outline-primary btn-lg">
                        <i class="bi bi-grid me-2"></i>Explore Categories
                    </a>
                </div>

                <!-- Trust Stats -->
                <div class="row mt-5 pt-3 border-top border-success-subtle g-4">
                    <div class="col-4">
                        <h4 class="fw-bolder mb-0 text-success">25+</h4>
                        <small class="text-muted fw-semibold">Pantry Essentials</small>
                    </div>
                    <div class="col-4">
                        <h4 class="fw-bolder mb-0 text-success">100%</h4>
                        <small class="text-muted fw-semibold">Pure & Organic</small>
                    </div>
                    <div class="col-4">
                        <h4 class="fw-bolder mb-0 text-success">Free</h4>
                        <small class="text-muted fw-semibold">Delivery &gt; ₹500</small>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="fc-hero-img-box text-center p-3 bg-white rounded-4 shadow-sm border">
                    <img src="<?= baseUrl('assets/images/dailybasket-logo.png') ?>" 
                         alt="DailyBasket Logo" class="img-fluid rounded-3" style="max-height: 380px; object-fit: contain;">
                    <div class="fc-hero-badge">
                        <div class="rounded-circle bg-success text-white p-2 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                            <i class="bi bi-truck fs-5"></i>
                        </div>
                        <div class="text-start">
                            <h6 class="mb-0 fw-bold">Express Delivery</h6>
                            <small class="text-muted">Doorstep delivery within hours</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 1. Popular Categories -->
<section id="categories-section" class="py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <span class="text-success text-uppercase fw-bold small">Explore By Category</span>
                <h2 class="mb-0">Popular Categories</h2>
            </div>
            <a href="<?= baseUrl('shop.php') ?>" class="btn btn-sm btn-outline-primary">View All <i class="bi bi-arrow-right ms-1"></i></a>
        </div>

        <div class="row g-3 row-cols-2 row-cols-md-4">
            <?php 
            $catIcons = [
                'Fruits'        => 'bi-apple',
                'Vegetables'    => 'bi-flower2',
                'Dairy'         => 'bi-cup-straw',
                'Rice & Grains' => 'bi-basket',
                'Snacks'        => 'bi-egg-fried',
                'Beverages'     => 'bi-cup-hot',
                'Bakery'        => 'bi-cookie',
                'Household'     => 'bi-shield-check',
            ];
            foreach ($categories as $cat): 
                $icon = $catIcons[$cat['name']] ?? 'bi-bag-check';
            ?>
                <div class="col">
                    <a href="<?= baseUrl('shop.php?category=' . $cat['id']) ?>" class="fc-category-card">
                        <div class="fc-category-icon">
                            <i class="bi <?= $icon ?>"></i>
                        </div>
                        <h6 class="fw-bold mb-1"><?= escape($cat['name']) ?></h6>
                        <small class="text-muted"><?= (int)$cat['product_count'] ?> Products</small>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- 2. Featured Products -->
<section class="py-5 bg-white border-top border-bottom">
    <div class="container">
        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <span class="text-success text-uppercase fw-bold small">Handpicked For You</span>
                <h2 class="mb-0">Featured Products</h2>
            </div>
            <a href="<?= baseUrl('shop.php') ?>" class="btn btn-sm btn-outline-primary">See All Groceries <i class="bi bi-arrow-right ms-1"></i></a>
        </div>

        <div class="row g-4 row-cols-1 row-cols-sm-2 row-cols-lg-4">
            <?php foreach ($featuredProducts as $product): ?>
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
    </div>
</section>

<!-- 3. Why DailyBasket? -->
<section class="py-5">
    <div class="container">
        <div class="text-center max-w-600 mx-auto mb-5">
            <span class="text-success text-uppercase fw-bold small">Why Customers Love Us</span>
            <h2 class="mb-2">Why DailyBasket?</h2>
            <p class="text-muted">Designed for convenience, freshness, and total shopping transparency.</p>
        </div>

        <div class="row g-4">
            <div class="col-md-6 col-lg-3">
                <div class="p-4 bg-white rounded-3 border h-100 shadow-sm text-center">
                    <div class="rounded-circle bg-success-subtle text-success mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px; font-size: 1.6rem;">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <h5 class="fw-bold mb-2">Fresh Products</h5>
                    <p class="text-muted small mb-0">Directly sourced daily essentials, crisp veggies, and premium dairy from verified suppliers.</p>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="p-4 bg-white rounded-3 border h-100 shadow-sm text-center">
                    <div class="rounded-circle bg-success-subtle text-success mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px; font-size: 1.6rem;">
                        <i class="bi bi-lightning-charge"></i>
                    </div>
                    <h5 class="fw-bold mb-2">Easy Ordering</h5>
                    <p class="text-muted small mb-0">Browse with instant category filters, rapid search, and smooth 1-click checkout review.</p>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="p-4 bg-white rounded-3 border h-100 shadow-sm text-center">
                    <div class="rounded-circle bg-success-subtle text-success mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px; font-size: 1.6rem;">
                        <i class="bi bi-cart3"></i>
                    </div>
                    <h5 class="fw-bold mb-2">Simple Cart</h5>
                    <p class="text-muted small mb-0">Instant stock validations, live item quantity adjusters, and clear delivery fee breakdowns.</p>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="p-4 bg-white rounded-3 border h-100 shadow-sm text-center">
                    <div class="rounded-circle bg-success-subtle text-success mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px; font-size: 1.6rem;">
                        <i class="bi bi-geo-alt"></i>
                    </div>
                    <h5 class="fw-bold mb-2">Order Tracking</h5>
                    <p class="text-muted small mb-0">Watch your order progress from Pending to Confirmed, Preparing, and Delivered live.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 4. How It Works -->
<section class="py-5 bg-light border-top">
    <div class="container">
        <div class="text-center mb-5">
            <span class="text-success text-uppercase fw-bold small">Seamless Experience</span>
            <h2 class="mb-2">How It Works</h2>
            <p class="text-muted">Order your daily essentials in 4 effortless steps.</p>
        </div>

        <div class="row g-4">
            <div class="col-md-3 text-center">
                <div class="mb-3 position-relative d-inline-block">
                    <span class="badge rounded-pill bg-success px-3 py-2 fw-bold position-absolute top-0 start-100 translate-middle">1</span>
                    <div class="rounded-circle bg-white shadow-sm p-4 d-flex align-items-center justify-content-center mx-auto" style="width: 90px; height: 90px;">
                        <i class="bi bi-search text-success fs-2"></i>
                    </div>
                </div>
                <h5 class="fw-bold">Step 1: Browse Products</h5>
                <p class="text-muted small">Explore farm produce, groceries, and staples categorized for fast shopping.</p>
            </div>

            <div class="col-md-3 text-center">
                <div class="mb-3 position-relative d-inline-block">
                    <span class="badge rounded-pill bg-success px-3 py-2 fw-bold position-absolute top-0 start-100 translate-middle">2</span>
                    <div class="rounded-circle bg-white shadow-sm p-4 d-flex align-items-center justify-content-center mx-auto" style="width: 90px; height: 90px;">
                        <i class="bi bi-bag-plus text-success fs-2"></i>
                    </div>
                </div>
                <h5 class="fw-bold">Step 2: Add to Cart</h5>
                <p class="text-muted small">Select your needed quantities with automatic stock validation protection.</p>
            </div>

            <div class="col-md-3 text-center">
                <div class="mb-3 position-relative d-inline-block">
                    <span class="badge rounded-pill bg-success px-3 py-2 fw-bold position-absolute top-0 start-100 translate-middle">3</span>
                    <div class="rounded-circle bg-white shadow-sm p-4 d-flex align-items-center justify-content-center mx-auto" style="width: 90px; height: 90px;">
                        <i class="bi bi-card-checklist text-success fs-2"></i>
                    </div>
                </div>
                <h5 class="fw-bold">Step 3: Review Order</h5>
                <p class="text-muted small">Confirm delivery address, verify item subtotals, and place your order.</p>
            </div>

            <div class="col-md-3 text-center">
                <div class="mb-3 position-relative d-inline-block">
                    <span class="badge rounded-pill bg-success px-3 py-2 fw-bold position-absolute top-0 start-100 translate-middle">4</span>
                    <div class="rounded-circle bg-white shadow-sm p-4 d-flex align-items-center justify-content-center mx-auto" style="width: 90px; height: 90px;">
                        <i class="bi bi-truck text-success fs-2"></i>
                    </div>
                </div>
                <h5 class="fw-bold">Step 4: Track Order</h5>
                <p class="text-muted small">Follow live order status updates until your groceries arrive at your door.</p>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
