<?php

require_once "../includes/db.php";
require_once "includes/admin-header.php";

$success = "";
$error = "";

/*
|--------------------------------------------------------------------------
| Edit mode
|--------------------------------------------------------------------------
*/

$editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);

$editCategory = null;

if ($editId && $editId > 0) {

    $editStmt = $pdo->prepare("
        SELECT
            id,
            name,
            description,
            is_active
        FROM categories
        WHERE id = :id
        LIMIT 1
    ");

    $editStmt->execute([
        ':id' => $editId
    ]);

    $editCategory = $editStmt->fetch();

    if (!$editCategory) {
        $error = "Category not found.";
        $editId = null;
    }
}


/*
|--------------------------------------------------------------------------
| Handle category creation / editing
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $editId = filter_input(INPUT_POST, 'edit_id', FILTER_VALIDATE_INT);

    /*
    |--------------------------------------------------------------------------
    | Validate name
    |--------------------------------------------------------------------------
    */

    if ($name === '') {

        $error = "Category name is required.";
    } else {

        /*
        |--------------------------------------------------------------------------
        | Check for duplicate category
        |--------------------------------------------------------------------------
        */

        if ($editId) {

            $checkStmt = $pdo->prepare("
                SELECT id
                FROM categories
                WHERE name = :name
                AND id != :id
                LIMIT 1
            ");

            $checkStmt->execute([
                ':name' => $name,
                ':id' => $editId
            ]);
        } else {

            $checkStmt = $pdo->prepare("
                SELECT id
                FROM categories
                WHERE name = :name
                LIMIT 1
            ");

            $checkStmt->execute([
                ':name' => $name
            ]);
        }

        if ($checkStmt->fetch()) {

            $error = "A category with this name already exists.";
        } else {

            try {

                /*
                |--------------------------------------------------------------------------
                | Update existing category
                |--------------------------------------------------------------------------
                */

                if ($editId) {

                    $stmt = $pdo->prepare("
                        UPDATE categories
                        SET
                            name = :name,
                            description = :description,
                            is_active = :is_active
                        WHERE id = :id
                    ");

                    $stmt->execute([
                        ':name' => $name,
                        ':description' =>
                        $description !== ''
                            ? $description
                            : null,
                        ':is_active' => $isActive,
                        ':id' => $editId
                    ]);

                    $success = "Category updated successfully.";
                } else {

                    /*
                    |--------------------------------------------------------------------------
                    | Create new category
                    |--------------------------------------------------------------------------
                    */

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
                        ':description' =>
                        $description !== ''
                            ? $description
                            : null,
                        ':is_active' => $isActive
                    ]);

                    $success = "Category created successfully.";
                }

                /*
                |--------------------------------------------------------------------------
                | Clear form after successful operation
                |--------------------------------------------------------------------------
                */

                $editId = null;
                $editCategory = null;
                $_POST = [];
            } catch (PDOException $e) {

                $error = $editId
                    ? "Unable to update category."
                    : "Unable to create category.";
            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| Fetch all categories
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        id,
        name,
        description,
        is_active
    FROM categories
    ORDER BY name ASC
");

$categories = $stmt->fetchAll();

?>



<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <h3 class="fw-bold mb-3">Categories</h3>
            <ul class="breadcrumbs mb-3">
                <li class="nav-home">
                    <a href="dashboard.php">
                        <i class="icon-home"></i>
                    </a>
                </li>
                <li class="separator">
                    <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                    <a href="#">Category</a>
                </li>
            </ul>
        </div>
        <!-- add category  -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <?= $editCategory ? 'Edit Category' : 'Add Category' ?>
                        </div>
                    </div>
                    <div class="card-body">

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
                        <?php if (empty($categories)): ?>
                            <div class="error-message">
                                No categories are available.
                                Please create a category first.

                                <br><br>
                                <a href="categories.php">
                                    Add Category
                                </a>
                            </div>
                        <?php else: ?>
                            <form method="POST" action="categories.php">
                                <?php if ($editCategory): ?>
                                    <input
                                        type="hidden"
                                        name="edit_id"
                                        value="<?= (int) $editCategory['id'] ?>">
                                <?php endif; ?>
                                <div class="row">
                                    <!-- CATEGORY NAME -->
                                    <div class="col-md-6 col-lg-12">
                                        <div class="form-group">
                                            <label for="name">Category Name</label>
                                            <input
                                                type="text"
                                                id="name"
                                                name="name"
                                                value="<?= htmlspecialchars($editCategory['name'] ?? $_POST['name'] ?? '') ?>"
                                                placeholder="Enter category name"
                                                class="form-control"
                                                required />
                                        </div>
                                    </div>
                                    <!-- DESCRIPTION -->
                                    <div class="col-md-6 col-lg-12">
                                        <div class="form-group">
                                            <label for="description">Description</label>
                                            <textarea
                                                id="description"
                                                name="description"
                                                rows="4"
                                                class="form-control"
                                                placeholder="Enter category description"><?= htmlspecialchars($editCategory['description'] ?? $_POST['description'] ?? '') ?></textarea>
                                        </div>
                                    </div>
                                    <!-- ACTIVE CATEGORY -->
                                    <div class="col-md-6 col-lg-2">
                                        <div class="form-group checkbox-group">
                                            <label>
                                                <input
                                                    type="checkbox"
                                                    name="is_active"
                                                    value="1"
                                                    <?= (
                                                        isset($editCategory)
                                                        ? (int) $editCategory['is_active'] === 1
                                                        : true
                                                    ) ? 'checked' : '' ?>>
                                                Active Category
                                            </label>
                                        </div>
                                    </div>
                                    <!-- SUBMIT -->
                                    <div class="col-md-6 col-lg-2">
                                        <button
                                            type="submit"
                                            class="btn btn-primary btn-round">
                                            <?= $editCategory ? 'Update Category' : 'Add Category' ?>
                                        </button>
                                    </div>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <?php if (empty($categories)): ?>
            <div class="empty-state">
                <h2> No categories available </h2>
                <p>Categories will appear here when they are added. </p>
            </div>
        <?php else: ?>
            <!-- categories table  -->
            <div class="card card-round">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="basic-datatables" class="display table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php if (empty($categories)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center">
                                            No categories found.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($categories as $category): ?>
                                        <tr>
                                            <td> <?= (int) $category['id'] ?> </td>
                                            <td>
                                                <strong>
                                                    <?= htmlspecialchars($category['name']) ?>
                                                </strong>
                                            </td>
                                            <td>
                                                <?php if (!empty($category['description'])): ?>
                                                    <p>
                                                        <?= htmlspecialchars(
                                                            $category['description']
                                                        ) ?>
                                                    </p>
                                                <?php else: ?>
                                                    <p>
                                                        Browse products in this category.
                                                    </p>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ((int) $category['is_active'] === 1): ?>
                                                    <span class="badge badge-success">
                                                        Active
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge badge-danger">
                                                        Inactive
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span>
                                                    <a href="products.php?category=<?= (int) $category['id'] ?>" class="btn btn-primary btn-round btn-icon mb-2" title="View Products">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="categories.php?edit=<?= (int) $category['id'] ?>" class="btn btn-primary btn-round btn-icon mb-2">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a
                                                        href="delete-category.php?id=<?= (int) $category['id'] ?>"
                                                        onclick="return confirm('Are you sure you want to delete this category? This action cannot be undone.');"
                                                        class="btn btn-danger btn-round btn-icon mb-2">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once "includes/admin-footer.php"; ?>