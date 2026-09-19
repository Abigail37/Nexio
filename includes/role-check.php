<?php

require_once __DIR__ . '/auth.php';

/**
 * Require one of the specified roles.
 */
function requireRole($allowedRoles)
{
    requireLogin();
    if (!in_array(getUserRole(), $allowedRoles, true)) {
        http_response_code(403);
        die("Access denied.");
    }
}

/**
 * Require CEO.
 */
function requireCEO()
{
    requireRole(['ceo']);
}

/**
 * Require CEO or Manager.
 */
function requireManagement()
{
    requireRole(['ceo', 'manager']);
}

/**
 * Require staff.
 */
function requireStaff()
{
    requireRole([
        'ceo',
        'manager',
        'sales_rep',
        'cashier',
        'supplier',
        'delivery',
        'accountant'
    ]);
}