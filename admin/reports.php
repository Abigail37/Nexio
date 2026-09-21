<?php

require_once "../includes/db.php";
require_once "../includes/auth.php";

requireLogin();

$allowedRoles = [
    'superuser',
    'ceo',
    'manager',
    'accountant'
];

if (!in_array(getUserRole(), $allowedRoles, true)) {
    http_response_code(403);
    exit("Access denied.");
}


/*
|--------------------------------------------------------------------------
| Date Filter
|--------------------------------------------------------------------------
*/

$fromDate = $_GET['from_date'] ?? '';
$toDate   = $_GET['to_date'] ?? '';

$dateCondition = '';
$dateParams = [];

if (!empty($fromDate) && !empty($toDate)) {

    $dateCondition = "
        AND DATE(o.created_at)
        BETWEEN ? AND ?
    ";

    $dateParams = [
        $fromDate,
        $toDate
    ];
}


/*
|--------------------------------------------------------------------------
| SALES REPORT
|--------------------------------------------------------------------------
*/

$salesStmt = $pdo->prepare("
    SELECT
        o.id,
        o.shipping_name,
        o.shipping_email,
        o.total_amount,
        o.status,
        o.created_at

    FROM orders o

    WHERE 1 = 1

    $dateCondition

    ORDER BY o.created_at DESC
");

$salesStmt->execute($dateParams);

$salesReport = $salesStmt->fetchAll(
    PDO::FETCH_ASSOC
);


/*
|--------------------------------------------------------------------------
| TRANSACTION REPORT
|--------------------------------------------------------------------------
*/

$transactionDateCondition = '';
$transactionParams = [];

if (!empty($fromDate) && !empty($toDate)) {

    $transactionDateCondition = "
        AND DATE(t.transaction_date)
        BETWEEN ? AND ?
    ";

    $transactionParams = [
        $fromDate,
        $toDate
    ];
}


$transactionStmt = $pdo->prepare("
    SELECT
        t.id,
        t.transaction_reference,
        t.order_id,
        o.shipping_name AS customer_name,
        t.amount,
        t.payment_method,
        t.status,
        t.transaction_date

    FROM transactions t

    INNER JOIN orders o
        ON o.id = t.order_id

    WHERE 1 = 1

    $transactionDateCondition

    ORDER BY t.transaction_date DESC
");

$transactionStmt->execute($transactionParams);

$transactionReport = $transactionStmt->fetchAll(
    PDO::FETCH_ASSOC
);


/*
|--------------------------------------------------------------------------
| PRODUCT SALES REPORT
|--------------------------------------------------------------------------
*/

$productDateCondition = '';
$productParams = [];

if (!empty($fromDate) && !empty($toDate)) {

    $productDateCondition = "
        AND DATE(o.created_at)
        BETWEEN ? AND ?
    ";

    $productParams = [
        $fromDate,
        $toDate
    ];
}


$productStmt = $pdo->prepare("
    SELECT
        p.name AS product_name,

        SUM(oi.quantity) AS quantity_sold,

        SUM(oi.subtotal) AS total_revenue

    FROM order_items oi

    INNER JOIN orders o
        ON o.id = oi.order_id

    INNER JOIN products p
        ON p.id = oi.product_id

    WHERE o.status != 'cancelled'

    $productDateCondition

    GROUP BY
        p.id,
        p.name

    ORDER BY quantity_sold DESC
");

$productStmt->execute($productParams);

$productReport = $productStmt->fetchAll(
    PDO::FETCH_ASSOC
);


/*
|--------------------------------------------------------------------------
| Report Totals
|--------------------------------------------------------------------------
*/

$totalSales = 0;
$totalOrders = count($salesReport);

foreach ($salesReport as $sale) {

    if ($sale['status'] !== 'cancelled') {

        $totalSales += (float) $sale['total_amount'];
    }
}


$totalTransactions = count($transactionReport);

$totalProductUnits = 0;

foreach ($productReport as $product) {

    $totalProductUnits +=
        (int) $product['quantity_sold'];
}


$pageTitle = "Reports";

require_once "includes/admin-header.php";

?>

<div class="container">

    <div class="page-inner">

        <!-- =====================================================
             PAGE HEADER
        ====================================================== -->

        <div class="page-header">

            <h3 class="fw-bold mb-3">
                Reports
            </h3>

            <ul class="breadcrumbs mb-3">

                <li class="nav-home">

                    <a href="dashboard.php">
                        <i class="icon-home"></i>
                    </a>

                </li>

                <li class="separator">

                    <i class="icon-arrow-right"></i>

                </li>

                <li class="nav-item">

                    <a href="#">
                        Reports
                    </a>

                </li>

            </ul>

        </div>


        <!-- =====================================================
             DATE FILTER
        ====================================================== -->

        <div class="card card-round">

            <div class="card-header">

                <div class="card-title">
                    Generate Report
                </div>

            </div>


            <div class="card-body">

                <form method="GET">

                    <div class="row align-items-end">

                        <div class="col-md-4 mb-3">

                            <label
                                for="from_date"
                                class="form-label">
                                From Date
                            </label>

                            <input
                                type="date"
                                name="from_date"
                                id="from_date"
                                class="form-control"
                                value="<?= htmlspecialchars($fromDate) ?>">

                        </div>


                        <div class="col-md-4 mb-3">

                            <label
                                for="to_date"
                                class="form-label">
                                To Date
                            </label>

                            <input
                                type="date"
                                name="to_date"
                                id="to_date"
                                class="form-control"
                                value="<?= htmlspecialchars($toDate) ?>">

                        </div>


                        <div class="col-md-4 mb-3">

                            <button
                                type="submit"
                                class="btn btn-primary me-2 btn-round btn-icon">
                                <i class="fas fa-filter"></i>
                            </button>

                            <a
                                href="reports.php"
                                class="btn btn-secondary btn-round">
                                Reset
                            </a>

                        </div>

                    </div>

                </form>

            </div>

        </div>


        <!-- =====================================================
             REPORT SUMMARY
        ====================================================== -->
        <div class="row">
            <!-- orders -->
             <div class="col-sm-6 col-md-4">
                <div class="card card-stats card-round">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-icon">
                                <div
                                    class="icon-big text-center icon-primary bubble-shadow-small">
                                    <i class="fas fa-luggage-cart"></i>
                                </div>
                            </div>
                            <div class="col col-stats ms-3 ms-sm-0">
                                <div class="numbers">
                                    <p class="card-category">Orders</p>
                                    <h4 class="card-title"><?= number_format( $totalOrders) ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- sales  -->
            <div class="col-sm-6 col-md-4">
                <div class="card card-stats card-round">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-icon">
                                <div
                                    class="icon-big text-center icon-secondary bubble-shadow-small">
                                    <i class="fas fa-shopping-bag"></i>
                                </div>
                            </div>
                            <div class="col col-stats ms-3 ms-sm-0">
                                <div class="numbers">
                                    <p class="card-category">Sales</p>
                                    <h4 class="card-title"> ₦<?= number_format( $totalSales,2) ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- totalunits sold  -->
             <div class="col-sm-6 col-md-4">
                <div class="card card-stats card-round">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-icon">
                                <div
                                    class="icon-big text-center icon-info bubble-shadow-small">
                                    <i class="fas fa-shipping-fast"></i>
                                </div>
                            </div>
                            <div class="col col-stats ms-3 ms-sm-0">
                                <div class="numbers">
                                    <p class="card-category">Units Sold</p>
                                    <h4 class="card-title"><?= number_format( $totalProductUnits) ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <!-- =====================================================
             SALES REPORT
        ====================================================== -->
        <div class="card card-round">
            <div class="card-header">
                <div class="card-title">
                    Sales Report
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="basic-datatables" class="display table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Email</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if (empty($salesReport)): ?>
                                <tr>
                                    <td
                                        colspan="6"
                                        class="text-center text-muted">
                                        No sales found for the selected period.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($salesReport as $sale): ?>
                                    <tr>
                                        <td>
                                            <?= (int) $sale['id'] ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars(
                                                $sale['shipping_name']
                                            ) ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars(
                                                $sale['shipping_email']
                                            ) ?>
                                        </td>

                                        <td>
                                            ₦<?= number_format(
                                                    (float) $sale['total_amount'],
                                                    2
                                                ) ?>
                                        </td>

                                        <td>

                                            <?php

                                            $statusClass = match ($sale['status']) {

                                                'pending'
                                                => 'badge-warning',

                                                'processing'
                                                => 'badge-info',

                                                'shipped'
                                                => 'badge-primary',

                                                'completed'
                                                => 'badge-success',

                                                'cancelled'
                                                => 'badge-danger',

                                                default
                                                => 'badge-secondary'
                                            };

                                            ?>

                                            <span
                                                class="badge <?= $statusClass ?>">

                                                <?= htmlspecialchars(
                                                    ucfirst(
                                                        $sale['status']
                                                    )
                                                ) ?>

                                            </span>

                                        </td>

                                        <td>

                                            <?= htmlspecialchars(
                                                date(
                                                    'M j, Y',
                                                    strtotime(
                                                        $sale['created_at']
                                                    )
                                                )
                                            ) ?>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </div>

        </div>


        <!-- =====================================================
             TRANSACTION REPORT
        ====================================================== -->
        <div class="card card-round">
            <div class="card-header">
                <div class="card-title">
                    Transaction Report
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="basic-datatables" class="display table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Reference</th>
                                <th> Order</th>
                                <th> Customer</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($transactionReport)): ?>
                                <tr>
                                    <td
                                        colspan="7"
                                        class="text-center text-muted">
                                        No transactions found for the selected period.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach (
                                    $transactionReport
                                    as $transaction
                                ): ?>
                                    <tr>
                                        <td>
                                            <strong>
                                                <?= htmlspecialchars(
                                                    $transaction['transaction_reference']
                                                ) ?>
                                            </strong>
                                        </td>
                                        <td>
                                            #<?= (int) $transaction['order_id'] ?>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars(
                                                $transaction['customer_name']
                                            ) ?>
                                        </td>
                                        <td>
                                            ₦<?= number_format(
                                                    (float) $transaction['amount'],
                                                    2
                                                ) ?>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars(
                                                ucwords(
                                                    str_replace(
                                                        '_',
                                                        ' ',
                                                        $transaction['payment_method']
                                                    )
                                                )
                                            ) ?>
                                        </td>
                                        <td>
                                            <span class="badge
                                                <?php
                                                echo match ($transaction['status']) {
                                                    'successful' => 'badge-success',
                                                    'pending' => 'badge-warning',
                                                    'failed' => 'badge-danger',
                                                    'refunded' => 'badge-info',
                                                    default => 'badge-secondary'
                                                };
                                                ?>
                                            ">
                                                <?= htmlspecialchars(
                                                    ucfirst(
                                                        $transaction['status']
                                                    )
                                                ) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars(
                                                date(
                                                    'M j, Y',
                                                    strtotime(
                                                        $transaction['transaction_date']
                                                    )
                                                )
                                            ) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>


        <!-- =====================================================
             PRODUCT SALES REPORT
        ====================================================== -->
        <div class="card card-round">
            <div class="card-header">
                <div class="card-title">
                    Product Sales Report
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="basic-datatables" class="display table table-striped table-hover">
                        <thead>
                            <tr>
                                <th> # </th>
                                <th> Product</th>
                                <th> Quantity Sold</th>
                                <th> Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($productReport)): ?>
                                <tr>
                                    <td
                                        colspan="4"
                                        class="text-center text-muted">
                                        No product sales found for the selected period.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php
                                $counter = 1;
                                ?>
                                <?php foreach ($productReport as $product): ?>
                                    <tr>
                                        <td>
                                            <?= $counter++ ?>
                                        </td>
                                        <td>
                                            <strong>
                                                <?= htmlspecialchars(
                                                    $product['product_name']
                                                ) ?>
                                            </strong>
                                        </td>
                                        <td>
                                            <?= number_format(
                                                (int) $product['quantity_sold']
                                            ) ?>
                                        </td>
                                        <td>
                                            ₦<?= number_format(
                                                    (float) $product['total_revenue'],
                                                    2
                                                ) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>


<?php require_once "includes/admin-footer.php"; ?>