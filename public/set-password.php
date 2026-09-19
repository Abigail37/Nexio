<?php

require_once "../includes/db.php";

$message = "";
$token = $_GET["token"] ?? $_POST["token"] ?? "";

if (empty($token)) {

    $message = "Invalid password setup link.";

} elseif ($_SERVER["REQUEST_METHOD"] === "POST") {

    $password = $_POST["password"] ?? "";
    $confirmPassword = $_POST["confirm_password"] ?? "";

    if (strlen($password) < 8) {

        $message = "Password must contain at least 8 characters.";

    } elseif ($password !== $confirmPassword) {

        $message = "Passwords do not match.";

    } else {

        $tokenHash = hash("sha256", $token);

        $stmt = $pdo->prepare(
            "SELECT *
             FROM password_setup_tokens
             WHERE token_hash = ?
             AND used_at IS NULL
             AND expires_at > NOW()
             LIMIT 1"
        );

        $stmt->execute([$tokenHash]);

        $setupToken = $stmt->fetch();

        if (!$setupToken) {

            $message =
                "This password setup link is invalid or has expired.";

        } else {

            $hashedPassword = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $stmt = $pdo->prepare(
                "UPDATE users
                 SET password = ?,
                     email_verified = TRUE
                 WHERE id = ?"
            );

            $stmt->execute([
                $hashedPassword,
                $setupToken["user_id"]
            ]);

            $stmt = $pdo->prepare(
                "UPDATE password_setup_tokens
                 SET used_at = NOW()
                 WHERE id = ?"
            );

            $stmt->execute([
                $setupToken["id"]
            ]);

            $message =
                "Password created successfully. "
                . "You can now log in.";

            $header = "Location: login.php";

            $token = "";
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

    <title>Set Password</title>

</head>

<body>

    <h1>Set Your Password</h1>

    <?php if (!empty($message)): ?>

        <p>
            <?= htmlspecialchars($message) ?>
        </p>

    <?php endif; ?>

    <?php if (!empty($token)): ?>

        <form method="POST">

            <input
                type="hidden"
                name="token"
                value="<?= htmlspecialchars($token) ?>"
            >

            <div>

                <label for="password">
                    New Password
                </label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    minlength="8"
                    required
                >

            </div>

            <br>

            <div>

                <label for="confirm_password">
                    Confirm Password
                </label>

                <input
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    minlength="8"
                    required
                >

            </div>

            <br>

            <button type="submit">
                Set Password
            </button>

        </form>

    <?php endif; ?>

</body>

</html>