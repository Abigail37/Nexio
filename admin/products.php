<?php

require_once "../includes/db.php";
require_once "../includes/auth.php";

requireLogin();

/*
|--------------------------------------------------------------------------
| Admin product access
|--------------------------------------------------------------------------
*/

$allowedRoles = [
    'superuser',
    'ceo',
    'manager'
];

if (!in_array(getUserRole(), $allowedRoles, true)) {
    http_response_code(403);
    exit("Access denied.");
}


/*
|--------------------------------------------------------------------------
| Get selected category
|--------------------------------------------------------------------------
*/

$categoryId = filter_input(INPUT_GET, 'category', FILTER_VALIDATE_INT);

/*
|--------------------------------------------------------------------------
| Get products
|--------------------------------------------------------------------------
*/

if ($categoryId && $categoryId > 0) {

    $stmt = $pdo->prepare("
        SELECT
            p.id,
            p.name,
            p.description,
            p.price,
            p.stock_quantity,
            p.image_url,
            p.is_active,
            p.created_at,
            c.name AS category_name
        FROM products p
        LEFT JOIN categories c
            ON p.category_id = c.id
        WHERE p.category_id = :category_id
        ORDER BY p.created_at DESC
    ");

    $stmt->execute([
        ':category_id' => $categoryId
    ]);
} else {

    $stmt = $pdo->query("
        SELECT
            p.id,
            p.name,
            p.description,
            p.price,
            p.stock_quantity,
            p.image_url,
            p.is_active,
            p.created_at,
            c.name AS category_name
        FROM products p
        LEFT JOIN categories c
            ON p.category_id = c.id
        ORDER BY p.created_at DESC
    ");
}

$products = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Get category name when viewing a specific category
|--------------------------------------------------------------------------
*/

$categoryName = null;

if ($categoryId && $categoryId > 0) {

    $categoryStmt = $pdo->prepare("
        SELECT name
        FROM categories
        WHERE id = :id
        LIMIT 1
    ");

    $categoryStmt->execute([
        ':id' => $categoryId
    ]);

    $categoryName = $categoryStmt->fetchColumn();

    if (!$categoryName) {
        $categoryId = null;
    }
}


$pageTitle = $categoryName
    ? $categoryName . " Products"
    : "Products";




require_once "includes/admin-header.php";

?>


<div class="container">

    <div class="page-inner">
        <!-- breadcrumbs -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="page-header">
                <h3 class="fw-bold mb-3">Products</h3>
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
                        <a href="products.php">Products</a>
                    </li>

                    <?php if ($categoryName): ?>
                        <li class="separator">
                            <i class="icon-arrow-right"></i>
                        </li>

                        <li class="nav-item">
                            <a href="#">
                                <?= htmlspecialchars($categoryName) ?>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
        <!-- products table -->
        <div class="card card-round">
            <div class="card-header">
                <div class="d-flex align-items-center justify-content-between">
                    <h3 class="card-title">
                        <?= $categoryName
                            ? htmlspecialchars($categoryName) . " Products"
                            : "Products"
                        ?>
                    </h3>
                    <a
                        href="add-product.php"
                        class="btn btn-primary btn-round">
                        <!-- <i class="fas fa-plus"></i> -->
                        Add Product
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="basic-datatables" class="display table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Product</th>
                                <th>Description</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if (empty($products)): ?>
                                <tr>
                                    <td colspan="8" class="text-center">
                                        No products found.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($products as $product): ?>
                                    <tr>
                                        <td> <?= (int) $product['id'] ?> </td>
                                        <td>
                                            <strong>
                                                <?= htmlspecialchars($product['name']) ?>
                                            </strong>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($product['description']) ?>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($product['category_name'] ?? 'Uncategorized') ?>
                                        </td>
                                        <td>
                                            ₦<?= number_format((float) $product['price'], 2) ?>
                                        </td>
                                        <td>
                                            <?= (int) $product['stock_quantity'] ?>
                                        </td>
                                        <td>
                                            <?php if ((int) $product['is_active'] === 1): ?>
                                                <span class="badge badge-success">
                                                    Active
                                                </span>
                                            <?php else: ?>
                                                <span class="badge badge-danger">
                                                    Inactive
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="d-flex">
                                            <a
                                                href="edit-product.php?id=<?= (int) $product['id'] ?>"
                                                class="btn btn-primary btn-round btn-icon me-2">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a
                                                href="delete-product.php?id=<?= (int) $product['id'] ?>"
                                                onclick="return confirm('Are you sure you want to delete this product? This action cannot be undone.');"
                                                class="btn btn-danger btn-round btn-icon">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>




        <?php require_once "includes/admin-footer.php"; ?>