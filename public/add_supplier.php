<?php

require_once "../includes/db.php";
require_once "../includes/auth.php";

requireLogin();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| Supplier management access
|--------------------------------------------------------------------------
*/

$allowedRoles = [
    'superuser',
    'ceo',
    'manager',
    'accountant'
];

if (!in_array(getUserRole(), $allowedRoles, true)) {
    http_response_code(403);
    die("You do not have permission to manage suppliers.");
}


$success = "";
$error = "";


/*
|--------------------------------------------------------------------------
| Handle supplier creation
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');

    $email = trim(
        $_POST['contact_email'] ?? ''
    );

    $phone = trim(
        $_POST['contact_phone'] ?? ''
    );

    $address = trim(
        $_POST['address'] ?? ''
    );


    /*
    |--------------------------------------------------------------------------
    | Validate supplier name
    |--------------------------------------------------------------------------
    */

    if ($name === '') {

        $error = "Supplier name is required.";

    /*
    |--------------------------------------------------------------------------
    | Validate email if provided
    |--------------------------------------------------------------------------
    */

    } elseif (
        $email !== ''
        && !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $error = "Please enter a valid email address.";

    } else {

        try {

            /*
            |--------------------------------------------------------------------------
            | Check duplicate email
            |--------------------------------------------------------------------------
            |
            | Only check when an email was provided.
            |
            */

            if ($email !== '') {

                $emailCheck = $pdo->prepare("
                    SELECT id
                    FROM suppliers
                    WHERE contact_email = :email
                    LIMIT 1
                ");

                $emailCheck->execute([
                    ':email' => $email
                ]);

                if ($emailCheck->fetch()) {

                    throw new Exception(
                        "A supplier with this email already exists."
                    );
                }
            }


            /*
            |--------------------------------------------------------------------------
            | Insert supplier
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare("
                INSERT INTO suppliers (
                    user_id,
                    name,
                    contact_email,
                    contact_phone,
                    address,
                    is_active
                )

                VALUES (
                    :user_id,
                    :name,
                    :contact_email,
                    :contact_phone,
                    :address,
                    1
                )
            ");

            $stmt->execute([
                ':user_id' =>
                    getUserId(),

                ':name' =>
                    $name,

                ':contact_email' =>
                    $email !== ''
                        ? $email
                        : null,

                ':contact_phone' =>
                    $phone !== ''
                        ? $phone
                        : null,

                ':address' =>
                    $address !== ''
                        ? $address
                        : null
            ]);


            $success =
                "Supplier created successfully.";


            /*
            |--------------------------------------------------------------------------
            | Clear form
            |--------------------------------------------------------------------------
            */

            $_POST = [];


        } catch (Exception $e) {

            $error =
                $e->getMessage();

        } catch (PDOException $e) {

            $error =
                "Unable to create supplier.";
        }
    }
}


require_once "assets/includes/header.php";

?>

<section class="admin-page">

    <div class="container">

        <div class="page-heading">

            <h1>Add Supplier</h1>

            <p>
                Add a supplier to your supplier records.
            </p>

        </div>


        <?php if ($success !== ''): ?>

            <div class="success-message">

                <?= htmlspecialchars($success) ?>

            </div>

        <?php endif; ?>
        <?php if ($error !== ''): ?>

            <div class="error-message">

                <?= htmlspecialchars($error) ?>

            </div>

        <?php endif; ?>
        <div class="form-card">

            <form
                method="POST"
                action="add_supplier.php"
            >
                <!-- SUPPLIER NAME -->
                <div class="form-group">

                    <label for="name">
                        Supplier Name
                    </label>

                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="<?= htmlspecialchars(
                            $_POST['name'] ?? ''
                        ) ?>"
                        placeholder="Enter supplier name"
                        required
                    >

                </div>


                <!-- EMAIL -->

                <div class="form-group">

                    <label for="contact_email">
                        Email
                        <span>(Optional)</span>
                    </label>

                    <input
                        type="email"
                        id="contact_email"
                        name="contact_email"
                        value="<?= htmlspecialchars(
                            $_POST['contact_email'] ?? ''
                        ) ?>"
                        placeholder="supplier@example.com"
                    >

                </div>


                <!-- PHONE -->

                <div class="form-group">

                    <label for="contact_phone">
                        Phone
                        <span>(Optional)</span>
                    </label>

                    <input
                        type="text"
                        id="contact_phone"
                        name="contact_phone"
                        value="<?= htmlspecialchars(
                            $_POST['contact_phone'] ?? ''
                        ) ?>"
                        placeholder="Supplier phone number"
                    >

                </div>


                <!-- ADDRESS -->

                <div class="form-group">

                    <label for="address">
                        Address
                        <span>(Optional)</span>
                    </label>

                    <textarea
                        id="address"
                        name="address"
                        rows="4"
                        placeholder="Supplier address"><?= htmlspecialchars($_POST['address'] ?? '') ?>
                    </textarea>

                </div>


                <!-- SUBMIT -->

                <button
                    type="submit"
                    class="btn"
                >
                    Add Supplier
                </button>


                <a
                    href="suppliers.php"
                    class="back-link"
                >
                    ← Back to Suppliers
                </a>

            </form>

        </div>

    </div>

</section>

<?php require_once "assets/includes/footer.php"; ?>