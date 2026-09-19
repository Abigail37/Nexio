<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check whether a user is logged in.
 */
function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

/**
 * Get the currently logged-in user's ID.
 */
function getUserId()
{
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get the currently logged-in user's role.
 */
function getUserRole()
{
    return $_SESSION['role'] ?? null;
}

/**
 * Require the user to be logged in.
 */
function requireLogin()
{
    if (!isLoggedIn()) {
        header("Location: ../public/login.php");
        exit;
    }
}

/**
 * Log the user into the session.
 */
function loginUser($user)
{
    // Prevent session fixation
    session_regenerate_id(true);

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['name'] = $user['name'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'];
}

/**
 * Log the user out.
 */
function logoutUser()
{
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    session_destroy();
}