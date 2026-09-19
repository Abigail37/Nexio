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


/*
|--------------------------------------------------------------------------
| Get category ID
|--------------------------------------------------------------------------
*/

$categoryId = isset($_GET['id'])
    ? (int) $_GET['id']
    : 0;

if ($categoryId <= 0) {
    header("Location: categories.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Fetch category
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        id,
        name,
        description,
        is_active
    FROM categories
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$categoryId]);

$category = $stmt->fetch();

if (!$category) {
    header("Location: categories.php");
    exit;
}


$success = "";
$error = "";


/*
|--------------------------------------------------------------------------
| Handle category update
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $isActive = isset($_POST['is_active']) ? 1 : 0;


    /*
    |--------------------------------------------------------------------------
    | Validate
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
        | Check duplicate category name
        |--------------------------------------------------------------------------
        */

        $checkStmt = $pdo->prepare("
            SELECT id
            FROM categories
            WHERE LOWER(name) = LOWER(:name)
              AND id != :id
            LIMIT 1
        ");

        $checkStmt->execute([
            ':name' => $name,
            ':id' => $categoryId
        ]);

        if ($checkStmt->fetch()) {

            $error = "A category with this name already exists.";

        } else {

            /*
            |--------------------------------------------------------------------------
            | Update category
            |--------------------------------------------------------------------------
            */

            try {

                $updateStmt = $pdo->prepare("
                    UPDATE categories
                    SET
                        name = :name,
                        description = :description,
                        is_active = :is_active
                    WHERE id = :id
                ");

                $updateStmt->execute([
                    ':name' => $name,
                    ':description' => $description !== ''
                        ? $description
                        : null,
                    ':is_active' => $isActive,
                    ':id' => $categoryId
                ]);

                $success = "Category updated successfully.";
                header("Location: categories.php");

                /*
                |--------------------------------------------------------------------------
                | Update local category data
                |--------------------------------------------------------------------------
                */

                $category['name'] = $name;
                $category['description'] = $description;
                $category['is_active'] = $isActive;

            } catch (PDOException $e) {

                $error = "Unable to update category.";

            }

        }

    }

}


require_once "assets/includes/header.php";

?>

<section class="admin-page">

    <div class="container">

        <div class="page-heading">

            <h1>Edit Category</h1>

            <p>
                Update the category information below.
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
                action="edit_category.php?id=<?= $categoryId ?>">

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
                            $_POST['name'] ?? $category['name']
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
                            $_POST['description']
                            ?? $category['description']
                            ?? ''
                        ) ?></textarea>

                </div>


                <div class="form-group checkbox-group">

                    <label>

                        <input
                            type="checkbox"
                            name="is_active"
                            value="1"
                            <?= isset($_POST['is_active'])
                                ? 'checked'
                                : (
                                    (int) $category['is_active'] === 1
                                        ? 'checked'
                                        : ''
                                ) ?>>

                        Active Category

                    </label>

                </div>


                <button
                    type="submit"
                    class="btn">
                    Save Changes
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
`