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

$isActive = 1;


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
    | Validate product fields
    |--------------------------------------------------------------------------
    */

    if ($name === '') {

        $error = "Product name is required.";

    } elseif (strlen($name) > 150) {

        $error = "Product name cannot exceed 150 characters.";

    } elseif (strlen($description) > 1000) {

        $error = "Product description cannot exceed 1000 characters.";

    } elseif (
        $price === '' ||
        !is_numeric($price) ||
        (float) $price < 0
    ) {

        $error = "Please enter a valid product price.";

    } elseif (
        $stockQuantity === '' ||
        filter_var($stockQuantity, FILTER_VALIDATE_INT) === false ||
        (int) $stockQuantity < 0
    ) {

        $error = "Please enter a valid stock quantity.";

    } elseif (
        $lowStockThreshold === '' ||
        filter_var($lowStockThreshold, FILTER_VALIDATE_INT) === false ||
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

        } elseif ($supplierId !== null) {

            /*
            |--------------------------------------------------------------------------
            | Verify supplier
            |--------------------------------------------------------------------------
            */

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
    | Handle product image upload
    |--------------------------------------------------------------------------
    */

    $imageUrl = null;
    $uploadedImagePath = null;

    if ($error === '' && isset($_FILES['product_image'])) {

        $image = $_FILES['product_image'];

        /*
        |--------------------------------------------------------------------------
        | Check upload error
        |--------------------------------------------------------------------------
        */

        if ($image['error'] !== UPLOAD_ERR_NO_FILE) {

            if ($image['error'] !== UPLOAD_ERR_OK) {

                $error = "There was a problem uploading the product image.";

            } elseif ($image['size'] > 5 * 1024 * 1024) {

                $error = "Product image cannot exceed 5 MB.";

            } else {

                /*
                |--------------------------------------------------------------------------
                | Validate MIME type
                |--------------------------------------------------------------------------
                */

                $allowedMimeTypes = [
                    'image/jpeg' => 'jpg',
                    'image/png'  => 'png',
                    'image/webp' => 'webp'
                ];

                $finfo = new finfo(FILEINFO_MIME_TYPE);

                $mimeType = $finfo->file(
                    $image['tmp_name']
                );

                if (!isset($allowedMimeTypes[$mimeType])) {

                    $error = "Only JPG, PNG, and WebP images are allowed.";

                } else {

                    /*
                    |--------------------------------------------------------------------------
                    | Prepare upload directory
                    |--------------------------------------------------------------------------
                    */

                    $uploadDirectory = __DIR__ . "/assets/uploads/products/";

                    if (!is_dir($uploadDirectory)) {

                        if (!mkdir($uploadDirectory, 0755, true)) {

                            $error = "Unable to create the image upload directory.";
                        }
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Generate unique filename
                    |--------------------------------------------------------------------------
                    */

                    if ($error === '') {

                        $extension = $allowedMimeTypes[$mimeType];

                        $fileName = uniqid(
                            'product_',
                            true
                        ) . '.' . $extension;

                        $destination = $uploadDirectory . $fileName;

                        /*
                        |--------------------------------------------------------------------------
                        | Move uploaded image
                        |--------------------------------------------------------------------------
                        */

                        if (
                            move_uploaded_file(
                                $image['tmp_name'],
                                $destination
                            )
                        ) {

                            $imageUrl =
                                "assets/uploads/products/" . $fileName;

                            $uploadedImagePath = $destination;

                        } else {

                            $error = "Unable to save the product image.";
                        }
                    }
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

                ':description' => $description !== ''
                    ? $description
                    : null,

                ':price' => (float) $price,

                ':stock_quantity' => (int) $stockQuantity,

                ':low_stock_threshold' => (int) $lowStockThreshold,

                ':category_id' => $categoryId,

                ':supplier_id' => $supplierId,

                ':image_url' => $imageUrl,

                ':is_active' => $isActive,

                ':created_by' => getUserId()
            ]);

            $success = "Product created successfully.";
            header("Location: products.php");

            $_POST = [];

            $isActive = 1;

        } catch (PDOException $e) {

            /*
            |--------------------------------------------------------------------------
            | Delete uploaded image if database insert fails
            |--------------------------------------------------------------------------
            */

            if (
                $uploadedImagePath !== null &&
                file_exists($uploadedImagePath)
            ) {
                unlink($uploadedImagePath);
            }

            $error = "Unable to create product.";
        }
    }
}


require_once "assets/includes/header.php";

?>


<section class="admin-page">

    <div class="container">

        <div class="page-heading">

            <h1>Add Product</h1>

            <p>
                Add a new product to your inventory.
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

            <div class="form-card">

                <form
                    method="POST"
                    action="add_product.php"
                    enctype="multipart/form-data">

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
                                maxlength="150"
                                value="<?= htmlspecialchars(
                                    $_POST['name'] ?? ''
                                ) ?>"
                                placeholder="Enter product name"
                                required>

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
                                min="0"
                                step="0.01"
                                value="<?= htmlspecialchars(
                                    $_POST['price'] ?? ''
                                ) ?>"
                                placeholder="0.00"
                                required>

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
                                min="0"
                                step="1"
                                value="<?= htmlspecialchars(
                                    $_POST['stock_quantity'] ?? ''
                                ) ?>"
                                placeholder="0"
                                required>

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
                                min="0"
                                step="1"
                                value="<?= htmlspecialchars(
                                    $_POST['low_stock_threshold'] ?? '5'
                                ) ?>"
                                required>

                            <small>
                                Product is considered low stock when
                                quantity reaches this number.
                            </small>

                        </div>


                        <!-- CATEGORY -->

                        <div class="form-group">

                            <label for="category_id">
                                Category
                            </label>

                            <select
                                id="category_id"
                                name="category_id"
                                required>

                                <option value="">
                                    Select a category
                                </option>

                                <?php foreach ($categories as $category): ?>

                                    <option
                                        value="<?= (int) $category['id'] ?>"
                                        <?= (
                                            isset($_POST['category_id'])
                                            && (int) $_POST['category_id']
                                                === (int) $category['id']
                                        )
                                            ? 'selected'
                                            : ''
                                        ?>>

                                        <?= htmlspecialchars(
                                            $category['name']
                                        ) ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>


                        <!-- SUPPLIER -->

                        <div class="form-group">

                            <label for="supplier_id">
                                Supplier
                                <span>(Optional)</span>
                            </label>

                            <select
                                id="supplier_id"
                                name="supplier_id">

                                <option value="">
                                    Select supplier
                                </option>

                                <?php foreach ($suppliers as $supplier): ?>

                                    <option
                                        value="<?= (int) $supplier['id'] ?>"
                                        <?= (
                                            isset($_POST['supplier_id'])
                                            && (int) $_POST['supplier_id']
                                                === (int) $supplier['id']
                                        )
                                            ? 'selected'
                                            : ''
                                        ?>>

                                        <?= htmlspecialchars(
                                            $supplier['name']
                                        ) ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>


                        <!-- PRODUCT IMAGE -->

                        <div class="form-group form-group-full">

                            <label for="product_image">
                                Product Image
                                <span>(Optional)</span>
                            </label>

                            <input
                                type="file"
                                id="product_image"
                                name="product_image"
                                accept="image/jpeg,image/png,image/webp">

                            <small>
                                JPG, PNG, or WebP. Maximum size: 5 MB.
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
                                rows="5"
                                maxlength="1000"
                                placeholder="Enter product description"><?= htmlspecialchars(
                                    $_POST['description'] ?? ''
                                ) ?></textarea>

                        </div>


                        <!-- ACTIVE STATUS -->

                        <div class="form-group checkbox-group">

                            <label>

                                <input
                                    type="checkbox"
                                    name="is_active"
                                    value="1"
                                    <?= $isActive ? 'checked' : '' ?>>

                                Active Product

                            </label>

                        </div>


                    </div>


                    <div class="form-actions">

                        <button
                            type="submit"
                            class="btn">
                            Add Product
                        </button>

                        <a
                            href="products.php"
                            class="back-link">
                            ← Back to Products
                        </a>

                    </div>

                </form>

            </div>

        <?php endif; ?>

    </div>

</section>


<?php require_once "assets/includes/footer.php"; ?>
