<?php
// admin/edit-product.php - Edit Existing Grocery Product
$adminTitle = "Edit Product";
require_once __DIR__ . '/includes/admin-header.php';

$productId = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
$db = getDB();
$errors = [];

// Fetch product
$stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    setFlash('danger', 'Product not found.');
    header('Location: ' . baseUrl('admin/products.php'));
    exit;
}

$categories = $db->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = sanitize($_POST['name'] ?? '');
    $categoryId  = (int)($_POST['category_id'] ?? 0);
    $description = sanitize($_POST['description'] ?? '');
    $price       = (float)($_POST['price'] ?? 0.00);
    $stock       = max(0, (int)($_POST['stock'] ?? 0));
    $unit        = sanitize($_POST['unit'] ?? 'kg');
    $image       = trim($_POST['image'] ?? '');
    $status      = sanitize($_POST['status'] ?? 'active');

    if (empty($name)) {
        $errors[] = "Product name cannot be empty.";
    }
    if ($categoryId <= 0) {
        $errors[] = "Please select a valid category.";
    }
    if ($price <= 0) {
        $errors[] = "Price must be greater than zero.";
    }

    // Handle new uploaded image file replacement if provided
    if (isset($_FILES['product_image_file']) && $_FILES['product_image_file']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['product_image_file']['tmp_name'];
        $fileName    = $_FILES['product_image_file']['name'];
        $fileSize    = $_FILES['product_image_file']['size'];
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        if (!in_array($ext, $allowedExtensions)) {
            $errors[] = "Only JPG, PNG, WEBP, or GIF image files are allowed.";
        } elseif ($fileSize > 5 * 1024 * 1024) {
            $errors[] = "Product image file size cannot exceed 5MB.";
        } else {
            $cleanBase = preg_replace('/[^a-z0-9_-]/i', '_', strtolower($name));
            $cleanBase = trim($cleanBase, '_');
            if (empty($cleanBase)) {
                $cleanBase = 'product_' . $productId;
            }
            $newFileName = $cleanBase . '_' . time() . '.' . $ext;
            $uploadTargetDir = dirname(__DIR__) . '/assets/images/products/';
            if (!is_dir($uploadTargetDir)) {
                mkdir($uploadTargetDir, 0755, true);
            }
            $targetPath = $uploadTargetDir . $newFileName;
            if (move_uploaded_file($fileTmpPath, $targetPath)) {
                $image = 'assets/images/products/' . $newFileName;
            } else {
                $errors[] = "Failed to save the replaced image file.";
            }
        }
    }

    // If no new image specified, retain the existing product image
    if (empty($image)) {
        $image = !empty($product['image']) ? $product['image'] : 'assets/images/products/placeholder.jpg';
    }

    // Auto-update status if restocked or depleted
    if ($stock <= 0 && $status === 'active') {
        $status = 'out_of_stock';
    } elseif ($stock > 0 && $status === 'out_of_stock') {
        $status = 'active';
    }

    if (empty($errors)) {
        $updStmt = $db->prepare("
            UPDATE products 
            SET category_id = ?, name = ?, description = ?, price = ?, stock = ?, unit = ?, image = ?, status = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $updStmt->execute([
            $categoryId,
            $name,
            $description,
            $price,
            $stock,
            $unit,
            $image,
            $status,
            $productId
        ]);

        setFlash('success', 'Product "' . htmlspecialchars($name) . '" updated successfully.');
        header('Location: ' . baseUrl('admin/products.php'));
        exit;
    }
}
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="admin-card">
            <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
                <h5 class="fw-bold mb-0">
                    <i class="bi bi-pencil-square text-success me-2"></i>Edit Product: <?= escape($product['name']) ?>
                </h5>
                <a href="<?= baseUrl('admin/products.php') ?>" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Back to Products
                </a>
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

            <form method="POST" action="<?= baseUrl('admin/edit-product.php?id=' . $productId) ?>" enctype="multipart/form-data">
                <div class="row g-3 mb-3">
                    <div class="col-md-8">
                        <label class="form-label small fw-bold">Product Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required value="<?= escape($_POST['name'] ?? $product['name']) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Category <span class="text-danger">*</span></label>
                        <select name="category_id" class="form-select" required>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= (($product['category_id'] == $cat['id'])) ? 'selected' : '' ?>>
                                    <?= escape($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Description</label>
                    <textarea name="description" class="form-control" rows="3"><?= escape($_POST['description'] ?? $product['description']) ?></textarea>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Price (₹) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light">₹</span>
                            <input type="number" step="0.01" min="0.01" name="price" class="form-control" required value="<?= escape($_POST['price'] ?? $product['price']) ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Stock Quantity <span class="text-danger">*</span></label>
                        <input type="number" min="0" name="stock" class="form-control" required value="<?= escape($_POST['stock'] ?? $product['stock']) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Unit <span class="text-danger">*</span></label>
                        <select name="unit" class="form-select" required>
                            <?php 
                            $units = ['kg', 'gram', '500g', '250g', 'liter', '500ml', 'ml', 'pack', 'piece', 'dozen', 'bottle'];
                            $currentUnit = $_POST['unit'] ?? $product['unit'];
                            foreach ($units as $u):
                            ?>
                                <option value="<?= $u ?>" <?= $currentUnit === $u ? 'selected' : '' ?>><?= $u ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Product Image Replacement / Upload Section -->
                <div class="mb-3 p-3 bg-light rounded-3 border">
                    <label class="form-label small fw-bold d-block"><i class="bi bi-image text-success me-1"></i>Replace Product Image</label>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-semibold">Upload New Image File</label>
                            <input type="file" name="product_image_file" class="form-control" accept="image/jpeg,image/png,image/webp,image/gif">
                            <small class="text-muted d-block mt-1">Upload exact product photo (JPG, PNG, WEBP)</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-semibold">Or Image Path / Direct URL</label>
                            <input type="text" name="image" class="form-control" value="<?= escape($_POST['image'] ?? $product['image']) ?>">
                            <small class="text-muted d-block mt-1">Leave as-is to keep current image</small>
                        </div>
                    </div>
                    
                    <div class="mt-3 p-2 bg-white rounded border d-flex align-items-center gap-3">
                        <img src="<?= escape(getProductImageUrl($product['image'])) ?>" alt="<?= escape($product['name']) ?>" class="rounded border p-1" style="width: 56px; height: 56px; object-fit: contain; background: #fff;">
                        <div>
                            <div class="small fw-bold text-dark">Current Product Image</div>
                            <div class="text-muted font-monospace" style="font-size: 0.75rem;"><?= escape($product['image']) ?></div>
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-bold">Status</label>
                    <select name="status" class="form-select">
                        <option value="active" <?= ($product['status'] === 'active') ? 'selected' : '' ?>>Active (Visible on shop)</option>
                        <option value="inactive" <?= ($product['status'] === 'inactive') ? 'selected' : '' ?>>Inactive (Hidden from shop)</option>
                        <option value="out_of_stock" <?= ($product['status'] === 'out_of_stock') ? 'selected' : '' ?>>Out of Stock</option>
                    </select>
                </div>

                <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                    <a href="<?= baseUrl('admin/products.php?delete=' . $productId) ?>" class="btn btn-outline-danger btn-sm" data-confirm="Delete this product permanently?">
                        <i class="bi bi-trash me-1"></i>Delete Product
                    </a>
                    <div class="d-flex gap-2">
                        <a href="<?= baseUrl('admin/products.php') ?>" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-check2-circle me-1"></i>Update Product
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
