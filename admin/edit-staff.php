<?php

require_once "../includes/db.php";
require_once "../includes/permissions.php";

requirePermission('manage_staff');

$staffId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$staffId) {
    die("Invalid staff ID.");
}

// Get staff member
$stmt = $pdo->prepare(
    "SELECT *
     FROM users
     WHERE id = ?
     AND role != 'customer'
     LIMIT 1"
);

$stmt->execute([$staffId]);
$staff = $stmt->fetch();

if (!$staff) {
    die("Staff member not found.");
}

// Protected accounts cannot be edited from here
if ($staff['is_protected']) {
    die("This account is protected and cannot be modified.");
}

$currentRole = getUserRole();

$message = "";
$error = "";

// Determine roles this user is allowed to manage
$manageableRoles = [];

if ($currentRole === "superuser") {
    $manageableRoles = ["ceo", "manager", "sales_rep", "cashier", "supplier", "delivery", "accountant"];
} elseif ($currentRole === "ceo") {
    $manageableRoles = ["manager", "sales_rep", "cashier", "supplier", "delivery", "accountant"];
} elseif ($currentRole === "manager") {
    $manageableRoles = [
        "sales_rep",
        "cashier",
        "supplier",
        "delivery",
        "accountant"
    ];
}

// Only allow editing staff within the hierarchy
if (!in_array($staff['role'], $manageableRoles, true)) {
    die("You are not authorized to edit this staff member.");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    if ($name === "" || $email === "") {
        $error = "Name and email are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        // Make sure email isn't being used by another account
        $stmt = $pdo->prepare(
            "SELECT id
             FROM users
             WHERE email = ?
             AND id != ?
             LIMIT 1"
        );

        $stmt->execute([
            $email,
            $staffId
        ]);

        if ($stmt->fetch()) {
            $error = "That email address is already in use.";
        } else {
            $stmt = $pdo->prepare(
                "UPDATE users
                 SET name = ?,
                     email = ?
                 WHERE id = ?
                 AND is_protected = FALSE"
            );
            $stmt->execute([
                $name,
                $email,
                $staffId
            ]);
            $message = "Staff information updated successfully.";
            // Refresh displayed data
            $staff['name'] = $name;
            $staff['email'] = $email;
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">
    <title>Edit Staff</title>
</head>

<body>
    <h1>Edit Staff</h1>
    <?php if ($message): ?>
        <p><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>
    <?php if ($error): ?>
        <p><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    <form method="POST">
        <div>
            <label for="name"> Full Name</label>
            <input
                type="text"
                id="name"
                name="name"
                value="<?= htmlspecialchars($staff['name']) ?>"
                required>
        </div>
        <div>
            <label for="email">Email </label>
            <input
                type="email"
                id="email"
                name="email"
                value="<?= htmlspecialchars($staff['email']) ?>"
                required>
        </div>
        <p>
            Role:
            <strong>
                <?= htmlspecialchars(
                    ucwords(
                        str_replace("_", " ", $staff['role'])
                    )
                ) ?>
            </strong>
        </p>
        <button type="submit">
            Save Changes
        </button>
    </form>
    <a href="staff.php"> Back to Staff Management </a>
</body>
</html>