<?php
// includes/functions.php - Common utility functions for DailyBasket

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

// Delivery charges configuration
define('FREE_DELIVERY_THRESHOLD', 500.00);
define('FLAT_DELIVERY_CHARGE', 30.00);

/**
 * Sanitize user text input
 */
function sanitize(string $input): string {
    return trim(filter_var($input, FILTER_DEFAULT));
}

/**
 * Escape HTML output to prevent XSS
 */
function escape(?string $str): string {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Format currency into Indian Rupees format (₹)
 */
function formatPrice(float|int|string $amount): string {
    return '₹' . number_format((float)$amount, 2);
}

/**
 * Generate absolute URL for project assets and links (works in localhost subfolders & cloud root)
 */
function baseUrl(string $path = ''): string {
    static $base = null;
    if ($base === null) {
        $envBase = getenv('APP_BASE_URL');
        if ($envBase !== false && $envBase !== '') {
            $base = rtrim($envBase, '/');
        } elseif (php_sapi_name() === 'cli') {
            $base = '/freshcart';
        } else {
            $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
            $cleanDir = str_replace('\\', '/', $scriptDir);
            if (str_ends_with($cleanDir, '/admin')) {
                $cleanDir = substr($cleanDir, 0, -6);
            }
            $base = ($cleanDir === '/' || $cleanDir === '.' || $cleanDir === '') ? '' : $cleanDir;
        }
    }
    $cleanPath = ltrim($path, '/');
    return ($base === '' ? '' : $base) . '/' . $cleanPath;
}

/**
 * Get accurate product image URL with fallback to placeholder
 */
function getProductImageUrl(?string $image): string {
    if (empty($image)) {
        return baseUrl('assets/images/products/placeholder.jpg');
    }
    // Remote URLs
    if (str_starts_with($image, 'http://') || str_starts_with($image, 'https://')) {
        return $image;
    }
    // Local relative paths
    $clean = ltrim($image, '/');
    $localFile = __DIR__ . '/../' . $clean;
    if (file_exists($localFile)) {
        return baseUrl($clean) . '?v=' . filemtime($localFile);
    }
    // If just a filename was stored
    $inProductsDir = __DIR__ . '/../assets/images/products/' . basename($image);
    if (file_exists($inProductsDir)) {
        return baseUrl('assets/images/products/' . basename($image)) . '?v=' . filemtime($inProductsDir);
    }
    return baseUrl('assets/images/products/placeholder.jpg');
}

/**
 * Set flash session message
 */
function setFlash(string $type, string $message): void {
    $_SESSION['flash_messages'][] = [
        'type' => $type, // success, danger, warning, info
        'message' => $message,
    ];
}

/**
 * Retrieve and clear flash session messages
 */
function getFlashMessages(): array {
    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);
    return $messages;
}

/**
 * Check if current logged in user is admin
 */
function isAdmin(): bool {
    return isset($_SESSION['user']) && (($_SESSION['user']['role'] ?? '') === 'admin');
}

/**
 * Check if customer is logged in
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user']);
}

/**
 * Get current logged in user data
 */
function currentUser(): ?array {
    return $_SESSION['user'] ?? null;
}

/**
 * Get total number of items in cart
 */
function getCartCount(): int {
    if (!isLoggedIn()) {
        $guestCart = $_SESSION['guest_cart'] ?? [];
        return array_sum($guestCart);
    }

    $db = getDB();
    $userId = (int)$_SESSION['user']['id'];
    $stmt = $db->prepare("SELECT SUM(quantity) as total_qty FROM cart WHERE user_id = ?");
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    return (int)($row['total_qty'] ?? 0);
}

/**
 * Retrieve all items in the user's cart with current product details
 */
function getCartItems(): array {
    $db = getDB();

    if (isLoggedIn()) {
        $userId = (int)$_SESSION['user']['id'];
        $stmt = $db->prepare("
            SELECT 
                c.id AS cart_id,
                c.user_id,
                c.product_id,
                c.quantity,
                p.name AS product_name,
                p.price,
                p.stock,
                p.unit,
                p.image,
                p.status AS product_status,
                cat.name AS category_name,
                (c.quantity * p.price) AS subtotal
            FROM cart c
            JOIN products p ON c.product_id = p.id
            JOIN categories cat ON p.category_id = cat.id
            WHERE c.user_id = ?
            ORDER BY c.created_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    } else {
        // Guest session cart
        $guestCart = $_SESSION['guest_cart'] ?? [];
        if (empty($guestCart)) {
            return [];
        }
        $items = [];
        $placeholders = implode(',', array_fill(0, count($guestCart), '?'));
        $stmt = $db->prepare("
            SELECT 
                p.id AS product_id,
                p.name AS product_name,
                p.price,
                p.stock,
                p.unit,
                p.image,
                p.status AS product_status,
                cat.name AS category_name
            FROM products p
            JOIN categories cat ON p.category_id = cat.id
            WHERE p.id IN ($placeholders)
        ");
        $stmt->execute(array_keys($guestCart));
        $products = $stmt->fetchAll();

        foreach ($products as $p) {
            $qty = (int)($guestCart[$p['product_id']] ?? 1);
            $items[] = [
                'cart_id'        => 'guest_' . $p['product_id'],
                'user_id'        => 0,
                'product_id'     => $p['product_id'],
                'quantity'       => $qty,
                'product_name'   => $p['product_name'],
                'price'          => $p['price'],
                'stock'          => $p['stock'],
                'unit'           => $p['unit'],
                'image'          => $p['image'],
                'product_status' => $p['product_status'],
                'category_name'  => $p['category_name'],
                'subtotal'       => $qty * (float)$p['price'],
            ];
        }
        return $items;
    }
}

/**
 * Calculate totals: subtotal, delivery fee, final grand total
 */
function getCartSummary(): array {
    $items = getCartItems();
    $subtotal = 0.00;
    $totalQty = 0;

    foreach ($items as $item) {
        $subtotal += (float)$item['subtotal'];
        $totalQty += (int)$item['quantity'];
    }

    if ($subtotal === 0.00 || empty($items)) {
        $deliveryCharge = 0.00;
    } elseif ($subtotal >= FREE_DELIVERY_THRESHOLD) {
        $deliveryCharge = 0.00;
    } else {
        $deliveryCharge = FLAT_DELIVERY_CHARGE;
    }

    $finalTotal = $subtotal + $deliveryCharge;

    return [
        'subtotal'        => $subtotal,
        'delivery_charge' => $deliveryCharge,
        'final_total'     => $finalTotal,
        'total_qty'       => $totalQty,
        'item_count'      => count($items),
        'is_free_delivery'=> ($subtotal >= FREE_DELIVERY_THRESHOLD && $subtotal > 0),
        'amount_for_free' => max(0, FREE_DELIVERY_THRESHOLD - $subtotal),
    ];
}

/**
 * Return appropriate badge markup based on order status
 */
function getStatusBadge(string $status): string {
    return match(trim($status)) {
        'Pending'   => '<span class="badge status-badge bg-warning text-dark"><i class="bi bi-clock-history me-1"></i>Pending</span>',
        'Confirmed' => '<span class="badge status-badge bg-info text-dark"><i class="bi bi-check2-circle me-1"></i>Confirmed</span>',
        'Preparing' => '<span class="badge status-badge bg-primary"><i class="bi bi-box-seam me-1"></i>Preparing</span>',
        'Ready'     => '<span class="badge status-badge bg-teal text-white" style="background-color: #0d9488;"><i class="bi bi-bag-check me-1"></i>Ready for Delivery</span>',
        'Delivered' => '<span class="badge status-badge bg-success"><i class="bi bi-check-circle-fill me-1"></i>Delivered</span>',
        'Cancelled' => '<span class="badge status-badge bg-danger"><i class="bi bi-x-circle-fill me-1"></i>Cancelled</span>',
        default     => '<span class="badge status-badge bg-secondary">' . escape($status) . '</span>',
    };
}

/**
 * Stock availability badge
 */
function getStockBadge(int $stock, string $status = 'active'): string {
    if ($status === 'inactive') {
        return '<span class="badge bg-secondary">Unavailable</span>';
    }
    if ($stock <= 0) {
        return '<span class="badge bg-danger">Out of Stock</span>';
    }
    if ($stock <= 5) {
        return '<span class="badge bg-warning text-dark">Only ' . $stock . ' left</span>';
    }
    return '<span class="badge bg-success-subtle text-success border border-success-subtle">In Stock</span>';
}
