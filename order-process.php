<?php
// order-process.php - Transactional Order Placement Processor
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . baseUrl('checkout.php'));
    exit;
}

$user = currentUser();
$userId = (int)$user['id'];
$db = getDB();

// Sanitize inputs
$deliveryName    = sanitize($_POST['delivery_name'] ?? '');
$deliveryPhone   = sanitize($_POST['delivery_phone'] ?? '');
$deliveryAddress = sanitize($_POST['delivery_address'] ?? '');
$deliveryCity    = sanitize($_POST['delivery_city'] ?? '');
$deliveryPincode = sanitize($_POST['delivery_pincode'] ?? '');
$saveAddress     = !empty($_POST['save_profile_address']);

if (empty($deliveryName) || empty($deliveryPhone) || empty($deliveryAddress) || empty($deliveryCity) || empty($deliveryPincode)) {
    setFlash('danger', 'Please complete all required delivery address fields.');
    header('Location: ' . baseUrl('checkout.php'));
    exit;
}

// Optionally update profile address
if ($saveAddress) {
    $uStmt = $db->prepare("UPDATE users SET phone = ?, address = ?, city = ?, pincode = ? WHERE id = ?");
    $uStmt->execute([$deliveryPhone, $deliveryAddress, $deliveryCity, $deliveryPincode, $userId]);
    // update session cached user info
    $_SESSION['user']['phone'] = $deliveryPhone;
    $_SESSION['user']['address'] = $deliveryAddress;
    $_SESSION['user']['city'] = $deliveryCity;
    $_SESSION['user']['pincode'] = $deliveryPincode;
}

try {
    // 1. Begin Database Transaction
    $db->beginTransaction();

    // 2. Fetch cart items locked FOR UPDATE
    $cartStmt = $db->prepare("
        SELECT c.id as cart_id, c.product_id, c.quantity, p.name, p.price, p.stock, p.status 
        FROM cart c
        JOIN products p ON c.product_id = p.id
        WHERE c.user_id = ?
        FOR UPDATE
    ");
    $cartStmt->execute([$userId]);
    $cartItems = $cartStmt->fetchAll();

    if (empty($cartItems)) {
        $db->rollBack();
        setFlash('danger', 'Your cart is empty. Please add products before placing an order.');
        header('Location: ' . baseUrl('shop.php'));
        exit;
    }

    // 3. Verify stock availability and calculate server-side totals
    $itemsSubtotal = 0.00;
    foreach ($cartItems as $item) {
        if ($item['status'] === 'inactive') {
            $db->rollBack();
            setFlash('danger', 'Item "' . $item['name'] . '" is no longer available.');
            header('Location: ' . baseUrl('cart.php'));
            exit;
        }

        if ($item['stock'] < $item['quantity']) {
            $db->rollBack();
            setFlash('danger', 'Insufficient stock for "' . $item['name'] . '". Only ' . $item['stock'] . ' available.');
            header('Location: ' . baseUrl('cart.php'));
            exit;
        }

        $itemsSubtotal += ((float)$item['price'] * (int)$item['quantity']);
    }

    // Calculate delivery charge
    $deliveryCharge = ($itemsSubtotal >= FREE_DELIVERY_THRESHOLD) ? 0.00 : FLAT_DELIVERY_CHARGE;
    $finalTotal = $itemsSubtotal + $deliveryCharge;

    // 4. Create Order in `orders` table (Initial status: Pending)
    $orderStmt = $db->prepare("
        INSERT INTO orders (
            user_id, total_amount, delivery_charge, status,
            delivery_name, delivery_phone, delivery_address, delivery_city, delivery_pincode,
            created_at
        ) VALUES (
            ?, ?, ?, 'Pending',
            ?, ?, ?, ?, ?,
            NOW()
        )
    ");
    $orderStmt->execute([
        $userId,
        $finalTotal,
        $deliveryCharge,
        $deliveryName,
        $deliveryPhone,
        $deliveryAddress,
        $deliveryCity,
        $deliveryPincode
    ]);
    $orderId = (int)$db->lastInsertId();

    // 5. Create Order Items & Deduct Stock
    $itemInsertStmt = $db->prepare("
        INSERT INTO order_items (order_id, product_id, product_name, quantity, price, subtotal)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $stockUpdateStmt = $db->prepare("
        UPDATE products 
        SET stock = stock - ?,
            status = CASE WHEN (stock - ?) <= 0 THEN 'out_of_stock' ELSE status END
        WHERE id = ?
    ");

    foreach ($cartItems as $item) {
        $subtotal = (float)$item['price'] * (int)$item['quantity'];

        // Save historical snapshot
        $itemInsertStmt->execute([
            $orderId,
            $item['product_id'],
            $item['name'],
            $item['quantity'],
            $item['price'],
            $subtotal
        ]);

        // Reduce inventory
        $stockUpdateStmt->execute([
            $item['quantity'],
            $item['quantity'],
            $item['product_id']
        ]);
    }

    // 6. Clear customer's cart
    $clearCartStmt = $db->prepare("DELETE FROM cart WHERE user_id = ?");
    $clearCartStmt->execute([$userId]);

    // 7. Commit Transaction
    $db->commit();

    setFlash('success', 'Order placed successfully! Thank you for shopping with DailyBasket.');
    header('Location: ' . baseUrl('order-success.php?id=' . $orderId));
    exit;

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    setFlash('danger', 'Unable to place order: ' . $e->getMessage());
    header('Location: ' . baseUrl('checkout.php'));
    exit;
}
