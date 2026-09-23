<?php
// admin/settings.php - Administrator Store & Profile Settings
$adminTitle = "System Settings";
require_once __DIR__ . '/includes/admin-header.php';

$db = getDB();
$user = currentUser();
$errors = [];

// Handle Admin Password Change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_admin_password') {
    $currentPass = $_POST['current_password'] ?? '';
    $newPass     = $_POST['new_password'] ?? '';
    $confirmPass = $_POST['confirm_password'] ?? '';

    if (strlen($newPass) < 6) {
        $errors[] = "New password must be at least 6 characters long.";
    } elseif ($newPass !== $confirmPass) {
        $errors[] = "New passwords do not match.";
    } else {
        $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user['id']]);
        $hash = $stmt->fetchColumn();

        if (!password_verify($currentPass, $hash)) {
            $errors[] = "Current password is incorrect.";
        } else {
            $newHash = password_hash($newPass, PASSWORD_DEFAULT);
            $upd = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
            $upd->execute([$newHash, $user['id']]);

            setFlash('success', 'Admin password changed successfully.');
            header('Location: ' . baseUrl('admin/settings.php'));
            exit;
        }
    }
}

// Handle Admin Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_admin_profile') {
    $name  = sanitize($_POST['name'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');

    if (empty($name) || empty($phone)) {
        $errors[] = "Name and Phone number are required.";
    } else {
        $upd = $db->prepare("UPDATE users SET name = ?, phone = ? WHERE id = ?");
        $upd->execute([$name, $phone, $user['id']]);

        $_SESSION['user']['name']  = $name;
        $_SESSION['user']['phone'] = $phone;

        setFlash('success', 'Admin profile updated.');
        header('Location: ' . baseUrl('admin/settings.php'));
        exit;
    }
}

// Fetch admin user
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user['id']]);
$adminUser = $stmt->fetch();
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h4 class="fw-bold mb-1">Store &amp; Admin Settings</h4>
        <p class="text-muted small mb-0">Manage platform parameters, operational rules, and administrator credentials</p>
    </div>
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

<div class="row g-4">
    <!-- Store Operational Policy Card -->
    <div class="col-lg-6">
        <div class="admin-card mb-4">
            <h5 class="fw-bold mb-3 pb-2 border-bottom">
                <i class="bi bi-shop text-success me-2"></i>Store Configurations
            </h5>

            <div class="mb-3">
                <label class="form-label small fw-bold text-muted">Platform Name</label>
                <input type="text" class="form-control" value="DailyBasket – Grocery Ordering System" readonly>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-bold text-muted">Tagline</label>
                <input type="text" class="form-control" value="Daily Essentials, Delivered Fresh" readonly>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label small fw-bold text-muted">Flat Delivery Charge</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light">₹</span>
                        <input type="text" class="form-control" value="30.00" readonly>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold text-muted">Free Delivery Threshold</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light">₹</span>
                        <input type="text" class="form-control" value="500.00" readonly>
                    </div>
                </div>
            </div>

            <div class="alert alert-info py-2 small mb-0">
                <i class="bi bi-info-circle me-1"></i>Delivery fee rule: Free shipping applies automatically to all orders of <strong>₹500.00 or more</strong>.
            </div>
        </div>

        <!-- Admin Profile Info -->
        <div class="admin-card">
            <h5 class="fw-bold mb-3 pb-2 border-bottom">
                <i class="bi bi-person-badge text-success me-2"></i>Admin Profile Information
            </h5>

            <form method="POST" action="<?= baseUrl('admin/settings.php') ?>">
                <input type="hidden" name="action" value="update_admin_profile">

                <div class="mb-3">
                    <label class="form-label small fw-bold">Admin Email</label>
                    <input type="email" class="form-control bg-light" value="<?= escape($adminUser['email']) ?>" readonly>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required value="<?= escape($adminUser['name']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Phone Number <span class="text-danger">*</span></label>
                        <input type="tel" name="phone" class="form-control" required value="<?= escape($adminUser['phone']) ?>">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="bi bi-check2 me-1"></i>Update Profile
                </button>
            </form>
        </div>
    </div>

    <!-- Security & Password Card -->
    <div class="col-lg-6">
        <div class="admin-card mb-4">
            <h5 class="fw-bold mb-3 pb-2 border-bottom">
                <i class="bi bi-key text-success me-2"></i>Change Administrator Password
            </h5>

            <form method="POST" action="<?= baseUrl('admin/settings.php') ?>">
                <input type="hidden" name="action" value="change_admin_password">

                <div class="mb-3">
                    <label class="form-label small fw-bold">Current Password <span class="text-danger">*</span></label>
                    <input type="password" name="current_password" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">New Password <span class="text-danger">*</span></label>
                    <input type="password" name="new_password" class="form-control" minlength="6" required placeholder="Min 6 characters">
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-bold">Confirm New Password <span class="text-danger">*</span></label>
                    <input type="password" name="confirm_password" class="form-control" minlength="6" required>
                </div>

                <button type="submit" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-shield-check me-1"></i>Update Password
                </button>
            </form>
        </div>

        <div class="admin-card bg-light border">
            <h6 class="fw-bold mb-2 small"><i class="bi bi-database text-success me-1"></i>Database Info</h6>
            <ul class="list-unstyled small text-muted mb-0">
                <li><strong>Database Name:</strong> <code>freshcart_db</code></li>
                <li><strong>Host:</strong> <code>localhost:3306</code></li>
                <li><strong>Collation:</strong> <code>utf8mb4_unicode_ci</code></li>
                <li><strong>Server:</strong> Apache / MariaDB (XAMPP)</li>
            </ul>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
