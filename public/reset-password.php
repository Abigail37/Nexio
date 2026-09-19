<?php
session_start();
require_once "../includes/db.php";

$message = "";
$messageType = "";

$token = trim($_GET["token"] ?? $_POST["token"] ?? "");

if (empty($token)) {

    $message = "Invalid or missing password reset link.";
    $messageType = "error";

} elseif ($_SERVER["REQUEST_METHOD"] === "POST") {

    $password = $_POST["password"] ?? "";
    $confirmPassword = $_POST["confirm_password"] ?? "";

    if (strlen($password) < 8) {

        $message = "Password must be at least 8 characters long.";
        $messageType = "error";

    } elseif ($password !== $confirmPassword) {

        $message = "Passwords do not match.";
        $messageType = "error";

    } else {

        // Find valid reset token
        $stmt = $pdo->prepare(
            "SELECT *
             FROM password_resets
             WHERE token = ?
             AND used_at IS NULL
             AND expires_at > NOW()
             LIMIT 1"
        );

        $stmt->execute([$token]);
        $reset = $stmt->fetch();

        if (!$reset) {

            $message = "This password reset link is invalid or has expired.";
            $messageType = "error";

        } else {

            // Hash the new password
            $hashedPassword = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            // Update password
            $stmt = $pdo->prepare(
                "UPDATE users
                 SET password = ?
                 WHERE email = ?"
            );

            $stmt->execute([
                $hashedPassword,
                $reset["email"]
            ]);

            // Mark reset token as used
            $stmt = $pdo->prepare(
                "UPDATE password_resets
                 SET used_at = NOW()
                 WHERE id = ?"
            );

            $stmt->execute([$reset["id"]]);

            $message = "Your password has been reset successfully.";
            $messageType = "success";

            // Redirect to login after a short delay
            header("Refresh: 2; URL=login.php");
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

    <title>Reset Password</title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

</head>


<body class="reset-password-page">

    <div class="reset-password-container">

        <div class="reset-password-card">

            <div class="reset-password-header">

                <h1>Reset Password</h1>

                <p>
                    Create a new password for your Nexio account.
                </p>

            </div>


            <?php if (!empty($message)): ?>

                <div class="reset-password-message <?= htmlspecialchars($messageType) ?>">

                    <?= htmlspecialchars($message) ?>

                </div>

            <?php endif; ?>


            <?php if ($messageType !== "success" && !empty($token)): ?>

                <form
                    method="POST"
                    class="reset-password-form"
                >

                    <input
                        type="hidden"
                        name="token"
                        value="<?= htmlspecialchars($token) ?>"
                    >


                    <div class="form-group">

                        <label for="password">
                            New Password
                        </label>

                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Enter new password"
                            minlength="8"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="confirm_password">
                            Confirm New Password
                        </label>

                        <input
                            type="password"
                            id="confirm_password"
                            name="confirm_password"
                            placeholder="Confirm new password"
                            minlength="8"
                            required
                        >

                    </div>


                    <button
                        type="submit"
                        class="reset-password-button"
                    >
                        Reset Password
                    </button>

                </form>

            <?php endif; ?>


            <div class="reset-password-footer">

                <a href="login.php">
                    Back to Login
                </a>

            </div>

        </div>

    </div>

</body>

</html>