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


require_once "includes/admin-header.php";
require_once "includes/admin-sidebar.php";

?>

<div class="container">
    <div class="page-inner">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="page-header">
                <h3 class="fw-bold mb-3">Inventory</h3>
                <ul class="breadcrumbs mb-3">
                    <li class="nav-home">
                        <a href="dashboard.php">
                            <i class="icon-home"></i>
                        </a>
                    </li>
                    <li class="separator">
                        <i class="icon-arrow-right"></i>
                    </li>
                    <li class="nav-item">
                        <a href="#">Inventory</a>
                    </li>
                </ul>
            </div>
        </div>


        <!-- INVENTORY SUMMARY -->
        <div class="inventory-summary">
            <div class="row">
                <div class="col-sm-6 col-md-3">
                    <div class="card card-stats card-round card-primary">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col col-stats ms-3 ms-sm-0">
                                    <div class="numbers">
                                        <p class="card-category">Total Products</p>
                                        <h4 class="card-title"><strong>
                                                <?= number_format($totalProducts) ?>
                                            </strong></h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-3">
                    <div class="card card-stats card-round card-secondary">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col col-stats ms-3 ms-sm-0">
                                    <div class="numbers">
                                        <p class="card-category">Total Units in Stock</p>
                                        <h4 class="card-title"><strong>
                                                <?= number_format($totalStock) ?>
                                            </strong></h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-3">
                    <div class="card card-stats card-round card-warning">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col col-stats ms-3 ms-sm-0">
                                    <div class="numbers">
                                        <p class="card-category">Low Stock</p>
                                        <h4 class="card-title"><strong>
                                                <?= number_format($lowStockProducts) ?>
                                            </strong></h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-3">
                    <div class="card card-stats card-round card-danger">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col col-stats ms-3 ms-sm-0">
                                    <div class="numbers">
                                        <p class="card-category">Out of Stock</p>
                                        <h4 class="card-title"><strong>
                                                <?= number_format($outOfStockProducts) ?>
                                            </strong></h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- INVENTORY TABLE -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">Stock Overview</div>
            </div>
            <div class="card-body">
                <?php if (empty($products)): ?>
                    <p>
                        No active products found.
                    </p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table id="basic-datatables" class="display table table-striped table-hover">
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
                                            ₦<?= number_format((float) $product['price'], 2) ?>
                                        </td>
                                        <td>
                                            <?= number_format($stock) ?>
                                        </td>
                                        <td>
                                            <?= number_format($threshold) ?>
                                        </td>
                                        <td>
                                            <?= $product['supplier_name'] ? htmlspecialchars($product['supplier_name']) : '—' ?>
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
    </div>
</div>


<?php require_once "includes/admin-footer.php"; ?>