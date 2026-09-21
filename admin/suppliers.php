<?php

require_once "../includes/db.php";
require_once "../includes/auth.php";

requireLogin();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$success = "";
$error = "";

/*
|--------------------------------------------------------------------------
| Edit mode
|--------------------------------------------------------------------------
*/

$editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);

$editSupplier = null;

if ($editId && $editId > 0) {

    $editStmt = $pdo->prepare("
        SELECT
            id,
            name,
            contact_email,
            contact_phone,
            address,
            is_active
        FROM suppliers
        WHERE id = :id
        LIMIT 1
    ");

    $editStmt->execute([
        ':id' => $editId
    ]);

    $editSupplier = $editStmt->fetch();

    if (!$editSupplier) {
        $error = "Supplier not found.";
        $editId = null;
    }
}


/*
|--------------------------------------------------------------------------
| Handle supplier creation / editing
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $contact_email = trim($_POST['contact_email'] ?? '');
    $contact_phone = trim($_POST['contact_phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $editId = filter_input(INPUT_POST, 'edit_id', FILTER_VALIDATE_INT);

    /*
    |--------------------------------------------------------------------------
    | Validate name
    |--------------------------------------------------------------------------
    */

    if ($name === '') {

        $error = "Supplier name is required.";
    } else {

        /*
        |--------------------------------------------------------------------------
        | Check for duplicate supplier
        |--------------------------------------------------------------------------
        */

        if ($editId) {

            $checkStmt = $pdo->prepare("
                SELECT id
                FROM suppliers
                WHERE name = :name
                AND id != :id
                LIMIT 1
            ");

            $checkStmt->execute([
                ':name' => $name,
                ':id' => $editId
            ]);
        } else {

            $checkStmt = $pdo->prepare("
                SELECT id
                FROM suppliers
                WHERE name = :name
                LIMIT 1
            ");

            $checkStmt->execute([
                ':name' => $name
            ]);
        }

        if ($checkStmt->fetch()) {

            $error = "A supplier with this name already exists.";
        } else {

            try {

                /*
                |--------------------------------------------------------------------------
                | Update existing supplier
                |--------------------------------------------------------------------------
                */

                if ($editId) {

                    $stmt = $pdo->prepare("
                        UPDATE suppliers
                        SET
                            name = :name,
                            contact_email = :contact_email,
                            contact_phone = :contact_phone,
                            address= :address,
                            is_active = :is_active
                        WHERE id = :id
                    ");

                    $stmt->execute([
                        ':name' => $name,
                        ':contact_email' =>
                        $contact_email !== ''
                            ? $contact_email
                            : null,
                        ':contact_phone' => $contact_phone,
                        ':address' => $address,
                        ':is_active' => $isActive,
                        ':id' => $editId
                    ]);

                    $success = "Supplier details updated successfully.";
                } else {

                    /*
                    |--------------------------------------------------------------------------
                    | Create new supplier
                    |--------------------------------------------------------------------------
                    */

                    $stmt = $pdo->prepare("
                        INSERT INTO suppliers (
                            name,
                            contact_email,
                            contact_phone,
                            address,
                            is_active
                        )
                        VALUES (
                            :name,
                            :contact_email,
                            :contact_phone,
                            :address,
                            :is_active
                        )
                    ");

                    $stmt->execute([
                        ':name' => $name,
                        ':contact_email' => $contact_email !== '' ? $contact_email : null,
                        ':contact_phone' => $contact_phone,
                        ':address' => $address,
                        ':is_active' => $isActive
                    ]);

                    $success = "Supplier created successfully.";
                }

                /*
                |--------------------------------------------------------------------------
                | Clear form after successful operation
                |--------------------------------------------------------------------------
                */

                $editId = null;
                $editSupplier = null;
                $_POST = [];
            } catch (PDOException $e) {

                $error = $editId
                    ? "Unable to update supplier details."
                    : "Unable to create supplier details.";
            }
        }
    }
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

require_once "includes/admin-header.php";

?>

<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <h3 class="fw-bold mb-3">Suppliers</h3>
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
                    <a href="#">Supplier</a>
                </li>
            </ul>
        </div>

        <!-- create supplier  -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <?= $editSupplier ? 'Edit Supplier' : 'Add Supplier' ?>
                </div>
            </div>
            <div class="card-body">
                <?php if ($success !== ''): ?>
                    <div class="success-message">
                        <?= htmlspecialchars($success) ?>
                    </div>
                <?php endif; ?>
                <?php if ($error !== ''): ?>
                    <div class="error-message">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
                <div class="form-card">
                    <form method="POST" action="suppliers.php">
                        <?php if ($editSupplier): ?>
                            <input
                                type="hidden"
                                name="edit_id"
                                value="<?= (int) $editSupplier['id'] ?>">
                        <?php endif; ?>
                        <div class="row">
                            <!-- SUPPLIER NAME -->
                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label for="name">Supplier Name </label>
                                    <input
                                        type="text"
                                        id="name"
                                        name="name"
                                        class="form-control"
                                        value="<?= htmlspecialchars($editSupplier['name'] ?? $_POST['name'] ?? '') ?>"
                                        placeholder="Enter supplier name"
                                        required>

                                </div>
                            </div>
                            <!-- EMAIL -->
                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label for="contact_email">Email
                                        <span>(Optional)</span>
                                    </label>
                                    <input
                                        type="email"
                                        id="contact_email"
                                        name="contact_email"
                                        class="form-control"
                                        value="<?= htmlspecialchars($editSupplier['contact_email'] ?? $_POST['contact_email'] ?? '') ?>"
                                        placeholder="supplier@example.com">

                                </div>
                            </div>
                            <!-- PHONE -->
                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label for="contact_phone">Phone</label>
                                    <input
                                        type="text"
                                        id="contact_phone"
                                        name="contact_phone"
                                        class="form-control"
                                        value="<?= htmlspecialchars($editSupplier['contact_phone'] ?? $_POST['contact_phone'] ?? '') ?>"
                                        placeholder="Supplier phone number"
                                        required>

                                </div>
                            </div>
                            <!-- ADDRESS -->
                            <div class="col-md-6 col-lg-10">
                                <div class="form-group">
                                    <label for="address">Address</label>
                                    <textarea
                                        id="address"
                                        name="address"
                                        rows="4"
                                        class="form-control"
                                        required
                                        placeholder="Supplier address"><?= htmlspecialchars($editSupplier['address'] ?? $_POST['address'] ?? '') ?>
                                </textarea>

                                </div>
                            </div>
                            <!-- SUBMIT -->
                            <div class="col-md-6 col-lg-2">
                                <button type="submit" style="margin-top: 100px;" class="btn btn-primary btn-round">
                                    <?= $editSupplier ? 'Update Supplier' : 'Add Supplier' ?>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- suppliers -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    Suppliers
                </div>
                <div style="margin-left: 1090px; margin-top: -25px;">
                    <span>
                        <?= number_format($resultCount) ?>
                        supplier<?= $resultCount === 1 ? '' : 's' ?>
                    </span>
                </div>
            </div>
            <div class="card-body">
                <!-- SEARCH -->
                <!-- <div class="supplier-filters">
                    <form method="GET" action="suppliers.php">
                        <div class="row">
                            <div class="col-md-6 col-lg-10">
                                <div class="filter-group search-group">
                                    <label for="search"> Search Suppliers</label>
                                    <input
                                        type="search"
                                        id="search"
                                        name="search"
                                        class="form-control"
                                        value="<?= htmlspecialchars($search) ?>"
                                        placeholder="Name, email, phone or address...">
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-2">
                                <div class="filter-actions">
                                    <button type="submit" class="btn btn-primary btn-round">
                                        Search
                                    </button>
                                    <a href="suppliers.php" class="clear-filter btn btn-danger btn-round">Clear</a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div> -->

                <!-- SUPPLIER TABLE  -->
                <div class="inventory-table-card">
                    <?php if (empty($suppliers)): ?>
                        <div class="empty-state">
                            <p> No suppliers found. </p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table id="basic-datatables" class="display table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Supplier</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Address</th>
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
                                                <?= htmlspecialchars($supplier['id']) ?>
                                            </td>
                                            <td>
                                                <strong>
                                                    <?= htmlspecialchars($supplier['name']) ?>
                                                </strong>
                                            </td>

                                            <td>
                                                <?= $supplier['contact_email'] ? htmlspecialchars($supplier['contact_email']) : '—' ?>
                                            </td>
                                            <td>
                                                <?= $supplier['contact_phone'] ? htmlspecialchars($supplier['contact_phone']) : '—' ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($supplier['address'])): ?>
                                                    <small style="display:block;">
                                                        <?= htmlspecialchars($supplier['address']) ?>
                                                    </small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?= number_format((int) $supplier['product_count']) ?>
                                            </td>
                                            <td>
                                                <?php if ((int) $supplier['is_active'] === 1): ?>
                                                    <span class="stock-status in-stock badge badge-success">Active</span>
                                                <?php else: ?>
                                                    <span class="stock-status out-of-stock badge badge-danger">
                                                        Inactive
                                                    </span>
                                                <?php endif; ?>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars(
                                                    date('M j, Y', strtotime($supplier['created_at']))
                                                ) ?>
                                            </td>

                                            <td>
                                                <a href="suppliers.php?edit=<?= (int) $supplier['id'] ?>"><i class="fas fa-edit btn btn-primary btn-round btn-icon mb-2"></i></a>
                                                <?php if ((int) $supplier['is_active'] === 1): ?>
                                                    <a href="../public/toggle_supplier.php?id=<?= (int) $supplier['id'] ?>"
                                                        onclick="return confirm('Deactivate this supplier?');" class="btn btn-danger btn-round">
                                                        Deactivate
                                                    </a>
                                                <?php else: ?>
                                                    <a href="../public/toggle_supplier.php?id=<?= (int) $supplier['id'] ?>"
                                                        onclick="return confirm('Activate this supplier?');" class="btn btn-success btn-round">
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
        </div>
        <?php require_once "includes/admin-footer.php"; ?>