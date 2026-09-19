<?php

require_once "../includes/db.php";
require_once "../includes/auth.php";

requireLogin();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| Inventory access
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
    die("You do not have permission to adjust stock.");
}


$success = "";
$error = "";


/*
|--------------------------------------------------------------------------
| Fetch active products
|--------------------------------------------------------------------------
*/

$productStmt = $pdo->query("
    SELECT
        id,
        name,
        stock_quantity
    FROM products
    WHERE is_active = 1
    ORDER BY name ASC
");

$products = $productStmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Handle stock adjustment
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $productId = (int) ($_POST['product_id'] ?? 0);

    $newStockInput = trim(
        $_POST['new_stock'] ?? ''
    );

    $reason = trim(
        $_POST['reason'] ?? ''
    );


    /*
    |--------------------------------------------------------------------------
    | Validate product
    |--------------------------------------------------------------------------
    */

    if ($productId <= 0) {

        $error = "Please select a product.";

    } elseif (
        $newStockInput === ''
        || filter_var(
            $newStockInput,
            FILTER_VALIDATE_INT
        ) === false
        || (int) $newStockInput < 0
    ) {

        $error =
            "Please enter a valid new stock quantity.";

    } elseif ($reason === '') {

        $error =
            "Please provide a reason for the adjustment.";

    } else {

        $newStock = (int) $newStockInput;


        try {

            $pdo->beginTransaction();


            /*
            |--------------------------------------------------------------------------
            | Lock product row
            |--------------------------------------------------------------------------
            */

            $lockStmt = $pdo->prepare("
                SELECT
                    id,
                    name,
                    stock_quantity
                FROM products
                WHERE id = :product_id
                  AND is_active = 1
                FOR UPDATE
            ");

            $lockStmt->execute([
                ':product_id' => $productId
            ]);

            $product = $lockStmt->fetch();


            if (!$product) {

                throw new Exception(
                    "The selected product could not be found."
                );
            }


            $previousStock =
                (int) $product['stock_quantity'];


            /*
            |--------------------------------------------------------------------------
            | No change
            |--------------------------------------------------------------------------
            */

            if ($previousStock === $newStock) {

                throw new Exception(
                    "The new stock quantity is the same as the current stock."
                );
            }


            /*
            |--------------------------------------------------------------------------
            | Calculate adjustment quantity
            |--------------------------------------------------------------------------
            */

            $quantity =
                abs($newStock - $previousStock);


            /*
            |--------------------------------------------------------------------------
            | Update product stock
            |--------------------------------------------------------------------------
            */

            $updateStmt = $pdo->prepare("
                UPDATE products

                SET
                    stock_quantity = :new_stock,
                    updated_at = CURRENT_TIMESTAMP

                WHERE id = :product_id
            ");

            $updateStmt->execute([
                ':new_stock' =>
                    $newStock,

                ':product_id' =>
                    $productId
            ]);


            /*
            |--------------------------------------------------------------------------
            | Record inventory transaction
            |--------------------------------------------------------------------------
            */

            $transactionStmt = $pdo->prepare("
                INSERT INTO inventory_transactions (
                    product_id,
                    user_id,
                    transaction_type,
                    quantity,
                    previous_stock,
                    new_stock,
                    reason
                )

                VALUES (
                    :product_id,
                    :user_id,
                    'adjustment',
                    :quantity,
                    :previous_stock,
                    :new_stock,
                    :reason
                )
            ");

            $transactionStmt->execute([
                ':product_id' =>
                    $productId,

                ':user_id' =>
                    getUserId(),

                ':quantity' =>
                    $quantity,

                ':previous_stock' =>
                    $previousStock,

                ':new_stock' =>
                    $newStock,

                ':reason' =>
                    $reason
            ]);


            /*
            |--------------------------------------------------------------------------
            | Commit
            |--------------------------------------------------------------------------
            */

            $pdo->commit();


            $success =
                "Stock adjusted successfully. " .
                $product['name'] .
                " now has " .
                number_format($newStock) .
                " units in stock.";


            /*
            |--------------------------------------------------------------------------
            | Clear form
            |--------------------------------------------------------------------------
            */

            $_POST = [];


        } catch (Exception $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $error =
                $e->getMessage();
        }
    }
}


require_once "assets/includes/header.php";

?>

<section class="admin-page">

    <div class="container">

        <div class="page-heading">

            <h1>Adjust Stock</h1>

            <p>
                Correct the current stock quantity of a product.
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


        <?php if (empty($products)): ?>

            <div class="error-message">

                There are no active products available.

                <br><br>

                <a href="add_product.php">
                    Add Product
                </a>

            </div>

        <?php else: ?>


            <div class="form-card">

                <form
                    method="POST"
                    action="adjust_stock.php"
                >


                    <!-- PRODUCT -->

                    <div class="form-group">

                        <label for="product_id">
                            Product
                        </label>

                        <select
                            id="product_id"
                            name="product_id"
                            required
                        >

                            <option value="">
                                Select a product
                            </option>

                            <?php foreach (
                                $products as $product
                            ): ?>

                                <option
                                    value="<?= (int) $product['id'] ?>"
                                    <?= (
                                        isset(
                                            $_POST['product_id']
                                        )
                                        && (int) $_POST[
                                            'product_id'
                                        ] === (int) $product['id']
                                    )
                                        ? 'selected'
                                        : ''
                                    ?>
                                >

                                    <?= htmlspecialchars(
                                        $product['name']
                                    ) ?>

                                    —
                                    Current stock:
                                    <?= number_format(
                                        (int) $product[
                                            'stock_quantity'
                                        ]
                                    ) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <!-- NEW STOCK -->

                    <div class="form-group">

                        <label for="new_stock">
                            New Stock Quantity
                        </label>

                        <input
                            type="number"
                            id="new_stock"
                            name="new_stock"
                            value="<?= htmlspecialchars(
                                $_POST['new_stock'] ?? ''
                            ) ?>"
                            min="0"
                            step="1"
                            placeholder="Enter correct stock quantity"
                            required
                        >

                    </div>


                    <!-- REASON -->

                    <div class="form-group">

                        <label for="reason">
                            Reason
                        </label>

                        <textarea
                            id="reason"
                            name="reason"
                            rows="4"
                            placeholder="e.g. Physical stock count showed 18 units"
                            required
                        ><?= htmlspecialchars(
                            $_POST['reason'] ?? ''
                        ) ?></textarea>

                    </div>


                    <!-- SUBMIT -->

                    <button
                        type="submit"
                        class="btn"
                    >
                        Adjust Stock
                    </button>


                    <a
                        href="inventory.php"
                        class="back-link"
                    >
                        ← Back to Inventory
                    </a>

                </form>

            </div>

        <?php endif; ?>

    </div>

</section>


<?php require_once "assets/includes/footer.php"; ?>