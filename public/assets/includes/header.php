<?php

require_once "../includes/auth.php";

$isLoggedIn = isLoggedIn();
$userRole = getUserRole();

$currentPage = basename($_SERVER['PHP_SELF']);

/*
|--------------------------------------------------------------------------
| Roles allowed to add products
|--------------------------------------------------------------------------
*/

$canAddProduct = in_array(
    $userRole,
    ['superuser', 'ceo', 'manager'],
    true
);

$canAddCategory = in_array(
    $userRole,
    ['superuser', 'ceo', 'manager'],
    true
);
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Nexio | Technology Store</title>

    <link
        rel="stylesheet"
        href="assets/css/style.css">

</head>

<body>

    <header class="site-header">

        <div class="container navbar">

            <!-- LOGO -->

            <a
                href="index.php"
                class="logo">

                <span class="logo-icon">N</span>
                <span class="logo-text">NEXIO</span>

            </a>


            <!-- MOBILE MENU BUTTON -->
            <button
                type="button"
                class="mobile-menu-toggle"
                aria-label="Toggle navigation">
                <span></span>
                <span></span>
                <span></span>
            </button>

            <!-- NAVIGATION -->

            <nav class="main-nav">
                <a
                    href="index.php"
                    class="nav-link <?= $currentPage === 'index.php' ? 'active' : '' ?>">
                    Home
                </a>
                <a
                    href="about.php"
                    class="nav-link <?= $currentPage === 'about.php' ? 'active' : '' ?>">
                    About
                </a>

                <?php if ($canAddProduct): ?>

                    <div class="nav-dropdown">

                        <a
                            href="products.php"
                            class="nav-link dropdown-toggle <?= in_array($currentPage, ['products.php', 'product.php', 'add_product.php'], true) ? 'active' : '' ?>">
                            Products
                            <span class="dropdown-arrow"></span>

                        </a>

                        <div class="dropdown-menu">

                            <a href="products.php">
                                Browse Products
                            </a>

                            <a href="add_product.php">
                                Add Product
                            </a>

                        </div>

                    </div>

                <?php else: ?>
                    <a
                        href="products.php"
                        class="nav-link <?= in_array($currentPage, ['products.php', 'product.php'], true) ? 'active' : '' ?>">

                        Products

                    </a>
                <?php endif; ?>

                <?php if ($canAddCategory): ?>

                    <div class="nav-dropdown">

                        <a
                            href="categories.php"
                            class="nav-link dropdown-toggle <?= in_array($currentPage, ['categories.php', 'add_category.php'], true) ? 'active' : '' ?>">
                            Categories
                            <span class="dropdown-arrow"></span>

                        </a>

                        <div class="dropdown-menu">

                            <a href="categories.php">
                                Browse Categories
                            </a>

                            <a href="add_category.php">
                                Add Category
                            </a>

                        </div>

                    </div>

                <?php else: ?>
                    <a
                        href="categories.php"
                        class="nav-link <?= in_array($currentPage, ['categories.php'], true) ? 'active' : '' ?>">

                        Categories

                    </a>
                <?php endif; ?>
                <a
                    href="contact.php"
                    class="nav-link <?= $currentPage === 'contact.php' ? 'active' : '' ?>">

                    Contact

                </a>

                <a
                    href="cart.php"
                    class="nav-link <?= $currentPage === 'cart.php' ? 'active' : '' ?>">

                    Cart

                    <span class="cart-icon">🛒</span>

                </a>



            </nav>
            <nav class="main-nav">
                <?php if (!$isLoggedIn): ?>
                    <div class="nav-auth">

                        <a
                            href="login.php"
                            class="nav-login <?= $currentPage === 'login.php' ? 'active' : '' ?>">

                            Login

                        </a>

                        <a
                            href="register.php"
                            class="nav-register">

                            Create Account

                        </a>

                    </div>


                <?php elseif ($userRole === 'customer'): ?>
                    <a
                        href="account.php"
                        class="nav-link <?= $currentPage === 'account.php' ? 'active' : '' ?>">

                        My Account
                    </a>

                    <a
                        href="logout.php"
                        class="nav-link nav-logout">

                        Logout

                    </a>


                <?php else: ?>
                    <a
                        href="../admin/dashboard.php"
                        class="nav-link">

                        Dashboard

                    </a>
                    <a href="account.php"
                        class="nav-link">Profile</a>
                    <a
                        href="logout.php"
                        class="nav-link nav-logout">

                        Logout

                    </a>
                <?php endif; ?>
            </nav>

        </div>

    </header>


    <main class="site-main">