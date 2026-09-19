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


/*
|--------------------------------------------------------------------------
| Fetch transactions
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        t.id,
        t.order_id,
        t.transaction_reference,
        t.amount,
        t.payment_method,
        t.status,
        t.transaction_date,

        o.shipping_name AS customer_name,
        o.shipping_email AS customer_email

    FROM transactions t

    INNER JOIN orders o
        ON o.id = t.order_id

    ORDER BY t.transaction_date DESC
");

$transactions = $stmt->fetchAll();


$pageTitle = "Transactions";

require_once "includes/admin-header.php";
require_once "includes/admin-sidebar.php";

?>

<div class="container">
    <div class="page-inner">
        <!-- Page Header -->
        <div class="page-header">
            <h3 class="fw-bold mb-3">
                Transactions
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
                        Transactions
                    </a>
                </li>
            </ul>
        </div>
        <!-- Transactions Card -->
        <div class="card card-round">
            <div class="card-header">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="card-title mb-0">
                        Transactions
                    </div>
                    <button
                        type="button"
                        class="btn btn-primary btn-round"
                        data-bs-toggle="modal"
                        data-bs-target="#addTransactionModal">
                        <!-- <i class="fas fa-plus me-1"></i> -->
                        Add Transaction
                    </button>
                </div>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table id="basic-datatables" class="display table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Transaction ID</th>
                                <th>Reference</th>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Payment Method</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if (empty($transactions)): ?>
                                <tr>
                                    <td
                                        colspan="9"
                                        class="text-center">
                                        No transactions found.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($transactions as $transaction): ?>
                                    <?php
                                    $status = $transaction['status'];
                                    $statusClass = match ($status) {
                                        'pending' => 'badge-warning',
                                        'successful' => 'badge-success',
                                        'failed' => 'badge-danger',
                                        'refunded' => 'badge-info',
                                        default => 'badge-secondary'
                                    };
                                    ?>
                                    <tr>
                                        <!-- Transaction ID -->
                                        <td>
                                            <?= (int) $transaction['id'] ?>
                                        </td>
                                        <!-- Reference -->
                                        <td>
                                            <strong>
                                                <?= htmlspecialchars(
                                                    $transaction['transaction_reference']
                                                ) ?>
                                            </strong>
                                        </td>
                                        <!-- Order -->
                                        <td>
                                            #<?= (int) $transaction['order_id'] ?>
                                        </td>
                                        <!-- Customer -->
                                        <td>
                                            <strong>
                                                <?= htmlspecialchars(
                                                    $transaction['customer_name']
                                                ) ?>
                                            </strong>
                                            <br>
                                            <small class="text-muted">
                                                <?= htmlspecialchars(
                                                    $transaction['customer_email']
                                                ) ?>
                                            </small>
                                        </td>
                                        <!-- Amount -->
                                        <td>
                                            ₦<?= number_format(
                                                    (float) $transaction['amount'],
                                                    2
                                                ) ?>
                                        </td>
                                        <!-- Payment Method -->
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
                                        <!-- Status -->
                                        <td>
                                            <span class="badge <?= $statusClass ?>">
                                                <?= htmlspecialchars(
                                                    ucfirst($status)
                                                ) ?>
                                            </span>
                                        </td>
                                        <!-- Date -->
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
                                        <!-- Action -->
                                        <td class="d-flex">
                                            <button
                                                type="button"
                                                class="btn btn-primary btn-round btn-icon view-transaction-btn me-2"

                                                data-bs-toggle="modal"
                                                data-bs-target="#transactionDetailsModal"

                                                data-transaction-id="<?= (int) $transaction['id'] ?>"

                                                data-reference="<?= htmlspecialchars(
                                                                    $transaction['transaction_reference'],
                                                                    ENT_QUOTES,
                                                                    'UTF-8'
                                                                ) ?>"

                                                data-order-id="<?= (int) $transaction['order_id'] ?>"

                                                data-customer="<?= htmlspecialchars(
                                                                    $transaction['customer_name'],
                                                                    ENT_QUOTES,
                                                                    'UTF-8'
                                                                ) ?>"

                                                data-email="<?= htmlspecialchars(
                                                                $transaction['customer_email'],
                                                                ENT_QUOTES,
                                                                'UTF-8'
                                                            ) ?>"

                                                data-amount="<?= number_format(
                                                                    (float) $transaction['amount'],
                                                                    2
                                                                ) ?>"

                                                data-method="<?= htmlspecialchars(
                                                                    ucwords(
                                                                        str_replace(
                                                                            '_',
                                                                            ' ',
                                                                            $transaction['payment_method']
                                                                        )
                                                                    ),
                                                                    ENT_QUOTES,
                                                                    'UTF-8'
                                                                ) ?>"

                                                data-status="<?= htmlspecialchars(
                                                                    ucfirst($transaction['status']),
                                                                    ENT_QUOTES,
                                                                    'UTF-8'
                                                                ) ?>"

                                                data-date="<?= htmlspecialchars(
                                                                date(
                                                                    'M j, Y',
                                                                    strtotime(
                                                                        $transaction['transaction_date']
                                                                    )
                                                                ),
                                                                ENT_QUOTES,
                                                                'UTF-8'
                                                            ) ?>">

                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <a
                                                href="delete-transaction.php?id=<?= (int) $transaction['id'] ?>"
                                                onclick="return confirm('Are you sure you want to delete this transaction? This action cannot be undone.');"
                                                class="btn btn-danger btn-round btn-icon">
                                                <i class="fas fa-trash"></i>
                                            </a>
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


<!-- =========================================================
     TRANSACTION DETAILS MODAL
========================================================= -->

<div
    class="modal fade"
    id="transactionDetailsModal"
    tabindex="-1"
    aria-labelledby="transactionDetailsModalLabel"
    aria-hidden="true">

    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <!-- Modal Header -->
            <div class="modal-header">
                <h5
                    class="modal-title"
                    id="transactionDetailsModalLabel">
                    Transaction Details
                </h5>
                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Close">
                </button>
            </div>
            <!-- Modal Body -->
            <div class="modal-body">
                <!-- Transaction Heading -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h5 class="mb-1">
                            Transaction #
                            <span id="modalTransactionId"></span>
                        </h5>
                        <small class="text-muted">
                            <span id="modalTransactionDate"></span>
                        </small>
                    </div>
                    <div>
                        <span
                            id="modalTransactionStatus"
                            class="badge"></span>
                    </div>
                </div>
                <!-- Transaction Information -->
                <div class="card card-round">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            Transaction Information
                        </h5>
                    </div>

                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <small class="text-muted d-block">Reference</small>
                                <strong id="modalTransactionReference"></strong>
                            </div>
                            <div class="col-md-6 mb-3">
                                <small class="text-muted d-block"> Order</small>
                                <strong>
                                    #<span id="modalTransactionOrder"></span>
                                </strong>
                            </div>
                            <div class="col-md-6 mb-3">
                                <small class="text-muted d-block">Customer </small>
                                <strong id="modalTransactionCustomer"></strong>
                            </div>
                            <div class="col-md-6 mb-3">
                                <small class="text-muted d-block">Email</small>
                                <strong id="modalTransactionEmail"></strong>
                            </div>
                            <div class="col-md-6 mb-3">
                                <small class="text-muted d-block">Payment Method</small>
                                <strong id="modalTransactionMethod"></strong>
                            </div>
                            <div class="col-md-6 mb-3">
                                <small class="text-muted d-block">Amount</small>
                                <strong class="text-success">
                                    ₦<span id="modalTransactionAmount"></span>
                                </strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Modal Footer -->
            <div class="modal-footer">
                <button
                    type="button"
                    class="btn btn-secondary btn-round btn-icon"
                    data-bs-dismiss="modal">
                    <i class="fas fa-window-close"></i>
                </button>
            </div>
        </div>
    </div>
</div>


<!-- =========================================================
     ADD TRANSACTION MODAL
========================================================= -->

<div
    class="modal fade"
    id="addTransactionModal"
    tabindex="-1"
    aria-labelledby="addTransactionModalLabel"
    aria-hidden="true">

    <div class="modal-dialog modal-lg modal-dialog-centered">

        <div class="modal-content">

            <form
                method="POST"
                action="add-transaction.php">

                <!-- Modal Header -->

                <div class="modal-header">

                    <h5
                        class="modal-title"
                        id="addTransactionModalLabel">
                        Add Transaction
                    </h5>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Close"></button>

                </div>


                <!-- Modal Body -->

                <div class="modal-body">

                    <div class="row">


                        <!-- Order -->

                        <div class="col-md-6 mb-3">

                            <label
                                for="transactionOrder"
                                class="form-label">
                                Order
                            </label>

                            <select
                                name="order_id"
                                id="transactionOrder"
                                class="form-select"
                                required>

                                <option value="">
                                    Select an order
                                </option>

                                <?php

                                $orderOptionsStmt = $pdo->query("
                                    SELECT
                                        id,
                                        shipping_name,
                                        total_amount,
                                        status

                                    FROM orders

                                    ORDER BY created_at DESC
                                ");

                                $orderOptions =
                                    $orderOptionsStmt->fetchAll(
                                        PDO::FETCH_ASSOC
                                    );

                                ?>

                                <?php foreach ($orderOptions as $orderOption): ?>

                                    <option
                                        value="<?= (int) $orderOption['id'] ?>"
                                        data-total="<?= htmlspecialchars(
                                                        $orderOption['total_amount']
                                                    ) ?>">

                                        #<?= (int) $orderOption['id'] ?>

                                        -
                                        <?= htmlspecialchars(
                                            $orderOption['shipping_name']
                                        ) ?>

                                        -
                                        ₦<?= number_format(
                                                (float) $orderOption['total_amount'],
                                                2
                                            ) ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>


                        <!-- Amount -->

                        <div class="col-md-6 mb-3">

                            <label
                                for="transactionAmount"
                                class="form-label">
                                Amount
                            </label>

                            <input
                                type="number"
                                name="amount"
                                id="transactionAmount"
                                class="form-control"
                                min="0.01"
                                step="0.01"
                                placeholder="Enter amount"
                                required>

                        </div>


                        <!-- Payment Method -->

                        <div class="col-md-6 mb-3">

                            <label
                                for="paymentMethod"
                                class="form-label">
                                Payment Method
                            </label>

                            <select
                                name="payment_method"
                                id="paymentMethod"
                                class="form-select"
                                required>

                                <option value="">
                                    Select payment method
                                </option>

                                <option value="cash">
                                    Cash
                                </option>

                                <option value="bank_transfer">
                                    Bank Transfer
                                </option>

                                <option value="card">
                                    Card
                                </option>

                                <option value="online">
                                    Online
                                </option>

                            </select>

                        </div>


                        <!-- Status -->

                        <div class="col-md-6 mb-3">

                            <label
                                for="transactionStatus"
                                class="form-label">
                                Status
                            </label>

                            <select
                                name="status"
                                id="transactionStatus"
                                class="form-select"
                                required>

                                <option value="successful">
                                    Successful
                                </option>

                                <option value="pending">
                                    Pending
                                </option>

                                <option value="failed">
                                    Failed
                                </option>

                                <option value="refunded">
                                    Refunded
                                </option>

                            </select>

                        </div>

                    </div>

                </div>


                <!-- Modal Footer -->

                <div class="modal-footer">
                    <button
                        type="submit"
                        class="btn btn-primary btn-round btn-icon">
                        <i class="fas fa-save"></i>
                    </button>

                    <button
                        type="button"
                        class="btn btn-secondary btn-round btn-icon"
                        data-bs-dismiss="modal">
                        <i class="fas fa-window-close"></i>
                    </button>
                </div>

            </form>

        </div>

    </div>

</div>


<script>
    document.addEventListener("DOMContentLoaded", function() {
        const transactionModal =
            document.getElementById("transactionDetailsModal");

        transactionModal.addEventListener(
            "show.bs.modal",
            function(event) {
                const button = event.relatedTarget;

                /*
                |--------------------------------------------------------------------------
                | Get transaction information
                |--------------------------------------------------------------------------
                */
                const transactionId =
                    button.getAttribute("data-transaction-id");
                const reference =
                    button.getAttribute("data-reference");
                const orderId =
                    button.getAttribute("data-order-id");
                const customer =
                    button.getAttribute("data-customer");
                const email =
                    button.getAttribute("data-email");
                const amount =
                    button.getAttribute("data-amount");
                const method =
                    button.getAttribute("data-method");
                const status =
                    button.getAttribute("data-status");
                const date =
                    button.getAttribute("data-date");

                /*
                |--------------------------------------------------------------------------
                | Populate modal
                |--------------------------------------------------------------------------
                */
                document.getElementById("modalTransactionId").textContent = transactionId;
                document.getElementById("modalTransactionReference").textContent = reference;
                document.getElementById("modalTransactionOrder").textContent = orderId;
                document.getElementById("modalTransactionCustomer").textContent = customer;
                document.getElementById("modalTransactionEmail").textContent = email;
                document.getElementById("modalTransactionAmount").textContent = amount;
                document.getElementById("modalTransactionMethod").textContent = method;
                document.getElementById("modalTransactionDate").textContent = date;


                /*
                |--------------------------------------------------------------------------
                | Status badge
                |--------------------------------------------------------------------------
                */
                const statusBadge =
                    document.getElementById(
                        "modalTransactionStatus"
                    );
                statusBadge.textContent = status;
                statusBadge.className = "badge";
                switch (status.toLowerCase()) {
                    case "pending":
                        statusBadge.classList.add(
                            "badge-warning"
                        );
                        break;
                    case "successful":
                        statusBadge.classList.add(
                            "badge-success"
                        );
                        break;
                    case "failed":
                        statusBadge.classList.add(
                            "badge-danger"
                        );
                        break;
                    case "refunded":
                        statusBadge.classList.add(
                            "badge-info"
                        );
                        break;
                    default:
                        statusBadge.classList.add(
                            "badge-secondary"
                        );
                }
            }
        );
    });
</script>

<script>
    document.addEventListener("DOMContentLoaded", function() {

        const orderSelect =
            document.getElementById("transactionOrder");

        const amountInput =
            document.getElementById("transactionAmount");


        orderSelect.addEventListener("change", function() {

            const selectedOption =
                this.options[this.selectedIndex];

            const total =
                selectedOption.getAttribute("data-total");


            if (total) {

                amountInput.value =
                    parseFloat(total).toFixed(2);

            } else {

                amountInput.value = "";

            }

        });

    });
</script>

<?php require_once "includes/admin-footer.php"; ?>