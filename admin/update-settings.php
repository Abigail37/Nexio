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


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: settings.php");
    exit;
}


$userId = $_SESSION['user_id'] ?? null;

if (!$userId) {
    exit("User session not found.");
}


$action = $_POST['action'] ?? '';


/*
|--------------------------------------------------------------------------
| SYSTEM SETTINGS
|--------------------------------------------------------------------------
*/

if ($action === 'system') {

    $storeName =
        trim($_POST['store_name'] ?? '');

    $storeEmail =
        trim($_POST['store_email'] ?? '');

    $storePhone =
        trim($_POST['store_phone'] ?? '');

    $storeAddress =
        trim($_POST['store_address'] ?? '');


    if ($storeName === '') {

        header(
            "Location: settings.php?error=store_name"
        );

        exit;
    }


    if (
        $storeEmail !== '' &&
        !filter_var($storeEmail, FILTER_VALIDATE_EMAIL)
    ) {

        header(
            "Location: settings.php?error=email"
        );

        exit;
    }


    $settings = [
        'store_name' =>
            $storeName,

        'store_email' =>
            $storeEmail,

        'store_phone' =>
            $storePhone,

        'store_address' =>
            $storeAddress
    ];


    $stmt = $pdo->prepare("
        INSERT INTO settings (
            setting_key,
            setting_value
        )

        VALUES (?, ?)

        ON DUPLICATE KEY UPDATE
            setting_value = VALUES(setting_value)
    ");


    foreach ($settings as $key => $value) {

        $stmt->execute([
            $key,
            $value
        ]);

    }


    header(
        "Location: settings.php?success=system"
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| PROFILE
|--------------------------------------------------------------------------
*/

if ($action === 'profile') {

    $name =
        trim($_POST['name'] ?? '');

    $email =
        trim($_POST['email'] ?? '');


    if (
        $name === '' ||
        !filter_var($email, FILTER_VALIDATE_EMAIL)
    ) {

        header(
            "Location: settings.php?error=profile"
        );

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | Check whether email is already being used
    |--------------------------------------------------------------------------
    */

    $emailCheck = $pdo->prepare("
        SELECT id
        FROM users
        WHERE email = ?
        AND id != ?
        LIMIT 1
    ");

    $emailCheck->execute([
        $email,
        $userId
    ]);


    if ($emailCheck->fetch()) {

        header(
            "Location: settings.php?error=email_exists"
        );

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | Update profile
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        UPDATE users

        SET
            name = ?,
            email = ?

        WHERE id = ?
    ");


    $stmt->execute([
        $name,
        $email,
        $userId
    ]);


    /*
    |--------------------------------------------------------------------------
    | Update session name if your header uses it
    |--------------------------------------------------------------------------
    */

    $_SESSION['user_name'] =
        $name;


    header(
        "Location: settings.php?success=profile"
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| PASSWORD
|--------------------------------------------------------------------------
*/

if ($action === 'password') {

    $currentPassword =
        $_POST['current_password'] ?? '';

    $newPassword =
        $_POST['new_password'] ?? '';

    $confirmPassword =
        $_POST['confirm_password'] ?? '';


    if (
        $currentPassword === '' ||
        $newPassword === '' ||
        $confirmPassword === ''
    ) {

        header(
            "Location: settings.php?error=password_empty"
        );

        exit;
    }


    if (strlen($newPassword) < 8) {

        header(
            "Location: settings.php?error=password_short"
        );

        exit;
    }


    if ($newPassword !== $confirmPassword) {

        header(
            "Location: settings.php?error=password_match"
        );

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | Get current password
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        SELECT password
        FROM users
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->execute([
        $userId
    ]);

    $user = $stmt->fetch(
        PDO::FETCH_ASSOC
    );


    if (!$user) {

        header(
            "Location: settings.php?error=user"
        );

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | Verify current password
    |--------------------------------------------------------------------------
    */

    if (
        !password_verify(
            $currentPassword,
            $user['password']
        )
    ) {

        header(
            "Location: settings.php?error=current_password"
        );

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | Hash new password
    |--------------------------------------------------------------------------
    */

    $hashedPassword =
        password_hash(
            $newPassword,
            PASSWORD_DEFAULT
        );


    $update = $pdo->prepare("
        UPDATE users

        SET password = ?

        WHERE id = ?
    ");


    $update->execute([
        $hashedPassword,
        $userId
    ]);


    header(
        "Location: settings.php?success=password"
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| Invalid action
|--------------------------------------------------------------------------
*/

header(
    "Location: settings.php?error=invalid_action"
);

exit;