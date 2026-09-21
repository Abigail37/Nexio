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
| Fetch orders
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        o.id,
        o.user_id,
        o.status,
        o.total_amount,
        o.shipping_name,
        o.shipping_email,
        o.created_at,

        COALESCE(SUM(oi.quantity), 0) AS item_count

    FROM orders o

    LEFT JOIN order_items oi
        ON oi.order_id = o.id

    GROUP BY
        o.id,
        o.user_id,
        o.status,
        o.total_amount,
        o.shipping_name,
        o.shipping_email,
        o.created_at

    ORDER BY o.created_at DESC
");

$orders = $stmt->fetchAll();


$pageTitle = "Orders";

require_once "includes/admin-header.php";
?>

<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <h3 class="fw-bold mb-3">
                Orders
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
                    <a href="#">Orders</a>
                </li>
            </ul>
        </div>
        <div class="card card-round">
            <div class="card-header">
                <div class="card-title">
                    Orders
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
                                <th>Items</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if (empty($orders)): ?>
                                <tr>
                                    <td colspan="8" class="text-center">
                                        No orders found.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td> <?= (int) $order['id'] ?></td>
                                        <td>
                                            <strong>
                                                <?= htmlspecialchars($order['shipping_name']) ?>
                                            </strong>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($order['shipping_email']) ?>
                                        </td>
                                        <td>
                                            <?= number_format((int) $order['item_count']) ?>
                                        </td>
                                        <td>
                                            ₦<?= number_format((float) $order['total_amount'], 2) ?>
                                        </td>
                                        <td>
                                            <?php
                                            $status = $order['status'];
                                            $statusClass = match ($status) {
                                                'pending' => 'badge-warning',
                                                'processing' => 'badge-info',
                                                'shipped' => 'badge-primary',
                                                'completed' => 'badge-success',
                                                'cancelled' => 'badge-danger',
                                                default => 'badge-secondary'
                                            };
                                            ?>
                                            <span class="badge <?= $statusClass ?>">
                                                <?= htmlspecialchars(ucfirst($status)) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars(date('M j, Y', strtotime($order['created_at']))) ?>
                                        </td>

                                        <td>
                                            <button
                                                type="button"
                                                class="btn btn-primary view-order-btn btn-round btn-icon"
                                                data-bs-toggle="modal"
                                                data-bs-target="#orderDetailsModal"

                                                data-order-id="<?= (int) $order['id'] ?>"

                                                data-customer="<?= htmlspecialchars(
                                                                    $order['shipping_name'],
                                                                    ENT_QUOTES,
                                                                    'UTF-8'
                                                                ) ?>"

                                                data-email="<?= htmlspecialchars(
                                                                $order['shipping_email'],
                                                                ENT_QUOTES,
                                                                'UTF-8'
                                                            ) ?>"

                                                data-items="<?= (int) $order['item_count'] ?>"

                                                data-total="<?= number_format(
                                                                (float) $order['total_amount'],
                                                                2
                                                            ) ?>"

                                                data-status="<?= htmlspecialchars(
                                                                    ucfirst($order['status']),
                                                                    ENT_QUOTES,
                                                                    'UTF-8'
                                                                ) ?>"

                                                data-date="<?= htmlspecialchars(
                                                                date('M j, Y', strtotime($order['created_at'])),
                                                                ENT_QUOTES,
                                                                'UTF-8'
                                                            ) ?>">
                                                <i class="fas fa-eye"></i>
                                            </button>
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
     ORDER DETAILS MODAL
========================================================= -->

<div class="modal fade" id="orderDetailsModal" tabindex="-1" role="dialog"
    aria-labelledby="orderDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="orderDetailsModalLabel">Order Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                <!-- <span aria-hidden="true">&times;</span> -->
                </button>
            </div>

            <div class="modal-body">
                <!-- Order heading -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h5 class="mb-1">
                            Order #<span id="modalOrderId"></span>
                        </h5>
                        <small class="text-muted">
                            <span id="modalOrderDate"></span>
                        </small>
                    </div>
                    <div>
                        <span id="modalOrderStatus" class="badge"></span>
                    </div>
                </div>

                <!-- Customer Information -->
                <div class="card card-round">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Customer Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <small class="text-muted d-block"> Customer</small>
                                <strong id="modalCustomer"></strong>
                            </div>
                            <div class="col-md-6 mb-3">
                                <small class="text-muted d-block">Email</small>
                                <strong id="modalEmail"></strong>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Summary -->
                <div class="card card-round mt-3">
                    <div class="card-header">
                        <h5 class="card-title mb-0"> Order Summary</h5>
                    </div>

                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <small class="text-muted d-block"> Number of Items</small>
                                <strong>
                                    <span id="modalItemCount"></span>
                                </strong>
                            </div>
                            <div class="col-md-6 mb-3">
                                <small class="text-muted d-block"> Order Total</small>
                                <strong class="text-success">
                                    ₦<span id="modalTotal"></span>
                                </strong>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Items -->
                <div class="card card-round mt-3">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            Ordered Products
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Quantity</th>
                                        <th>Price</th>
                                        <th>Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody id="modalOrderItems">
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">
                                            Loading order items...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-round" data-bs-dismiss="modal">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>


<script>
document.addEventListener("DOMContentLoaded", function () {
    const orderModal = document.getElementById("orderDetailsModal");

    orderModal.addEventListener("show.bs.modal", function (event) {
        const button = event.relatedTarget;
        const orderId = button.getAttribute("data-order-id");
        const customer = button.getAttribute("data-customer");
        const email = button.getAttribute("data-email");
        const itemsCount = button.getAttribute("data-items");
        const total = button.getAttribute("data-total");
        const status = button.getAttribute("data-status");
        const date = button.getAttribute("data-date");

        /*
        |--------------------------------------------------------------------------
        | Basic order information
        |--------------------------------------------------------------------------
        */
        document.getElementById("modalOrderId").textContent = orderId;
        document.getElementById("modalCustomer").textContent = customer;
        document.getElementById("modalEmail").textContent = email;
        document.getElementById("modalItemCount").textContent = itemsCount;
        document.getElementById("modalTotal").textContent = total;
        document.getElementById("modalOrderDate").textContent = date;

        /*
        |--------------------------------------------------------------------------
        | Status
        |--------------------------------------------------------------------------
        */
        const statusBadge =
            document.getElementById("modalOrderStatus");
        statusBadge.textContent = status;
        statusBadge.className = "badge";

        switch (status.toLowerCase()) {
            case "pending":
                statusBadge.classList.add("badge-warning");
                break;
            case "processing":
                statusBadge.classList.add("badge-info");
                break;
            case "shipped":
                statusBadge.classList.add("badge-primary");
                break;
            case "completed":
                statusBadge.classList.add("badge-success");
                break;
            case "cancelled":
                statusBadge.classList.add("badge-danger");
                break;
            default:
                statusBadge.classList.add("badge-secondary");

        }

        /*
        |--------------------------------------------------------------------------
        | Loading state
        |--------------------------------------------------------------------------
        */
        const itemsContainer =
            document.getElementById("modalOrderItems");
        itemsContainer.innerHTML = `
            <tr>
                <td colspan="4" class="text-center text-muted">
                    <i class="fas fa-spinner fa-spin me-2"></i>
                    Loading order items...
                </td>
            </tr>
        `;

        /*
        |--------------------------------------------------------------------------
        | Fetch order items
        |--------------------------------------------------------------------------
        */
        fetch("get-order-details.php?order_id=" + encodeURIComponent(orderId))
            .then(response => {
                if (!response.ok) {
                    throw new Error("Unable to fetch order details.");
                }
                return response.json();
            })

            .then(data => {
                if (!data.success) {
                    throw new Error(data.message || "Unable to load order.");
                }

                /*
                |--------------------------------------------------------------
                | Display order items
                |--------------------------------------------------------------
                */
                if (!data.items || data.items.length === 0) {
                    itemsContainer.innerHTML = `
                        <tr>
                            <td colspan="4"
                                class="text-center text-muted">
                                No items found for this order.
                            </td>
                        </tr>
                    `;
                    return;
                }

                itemsContainer.innerHTML = "";
                data.items.forEach(item => {
                    const row = document.createElement("tr");
                    row.innerHTML = `
                        <td>
                            <strong>
                                ${escapeHtml(item.product_name)}
                            </strong>
                        </td>
                        <td>
                            ${Number(item.quantity)}
                        </td>
                        <td>
                            ₦${formatMoney(item.price_at_purchase)}
                        </td>
                        <td>
                            ₦${formatMoney(item.subtotal)}
                        </td>
                    `;
                    itemsContainer.appendChild(row);

                });

            })
            .catch(error => {
                console.error(error);
                itemsContainer.innerHTML = `
                    <tr>
                        <td colspan="4"
                            class="text-center text-danger">
                            Unable to load order items.
                        </td>
                    </tr>
                `;
            });
    });

    /*
    |--------------------------------------------------------------------------
    | Format money
    |--------------------------------------------------------------------------
    */
    function formatMoney(value) {
        const number = Number(value);
        if (isNaN(number)) {
            return "0.00";
        }
        return number.toLocaleString("en-NG", {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Escape HTML
    |--------------------------------------------------------------------------
    */
    function escapeHtml(value) {
        const div = document.createElement("div");
        div.textContent = value ?? "";
        return div.innerHTML;
    }
});
</script>

<?php require_once "includes/admin-footer.php"; ?>