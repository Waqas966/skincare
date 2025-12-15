
<?php
// Include config file
require_once "config.php";

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Derma Elixir Clinic</title>
    <!-- Basic favicon -->
    <link rel="icon" href="./images/favicon.svg" sizes="32x32">
    <!-- SVG favicon -->
    <link rel="icon" href="./images/favicon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<style>
    .hero {
        background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)),
            url(./images/main-heros.png);
        background-size: cover;
        background-position: center;
        color: white;
        padding: 16% 0;
        text-align: center;

    }
</style>

<body>

    <?php
    require "./src/header.php";
    ?>
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <h1 class="animate__animated animate__backInDown">welcome to Derma Elixir Clinic</h1>
                <p class="animate__animated animate__backInDown">Discover personalized treatments by certified specialists</p>
                <div class="hero-buttons">
                    <a href="register.php" class="btn btn-primary">Make an Appointment</a>
                    <a href="lab-tests.php" class="btn btn-secondary">Explore Lab Tests</a>
                </div>
            </div>
        </div>
    </section>

    <section class="services">
        <div class="container">
            <div class="section-header">
                <h2>Our Services</h2>
                <p>Comprehensive skin and hair care solutions</p>
            </div>
            <div class="services-grid">
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-spa"></i>
                    </div>
                    <h3>SkinCare Treatments</h3>
                    <p>Advanced treatments for acne, pigmentation, and other skin concerns.</p>
                    <a href="skin-treatments.php" class="btn-text">Learn More <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <h3>Laser Treatments</h3>
                    <p>State-of-the-art laser procedures for hair removal and skin rejuvenation.</p>
                    <a href="laser-treatments.php" class="btn-text">Learn More <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-magic"></i>
                    </div>
                    <h3>Cosmetic Aesthetic</h3>
                    <p>Botox, fillers, and other aesthetic procedures for a youthful appearance.</p>
                    <a href="cosmetic-treatments.php" class="btn-text">Learn More <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-cut"></i>
                    </div>
                    <h3>Hair Treatments</h3>
                    <p>Solutions for hair loss, scalp issues, and hair restoration.</p>
                    <a href="hair-treatments.php" class="btn-text">Learn More <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>
        </div>
    </section>

<?php


require_once "specialists.php";

?>

<?php

require_once "testimonials.php";

?>



    <section class="cta">
        <div class="container">
            <div class="cta-content">
                <h2>Ready to Transform Your Skin?</h2>
                <p>Book an appointment with our specialists today</p>
                <a href="register.php" class="btn btn-primary">Schedule Now</a>
            </div>
        </div>
    </section>

    <?php
    require "./src/footer.php";
    ?>
    <script src="js/script.js"></script>
</body>

</html>