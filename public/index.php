<?php

require_once "../includes/db.php";
require_once "assets/includes/header.php";

/*
|--------------------------------------------------------------------------
| Fetch active categories
|--------------------------------------------------------------------------
| Only 4 random categories are shown on the homepage.
| The full category list remains available on categories.php.
|--------------------------------------------------------------------------
*/

$categoryStmt = $pdo->query(
    "SELECT id, name, description
     FROM categories
     WHERE is_active = 1
     ORDER BY RAND()
     LIMIT 4"
);

$categories = $categoryStmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Fetch featured products
|--------------------------------------------------------------------------
| Products are randomly selected so the homepage does not always
| display products according to their creation order.
|--------------------------------------------------------------------------
*/

$productStmt = $pdo->query(
    "SELECT
        p.id,
        p.name,
        p.description,
        p.price,
        p.stock_quantity,
        p.image_url,
        c.name AS category_name
     FROM products p
     INNER JOIN categories c
        ON p.category_id = c.id
     WHERE p.is_active = 1
       AND c.is_active = 1
     ORDER BY RAND()
     LIMIT 6"
);

$featuredProducts = $productStmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Prepare hero products
|--------------------------------------------------------------------------
| Only products that actually have images are used in the hero slider.
|--------------------------------------------------------------------------
*/

$heroProducts = array_values(
    array_filter(
        $featuredProducts,
        function ($product) {
            return !empty($product['image_url']);
        }
    )
);

?>

<!-- =========================================================
     HERO
========================================================= -->

<section class="nexio-hero">

    <div class="container">

        <div class="nexio-hero-grid">

            <!-- HERO TEXT -->
            <div class="nexio-hero-content">

                <span class="hero-eyebrow">
                    TECHNOLOGY • QUALITY • INNOVATION
                </span>

                <h1>
                    Technology
                    <span>that moves with you.</span>
                </h1>

                <p>
                    Discover smartphones, laptops, accessories and
                    smart devices designed to keep you connected,
                    productive and ahead.
                </p>

                <div class="hero-actions">

                    <a href="products.php" class="btn btn-primary">
                        Shop Now
                        <span>→</span>
                    </a>

                    <a href="categories.php" class="btn btn-outline">
                        Explore Categories
                    </a>

                </div>

            </div>


            <!-- HERO PRODUCT VISUAL -->
            <div class="nexio-hero-visual">

                <div class="hero-circle"></div>

                <div class="hero-glow"></div>


                <?php
                /*
                 * Display available product images in the hero.
                 * JavaScript automatically rotates through them.
                 */
                ?>

                <?php
                $heroProducts = array_values(
                    array_filter(
                        $featuredProducts,
                        function ($product) {
                            return !empty($product['image_url']);
                        }
                    )
                );
                ?>


                <?php if (!empty($heroProducts)): ?>

                    <div class="hero-product-slideshow">

                        <?php foreach ($heroProducts as $index => $heroProduct): ?>

                            <div
                                class="hero-product-slide <?= $index === 0 ? 'active' : '' ?>"
                                data-hero-slide>

                                <div class="hero-product-main">

                                    <img
                                        src="<?= htmlspecialchars($heroProduct['image_url']) ?>"
                                        alt="<?= htmlspecialchars($heroProduct['name']) ?>">

                                </div>

                                <div class="hero-product-card">

                                    <span>Featured</span>
                                    <a href="product_details.php?id=<?php echo (int) $heroProduct['id']; ?>">
                                        <strong>
                                            <?= htmlspecialchars($heroProduct['name']) ?>
                                        </strong>
                                    </a>

                                    <small>
                                        ₦<?= number_format((float)$heroProduct['price'], 2) ?>
                                    </small>

                                </div>

                            </div>

                        <?php endforeach; ?>

                    </div>

                <?php else: ?>

                    <div class="hero-product-placeholder">
                        <span>YOUR TECH</span>
                        <strong>GOES HERE</strong>
                    </div>

                <?php endif; ?>

            </div>

        </div>

    </div>

</section>

</div>

</div>

</section>


<!-- =========================================================
     BENEFITS
========================================================= -->

<section class="nexio-benefits">

    <div class="container">

        <div class="benefits-grid">

            <div class="benefit-item">

                <div class="benefit-icon">
                    🚚
                </div>

                <div>
                    <h3>Fast Delivery</h3>
                    <p>Get your technology delivered with ease.</p>
                </div>

            </div>


            <div class="benefit-item">

                <div class="benefit-icon">
                    🔒
                </div>

                <div>
                    <h3>Secure Payments</h3>
                    <p>Shop confidently with secure checkout.</p>
                </div>

            </div>


            <div class="benefit-item">

                <div class="benefit-icon">
                    ✓
                </div>

                <div>
                    <h3>Quality Products</h3>
                    <p>Technology selected with quality in mind.</p>
                </div>

            </div>


            <div class="benefit-item">

                <div class="benefit-icon">
                    🎧
                </div>

                <div>
                    <h3>Customer Support</h3>
                    <p>We're here when you need us.</p>
                </div>

            </div>

        </div>

    </div>

</section>


<!-- =========================================================
     INTRO
========================================================= -->

<section class="nexio-intro">

    <div class="container">

        <div class="section-heading centered">

            <span class="section-label">
                WELCOME TO NEXIO
            </span>

            <h2>
                Your technology,
                <span>all in one place.</span>
            </h2>

            <p>
                From everyday essentials to the latest smart devices,
                Nexio brings the technology you need closer to you.
            </p>

        </div>

    </div>

</section>


<!-- =========================================================
     FEATURED PRODUCTS
========================================================= -->

<section class="featured-products" id="featured-products">

    <div class="container">

        <div class="section-heading">

            <div>

                <span class="section-label">
                    FEATURED PRODUCTS
                </span>

                <h2>
                    Top Picks for You
                </h2>

                <p>
                    Explore some of our latest technology products.
                </p>

            </div>

            <a href="products.php" class="section-link">
                View All Products →
            </a>

        </div>


        <?php if (empty($featuredProducts)): ?>

            <div class="empty-state">

                <h3>No products available</h3>

                <p>
                    Products will appear here once they are added.
                </p>

            </div>

        <?php else: ?>

            <div class="product-grid">

                <?php foreach ($featuredProducts as $product): ?>

                    <article class="product-card">

                        <a
                            href="product_details.php?id=<?= (int)$product['id'] ?>"
                            class="product-image">

                            <?php if (!empty($product['image_url'])): ?>

                                <img
                                    src="<?= htmlspecialchars($product['image_url']) ?>"
                                    alt="<?= htmlspecialchars($product['name']) ?>"
                                    loading="lazy">

                            <?php else: ?>

                                <div class="product-image-placeholder">
                                    <span>No Image</span>
                                </div>

                            <?php endif; ?>

                        </a>


                        <div class="product-card-content">

                            <span class="product-category">
                                <?= htmlspecialchars($product['category_name']) ?>
                            </span>


                            <h3>
                                <a
                                    href="product_details.php?id=<?= (int)$product['id'] ?>">
                                    <?= htmlspecialchars($product['name']) ?>
                                </a>
                            </h3>


                            <?php if (!empty($product['description'])): ?>

                                <p class="product-description">
                                    <?= htmlspecialchars($product['description']) ?>
                                </p>

                            <?php endif; ?>


                            <div class="product-card-bottom">

                                <strong class="product-price">
                                    ₦<?= number_format((float)$product['price'], 2) ?>
                                </strong>


                                <?php if ((int)$product['stock_quantity'] > 0): ?>

                                    <span class="stock-status in-stock">
                                        In Stock
                                    </span>

                                <?php else: ?>

                                    <span class="stock-status out-of-stock">
                                        Out of Stock
                                    </span>

                                <?php endif; ?>

                            </div>


                            <a
                                href="product_details.php?id=<?= (int)$product['id'] ?>"
                                class="btn btn-product">
                                View Product →
                            </a>

                        </div>

                    </article>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    </div>

</section>


<!-- =========================================================
     CATEGORIES
========================================================= -->

<section class="categories">

    <div class="container">

        <div class="section-heading">

            <div>

                <span class="section-label">
                    SHOP BY CATEGORY
                </span>

                <h2>
                    Explore Our Categories
                </h2>

                <p>
                    Find the technology that fits your needs.
                </p>

            </div>

            <a href="categories.php" class="section-link">
                View All Categories →
            </a>

        </div>


        <?php if (empty($categories)): ?>

            <div class="empty-state">

                <h3>No categories available</h3>

                <p>
                    Categories will appear here once they are added.
                </p>

            </div>

        <?php else: ?>

            <div class="category-grid">

                <?php foreach ($categories as $category): ?>

                    <a
                        href="products.php?category=<?= (int)$category['id'] ?>"
                        class="category-card">

                        <div class="category-card-content">

                            <!-- <span class="category-number">
                                <?= str_pad(
                                    (string)((int)$category['id']),
                                    2,
                                    '0',
                                    STR_PAD_LEFT
                                ) ?>
                            </span> -->


                            <h3>
                                <?= htmlspecialchars($category['name']) ?>
                            </h3>


                            <?php if (!empty($category['description'])): ?>

                                <p>
                                    <?= htmlspecialchars($category['description']) ?>
                                </p>

                            <?php endif; ?>


                            <span class="category-link">
                                Explore ➔
                            </span>

                        </div>

                    </a>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    </div>

</section>


<!-- =========================================================
     WHY NEXIO
========================================================= -->

<section class="why-nexio">

    <div class="container">

        <div class="why-nexio-grid">

            <div class="why-nexio-heading">

                <span class="section-label">
                    WHY NEXIO?
                </span>

                <h2>
                    More than a store.
                    <span>Your tech connection.</span>
                </h2>

            </div>


            <div class="why-nexio-text">

                <p>
                    Nexio makes it easier to discover, purchase and
                    manage the technology products you need.
                </p>

                <p>
                    Whether you're upgrading your device, setting up
                    your workspace or looking for everyday accessories,
                    we've got you covered.
                </p>

                <a href="about.php" class="section-link">
                    Read About Us →
                </a>

            </div>

        </div>

    </div>

</section>


<!-- =========================================================
     FINAL CTA
========================================================= -->

<section class="cta">

    <div class="container">

        <div class="cta-content">

            <span class="section-label">
                READY TO UPGRADE?
            </span>

            <h2>
                Find your next piece of technology.
            </h2>

            <p>
                Explore Nexio and discover technology made for
                the way you live, work and connect.
            </p>

            <a href="products.php" class="btn btn-primary">
                Explore Products →
            </a>

        </div>

    </div>

</section>


<?php require_once "assets/includes/footer.php"; ?>


<!-- =========================================================
     HERO SLIDESHOW
========================================================= -->

<script>
    document.addEventListener("DOMContentLoaded", function() {

        const heroSlides = document.querySelectorAll("[data-hero-slide]");

        if (heroSlides.length <= 1) {
            return;
        }

        let currentSlide = 0;

        setInterval(function() {

            heroSlides[currentSlide].classList.remove("active");

            currentSlide = (currentSlide + 1) % heroSlides.length;

            heroSlides[currentSlide].classList.add("active");

        }, 9000);

    });
</script>