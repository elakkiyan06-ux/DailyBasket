</main>

<!-- Global Footer -->
<footer class="fc-footer">
    <div class="container">
        <div class="row g-4">
            <!-- Brand & Mission with DailyBasket Logo -->
            <div class="col-lg-4 col-md-6">
                <a class="fc-brand text-white mb-3 d-inline-block text-decoration-none" href="<?= baseUrl('index.php') ?>">
                    <div class="bg-white px-2 py-1 rounded-3 shadow-sm d-inline-block">
                        <img src="<?= baseUrl('assets/images/dailybasket-logo.png') ?>" alt="DailyBasket Logo" style="height: 54px; width: auto; object-fit: contain;">
                    </div>
                </a>
                <p class="text-secondary small pe-lg-4">
                    “Daily Essentials, Delivered Fresh.” Your daily groceries, fresh dairy, farm-fresh produce, and household staples delivered promptly right to your doorstep.
                </p>
                <div class="d-flex gap-2 flex-wrap text-secondary mt-3">
                    <span class="badge bg-dark border border-secondary"><i class="bi bi-shield-check text-success me-1"></i>100% Quality Assured</span>
                    <span class="badge bg-dark border border-secondary"><i class="bi bi-truck text-success me-1"></i>Fast Home Delivery</span>
                </div>
            </div>

            <!-- Quick Navigation -->
            <div class="col-lg-2 col-md-6 col-6">
                <h6 class="text-white fw-bold mb-3">Quick Links</h6>
                <ul class="list-unstyled small d-flex flex-column gap-2">
                    <li><a href="<?= baseUrl('index.php') ?>">Home</a></li>
                    <li><a href="<?= baseUrl('shop.php') ?>">Shop Groceries</a></li>
                    <li><a href="<?= baseUrl('cart.php') ?>">Shopping Cart</a></li>
                    <li><a href="<?= baseUrl('orders.php') ?>">My Orders</a></li>
                    <li><a href="<?= baseUrl('profile.php') ?>">My Profile</a></li>
                </ul>
            </div>

            <!-- Popular Categories -->
            <div class="col-lg-3 col-md-6 col-6">
                <h6 class="text-white fw-bold mb-3">Categories</h6>
                <ul class="list-unstyled small d-flex flex-column gap-2">
                    <li><a href="<?= baseUrl('shop.php?category=1') ?>">Fresh Fruits</a></li>
                    <li><a href="<?= baseUrl('shop.php?category=2') ?>">Organic Vegetables</a></li>
                    <li><a href="<?= baseUrl('shop.php?category=3') ?>">Dairy & Milk</a></li>
                    <li><a href="<?= baseUrl('shop.php?category=4') ?>">Rice, Atta & Grains</a></li>
                    <li><a href="<?= baseUrl('shop.php?category=5') ?>">Snacks & Beverages</a></li>
                </ul>
            </div>

            <!-- Store Info & Admin link -->
            <div class="col-lg-3 col-md-6">
                <h6 class="text-white fw-bold mb-3">Store Support</h6>
                <ul class="list-unstyled small text-secondary d-flex flex-column gap-2">
                    <li><i class="bi bi-geo-alt text-success me-2"></i>Market Yard Road, Mumbai, MH</li>
                    <li><i class="bi bi-telephone text-success me-2"></i>+91 98765 43210</li>
                    <li><i class="bi bi-envelope text-success me-2"></i>support@dailybasket.com</li>
                    <li><i class="bi bi-clock text-success me-2"></i>Mon - Sun: 7:00 AM – 10:00 PM</li>
                </ul>
                <div class="mt-3">
                    <a href="<?= baseUrl('admin/login.php') ?>" class="btn btn-sm btn-outline-secondary text-white-50">
                        <i class="bi bi-shield-lock me-1"></i>Admin Portal
                    </a>
                </div>
            </div>
        </div>

        <div class="fc-footer-bottom d-flex flex-column flex-md-row justify-content-between align-items-center small text-secondary">
            <p class="mb-2 mb-md-0">&copy; <?= date('Y') ?> DailyBasket – Grocery Ordering System. All rights reserved.</p>
            <div class="d-flex gap-3">
                <span class="text-secondary">PS29 - Simple Grocery Ordering System</span>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap 5.3 Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- Custom Script JS -->
<script src="<?= baseUrl('assets/js/script.js') ?>"></script>
</body>
</html>
