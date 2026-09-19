<?php

session_start();

require_once "../includes/db.php";
require_once "../includes/mailer.php";

$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Please enter a valid email address.";
        $messageType = "error";
    } else {

        // Find customer account
        $stmt = $pdo->prepare(
            "SELECT *
             FROM users
             WHERE email = ?
             AND role = 'customer'
             LIMIT 1"
        );

        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {

            $message = "No customer account was found with that email address.";
            $messageType = "error";
        } else {

            // Invalidate previous unused reset tokens
            $stmt = $pdo->prepare(
                "UPDATE password_resets
                 SET used_at = NOW()
                 WHERE email = ?
                 AND used_at IS NULL"
            );

            $stmt->execute([$email]);


            // Generate secure reset token
            $token = bin2hex(random_bytes(32));

            // Token expires in 15 minutes
            $expiresAt = date(
                "Y-m-d H:i:s",
                time() + (15 * 60)
            );


            // Store reset token
            $stmt = $pdo->prepare(
                "INSERT INTO password_resets
                 (email, token, expires_at)
                 VALUES (?, ?, ?)"
            );

            $stmt->execute([
                $email,
                $token,
                $expiresAt
            ]);


            // Build reset link
            $resetLink =
                "http://localhost/nexio/public/reset-password.php?token="
                . urlencode($token);


            $emailSent = sendPasswordResetEmail(
                $email,
                $user["name"],
                $resetLink
            );

            if ($emailSent) {

                $message = "Password reset instructions have been sent to your email.";
                $messageType = "success";
            } else {

                $message = "Unable to send the password reset email. Please try again.";
                $messageType = "error";
            }


            $message = "Password reset instructions have been sent to your email.";
            $messageType = "success";
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

    <title>Forgot Password</title>

    <link
        rel="stylesheet"
        href="assets/css/style.css">

</head>


<body class="forgot-password-page">

    <div class="forgot-password-container">

        <div class="forgot-password-card">

            <div class="forgot-password-header">

                <h1>Forgot Password?</h1>

                <p>
                    Enter your email address and we'll help you reset your password.
                </p>

            </div>


            <?php if (!empty($message)): ?>

                <div class="forgot-password-message <?= htmlspecialchars($messageType) ?>">

                    <?= htmlspecialchars($message) ?>

                </div>

            <?php endif; ?>


            <form
                method="POST"
                class="forgot-password-form">

                <div class="form-group">

                    <label for="email">
                        Email Address
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="Enter your email"
                        value="<?= htmlspecialchars($_POST["email"] ?? "") ?>"
                        required>

                </div>


                <button
                    type="submit"
                    class="forgot-password-button">
                    Send Reset Link
                </button>

            </form>


            <div class="forgot-password-footer">

                <a href="login.php">
                    Back to Login
                </a>

            </div>

        </div>

    </div>

</body>

</html>