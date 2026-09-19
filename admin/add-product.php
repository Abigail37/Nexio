<?php

require_once "../includes/db.php";
require_once "../includes/auth.php";

requireLogin();

$allowedRoles = [
    'superuser',
    'ceo',
    'manager'
];

if (!in_array(getUserRole(), $allowedRoles, true)) {
    http_response_code(403);
    exit("Access denied.");
}

$success = "";
$error = "";


/*
|--------------------------------------------------------------------------
| Fetch active categories
|--------------------------------------------------------------------------
*/

$categoryStmt = $pdo->query("
    SELECT
        id,
        name
    FROM categories
    WHERE is_active = 1
    ORDER BY name ASC
");

$categories = $categoryStmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Fetch active suppliers
|--------------------------------------------------------------------------
*/

$supplierStmt = $pdo->query("
    SELECT
        id,
        name
    FROM suppliers
    WHERE is_active = 1
    ORDER BY name ASC
");

$suppliers = $supplierStmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Handle product creation
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    $price = trim($_POST['price'] ?? '');
    $stockQuantity = trim($_POST['stock_quantity'] ?? '');
    $lowStockThreshold = trim(
        $_POST['low_stock_threshold'] ?? '5'
    );

    $categoryId = (int) ($_POST['category_id'] ?? 0);

    $supplierId = !empty($_POST['supplier_id'])
        ? (int) $_POST['supplier_id']
        : null;

    $isActive = isset($_POST['is_active']) ? 1 : 0;


    /*
    |--------------------------------------------------------------------------
    | Validate product name
    |--------------------------------------------------------------------------
    */

    if ($name === '') {

        $error = "Product name is required.";
    } elseif (
        $price === '' ||
        !is_numeric($price) ||
        (float) $price < 0
    ) {

        $error = "Please enter a valid product price.";
    } elseif (
        $stockQuantity === '' ||
        filter_var(
            $stockQuantity,
            FILTER_VALIDATE_INT
        ) === false ||
        (int) $stockQuantity < 0
    ) {

        $error = "Please enter a valid stock quantity.";
    } elseif (
        $lowStockThreshold === '' ||
        filter_var(
            $lowStockThreshold,
            FILTER_VALIDATE_INT
        ) === false ||
        (int) $lowStockThreshold < 0
    ) {

        $error = "Please enter a valid low-stock threshold.";
    } elseif ($categoryId <= 0) {

        $error = "Please select a category.";
    } else {

        /*
        |--------------------------------------------------------------------------
        | Verify category
        |--------------------------------------------------------------------------
        */
        $categoryCheck = $pdo->prepare("
            SELECT id
            FROM categories
            WHERE id = :category_id
              AND is_active = 1
            LIMIT 1
        ");
        $categoryCheck->execute([
            ':category_id' => $categoryId
        ]);
        if (!$categoryCheck->fetch()) {
            $error = "The selected category is invalid.";
        } else {

            /*
            |--------------------------------------------------------------------------
            | Verify supplier if supplied
            |--------------------------------------------------------------------------
            */
            if ($supplierId !== null) {

                $supplierCheck = $pdo->prepare("
                    SELECT id
                    FROM suppliers
                    WHERE id = :supplier_id
                      AND is_active = 1
                    LIMIT 1
                ");
                $supplierCheck->execute([
                    ':supplier_id' => $supplierId
                ]);
                if (!$supplierCheck->fetch()) {
                    $error = "The selected supplier is invalid.";
                }
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Insert product
    |--------------------------------------------------------------------------
    */
    if ($error === '') {

        try {

            $stmt = $pdo->prepare("
                INSERT INTO products (
                    name,
                    description,
                    price,
                    stock_quantity,
                    low_stock_threshold,
                    category_id,
                    supplier_id,
                    image_url,
                    is_active,
                    created_by
                )
                VALUES (
                    :name,
                    :description,
                    :price,
                    :stock_quantity,
                    :low_stock_threshold,
                    :category_id,
                    :supplier_id,
                    :image_url,
                    :is_active,
                    :created_by
                )
            ");

            $stmt->execute([
                ':name' => $name,
                ':description' =>
                $description !== ''
                    ? $description
                    : null,
                ':price' => (float) $price,
                ':stock_quantity' =>
                (int) $stockQuantity,
                ':low_stock_threshold' =>
                (int) $lowStockThreshold,
                ':category_id' =>
                $categoryId,
                ':supplier_id' =>
                $supplierId,
                ':image_url' =>
                null,
                ':is_active' =>
                $isActive,
                ':created_by' =>
                getUserId()
            ]);

            $success =
                "Product created successfully.";
            /*
            |--------------------------------------------------------------------------
            | Clear form
            |--------------------------------------------------------------------------
            */
            $_POST = [];
        } catch (PDOException $e) {
            $error =
                "Unable to create product.";
        }
    }
}


require_once "includes/admin-header.php";
require_once "includes/admin-sidebar.php";

?>

<div class="container">
    <div class="page-inner">
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
                <li class="separator">
                    <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                    <a href="#">Add Product</a>
                </li>
            </ul>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Add Product</div>
                        <!-- <p>Add a new product to your inventory.</p> -->
                    </div>
                    <div class="card-body">

                        <?php if ($success !== ''): ?>
                            <div class="success-message">
                                <?= htmlspecialchars($success) ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($error !== ''): ?>
                            <div class="error-message">
                                <?= htmlspecialchars($error) ?>
                            </div>
                        <?php endif; ?>
                        <?php if (empty($categories)): ?>
                            <div class="error-message">
                                No active categories are available.
                                Please create a category first.

                                <br><br>
                                <a href="add_category.php">
                                    Add Category
                                </a>
                            </div>
                        <?php else: ?>
                            <form method="POST" action="add-product.php">
                                <div class="row">
                                    <!-- PRODUCT NAME -->
                                    <div class="col-md-6 col-lg-4">
                                        <div class="form-group">
                                            <label for="name"> Product Name</label>
                                            <input
                                                type="text"
                                                id="name"
                                                name="name"
                                                value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                                                placeholder="Enter product name"
                                                class="form-control"
                                                required />
                                        </div>
                                    </div>
                                    <!-- PRICE -->
                                    <div class="col-md-6 col-lg-4">
                                        <div class="form-group">
                                            <label for="price">Price (₦) </label>
                                            <input
                                                type="number"
                                                id="price"
                                                name="price"
                                                value="<?= htmlspecialchars($_POST['price'] ?? '') ?>"
                                                min="0"
                                                step="0.01"
                                                placeholder="0.00"
                                                class="form-control"
                                                required />
                                        </div>
                                    </div>
                                    <!-- STOCK -->
                                    <div class="col-md-6 col-lg-4">
                                        <div class="form-group">
                                            <label for="stock_quantity">Stock Quantity</label>
                                            <input
                                                type="number"
                                                id="stock_quantity"
                                                name="stock_quantity"
                                                value="<?= htmlspecialchars($_POST['stock_quantity'] ?? '') ?>"
                                                min="0"
                                                step="1"
                                                placeholder="0"
                                                class="form-control"
                                                required />
                                        </div>
                                    </div>
                                    <!-- DESCRIPTION -->
                                    <div class="col-md-6 col-lg-12">
                                        <div class="form-group">
                                            <label for="description">Description</label>
                                            <textarea
                                                id="description"
                                                name="description"
                                                rows="4"
                                                class="form-control"
                                                placeholder="Enter product description"><?= htmlspecialchars($_POST['description'] ?? '') ?>
                                    </textarea>
                                        </div>
                                    </div>
                                    <!-- LOW STOCK THRESHOLD -->
                                    <div class="col-md-6 col-lg-4">
                                        <div class="form-group">
                                            <label for="low_stock_threshold">Low Stock Threshold</label>
                                            <input
                                                type="number"
                                                id="low_stock_threshold"
                                                name="low_stock_threshold"
                                                value="<?= htmlspecialchars($_POST['low_stock_threshold'] ?? '5') ?>"
                                                min="0"
                                                step="1"
                                                class="form-control"
                                                required />
                                        </div>
                                        <small>The product will be considered low stock when its quantity reaches this number.</small>
                                    </div>
                                    <!-- CATEGORY -->
                                    <div class="col-md-6 col-lg-4">
                                        <div class="form-group">
                                            <label for="category_id">Category</label>
                                            <select
                                                class="form-control"
                                                id="category_id"
                                                name="category_id"
                                                required>

                                                <option value="">Select a category</option>
                                                <?php foreach (
                                                    $categories as $category
                                                ): ?>
                                                    <option
                                                        value="<?= (int) $category['id'] ?>"
                                                        <?= (
                                                            isset(
                                                                $_POST['category_id']
                                                            )
                                                            && (int) $_POST['category_id'] === (int) $category['id']
                                                        )
                                                            ? 'selected'
                                                            : ''
                                                        ?>>
                                                        <?= htmlspecialchars($category['name']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <!-- SUPPLIER -->
                                    <div class="col-md-6 col-lg-4">
                                        <div class="form-group">
                                            <label for="supplier_id">Supplier
                                                <span>(Optional)</span>
                                            </label>
                                            <select
                                                id="supplier_id"
                                                name="supplier_id"
                                                class="form-control">
                                                <option value="">Select supplier</option>
                                                <?php foreach (
                                                    $suppliers as $supplier
                                                ): ?>

                                                    <option
                                                        value="<?= (int) $supplier['id'] ?>"
                                                        <?= (
                                                            isset(
                                                                $_POST['supplier_id']
                                                            )
                                                            && (int) $_POST['supplier_id'] === (int) $supplier['id']
                                                        )
                                                            ? 'selected'
                                                            : ''
                                                        ?>>
                                                        <?= htmlspecialchars($supplier['name']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <!-- ACTIVE STATUS -->
                                    <div class="col-md-6 col-lg-2">
                                        <div class="form-group checkbox-group">
                                            <label>
                                                <input
                                                    type="checkbox"
                                                    name="is_active"
                                                    value="1"
                                                    checked>
                                                Active Product
                                            </label>
                                        </div>
                                    </div>
                                    <!-- SUBMIT -->
                                    <div class="col-md-6 col-lg-4">
                                        <button
                                            type="submit"
                                            class="btn btn-primary btn-round">
                                            Add Product
                                        </button>
                                        <!-- BACK TO PRODUCTS -->
                                        <button class="btn btn-secondary btn-round">
                                            <a
                                                href="products.php"
                                                class="back-link text-white">
                                                Back to Products
                                            </a>
                                        </button>
                                    </div>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>