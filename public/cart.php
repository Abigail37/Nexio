<?php

require_once "../includes/db.php";
require_once "assets/includes/header.php";


/*
|--------------------------------------------------------------------------
| Initialize cart
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

/*
|--------------------------------------------------------------------------
| Add product to cart
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {

    $productId = (int) ($_POST['product_id'] ?? 0);
    $quantity = (int) ($_POST['quantity'] ?? 1);

    if ($productId > 0 && $quantity > 0) {

        $stmt = $pdo->prepare("
            SELECT id, stock_quantity
            FROM products
            WHERE id = :id
              AND is_active = 1
            LIMIT 1
        ");

        $stmt->execute([
            'id' => $productId
        ]);

        $product = $stmt->fetch();

        if ($product) {

            $stock = (int) $product['stock_quantity'];

            if ($stock > 0) {

                /*
                |--------------------------------------------------------------
                | If product already exists in cart, increase quantity
                |--------------------------------------------------------------
                */

                $currentQuantity = (int) (
                    $_SESSION['cart'][$productId] ?? 0
                );

                $newQuantity = $currentQuantity + $quantity;

                $_SESSION['cart'][$productId] = min(
                    $newQuantity,
                    $stock
                );
            }
        }
    }

    header("Location: cart.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Remove product from cart
|--------------------------------------------------------------------------
*/

if (isset($_GET['remove'])) {

    $removeId = (int) $_GET['remove'];

    if (isset($_SESSION['cart'][$removeId])) {
        unset($_SESSION['cart'][$removeId]);
    }

    header("Location: cart.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Update cart quantities
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cart'])) {

    $quantities = $_POST['quantity'] ?? [];

    foreach ($quantities as $productId => $quantity) {

        $productId = (int) $productId;
        $quantity = (int) $quantity;

        if ($productId <= 0) {
            continue;
        }

        if ($quantity <= 0) {
            unset($_SESSION['cart'][$productId]);
            continue;
        }

        $stmt = $pdo->prepare("
            SELECT stock_quantity
            FROM products
            WHERE id = :id
              AND is_active = 1
            LIMIT 1
        ");

        $stmt->execute([
            'id' => $productId
        ]);

        $product = $stmt->fetch();

        if (!$product) {
            unset($_SESSION['cart'][$productId]);
            continue;
        }

        $stock = (int) $product['stock_quantity'];

        $_SESSION['cart'][$productId] = min(
            $quantity,
            $stock
        );
    }

    header("Location: cart.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Get cart products
|--------------------------------------------------------------------------
*/

$cartItems = [];
$cartTotal = 0;

if (!empty($_SESSION['cart'])) {

    $productIds = array_keys($_SESSION['cart']);

    $placeholders = implode(
        ',',
        array_fill(0, count($productIds), '?')
    );

    $stmt = $pdo->prepare("
        SELECT
            p.id,
            p.name,
            p.price,
            p.stock_quantity,
            p.image_url,
            c.name AS category_name
        FROM products p
        INNER JOIN categories c
            ON p.category_id = c.id
        WHERE p.id IN ($placeholders)
          AND p.is_active = 1
          AND c.is_active = 1
    ");

    $stmt->execute($productIds);

    $products = $stmt->fetchAll();


    foreach ($products as $product) {

        $productId = (int) $product['id'];

        $quantity = (int) ($_SESSION['cart'][$productId] ?? 0);

        if ($quantity <= 0) {
            continue;
        }

        $quantity = min(
            $quantity,
            (int) $product['stock_quantity']
        );

        if ($quantity <= 0) {
            unset($_SESSION['cart'][$productId]);
            continue;
        }

        $_SESSION['cart'][$productId] = $quantity;

        $subtotal = (float) $product['price'] * $quantity;

        $cartTotal += $subtotal;

        $product['quantity'] = $quantity;
        $product['subtotal'] = $subtotal;

        $cartItems[] = $product;
    }
}

?>

<!-- =========================================================
     CART PAGE
     ========================================================= -->

<section class="cart-page">

    <div class="container">

        <!-- PAGE HEADING -->

        <div class="cart-heading">

            <span class="cart-eyebrow">
                NEXIO CART
            </span>

            <h1>
                Your Cart
            </h1>

            <p>
                Review your selected technology before checkout.
            </p>

        </div>


        <?php if (empty($cartItems)): ?>

            <!-- EMPTY CART -->

            <div class="cart-empty">

                <div class="cart-empty-icon">
                    🛒
                </div>

                <h2>
                    Your cart is empty
                </h2>

                <p>
                    You haven't added any products yet.
                    Explore our collection and find something you like.
                </p>

                <a
                    href="products.php"
                    class="btn">

                    Browse Products

                </a>

            </div>


        <?php else: ?>


            <!-- CART CONTENT -->

            <form
                method="POST"
                action="cart.php"
                class="cart-form">

                <div class="cart-layout">


                    <!-- CART ITEMS -->

                    <div class="cart-items">

                        <div class="cart-items-header">

                            <h2>
                                Cart Items
                            </h2>

                            <span>
                                <?= count($cartItems) ?>
                                <?= count($cartItems) === 1 ? 'item' : 'items' ?>
                            </span>

                        </div>


                        <?php foreach ($cartItems as $item): ?>

                            <div class="cart-item">


                                <!-- IMAGE -->

                                <a
                                    href="product.php?id=<?= (int) $item['id'] ?>"
                                    class="cart-item-image">

                                    <?php if (!empty($item['image_url'])): ?>

                                        <img
                                            src="<?= htmlspecialchars($item['image_url']) ?>"
                                            alt="<?= htmlspecialchars($item['name']) ?>">

                                    <?php else: ?>

                                        <div class="cart-image-placeholder">
                                            NEXIO
                                        </div>

                                    <?php endif; ?>

                                </a>


                                <!-- DETAILS -->

                                <div class="cart-item-details">

                                    <span class="cart-item-category">

                                        <?= htmlspecialchars($item['category_name']) ?>

                                    </span>

                                    <h3>

                                        <a
                                            href="product.php?id=<?= (int) $item['id'] ?>">

                                            <?= htmlspecialchars($item['name']) ?>

                                        </a>

                                    </h3>

                                    <p class="cart-item-price">

                                        ₦<?= number_format(
                                            (float) $item['price'],
                                            2
                                        ) ?>

                                    </p>

                                </div>


                                <!-- QUANTITY -->

                                <div class="cart-item-quantity">

                                    <label>
                                        Quantity
                                    </label>

                                    <input
                                        type="number"
                                        name="quantity[<?= (int) $item['id'] ?>]"
                                        value="<?= (int) $item['quantity'] ?>"
                                        min="1"
                                        max="<?= (int) $item['stock_quantity'] ?>">

                                </div>


                                <!-- SUBTOTAL -->

                                <div class="cart-item-subtotal">

                                    <span>
                                        Subtotal
                                    </span>

                                    <strong>

                                        ₦<?= number_format(
                                            (float) $item['subtotal'],
                                            2
                                        ) ?>

                                    </strong>

                                </div>


                                <!-- REMOVE -->

                                <a
                                    href="cart.php?remove=<?= (int) $item['id'] ?>"
                                    class="cart-remove"
                                    onclick="return confirm('Remove this product from your cart?');">

                                    ×

                                </a>

                            </div>

                        <?php endforeach; ?>


                        <!-- CART ACTIONS -->

                        <div class="cart-actions">

                            <a
                                href="products.php"
                                class="continue-shopping">

                                ← Continue Shopping

                            </a>

                            <button
                                type="submit"
                                name="update_cart"
                                class="update-cart-btn">

                                Update Cart

                            </button>

                        </div>

                    </div>


                    <!-- CART SUMMARY -->

                    <aside class="cart-summary">

                        <span class="cart-summary-label">
                            ORDER SUMMARY
                        </span>

                        <h2>
                            Cart Summary
                        </h2>


                        <div class="summary-row">

                            <span>
                                Items
                            </span>

                            <span>
                                <?= count($cartItems) ?>
                            </span>

                        </div>


                        <div class="summary-divider"></div>


                        <div class="summary-total">

                            <span>
                                Total
                            </span>

                            <strong>
                                ₦<?= number_format($cartTotal, 2) ?>
                            </strong>

                        </div>


                        <a
                            href="checkout.php"
                            class="checkout-btn">

                            Proceed to Checkout
                            <span>→</span>

                        </a>


                        <p class="secure-checkout">

                            🔒 Secure checkout

                        </p>

                    </aside>

                </div>

            </form>

        <?php endif; ?>

    </div>

</section>


<?php require_once "assets/includes/footer.php"; ?>