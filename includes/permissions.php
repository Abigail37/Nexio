<?php

require_once __DIR__ . '/auth.php';

$permissions = [
    'superuser' => [
        'view_dashboard',
        'view_products',
        'add_products',
        'edit_products',
        'delete_products',
        'manage_inventory',
        'view_orders',
        'manage_orders',
        'view_sales',
        'view_payments',
        'manage_payments',
        'manage_suppliers',
        'manage_categories',
        'manage_staff',
        'view_reports',
        'manage_settings',
        'manage_superuser'
    ],

    'ceo' => [
        'view_dashboard',
        'view_products',
        'add_products',
        'edit_products',
        'delete_products',
        'manage_inventory',
        'view_orders',
        'manage_orders',
        'view_sales',
        'view_payments',
        'manage_payments',
        'manage_suppliers',
        'manage_categories',
        'manage_staff',
        'view_reports',
        'manage_settings'
    ],

    'manager' => [
        'view_dashboard',
        'view_products',
        'add_products',
        'edit_products',
        'delete_products',
        'manage_inventory',
        'view_orders',
        'manage_orders',
        'view_sales',
        'view_payments',
        'manage_payments',
        'manage_suppliers',
        'manage_categories',
        'manage_staff'
    ],

    'sales_rep' => [
        'view_dashboard',
        'view_products',
        'add_products',
        'edit_products',
        'delete_products',
        'view_orders',
        'manage_orders',
        'view_sales'
    ],

    'cashier' => [
        'view_dashboard',
        'view_sales',
        'view_payments',
        'manage_payments'
    ],

    'supplier' => [
        'view_dashboard',
        'view_products',
        'manage_suppliers'
    ],

    'delivery' => [
        'view_dashboard',
        'view_orders',
        'manage_orders'
    ],

    'accountant' => [
        'view_dashboard',
        'view_sales',
        'view_payments'
    ],

    'customer' => [
        'view_products',
        'view_orders'
    ]
];

/**
 * Check whether the logged-in user has a permission.
 */
function hasPermission($permission)
{
    global $permissions;
    $role = getUserRole();
    if (!$role || !isset($permissions[$role])) {
        return false;
    }
    return in_array($permission, $permissions[$role], true);
}

/**
 * Stop access if the user doesn't have a permission.
 */
function requirePermission($permission)
{
    requireLogin();
    if (!hasPermission($permission)) {
        http_response_code(403);
        die("Access denied.");
    }
}
