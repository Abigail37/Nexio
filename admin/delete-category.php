<?php

require_once "../includes/db.php";
require_once "../includes/auth.php";

requireLogin();

/*
|--------------------------------------------------------------------------
| Admin category access
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
| Get category ID
|--------------------------------------------------------------------------
*/
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    header("Location: categories.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Check that category exists
|--------------------------------------------------------------------------
*/
$checkStmt = $pdo->prepare("
    SELECT id
    FROM categories
    WHERE id = ?
    LIMIT 1
");

$checkStmt->execute([$id]);

if (!$checkStmt->fetch()) {
    header("Location: categories.php?error=not_found");
    exit;
}

/*
|--------------------------------------------------------------------------
| Delete category
|--------------------------------------------------------------------------
*/
try {
    $deleteStmt = $pdo->prepare("
        DELETE FROM categories
        WHERE id = ?
    ");
    $deleteStmt->execute([$id]);
    header("Location: categories.php?deleted=1");
    exit;
} catch (PDOException $e) {
    header("Location: categories.php?error=delete_failed");
    exit;
}

