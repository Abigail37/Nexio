<?php

require_once "../includes/db.php";
require_once "../includes/auth.php";

requireLogin();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| Get order ID
|--------------------------------------------------------------------------
*/

$orderId = isset($_GET['order_id'])
    ? (int) $_GET['order_id']
    : 0;

if ($orderId <= 0) {
    header("Location: cart.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Get order belonging to logged-in customer
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        id,
        user_id,
        status,
        total_amount,
        shipping_name,
        shipping_email,
        shipping_phone,
        shipping_address,
        notes,
        created_at
    FROM orders
    WHERE id = :order_id
      AND user_id = :user_id
    LIMIT 1
");

$stmt->execute([
    ':order_id' => $orderId,
    ':user_id' => getUserId()
]);

$order = $stmt->fetch();


/*
|--------------------------------------------------------------------------
| Order not found
|--------------------------------------------------------------------------
*/

if (!$order) {
    header("Location: account.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Only pending orders can be paid
|--------------------------------------------------------------------------
*/

if ($order['status'] !== 'pending') {
    header(
        "Location: order.php?id=" . $orderId
    );
    exit;
}


/*
|--------------------------------------------------------------------------
| Get order items
|--------------------------------------------------------------------------
*/

$itemStmt = $pdo->prepare("
    SELECT
        oi.product_id,
        oi.quantity,
        oi.price_at_purchase,
        oi.subtotal,
        p.name,
        p.image_url
    FROM order_items oi
    INNER JOIN products p
        ON p.id = oi.product_id
    WHERE oi.order_id = :order_id
    ORDER BY oi.id ASC
");

$itemStmt->execute([
    ':order_id' => $orderId
]);

$orderItems = $itemStmt->fetchAll();


require_once "assets/includes/header.php";

?>

<section class="payment-page">

    <div class="container">

        <div class="page-heading">

            <h1>Complete Payment</h1>

            <p>
                Review your order before proceeding with payment.
            </p>

        </div>


        <div class="payment-grid">


            <!-- ORDER DETAILS -->

            <div class="payment-order">

                <h2>Order #<?= (int) $order['id'] ?></h2>


                <?php foreach ($orderItems as $item): ?>

                    <div class="payment-item">

                        <?php if (!empty($item['image_url'])): ?>

                            <img
                                src="<?= htmlspecialchars(
                                            $item['image_url']
                                        ) ?>"
                                alt="<?= htmlspecialchars(
                                            $item['name']
                                        ) ?>">

                        <?php endif; ?>


                        <div>

                            <h3>
                                <?= htmlspecialchars(
                                    $item['name']
                                ) ?>
                            </h3>

                            <p>
                                Quantity:
                                <?= (int) $item['quantity'] ?>
                            </p>

                            <strong>
                                ₦<?= number_format(
                                        (float) $item['subtotal'],
                                        2
                                    ) ?>
                            </strong>

                        </div>

                    </div>

                <?php endforeach; ?>


                <div class="payment-total">

                    <span>
                        Amount to Pay
                    </span>

                    <strong>
                        ₦<?= number_format(
                                (float) $order['total_amount'],
                                2
                            ) ?>
                    </strong>

                </div>

            </div>


            <!-- CUSTOMER DETAILS -->

            <div class="payment-details">

                <h2>Delivery Information</h2>

                <p>
                    <strong>Name:</strong><br>
                    <?= htmlspecialchars(
                        $order['shipping_name']
                    ) ?>
                </p>


                <p>
                    <strong>Email:</strong><br>
                    <?= htmlspecialchars(
                        $order['shipping_email']
                    ) ?>
                </p>


                <p>
                    <strong>Phone:</strong><br>
                    <?= htmlspecialchars(
                        $order['shipping_phone']
                    ) ?>
                </p>


                <p>
                    <strong>Address:</strong><br>
                    <?= nl2br(
                        htmlspecialchars(
                            $order['shipping_address']
                        )
                    ) ?>
                </p>


                <?php if (!empty($order['notes'])): ?>

                    <p>
                        <strong>Order Notes:</strong><br>
                        <?= nl2br(
                            htmlspecialchars(
                                $order['notes']
                            )
                        ) ?>
                    </p>

                <?php endif; ?>


                <div class="payment-status">

                    <strong>
                        Payment Status:
                    </strong>

                    <span>
                        Pending
                    </span>

                </div>


                <form
                    method="POST"
                    action="initialize_payment.php">

                    <input
                        type="hidden"
                        name="order_id"
                        value="<?= (int) $order['id'] ?>">

                    <button
                        type="submit"
                        class="btn">
                        Pay ₦<?= number_format(
                                    (float) $order['total_amount'],
                                    2
                                ) ?>
                    </button>

                </form>

                <p class="payment-note">
                    You will be redirected to Paystack to complete your payment securely.
                </p>


                <a href="cart.php">
                    ← Back to Cart
                </a>

            </div>

        </div>

    </div>

</section>


<?php require_once "assets/includes/footer.php"; ?>