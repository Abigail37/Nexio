<?php

require_once "../includes/db.php";
require_once "../includes/auth.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Please enter a valid email address.";
    } elseif (empty($password)) {

        $message = "Please enter your password.";
    } else {

        // Find user by email
        $stmt = $pdo->prepare(
            "SELECT *
             FROM users
             WHERE email = ?
             LIMIT 1"
        );

        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {

            $message = "Invalid email or password.";
        } elseif (!$user["is_active"]) {

            $message = "This account has been deactivated.";
        } elseif (!$user["email_verified"]) {

            $message = "Please verify your email before logging in.";
        } elseif (!password_verify($password, $user["password"])) {

            $message = "Invalid email or password.";
        } else {

            // Successful login
            loginUser($user);

            // Redirect based on role
            switch ($user["role"]) {

                case "customer":
                    header("Location: index.php");
                    break;

                case "superuser":
                    header("Location: ../admin/dashboard.php");
                    break;

                case "ceo":
                case "manager":
                case "sales_rep":
                case "cashier":
                case "supplier":
                case "delivery":
                case "accountant":
                    header("Location: ../admin/dashboard.php");
                    break;

                default:
                    logoutUser();
                    $message = "Invalid account role.";
                    break;
            }

            if (empty($message)) {
                exit;
            }
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

    <title>Login</title>

    <link
        rel="stylesheet"
        href="assets/css/style.css">

</head>

<body class="login-page">

    <div class="login-container">

        <div class="login-card">

            <div class="login-header">

                <h1>Welcome Back</h1>

                <p>Login to your account</p>

            </div>


            <?php if (!empty($message)): ?>

                <div class="login-message">

                    <?= htmlspecialchars($message) ?>

                </div>

            <?php endif; ?>


            <form
                method="POST"
                class="login-form">

                <div class="form-group">

                    <label for="email">
                        Email Address
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="Enter your email"
                        required>

                </div>

                <div class="form-group">
                    <label for="password">
                        Password
                    </label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Enter your password"
                        required>

                    <div class="forgot-password-link">
                        <a href="forgot-password.php">
                            Forgot Password?
                        </a>
                    </div>

                </div>
                <button
                    type="submit"
                    class="login-button">
                    Login
                </button>

            </form>


            <div class="login-footer">
                <p>
                    Don't have an account?
                    <a href="register.php">
                        Register
                    </a>
                </p>
                <p>
                    <a href="index.php">
                        Back to Website
                    </a>
                </p>
            </div>

        </div>

    </div>

</body>

</html>