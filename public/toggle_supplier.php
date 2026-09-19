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
| Get supplier ID
|--------------------------------------------------------------------------
*/

$supplierId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$supplierId || $supplierId <= 0) {
    http_response_code(400);
    die("Invalid supplier ID.");
}


/*
|--------------------------------------------------------------------------
| Get current supplier
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        id,
        name,
        is_active
    FROM suppliers
    WHERE id = :id
    LIMIT 1
");

$stmt->execute([
    ':id' => $supplierId
]);

$supplier = $stmt->fetch();


if (!$supplier) {
    http_response_code(404);
    die("Supplier not found.");
}


/*
|--------------------------------------------------------------------------
| Toggle status
|--------------------------------------------------------------------------
*/

$newStatus =
    ((int) $supplier['is_active'] === 1)
        ? 0
        : 1;


$update = $pdo->prepare("
    UPDATE suppliers

    SET
        is_active = :is_active,
        updated_at = CURRENT_TIMESTAMP

    WHERE id = :id
");

$update->execute([
    ':is_active' => $newStatus,
    ':id' => $supplierId
]);


/*
|--------------------------------------------------------------------------
| Redirect
|--------------------------------------------------------------------------
*/

header(
    "Location:../admin/suppliers.php"
);

exit;