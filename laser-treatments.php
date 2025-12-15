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
        url(./images/laser-banner.png);
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
            <h1 class="animate__animated animate__backInDown">Laser Treatments</h1>
<p class="animate__animated animate__backInDown" style="font-size: 1.2rem; font-style: italic;">"Precision. Power. Perfection in Every Pulse."</p>
        </div>
    </section>



    <section class="about-intro">
        <div class="container">
            <div class="about-grid">
                <div class="about-content">
                    <div class="section-header" style="text-align: left;">
                        <h2>Why Choose Us?</h2>
                    </div>
                    <p>
    At Derma Elixir Clinic, our Laser Treatments are designed to deliver precise and powerful results for a variety of skin and hair concerns. From laser hair removal and acne scar reduction to pigmentation correction and skin resurfacing, we use FDA-approved laser technologies that are both safe and effective. Our highly trained specialists customize every treatment based on your skin type and condition, ensuring minimal discomfort and maximum outcomes. Experience smoother, clearer, and rejuvenated skin with our state-of-the-art laser solutions.
</p>

                  
                    <div class="about-values">
    <div class="value-item">
        <div class="value-icon">
            <i class="fas fa-bolt"></i>
        </div>
        <h3>Precision Targeting</h3>
        <p>Delivers accurate results with minimal impact on surrounding skin.</p>
    </div>
    <div class="value-item">
        <div class="value-icon">
            <i class="fas fa-wand-magic-sparkles"></i>
        </div>
        <h3>Advanced Technology</h3>
        <p>We use FDA-approved laser systems for safety and performance.</p>
    </div>
    <div class="value-item">
        <div class="value-icon">
            <i class="fas fa-sun"></i>
        </div>
        <h3>Radiant Results</h3>
        <p>Achieve visibly clearer and younger-looking skin with every session.</p>
    </div>
</div>

                </div>
                <div class="about-image">
                    <img src="./images/laser-side.png" alt="DermaCare Clinic Building">
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