<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isLoggedIn = isset($_SESSION['user_id']);
?>

</main>

<section class="newsletter-section">
    <div class="container">
        <div class="newsletter-content">
            <div class="newsletter-text">
                <span class="newsletter-label">STAY CONNECTED</span>
                <h2>Get the latest from Nexio.</h2>
                <p>
                    Subscribe to our newsletter for new products,
                    updates, and useful technology news.
                </p>
            </div>

            <form class="newsletter-form" action="#" method="POST">
                <input
                    type="email"
                    name="email"
                    placeholder="Enter your email address"
                    required>

                <button type="submit">
                    Subscribe
                </button>
            </form>
        </div>
    </div>
</section>

<footer class="site-footer">
    <div class="container">
        <div class="footer-main">
            <div class="footer-brand">
                <a href="index.php" class="footer-logo">NEXIO</a>
                <p>
                    Technology that keeps you connected.
                    Discover reliable tech products for work,
                    learning, entertainment, and everyday life.
                </p>
            </div>

            <div class="footer-column">
                <h3>Explore</h3>
                <ul>
                    <li>
                        <a href="index.php">Home</a>
                    </li>
                    <li>
                        <a href="products.php">Products</a>
                    </li>
                    <li>
                        <a href="categories.php">Categories</a>
                    </li>
                    <li>
                        <a href="about.php">About Us</a>
                    </li>
                </ul>
            </div>

            <div class="footer-column">
                <h3>Customer</h3>
                <ul>
                    <li>
                        <a href="cart.php">Shopping Cart</a>
                    </li>
                    <li>
                        <a href="contact.php">Contact Us</a>
                    </li>
                    <?php if ($isLoggedIn): ?>

                        <li><a href="account.php">My Account</a></li>
                        <li><a href="logout.php">Logout</a></li>

                    <?php else: ?>

                        <li><a href="login.php">Login</a></li>
                        <li><a href="register.php">Create Account</a></li>

                    <?php endif; ?>
                </ul>
            </div>

            <div class="footer-column footer-contact">
                <h3>Get in Touch</h3>
                <p>
                    <span>Email</span>
                    abigailogunmola37@gmail.com
                </p>
                <p>
                    <span>Phone</span>
                    +234 913 204 1854
                </p>
                <p>
                    <span>Location</span>
                    Akure, Ondo State, Nigeria.
                </p>
            </div>
        </div>

        <div class="footer-bottom">
            <p>
                &copy; <?= date('Y') ?> NEXIO.
                All rights reserved.
            </p>
            <div class="footer-bottom-links">
                <a href="#">Privacy Policy</a>
                <a href="#">Terms & Conditions</a>
            </div>
        </div>
    </div>
</footer>

<button type="button" class="back-to-top" id="backToTop" aria-label="Back to top">
    ⬆
</button>

<script src="./assets/js/script.js?v=2"></script>

</body>

</html>