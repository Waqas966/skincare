<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <header>
    <div class="container">
        <div class="logo">
            <h1>DermaCare</h1>
            <p>Advanced Skin & Hair Clinic</p>
        </div>
        <nav>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="aboutus.php">About Us</a></li>
                <li class="dropdown">
                    <a href="javascript:void(0)">Treatments <i class="fas fa-chevron-down"></i></a>
                    <div class="dropdown-content">
                        <a href="skin-treatments.php">Skin Care Treatments</a>
                        <a href="laser-treatments.php">Laser Treatments</a>
                        <a href="cosmetic-treatments.php">Cosmetic Aesthetic Treatments</a>
                        <a href="hair-treatments.php">Hair Treatments</a>
                    </div>
                </li>
                <li><a href="lab-tests.php">Lab Tests</a></li>
                <li><a href="contactus.php">Contact Us</a></li>
                <li><a href="register.php" class="btn btn-outline">Register</a></li>
                <li><a href="login.php" class="btn btn-primary">Login</a></li>
            </ul>
        </nav>
        <div class="mobile-menu-btn">
            <i class="fas fa-bars"></i>
        </div>
    </div>
</header>

  <script>
        // Mobile menu toggle
        document.querySelector('.mobile-menu-btn').addEventListener('click', function() {
            document.querySelector('.nav-menu').classList.toggle('active');
        });

    </script>
<script src="js/script.js"></script>
</body>
</html>