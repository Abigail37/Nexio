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
| Current logged-in user
|--------------------------------------------------------------------------
*/

$userId = $_SESSION['user_id'] ?? null;

if (!$userId) {
    exit("User session not found.");
}


/*
|--------------------------------------------------------------------------
| Fetch current user
|--------------------------------------------------------------------------
*/

$userStmt = $pdo->prepare("
    SELECT
        id,
        name,
        email,
        role
    FROM users
    WHERE id = ?
    LIMIT 1
");

$userStmt->execute([$userId]);

$currentUser = $userStmt->fetch(PDO::FETCH_ASSOC);

if (!$currentUser) {
    exit("User not found.");
}


/*
|--------------------------------------------------------------------------
| Fetch system settings
|--------------------------------------------------------------------------
*/

$settingsStmt = $pdo->query("
    SELECT
        setting_key,
        setting_value
    FROM settings
");

$settingsRows = $settingsStmt->fetchAll(PDO::FETCH_ASSOC);

$settings = [];

foreach ($settingsRows as $row) {
    $settings[$row['setting_key']] =
        $row['setting_value'];
}


/*
|--------------------------------------------------------------------------
| Default values
|--------------------------------------------------------------------------
*/

$storeName =
    $settings['store_name'] ?? 'Sales Management System';

$storeEmail =
    $settings['store_email'] ?? '';

$storePhone =
    $settings['store_phone'] ?? '';

$storeAddress =
    $settings['store_address'] ?? '';


$pageTitle = "Settings";

require_once "includes/admin-header.php";

?>

<div class="container">

    <div class="page-inner">

        <!-- Page Header -->

        <div class="page-header">

            <h3 class="fw-bold mb-3">
                Settings
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
                        Settings
                    </a>

                </li>

            </ul>

        </div>


        <!-- =====================================================
             SYSTEM INFORMATION
        ====================================================== -->

        <div class="card card-round">

            <div class="card-header">

                <div class="card-title">
                    System Information
                </div>

            </div>

            <div class="card-body">

                <form
                    method="POST"
                    action="update-settings.php">

                    <input
                        type="hidden"
                        name="action"
                        value="system">

                    <div class="row">

                        <div class="col-md-6 mb-3">

                            <label
                                for="store_name"
                                class="form-label">
                                Store / Company Name
                            </label>

                            <input
                                type="text"
                                name="store_name"
                                id="store_name"
                                class="form-control"
                                value="<?= htmlspecialchars($storeName) ?>"
                                required>

                        </div>


                        <div class="col-md-6 mb-3">

                            <label
                                for="store_email"
                                class="form-label">
                                Store Email
                            </label>

                            <input
                                type="email"
                                name="store_email"
                                id="store_email"
                                class="form-control"
                                value="<?= htmlspecialchars($storeEmail) ?>">

                        </div>


                        <div class="col-md-6 mb-3">

                            <label
                                for="store_phone"
                                class="form-label">
                                Phone Number
                            </label>

                            <input
                                type="text"
                                name="store_phone"
                                id="store_phone"
                                class="form-control"
                                value="<?= htmlspecialchars($storePhone) ?>">

                        </div>


                        <div class="col-md-6 mb-3">

                            <label
                                for="store_address"
                                class="form-label">
                                Address
                            </label>

                            <textarea
                                name="store_address"
                                id="store_address"
                                class="form-control"
                                rows="3"><?= htmlspecialchars($storeAddress) ?></textarea>

                        </div>

                    </div>


                    <button
                        type="submit"
                        class="btn btn-primary btn-round btn-icon" style="margin-top: -80px;">
                        <i class="fas fa-save"></i>
                    </button>

                </form>

            </div>

        </div>


        <!-- =====================================================
             ADMIN PROFILE
        ====================================================== -->

        <div class="card card-round">

            <div class="card-header">

                <div class="card-title">
                    My Profile
                </div>

            </div>

            <div class="card-body">

                <form
                    method="POST"
                    action="update-settings.php">

                    <input
                        type="hidden"
                        name="action"
                        value="profile">

                    <div class="row">

                        <div class="col-md-6 mb-3">

                            <label
                                for="name"
                                class="form-label">
                                Name
                            </label>

                            <input
                                type="text"
                                name="name"
                                id="name"
                                class="form-control"
                                value="<?= htmlspecialchars(
                                            $currentUser['name']
                                        ) ?>"
                                required>

                        </div>


                        <div class="col-md-6 mb-3">

                            <label
                                for="email"
                                class="form-label">
                                Email
                            </label>

                            <input
                                type="email"
                                name="email"
                                id="email"
                                class="form-control"
                                value="<?= htmlspecialchars(
                                            $currentUser['email']
                                        ) ?>"
                                required>

                        </div>


                        <div class="col-md-6 mb-3">

                            <label class="form-label">
                                Role
                            </label>

                            <input
                                type="text"
                                class="form-control"
                                value="<?= htmlspecialchars(
                                            ucfirst($currentUser['role'])
                                        ) ?>"
                                readonly>

                        </div>
                        <button
                            type="submit"
                            class="btn btn-primary btn-round btn-icon" style="margin-top: 30px;">
                            <i class="fas fa-save"></i>
                        </button>
                    </div>
                </form>

            </div>

        </div>


        <!-- =====================================================
             CHANGE PASSWORD
        ====================================================== -->

        <div class="card card-round">

            <div class="card-header">

                <div class="card-title">
                    Change Password
                </div>

            </div>

            <div class="card-body">

                <form
                    method="POST"
                    action="update-settings.php">

                    <input
                        type="hidden"
                        name="action"
                        value="password">

                    <div class="row">

                        <div class="col-md-4 mb-3">

                            <label
                                for="current_password"
                                class="form-label">
                                Current Password
                            </label>

                            <input
                                type="password"
                                name="current_password"
                                id="current_password"
                                class="form-control"
                                required>

                        </div>


                        <div class="col-md-4 mb-3">

                            <label
                                for="new_password"
                                class="form-label">
                                New Password
                            </label>

                            <input
                                type="password"
                                name="new_password"
                                id="new_password"
                                class="form-control"
                                minlength="8"
                                required>

                        </div>


                        <div class="col-md-4 mb-3">

                            <label
                                for="confirm_password"
                                class="form-label">
                                Confirm New Password
                            </label>

                            <input
                                type="password"
                                name="confirm_password"
                                id="confirm_password"
                                class="form-control"
                                minlength="8"
                                required>

                        </div>

                    </div>


                    <button
                        type="submit"
                        class="btn btn-warning btn-round">
                        <!-- <i class="fas fa-key me-1"></i> -->
                        Change Password
                    </button>

                </form>

            </div>

        </div>

    </div>

</div>


<?php require_once "includes/admin-footer.php"; ?>