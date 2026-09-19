<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

function sendOTPEmail($recipientEmail, $recipientName, $otp)
{
    $mailConfig = require __DIR__ . '/mail_config.php';

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = $mailConfig['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $mailConfig['username'];
        $mail->Password = $mailConfig['password'];
        $mail->SMTPSecure = $mailConfig['encryption'];
        $mail->Port = $mailConfig['port'];

        $mail->setFrom(
            $mailConfig['from_email'],
            $mailConfig['from_name']
        );

        $mail->addAddress($recipientEmail, $recipientName);

        $mail->isHTML(true);
        $mail->Subject = 'Your Verification Code';

        $mail->Body = "
            <h2>Verify Your Email</h2>

            <p>Hello " . htmlspecialchars($recipientName) . ",</p>

            <p>
                Thank you for registering with the Sales Management System.
            </p>

            <p>Your verification code is:</p>

            <h1 style='letter-spacing: 5px;'>$otp</h1>

            <p>
                This code will expire in <strong>5 minutes</strong>.
            </p>

            <p>
                If you did not create this account, you can safely ignore
                this email.
            </p>
        ";

        $mail->AltBody =
            "Hello $recipientName,\n\n" .
            "Your verification code is: $otp\n\n" .
            "This code will expire in 5 minutes.";

        $mail->send();

        return true;

    } catch (Exception $e) {

        return false;
    }
}

function sendPasswordResetEmail($recipientEmail, $recipientName, $resetLink)
{
    $mailConfig = require __DIR__ . '/mail_config.php';

    $mail = new PHPMailer(true);

    try {

        $mail->isSMTP();
        $mail->Host = $mailConfig['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $mailConfig['username'];
        $mail->Password = $mailConfig['password'];
        $mail->SMTPSecure = $mailConfig['encryption'];
        $mail->Port = $mailConfig['port'];

        $mail->setFrom(
            $mailConfig['from_email'],
            $mailConfig['from_name']
        );

        $mail->addAddress(
            $recipientEmail,
            $recipientName
        );

        $mail->isHTML(true);

        $mail->Subject = 'Reset Your Nexio Password';

        $mail->Body = "
            <h2>Reset Your Password</h2>

            <p>Hello " . htmlspecialchars($recipientName) . ",</p>

            <p>
                We received a request to reset the password for your
                Nexio account.
            </p>

            <p>
                Click the button below to create a new password:
            </p>

            <p>
                <a
                    href='" . htmlspecialchars($resetLink) . "'
                    style='
                        display: inline-block;
                        padding: 12px 20px;
                        background: #2563eb;
                        color: #ffffff;
                        text-decoration: none;
                        border-radius: 6px;
                    '
                >
                    Reset Password
                </a>
            </p>

            <p>
                This link will expire in <strong>15 minutes</strong>.
            </p>

            <p>
                If you did not request a password reset, you can safely
                ignore this email.
            </p>

            <p>
                Regards,<br>
                Nexio Team
            </p>
        ";

        $mail->AltBody =
            "Hello $recipientName,\n\n" .
            "We received a request to reset your Nexio password.\n\n" .
            "Use the following link to reset your password:\n" .
            "$resetLink\n\n" .
            "This link will expire in 15 minutes.\n\n" .
            "If you did not request a password reset, you can safely ignore this email.\n\n" .
            "Regards,\n" .
            "Nexio Team";

        $mail->send();

        return true;

    } catch (Exception $e) {

        return false;
    }
}