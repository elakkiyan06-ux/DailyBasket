<?php
// cart.php - Shopping Cart View & Calculations
$pageTitle = "My Cart";
require_once __DIR__ . '/includes/header.php';

$cartItems = getCartItems();
$summary   = getCartSummary();
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="<?= baseUrl('index.php') ?>">Home</a></li>
            <li class="breadcrumb-item active" aria-current="page">Shopping Cart</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <h1 class="h2 mb-1">Shopping Cart</h1>
            <p class="text-muted small mb-0">Review and manage your selected grocery items</p>
        </div>
        <?php if (!empty($cartItems)): ?>
            <form action="<?= baseUrl('cart-action.php') ?>" method="POST">
                <input type="hidden" name="action" value="clear">
                <button type="submit" class="btn btn-sm btn-outline-danger" data-confirm="Are you sure you want to empty your entire cart?">
                    <i class="bi bi-trash3 me-1"></i>Empty Cart
                </button>
            </form>
        <?php endif; ?>
    </div>

    <?php if (empty($cartItems)): ?>
        <!-- Empty Cart Display -->
        <div class="fc-empty-state bg-white border rounded-3 shadow-sm my-4 py-5">
            <div class="rounded-circle bg-light p-4 d-inline-flex align-items-center justify-content-center mb-3" style="width: 120px; height: 120px;">
                <i class="bi bi-cart-x text-muted" style="font-size: 3.5rem;"></i>
            </div>
            <h3 class="fw-bold mb-2">Your cart is empty.</h3>
            <p class="text-muted max-w-500 mx-auto mb-4">
                Looks like you haven't added any fresh groceries to your basket yet. Explore our fresh fruits, vegetables, dairy, and pantry items today!
            </p>
            <a href="<?= baseUrl('shop.php') ?>" class="btn btn-primary btn-lg px-4 shadow-sm">
                <i class="bi bi-basket me-2"></i>Start Shopping
            </a>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <!-- Items Table Area -->
            <div class="col-lg-8">
                <div class="card border rounded-3 shadow-sm bg-white overflow-hidden mb-3">
                    <div class="table-responsive">
                        <table class="table fc-cart-table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th scope="col" style="min-width: 250px;">Product</th>
                                    <th scope="col">Price</th>
                                    <th scope="col" style="min-width: 150px;">Quantity</th>
                                    <th scope="col">Subtotal</th>
                                    <th scope="col" class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cartItems as $item): ?>
                                    <tr>
                                        <!-- Product Image & Name -->
                                        <td>
                                            <div class="d-flex align-items-center gap-3">
                                                <img src="<?= getProductImageUrl($item['image']) ?>" alt="<?= escape($item['product_name']) ?>" class="rounded-2 border" style="width: 64px; height: 64px; object-fit: cover;">
                                                <div>
                                                    <span class="fc-category-tag d-block"><?= escape($item['category_name']) ?></span>
                                                    <a href="<?= baseUrl('product.php?id=' . $item['product_id']) ?>" class="fw-bold text-dark text-decoration-none">
                                                        <?= escape($item['product_name']) ?>
                                                    </a>
                                                    <div class="text-muted small mt-1">
                                                        Stock: <?= (int)$item['stock'] ?> <?= escape($item['unit']) ?>
                                                        <?php if ($item['stock'] < $item['quantity']): ?>
                                                            <span class="badge bg-danger ms-1">Stock Low!</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Unit Price -->
                                        <td>
                                            <span class="fw-bold"><?= formatPrice($item['price']) ?></span>
                                            <small class="text-muted d-block">/ <?= escape($item['unit']) ?></small>
                                        </td>

                                        <!-- Quantity Controls Form -->
                                        <td>
                                            <form action="<?= baseUrl('cart-action.php') ?>" method="POST" class="d-flex align-items-center gap-1">
                                                <input type="hidden" name="action" value="update">
                                                <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
                                                
                                                <div class="fc-qty-group">
                                                    <button type="button" class="fc-qty-btn" data-action="dec">-</button>
                                                    <input type="number" name="quantity" class="fc-qty-input" value="<?= $item['quantity'] ?>" min="1" max="<?= $item['stock'] ?>" onchange="this.form.submit()">
                                                    <button type="button" class="fc-qty-btn" data-action="inc">+</button>
                                                </div>
                                            </form>
                                        </td>

                                        <!-- Line Subtotal -->
                                        <td>
                                            <span class="fw-bolder text-dark fs-6"><?= formatPrice($item['subtotal']) ?></span>
                                        </td>

                                        <!-- Remove Item Action -->
                                        <td class="text-end">
                                            <form action="<?= baseUrl('cart-action.php') ?>" method="POST" class="d-inline">
                                                <input type="hidden" name="action" value="remove">
                                                <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger border-0" title="Remove item" data-confirm="Remove <?= escape($item['product_name']) ?> from cart?">
                                                    <i class="bi bi-trash3 fs-5"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center">
                    <a href="<?= baseUrl('shop.php') ?>" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-left me-1"></i>Continue Shopping
                    </a>
                    <span class="text-muted small"><?= $summary['total_qty'] ?> total items in cart</span>
                </div>
            </div>

            <!-- Order Summary Sidebar -->
            <div class="col-lg-4">
                <div class="fc-summary-box">
                    <h5 class="fw-bold mb-3 pb-2 border-bottom">Order Summary</h5>

                    <!-- Free delivery progress tip -->
                    <?php if (!$summary['is_free_delivery']): ?>
                        <div class="alert alert-warning py-2 px-3 small d-flex align-items-center gap-2 mb-3" role="alert">
                            <i class="bi bi-info-circle-fill fs-5"></i>
                            <div>
                                Add <strong><?= formatPrice($summary['amount_for_free']) ?></strong> more groceries to qualify for <strong>FREE Delivery!</strong>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-success py-2 px-3 small d-flex align-items-center gap-2 mb-3" role="alert">
                            <i class="bi bi-check-circle-fill fs-5"></i>
                            <div>You unlocked <strong>FREE Delivery</strong> on this order!</div>
                        </div>
                    <?php endif; ?>

                    <div class="fc-summary-item">
                        <span>Items Subtotal (<?= $summary['total_qty'] ?> items)</span>
                        <span class="fw-semibold text-dark"><?= formatPrice($summary['subtotal']) ?></span>
                    </div>

                    <div class="fc-summary-item">
                        <span>Delivery Charges</span>
                        <?php if ($summary['delivery_charge'] == 0): ?>
                            <span class="text-success fw-bold">FREE</span>
                        <?php else: ?>
                            <span class="fw-semibold text-dark"><?= formatPrice($summary['delivery_charge']) ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="fc-summary-total">
                        <span>Estimated Total</span>
                        <span class="text-success"><?= formatPrice($summary['final_total']) ?></span>
                    </div>

                    <div class="mt-4">
                        <a href="<?= baseUrl('checkout.php') ?>" class="btn btn-primary w-100 py-2 d-flex align-items-center justify-content-center gap-2 shadow-sm">
                            <i class="bi bi-shield-lock-fill"></i>
                            <span>Proceed to Checkout</span>
                        </a>
                    </div>

                    <div class="text-center mt-3">
                        <small class="text-muted"><i class="bi bi-lock me-1"></i>Secure 256-bit checkout encryption</small>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
