<?php

require_once "../includes/db.php";
require_once "../includes/auth.php";
require_once "../includes/paystack_config.php";
require_once "../includes/mailer.php";

requireLogin();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| Get Paystack reference
|--------------------------------------------------------------------------
*/

$reference = trim($_GET['reference'] ?? '');

if ($reference === '') {
    die("Payment reference is missing.");
}


/*
|--------------------------------------------------------------------------
| Find payment and order
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        p.id AS payment_id,
        p.order_id,
        p.payment_reference,
        p.amount,
        p.status AS payment_status,

        o.user_id,
        o.total_amount,
        o.status AS order_status

    FROM payments p

    INNER JOIN orders o
        ON o.id = p.order_id

    WHERE p.payment_reference = :reference
      AND o.user_id = :user_id

    LIMIT 1
");

$stmt->execute([
    ':reference' => $reference,
    ':user_id' => getUserId()
]);

$payment = $stmt->fetch();


/*
|--------------------------------------------------------------------------
| Payment not found
|--------------------------------------------------------------------------
*/

if (!$payment) {
    die("Payment record not found.");
}


/*
|--------------------------------------------------------------------------
| Already processed
|--------------------------------------------------------------------------
*/

if ($payment['payment_status'] === 'successful') {

    header(
        "Location: order.php?id=" .
            (int) $payment['order_id']
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| Verify transaction with Paystack
|--------------------------------------------------------------------------
*/

$url =
    PAYSTACK_BASE_URL .
    '/transaction/verify/' .
    urlencode($reference);


$ch = curl_init($url);

curl_setopt(
    $ch,
    CURLOPT_RETURNTRANSFER,
    true
);

curl_setopt(
    $ch,
    CURLOPT_HTTPHEADER,
    [
        'Authorization: Bearer ' . PAYSTACK_SECRET_KEY,
        'Cache-Control: no-cache'
    ]
);

curl_setopt(
    $ch,
    CURLOPT_TIMEOUT,
    30
);


$response = curl_exec($ch);

$curlError = curl_error($ch);

curl_close($ch);


if ($response === false || $curlError !== '') {

    die("Unable to verify payment. Please contact support.");
}


$result = json_decode($response, true);


if (
    !is_array($result) ||
    !isset($result['status']) ||
    $result['status'] !== true ||
    !isset($result['data'])
) {

    die("Payment verification failed.");
}


$transaction = $result['data'];

$transactionStatus =
    $transaction['status'] ?? '';

$transactionAmount =
    (int) ($transaction['amount'] ?? 0);

$expectedAmount =
    (int) round(
        ((float) $payment['amount']) * 100
    );

$transactionReference =
    $transaction['reference'] ?? '';


/*
|--------------------------------------------------------------------------
| Verify reference
|--------------------------------------------------------------------------
*/

if ($transactionReference !== $reference) {

    die("Payment reference verification failed.");
}


/*
|--------------------------------------------------------------------------
| Verify amount
|--------------------------------------------------------------------------
*/

if ($transactionAmount !== $expectedAmount) {

    $failedStmt = $pdo->prepare("
        UPDATE payments

        SET
            status = 'failed',
            transaction_reference = :transaction_reference,
            updated_at = CURRENT_TIMESTAMP

        WHERE id = :payment_id
    ");

    $failedStmt->execute([
        ':transaction_reference' =>
        $transactionReference,

        ':payment_id' =>
        $payment['payment_id']
    ]);

    die("Payment amount does not match the order amount.");
}


/*
|--------------------------------------------------------------------------
| Payment successful
|--------------------------------------------------------------------------
*/

if ($transactionStatus === 'success') {

    try {

        $pdo->beginTransaction();


        /*
        |--------------------------------------------------------------------------
        | Lock the payment row
        |--------------------------------------------------------------------------
        */

        $lockPaymentStmt = $pdo->prepare("
            SELECT
                id,
                order_id,
                status
            FROM payments
            WHERE id = :payment_id
            FOR UPDATE
        ");

        $lockPaymentStmt->execute([
            ':payment_id' =>
            $payment['payment_id']
        ]);

        $lockedPayment =
            $lockPaymentStmt->fetch();


        /*
        |--------------------------------------------------------------------------
        | Prevent duplicate processing
        |--------------------------------------------------------------------------
        */

        if (
            !$lockedPayment ||
            $lockedPayment['status'] === 'successful'
        ) {

            $pdo->commit();

            header(
                "Location: order.php?id=" .
                    (int) $payment['order_id']
            );

            exit;
        }


        /*
        |--------------------------------------------------------------------------
        | Get order items and lock products
        |--------------------------------------------------------------------------
        */

        $itemsStmt = $pdo->prepare("
            SELECT
                oi.product_id,
                oi.quantity,
                p.name,
                p.stock_quantity

            FROM order_items oi

            INNER JOIN products p
                ON p.id = oi.product_id

            WHERE oi.order_id = :order_id

            FOR UPDATE
        ");

        $itemsStmt->execute([
            ':order_id' =>
            $payment['order_id']
        ]);

        $orderItems = $itemsStmt->fetchAll();


        if (empty($orderItems)) {

            throw new Exception(
                "No products were found for this order."
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Prepare stock update
        |--------------------------------------------------------------------------
        */

        $stockStmt = $pdo->prepare("
            UPDATE products

            SET
                stock_quantity =
                    stock_quantity - :quantity,
                updated_at = CURRENT_TIMESTAMP

            WHERE id = :product_id
              AND stock_quantity >= :quantity
        ");


        /*
        |--------------------------------------------------------------------------
        | Prepare inventory transaction
        |--------------------------------------------------------------------------
        */

        $inventoryStmt = $pdo->prepare("
            INSERT INTO inventory_transactions (
                product_id,
                user_id,
                transaction_type,
                quantity,
                previous_stock,
                new_stock,
                reason
            )

            VALUES (
                :product_id,
                :user_id,
                'sale',
                :quantity,
                :previous_stock,
                :new_stock,
                :reason
            )
        ");


        /*
        |--------------------------------------------------------------------------
        | Reduce stock
        |--------------------------------------------------------------------------
        */

        foreach ($orderItems as $item) {

            $productId =
                (int) $item['product_id'];

            $quantity =
                (int) $item['quantity'];

            $previousStock =
                (int) $item['stock_quantity'];


            if ($quantity <= 0) {

                throw new Exception(
                    "Invalid quantity for " .
                        $item['name'] . "."
                );
            }


            if ($previousStock < $quantity) {

                throw new Exception(
                    "Insufficient stock for " .
                        $item['name'] . "."
                );
            }


            $newStock =
                $previousStock - $quantity;


            /*
            |--------------------------------------------------------------------------
            | Update product stock
            |--------------------------------------------------------------------------
            */

            $stockStmt->execute([
                ':quantity' =>
                $quantity,

                ':product_id' =>
                $productId
            ]);


            if ($stockStmt->rowCount() !== 1) {

                throw new Exception(
                    "Unable to update stock for " .
                        $item['name'] . "."
                );
            }


            /*
            |--------------------------------------------------------------------------
            | Record inventory transaction
            |--------------------------------------------------------------------------
            */

            $inventoryStmt->execute([
                ':product_id' =>
                $productId,

                ':user_id' =>
                getUserId(),

                ':quantity' =>
                $quantity,

                ':previous_stock' =>
                $previousStock,

                ':new_stock' =>
                $newStock,

                ':reason' =>
                'Sale - Order #' .
                    $payment['order_id']
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Update payment
        |--------------------------------------------------------------------------
        */

        $paymentStmt = $pdo->prepare("
            UPDATE payments

            SET
                transaction_reference = :transaction_reference,
                status = 'successful',
                paid_at = :paid_at,
                updated_at = CURRENT_TIMESTAMP

            WHERE id = :payment_id
              AND status = 'pending'
        ");

        $paymentStmt->execute([
            ':transaction_reference' =>
            $transactionReference,

            ':paid_at' =>
            !empty($transaction['paid_at'])
                ? date(
                    'Y-m-d H:i:s',
                    strtotime(
                        $transaction['paid_at']
                    )
                )
                : date('Y-m-d H:i:s'),

            ':payment_id' =>
            $payment['payment_id']
        ]);


        /*
        |--------------------------------------------------------------------------
        | Update order
        |--------------------------------------------------------------------------
        */

        $orderStmt = $pdo->prepare("
            UPDATE orders

            SET
                status = 'processing',
                updated_at = CURRENT_TIMESTAMP

            WHERE id = :order_id
              AND user_id = :user_id
              AND status = 'pending'
        ");

        $orderStmt->execute([
            ':order_id' =>
            $payment['order_id'],

            ':user_id' =>
            getUserId()
        ]);


        /*
        |--------------------------------------------------------------------------
        | Commit everything
        |--------------------------------------------------------------------------
        */

        $pdo->commit();

        try {

            // Get customer details
            $customerStmt = $pdo->prepare("
        SELECT
            name,
            email
        FROM users
        WHERE id = :user_id
        LIMIT 1
    ");

            $customerStmt->execute([
                ':user_id' => $payment['user_id']
            ]);

            $customer = $customerStmt->fetch();


            // Get order items with prices
            $emailItemsStmt = $pdo->prepare("
        SELECT
            oi.quantity,
            oi.price,
            p.name

        FROM order_items oi

        INNER JOIN products p
            ON p.id = oi.product_id

        WHERE oi.order_id = :order_id
    ");

            $emailItemsStmt->execute([
                ':order_id' => $payment['order_id']
            ]);

            $emailItems = $emailItemsStmt->fetchAll();


            if ($customer && !empty($emailItems)) {

                sendOrderConfirmationEmail(
                    $customer['email'],
                    $customer['name'],
                    $payment['order_id'],
                    $payment['total_amount'],
                    $emailItems
                );
            }
        } catch (Exception $e) {

            // Email failure should not affect the completed order.
            error_log(
                "Nexio Order Confirmation Email Error: " .
                    $e->getMessage()
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Clear cart
        |--------------------------------------------------------------------------
        */

        $_SESSION['cart'] = [];

        unset(
            $_SESSION['pending_order_id']
        );


        /*
        |--------------------------------------------------------------------------
        | Redirect to order
        |--------------------------------------------------------------------------
        */

        header(
            "Location: order.php?id=" .
                (int) $payment['order_id']
        );

        exit;
    } catch (Exception $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        die("Payment was successful, but the order could not be completed. Please contact support.");
    }
}


/*
|--------------------------------------------------------------------------
| Payment failed
|--------------------------------------------------------------------------
*/

$failedStmt = $pdo->prepare("
    UPDATE payments

    SET
        transaction_reference = :transaction_reference,
        status = 'failed',
        updated_at = CURRENT_TIMESTAMP

    WHERE id = :payment_id
");

$failedStmt->execute([
    ':transaction_reference' =>
    $transactionReference !== ''
        ? $transactionReference
        : null,

    ':payment_id' =>
    $payment['payment_id']
]);


require_once "includes/header.php";

?>

<section class="payment-result">

    <div class="container">

        <h1>Payment Not Completed</h1>

        <p>
            Your payment was not successful or was not completed.
        </p>

        <p>
            Your order has not been marked as paid.
        </p>

        <a
            href="payment.php?order_id=<?= (int) $payment['order_id'] ?>"
            class="btn">
            Try Payment Again
        </a>

    </div>

</section>

<?php require_once "includes/footer.php"; ?>