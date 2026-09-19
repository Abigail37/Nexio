<?php

require_once "../includes/db.php";
require_once "../includes/mailer.php";

$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirmPassword = $_POST["confirm_password"] ?? "";

    // -----------------------------
    // Validate name
    // -----------------------------

    if (empty($name)) {

        $message = "Please enter your name.";
        $messageType = "error";
    } elseif (strlen($name) < 2) {

        $message = "Name must contain at least 2 characters.";
        $messageType = "error";

        // -----------------------------
        // Validate email
        // -----------------------------

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Please enter a valid email address.";
        $messageType = "error";

        // -----------------------------
        // Validate password
        // -----------------------------

    } elseif (strlen($password) < 8) {

        $message = "Password must contain at least 8 characters.";
        $messageType = "error";
    } elseif ($password !== $confirmPassword) {

        $message = "Passwords do not match.";
        $messageType = "error";
    } else {

        // Check whether email already exists
        $stmt = $pdo->prepare(
            "SELECT id, email_verified FROM users WHERE email = ?"
        );

        $stmt->execute([$email]);

        $existingUser = $stmt->fetch();

        if ($existingUser) {

            if ($existingUser["email_verified"]) {

                $message = "An account with this email already exists.";
                $messageType = "error";
            } else {

                $message = "This email is already registered but not verified.";
                $messageType = "error";
            }
        } else {

            // -----------------------------
            // Hash password
            // -----------------------------

            $hashedPassword = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            // -----------------------------
            // Create customer account
            // -----------------------------

            $stmt = $pdo->prepare(
                "INSERT INTO users
                (name, email, password, role, email_verified)
                VALUES (?, ?, ?, 'customer', FALSE)"
            );

            $stmt->execute([
                $name,
                $email,
                $hashedPassword
            ]);

            $userId = $pdo->lastInsertId();

            // -----------------------------
            // Generate secure OTP
            // -----------------------------

            $otp = (string) random_int(100000, 999999);

            // Hash OTP before storing it
            $hashedOTP = password_hash(
                $otp,
                PASSWORD_DEFAULT
            );

            // OTP expires in 5 minutes
            $expiresAt = date(
                "Y-m-d H:i:s",
                strtotime("+5 minutes")
            );

            // -----------------------------
            // Store OTP
            // -----------------------------

            $stmt = $pdo->prepare(
                "INSERT INTO email_otps
                (user_id, email, otp_code, expires_at)
                VALUES (?, ?, ?, ?)"
            );

            $stmt->execute([
                $userId,
                $email,
                $hashedOTP,
                $expiresAt
            ]);

            // -----------------------------
            // Send OTP
            // -----------------------------

            $emailSent = sendOTPEmail(
                $email,
                $name,
                $otp
            );

            if ($emailSent) {

                // Store email temporarily for verification page
                session_start();

                $_SESSION["verification_email"] = $email;

                header("Location: verify-otp.php");
                exit;
            } else {

                // Remove account if email could not be sent
                $stmt = $pdo->prepare(
                    "DELETE FROM users WHERE id = ?"
                );

                $stmt->execute([$userId]);

                $message = "We could not send the verification email. Please try again.";
                $messageType = "error";
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
        content="width=device-width, initial-scale=1.0"
    >

    <title>Create Account</title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

</head>

<body class="register-page">

    <div class="register-container">

        <div class="register-card">

            <div class="register-header">

                <h1 class="text-center">Create Account</h1>

                <p>
                    Create your Nexio customer account
                </p>

            </div>


            <?php if (!empty($message)): ?>

                <div class="register-message <?= htmlspecialchars($messageType) ?>">

                    <?= htmlspecialchars($message) ?>

                </div>

            <?php endif; ?>


            <form
                method="POST"
                class="register-form"
            >

                <div class="form-group">

                    <label for="name">
                        Full Name
                    </label>

                    <input
                        type="text"
                        id="name"
                        name="name"
                        placeholder="Enter your full name"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="email">
                        Email Address
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="Enter your email"
                        required
                    >

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
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="confirm_password">
                        Confirm Password
                    </label>

                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        placeholder="Confirm your password"
                        required
                    >

                </div>


                <button
                    type="submit"
                    class="register-button"
                >
                    Create Account
                </button>

            </form>


            <div class="register-footer">

                <p>

                    Already have an account?

                    <a href="login.php">
                        Login
                    </a>

                </p>

            </div>

        </div>

    </div>

</body>

</html>