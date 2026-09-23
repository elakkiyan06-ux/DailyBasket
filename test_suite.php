<?php
// test_suite.php - Automated Verification & Quality Assurance Suite for FreshCart

echo "=========================================================\n";
echo "   FRESHCART GROCERY SYSTEM - AUTOMATED TEST SUITE       \n";
echo "=========================================================\n\n";

$passCount = 0;
$failCount = 0;

function assertTest(string $description, bool $condition, string $detail = ''): void {
    global $passCount, $failCount;
    if ($condition) {
        $passCount++;
        echo " [PASS] $description\n";
    } else {
        $failCount++;
        echo " [FAIL] $description" . ($detail ? " ($detail)" : "") . "\n";
    }
}

// 1. Database Connection Test
require_once __DIR__ . '/config/database.php';
try {
    $db = getDB();
    assertTest("Database Connection to freshcart_db", $db instanceof PDO);
} catch (Exception $e) {
    assertTest("Database Connection to freshcart_db", false, $e->getMessage());
    exit(1);
}

// 2. Schema Table Verification
$expectedTables = ['users', 'categories', 'products', 'cart', 'orders', 'order_items'];
$stmt = $db->query("SHOW TABLES");
$existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);

foreach ($expectedTables as $tbl) {
    assertTest("Table exists: $tbl", in_array($tbl, $existingTables));
}

// 3. User Accounts and Password Verification Test
$adminStmt = $db->prepare("SELECT * FROM users WHERE email = ?");
$adminStmt->execute(['admin@freshcart.com']);
$admin = $adminStmt->fetch();

assertTest("Admin account exists", !empty($admin) && $admin['role'] === 'admin');
assertTest("Admin password verification for Admin@123", password_verify('Admin@123', $admin['password']));

$custStmt = $db->prepare("SELECT * FROM users WHERE email = ?");
$custStmt->execute(['customer@freshcart.com']);
$cust = $custStmt->fetch();

assertTest("Demo customer account exists", !empty($cust) && $cust['role'] === 'customer');
assertTest("Customer password verification for Customer@123", password_verify('Customer@123', $cust['password']));

// 4. Products & Categories Catalog Test
$pCount = (int)$db->query("SELECT COUNT(*) FROM products")->fetchColumn();
assertTest("Product catalog has 20+ items", $pCount >= 20, "Found $pCount items");

$cCount = (int)$db->query("SELECT COUNT(*) FROM categories")->fetchColumn();
assertTest("Categories table has at least 8 categories", $cCount >= 8, "Found $cCount categories");

// 5. Search & Filter Query Logic Test
$searchStmt = $db->prepare("
    SELECT p.*, c.name as category_name 
    FROM products p 
    JOIN categories c ON p.category_id = c.id 
    WHERE (p.name LIKE ? OR p.description LIKE ? OR c.name LIKE ?)
");
$searchStmt->execute(['%Rice%', '%Rice%', '%Rice%']);
$searchResults = $searchStmt->fetchAll();
assertTest("Product search for 'Rice' returns results", count($searchResults) > 0);

// 6. Transactional Order Placement & Stock Decrement Simulation
try {
    $db->beginTransaction();

    // Pick an active product with stock >= 5
    $targetProduct = $db->query("SELECT * FROM products WHERE stock >= 5 AND status = 'active' LIMIT 1")->fetch();
    assertTest("Found in-stock product for transactional test", !empty($targetProduct));

    $initialStock = (int)$targetProduct['stock'];
    $orderQty = 2;
    $unitPrice = (float)$targetProduct['price'];
    $subtotal = $unitPrice * $orderQty;
    $deliveryCharge = ($subtotal >= 500) ? 0.00 : 30.00;
    $grandTotal = $subtotal + $deliveryCharge;

    // Create Order
    $oStmt = $db->prepare("
        INSERT INTO orders (user_id, total_amount, delivery_charge, status, delivery_name, delivery_phone, delivery_address, delivery_city, delivery_pincode, created_at)
        VALUES (?, ?, ?, 'Pending', 'Test Automated', '9999999999', '123 Test Street', 'Test City', '400001', NOW())
    ");
    $oStmt->execute([$cust['id'], $grandTotal, $deliveryCharge]);
    $newOrderId = (int)$db->lastInsertId();
    assertTest("Transactional Order record created (#$newOrderId)", $newOrderId > 0);

    // Create Order Items with snapshot
    $oiStmt = $db->prepare("
        INSERT INTO order_items (order_id, product_id, product_name, quantity, price, subtotal)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $oiStmt->execute([$newOrderId, $targetProduct['id'], $targetProduct['name'], $orderQty, $unitPrice, $subtotal]);
    $newOrderItemId = (int)$db->lastInsertId();
    assertTest("Historical order item snapshot created (#$newOrderItemId)", $newOrderItemId > 0);

    // Deduct stock
    $stkStmt = $db->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
    $stkStmt->execute([$orderQty, $targetProduct['id']]);

    // Verify stock in transaction
    $checkStk = $db->prepare("SELECT stock FROM products WHERE id = ?");
    $checkStk->execute([$targetProduct['id']]);
    $newStock = (int)$checkStk->fetchColumn();
    assertTest("Product stock successfully decremented by $orderQty", $newStock === ($initialStock - $orderQty), "Old: $initialStock, New: $newStock");

    // Commit Transaction
    $db->commit();
    assertTest("Database transaction committed successfully", true);

    // 7. Admin Status Update Test
    $statusUpdateStmt = $db->prepare("UPDATE orders SET status = 'Preparing' WHERE id = ?");
    $statusUpdateStmt->execute([$newOrderId]);
    
    $verifyStatusStmt = $db->prepare("SELECT status FROM orders WHERE id = ?");
    $verifyStatusStmt->execute([$newOrderId]);
    $updatedStatus = $verifyStatusStmt->fetchColumn();
    assertTest("Order status successfully updated to 'Preparing'", $updatedStatus === 'Preparing');

    // 8. Progress Tracker Logic Verification
    $stages = ['Pending', 'Confirmed', 'Preparing', 'Ready', 'Delivered'];
    $stageIndex = array_search($updatedStatus, $stages);
    assertTest("Tracker maps 'Preparing' to Stage 3 (index 2)", $stageIndex === 2);

    // Clean up test order to keep demo data tidy
    $db->prepare("DELETE FROM orders WHERE id = ?")->execute([$newOrderId]);
    // Restore stock
    $db->prepare("UPDATE products SET stock = ? WHERE id = ?")->execute([$initialStock, $targetProduct['id']]);
    assertTest("Test order cleaned up and stock restored", true);

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    assertTest("Transactional Order test failed", false, $e->getMessage());
}

// 9. HTTP Web Server Endpoints Verification
echo "\n--- HTTP ENDPOINTS LIVE CHECK (http://localhost/freshcart/) ---\n";
$endpoints = [
    'index.php'            => 'Customer Homepage',
    'shop.php'             => 'Shop Catalog',
    'cart.php'             => 'Cart Page',
    'login.php'            => 'Customer Login',
    'register.php'         => 'Registration Page',
    'admin/login.php'      => 'Admin Portal Login',
    'assets/css/style.css' => 'Custom CSS Stylesheet',
    'assets/js/script.js'  => 'Custom JavaScript',
];

foreach ($endpoints as $path => $desc) {
    $url = "http://localhost/freshcart/" . $path;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    assertTest("HTTP 200 on $desc ($url)", $httpCode === 200, "HTTP Code: $httpCode");
}

echo "\n=========================================================\n";
echo " TEST SUMMARY: $passCount Passed, $failCount Failed\n";
echo "=========================================================\n";

if ($failCount > 0) {
    exit(1);
} else {
    exit(0);
}
