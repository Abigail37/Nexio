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
| Get product ID
|--------------------------------------------------------------------------
*/
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    header("Location: products.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Fetch product
|--------------------------------------------------------------------------
*/
$stmt = $pdo->prepare("
    SELECT
        id,
        name,
        description,
        price,
        stock_quantity,
        low_stock_threshold,
        category_id,
        supplier_id,
        image_url,
        is_active,
        created_by,
        created_at
    FROM products
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$id]);

$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    http_response_code(404);
    exit("Product not found.");
}

/*
|--------------------------------------------------------------------------
| Fetch categories
|--------------------------------------------------------------------------
*/
$categoryStmt = $pdo->query("
    SELECT id, name
    FROM categories
    ORDER BY name ASC
");

$categories = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| Fetch suppliers
|--------------------------------------------------------------------------
*/
$supplierStmt = $pdo->query("
    SELECT id, name
    FROM suppliers
    ORDER BY name ASC
");

$suppliers = $supplierStmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| Form values
|--------------------------------------------------------------------------
*/
$name = $product['name'];
$description = $product['description'];
$price = $product['price'];
$stockQuantity = $product['stock_quantity'];
$lowStockThreshold = $product['low_stock_threshold'];
$categoryId = $product['category_id'];
$supplierId = $product['supplier_id'];
$isActive = (int) $product['is_active'];

$errors = [];

/*
|--------------------------------------------------------------------------
| Update product
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $stockQuantity = trim($_POST['stock_quantity'] ?? '');
    $lowStockThreshold = trim($_POST['low_stock_threshold'] ?? '');
    $categoryId = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
    $supplierId = filter_input(INPUT_POST, 'supplier_id', FILTER_VALIDATE_INT);
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */
    if ($name === '') {
        $errors[] = "Product name is required.";
    } elseif (strlen($name) > 150) {
        $errors[] = "Product name cannot exceed 150 characters.";
    }
    if ($price === '' || !is_numeric($price) || $price < 0) {
        $errors[] = "Enter a valid price.";
    }
    if (
        $stockQuantity === '' ||
        filter_var($stockQuantity, FILTER_VALIDATE_INT) === false ||
        $stockQuantity < 0
    ) {
        $errors[] = "Enter a valid stock quantity.";
    }
    if (
        $lowStockThreshold === '' ||
        filter_var($lowStockThreshold, FILTER_VALIDATE_INT) === false ||
        $lowStockThreshold < 0
    ) {
        $errors[] = "Enter a valid low-stock threshold.";
    }
    if (!$categoryId || $categoryId <= 0) {
        $errors[] = "Please select a category.";
    }

    /*
    |--------------------------------------------------------------------------
    | Update database
    |--------------------------------------------------------------------------
    */
    if (empty($errors)) {
        $updateStmt = $pdo->prepare("
            UPDATE products
            SET
                name = ?,
                description = ?,
                price = ?,
                stock_quantity = ?,
                low_stock_threshold = ?,
                category_id = ?,
                supplier_id = ?,
                is_active = ?
            WHERE id = ?
        ");

        $updateStmt->execute([
            $name,
            $description !== '' ? $description : null,
            $price,
            (int) $stockQuantity,
            (int) $lowStockThreshold,
            $categoryId,
            $supplierId ?: null,
            $isActive,
            $id
        ]);
        header("Location: products.php?updated=1");
        exit;
    }
}


require_once "includes/admin-header.php";

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
                    <a href="#">Edit Product</a>
                </li>
            </ul>
        </div>

        <?php if (!empty($errors)): ?>

            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>

        <?php endif; ?>

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            Edit Product
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row">
                                <!-- Product Name -->
                                <div class="col-md-6 col-lg-4">
                                    <label for="name" class="form-label">Product Name</label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        id="name"
                                        name="name"
                                        maxlength="150"
                                        value="<?= htmlspecialchars($name) ?>"
                                        required>
                                </div>
                                <!-- Price -->
                                <div class="col-md-6 col-lg-4">
                                    <label for="price" class="form-label">Price</label>
                                    <input
                                        type="number"
                                        class="form-control"
                                        id="price"
                                        name="price"
                                        min="0"
                                        step="0.01"
                                        value="<?= htmlspecialchars($price) ?>"
                                        required>
                                </div>
                                <!-- Stock Quantity -->
                                <div class="col-md-6 col-lg-4">
                                    <label for="stock_quantity" class="form-label">Stock Quantity</label>
                                    <input
                                        type="number"
                                        class="form-control"
                                        id="stock_quantity"
                                        name="stock_quantity"
                                        min="0"
                                        value="<?= htmlspecialchars($stockQuantity) ?>"
                                        required>
                                </div>
                                <!-- Description -->
                                <div class="col-12 mt-3 mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea
                                        class="form-control"
                                        id="description"
                                        name="description"
                                        rows="4"><?= htmlspecialchars($description ?? '') ?></textarea>
                                </div>
                                <!-- Low Stock Threshold -->
                                <div class="col-md-6 col-lg-4">
                                    <label for="low_stock_threshold" class="form-label">Low Stock Threshold</label>
                                    <input
                                        type="number"
                                        class="form-control"
                                        id="low_stock_threshold"
                                        name="low_stock_threshold"
                                        min="0"
                                        value="<?= htmlspecialchars($lowStockThreshold) ?>"
                                        required>
                                </div>
                                <!-- Category -->
                                <div class="col-md-6 col-lg-4">
                                    <label for="category_id" class="form-label">
                                        Category
                                    </label>

                                    <select
                                        class="form-select"
                                        id="category_id"
                                        name="category_id"
                                        required>
                                        <option value="">Select Category</option>

                                        <?php foreach ($categories as $category): ?>

                                            <option
                                                value="<?= (int) $category['id'] ?>"
                                                <?= ((int) $categoryId === (int) $category['id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($category['name']) ?>
                                            </option>

                                        <?php endforeach; ?>

                                    </select>
                                </div>
                                <!-- Supplier -->
                                <div class="col-md-6 col-lg-4">
                                    <label for="supplier_id" class="form-label">
                                        Supplier
                                    </label>

                                    <select
                                        class="form-select"
                                        id="supplier_id"
                                        name="supplier_id">
                                        <option value="">No Supplier</option>

                                        <?php foreach ($suppliers as $supplier): ?>

                                            <option
                                                value="<?= (int) $supplier['id'] ?>"
                                                <?= ((int) $supplierId === (int) $supplier['id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($supplier['name']) ?>
                                            </option>

                                        <?php endforeach; ?>

                                    </select>
                                </div>
                                <!-- Active Status -->
                                <div class="col-md-6 col-lg-2 mt-3">

                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        id="is_active"
                                        name="is_active"
                                        <?= $isActive === 1 ? 'checked' : '' ?>>

                                    <label
                                        class="form-check-label"
                                        for="is_active">
                                        Active Product
                                    </label>

                                </div>
                                <!-- Buttons -->
                                <div class="col-md-6 col-lg-4 mt-2">
                                    <button
                                        type="submit"
                                        class="btn btn-primary btn-round">
                                        Update Product
                                    </button>

                                    <a
                                        href="products.php"
                                        class="btn btn-secondary btn-round">
                                        Cancel
                                    </a>

                                </div>
                            </div>
                        </form>

                    </div>
                </div>
            </div>
        </div>
    </div>

    </body>

    </html>