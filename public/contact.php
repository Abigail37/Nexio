<?php require_once "../includes/db.php";
require_once "assets/includes/header.php"; ?>
<section class="contact-page">
    <div class="container">
        <div class="page-heading contact-heading">
            <h1>Contact Us</h1>
            <p> Have a question or need assistance? We'd be happy to hear from you. </p>
        </div>
        <div class="contact-grid"> <!-- CONTACT INFORMATION -->
            <div class="contact-info">
                <div class="contact-info-header"> <span class="contact-label"> GET IN TOUCH </span>
                    <h2> We're here to help. </h2>
                    <p> Whether you have a question about a product, an order, or anything else, feel free to reach out. </p>
                </div>
                <div class="contact-details">
                    <!-- EMAIL -->
                    <div class="contact-detail">
                        <div class="contact-detail-icon"> @ </div>
                        <div>
                            <h3>Email</h3>
                            <p> abigailogunmola37@gmail.com </p>
                        </div>
                    </div>
                    <!-- PHONE -->
                    <div class="contact-detail">
                        <div class="contact-detail-icon"> ☎ </div>
                        <div>
                            <h3>Phone</h3>
                            <p> +234 913 204 1854 </p>
                        </div>
                    </div>
                    <!-- ADDRESS -->
                    <div class="contact-detail">
                        <div class="contact-detail-icon"> ⌖ </div>
                        <div>
                            <h3>Address</h3>
                            <p>Opp First Bank, BOI Cl, Alagbaka, Akure, Ondo State. </p>
                        </div>
                    </div>
                    <!-- HOURS -->
                    <div class="contact-detail">
                        <div class="contact-detail-icon"> ◷ </div>
                        <div>
                            <h3>Business Hours</h3>
                            <p> Monday – Friday: 9:00 AM – 5:00 PM </p>
                            <p> Saturday: 10:00 AM – 2:00 PM </p>
                        </div>
                    </div>
                    <!-- MAP -->
                    <div class="contact-map">
                        <iframe
                            src="https://www.google.com/maps?q=Akure, Ondo State, Nigeria&output=embed"
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade"
                            allowfullscreen>
                        </iframe>

                    </div>
                </div>
            </div> <!-- CONTACT FORM -->
            <div class="contact-form-card">
                <h2> Send us a message </h2>
                <p> Fill out the form below and we'll get back to you. </p>
                <form method="POST" action="contact.php" class="contact-form"> <!-- NAME -->
                    <div class="form-group"> <label for="name"> Name </label> <input type="text" id="name" name="name" placeholder="Enter your name" required> </div> <!-- EMAIL -->
                    <div class="form-group"> <label for="email"> Email </label> <input type="email" id="email" name="email" placeholder="Enter your email" required> </div> <!-- SUBJECT -->
                    <div class="form-group"> <label for="subject"> Subject </label> <input type="text" id="subject" name="subject" placeholder="What is your message about?" required> </div> <!-- MESSAGE -->
                    <div class="form-group"> <label for="message"> Message </label> <textarea id="message" name="message" rows="6" placeholder="Write your message..." required></textarea> </div> <!-- SUBMIT --> <button type="submit" class="btn contact-submit"> Send Message </button>
                </form>
            </div>
        </div>
    </div>
</section> <?php require_once "assets/includes/footer.php"; ?>