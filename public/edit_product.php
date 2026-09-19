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
| Get product ID
|--------------------------------------------------------------------------
*/

$productId = isset($_GET['id'])
    ? (int) $_GET['id']
    : 0;

if ($productId <= 0) {
    header("Location: products.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Fetch product
|--------------------------------------------------------------------------
*/

$productStmt = $pdo->prepare("
    SELECT
        id,
        name,
        description,
        price,
        stock_quantity,
        low_stock_threshold,
        category_id,
        supplier_id,
        is_active
    FROM products
    WHERE id = :id
    LIMIT 1
");

$productStmt->execute([
    ':id' => $productId
]);

$product = $productStmt->fetch();

if (!$product) {
    header("Location: products.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Initialize form values
|--------------------------------------------------------------------------
*/

$name = $product['name'];
$description = $product['description'] ?? '';
$price = $product['price'];
$stockQuantity = $product['stock_quantity'];
$lowStockThreshold = $product['low_stock_threshold'];
$categoryId = (int) $product['category_id'];
$supplierId = $product['supplier_id'] !== null
    ? (int) $product['supplier_id']
    : null;
$isActive = (int) $product['is_active'];


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
       OR id = " . ($supplierId ?? 0) . "
    ORDER BY name ASC
");

$suppliers = $supplierStmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Handle product update
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

    } elseif (mb_strlen($name) > 150) {

        $error = "Product name must not exceed 150 characters.";

    } elseif (mb_strlen($description) > 1000) {

        $error = "Description must not exceed 1000 characters.";

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
        }


        /*
        |--------------------------------------------------------------------------
        | Verify supplier
        |--------------------------------------------------------------------------
        */

        if ($error === '' && $supplierId !== null) {

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


    /*
    |--------------------------------------------------------------------------
    | Check duplicate product name
    |--------------------------------------------------------------------------
    */

    if ($error === '') {

        $duplicateStmt = $pdo->prepare("
            SELECT id
            FROM products
            WHERE LOWER(name) = LOWER(:name)
              AND id != :id
            LIMIT 1
        ");

        $duplicateStmt->execute([
            ':name' => $name,
            ':id' => $productId
        ]);

        if ($duplicateStmt->fetch()) {

            $error = "A product with this name already exists.";
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Update product
    |--------------------------------------------------------------------------
    */

    if ($error === '') {

        try {

            $updateStmt = $pdo->prepare("
                UPDATE products
                SET
                    name = :name,
                    description = :description,
                    price = :price,
                    stock_quantity = :stock_quantity,
                    low_stock_threshold = :low_stock_threshold,
                    category_id = :category_id,
                    supplier_id = :supplier_id,
                    is_active = :is_active
                WHERE id = :id
            ");

            $updateStmt->execute([
                ':name' => $name,

                ':description' =>
                    $description !== ''
                        ? $description
                        : null,

                ':price' =>
                    (float) $price,

                ':stock_quantity' =>
                    (int) $stockQuantity,

                ':low_stock_threshold' =>
                    (int) $lowStockThreshold,

                ':category_id' =>
                    $categoryId,

                ':supplier_id' =>
                    $supplierId,

                ':is_active' =>
                    $isActive,

                ':id' =>
                    $productId
            ]);


            $success = "Product updated successfully.";


            /*
            |--------------------------------------------------------------------------
            | Update local product data
            |--------------------------------------------------------------------------
            */

            $product['name'] = $name;
            $product['description'] = $description;
            $product['price'] = $price;
            $product['stock_quantity'] = $stockQuantity;
            $product['low_stock_threshold'] = $lowStockThreshold;
            $product['category_id'] = $categoryId;
            $product['supplier_id'] = $supplierId;
            $product['is_active'] = $isActive;


        } catch (PDOException $e) {

            $error = "Unable to update product.";
        }
    }
}


require_once "assets/includes/header.php";

?>


<section class="admin-page">

    <div class="container">

        <div class="page-heading">

            <h1>Edit Product</h1>

            <p>
                Update the information for this product.
            </p>

        </div>


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


        <div class="form-card">

            <form
                method="POST"
                action="edit_product.php?id=<?= $productId ?>"
            >

                <div class="product-form-grid">


                    <!-- PRODUCT NAME -->

                    <div class="form-group">

                        <label for="name">
                            Product Name
                        </label>

                        <input
                            type="text"
                            id="name"
                            name="name"
                            value="<?= htmlspecialchars($name) ?>"
                            maxlength="150"
                            placeholder="Enter product name"
                            required
                        >

                    </div>


                    <!-- PRICE -->

                    <div class="form-group">

                        <label for="price">
                            Price (₦)
                        </label>

                        <input
                            type="number"
                            id="price"
                            name="price"
                            value="<?= htmlspecialchars($price) ?>"
                            min="0"
                            step="0.01"
                            placeholder="0.00"
                            required
                        >

                    </div>


                    <!-- STOCK -->

                    <div class="form-group">

                        <label for="stock_quantity">
                            Stock Quantity
                        </label>

                        <input
                            type="number"
                            id="stock_quantity"
                            name="stock_quantity"
                            value="<?= htmlspecialchars($stockQuantity) ?>"
                            min="0"
                            step="1"
                            placeholder="0"
                            required
                        >

                    </div>


                    <!-- LOW STOCK THRESHOLD -->

                    <div class="form-group">

                        <label for="low_stock_threshold">
                            Low Stock Threshold
                        </label>

                        <input
                            type="number"
                            id="low_stock_threshold"
                            name="low_stock_threshold"
                            value="<?= htmlspecialchars($lowStockThreshold) ?>"
                            min="0"
                            step="1"
                            required
                        >

                        <small>
                            The product will be considered low stock
                            when its quantity reaches this number.
                        </small>

                    </div>


                    <!-- DESCRIPTION -->

                    <div class="form-group form-group-full">

                        <label for="description">
                            Description
                        </label>

                        <textarea
                            id="description"
                            name="description"
                            maxlength="1000"
                            placeholder="Enter product description"
                        ><?= htmlspecialchars($description) ?></textarea>

                    </div>


                    <!-- CATEGORY -->

                    <div class="form-group">

                        <label for="category_id">
                            Category
                        </label>

                        <select
                            id="category_id"
                            name="category_id"
                            required
                        >

                            <option value="">
                                Select a category
                            </option>

                            <?php foreach ($categories as $category): ?>

                                <option
                                    value="<?= (int) $category['id'] ?>"
                                    <?= $categoryId === (int) $category['id']
                                        ? 'selected'
                                        : ''
                                    ?>
                                >
                                    <?= htmlspecialchars($category['name']) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <!-- SUPPLIER -->

                    <div class="form-group">

                        <label for="supplier_id">

                            Supplier

                            <span>
                                (Optional)
                            </span>

                        </label>

                        <select
                            id="supplier_id"
                            name="supplier_id"
                        >

                            <option value="">
                                Select supplier
                            </option>

                            <?php foreach ($suppliers as $supplier): ?>

                                <option
                                    value="<?= (int) $supplier['id'] ?>"
                                    <?= $supplierId !== null
                                        && $supplierId === (int) $supplier['id']
                                            ? 'selected'
                                            : ''
                                    ?>
                                >
                                    <?= htmlspecialchars($supplier['name']) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <!-- ACTIVE STATUS -->

                    <div class="form-group checkbox-group">

                        <label>

                            <input
                                type="checkbox"
                                name="is_active"
                                value="1"
                                <?= $isActive === 1 ? 'checked' : '' ?>
                            >

                            Active Product

                        </label>

                    </div>


                    <!-- ACTIONS -->

                    <div class="form-actions form-group-full">

                        <button
                            type="submit"
                            class="btn"
                        >
                            Save Changes
                        </button>

                        <a
                            href="products.php"
                            class="back-link"
                        >
                            Back to Products
                        </a>

                    </div>


                </div>

            </form>

        </div>

    </div>

</section>


<?php require_once "assets/includes/footer.php"; ?>
