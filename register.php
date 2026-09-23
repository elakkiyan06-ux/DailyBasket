<?php
// register.php - Customer Registration Page
require_once __DIR__ . '/includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: ' . baseUrl('index.php'));
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name            = sanitize($_POST['name'] ?? '');
    $email           = trim($_POST['email'] ?? '');
    $phone           = sanitize($_POST['phone'] ?? '');
    $password        = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Validations
    if (empty($name) || empty($email) || empty($phone) || empty($password)) {
        $errors[] = 'All required fields must be filled in.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters long.';
    }

    if ($password !== $confirmPassword) {
        $errors[] = 'Password and Confirm Password do not match.';
    }

    if (empty($errors)) {
        $db = getDB();

        // Check for duplicate email
        $checkStmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $checkStmt->execute([$email]);
        if ($checkStmt->fetch()) {
            $errors[] = 'An account with this email address already exists. Please sign in instead.';
        } else {
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $insStmt = $db->prepare("
                INSERT INTO users (name, email, phone, password, role, created_at)
                VALUES (?, ?, ?, ?, 'customer', NOW())
            ");
            $insStmt->execute([$name, $email, $phone, $hashedPassword]);
            $newUserId = (int)$db->lastInsertId();

            // Automatically log in newly registered customer
            $_SESSION['user'] = [
                'id'      => $newUserId,
                'name'    => $name,
                'email'   => $email,
                'phone'   => $phone,
                'role'    => 'customer',
                'address' => '',
                'city'    => '',
                'pincode' => '',
            ];

            // Merge guest cart if any
            if (!empty($_SESSION['guest_cart'])) {
                $insCart = $db->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
                foreach ($_SESSION['guest_cart'] as $pId => $qty) {
                    $insCart->execute([$newUserId, $pId, $qty]);
                }
                unset($_SESSION['guest_cart']);
            }

            setFlash('success', 'Your DailyBasket account has been created successfully! Happy shopping.');
            header('Location: ' . baseUrl('index.php'));
            exit;
        }
    }
}

$pageTitle = "Create Account";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-6">
            <div class="card border rounded-3 p-4 p-md-5 bg-white shadow-sm">
                <div class="text-center mb-4">
                    <img src="<?= baseUrl('assets/images/dailybasket-logo.png') ?>" alt="DailyBasket" style="height: 60px; max-width: 200px; object-fit: contain;" class="mb-3">
                    <h3 class="fw-bold text-dark">Join DailyBasket</h3>
                    <p class="text-muted small">Create an account for quick orders and easy delivery tracking</p>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger alert-dismissible fade show small" role="alert">
                        <ul class="mb-0">
                            <?php foreach ($errors as $e): ?>
                                <li><?= escape($e) ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?= baseUrl('register.php') ?>">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Full Name <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted"><i class="bi bi-person"></i></span>
                            <input type="text" name="name" class="form-control" required placeholder="e.g. Rahul Sharma" value="<?= escape($_POST['name'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Email Address <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted"><i class="bi bi-envelope"></i></span>
                            <input type="email" name="email" class="form-control" required placeholder="name@example.com" value="<?= escape($_POST['email'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Phone Number <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted"><i class="bi bi-telephone"></i></span>
                            <input type="tel" name="phone" class="form-control" required placeholder="10-digit mobile number" value="<?= escape($_POST['phone'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="bi bi-lock"></i></span>
                                <input type="password" name="password" class="form-control" minlength="6" required placeholder="Min 6 chars">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Confirm Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="bi bi-check2-circle"></i></span>
                                <input type="password" name="confirm_password" class="form-control" minlength="6" required placeholder="Repeat password">
                            </div>
                        </div>
                    </div>

                    <div class="form-check small text-muted mb-4">
                        <input class="form-check-input" type="checkbox" id="terms" required checked>
                        <label class="form-check-label" for="terms">
                            I agree to FreshCart's quality and terms of service.
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2 shadow-sm fw-bold">
                        <i class="bi bi-person-check me-1"></i>Create Account
                    </button>
                </form>

                <div class="border-top pt-3 mt-4 text-center small text-muted">
                    Already have an account? <a href="<?= baseUrl('login.php') ?>" class="fw-bold text-success">Sign In</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
