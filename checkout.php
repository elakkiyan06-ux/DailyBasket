<?php
// checkout.php - Checkout and Order Review Page
$pageTitle = "Checkout & Review";
require_once __DIR__ . '/includes/header.php';

// Authentication requirement: User must be signed in to place an order
if (!isLoggedIn()) {
    setFlash('warning', 'Please sign in or create an account to proceed with checkout.');
    $_SESSION['redirect_after_login'] = baseUrl('checkout.php');
    echo "<script>window.location.href = '" . baseUrl('login.php') . "';</script>";
    exit;
}

$cartItems = getCartItems();
$summary   = getCartSummary();

if (empty($cartItems)) {
    setFlash('warning', 'Your shopping cart is empty.');
    header('Location: ' . baseUrl('shop.php'));
    exit;
}

// Check if any product in cart has insufficient stock
$stockErrors = [];
foreach ($cartItems as $item) {
    if ($item['stock'] < $item['quantity']) {
        $stockErrors[] = "{$item['product_name']} only has {$item['stock']} in stock (you have {$item['quantity']} in cart).";
    }
}

$user = currentUser();
$db = getDB();

// Fetch latest user address details
$uStmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$uStmt->execute([$user['id']]);
$userData = $uStmt->fetch() ?: $user;
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="<?= baseUrl('index.php') ?>">Home</a></li>
            <li class="breadcrumb-item"><a href="<?= baseUrl('cart.php') ?>">Cart</a></li>
            <li class="breadcrumb-item active" aria-current="page">Checkout</li>
        </ol>
    </nav>

    <div class="mb-4 pb-2 border-bottom">
        <h1 class="h2 mb-1">Checkout &amp; Order Review</h1>
        <p class="text-muted small mb-0">Confirm delivery details and review items before placing your order</p>
    </div>

    <!-- Review Notice Alert -->
    <div class="alert alert-info d-flex align-items-center gap-3 mb-4 shadow-sm" role="alert">
        <i class="bi bi-info-circle-fill fs-3 text-info"></i>
        <div>
            <h6 class="fw-bold mb-1">Review your order before confirming.</h6>
            <div class="small">Please verify your delivery address and grocery items below. Once placed, your order is immediately queued for preparation.</div>
        </div>
    </div>

    <?php if (!empty($stockErrors)): ?>
        <div class="alert alert-danger shadow-sm mb-4">
            <h6 class="fw-bold mb-2"><i class="bi bi-exclamation-triangle-fill me-2"></i>Stock Notice:</h6>
            <ul class="mb-2">
                <?php foreach ($stockErrors as $err): ?>
                    <li><?= escape($err) ?></li>
                <?php endforeach; ?>
            </ul>
            <a href="<?= baseUrl('cart.php') ?>" class="btn btn-sm btn-outline-danger mt-1">Return to Cart &amp; Adjust Quantities</a>
        </div>
    <?php endif; ?>

    <form action="<?= baseUrl('order-process.php') ?>" method="POST" id="checkoutForm">
        <div class="row g-4">
            <!-- Delivery Address Form -->
            <div class="col-lg-7">
                <div class="card border rounded-3 p-4 bg-white shadow-sm mb-4">
                    <h5 class="fw-bold mb-3 pb-2 border-bottom">
                        <i class="bi bi-geo-alt-fill text-success me-2"></i>1. Delivery Address
                    </h5>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="delivery_name" class="form-control" required value="<?= escape($userData['name']) ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Phone Number <span class="text-danger">*</span></label>
                            <input type="tel" name="delivery_phone" class="form-control" required value="<?= escape($userData['phone']) ?>" placeholder="10-digit mobile number">
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-bold">Delivery Address (House / Flat / Street) <span class="text-danger">*</span></label>
                            <textarea name="delivery_address" class="form-control" rows="3" required placeholder="Apartment name, door number, street..."><?= escape($userData['address'] ?? '') ?></textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">City <span class="text-danger">*</span></label>
                            <input type="text" name="delivery_city" class="form-control" required value="<?= escape($userData['city'] ?? 'Mumbai') ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Pincode / Postal Code <span class="text-danger">*</span></label>
                            <input type="text" name="delivery_pincode" class="form-control" required value="<?= escape($userData['pincode'] ?? '') ?>" placeholder="e.g. 400001">
                        </div>

                        <div class="col-12">
                            <div class="form-check small text-muted">
                                <input class="form-check-input" type="checkbox" name="save_profile_address" value="1" id="saveAddr" checked>
                                <label class="form-check-label" for="saveAddr">
                                    Update and save this address to my profile for future orders
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border rounded-3 p-4 bg-white shadow-sm">
                    <h5 class="fw-bold mb-3 pb-2 border-bottom">
                        <i class="bi bi-credit-card-2-front-fill text-success me-2"></i>2. Payment Method
                    </h5>
                    <div class="p-3 border rounded-3 bg-light d-flex align-items-center gap-3">
                        <input class="form-check-input mt-0" type="radio" name="payment_method" id="cod" value="COD" checked>
                        <label class="form-check-label d-flex align-items-center gap-2 cursor-pointer w-100" for="cod">
                            <i class="bi bi-cash-stack fs-4 text-success"></i>
                            <div>
                                <strong class="d-block">Cash / UPI on Delivery</strong>
                                <small class="text-muted">Pay securely at your doorstep via cash or any UPI QR scanner.</small>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Order Review Sidebar -->
            <div class="col-lg-5">
                <div class="fc-summary-box">
                    <h5 class="fw-bold mb-3 pb-2 border-bottom">
                        <i class="bi bi-basket2-fill text-success me-2"></i>3. Order Items Review
                    </h5>

                    <!-- Items list -->
                    <div class="cart-items-preview mb-3" style="max-height: 280px; overflow-y: auto;">
                        <?php foreach ($cartItems as $item): ?>
                            <div class="d-flex align-items-center justify-content-between py-2 border-bottom">
                                <div class="d-flex align-items-center gap-2">
                                    <img src="<?= getProductImageUrl($item['image']) ?>" alt="<?= escape($item['product_name']) ?>" class="rounded" style="width: 44px; height: 44px; object-fit: cover;">
                                    <div>
                                        <div class="fw-bold small"><?= escape($item['product_name']) ?></div>
                                        <div class="text-muted small">
                                            <?= formatPrice($item['price']) ?> &times; <?= $item['quantity'] ?> <?= escape($item['unit']) ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="fw-bold small text-dark">
                                    <?= formatPrice($item['subtotal']) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Breakdown -->
                    <div class="fc-summary-item">
                        <span>Items Subtotal</span>
                        <span class="fw-semibold text-dark"><?= formatPrice($summary['subtotal']) ?></span>
                    </div>

                    <div class="fc-summary-item">
                        <span>Delivery Fee</span>
                        <?php if ($summary['delivery_charge'] == 0): ?>
                            <span class="text-success fw-bold">FREE</span>
                        <?php else: ?>
                            <span class="fw-semibold text-dark"><?= formatPrice($summary['delivery_charge']) ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="fc-summary-total">
                        <span>Total Payable</span>
                        <span class="text-success"><?= formatPrice($summary['final_total']) ?></span>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary w-100 py-3 d-flex align-items-center justify-content-center gap-2 fs-6 shadow-sm" <?= !empty($stockErrors) ? 'disabled' : '' ?>>
                            <i class="bi bi-check2-circle fs-5"></i>
                            <span>Place Grocery Order</span>
                        </button>
                    </div>

                    <div class="text-center mt-3 small text-muted">
                        By placing this order, you agree to DailyBasket's quality and delivery terms.
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
