<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

function sendPasswordSetupEmail($recipientEmail, $recipientName, $setupLink)
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
        $mail->Subject = 'Set Up Your Account';

        $mail->Body = "
            <h2>Welcome to Nexio</h2>
            <p>Hello " . htmlspecialchars($recipientName) . ",</p>
            <p> An account has been created for you.</p>
            <p> Click the button below to create your password:</p>
            <p>
                <a href='" . htmlspecialchars($setupLink) . "'>
                    Set My Password
                </a>
            </p>
            <p> This link will expire in <strong>30 minutes</strong>. </p>
            <p>If you were not expecting this account, please contact the system administrator.</p>
        ";

        $mail->AltBody =
            "Hello $recipientName,\n\n" .
            "An account has been created for you.\n\n" .
            "Use this link to create your password:\n" .
            "$setupLink\n\n" .
            "This link expires in 30 minutes.";

        $mail->send();

        return true;

    } catch (Exception $e) {

        return false;
    }
}