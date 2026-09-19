<?php

require_once "../includes/db.php";
require_once "../includes/auth.php";

requireLogin();

/*
|--------------------------------------------------------------------------
| Admin product access
|--------------------------------------------------------------------------
*/
$allowedRoles = [
    'superuser',
    'ceo',
    'manager'
];

if (!in_array(getUserRole(), $allowedRoles, true)) {
    http_response_code(403);
    exit("Access denied.");
}

/*
|--------------------------------------------------------------------------
| Get product ID
|--------------------------------------------------------------------------
*/
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    header("Location: transactions.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Check that product exists
|--------------------------------------------------------------------------
*/
$checkStmt = $pdo->prepare("
    SELECT id
    FROM transactions
    WHERE id = ?
    LIMIT 1
");

$checkStmt->execute([$id]);

if (!$checkStmt->fetch()) {
    header("Location: transactions.php?error=not_found");
    exit;
}

/*
|--------------------------------------------------------------------------
| Delete product
|--------------------------------------------------------------------------
*/
try {
    $deleteStmt = $pdo->prepare("
        DELETE FROM transactions
        WHERE id = ?
    ");
    $deleteStmt->execute([$id]);
    header("Location: transactions.php?deleted=1");
    exit;
} catch (PDOException $e) {
    header("Location: transactions.php?error=delete_failed");
    exit;
}

