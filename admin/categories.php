<?php
// admin/categories.php - Administrator Category Management
$adminTitle = "Categories Management";
require_once __DIR__ . '/includes/admin-header.php';

$db = getDB();
$errors = [];

// Handle Add Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_category') {
    $name = sanitize($_POST['name'] ?? '');
    $desc = sanitize($_POST['description'] ?? '');

    if (empty($name)) {
        $errors[] = 'Category name is required.';
    } else {
        $check = $db->prepare("SELECT id FROM categories WHERE name = ?");
        $check->execute([$name]);
        if ($check->fetch()) {
            $errors[] = 'A category with this name already exists.';
        } else {
            $ins = $db->prepare("INSERT INTO categories (name, description, created_at) VALUES (?, ?, NOW())");
            $ins->execute([$name, $desc]);
            setFlash('success', 'Category "' . htmlspecialchars($name) . '" added successfully.');
            header('Location: ' . baseUrl('admin/categories.php'));
            exit;
        }
    }
}

// Handle Update Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_category') {
    $catId = (int)$_POST['category_id'];
    $name  = sanitize($_POST['name'] ?? '');
    $desc  = sanitize($_POST['description'] ?? '');

    if (empty($name)) {
        $errors[] = 'Category name is required.';
    } else {
        $upd = $db->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
        $upd->execute([$name, $desc, $catId]);
        setFlash('success', 'Category updated successfully.');
        header('Location: ' . baseUrl('admin/categories.php'));
        exit;
    }
}

// Handle Delete Category
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    
    // Check if products exist in category
    $pCheck = $db->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
    $pCheck->execute([$delId]);
    $pCount = (int)$pCheck->fetchColumn();

    if ($pCount > 0) {
        setFlash('danger', 'Cannot delete this category because it contains ' . $pCount . ' products. Reassign or delete those products first.');
    } else {
        $delStmt = $db->prepare("DELETE FROM categories WHERE id = ?");
        $delStmt->execute([$delId]);
        setFlash('success', 'Category deleted successfully.');
    }
    header('Location: ' . baseUrl('admin/categories.php'));
    exit;
}

// Fetch all categories with product counts
$stmt = $db->query("
    SELECT c.*, COUNT(p.id) as product_count 
    FROM categories c 
    LEFT JOIN products p ON c.id = p.category_id 
    GROUP BY c.id 
    ORDER BY c.name ASC
");
$categories = $stmt->fetchAll();
?>

<div class="row g-4">
    <!-- Left Column: Add Category Form -->
    <div class="col-lg-4">
        <div class="admin-card">
            <h5 class="fw-bold mb-3 pb-2 border-bottom">
                <i class="bi bi-plus-circle text-success me-2"></i>Add New Category
            </h5>

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

            <form method="POST" action="<?= baseUrl('admin/categories.php') ?>">
                <input type="hidden" name="action" value="add_category">

                <div class="mb-3">
                    <label class="form-label small fw-bold">Category Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" required placeholder="e.g. Organic Herbs">
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-bold">Description</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="Short summary of items in this category..."></textarea>
                </div>

                <button type="submit" class="btn btn-primary btn-sm w-100 py-2">
                    <i class="bi bi-folder-plus me-1"></i>Create Category
                </button>
            </form>
        </div>
    </div>

    <!-- Right Column: Categories List Table -->
    <div class="col-lg-8">
        <div class="admin-card p-0 overflow-hidden">
            <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0"><i class="bi bi-grid-fill text-success me-2"></i>Existing Categories (<?= count($categories) ?>)</h6>
                <span class="badge bg-white text-dark border">Catalog Taxonomies</span>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="table-light">
                        <tr>
                            <th>Category Name</th>
                            <th>Description</th>
                            <th>Products</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $cat): ?>
                            <tr>
                                <td>
                                    <strong class="text-dark d-block"><?= escape($cat['name']) ?></strong>
                                    <span class="text-muted" style="font-size: 0.75rem;">ID: #<?= $cat['id'] ?></span>
                                </td>
                                <td>
                                    <span class="text-muted text-truncate d-inline-block" style="max-width: 260px;">
                                        <?= escape($cat['description']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border fw-bold"><?= (int)$cat['product_count'] ?> items</span>
                                </td>
                                <td class="text-end">
                                    <!-- Edit Button (triggers modal) -->
                                    <button type="button" class="btn btn-sm btn-outline-primary py-1 px-2 me-1" data-bs-toggle="modal" data-bs-target="#editModal<?= $cat['id'] ?>">
                                        <i class="bi bi-pencil"></i>
                                    </button>

                                    <!-- Delete Button -->
                                    <a href="<?= baseUrl('admin/categories.php?delete=' . $cat['id']) ?>" class="btn btn-sm btn-outline-danger py-1 px-2" data-confirm="Delete category '<?= escape($cat['name']) ?>'?">
                                        <i class="bi bi-trash"></i>
                                    </a>

                                    <!-- Edit Modal -->
                                    <div class="modal fade" id="editModal<?= $cat['id'] ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog text-start">
                                            <div class="modal-content">
                                                <form method="POST" action="<?= baseUrl('admin/categories.php') ?>">
                                                    <input type="hidden" name="action" value="edit_category">
                                                    <input type="hidden" name="category_id" value="<?= $cat['id'] ?>">

                                                    <div class="modal-header">
                                                        <h6 class="modal-title fw-bold">Edit Category: <?= escape($cat['name']) ?></h6>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label class="form-label small fw-bold">Category Name <span class="text-danger">*</span></label>
                                                            <input type="text" name="name" class="form-control" required value="<?= escape($cat['name']) ?>">
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label small fw-bold">Description</label>
                                                            <textarea name="description" class="form-control" rows="3"><?= escape($cat['description']) ?></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-sm btn-primary">Save Changes</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
