<?php

require_once "../includes/db.php";
require_once "../includes/auth.php";

requireLogin();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| Inventory access
|--------------------------------------------------------------------------
*/

$allowedRoles = [
    'superuser',
    'ceo',
    'manager',
    'accountant'
];

if (!in_array(getUserRole(), $allowedRoles, true)) {
    http_response_code(403);
    die("You do not have permission to access inventory.");
}


/*
|--------------------------------------------------------------------------
| Inventory summary
|--------------------------------------------------------------------------
*/

/* Total active products */

$totalProductsStmt = $pdo->query("
    SELECT COUNT(*)
    FROM products
    WHERE is_active = 1
");

$totalProducts = (int) $totalProductsStmt->fetchColumn();


/* Total units in stock */

$totalStockStmt = $pdo->query("
    SELECT COALESCE(SUM(stock_quantity), 0)
    FROM products
    WHERE is_active = 1
");

$totalStock = (int) $totalStockStmt->fetchColumn();


/* Low-stock products */

$lowStockStmt = $pdo->query("
    SELECT COUNT(*)
    FROM products
    WHERE is_active = 1
      AND stock_quantity > 0
      AND stock_quantity <= low_stock_threshold
");

$lowStockProducts = (int) $lowStockStmt->fetchColumn();


/* Out-of-stock products */

$outOfStockStmt = $pdo->query("
    SELECT COUNT(*)
    FROM products
    WHERE is_active = 1
      AND stock_quantity = 0
");

$outOfStockProducts = (int) $outOfStockStmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| Get inventory products
|--------------------------------------------------------------------------
*/

$productStmt = $pdo->query("
    SELECT
        p.id,
        p.name,
        p.price,
        p.stock_quantity,
        p.low_stock_threshold,
        p.is_active,
        c.name AS category_name,
        s.name AS supplier_name

    FROM products p

    INNER JOIN categories c
        ON c.id = p.category_id

    LEFT JOIN suppliers s
        ON s.id = p.supplier_id

    WHERE p.is_active = 1

    ORDER BY
        CASE
            WHEN p.stock_quantity = 0 THEN 1
            WHEN p.stock_quantity <= p.low_stock_threshold THEN 2
            ELSE 3
        END,
        p.name ASC
");

$products = $productStmt->fetchAll();


require_once "assets/includes/header.php";

?>

<section class="inventory-page">
    <div class="container">
        <div class="page-heading">
            <h1>Inventory</h1>
            <p>
                Monitor your products and current stock levels.
            </p>
        </div>

        <!-- INVENTORY SUMMARY -->
        <div class="inventory-summary">
            <div class="inventory-card">
                <h3>Total Products</h3>
                <strong>
                    <?= number_format($totalProducts) ?>
                </strong>
            </div>

            <div class="inventory-card">
                <h3>Total Units in Stock</h3>
                <strong>
                    <?= number_format($totalStock) ?>
                </strong>
            </div>

            <div class="inventory-card">
                <h3>Low Stock</h3>
                <strong>
                    <?= number_format($lowStockProducts) ?>
                </strong>
            </div>

            <div class="inventory-card">
                <h3>Out of Stock</h3>
                <strong>
                    <?= number_format($outOfStockProducts) ?>
                </strong>
            </div>
        </div>

        <!-- INVENTORY TABLE -->
        <div class="inventory-table-card">
            <div class="section-heading">
                <h2>Stock Overview</h2>
            </div>

            <?php if (empty($products)): ?>
                <p>
                    No active products found.
                </p>
            <?php else: ?>
                <div class="table-wrapper">
                    <table class="inventory-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Threshold</th>
                                <th>Supplier</th>
                                <th>Status</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($products as $product): ?>
                                <?php
                                $stock =
                                    (int) $product['stock_quantity'];
                                $threshold =
                                    (int) $product['low_stock_threshold'];
                                if ($stock === 0) {
                                    $stockStatus = 'Out of Stock';
                                    $statusClass = 'out-of-stock';
                                } elseif (
                                    $stock <= $threshold
                                ) {
                                    $stockStatus = 'Low Stock';
                                    $statusClass = 'low-stock';
                                } else {
                                    $stockStatus = 'In Stock';
                                    $statusClass = 'in-stock';
                                }
                                ?>

                                <tr>
                                    <td>
                                        <?= htmlspecialchars($product['name']) ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($product['category_name']) ?>
                                    </td>
                                    <td>
                                        ₦<?= number_format((float) $product['price'],2) ?>
                                    </td>
                                    <td>
                                        <?= number_format($stock) ?>
                                    </td>
                                    <td>
                                        <?= number_format($threshold) ?>
                                    </td>
                                    <td>
                                        <?= $product['supplier_name'] ? htmlspecialchars($product['supplier_name']): '—'?>
                                    </td>
                                    <td>
                                        <span class="stock-status <?= $statusClass ?>">
                                            <?= $stockStatus ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once "assets/includes/footer.php"; ?>