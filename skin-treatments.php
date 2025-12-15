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
    .page-banner {
        background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)),
            url(./images/skincare-banner.png);
        background-size: cover;
        background-position: center;
        color: white;
        padding: 15%;
        text-align: center;
    }
</style>

<body>

    <?php
    require "./src/header.php";
    ?>

    <section class="page-banner">
        <div class="container">
            <h1 class="animate__animated animate__backInDown">Skin Care Treatments</h1>
            <p class="animate__animated animate__backInDown" style="font-size: 1.2rem; font-style: italic;">"Glow Naturally, Shine Confidently."</p>

        </div>
    </section>



    <section class="about-intro">
        <div class="container">
            <div class="about-grid">
                <div class="about-content">
                    <div class="section-header" style="text-align: left;">
                        <h2>Why Choose Us?</h2>
                    </div>
                    <p>DermaCare Clinic was founded in 2010 with a simple yet powerful vision: to provide exceptional
                        skin care solutions that transform lives. What started as a small practice has grown into a
                        leading dermatology clinic trusted by thousands of patients.</p>
                    <p>Our journey began when Dr. Sarah Johnson, a renowned dermatologist with a passion for helping
                        people gain confidence through healthy skin, opened the doors of our first clinic. Over the
                        years, we have expanded our team, services, and facilities to become the comprehensive skin care
                        center we are today.</p>
                    <div class="about-values">
                        <div class="value-item">
                            <div class="value-icon">
                                <i class="fas fa-leaf"></i>
                            </div>
                            <h3>Natural Glow</h3>
                            <p>Enhance your skin’s natural beauty with safe and gentle treatments.</p>
                        </div>
                        <div class="value-item">
                            <div class="value-icon">
                                <i class="fas fa-magic"></i>
                            </div>
                            <h3>Advanced Techniques</h3>
                            <p>We use the latest skin therapies for optimal, visible results.</p>
                        </div>
                        <div class="value-item">
                            <div class="value-icon">
                                <i class="fas fa-spa"></i>
                            </div>
                            <h3>Relax & Renew</h3>
                            <p>Enjoy therapeutic skincare experiences that refresh your mind and body.</p>
                        </div>
                    </div>

                </div>
                <div class="about-image">
                    <img src="./images/skincare-side.png" alt="DermaCare Clinic Building">
                </div>
            </div>
        </div>
    </section>




    <?php
    require "./src/footer.php";
    ?>
    <script src="js/script.js"></script>
</body>

</html>