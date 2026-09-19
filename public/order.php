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

$orderId = isset($_GET['id'])
    ? (int) $_GET['id']
    : 0;

if ($orderId <= 0) {
    header("Location: account.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Get order
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        o.id,
        o.user_id,
        o.status,
        o.total_amount,
        o.shipping_name,
        o.shipping_email,
        o.shipping_phone,
        o.shipping_address,
        o.notes,
        o.created_at,
        o.updated_at
    FROM orders o
    WHERE o.id = :order_id
      AND o.user_id = :user_id
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
| Get order items
|--------------------------------------------------------------------------
*/

$itemStmt = $pdo->prepare("
    SELECT
        oi.id,
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


/*
|--------------------------------------------------------------------------
| Get payment
|--------------------------------------------------------------------------
*/

$paymentStmt = $pdo->prepare("
    SELECT
        id,
        payment_reference,
        transaction_reference,
        amount,
        payment_method,
        status,
        paid_at,
        created_at
    FROM payments
    WHERE order_id = :order_id
    ORDER BY id DESC
    LIMIT 1
");

$paymentStmt->execute([
    ':order_id' => $orderId
]);

$payment = $paymentStmt->fetch();


require_once "assets/includes/header.php";

?>

<section class="order-page">

    <div class="container">

        <div class="page-heading">

            <h1>
                Order #<?= (int) $order['id'] ?>
            </h1>

            <p>
                Placed on
                <?= htmlspecialchars(
                    date(
                        'F j, Y \a\t g:i A',
                        strtotime($order['created_at'])
                    )
                ) ?>
            </p>

        </div>


        <!-- ORDER STATUS -->

        <div class="order-status">

            <strong>
                Order Status:
            </strong>

            <span class="status-<?= htmlspecialchars(
                $order['status']
            ) ?>">
                <?= htmlspecialchars(
                    ucfirst($order['status'])
                ) ?>
            </span>

        </div>


        <div class="order-grid">


            <!-- ORDER ITEMS -->

            <div class="order-items">

                <h2>Items Ordered</h2>


                <?php if (empty($orderItems)): ?>

                    <p>
                        No items found for this order.
                    </p>

                <?php else: ?>

                    <?php foreach ($orderItems as $item): ?>

                        <div class="order-item">

                            <?php if (!empty($item['image_url'])): ?>

                                <img
                                    src="<?= htmlspecialchars(
                                        $item['image_url']
                                    ) ?>"
                                    alt="<?= htmlspecialchars(
                                        $item['name']
                                    ) ?>"
                                >

                            <?php endif; ?>


                            <div class="order-item-info">

                                <h3>
                                    <?= htmlspecialchars(
                                        $item['name']
                                    ) ?>
                                </h3>

                                <p>
                                    Quantity:
                                    <?= (int) $item['quantity'] ?>
                                </p>

                                <p>
                                    Price:
                                    ₦<?= number_format(
                                        (float) $item['price_at_purchase'],
                                        2
                                    ) ?>
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

                <?php endif; ?>


                <div class="order-total">

                    <span>
                        Total
                    </span>

                    <strong>
                        ₦<?= number_format(
                            (float) $order['total_amount'],
                            2
                        ) ?>
                    </strong>

                </div>

            </div>


            <!-- ORDER INFORMATION -->

            <div class="order-information">

                <h2>Order Information</h2>


                <div class="order-info-section">

                    <h3>
                        Delivery Information
                    </h3>

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
                            <strong>Notes:</strong><br>
                            <?= nl2br(
                                htmlspecialchars(
                                    $order['notes']
                                )
                            ) ?>
                        </p>

                    <?php endif; ?>

                </div>


                <!-- PAYMENT INFORMATION -->

                <div class="order-info-section">

                    <h3>
                        Payment Information
                    </h3>


                    <?php if ($payment): ?>

                        <p>
                            <strong>Method:</strong>
                            <?= htmlspecialchars(
                                ucfirst(
                                    $payment['payment_method']
                                )
                            ) ?>
                        </p>

                        <p>
                            <strong>Status:</strong>
                            <?= htmlspecialchars(
                                ucfirst(
                                    $payment['status']
                                )
                            ) ?>
                        </p>


                        <?php if (!empty(
                            $payment['transaction_reference']
                        )): ?>

                            <p>
                                <strong>
                                    Transaction Reference:
                                </strong><br>

                                <?= htmlspecialchars(
                                    $payment['transaction_reference']
                                ) ?>
                            </p>

                        <?php endif; ?>


                        <?php if (!empty($payment['paid_at'])): ?>

                            <p>
                                <strong>Paid At:</strong><br>

                                <?= htmlspecialchars(
                                    date(
                                        'F j, Y \a\t g:i A',
                                        strtotime(
                                            $payment['paid_at']
                                        )
                                    )
                                ) ?>
                            </p>

                        <?php endif; ?>

                    <?php else: ?>

                        <p>
                            No payment record found.
                        </p>

                    <?php endif; ?>

                </div>


                <!-- ACTIONS -->

                <div class="order-actions">

                    <?php if (
                        $order['status'] === 'pending'
                        && (!$payment
                            || $payment['status'] !== 'successful')
                    ): ?>

                        <a
                            href="payment.php?order_id=<?= (int) $order['id'] ?>"
                            class="btn"
                        >
                            Complete Payment
                        </a>

                    <?php endif; ?>


                    <a
                        href="account.php"
                        class="order-back-link"
                    >
                        ← Back to Account
                    </a>

                </div>

            </div>

        </div>

    </div>

</section>


<?php require_once "assets/includes/footer.php"; ?>