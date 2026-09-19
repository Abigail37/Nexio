<?php

require_once "../includes/db.php";
require_once "../includes/auth.php";

requireLogin();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| Inventory access
|--------------------------------------------------------------------------
*/

$allowedRoles = [
    'superuser',
    'ceo',
    'manager',
    'accountant'
];

if (!in_array(getUserRole(), $allowedRoles, true)) {
    http_response_code(403);
    die("You do not have permission to view inventory transactions.");
}


/*
|--------------------------------------------------------------------------
| Search and filter values
|--------------------------------------------------------------------------
*/

$search = trim($_GET['search'] ?? '');

$transactionType =
    trim($_GET['transaction_type'] ?? '');


$allowedTransactionTypes = [
    'stock_in',
    'sale',
    'adjustment',
    'return',
    'damaged',
    'expired'
];

if (
    $transactionType !== ''
    && !in_array(
        $transactionType,
        $allowedTransactionTypes,
        true
    )
) {
    $transactionType = '';
}


/*
|--------------------------------------------------------------------------
| Build query
|--------------------------------------------------------------------------
*/

$where = [];

$params = [];


/*
|--------------------------------------------------------------------------
| Search
|--------------------------------------------------------------------------
*/

if ($search !== '') {

    $where[] = "
        (
            p.name LIKE :search
            OR u.name LIKE :search
            OR it.reason LIKE :search
            OR it.transaction_type LIKE :search
        )
    ";

    $params[':search'] = '%' . $search . '%';
}


/*
|--------------------------------------------------------------------------
| Transaction type filter
|--------------------------------------------------------------------------
*/

if ($transactionType !== '') {

    $where[] = "
        it.transaction_type = :transaction_type
    ";

    $params[':transaction_type'] =
        $transactionType;
}


/*
|--------------------------------------------------------------------------
| Build WHERE clause
|--------------------------------------------------------------------------
*/

$whereSql = '';

if (!empty($where)) {

    $whereSql =
        'WHERE ' . implode(' AND ', $where);
}


/*
|--------------------------------------------------------------------------
| Fetch transactions
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        it.id,
        it.product_id,
        it.user_id,
        it.transaction_type,
        it.quantity,
        it.previous_stock,
        it.new_stock,
        it.reason,
        it.created_at,

        p.name AS product_name,

        u.name AS user_name

    FROM inventory_transactions it

    INNER JOIN products p
        ON p.id = it.product_id

    LEFT JOIN users u
        ON u.id = it.user_id

    $whereSql

    ORDER BY
        it.created_at DESC,
        it.id DESC
";


$stmt = $pdo->prepare($sql);

$stmt->execute($params);

$transactions = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Result count
|--------------------------------------------------------------------------
*/

$resultCount = count($transactions);


require_once "assets/includes/header.php";

?>

<section class="inventory-transactions-page">

    <div class="container">

        <div class="page-heading">

            <h1>Inventory Transactions</h1>

            <p>
                View and search the history of all stock movements.
            </p>

        </div>


        <!-- SEARCH AND FILTER -->

        <div class="transaction-filters">

            <form
                method="GET"
                action="inventory_transactions.php"
            >

                <div class="filter-row">


                    <!-- SEARCH -->

                    <div class="filter-group search-group">

                        <label for="search">
                            Search
                        </label>

                        <input
                            type="search"
                            id="search"
                            name="search"
                            value="<?= htmlspecialchars(
                                $search
                            ) ?>"
                            placeholder="Product, staff, reason..."
                        >

                    </div>


                    <!-- TRANSACTION TYPE -->

                    <div class="filter-group">

                        <label for="transaction_type">
                            Transaction Type
                        </label>

                        <select
                            id="transaction_type"
                            name="transaction_type"
                        >

                            <option value="">
                                All Transactions
                            </option>

                            <?php foreach (
                                $allowedTransactionTypes
                                as $type
                            ): ?>

                                <option
                                    value="<?= htmlspecialchars(
                                        $type
                                    ) ?>"
                                    <?= (
                                        $transactionType === $type
                                    )
                                        ? 'selected'
                                        : ''
                                    ?>
                                >

                                    <?= htmlspecialchars(
                                        ucwords(
                                            str_replace(
                                                '_',
                                                ' ',
                                                $type
                                            )
                                        )
                                    ) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <!-- BUTTONS -->

                    <div class="filter-actions">

                        <button
                            type="submit"
                            class="btn"
                        >
                            Search
                        </button>

                        <a
                            href="inventory_transactions.php"
                            class="clear-filter"
                        >
                            Clear
                        </a>

                    </div>

                </div>

            </form>

        </div>


        <!-- RESULTS -->

        <div class="transaction-results-header">

            <h2>
                Transaction History
            </h2>

            <span>
                <?= number_format($resultCount) ?>
                result<?= $resultCount === 1 ? '' : 's' ?>
            </span>

        </div>


        <div class="inventory-table-card">


            <?php if (empty($transactions)): ?>

                <div class="empty-state">

                    <p>
                        No transactions matched your search or filter.
                    </p>

                </div>

            <?php else: ?>


                <div class="table-wrapper">

                    <table class="inventory-table">

                        <thead>

                            <tr>

                                <th>Date</th>

                                <th>Product</th>

                                <th>Transaction</th>

                                <th>Quantity</th>

                                <th>Previous Stock</th>

                                <th>New Stock</th>

                                <th>Reason</th>

                                <th>Recorded By</th>

                            </tr>

                        </thead>


                        <tbody>

                            <?php foreach (
                                $transactions as $transaction
                            ): ?>

                                <?php

                                $type =
                                    $transaction[
                                        'transaction_type'
                                    ];

                                ?>

                                <tr>

                                    <!-- DATE -->

                                    <td>

                                        <?= htmlspecialchars(
                                            date(
                                                'M j, Y g:i A',
                                                strtotime(
                                                    $transaction[
                                                        'created_at'
                                                    ]
                                                )
                                            )
                                        ) ?>

                                    </td>


                                    <!-- PRODUCT -->

                                    <td>

                                        <?= htmlspecialchars(
                                            $transaction[
                                                'product_name'
                                            ]
                                        ) ?>

                                    </td>


                                    <!-- TRANSACTION TYPE -->

                                    <td>

                                        <span
                                            class="transaction-type transaction-<?= htmlspecialchars(
                                                $type
                                            ) ?>"
                                        >

                                            <?= htmlspecialchars(
                                                ucwords(
                                                    str_replace(
                                                        '_',
                                                        ' ',
                                                        $type
                                                    )
                                                )
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- QUANTITY -->

                                    <td>

                                        <?= number_format(
                                            (int) $transaction[
                                                'quantity'
                                            ]
                                        ) ?>

                                    </td>


                                    <!-- PREVIOUS STOCK -->

                                    <td>

                                        <?= number_format(
                                            (int) $transaction[
                                                'previous_stock'
                                            ]
                                        ) ?>

                                    </td>


                                    <!-- NEW STOCK -->

                                    <td>

                                        <?= number_format(
                                            (int) $transaction[
                                                'new_stock'
                                            ]
                                        ) ?>

                                    </td>


                                    <!-- REASON -->

                                    <td>

                                        <?= $transaction['reason']
                                            ? htmlspecialchars(
                                                $transaction[
                                                    'reason'
                                                ]
                                            )
                                            : '—'
                                        ?>

                                    </td>


                                    <!-- USER -->

                                    <td>

                                        <?= $transaction['user_name']
                                            ? htmlspecialchars(
                                                $transaction[
                                                    'user_name'
                                                ]
                                            )
                                            : 'System'
                                        ?>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php endif; ?>


        </div>

    </div>

</section>


<?php require_once "assets/includes/footer.php"; ?>