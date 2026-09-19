<?php

require_once "../includes/db.php";
require_once "../includes/permissions.php";

requirePermission('manage_staff');

$staffId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$staffId) {
    die("Invalid staff ID.");
}

// Get account
$stmt = $pdo->prepare(
    "SELECT id, name, role, is_active, is_protected
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

// Protected account cannot be deactivated
if ($staff['is_protected']) {
    die("This account is protected and cannot be deactivated.");
}

// Never allow someone to deactivate themselves
if ((int)$staff['id'] === (int)getUserId()) {
    die("You cannot deactivate your own account.");
}

$currentRole = getUserRole();

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

if (!in_array($staff['role'], $manageableRoles, true)) {
    die("You are not authorized to modify this account.");
}

// Toggle account status
$newStatus = $staff['is_active'] ? 0 : 1;

$stmt = $pdo->prepare(
    "UPDATE users
     SET is_active = ?
     WHERE id = ?
     AND is_protected = FALSE"
);

$stmt->execute([
    $newStatus,
    $staffId
]);

header("Location: staff.php");
exit;