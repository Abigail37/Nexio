<?php

require_once "../includes/db.php";
require_once "../includes/auth.php";

requireLogin();

$allowedRoles = [
    'superuser',
    'ceo',
    'manager',
    'accountant',
    'sales_rep',
    'cashier'
];

if (!in_array(getUserRole(), $allowedRoles, true)) {
    http_response_code(403);
    exit("Access denied.");
}


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: transactions.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Get submitted values
|--------------------------------------------------------------------------
*/

$orderId = filter_input(
    INPUT_POST,
    'order_id',
    FILTER_VALIDATE_INT
);

$amount = filter_input(
    INPUT_POST,
    'amount',
    FILTER_VALIDATE_FLOAT
);

$paymentMethod = trim(
    $_POST['payment_method'] ?? ''
);

$status = trim(
    $_POST['status'] ?? ''
);


/*
|--------------------------------------------------------------------------
| Validate
|--------------------------------------------------------------------------
*/

$allowedPaymentMethods = [
    'cash',
    'bank_transfer',
    'card',
    'online'
];

$allowedStatuses = [
    'pending',
    'successful',
    'failed',
    'refunded'
];


if (
    !$orderId ||
    $amount === false ||
    $amount <= 0 ||
    !in_array($paymentMethod, $allowedPaymentMethods, true) ||
    !in_array($status, $allowedStatuses, true)
) {

    header(
        "Location: transactions.php?error=invalid"
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| Check that order exists
|--------------------------------------------------------------------------
*/

$orderStmt = $pdo->prepare("
    SELECT
        id,
        total_amount
    FROM orders
    WHERE id = ?
    LIMIT 1
");

$orderStmt->execute([$orderId]);

$order = $orderStmt->fetch(PDO::FETCH_ASSOC);


if (!$order) {

    header(
        "Location: transactions.php?error=order_not_found"
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| Get logged-in staff ID
|--------------------------------------------------------------------------
|
| This assumes your auth system stores the logged-in user's ID
| in $_SESSION['user_id'].
|
*/

$recordedBy = $_SESSION['user_id'] ?? null;


/*
|--------------------------------------------------------------------------
| Generate transaction reference
|--------------------------------------------------------------------------
*/

$transactionReference =
    'TXN-' .
    date('YmdHis') .
    '-' .
    strtoupper(
        substr(
            bin2hex(random_bytes(3)),
            0,
            6
        )
    );


/*
|--------------------------------------------------------------------------
| Insert transaction
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    INSERT INTO transactions (
        order_id,
        transaction_reference,
        amount,
        payment_method,
        status,
        recorded_by
    )

    VALUES (?, ?, ?, ?, ?, ?)
");


$stmt->execute([
    $orderId,
    $transactionReference,
    $amount,
    $paymentMethod,
    $status,
    $recordedBy
]);


/*
|--------------------------------------------------------------------------
| Redirect
|--------------------------------------------------------------------------
*/

header(
    "Location: transactions.php?success=added"
);

exit;