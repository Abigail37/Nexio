<?php

require_once "../includes/db.php";
require_once "../includes/permissions.php";
require_once "../includes/password-setup-mailer.php";

requirePermission('manage_staff');

$currentUserId = getUserId();
$currentRole = getUserRole();

$message = "";
$error = "";

/*
|--------------------------------------------------------------------------
| Determine which roles this user can create
|--------------------------------------------------------------------------
*/

$allowedRoles = [];
if ($currentRole === "superuser") {
    $allowedRoles = ["ceo", "manager", "sales_rep", "cashier", "supplier", "delivery", "accountant"];
} elseif ($currentRole === "ceo") {
    $allowedRoles = ["manager", "sales_rep", "cashier", "supplier", "delivery", "accountant"];
} elseif ($currentRole === "manager") {
    $allowedRoles = [
        "sales_rep",
        "cashier",
        "supplier",
        "delivery",
        "accountant"
    ];
}


/*
|--------------------------------------------------------------------------
| Create Staff Account
|--------------------------------------------------------------------------
*/

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    && isset($_POST["create_staff"])
) {

    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $role = $_POST["role"] ?? "";

    if ($name === "" || $email === "" || $role === "") {

        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid email address.";
    } elseif (!in_array($role, $allowedRoles, true)) {

        $error = "You are not allowed to create this role.";
    } else {

        $stmt = $pdo->prepare(
            "SELECT id
             FROM users
             WHERE email = ?
             LIMIT 1"
        );

        $stmt->execute([$email]);

        if ($stmt->fetch()) {

            $error = "An account with this email already exists.";
        } else {

            try {

                $pdo->beginTransaction();

                /*
                 * Random unusable password.
                 * Staff will create their real password
                 * through the setup link.
                 */
                $temporaryPassword = bin2hex(
                    random_bytes(32)
                );

                $hashedPassword = password_hash(
                    $temporaryPassword,
                    PASSWORD_DEFAULT
                );

                $stmt = $pdo->prepare(
                    "INSERT INTO users
                    (
                        name,
                        email,
                        password,
                        role,
                        email_verified,
                        is_active,
                        is_protected,
                        created_by
                    )
                    VALUES (?, ?, ?, ?, TRUE, TRUE, FALSE, ?)"
                );

                $stmt->execute([
                    $name,
                    $email,
                    $hashedPassword,
                    $role,
                    $currentUserId
                ]);

                $userId = $pdo->lastInsertId();

                /*
                 * Generate password setup token.
                 */
                $setupToken = bin2hex(
                    random_bytes(32)
                );

                $tokenHash = hash(
                    "sha256",
                    $setupToken
                );

                $expiresAt = date(
                    "Y-m-d H:i:s",
                    strtotime("+30 minutes")
                );

                $stmt = $pdo->prepare(
                    "INSERT INTO password_setup_tokens
                    (
                        user_id,
                        token_hash,
                        expires_at
                    )
                    VALUES (?, ?, ?)"
                );

                $stmt->execute([
                    $userId,
                    $tokenHash,
                    $expiresAt
                ]);

                $setupLink =
                    "http://localhost/SMS/public/set-password.php?token="
                    . urlencode($setupToken);

                $emailSent = sendPasswordSetupEmail(
                    $email,
                    $name,
                    $setupLink
                );

                if (!$emailSent) {

                    $pdo->rollBack();

                    $error =
                        "Account could not be created because "
                        . "the setup email could not be sent.";
                } else {

                    $pdo->commit();

                    $message =
                        "Staff account created successfully. "
                        . "A password setup email has been sent.";
                }
            } catch (Exception $e) {

                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                $error = "An unexpected error occurred.";
            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| Fetch Staff
|--------------------------------------------------------------------------
|
| Customers are excluded from this page.
|
*/

$stmt = $pdo->query(
    "SELECT
        u.id,
        u.name,
        u.email,
        u.role,
        u.is_active,
        u.is_protected,
        u.email_verified,
        u.created_at,
        creator.name AS created_by_name
     FROM users u
     LEFT JOIN users creator
        ON u.created_by = creator.id
     WHERE u.role != 'customer'
     ORDER BY u.created_at DESC"
);

$staff = $stmt->fetchAll();



require_once "includes/admin-header.php";
require_once "includes/admin-sidebar.php";


?>

<div class="container">

    <div class="page-inner">
        <div class="page-header">
            <h3 class="fw-bold mb-3">Staffs</h3>
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
                    <a href="#">Staff</a>
                </li>
            </ul>
        </div>
        <div class="card">
            <div class="card-body card-primary card-round">
                <p>
                    Logged in as:
                    <strong>
                        <?= htmlspecialchars($_SESSION["name"]) ?>
                    </strong>
                </p>
                <p>
                    Role:
                    <strong style="text-transform: uppercase;">
                        <?= htmlspecialchars($_SESSION["role"]) ?>
                    </strong>
                </p>
            </div>
        </div>


        <?php if ($message): ?>

            <p>
                <?= htmlspecialchars($message) ?>
            </p>

        <?php endif; ?>
        <?php if ($error): ?>
            <p> <?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <!-- create staff account -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">Create Staff Account</div>
            </div>
            <div class="card-body">
                <?php if (!empty($allowedRoles)): ?>
                    <form method="POST">
                        <div class="row">
                            <input
                                type="hidden"
                                name="create_staff"
                                value="1">
                            <!-- name  -->
                            <div class="col--md-6 col-lg-3">
                                <div>
                                    <label for="name">Full Name</label>
                                    <input
                                        type="text"
                                        id="name"
                                        name="name"
                                        placeholder="Enter Staff name"
                                        class="form-control"
                                        required>
                                </div>
                            </div>
                            <!-- email -->
                            <div class="col--md-6 col-lg-3">
                                <div>
                                    <label for="email">Email</label>
                                    <input
                                        type="email"
                                        id="email"
                                        name="email"
                                        placeholder="Enter Staff email"
                                        class="form-control"
                                        required>
                                </div>
                            </div>
                            <!-- role  -->
                            <div class="col--md-6 col-lg-3">
                                <div>
                                    <label for="role">Role</label>
                                    <select
                                        id="role"
                                        name="role"
                                        class="form-control"
                                        required>

                                        <option value="">Select role</option>

                                        <?php foreach ($allowedRoles as $role): ?>
                                            <span style="text-transform: uppercase;">
                                                <option value="<?= htmlspecialchars($role) ?>">
                                                    <?= htmlspecialchars(
                                                        ucwords(
                                                            str_replace("_", " ", $role)
                                                        )
                                                    ) ?>
                                                </option>
                                            </span>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <!-- create button  -->
                            <div class="col--md-6 col-lg-3" style="margin-top:20px;">
                                <button type="submit" class="btn btn-primary btn-round">
                                    Create Account
                                </button>
                            </div>
                        </div>

                    </form>

                <?php endif; ?>
            </div>
        </div>

        <!-- staff accounts -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">Staff Accounts</div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="basic-datatables" class="display table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Email</th>
                                <th>Created By</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if (empty($staff)): ?>
                                <tr>
                                    <td colspan="9">No staff accounts found.</td>
                                </tr>

                            <?php else: ?>
                                <?php foreach ($staff as $member): ?>
                                    <tr>
                                        <td>
                                            <?= htmlspecialchars($member["id"]) ?>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($member["name"]) ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($member["email"]) ?>
                                        </td>
                                        <td>
                                            <span style="text-transform: uppercase;">
                                                <?= htmlspecialchars(ucwords(str_replace("_", " ", $member["role"]))) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($member["is_active"]): ?>
                                                <span class="badge badge-success"> Active </span>
                                            <?php else: ?>
                                                <span class="badge badge-danger"> Inactive </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($member["email_verified"]): ?>
                                                Verified
                                            <?php else: ?>
                                                Unverified
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($member["created_by_name"] ?? "System") ?>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($member["created_at"]) ?>
                                        </td>
                                        <td>
                                            <?php if ($member["is_protected"]): ?>
                                                <!-- Protected Account -->
                                            <?php else: ?>
                                                <a href="edit-staff.php?id=<?= $member["id"] ?>"><i class="fas fa-edit btn btn-primary btn-round btn-icon mb-2"></i></a>
                                                <?php if ((int) $member['is_active'] === 1): ?>
                                                    <a href="toggle-staff.php?id=<?= $member["id"] ?>"
                                                        onclick="return confirm('Deactivate this staff?');" class="btn btn-danger btn-round">
                                                        Deactivate
                                                    </a>
                                                <?php else: ?>
                                                    <a href="toggle-staff.php?id=<?= $member["id"] ?>"
                                                        onclick="return confirm('Activate this staff?');" class="btn btn-success btn-round">
                                                        Activate
                                                    </a>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php require_once "includes/admin-footer.php"; ?>