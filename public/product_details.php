<?php

require_once "../includes/db.php";
require_once "../includes/auth.php";


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

$stmt = $pdo->prepare(
    "SELECT
        p.id,
        p.name,
        p.description,
        p.price,
        p.stock_quantity,
        p.low_stock_threshold,
        p.image_url,
        c.id AS category_id,
        c.name AS category_name
     FROM products p
     INNER JOIN categories c
        ON p.category_id = c.id
     WHERE p.id = ?
       AND p.is_active = 1
       AND c.is_active = 1
     LIMIT 1"
);

$stmt->execute([$productId]);

$product = $stmt->fetch();

if (!$product) {
    header("Location: products.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Stock status
|--------------------------------------------------------------------------
*/

$stock = (int) $product['stock_quantity'];
$threshold = (int) ($product['low_stock_threshold'] ?? 5);

if ($stock <= 0) {
    $stockClass = "out-of-stock";
    $stockText = "Out of Stock";
} elseif ($stock <= $threshold) {
    $stockClass = "low-stock";
    $stockText = "Only {$stock} left";
} else {
    $stockClass = "in-stock";
    $stockText = "In Stock";
}


/*
|--------------------------------------------------------------------------
| Load header
|--------------------------------------------------------------------------
*/

require_once "assets/includes/header.php";

?>

<!-- =========================================================
     PRODUCT DETAILS
     ========================================================= -->

<section class="product-details-section">

    <div class="container">

        <!-- BREADCRUMB -->

        <div class="product-breadcrumb">

            <a href="products.php">
                Products
            </a>

            <span>/</span>

            <span>
                <?= htmlspecialchars($product['name']) ?>
            </span>

        </div>


        <!-- PRODUCT -->

        <div class="product-details-grid">

            <!-- IMAGE -->

            <div class="product-details-image">

                <?php if (!empty($product['image_url'])): ?>

                    <img
                        src="<?= htmlspecialchars($product['image_url']) ?>"
                        alt="<?= htmlspecialchars($product['name']) ?>">

                <?php else: ?>

                    <div class="product-details-placeholder">
                        <span>NEXIO</span>
                    </div>

                <?php endif; ?>

            </div>


            <!-- INFORMATION -->

            <div class="product-details-content">

                <span class="product-details-category">

                    <?= htmlspecialchars($product['category_name']) ?>

                </span>


                <h1>
                    <?= htmlspecialchars($product['name']) ?>
                </h1>


                <div class="product-details-price">

                    ₦<?= number_format(
                            (float) $product['price'],
                            2
                        ) ?>

                </div>


                <div class="product-details-stock">

                    <span class="product-stock <?= $stockClass ?>">

                        <?= htmlspecialchars($stockText) ?>

                    </span>

                </div>


                <?php if (!empty($product['description'])): ?>

                    <div class="product-details-description">

                        <h3>
                            Product Description
                        </h3>

                        <p>
                            <?= nl2br(
                                htmlspecialchars($product['description'])
                            ) ?>
                        </p>

                    </div>

                <?php endif; ?>


                <!-- ADD TO CART -->

                <?php if ($stock > 0): ?>

                    <form
                        action="cart.php"
                        method="POST"
                        class="add-to-cart-form">

                        <input
                            type="hidden"
                            name="product_id"
                            value="<?= (int) $product['id'] ?>">

                        <div class="purchase-row">
                            <div class="quantity-control">
                                <label for="quantity">
                                    Quantity
                                </label>

                                <div class="quantity-input">
                                    <button
                                        type="button"
                                        class="quantity-btn"
                                        data-action="decrease">
                                        −
                                    </button>

                                    <input
                                        type="number"
                                        id="quantity"
                                        name="quantity"
                                        value="1"
                                        min="1"
                                        max="<?= $stock ?>">

                                    <button
                                        type="button"
                                        class="quantity-btn"
                                        data-action="increase">
                                        +
                                    </button>
                                </div>
                            </div>

                            <button
                                type="submit"
                                name="add_to_cart"
                                class="add-to-cart-btn">
                                Add to Cart
                            </button>
                        </div>
                    </form>

                    <a
                        href="products.php"
                        class="back-to-products">
                        ← Back to Products
                    </a>

                <?php else: ?>

                    <div class="product-unavailable">

                        This product is currently unavailable.

                    </div>

                <?php endif; ?>


                <div class="product-features">

                    <div class="product-feature">
                        <span class="feature-icon">
                            ✓
                        </span>
                        <div>
                            <strong>Quality Products</strong>
                            <p>Technology selected for everyday use.</p>
                        </div>
                    </div>

                    <div class="product-feature">
                        <span class="feature-icon">
                            ✓
                        </span>
                        <div>
                            <strong>Secure Shopping</strong>
                            <p>Safe and convenient checkout.</p>
                        </div>
                    </div>

                    <div class="product-feature">
                        <span class="feature-icon">
                            ✓
                        </span>
                        <div>
                            <strong>Customer Support</strong>
                            <p>We're here when you need assistance.</p>
                        </div>
                    </div>

                </div>

            </div>

        </div>

    </div>

</section>

<?php require_once "assets/includes/footer.php"; ?>