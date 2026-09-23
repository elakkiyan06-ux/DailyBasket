<?php
// admin/login.php - Dedicated Administrator Portal Login
require_once __DIR__ . '/../includes/functions.php';

if (isAdmin()) {
    header('Location: ' . baseUrl('admin/index.php'));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please provide both email and password.';
    } else {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if ($user['role'] !== 'admin') {
                $error = 'Access denied. You do not have administrator permissions.';
            } else {
                $_SESSION['user'] = [
                    'id'      => $user['id'],
                    'name'    => $user['name'],
                    'email'   => $user['email'],
                    'phone'   => $user['phone'],
                    'role'    => $user['role'],
                    'address' => $user['address'],
                    'city'    => $user['city'],
                    'pincode' => $user['pincode'],
                ];
                setFlash('success', 'Welcome to DailyBasket Admin, ' . htmlspecialchars($user['name']));
                header('Location: ' . baseUrl('admin/index.php'));
                exit;
            }
        } else {
            $error = 'Invalid credentials. Please verify your email and password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Sign In - DailyBasket</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= baseUrl('assets/css/style.css') ?>">
    <style>
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4">
            <div class="card border-0 rounded-4 p-4 p-md-5 bg-white shadow-lg">
                <div class="text-center mb-4">
                    <img src="<?= baseUrl('assets/images/dailybasket-logo.png') ?>" alt="DailyBasket Logo" style="height: 64px; width: auto; object-fit: contain;" class="mb-3">
                    <h3 class="fw-bold text-dark mb-1">Admin Portal</h3>
                    <p class="text-muted small">DailyBasket Grocery Management</p>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger small py-2" role="alert">
                        <i class="bi bi-exclamation-circle-fill me-1"></i><?= escape($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?= baseUrl('admin/login.php') ?>">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Admin Email</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted"><i class="bi bi-envelope"></i></span>
                            <input type="email" name="email" class="form-control" required placeholder="admin@freshcart.com" value="<?= escape($_POST['email'] ?? 'admin@freshcart.com') ?>">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted">Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted"><i class="bi bi-lock"></i></span>
                            <input type="password" name="password" class="form-control" required placeholder="••••••••" value="Admin@123">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2 fw-bold shadow-sm">
                        <i class="bi bi-box-arrow-in-right me-1"></i>Sign In to Dashboard
                    </button>
                </form>

                <div class="text-center mt-4 pt-3 border-top small text-muted">
                    <a href="<?= baseUrl('index.php') ?>" class="text-decoration-none text-secondary">
                        <i class="bi bi-arrow-left me-1"></i>Return to Storefront
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
