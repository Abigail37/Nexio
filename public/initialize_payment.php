<?php

require_once "../includes/db.php";
require_once "../includes/auth.php";
require_once "../includes/paystack_config.php";

requireLogin();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| Get order ID
|--------------------------------------------------------------------------
*/

$orderId = isset($_POST['order_id'])
    ? (int) $_POST['order_id']
    : 0;

if ($orderId <= 0) {
    header("Location: account.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Get customer's pending order
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        id,
        user_id,
        status,
        total_amount,
        shipping_email
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
    header("Location: payment.php?order_id=" . $orderId);
    exit;
}


/*
|--------------------------------------------------------------------------
| Convert amount to kobo
|--------------------------------------------------------------------------
*/

$amount = (int) round(
    ((float) $order['total_amount']) * 100
);


/*
|--------------------------------------------------------------------------
| Generate unique payment reference
|--------------------------------------------------------------------------
*/

$reference = 'ORD_' . $orderId . '_' . uniqid();


/*
|--------------------------------------------------------------------------
| Paystack callback URL
|--------------------------------------------------------------------------
*/

$callbackUrl =
    'http://' .
    $_SERVER['HTTP_HOST'] .
    dirname($_SERVER['PHP_SELF']) .
    '/payment_callback.php';


/*
|--------------------------------------------------------------------------
| Initialize Paystack transaction
|--------------------------------------------------------------------------
*/

$payload = [
    'email' => $order['shipping_email'],
    'amount' => $amount,
    'reference' => $reference,
    'callback_url' => $callbackUrl,
    'metadata' => [
        'order_id' => $orderId,
        'user_id' => getUserId()
    ]
];


$ch = curl_init(
    PAYSTACK_BASE_URL . '/transaction/initialize'
);

curl_setopt($ch, CURLOPT_POST, true);

curl_setopt(
    $ch,
    CURLOPT_POSTFIELDS,
    json_encode($payload)
);

curl_setopt(
    $ch,
    CURLOPT_HTTPHEADER,
    [
        'Authorization: Bearer ' . PAYSTACK_SECRET_KEY,
        'Content-Type: application/json'
    ]
);

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

curl_setopt($ch, CURLOPT_TIMEOUT, 30);


$response = curl_exec($ch);

$curlError = curl_error($ch);

curl_close($ch);


/*
|--------------------------------------------------------------------------
| Handle cURL error
|--------------------------------------------------------------------------
*/

if ($response === false || $curlError !== '') {

    die(
        "Unable to connect to Paystack. Please try again."
    );
}


$result = json_decode($response, true);


/*
|--------------------------------------------------------------------------
| Handle Paystack error
|--------------------------------------------------------------------------
*/

if (
    !isset($result['status']) ||
    $result['status'] !== true ||
    empty($result['data']['authorization_url'])
) {

    $message = $result['message']
        ?? 'Unable to initialize payment.';

    die(
        htmlspecialchars($message)
    );
}


/*
|--------------------------------------------------------------------------
| Create pending payment record
|--------------------------------------------------------------------------
*/

$paymentStmt = $pdo->prepare("
    INSERT INTO payments (
        order_id,
        payment_reference,
        transaction_reference,
        amount,
        payment_method,
        status
    )
    VALUES (
        :order_id,
        :payment_reference,
        :transaction_reference,
        :amount,
        'paystack',
        'pending'
    )
");

$paymentStmt->execute([
    ':order_id' => $orderId,
    ':payment_reference' => $reference,
    ':transaction_reference' => $result['data']['reference'],
    ':amount' => $order['total_amount']
]);


/*
|--------------------------------------------------------------------------
| Redirect customer to Paystack
|--------------------------------------------------------------------------
*/

header(
    "Location: " . $result['data']['authorization_url']
);

exit;