<?php

require_once "../includes/db.php";
require_once "../includes/auth.php";

requireLogin();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if (empty($_SESSION['cart'])) {
    header("Location: cart.php");
    exit;
}

$checkoutError = "";


/*
|--------------------------------------------------------------------------
| Process checkout
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['review_order'])) {

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if ($name === '') {

        $checkoutError = "Please enter your full name.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $checkoutError = "Please enter a valid email address.";

    } elseif ($phone === '') {

        $checkoutError = "Please enter your phone number.";

    } elseif ($address === '') {

        $checkoutError = "Please enter your shipping address.";

    } else {

        $productIds = array_keys($_SESSION['cart']);

        if (empty($productIds)) {
            header("Location: cart.php");
            exit;
        }

        $placeholders = implode(
            ',',
            array_fill(0, count($productIds), '?')
        );

        try {

            $pdo->beginTransaction();

            /*
            |--------------------------------------------------------------------------
            | Get products and lock their rows
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare("
                SELECT
                    p.id,
                    p.name,
                    p.price,
                    p.stock_quantity
                FROM products p
                WHERE p.id IN ($placeholders)
                AND p.is_active = 1
                FOR UPDATE
            ");

            $stmt->execute($productIds);

            $productsForOrder = $stmt->fetchAll();

            if (count($productsForOrder) !== count($productIds)) {
                throw new Exception(
                    "One or more products in your cart are no longer available."
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Calculate total and validate stock
            |--------------------------------------------------------------------------
            */

            $orderTotal = 0;

            foreach ($productsForOrder as $product) {

                $productId = (int) $product['id'];

                $quantity = (int) (
                    $_SESSION['cart'][$productId] ?? 0
                );

                $stock = (int) $product['stock_quantity'];

                if ($quantity <= 0) {
                    throw new Exception(
                        "Invalid quantity for " . $product['name'] . "."
                    );
                }

                if ($stock <= 0) {
                    throw new Exception(
                        $product['name'] . " is currently out of stock."
                    );
                }

                if ($quantity > $stock) {
                    throw new Exception(
                        "There is not enough stock for " .
                        $product['name'] . "."
                    );
                }

                $orderTotal +=
                    (float) $product['price'] * $quantity;
            }

            /*
            |--------------------------------------------------------------------------
            | Create order
            |--------------------------------------------------------------------------
            */

            $orderStmt = $pdo->prepare("
                INSERT INTO orders (
                    user_id,
                    status,
                    total_amount,
                    shipping_name,
                    shipping_email,
                    shipping_phone,
                    shipping_address,
                    notes
                )
                VALUES (
                    :user_id,
                    'pending',
                    :total_amount,
                    :shipping_name,
                    :shipping_email,
                    :shipping_phone,
                    :shipping_address,
                    :notes
                )
            ");

            $orderStmt->execute([
                ':user_id' => getUserId(),
                ':total_amount' => $orderTotal,
                ':shipping_name' => $name,
                ':shipping_email' => $email,
                ':shipping_phone' => $phone,
                ':shipping_address' => $address,
                ':notes' => $notes !== '' ? $notes : null
            ]);

            $orderId = (int) $pdo->lastInsertId();

            /*
            |--------------------------------------------------------------------------
            | Create order items
            |--------------------------------------------------------------------------
            */

            $itemStmt = $pdo->prepare("
                INSERT INTO order_items (
                    order_id,
                    product_id,
                    quantity,
                    price_at_purchase,
                    subtotal
                )
                VALUES (
                    :order_id,
                    :product_id,
                    :quantity,
                    :price_at_purchase,
                    :subtotal
                )
            ");

            foreach ($productsForOrder as $product) {

                $productId = (int) $product['id'];

                $quantity = (int) $_SESSION['cart'][$productId];

                $price = (float) $product['price'];

                $subtotal = $price * $quantity;

                $itemStmt->execute([
                    ':order_id' => $orderId,
                    ':product_id' => $productId,
                    ':quantity' => $quantity,
                    ':price_at_purchase' => $price,
                    ':subtotal' => $subtotal
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Finish transaction
            |--------------------------------------------------------------------------
            */

            $pdo->commit();

            $_SESSION['pending_order_id'] = $orderId;

            header(
                "Location: payment.php?order_id=" . $orderId
            );
            exit;

        } catch (Exception $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $checkoutError = $e->getMessage();
        }
    }
}


/*
|--------------------------------------------------------------------------
| Fetch products for checkout display
|--------------------------------------------------------------------------
*/

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

$checkoutItems = [];
$checkoutTotal = 0;

foreach ($products as $product) {

    $productId = (int) $product['id'];

    $quantity = (int) (
        $_SESSION['cart'][$productId] ?? 0
    );

    if ($quantity <= 0) {
        continue;
    }

    $stock = (int) $product['stock_quantity'];

    if ($quantity > $stock) {

        $quantity = $stock;

        if ($quantity > 0) {
            $_SESSION['cart'][$productId] = $quantity;
        } else {
            unset($_SESSION['cart'][$productId]);
            continue;
        }
    }

    $subtotal = (float) $product['price'] * $quantity;

    $checkoutTotal += $subtotal;

    $product['quantity'] = $quantity;
    $product['subtotal'] = $subtotal;

    $checkoutItems[] = $product;
}

if (empty($checkoutItems)) {
    header("Location: cart.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Header
|--------------------------------------------------------------------------
*/

require_once "assets/includes/header.php";

?>

<section class="checkout-page">

    <div class="container">

        <div class="page-heading">

            <h1>Checkout</h1>

            <p>
                Review your order and provide your delivery information.
            </p>

        </div>

        <?php if ($checkoutError !== ''): ?>

            <div class="checkout-error">
                <?= htmlspecialchars($checkoutError) ?>
            </div>

        <?php endif; ?>


        <div class="checkout-grid">

            <!-- ORDER SUMMARY -->

            <div class="checkout-order">

                <h2>Order Summary</h2>

                <?php foreach ($checkoutItems as $item): ?>

                    <div class="checkout-item">

                        <?php if (!empty($item['image_url'])): ?>

                            <img
                                src="<?= htmlspecialchars($item['image_url']) ?>"
                                alt="<?= htmlspecialchars($item['name']) ?>"
                            >

                        <?php else: ?>

                            <div class="product-image-placeholder">
                                No image
                            </div>

                        <?php endif; ?>

                        <div class="checkout-item-info">

                            <h3>
                                <?= htmlspecialchars($item['name']) ?>
                            </h3>

                            <p>
                                <?= htmlspecialchars($item['category_name']) ?>
                            </p>

                            <p>
                                Quantity:
                                <?= (int) $item['quantity'] ?>
                            </p>

                            <strong>
                                ₦<?= number_format(
                                    $item['subtotal'],
                                    2
                                ) ?>
                            </strong>

                        </div>

                    </div>

                <?php endforeach; ?>


                <div class="checkout-total">

                    <span>Total</span>

                    <strong>
                        ₦<?= number_format(
                            $checkoutTotal,
                            2
                        ) ?>
                    </strong>

                </div>

            </div>


            <!-- DELIVERY INFORMATION -->

            <div class="checkout-customer">

                <h2>Delivery Information</h2>

                <form
                    method="POST"
                    action="checkout.php"
                >

                    <div class="form-group">

                        <label for="name">
                            Full Name
                        </label>

                        <input
                            type="text"
                            id="name"
                            name="name"
                            value="<?= htmlspecialchars(
                                $_POST['name'] ?? $_SESSION['name']
                            ) ?>"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="email">
                            Email Address
                        </label>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="<?= htmlspecialchars(
                                $_POST['email'] ?? $_SESSION['email']
                            ) ?>"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="phone">
                            Phone Number
                        </label>

                        <input
                            type="tel"
                            id="phone"
                            name="phone"
                            value="<?= htmlspecialchars(
                                $_POST['phone'] ?? ''
                            ) ?>"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="address">
                            Shipping Address
                        </label>

                        <textarea
                            id="address"
                            name="address"
                            rows="4"
                            required
                        ><?= htmlspecialchars(
                            $_POST['address'] ?? ''
                        ) ?></textarea>

                    </div>


                    <div class="form-group">

                        <label for="notes">
                            Order Notes
                            <span>(Optional)</span>
                        </label>

                        <textarea
                            id="notes"
                            name="notes"
                            rows="3"
                        ><?= htmlspecialchars(
                            $_POST['notes'] ?? ''
                        ) ?></textarea>

                    </div>


                    <button
                        type="submit"
                        name="review_order"
                        class="btn"
                    >
                        Continue to Payment
                    </button>

                </form>


                <br>

                <a href="cart.php">
                    ← Back to Cart
                </a>

            </div>

        </div>

    </div>

</section>

<?php require_once "assets/includes/footer.php"; ?>