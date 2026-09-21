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
                    <div style="position: relative;">
                        <input
                            type="password"
                            name="password"
                            id="loginPassword"
                            class="form-control"
                            required>

                        <button
                            type="button"
                            onclick="togglePassword('loginPassword', this)"
                            aria-label="Show password"
                            style="
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            border: none;
            background: transparent;
            padding: 4px;
            cursor: pointer;
            color: #6b7280;
        ">
                            <svg
                                width="20"
                                height="20"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z" />
                                <circle cx="12" cy="12" r="3" />
                            </svg>
                        </button>
                    </div>

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

    <script>
        function togglePassword(inputId, button) {
            const input = document.getElementById(inputId);

            if (input.type === "password") {
                input.type = "text";
                button.setAttribute("aria-label", "Hide password");

                button.innerHTML = `
            <svg
                width="20"
                height="20"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
            >
                <path d="M3 3l18 18"/>
                <path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"/>
                <path d="M9.9 4.2A10.8 10.8 0 0 1 12 4c6.5 0 10 8 10 8a18.5 18.5 0 0 1-3.2 4.6"/>
                <path d="M6.6 6.6C3.7 8.5 2 12 2 12s3.5 8 10 8c1.8 0 3.4-.5 4.8-1.2"/>
            </svg>
        `;
            } else {
                input.type = "password";
                button.setAttribute("aria-label", "Show password");

                button.innerHTML = `
            <svg
                width="20"
                height="20"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
            >
                <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/>
                <circle cx="12" cy="12" r="3"/>
            </svg>
        `;
            }
        }
    </script>

</body>

</html>