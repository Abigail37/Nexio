<?php

require_once "../includes/db.php";
require_once "../includes/auth.php";

requireLogin();

$allowedRoles = [
    'superuser',
    'ceo',
    'manager',
    'accountant',
    'sales_rep',
    'cashier'
];

if (!in_array(getUserRole(), $allowedRoles, true)) {
    http_response_code(403);

    echo json_encode([
        'success' => false,
        'message' => 'Access denied.'
    ]);

    exit;
}


header('Content-Type: application/json');


/*
|--------------------------------------------------------------------------
| Validate order ID
|--------------------------------------------------------------------------
*/

$orderId = filter_input(INPUT_GET, 'order_id', FILTER_VALIDATE_INT);

if (!$orderId) {

    echo json_encode([
        'success' => false,
        'message' => 'Invalid order ID.'
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Fetch order
|--------------------------------------------------------------------------
*/

$orderStmt = $pdo->prepare("
    SELECT
        id,
        shipping_name,
        shipping_email,
        status,
        total_amount,
        created_at
    FROM orders
    WHERE id = ?
    LIMIT 1
");

$orderStmt->execute([$orderId]);

$order = $orderStmt->fetch(PDO::FETCH_ASSOC);


if (!$order) {

    echo json_encode([
        'success' => false,
        'message' => 'Order not found.'
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Fetch order items
|--------------------------------------------------------------------------
*/

$itemStmt = $pdo->prepare("
    SELECT
        oi.product_id,
        oi.quantity,
        oi.price_at_purchase,
        oi.subtotal,
        p.name AS product_name
    FROM order_items oi

    INNER JOIN products p
        ON p.id = oi.product_id

    WHERE oi.order_id = ?

    ORDER BY oi.id ASC
");

$itemStmt->execute([$orderId]);

$items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Return response
|--------------------------------------------------------------------------
*/

echo json_encode([
    'success' => true,
    'order' => $order,
    'items' => $items
]);

