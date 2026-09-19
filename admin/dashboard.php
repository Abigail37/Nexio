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
| Dashboard Statistics
|--------------------------------------------------------------------------
*/


/* Total Sales */

$stmt = $pdo->query("
    SELECT COALESCE(SUM(total_amount), 0)
    FROM orders
    WHERE status != 'cancelled'
");

$totalSales = (float) $stmt->fetchColumn();


/* Total Orders */

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM orders
");

$totalOrders = (int) $stmt->fetchColumn();


/* Total Customers */

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM users
    WHERE role = 'customer'
");

$totalCustomers = (int) $stmt->fetchColumn();


/* Total Products */

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM products
    WHERE is_active = 1
");

$totalProducts = (int) $stmt->fetchColumn();


/* Pending Orders */

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM orders
    WHERE status = 'pending'
");

$pendingOrders = (int) $stmt->fetchColumn();


/* Low Stock */

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM products
    WHERE is_active = 1
    AND stock_quantity <= low_stock_threshold
");

$lowStockProducts = (int) $stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| Recent Orders
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        id,
        shipping_name,
        total_amount,
        status,
        created_at

    FROM orders

    ORDER BY created_at DESC

    LIMIT 5
");

$recentOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Recent Transactions
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        t.id,
        t.transaction_reference,
        t.order_id,
        t.amount,
        t.payment_method,
        t.status,
        t.transaction_date,

        o.shipping_name AS customer_name

    FROM transactions t

    INNER JOIN orders o
        ON o.id = t.order_id

    ORDER BY t.transaction_date DESC

    LIMIT 5
");

$recentTransactions =
  $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Best Selling Products
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        p.name,
        SUM(oi.quantity) AS quantity_sold,
        SUM(oi.subtotal) AS revenue

    FROM order_items oi

    INNER JOIN products p
        ON p.id = oi.product_id

    INNER JOIN orders o
        ON o.id = oi.order_id

    WHERE o.status != 'cancelled'

    GROUP BY
        p.id,
        p.name

    ORDER BY quantity_sold DESC

    LIMIT 5
");

$bestSellingProducts =
  $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Page
|--------------------------------------------------------------------------
*/

$pageTitle = "Dashboard";

require_once "includes/admin-header.php";
require_once "includes/admin-sidebar.php";

?>

<div class="container">

  <div class="page-inner">


    <!-- =====================================================
             PAGE HEADER
        ====================================================== -->

    <div class="page-header">

      <h3 class="fw-bold mb-3">
        Dashboard
      </h3>
    </div>


    <!-- =====================================================
             STATISTICS
        ====================================================== -->

    <div class="row">


      <!-- Total Sales -->

      <div class="col-sm-6 col-md-3">

        <div class="card card-stats card-round">

          <div class="card-body">

            <div class="row align-items-center">

              <div class="col-icon">

                <div class="icon-big text-center icon-primary bubble-shadow-small">

                  <i class="fas fa-money-bill-wave"></i>

                </div>

              </div>

              <div class="col col-stats ms-3 ms-sm-0">

                <div class="numbers">

                  <p class="card-category">
                    Total Sales
                  </p>

                  <h4 class="card-title">
                    ₦<?= number_format(
                        $totalSales,
                        2
                      ) ?>
                  </h4>

                </div>

              </div>

            </div>

          </div>

        </div>

      </div>


      <!-- Total Orders -->

      <div class="col-sm-6 col-md-3">

        <div class="card card-stats card-round">

          <div class="card-body">

            <div class="row align-items-center">

              <div class="col-icon">

                <div class="icon-big text-center icon-info bubble-shadow-small">

                  <i class="fas fa-shopping-cart"></i>

                </div>

              </div>

              <div class="col col-stats ms-3 ms-sm-0">

                <div class="numbers">

                  <p class="card-category">
                    Total Orders
                  </p>

                  <h4 class="card-title">
                    <?= number_format(
                      $totalOrders
                    ) ?>
                  </h4>

                </div>

              </div>

            </div>

          </div>

        </div>

      </div>


      <!-- Customers -->

      <div class="col-sm-6 col-md-3">

        <div class="card card-stats card-round">

          <div class="card-body">

            <div class="row align-items-center">

              <div class="col-icon">

                <div class="icon-big text-center icon-success bubble-shadow-small">

                  <i class="fas fa-users"></i>

                </div>

              </div>

              <div class="col col-stats ms-3 ms-sm-0">

                <div class="numbers">

                  <p class="card-category">
                    Customers
                  </p>

                  <h4 class="card-title">
                    <?= number_format(
                      $totalCustomers
                    ) ?>
                  </h4>

                </div>

              </div>

            </div>

          </div>

        </div>

      </div>


      <!-- Products -->

      <div class="col-sm-6 col-md-3">

        <div class="card card-stats card-round">

          <div class="card-body">

            <div class="row align-items-center">

              <div class="col-icon">

                <div class="icon-big text-center icon-secondary bubble-shadow-small">

                  <i class="fas fa-box"></i>

                </div>

              </div>

              <div class="col col-stats ms-3 ms-sm-0">

                <div class="numbers">

                  <p class="card-category">
                    Products
                  </p>

                  <h4 class="card-title">
                    <?= number_format(
                      $totalProducts
                    ) ?>
                  </h4>

                </div>

              </div>

            </div>

          </div>

        </div>

      </div>

    </div>


    <!-- =====================================================
             ALERTS
        ====================================================== -->

    <div class="row">


      <div class="col-md-6">

        <div class="card card-round">

          <div class="card-body">

            <div class="d-flex align-items-center">

              <div>

                <h5 class="fw-bold mb-1">
                  Pending Orders
                </h5>

                <p class="text-muted mb-0">

                  <?= number_format(
                    $pendingOrders
                  ) ?>

                  order(s) waiting for processing.

                </p>

              </div>

              <div class="ms-auto">

                <a
                  href="orders.php"
                  class="btn btn-primary btn-round">
                  View Orders
                </a>

              </div>

            </div>

          </div>

        </div>

      </div>


      <div class="col-md-6">

        <div class="card card-round">

          <div class="card-body">

            <div class="d-flex align-items-center">

              <div>

                <h5 class="fw-bold mb-1">
                  Low Stock
                </h5>

                <p class="text-muted mb-0">

                  <?= number_format(
                    $lowStockProducts
                  ) ?>

                  product(s) are low on stock.

                </p>

              </div>

              <div class="ms-auto">

                <a
                  href="products.php"
                  class="btn btn-warning btn-round">
                  View Products
                </a>

              </div>

            </div>

          </div>

        </div>

      </div>

    </div>


    <!-- =====================================================
             RECENT ORDERS
        ====================================================== -->

    <div class="card card-round">

      <div class="card-header">
        <div class="d-flex align-items-center justify-content-between">
          <div class="card-title">
            Recent Orders
          </div>
          <a
            href="orders.php"
            class="btn btn-primary btn-round">
            View All
          </a>
        </div>
      </div>


      <div class="card-body">
        <div class="table-responsive">
          <table id="basic-datatables" class="display table table-striped table-hover">
            <thead>

              <tr>

                <th>
                  Order
                </th>

                <th>
                  Customer
                </th>

                <th>
                  Total
                </th>

                <th>
                  Status
                </th>

                <th>
                  Date
                </th>

              </tr>

            </thead>


            <tbody>

              <?php if (empty($recentOrders)): ?>

                <tr>

                  <td
                    colspan="5"
                    class="text-center text-muted">
                    No orders yet.
                  </td>

                </tr>

              <?php else: ?>

                <?php foreach ($recentOrders as $order): ?>

                  <tr>

                    <td>
                      <?= (int) $order['id'] ?>
                    </td>

                    <td>

                      <?= htmlspecialchars(
                        $order['shipping_name']
                      ) ?>

                    </td>

                    <td>

                      ₦<?= number_format(
                          (float) $order['total_amount'],
                          2
                        ) ?>

                    </td>

                    <td>

                      <?php

                      $statusClass = match ($order['status']) {

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
                            $order['status']
                          )
                        ) ?>

                      </span>

                    </td>

                    <td>

                      <?= htmlspecialchars(
                        date(
                          'M j, Y',
                          strtotime(
                            $order['created_at']
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
             RECENT TRANSACTIONS
        ====================================================== -->

    <div class="card card-round">

      <div class="card-header">

        <div class="d-flex align-items-center justify-content-between">


          <div class="card-title">
            Recent Transactions
          </div>

          <a
            href="transactions.php"
            class="btn btn-primary btn-round">
            View All
          </a>

        </div>

      </div>


      <div class="card-body">

        <div class="table-responsive">

          <table class="table table-hover">

            <thead>

              <tr>

                <th>
                  Reference
                </th>

                <th>
                  Customer
                </th>

                <th>
                  Amount
                </th>

                <th>
                  Method
                </th>

                <th>
                  Status
                </th>

              </tr>

            </thead>


            <tbody>

              <?php if (empty($recentTransactions)): ?>

                <tr>

                  <td
                    colspan="5"
                    class="text-center text-muted">
                    No transactions yet.
                  </td>

                </tr>

              <?php else: ?>

                <?php foreach (
                  $recentTransactions
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

                      <?php

                      $transactionClass = match ($transaction['status']) {

                        'successful'
                        => 'badge-success',

                        'pending'
                        => 'badge-warning',

                        'failed'
                        => 'badge-danger',

                        'refunded'
                        => 'badge-info',

                        default
                        => 'badge-secondary'
                      };

                      ?>

                      <span
                        class="badge <?= $transactionClass ?>">

                        <?= htmlspecialchars(
                          ucfirst(
                            $transaction['status']
                          )
                        ) ?>

                      </span>

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
             BEST SELLING PRODUCTS
        ====================================================== -->

    <div class="card card-round">

      <div class="card-header">

        <div class="card-title">
          Best Selling Products
        </div>

      </div>


      <div class="card-body">

        <div class="table-responsive">

          <table class="table table-hover">

            <thead>

              <tr>

                <th>
                  #
                </th>

                <th>
                  Product
                </th>

                <th>
                  Quantity Sold
                </th>

                <th>
                  Revenue
                </th>

              </tr>

            </thead>


            <tbody>

              <?php if (empty($bestSellingProducts)): ?>

                <tr>

                  <td
                    colspan="4"
                    class="text-center text-muted">
                    No sales data available.
                  </td>

                </tr>

              <?php else: ?>

                <?php
                $rank = 1;
                ?>

                <?php foreach (
                  $bestSellingProducts
                  as $product
                ): ?>

                  <tr>

                    <td>
                      <?= $rank++ ?>
                    </td>

                    <td>

                      <strong>
                        <?= htmlspecialchars(
                          $product['name']
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
                          (float) $product['revenue'],
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