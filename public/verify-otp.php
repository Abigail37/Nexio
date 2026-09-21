<?php
session_start();
require_once "../includes/db.php";
require_once "../includes/auth.php";
require_once "../includes/mailer.php";

$message = "";

if (!isset($_SESSION["verification_email"])) {

    header("Location: register.php");
    exit;
}

$email = $_SESSION["verification_email"];

if (isset($_GET["resend"]) && $_GET["resend"] === "1") {

    $lastResend = $_SESSION["otp_resend_time"] ?? 0;
    $currentTime = time();

    if (($currentTime - $lastResend) < 60) {

        $remaining = 60 - ($currentTime - $lastResend);

        $message = "Please wait {$remaining} seconds before requesting another code.";
    } else {

        // Get the user
        $stmt = $pdo->prepare(
            "SELECT id, name
             FROM users
             WHERE email = ?
             LIMIT 1"
        );

        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {

            $message = "Account not found. Please register again.";
        } else {

            // Generate new OTP
            $otp = (string) random_int(100000, 999999);

            $hashedOTP = password_hash(
                $otp,
                PASSWORD_DEFAULT
            );

            $expiresAt = date(
                "Y-m-d H:i:s",
                strtotime("+5 minutes")
            );

            // Invalidate previous OTPs
            $stmt = $pdo->prepare(
                "UPDATE email_otps
                 SET verified_at = NOW()
                 WHERE email = ?
                 AND verified_at IS NULL"
            );

            $stmt->execute([$email]);

            // Save new OTP
            $stmt = $pdo->prepare(
                "INSERT INTO email_otps
                (user_id, email, otp_code, expires_at, attempts)
                VALUES (?, ?, ?, ?, 0)"
            );

            $stmt->execute([
                $user["id"],
                $email,
                $hashedOTP,
                $expiresAt
            ]);

            // Send new OTP
            $emailSent = sendOTPEmail(
                $email,
                $user["name"],
                $otp
            );

            if ($emailSent) {

                $_SESSION["otp_resend_time"] = $currentTime;

                $message = "A new verification code has been sent to your email.";
            } else {

                $message = "We could not send the new verification code. Please try again.";
            }
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $otp = trim($_POST["otp"] ?? "");

    if (!preg_match('/^[0-9]{6}$/', $otp)) {
        $message = "Please enter a valid 6-digit OTP.";
    } else {

        $stmt = $pdo->prepare(
            "SELECT *
             FROM email_otps
             WHERE email = ?
             ORDER BY created_at DESC
             LIMIT 1"
        );

        $stmt->execute([$email]);
        $otpRecord = $stmt->fetch();

        if (!$otpRecord) {

            $message = "Verification code not found.";
        } elseif ($otpRecord["verified_at"] !== null) {

            $message = "This verification code has already been used.";
        } elseif (strtotime($otpRecord["expires_at"]) < time()) {

            $message = "This verification code has expired.";
        } elseif ($otpRecord["attempts"] >= 5) {

            $message = "Too many incorrect attempts. Please request a new code.";
        } else {

            $stmt = $pdo->prepare(
                "UPDATE email_otps
                 SET attempts = attempts + 1
                 WHERE id = ?"
            );

            $stmt->execute([$otpRecord["id"]]);

            if (password_verify($otp, $otpRecord["otp_code"])) {

                $stmt = $pdo->prepare(
                    "UPDATE email_otps
                     SET verified_at = NOW()
                     WHERE id = ?"
                );

                $stmt->execute([$otpRecord["id"]]);

                // Activate user's email
                $stmt = $pdo->prepare(
                    "UPDATE users
     SET email_verified = TRUE
     WHERE email = ?"
                );

                $stmt->execute([$email]);

                // Get the verified customer
                $stmt = $pdo->prepare(
                    "SELECT *
     FROM users
     WHERE email = ?
     LIMIT 1"
                );

                $stmt->execute([$email]);
                $user = $stmt->fetch();

                // Log the customer in
                loginUser($user);

                // Remove verification session
                unset($_SESSION["verification_email"]);

                $message = "Email verified successfully!";

                // Show success message briefly before redirecting
                header("Refresh: 2; URL=index.php");
            } else {
                $message = "Incorrect verification code.";
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

    <title>Verify Email</title>

    <link
        rel="stylesheet"
        href="assets/css/style.css">

</head>


<body class="verify-page">

    <div class="verify-container">

        <div class="verify-card">

            <div class="verify-header">

                <h1>Verify Your Email</h1>

                <p>
                    Enter the 6-digit verification code sent to:
                </p>

                <strong class="verify-email">
                    <?= htmlspecialchars($email) ?>
                </strong>

            </div>


            <?php if (!empty($message)): ?>

                <div class="verify-message">

                    <?= htmlspecialchars($message) ?>

                </div>

            <?php endif; ?>


            <form
                method="POST"
                class="verify-form">

                <div class="form-group">

                    <label for="otp">
                        Verification Code
                    </label>

                    <input
                        type="text"
                        id="otp"
                        name="otp"
                        maxlength="6"
                        pattern="[0-9]{6}"
                        inputmode="numeric"
                        autocomplete="one-time-code"
                        placeholder="Enter 6-digit code"
                        required>

                </div>


                <button
                    type="submit"
                    class="verify-button">
                    Verify Email
                </button>

            </form>


            <div class="verify-footer">

                <p>
                    Didn't receive the code?
                </p>

                <?php
                $lastResend = $_SESSION["otp_resend_time"] ?? 0;
                $cooldownRemaining = max(
                    0,
                    60 - (time() - $lastResend)
                );
                ?>

                <?php if ($cooldownRemaining > 0): ?>

                    <span class="resend-disabled">
                        Request a new code (<span id="resendCountdown"><?= $cooldownRemaining ?></span>s)
                    </span>

                <?php else: ?>

                    <a href="verify-otp.php?resend=1">
                        Request a new code
                    </a>

                <?php endif; ?>

            </div>

        </div>

    </div>

    <script>
        let countdown = document.getElementById("resendCountdown");

        if (countdown) {

            let remaining = parseInt(countdown.textContent);

            const timer = setInterval(function() {

                remaining--;

                if (remaining <= 0) {

                    clearInterval(timer);

                    window.location.reload();

                } else {

                    countdown.textContent = remaining;
                }

            }, 1000);
        }
    </script>

</body>

</html>