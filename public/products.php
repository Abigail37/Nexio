<?php

require_once "../includes/db.php";
require_once "assets/includes/header.php";


/*
|--------------------------------------------------------------------------
| Filters
|--------------------------------------------------------------------------
*/

$categoryId = isset($_GET['category']) ? (int) $_GET['category'] : 0;
$priceFilter = isset($_GET['price']) ? trim($_GET['price']) : '';
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;

if ($page < 1) {
    $page = 1;
}


/*
|--------------------------------------------------------------------------
| Price filter ranges
|--------------------------------------------------------------------------
*/

$priceRanges = [
    'under-50000' => [
        'label' => 'Under ₦50,000',
        'min' => 0,
        'max' => 50000
    ],
    '50000-100000' => [
        'label' => '₦50,000 – ₦100,000',
        'min' => 50000,
        'max' => 100000
    ],
    '100000-300000' => [
        'label' => '₦100,000 – ₦300,000',
        'min' => 100000,
        'max' => 300000
    ],
    '300000-700000' => [
        'label' => '₦300,000 – ₦700,000',
        'min' => 300000,
        'max' => 700000
    ],
    '700000-up' => [
        'label' => '₦700,000+',
        'min' => 700000,
        'max' => null
    ]
];


/*
|--------------------------------------------------------------------------
| Validate price filter
|--------------------------------------------------------------------------
*/

if (!array_key_exists($priceFilter, $priceRanges)) {
    $priceFilter = '';
}


/*
|--------------------------------------------------------------------------
| Fetch categories
|--------------------------------------------------------------------------
*/

$categoryStmt = $pdo->query(
    "SELECT
        c.id,
        c.name,
        COUNT(p.id) AS product_count
     FROM categories c
     LEFT JOIN products p
        ON p.category_id = c.id
        AND p.is_active = 1
     WHERE c.is_active = 1
     GROUP BY c.id, c.name
     ORDER BY c.name ASC"
);

$categories = $categoryStmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Build product query
|--------------------------------------------------------------------------
*/

$where = [
    "p.is_active = 1",
    "c.is_active = 1"
];

$params = [];


/*
|--------------------------------------------------------------------------
| Category filter
|--------------------------------------------------------------------------
*/

if ($categoryId > 0) {

    $where[] = "p.category_id = ?";
    $params[] = $categoryId;
}


/*
|--------------------------------------------------------------------------
| Price filter
|--------------------------------------------------------------------------
*/

if ($priceFilter !== '') {

    $range = $priceRanges[$priceFilter];

    if ($range['max'] === null) {

        $where[] = "p.price >= ?";
        $params[] = $range['min'];
    } else {

        $where[] = "p.price >= ?";
        $where[] = "p.price <= ?";

        $params[] = $range['min'];
        $params[] = $range['max'];
    }
}

$whereSql = implode(" AND ", $where);


/*
|--------------------------------------------------------------------------
| Count products
|--------------------------------------------------------------------------
*/

$countSql = "
    SELECT COUNT(*)
    FROM products p
    INNER JOIN categories c
        ON p.category_id = c.id
    WHERE {$whereSql}
";

$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);

$totalProducts = (int) $countStmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| Pagination
|--------------------------------------------------------------------------
*/

$productsPerPage = 9;

$totalPages = max(
    1,
    (int) ceil($totalProducts / $productsPerPage)
);

if ($page > $totalPages) {
    $page = $totalPages;
}

$offset = ($page - 1) * $productsPerPage;


/*
|--------------------------------------------------------------------------
| Fetch products
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        p.id,
        p.name,
        p.description,
        p.price,
        p.stock_quantity,
        p.low_stock_threshold,
        p.image_url,
        c.name AS category_name
    FROM products p
    INNER JOIN categories c
        ON p.category_id = c.id
    WHERE {$whereSql}
    ORDER BY p.created_at DESC
    LIMIT " . (int) $productsPerPage . "
    OFFSET " . (int) $offset;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$products = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Selected category name
|--------------------------------------------------------------------------
*/

$selectedCategoryName = 'All Products';

if ($categoryId > 0) {

    foreach ($categories as $category) {

        if ((int) $category['id'] === $categoryId) {

            $selectedCategoryName = $category['name'];
            break;
        }
    }
}


/*
|--------------------------------------------------------------------------
| Pagination URL helper
|--------------------------------------------------------------------------
*/

function buildProductsUrl($pageNumber)
{
    global $categoryId, $priceFilter;

    $query = [
        'page' => $pageNumber
    ];

    if ($categoryId > 0) {
        $query['category'] = $categoryId;
    }

    if ($priceFilter !== '') {
        $query['price'] = $priceFilter;
    }

    return 'products.php?' . http_build_query($query);
}

?>

<section class="products-page">

    <div class="container">

        <!-- =====================================================
             PRODUCTS HERO
        ====================================================== -->

        <div class="products-hero">

            <div class="products-hero-content">

                <span class="products-hero-label">
                    NEXIO STORE
                </span>

                <h1>
                    Technology that
                    <span>keeps you connected.</span>
                </h1>

                <p>
                    Explore our collection of technology products,
                    accessories, and smart devices designed for
                    work, learning, entertainment, and everyday life.
                </p>

                <div class="products-hero-meta">

                    <span>
                        <?php echo $totalProducts; ?>
                        <?php echo $totalProducts === 1 ? 'product' : 'products'; ?>
                    </span>

                    <span class="hero-meta-divider"></span>

                    <span>
                        Shop by category or price
                    </span>

                </div>

            </div>


            <div class="products-hero-visual">

                <div class="hero-orbit hero-orbit-one"></div>

                <div class="hero-orbit hero-orbit-two"></div>

                <div class="hero-product-mark">
                    N
                </div>

                <div class="hero-floating-card hero-card-one">
                    <span>01</span>
                    <strong>Tech</strong>
                </div>

                <div class="hero-floating-card hero-card-two">
                    <span>02</span>
                    <strong>Smart</strong>
                </div>

                <div class="hero-floating-card hero-card-three">
                    <span>03</span>
                    <strong>Connected</strong>
                </div>

            </div>

        </div>


        <!-- =====================================================
             SHOPPING AREA
        ====================================================== -->

        <section class="products-page">
            <!-- ===================================================== SHOPPING AREA ====================================================== -->
            <div class="products-section-heading">
                <div> <span class="products-label"> EXPLORE </span>
                    <h2> Find what you need. </h2>
                </div>
                <p> Browse our products using the filters to narrow down your options. </p>
            </div>

            <div class="products-layout">

                <!-- =====================================================
                 SIDEBAR
            ====================================================== -->
                <aside class="products-sidebar">

                    <div class="category-sidebar">


                        <!-- CATEGORY FILTER -->

                        <div class="category-sidebar-section">

                            <div class="category-sidebar-header">

                                <span class="products-label">
                                    SHOP
                                </span>

                                <h3>
                                    Categories
                                </h3>

                            </div>


                            <nav class="category-sidebar-nav">

                                <a
                                    href="products.php"
                                    class="category-sidebar-link <?php echo ($categoryId === 0 && $priceFilter === '') ? 'active' : ''; ?>">
                                    All Products
                                </a>


                                <?php foreach ($categories as $category): ?>

                                    <a
                                        href="products.php?category=<?php echo (int) $category['id']; ?>"
                                        class="category-sidebar-link <?php echo ($categoryId === (int) $category['id']) ? 'active' : ''; ?>">

                                        <span>
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </span>

                                        <span class="category-product-count">
                                            <?php echo (int) $category['product_count']; ?>
                                        </span>

                                    </a>

                                <?php endforeach; ?>

                            </nav>

                        </div>


                        <!-- PRICE FILTER -->

                        <div class="price-filter-section">

                            <div class="price-filter-header">

                                <span class="products-label">
                                    FILTER BY
                                </span>

                                <h3>
                                    Price
                                </h3>

                            </div>


                            <nav class="price-filter-nav">

                                <a
                                    href="products.php<?php echo $categoryId > 0 ? '?category=' . $categoryId : ''; ?>"
                                    class="price-filter-link <?php echo ($priceFilter === '') ? 'active' : ''; ?>">

                                    All Prices

                                </a>


                                <?php foreach ($priceRanges as $key => $range): ?>

                                    <?php

                                    $query = [
                                        'price' => $key
                                    ];

                                    if ($categoryId > 0) {
                                        $query['category'] = $categoryId;
                                    }

                                    ?>

                                    <a
                                        href="products.php?<?php echo http_build_query($query); ?>"
                                        class="price-filter-link <?php echo ($priceFilter === $key) ? 'active' : ''; ?>">

                                        <?php echo htmlspecialchars($range['label']); ?>

                                    </a>

                                <?php endforeach; ?>

                            </nav>

                        </div>


                        <!-- CLEAR FILTERS -->

                        <?php if ($categoryId > 0 || $priceFilter !== ''): ?>

                            <div class="clear-filters">

                                <a href="products.php">
                                    Clear all filters
                                </a>

                            </div>

                        <?php endif; ?>


                    </div>

                </aside>

                <!-- =====================================================
                 PRODUCTS
                 ====================================================== -->
                <main class="products-main">


                    <div class="products-header">

                        <div>

                            <span class="products-label">
                                <?php echo htmlspecialchars($selectedCategoryName); ?>
                            </span>

                            <h2>
                                <?php echo htmlspecialchars($selectedCategoryName); ?>
                            </h2>

                            <p>
                                <?php echo $totalProducts; ?>
                                <?php echo $totalProducts === 1 ? 'product' : 'products'; ?>
                                available
                            </p>

                        </div>

                    </div>


                    <?php if (empty($products)): ?>

                        <div class="empty-products">

                            <h3>
                                No products found
                            </h3>

                            <p>
                                Try adjusting your category or price filters.
                            </p>

                            <a
                                href="products.php"
                                class="btn btn-primary">
                                Clear Filters
                            </a>

                        </div>

                    <?php else: ?>


                        <div class="nexio-product-grid">

                            <?php foreach ($products as $product): ?>

                                <?php

                                $stock = (int) $product['stock_quantity'];
                                $threshold = (int) ($product['low_stock_threshold'] ?? 5);

                                if ($stock <= 0) {

                                    $stockClass = 'out-of-stock';
                                    $stockText = 'Out of Stock';
                                } elseif ($stock <= $threshold) {

                                    $stockClass = 'low-stock';
                                    $stockText = 'Only ' . $stock . ' left';
                                } else {

                                    $stockClass = 'in-stock';
                                    $stockText = 'In Stock';
                                }

                                ?>


                                <article class="nexio-product-card">


                                    <a
                                        href="product_details.php?id=<?php echo (int) $product['id']; ?>"
                                        class="product-image">

                                        <?php if (!empty($product['image_url'])): ?>

                                            <img
                                                src="<?php echo htmlspecialchars($product['image_url']); ?>"
                                                alt="<?php echo htmlspecialchars($product['name']); ?>">

                                        <?php else: ?>

                                            <div class="product-image-placeholder">
                                                NEXIO
                                            </div>

                                        <?php endif; ?>

                                    </a>


                                    <div class="nexio-product-info">

                                        <span class="product-category">
                                            <?php echo htmlspecialchars($product['category_name']); ?>
                                        </span>

                                        <h3>
                                            <a href="product_details.php?id=<?php echo (int) $product['id']; ?>">
                                                <?php echo htmlspecialchars($product['name']); ?>
                                            </a>
                                        </h3>

                                        <?php if (!empty($product['description'])): ?>

                                            <p class="product-description">
                                                <?php echo htmlspecialchars($product['description']); ?>
                                            </p>

                                        <?php endif; ?>


                                        <div class="product-bottom">

                                            <div>

                                                <span class="product-price">
                                                    ₦<?php echo number_format((float) $product['price'], 2); ?>
                                                </span>

                                                <span class="product-stock <?php echo $stockClass; ?>">
                                                    <?php echo htmlspecialchars($stockText); ?>
                                                </span>

                                            </div>


                                            <a
                                                href="product_details.php?id=<?php echo (int) $product['id']; ?>"
                                                class="product-view-btn">
                                                ➔
                                            </a>

                                        </div>

                                    </div>

                                </article>

                            <?php endforeach; ?>

                        </div>


                        <!-- =================================================
                         PAGINATION
                    ================================================== -->

                        <?php if ($totalPages > 1): ?>

                            <div class="products-pagination">

                                <?php if ($page > 1): ?>

                                    <a
                                        href="<?php echo htmlspecialchars(buildProductsUrl($page - 1)); ?>"
                                        class="pagination-link">

                                        ← Previous

                                    </a>

                                <?php endif; ?>


                                <div class="pagination-pages">

                                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>

                                        <a
                                            href="<?php echo htmlspecialchars(buildProductsUrl($i)); ?>"
                                            class="pagination-link <?php echo ($i === $page) ? 'active' : ''; ?>">

                                            <?php echo $i; ?>

                                        </a>

                                    <?php endfor; ?>

                                </div>


                                <?php if ($page < $totalPages): ?>

                                    <a
                                        href="<?php echo htmlspecialchars(buildProductsUrl($page + 1)); ?>"
                                        class="pagination-link">

                                        Next →

                                    </a>

                                <?php endif; ?>

                            </div>

                        <?php endif; ?>


                    <?php endif; ?>


                </main>

            </div>
        </section>
    </div>

</section>


<?php require_once "assets/includes/footer.php"; ?>