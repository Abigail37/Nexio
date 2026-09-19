<?php

require_once "../includes/db.php";
require_once "assets/includes/header.php";


/*
|--------------------------------------------------------------------------
| Check management permission
|--------------------------------------------------------------------------
*/

$userRole = getUserRole();

$canManageCategories = in_array(
    $userRole,
    ['superuser', 'ceo', 'manager'],
    true
);


/*
|--------------------------------------------------------------------------
| Fetch active categories with product count
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        c.id,
        c.name,
        c.description,
        COUNT(p.id) AS product_count
    FROM categories c
    LEFT JOIN products p
        ON p.category_id = c.id
        AND p.is_active = 1
    WHERE c.is_active = 1
    GROUP BY
        c.id,
        c.name,
        c.description
    ORDER BY c.name ASC
");

$categories = $stmt->fetchAll();

?>

<section class="categories-page">

    <div class="container">

        <div class="page-heading">

            <div>

                <h1>Categories</h1>

                <p>
                    Browse our products by category.
                </p>

            </div>

        </div>


        <?php if (empty($categories)): ?>

            <div class="empty-state">

                <h2>
                    No categories available
                </h2>

                <p>
                    Categories will appear here when they are added.
                </p>

            </div>

        <?php else: ?>

            <div class="category-grid">

                <?php foreach ($categories as $category): ?>

                    <div class="category-card">

                        <div class="category-card-content">

                            <h2>
                                <?= htmlspecialchars($category['name']) ?>
                            </h2>

                            <p class="category-description">

                                <?php if (!empty($category['description'])): ?>

                                    <?= htmlspecialchars($category['description']) ?>

                                <?php else: ?>

                                    Browse products in this category.

                                <?php endif; ?>

                            </p>


                            <span class="category-product-count">

                                <?= (int) $category['product_count'] ?>

                                <?= (int) $category['product_count'] === 1
                                    ? 'Product'
                                    : 'Products'
                                ?>

                            </span>

                        </div>


                        <div class="category-card-footer">

                            <a
                                href="products.php?category=<?= (int) $category['id'] ?>"
                                class="category-view-button"
                            >
                                View Products
                                <!-- <span>→</span> -->
                            </a>


                            <?php if ($canManageCategories): ?>

                                <a
                                    href="edit_category.php?id=<?= (int) $category['id'] ?>"
                                    class="category-edit-button"
                                >
                                    Edit
                                </a>

                            <?php endif; ?>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    </div>

</section>


<?php require_once "assets/includes/footer.php"; ?>