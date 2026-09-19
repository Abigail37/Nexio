<?php

require_once "../includes/db.php";
require_once "../includes/auth.php";

requireLogin();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| Check permission
|--------------------------------------------------------------------------
*/

$userRole = getUserRole();

$allowedRoles = [
    'superuser',
    'ceo',
    'manager'
];

if (!in_array($userRole, $allowedRoles, true)) {
    header("Location: categories.php");
    exit;
}


$success = "";
$error = "";


/*
|--------------------------------------------------------------------------
| Handle category creation
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $isActive = isset($_POST['is_active']) ? 1 : 0;


    /*
    |--------------------------------------------------------------------------
    | Validate category name
    |--------------------------------------------------------------------------
    */

    if ($name === '') {

        $error = "Category name is required.";

    } elseif (strlen($name) > 100) {

        $error = "Category name cannot exceed 100 characters.";

    } elseif (strlen($description) > 500) {

        $error = "Category description cannot exceed 500 characters.";

    } else {

        /*
        |--------------------------------------------------------------------------
        | Check for duplicate category
        |--------------------------------------------------------------------------
        */

        $checkStmt = $pdo->prepare("
            SELECT id
            FROM categories
            WHERE LOWER(name) = LOWER(:name)
            LIMIT 1
        ");

        $checkStmt->execute([
            ':name' => $name
        ]);

        if ($checkStmt->fetch()) {

            $error = "A category with this name already exists.";

        } else {

            /*
            |--------------------------------------------------------------------------
            | Insert category
            |--------------------------------------------------------------------------
            */

            try {

                $stmt = $pdo->prepare("
                    INSERT INTO categories (
                        name,
                        description,
                        is_active
                    )
                    VALUES (
                        :name,
                        :description,
                        :is_active
                    )
                ");

                $stmt->execute([
                    ':name' => $name,
                    ':description' => $description !== ''
                        ? $description
                        : null,
                    ':is_active' => $isActive
                ]);

                $success = "Category created successfully.";
                header("Location: categories.php");

                /*
                |--------------------------------------------------------------------------
                | Clear form after successful creation
                |--------------------------------------------------------------------------
                */

                $_POST = [];

            } catch (PDOException $e) {

                $error = "Unable to create category.";

            }

        }

    }

}


require_once "assets/includes/header.php";

?>

<section class="admin-page">

    <div class="container">

        <div class="page-heading">

            <h1>Add Category</h1>

            <p>
                Create a new product category.
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
                action="add_category.php">

                <div class="form-group">

                    <label for="name">
                        Category Name
                    </label>

                    <input
                        type="text"
                        id="name"
                        name="name"
                        maxlength="100"
                        value="<?= htmlspecialchars(
                            $_POST['name'] ?? ''
                        ) ?>"
                        placeholder="Enter category name"
                        required>

                </div>
                <div class="form-group">

                    <label for="description">
                        Description
                    </label>

                    <textarea
                        id="description"
                        name="description"
                        rows="4"
                        maxlength="500"
                        placeholder="Enter category description"><?= htmlspecialchars(
                            $_POST['description'] ?? ''
                        ) ?></textarea>

                </div>


                <div class="form-group checkbox-group">

                    <label>

                        <input
                            type="checkbox"
                            name="is_active"
                            value="1"
                            <?= isset($_POST['is_active']) || $_SERVER['REQUEST_METHOD'] !== 'POST'
                                ? 'checked'
                                : '' ?>>

                        Active Category

                    </label>

                </div>


                <button
                    type="submit"
                    class="btn">
                    Add Category
                </button>


                <a
                    href="categories.php"
                    class="back-link">
                    ← Back to Categories
                </a>

            </form>

        </div>

    </div>

</section>


<?php require_once "assets/includes/footer.php"; ?>