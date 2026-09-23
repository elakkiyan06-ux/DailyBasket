<?php
// login.php - Customer Login Screen
require_once __DIR__ . '/includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: ' . (isAdmin() ? baseUrl('admin/index.php') : baseUrl('index.php')));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please enter both your email address and password.';
    } else {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Set session
            $_SESSION['user'] = [
                'id'       => $user['id'],
                'name'     => $user['name'],
                'email'    => $user['email'],
                'phone'    => $user['phone'],
                'role'     => $user['role'],
                'address'  => $user['address'],
                'city'     => $user['city'],
                'pincode'  => $user['pincode'],
            ];

            // Merge guest cart into user database cart if any
            if (!empty($_SESSION['guest_cart'])) {
                $checkStmt = $db->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
                $updStmt   = $db->prepare("UPDATE cart SET quantity = ?, updated_at = NOW() WHERE id = ?");
                $insStmt   = $db->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");

                foreach ($_SESSION['guest_cart'] as $pId => $qty) {
                    $checkStmt->execute([$user['id'], $pId]);
                    $row = $checkStmt->fetch();
                    if ($row) {
                        $updStmt->execute([$row['quantity'] + $qty, $row['id']]);
                    } else {
                        $insStmt->execute([$user['id'], $pId, $qty]);
                    }
                }
                unset($_SESSION['guest_cart']);
            }

            setFlash('success', 'Welcome back, ' . htmlspecialchars($user['name']) . '!');

            // Redirect appropriately
            if ($user['role'] === 'admin') {
                header('Location: ' . baseUrl('admin/index.php'));
                exit;
            }

            $redirectUrl = $_SESSION['redirect_after_login'] ?? baseUrl('index.php');
            unset($_SESSION['redirect_after_login']);
            header('Location: ' . $redirectUrl);
            exit;
        } else {
            $error = 'Invalid email address or password. Please try again.';
        }
    }
}

$pageTitle = "Sign In";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card border rounded-3 p-4 p-md-5 bg-white shadow-sm">
                <div class="text-center mb-4">
                    <img src="<?= baseUrl('assets/images/dailybasket-logo.png') ?>" alt="DailyBasket" style="height: 60px; max-width: 200px; object-fit: contain;" class="mb-3">
                    <h3 class="fw-bold text-dark">Welcome Back</h3>
                    <p class="text-muted small">Sign in to your DailyBasket grocery account</p>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show small" role="alert">
                        <i class="bi bi-exclamation-circle-fill me-1"></i><?= escape($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?= baseUrl('login.php') ?>">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted"><i class="bi bi-envelope"></i></span>
                            <input type="email" name="email" class="form-control" required placeholder="name@example.com" value="<?= escape($_POST['email'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted"><i class="bi bi-lock"></i></span>
                            <input type="password" name="password" class="form-control" required placeholder="Enter your password">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2 shadow-sm fw-bold">
                        <i class="bi bi-box-arrow-in-right me-1"></i>Sign In
                    </button>
                </form>

                <div class="border-top pt-3 mt-4 text-center small text-muted">
                    Don't have an account? <a href="<?= baseUrl('register.php') ?>" class="fw-bold text-success">Create one now</a>
                </div>

                <!-- Quick Demo Login Hint Box -->
                <div class="mt-4 p-3 bg-light rounded-3 border small">
                    <div class="fw-bold text-dark mb-1"><i class="bi bi-info-circle text-primary me-1"></i>Demo Accounts:</div>
                    <div class="text-muted">
                        <strong>Customer:</strong> <code>customer@freshcart.com</code> / <code>Customer@123</code><br>
                        <strong>Administrator:</strong> <code>admin@freshcart.com</code> / <code>Admin@123</code>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
