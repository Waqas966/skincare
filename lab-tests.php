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
                url(./images/lab-test.png);
    background-size: cover;
    background-position: center;
    color: white;
    padding: 15%;
    text-align: center;
}

        /* Additional styles for lab tests page */
        .lab-tests-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 50px;
        }

        .lab-test-card {
background: linear-gradient(135deg, #d6e4ff, #bceeff);
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            padding: 30px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: 2px solid transparent;
        box-sizing: border-box;
        }

        .lab-test-card:hover {
            transform: translateY(-5px);
             border: 2px dotted #4a90e2;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
            background: linear-gradient(135deg, #b0d0ff, #9ce2f5);

        }

        .lab-test-icon {
            font-size: 40px;
            color: #4a90e2;
            margin-bottom: 20px;
        }

        .lab-test-card h3 {
            margin-bottom: 15px;
            color: #333;
        }

        .lab-test-card ul {
            list-style-type: none;
            padding: 0;
            margin: 15px 0;
        }

        .lab-test-card ul li {
            position: relative;
            padding-left: 25px;
            margin-bottom: 10px;
            color: #555;
        }

        .lab-test-card ul li:before {
            content: "\f058";
            font-family: "Font Awesome 5 Free";
            font-weight: 900;
            position: absolute;
            left: 0;
            color: #4a90e2;
        }

        .lab-test-card p {
            margin-bottom: 20px;
            color: #666;
        }

        .lab-test-price {
            font-weight: bold;
            color: #4a90e2;
            margin-top: 10px;
            font-size: 18px;
        }

        .process-steps {
            display: flex;
            justify-content: space-between;
            margin: 60px 0;
            flex-wrap: wrap;
        }

        .process-step {
            flex: 1;
            min-width: 200px;
            text-align: center;
            padding: 0 20px;
            position: relative;
            margin-bottom: 30px;
        }

        .process-step:not(:last-child):after {
            content: "";
            position: absolute;
            top: 40px;
            right: -10px;
            width: 20px;
            height: 20px;
            border-top: 2px dashed #4e9cb5;
            border-right: 2px dashed #4e9cb5;
            transform: rotate(45deg);
        }

        .step-number {
            width: 60px;
            height: 60px;
            background-color: #4a90e2;
            color: #fff;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0 auto 20px;
            font-size: 24px;
            font-weight: bold;
        }

        .faq-section {
            margin-top: 60px;
        }

        .faq-item {
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
            padding-bottom: 20px;
        }

        .faq-question {
            font-weight: bold;
            color: #333;
            cursor: pointer;
            position: relative;
            padding-right: 30px;
        }

        .faq-question:after {
            content: "\f107";
            font-family: "Font Awesome 5 Free";
            font-weight: 900;
            position: absolute;
            right: 0;
            transition: transform 0.3s ease;
        }

        .faq-answer {
            margin-top: 10px;
            display: none;
            color: #666;
        }

        .faq-item.active .faq-question:after {
            transform: rotate(180deg);
        }

        .faq-item.active .faq-answer {
            display: block;
        }

        .test-info{
            margin-top: 24px;
        }
    </style>
</head>

<body>

    <?php
    require "./src/header.php";
    ?>

    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <h1 class="animate__animated animate__backInDown">Advanced Laboratory Tests</h1>
                <p class="animate__animated animate__backInDown" style="font-size: 1.2rem; font-style: italic;">Comprehensive diagnostics for skin, hair, and hormonal health</p>
            </div>
        </div>
    </section>

    <section id="test-info" class="test-info">
        <div class="container">
            <div class="section-header">
                <h2>Why Choose Our Laboratory Tests</h2>
                <p>State-of-the-art diagnostics for personalized treatment plans</p>
            </div>
            <div class="process-steps">
                <div class="process-step">
                    <div class="step-number">1</div>
                    <h3>Consultation</h3>
                    <p>Meet with our specialists to discuss your concerns and determine appropriate tests</p>
                </div>
                <div class="process-step">
                    <div class="step-number">2</div>
                    <h3>Sample Collection</h3>
                    <p>Quick and comfortable sample collection by our trained professionals</p>
                </div>
                <div class="process-step">
                    <div class="step-number">3</div>
                    <h3>Laboratory Analysis</h3>
                    <p>Advanced testing using the latest technology and protocols</p>
                </div>
                <div class="process-step">
                    <div class="step-number">4</div>
                    <h3>Results & Treatment</h3>
                    <p>Detailed review of results with personalized treatment recommendations</p>
                </div>
            </div>
            <div class="lab-tests-grid">
                <div class="lab-test-card">
                    <div class="lab-test-icon">
                        <i class="fas fa-flask"></i>
                    </div>
                    <h3>Skin Health Profile</h3>
                    <p>Comprehensive skin analysis to identify underlying issues affecting your complexion.</p>
                    <ul>
                        <li>Sebum production analysis</li>
                        <li>Skin barrier function test</li>
                        <li>Microbiome assessment</li>
                        <li>Inflammatory markers</li>
                    </ul>
                    <a href="register.php" class="btn-text">Book Now <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="lab-test-card">
                    <div class="lab-test-icon">
                        <i class="fas fa-vial"></i>
                    </div>
                    <h3>Hormonal Analysis</h3>
                    <p>Evaluate hormone levels that can impact skin health, hair growth, and overall wellness.</p>
                    <ul>
                        <li>Thyroid function tests</li>
                        <li>Sex hormone panel</li>
                        <li>Adrenal function assessment</li>
                        <li>Stress hormone evaluation</li>
                    </ul>
                    <a href="register.php" class="btn-text">Book Now <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="lab-test-card">
                    <div class="lab-test-icon">
                        <i class="fas fa-microscope"></i>
                    </div>
                    <h3>Hair and Scalp Analysis</h3>
                    <p>Detailed evaluation of hair follicles and scalp health to address hair loss and scalp conditions.
                    </p>
                    <ul>
                        <li>Follicular microscopy</li>
                        <li>Scalp pH measurement</li>
                        <li>Hair mineral analysis</li>
                        <li>Scalp microbiome assessment</li>
                    </ul>
                    <a href="register.php" class="btn-text">Book Now <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="lab-test-card">
                    <div class="lab-test-icon">
                        <i class="fas fa-allergies"></i>
                    </div>
                    <h3>Allergy and Sensitivity Testing</h3>
                    <p>Identify potential allergens and sensitivities that may be affecting your skin.</p>
                    <ul>
                        <li>Contact dermatitis panel</li>
                        <li>Food sensitivity assessment</li>
                        <li>Environmental allergen testing</li>
                        <li>Product sensitivity analysis</li>
                    </ul>
                    <a href="register.php" class="btn-text">Book Now <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="lab-test-card">
                    <div class="lab-test-icon">
                        <i class="fas fa-dna"></i>
                    </div>
                    <h3>Genetic Skin Analysis</h3>
                    <p>Advanced genetic testing to understand your skin's unique characteristics and predispositions.
                    </p>
                    <ul>
                        <li>Collagen synthesis markers</li>
                        <li>Pigmentation gene analysis</li>
                        <li>Skin aging predictors</li>
                        <li>Personalized treatment recommendations</li>
                    </ul>
                    <a href="register.php" class="btn-text">Book Now <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="lab-test-card">
                    <div class="lab-test-icon">
                        <i class="fas fa-tint"></i>
                    </div>
                    <h3>Nutritional Deficiency Panel</h3>
                    <p>Comprehensive analysis of vitamins, minerals, and nutrients essential for skin and hair health.
                    </p>
                    <ul>
                        <li>Vitamin analysis (A, C, D, E, K)</li>
                        <li>Mineral profile (Zinc, Iron, Selenium)</li>
                        <li>Fatty acid assessment</li>
                        <li>Antioxidant status evaluation</li>
                    </ul>
                    <a href="register.php" class="btn-text">Book Now <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>
        </div>
    </section>

    <section class="faq-section">
        <div class="container">
          
          
        </div>
    </section>



    <?php
    require "./src/footer.php";
    ?>

    <script src="js/script.js"></script>
    
</body>

</html>