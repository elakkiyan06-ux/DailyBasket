<?php
// cart-action.php - Handles adding, updating, and removing items in the cart
require_once __DIR__ . '/includes/functions.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : (int)($_GET['product_id'] ?? 0);
$quantity = isset($_POST['quantity']) ? max(1, (int)$_POST['quantity']) : 1;

$db = getDB();

// Redirect target
$redirect = $_SERVER['HTTP_REFERER'] ?? baseUrl('cart.php');

if (!$productId && $action !== 'clear') {
    setFlash('danger', 'Invalid product selected.');
    header('Location: ' . $redirect);
    exit;
}

// Fetch product details & verify stock
if ($productId > 0) {
    $stmt = $db->prepare("SELECT id, name, stock, price, status FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $product = $stmt->fetch();

    if (!$product || $product['status'] === 'inactive') {
        setFlash('danger', 'Product is currently unavailable.');
        header('Location: ' . $redirect);
        exit;
    }
}

// Handle Add to Cart
if ($action === 'add') {
    if ($product['stock'] <= 0) {
        setFlash('danger', 'Sorry, "' . $product['name'] . '" is currently out of stock.');
        header('Location: ' . $redirect);
        exit;
    }

    if (isLoggedIn()) {
        $userId = (int)$_SESSION['user']['id'];

        // Check if product already exists in cart
        $checkStmt = $db->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
        $checkStmt->execute([$userId, $productId]);
        $existing = $checkStmt->fetch();

        if ($existing) {
            $newQty = $existing['quantity'] + $quantity;
            if ($newQty > $product['stock']) {
                $newQty = $product['stock'];
                setFlash('warning', 'Cart updated to maximum available stock (' . $product['stock'] . ') for ' . $product['name'] . '.');
            } else {
                setFlash('success', 'Updated quantity for ' . $product['name'] . ' in cart.');
            }
            $upd = $db->prepare("UPDATE cart SET quantity = ?, updated_at = NOW() WHERE id = ?");
            $upd->execute([$newQty, $existing['id']]);
        } else {
            if ($quantity > $product['stock']) {
                $quantity = $product['stock'];
                setFlash('warning', 'Added maximum available quantity (' . $product['stock'] . ') to cart.');
            } else {
                setFlash('success', 'Added ' . $product['name'] . ' to cart.');
            }
            $ins = $db->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
            $ins->execute([$userId, $productId, $quantity]);
        }
    } else {
        // Guest cart
        if (!isset($_SESSION['guest_cart'])) {
            $_SESSION['guest_cart'] = [];
        }
        $currentQty = $_SESSION['guest_cart'][$productId] ?? 0;
        $newQty = $currentQty + $quantity;
        if ($newQty > $product['stock']) {
            $newQty = $product['stock'];
            setFlash('warning', 'Cart updated to maximum available stock (' . $product['stock'] . ') for ' . $product['name'] . '.');
        } else {
            setFlash('success', 'Added ' . $product['name'] . ' to cart.');
        }
        $_SESSION['guest_cart'][$productId] = $newQty;
    }

    header('Location: ' . $redirect);
    exit;
}

// Handle Update Quantity in Cart
if ($action === 'update') {
    if ($quantity > $product['stock']) {
        $quantity = $product['stock'];
        setFlash('warning', 'Quantity capped at maximum available stock (' . $product['stock'] . ') for ' . $product['name'] . '.');
    } else {
        setFlash('success', 'Cart updated successfully.');
    }

    if (isLoggedIn()) {
        $userId = (int)$_SESSION['user']['id'];
        $upd = $db->prepare("UPDATE cart SET quantity = ?, updated_at = NOW() WHERE user_id = ? AND product_id = ?");
        $upd->execute([$quantity, $userId, $productId]);
    } else {
        $_SESSION['guest_cart'][$productId] = $quantity;
    }

    header('Location: ' . baseUrl('cart.php'));
    exit;
}

// Handle Remove from Cart
if ($action === 'remove') {
    if (isLoggedIn()) {
        $userId = (int)$_SESSION['user']['id'];
        $del = $db->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
        $del->execute([$userId, $productId]);
    } else {
        unset($_SESSION['guest_cart'][$productId]);
    }

    setFlash('info', 'Item removed from your cart.');
    header('Location: ' . baseUrl('cart.php'));
    exit;
}

// Handle Clear Entire Cart
if ($action === 'clear') {
    if (isLoggedIn()) {
        $userId = (int)$_SESSION['user']['id'];
        $del = $db->prepare("DELETE FROM cart WHERE user_id = ?");
        $del->execute([$userId]);
    } else {
        $_SESSION['guest_cart'] = [];
    }

    setFlash('info', 'Your cart has been cleared.');
    header('Location: ' . baseUrl('cart.php'));
    exit;
}

header('Location: ' . baseUrl('cart.php'));
exit;
