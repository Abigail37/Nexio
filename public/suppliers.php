<?php

require_once "../includes/db.php";
require_once "../includes/auth.php";

requireLogin();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| Supplier management access
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
    die("You do not have permission to manage suppliers.");
}


/*
|--------------------------------------------------------------------------
| Search
|--------------------------------------------------------------------------
*/

$search = trim($_GET['search'] ?? '');


$where = [];
$params = [];


if ($search !== '') {

    $where[] = "
        (
            s.name LIKE :search
            OR s.contact_email LIKE :search
            OR s.contact_phone LIKE :search
        )
    ";

    $params[':search'] =
        '%' . $search . '%';
}


$whereSql = '';

if (!empty($where)) {

    $whereSql =
        'WHERE ' . implode(' AND ', $where);
}


/*
|--------------------------------------------------------------------------
| Fetch suppliers
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        s.id,
        s.name,
        s.contact_email,
        s.contact_phone,
        s.address,
        s.is_active,
        s.created_at,

        COUNT(p.id) AS product_count

    FROM suppliers s

    LEFT JOIN products p
        ON p.supplier_id = s.id

    $whereSql

    GROUP BY
        s.id,
        s.name,
        s.contact_email,
        s.contact_phone,
        s.address,
        s.is_active,
        s.created_at

    ORDER BY
        s.created_at DESC,
        s.name ASC
";


$stmt = $pdo->prepare($sql);

$stmt->execute($params);

$suppliers = $stmt->fetchAll();


$resultCount = count($suppliers);


require_once "assets/includes/header.php";

?>

<section class="suppliers-page">

    <div class="container">
        <div class="page-heading">

            <div>

                <h1>Suppliers</h1>

                <p>
                    Manage your product suppliers.
                </p>

            </div>

            <a
                href="add_supplier.php"
                class="btn">
                + Add Supplier
            </a>

        </div>
        <!-- SEARCH -->
        <div class="supplier-filters">
            <form method="GET" action="suppliers.php">
                <div class="filter-row">
                    <div class="filter-group search-group">
                        <label for="search"> Search Suppliers</label>
                        <input
                            type="search"
                            id="search"
                            name="search"
                            value="<?= htmlspecialchars($search ) ?>"
                            placeholder="Name, email, or phone...">
                    </div>
                    <div class="filter-actions">
                        <button type="submit" class="btn">
                            Search
                        </button>
                        <a href="suppliers.php" class="clear-filter">Clear</a>
                    </div>
                </div>
            </form>
        </div>
        <!-- RESULTS -->
        <div class="supplier-results-header">
            <h2>Supplier List
            </h2>
            <span>
                <?= number_format($resultCount) ?>
                supplier<?= $resultCount === 1 ? '' : 's' ?>
            </span>
        </div>

        <div class="inventory-table-card">
            <?php if (empty($suppliers)): ?>
                <div class="empty-state">
                    <p>  No suppliers found. </p>
                </div>
            <?php else: ?>
                <div class="table-wrapper">
                    <table class="inventory-table">
                        <thead>
                            <tr>
                                <th>Supplier</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Products</th>
                                <th>Status</th>
                                <th>Date Added</th>
                                <th>Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($suppliers as $supplier): ?>
                                <tr>
                                    <td>
                                        <strong>
                                            <?= htmlspecialchars( $supplier['name']) ?>
                                        </strong>

                                        <?php if ( !empty($supplier['address'])): ?>
                                            <small style="display:block;">
                                                <?= htmlspecialchars($supplier['address']) ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= $supplier['contact_email']? htmlspecialchars($supplier['contact_email']): '—'?>
                                    </td>
                                    <td>
                                        <?= $supplier['contact_phone'] ? htmlspecialchars($supplier['contact_phone']): '—'?>
                                    </td>
                                    <td>
                                        <?= number_format((int) $supplier['product_count']) ?>
                                    </td>
                                    <td>
                                        <?php if ((int) $supplier['is_active'] === 1): ?>
                                            <span class="stock-status in-stock">Active</span>
                                        <?php else: ?>
                                            <span class="stock-status out-of-stock">
                                                Inactive
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            date('M j, Y',strtotime($supplier['created_at']))
                                        ) ?>
                                    </td>

                                    <td>
                                        <a href="edit_supplier.php?id=<?= (int) $supplier['id'] ?>">Edit</a>

                                        |

                                        <?php if ( (int) $supplier['is_active'] === 1): ?>
                                            <a href="toggle_supplier.php?id=<?= (int) $supplier['id'] ?>"
                                                onclick="return confirm('Deactivate this supplier?');">
                                                Deactivate
                                            </a>
                                        <?php else: ?>
                                            <a href="toggle_supplier.php?id=<?= (int) $supplier['id'] ?>"
                                                onclick="return confirm('Activate this supplier?');">
                                                Activate
                                            </a>
                                        <?php endif; ?>
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