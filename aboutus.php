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

<body>

    <?php
    require "./src/header.php";
    ?>

    <section class="page-banner">
        <div class="container">
            <div class="banner-content">
                <h1 class="animate__animated animate__backInDown">DermaCare Clinic</h1>
                <p class="animate__animated animate__backInDown" style="font-size: 1.2rem; font-style: italic;">"Bringing Confidence Back – One Treatment at a Time."
                </p>
            </div>
        </div>
    </section>

    <section class="about-intro">
        <div class="container">
            <div class="about-grid">
                <div class="about-content">
                    <div class="section-header" style="text-align: left;">
                        <h2>About us</h2>
                    </div>
                    <p>Derma Elixir Studio is more than just a skincare clinic — it's a welfare initiative dedicated to
                        bringing confidence and care to those in need. Located in the heart of Islamabad, our clinic
                        offers free dermatological services to underprivileged individuals across Pakistan. With a
                        commitment to compassionate treatment, we provide expert care from licensed skin specialists,
                        advanced skin analysis, and personalized treatment plans. Every patient’s journey is supported
                        with proper medical history tracking, transparent processes, and a streamlined appointment
                        system through our online portal. At Derma Elixir Studio, we believe healthy skin is a right,
                        not a privilege.</p>
                    <div class="about-values">
                        <div class="value-item">
                            <div class="value-icon">
                                <i class="fas fa-spa"></i>
                            </div>
                            <h3>Skin Wellness</h3>
                            <p>Dedicated to healthy, radiant skin with personalized care for every patient.</p>
                        </div>
                        <div class="value-item">
                            <div class="value-icon">
                                <i class="fas fa-stethoscope"></i>
                            </div>
                            <h3>Trusted Dermatologists</h3>
                            <p>Qualified skin specialists delivering expert treatment with compassion.</p>
                        </div>
                        <div class="value-item">
                            <div class="value-icon">
                                <i class="fas fa-hand-holding-medical"></i>
                            </div>
                            <h3>Free Skincare Support</h3>
                            <p>Free skincare services under our welfare program.</p>
                        </div>
                    </div>

                </div>
                <div class="about-image">
                    <img src="./images/aboutus-side.png" alt="DermaCare Clinic Building">
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