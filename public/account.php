<?php

require_once "../includes/db.php";
require_once "../includes/auth.php";

requireLogin();

/*
|--------------------------------------------------------------------------
| Get customer's orders
|--------------------------------------------------------------------------
*/

$orderStmt = $pdo->prepare("
    SELECT
        id,
        total_amount,
        status,
        created_at
    FROM orders
    WHERE user_id = :user_id
    ORDER BY created_at DESC
");

$orderStmt->execute([
    ':user_id' => getUserId()
]);

$orders = $orderStmt->fetchAll();

require_once "assets/includes/header.php";

?>

<section class="account-page">

    <div class="container">

        <div class="page-heading">

            <h1>My Account</h1>

            <p>
                Welcome,
                <?= htmlspecialchars($_SESSION['name']) ?>!
            </p>

        </div>


        <!-- ACCOUNT INFORMATION -->

        <div class="account-card">

            <h2>Account Information</h2>

            <p>
                <strong>Name:</strong>
                <?= htmlspecialchars($_SESSION['name']) ?>
            </p>

            <p>
                <strong>Email:</strong>
                <?= htmlspecialchars($_SESSION['email']) ?>
            </p>

            <p style="text-transform: capitalize;">
                <strong>Role:</strong>
                <?= htmlspecialchars($_SESSION['role']) ?>
            </p>

        </div>


        <!-- ORDER HISTORY -->

        <div class="account-card order-history">

            <h2>My Orders</h2>

            <?php if (empty($orders)): ?>

                <p class="no-orders">
                    You haven't placed any orders yet.
                </p>

                <a
                    href="products.php"
                    class="btn"
                >
                    Start Shopping
                </a>

            <?php else: ?>

                <div class="orders-list">

                    <?php foreach ($orders as $order): ?>

                        <div class="order-history-item">

                            <div class="order-history-info">

                                <h3>
                                    Order #<?= (int) $order['id'] ?>
                                </h3>

                                <p>
                                    <?= htmlspecialchars(
                                        date(
                                            'F j, Y \a\t g:i A',
                                            strtotime($order['created_at'])
                                        )
                                    ) ?>
                                </p>

                            </div>


                            <div class="order-history-details">

                                <strong>
                                    ₦<?= number_format(
                                        (float) $order['total_amount'],
                                        2
                                    ) ?>
                                </strong>

                                <span class="order-status-badge status-<?= htmlspecialchars(
                                    $order['status']
                                ) ?>">
                                    <?= htmlspecialchars(
                                        ucfirst($order['status'])
                                    ) ?>
                                </span>

                                <a
                                    href="order.php?id=<?= (int) $order['id'] ?>"
                                    class="btn"
                                >
                                    View Order
                                </a>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </div>


        <!-- ACCOUNT ACTIONS -->

        <div class="account-actions">

            <a
                href="products.php"
                class="btn"
            >
                Continue Shopping
            </a>

            <a
                href="cart.php"
                class="btn"
            >
                View Cart
            </a>

            <a
                href="logout.php"
                class="btn"
            >
                Logout
            </a>

        </div>

    </div>

</section>


<?php require_once "assets/includes/footer.php"; ?>