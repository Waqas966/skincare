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
        url(./images/Hair-banner.png);
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
            <h1 class="animate__animated animate__backInDown">Hair Treatments</h1>
     <p class="animate__animated animate__backInDown" style="font-size: 1.2rem; font-style: italic;">"Revive. Restore. Reveal Your Best Hair."</p>

        </div>
    </section>



    <section class="about-intro">
        <div class="container">
            <div class="about-grid">
                <div class="about-content">
                    <div class="section-header" style="text-align: left;">
                        <h2>Why Choose Us?</h2>
                    </div>
                    <p>Derma Elixir Clinic is committed to providing excellence in hair and scalp care through cutting-edge
                        treatments and compassionate care. Our multidisciplinary team works closely with patients to
                        develop customized treatment plans with measurable results.</p>
                        <p>At Derma Elixir Clinic, we specialize in offering innovative and personalized hair treatment solutions
                designed to restore the health and beauty of your hair. Whether you're facing hair thinning, pattern
                baldness, dandruff, or scalp disorders, our board-certified dermatologists and trichologists use
                scientifically-proven methods and advanced technologies to provide real, lasting results.</p>
                    <div class="about-values">
                        <div class="value-item">
                            <div class="value-icon">
                                <i class="fas fa-heartbeat"></i>
                            </div>
                            <h3>Patient-Centered Care</h3>
                            <p>Your comfort and satisfaction are our top priorities.</p>
                        </div>
                        <div class="value-item">
                            <div class="value-icon">
                                <i class="fas fa-flask"></i>
                            </div>
                            <h3>Scientific Excellence</h3>
                            <p>We stay at the forefront of dermatological research.</p>
                        </div>
                        <div class="value-item">
                            <div class="value-icon">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <h3>Safety & Quality</h3>
                            <p>We maintain the highest standards in all our treatments.</p>
                        </div>
                    </div>
                </div>
                <div class="about-image">
                    <img src="./images/Hair-side.png" alt="DermaCare Clinic Building" height="50px">
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