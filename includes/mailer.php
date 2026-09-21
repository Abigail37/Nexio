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
                Thank you for registering with Nexio.
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

        error_log(
            "Nexio Mail Error: " . $mail->ErrorInfo
        );

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


function sendOrderConfirmationEmail(
    $recipientEmail,
    $recipientName,
    $orderId,
    $totalAmount,
    $orderItems
) {
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

        $mail->Subject =
            'Nexio Order Confirmation - Order #' . $orderId;


        /*
        |--------------------------------------------------------------------------
        | Build order items HTML
        |--------------------------------------------------------------------------
        */

        $itemsHtml = '';

        foreach ($orderItems as $item) {

            $productName =
                htmlspecialchars($item['name']);

            $quantity =
                (int) $item['quantity'];

            $price =
                number_format(
                    (float) $item['price_at_purchase'],
                    2
                );

            $subtotal =
                number_format(
                    (float) $item['subtotal'],
                    2
                );

            $itemsHtml .= "
                <tr>
                    <td style='
                        padding: 12px;
                        border-bottom: 1px solid #e5e7eb;
                    '>
                        {$productName}
                    </td>

                    <td style='
                        padding: 12px;
                        border-bottom: 1px solid #e5e7eb;
                        text-align: center;
                    '>
                        {$quantity}
                    </td>

                    <td style='
                        padding: 12px;
                        border-bottom: 1px solid #e5e7eb;
                        text-align: right;
                    '>
                        ₦{$price}
                    </td>

                    <td style='
                        padding: 12px;
                        border-bottom: 1px solid #e5e7eb;
                        text-align: right;
                    '>
                        ₦{$subtotal}
                    </td>
                </tr>
            ";
        }


        $formattedTotal =
            number_format(
                (float) $totalAmount,
                2
            );


        /*
        |--------------------------------------------------------------------------
        | Email body
        |--------------------------------------------------------------------------
        */

        $mail->Body = "

        <div style='
            margin: 0;
            padding: 30px 15px;
            background-color: #f5f7fb;
            font-family: Arial, sans-serif;
            color: #1f2937;
        '>

            <div style='
                max-width: 650px;
                margin: 0 auto;
                background: #ffffff;
                border-radius: 10px;
                overflow: hidden;
                border: 1px solid #e5e7eb;
            '>

                <div style='
                    padding: 25px;
                    text-align: center;
                    background: #0f172a;
                    color: #ffffff;
                '>
                    <h1 style='
                        margin: 0;
                        font-size: 28px;
                    '>
                        Nexio
                    </h1>

                    <p style='
                        margin: 8px 0 0;
                        font-size: 13px;
                        color: #cbd5e1;
                    '>
                        Technology • Quality • Innovation
                    </p>
                </div>


                <div style='padding: 30px;'>

                    <h2 style='margin-top: 0;'>
                        Order Confirmed 🎉
                    </h2>

                    <p>
                        Hello <strong>" .
            htmlspecialchars($recipientName) .
            "</strong>,
                    </p>

                    <p>
                        Thank you for shopping with Nexio.
                        Your payment has been successfully received
                        and your order is now being processed.
                    </p>


                    <div style='
                        background: #f0f9ff;
                        border: 1px solid #bae6fd;
                        border-radius: 8px;
                        padding: 18px;
                        margin: 25px 0;
                    '>

                        <p style='margin: 5px 0;'>
                            <strong>Order Number:</strong>
                            #{$orderId}
                        </p>

                        <p style='margin: 5px 0;'>
                            <strong>Payment Status:</strong>
                            <span style='color: #15803d;'>
                                Successful
                            </span>
                        </p>

                        <p style='margin: 5px 0;'>
                            <strong>Order Status:</strong>
                            Processing
                        </p>

                    </div>


                    <h3>Order Summary</h3>

                    <table style='
                        width: 100%;
                        border-collapse: collapse;
                        font-size: 14px;
                    '>

                        <thead>

                            <tr style='
                                background: #f8fafc;
                            '>

                                <th style='
                                    padding: 12px;
                                    text-align: left;
                                '>
                                    Product
                                </th>

                                <th style='
                                    padding: 12px;
                                    text-align: center;
                                '>
                                    Qty
                                </th>

                                <th style='
                                    padding: 12px;
                                    text-align: right;
                                '>
                                    Price
                                </th>

                                <th style='
                                    padding: 12px;
                                    text-align: right;
                                '>
                                    Subtotal
                                </th>

                            </tr>

                        </thead>

                        <tbody>

                            {$itemsHtml}

                        </tbody>

                    </table>


                    <div style='
                        margin-top: 20px;
                        padding-top: 15px;
                        border-top: 2px solid #e5e7eb;
                        text-align: right;
                    '>

                        <strong style='font-size: 18px;'>
                            Total: ₦{$formattedTotal}
                        </strong>

                    </div>


                    <p style='
                        margin-top: 30px;
                    '>
                        We will keep you updated as your order
                        progresses.
                    </p>

                    <p>
                        Thank you for choosing Nexio.
                    </p>

                </div>


                <div style='
                    padding: 20px;
                    text-align: center;
                    background: #f8fafc;
                    color: #64748b;
                    font-size: 12px;
                '>

                    <p style='margin: 0;'>
                        © " . date('Y') . " Nexio.
                        All rights reserved.
                    </p>

                </div>

            </div>

        </div>
        ";


        /*
        |--------------------------------------------------------------------------
        | Plain-text fallback
        |--------------------------------------------------------------------------
        */

        $mail->AltBody =
            "Hello {$recipientName},\n\n" .
            "Thank you for shopping with Nexio.\n\n" .
            "Your order #{$orderId} has been confirmed.\n" .
            "Payment Status: Successful\n" .
            "Order Status: Processing\n\n" .
            "Order Total: ₦{$formattedTotal}\n\n" .
            "Thank you for choosing Nexio.";


        $mail->send();

        return true;
    } catch (Exception $e) {

        error_log(
            "Nexio Order Confirmation Email Error: " .
                $mail->ErrorInfo
        );

        return false;
    }
}
