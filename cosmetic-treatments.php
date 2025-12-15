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
        url(./images/Cosmetic-banner.png);
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
            <h1 class="animate__animated animate__backInDown">Cosmetic Aesthetic Treatments</h1>
        <p class="animate__animated animate__backInDown" style="font-size: 1.2rem; font-style: italic;">"Refine Your Beauty. Restore Your Confidence."</p>
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
    At Derma Elixir Clinic, our Cosmetic Aesthetic Treatments are designed to enhance your natural features while maintaining a balanced, youthful appearance. Whether you're looking for wrinkle reduction, lip enhancement, facial contouring, or skin tightening, our experts use advanced non-surgical techniques to deliver subtle yet stunning results. Every treatment plan is personalized to your facial structure and beauty goals, ensuring you look refreshed, radiant, and uniquely you—with results you’ll love and others will admire.
</p>

                  <div class="about-values">
    <div class="value-item">
        <div class="value-icon">
            <i class="fas fa-smile-beam"></i>
        </div>
        <h3>Youthful Appearance</h3>
        <p>Reduce fine lines and signs of aging for a revitalized look.</p>
    </div>
    <div class="value-item">
        <div class="value-icon">
            <i class="fas fa-pencil-ruler"></i>
        </div>
        <h3>Facial Contouring</h3>
        <p>Define your features with precise and artistic aesthetic techniques.</p>
    </div>
    <div class="value-item">
        <div class="value-icon">
           <i class="fas fa-user-check"></i>
        </div>
        <h3>Natural-Looking Results</h3>
        <p>Subtle enhancements that preserve and elevate your natural beauty.</p>
    </div>
</div>

                </div>
                <div class="about-image">
                    <img src="./images/Cosmetic-side.png" alt="DermaCare Clinic Building">
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