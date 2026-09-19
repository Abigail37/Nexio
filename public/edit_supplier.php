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


$error = "";
$success = "";


/*
|--------------------------------------------------------------------------
| Get supplier ID
|--------------------------------------------------------------------------
*/

$supplierId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$supplierId || $supplierId <= 0) {

    http_response_code(400);
    die("Invalid supplier ID.");
}


/*
|--------------------------------------------------------------------------
| Fetch supplier
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        id,
        name,
        contact_email,
        contact_phone,
        address,
        is_active
    FROM suppliers
    WHERE id = :id
    LIMIT 1
");

$stmt->execute([
    ':id' => $supplierId
]);

$supplier = $stmt->fetch();


if (!$supplier) {

    http_response_code(404);
    die("Supplier not found.");
}


/*
|--------------------------------------------------------------------------
| Handle update
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim(
        $_POST['name'] ?? ''
    );

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
    | Validate
    |--------------------------------------------------------------------------
    */

    if ($name === '') {

        $error =
            "Supplier name is required.";

    } elseif (
        $email !== ''
        && !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $error =
            "Please enter a valid email address.";

    } else {

        try {

            /*
            |--------------------------------------------------------------------------
            | Check duplicate email
            |--------------------------------------------------------------------------
            */

            if ($email !== '') {

                $emailCheck = $pdo->prepare("
                    SELECT id
                    FROM suppliers
                    WHERE contact_email = :email
                      AND id != :id
                    LIMIT 1
                ");

                $emailCheck->execute([
                    ':email' =>
                        $email,

                    ':id' =>
                        $supplierId
                ]);

                if ($emailCheck->fetch()) {

                    throw new Exception(
                        "Another supplier already uses this email address."
                    );
                }
            }


            /*
            |--------------------------------------------------------------------------
            | Update supplier
            |--------------------------------------------------------------------------
            */

            $update = $pdo->prepare("
                UPDATE suppliers

                SET
                    name = :name,
                    contact_email = :contact_email,
                    contact_phone = :contact_phone,
                    address = :address,
                    updated_at = CURRENT_TIMESTAMP

                WHERE id = :id
            ");

            $update->execute([
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
                        : null,

                ':id' =>
                    $supplierId
            ]);


            $success =
                "Supplier updated successfully.";


            /*
            |--------------------------------------------------------------------------
            | Update displayed values
            |--------------------------------------------------------------------------
            */

            $supplier['name'] =
                $name;

            $supplier['contact_email'] =
                $email;

            $supplier['contact_phone'] =
                $phone;

            $supplier['address'] =
                $address;


        } catch (Exception $e) {

            $error =
                $e->getMessage();

        } catch (PDOException $e) {

            $error =
                "Unable to update supplier.";
        }
    }
}


require_once "assets/includes/header.php";

?>

<section class="admin-page">

    <div class="container">

        <div class="page-heading">

            <h1>Edit Supplier</h1>

            <p>
                Update supplier information.
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
                action="edit_supplier.php?id=<?= (int) $supplierId ?>"
            >


                <!-- NAME -->

                <div class="form-group">

                    <label for="name">
                        Supplier Name
                    </label>

                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="<?= htmlspecialchars(
                            $supplier['name']
                        ) ?>"
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
                            $supplier[
                                'contact_email'
                            ] ?? ''
                        ) ?>"
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
                            $supplier[
                                'contact_phone'
                            ] ?? ''
                        ) ?>"
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
                    ><?= htmlspecialchars(
                        $supplier[
                            'address'
                        ] ?? ''
                    ) ?></textarea>

                </div>


                <!-- BUTTONS -->

                <button
                    type="submit"
                    class="btn"
                >
                    Save Changes
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