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
    <style>
        .hero {
            background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)),
                url(./images/contactus.png);
            background-size: cover;
            background-position: center;
            color: white;
            padding: 15%;
            text-align: center;
        }

        .contact-section{
            background: #f5f7fa;
            margin-top: 10px;
        }
        /* Additional styles for contact page */
        .contact-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 40px;
            margin-top: 50px;
        }

        .contact-info {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            padding: 30px;
            background-color: #e6f2ff;
        }

        .contact-info h3 {
            color: #333;
            margin-bottom: 20px;
            font-size: 22px;
        }

        .info-item {
            margin-bottom: 25px;
            display: flex;
            align-items: flex-start;
        }

        .info-icon {
            font-size: 18px;
            color: #4a90e2;
            margin-right: 15px;
            width: 24px;
            text-align: center;
        }

        .info-content h4 {
            margin-bottom: 8px;
            color: #444;
        }

        .info-content p {
            color: #666;
            line-height: 1.5;
        }



        .contact-form {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #444;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }

        .form-control:focus {
            border-color: #4a90e2;
            outline: none;
        }

        textarea.form-control {
            min-height: 150px;
            resize: vertical;
        }


        .map-container {
            height: 450px;
            border-radius: 10px;
            overflow: hidden;
            margin: 50px auto;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .map-container iframe {
            width: 100%;
            height: 100%;
            border: 2px;
        }


        .notification {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 6px;
            display: none;
        }

        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            display: block;
        }

        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            display: block;
        }

        @media (max-width: 768px) {
            .contact-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

    <?php
    require "./src/header.php";

    // Initialize variables
    $name = $email = $phone = $subject = $message = "";
    $nameErr = $emailErr = $messageErr = "";
    $success = $error = "";

    // Form processing
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Validate name
        if (empty($_POST["name"])) {
            $nameErr = "Name is required";
        } else {
            $name = test_input($_POST["name"]);
        }

        // Validate email
        if (empty($_POST["email"])) {
            $emailErr = "Email is required";
        } else {
            $email = test_input($_POST["email"]);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $emailErr = "Invalid email format";
            }
        }

        // Get phone (optional)
        $phone = test_input($_POST["phone"]);

        // Get subject
        $subject = test_input($_POST["subject"]);

        // Validate message
        if (empty($_POST["message"])) {
            $messageErr = "Message is required";
        } else {
            $message = test_input($_POST["message"]);
        }

        // If no errors, process the form
        if (empty($nameErr) && empty($emailErr) && empty($messageErr)) {
            // In a real application, you would send an email here
            // For demonstration, we'll just set a success message
            $success = "Thank you for your message! We will get back to you shortly.";

            // Reset form fields
            $name = $email = $phone = $subject = $message = "";
        } else {
            $error = "Please fix the errors and try again.";
        }
    }

    // Helper function to sanitize input
    function test_input($data)
    {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }
    ?>

    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <h1 class="animate__animated animate__backInDown">Contact Us</h1>
                <p class="animate__animated animate__backInDown" style="font-size: 1.2rem; font-style: italic;">Get in touch with our team of skin care specialists
                </p>
            </div>
        </div>
    </section>

    <section class="contact-section">
        <div class="container">
            <div class="section-header">
                <h2>Get In Touch</h2>
                <p>We're here to answer your questions and provide support</p>
            </div>

            <div class="contact-container">
                <div class="contact-info">
                    <h3>Contact Information</h3>
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div class="info-content">
                            <h4>Our Location</h4>
                            <p>Derma Elixir Studio
<br> Islamabad</p>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-phone-alt"></i>
                        </div>
                        <div class="info-content">
                            <h4>Call Us</h4>
                            <p>Phone:+92 (348) 1234567</p>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="info-content">
                            <h4>Email Us</h4>
                            <p>appointments@dermaclinic.com<br>info@dermacare.com</p>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="info-content">
                            <h4>Hours</h4>
                            <p>Monday - Friday: 9AM - 6PM<br>Saturday: 9AM - 6PM</p>
                        </div>
                    </div>


                </div>

                <div class="contact-form">
                    <h3>Send Us a Message</h3>

                    <?php if (!empty($success)): ?>
                        <div class="notification success"><?php echo $success; ?></div>
                    <?php endif; ?>

                    <?php if (!empty($error)): ?>
                        <div class="notification error"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                        <div class="form-group">
                            <label for="name">Your Name *</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?php echo $name; ?>">
                            <span class="error"><?php echo $nameErr; ?></span>
                        </div>

                        <div class="form-group">
                            <label for="email">Your Email *</label>
                            <input type="email" class="form-control" id="email" name="email"
                                value="<?php echo $email; ?>">
                            <span class="error"><?php echo $emailErr; ?></span>
                        </div>

                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" class="form-control" id="phone" name="phone"
                                value="<?php echo $phone; ?>">
                        </div>

                        <div class="form-group">
                            <label for="subject">Subject</label>
                            <input type="text" class="form-control" id="subject" name="subject"
                                value="<?php echo $subject; ?>">
                        </div>

                        <div class="form-group">
                            <label for="message">Your Message *</label>
                            <textarea class="form-control" id="message" name="message"
                                rows="5"><?php echo $message; ?></textarea>
                            <span class="error"><?php echo $messageErr; ?></span>
                        </div>

                        <button type="submit" class="btn btn-primary">Send Message</button>
                    </form>
                </div>
            </div>

            <div class="map-container">
                <!-- Replace with your actual Google Maps embed code -->
                <iframe
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3022.215277297956!2d-73.98823492420364!3d40.74844097138558!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zNDDCsDQ0JzU0LjQiTiA3M8KwNTknMTIuOCJX!5e0!3m2!1sen!2sus!4v1615393406857!5m2!1sen!2sus"
                    style="border:0;" allowfullscreen="" loading="lazy"></iframe>
            </div>

        </div>
    </section>



    <?php
    require "./src/footer.php";
    ?>

    <script src="js/script.js"></script>
</body>

</html>