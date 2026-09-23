<?php
// profile.php - Customer Profile Management Page
$pageTitle = "My Profile";
require_once __DIR__ . '/includes/auth.php';

$user = currentUser();
$db = getDB();
$errors = [];

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'update_profile';

    if ($action === 'update_profile') {
        $name    = sanitize($_POST['name'] ?? '');
        $phone   = sanitize($_POST['phone'] ?? '');
        $address = sanitize($_POST['address'] ?? '');
        $city    = sanitize($_POST['city'] ?? '');
        $pincode = sanitize($_POST['pincode'] ?? '');

        if (empty($name) || empty($phone)) {
            $errors[] = "Name and Phone number are required.";
        }

        if (empty($errors)) {
            $stmt = $db->prepare("
                UPDATE users 
                SET name = ?, phone = ?, address = ?, city = ?, pincode = ? 
                WHERE id = ?
            ");
            $stmt->execute([$name, $phone, $address, $city, $pincode, $user['id']]);

            // Update session cache
            $_SESSION['user']['name']    = $name;
            $_SESSION['user']['phone']   = $phone;
            $_SESSION['user']['address'] = $address;
            $_SESSION['user']['city']    = $city;
            $_SESSION['user']['pincode'] = $pincode;

            setFlash('success', 'Profile details updated successfully.');
            header('Location: ' . baseUrl('profile.php'));
            exit;
        }
    } elseif ($action === 'change_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword     = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (strlen($newPassword) < 6) {
            $errors[] = "New password must be at least 6 characters long.";
        } elseif ($newPassword !== $confirmPassword) {
            $errors[] = "New passwords do not match.";
        } else {
            // Verify current password
            $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$user['id']]);
            $hash = $stmt->fetchColumn();

            if (!password_verify($currentPassword, $hash)) {
                $errors[] = "Current password is incorrect.";
            } else {
                $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
                $upd = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                $upd->execute([$newHash, $user['id']]);

                setFlash('success', 'Password changed successfully.');
                header('Location: ' . baseUrl('profile.php'));
                exit;
            }
        }
    }
}

// Fetch fresh user data
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user['id']]);
$userData = $stmt->fetch();

require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="<?= baseUrl('index.php') ?>">Home</a></li>
            <li class="breadcrumb-item active" aria-current="page">My Profile</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <h1 class="h2 mb-1">My Profile</h1>
            <p class="text-muted small mb-0">Manage your contact details and default delivery address</p>
        </div>
        <a href="<?= baseUrl('logout.php') ?>" class="btn btn-outline-danger btn-sm">
            <i class="bi bi-box-arrow-right me-1"></i>Sign Out
        </a>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                <?php foreach ($errors as $e): ?>
                    <li><?= escape($e) ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Personal & Address Info Form -->
        <div class="col-lg-7">
            <div class="card border rounded-3 p-4 bg-white shadow-sm mb-4">
                <h5 class="fw-bold mb-3 pb-2 border-bottom">
                    <i class="bi bi-person-lines-fill text-success me-2"></i>Personal &amp; Delivery Information
                </h5>

                <form method="POST" action="<?= baseUrl('profile.php') ?>">
                    <input type="hidden" name="action" value="update_profile">

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Email Address</label>
                        <input type="email" class="form-control bg-light" value="<?= escape($userData['email']) ?>" readonly disabled>
                        <small class="text-muted">Registered account email cannot be changed.</small>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required value="<?= escape($userData['name']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Phone Number <span class="text-danger">*</span></label>
                            <input type="tel" name="phone" class="form-control" required value="<?= escape($userData['phone']) ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Default Delivery Address</label>
                        <textarea name="address" class="form-control" rows="3" placeholder="Apartment / House / Street details"><?= escape($userData['address'] ?? '') ?></textarea>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">City</label>
                            <input type="text" name="city" class="form-control" value="<?= escape($userData['city'] ?? '') ?>" placeholder="e.g. Mumbai">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Pincode</label>
                            <input type="text" name="pincode" class="form-control" value="<?= escape($userData['pincode'] ?? '') ?>" placeholder="e.g. 400001">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check2-circle me-1"></i>Save Changes
                    </button>
                </form>
            </div>
        </div>

        <!-- Security / Change Password -->
        <div class="col-lg-5">
            <div class="card border rounded-3 p-4 bg-white shadow-sm mb-4">
                <h5 class="fw-bold mb-3 pb-2 border-bottom">
                    <i class="bi bi-shield-lock-fill text-success me-2"></i>Change Password
                </h5>

                <form method="POST" action="<?= baseUrl('profile.php') ?>">
                    <input type="hidden" name="action" value="change_password">

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Current Password <span class="text-danger">*</span></label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">New Password <span class="text-danger">*</span></label>
                        <input type="password" name="new_password" class="form-control" minlength="6" required placeholder="Minimum 6 characters">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Confirm New Password <span class="text-danger">*</span></label>
                        <input type="password" name="confirm_password" class="form-control" minlength="6" required>
                    </div>

                    <button type="submit" class="btn btn-outline-primary">
                        <i class="bi bi-key me-1"></i>Update Password
                    </button>
                </form>
            </div>

            <div class="card border rounded-3 p-3 bg-light text-muted small">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <i class="bi bi-calendar3"></i>
                    <span>Member since: <strong><?= date('F Y', strtotime($userData['created_at'])) ?></strong></span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-person-badge"></i>
                    <span>Role: <strong class="text-capitalize"><?= escape($userData['role']) ?></strong></span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
