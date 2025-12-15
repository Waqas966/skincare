<?php
// Include config file
require_once "config.php";

// Start session
start_session();

// Check if the user is already logged in, if yes then redirect to welcome page
/*if (is_logged_in()) {
    header("location: index.php");
    exit;
}*/

// Define variables and initialize with empty values
$username = $password = "";
$error_msg = "";

// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if username is empty
    if (empty(trim($_POST["username"]))) {
        $error_msg = "Please enter username.";
    } else {
        $username = trim($_POST["username"]);
    }

    // Check if password is empty
    if (empty(trim($_POST["password"]))) {
        $error_msg = "Please enter your password.";
    } else {
        $password = trim($_POST["password"]);
    }

    // Validate credentials
    if (empty($error_msg)) {
        // Attempt to log in
        $login_result = user_login($username, $password);

        if ($login_result['success']) {
            // Redirect user based on their type
            $redirect_url = "index.php";

            if (is_admin()) {
                $redirect_url = "admin/dashboard.php";
            } elseif (is_specialist()) {
                $redirect_url = "specialist/dashboard.php";
            } elseif (is_patient()) {
                $redirect_url = "patient/dashboard.php";
            }

            header("location: " . $redirect_url);
            exit;
        } else {
            $error_msg = $login_result['message'];
        }
    }
}
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
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <style>
        :root {
            --primary-color: #4a90e2;
            --secondary-color: #6c63ff;
            --accent-color: #9b59b6;
            --text-color: #34495e;
            --light-bg: #f5f8fa;
        }
        
       body {
    font-family: 'Poppins', sans-serif;
    line-height: 1.6;
    color: var(--text-color);
    background-color: #fff;
}

h1,
h2,
h3,
h4,
h5,
h6 {
    color: var(--heading-color);
    font-weight: 600;
    line-height: 1.3;
    margin-bottom: 0.5rem;
}

.logo h1 {
    font-size: 1.8rem;
    margin-bottom: 0;
    color: var(--primary-color);
}

.logo p {
    font-size: 0.9rem;
    margin-bottom: 0;
    color: var(--text-color);
}
p {
    margin-bottom: 1rem;
}

a {
    text-decoration: none;
    color: var(--primary-color);
    transition: var(--transition);
}

a:hover {
    color: var(--secondary-color);
}

ul {
    list-style: none;
}

img {
    max-width: 100%;
    height: auto;
}
      
        
        /* Main content area */
        .main-container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 15px 0;
             background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)),
                url(./images/register.png);
            background-size: cover;
            background-position: center;
        }
        
        .wrapper {
            width: 100%;
            max-width: 450px;
            padding: 35px;
            background:var(--light-color);
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }
  
        /* Footer area */
        .footer-container {
            width: 100%;
            background-color: #fff;
            padding: 15px 0;
            text-align: center;
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
        }
        
        .footer-links a {
            margin: 0 10px;
            color: var(--text-color);
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .footer-links a:hover {
            color: var(--primary-color);
        }
        
        .copyright {
            color: #95a5a6;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 25px;
            position: relative;
        }
        
        .form-control {
            border-radius: 5px;
            height: 100%;
            padding: 12px 15px;
            padding-left: 45px;
            border: 1px solid #e0e0e0;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }
        
        .form-group i.input-icon {
            position: absolute;
            left: 15px;
            top: 45px;
            color: var(--primary-color);
            font-size: 18px;
        }
        
        /* Buttons */
.btn {
    display: inline-block;
    padding: 10px 24px;
    font-size: 1rem;
    font-weight: 500;
    text-align: center;
    border-radius: var(--border-radius);
    cursor: pointer;
    transition: var(--transition);
    text-transform: capitalize;
}
            .btn-primary {
           background-color: transparent;
    color: var(--primary-color);
    border: 2px solid var(--primary-color);
        }
        
        .btn-primary:hover {
           background-color: var(--primary-color);
    color: white;
        }

        .btn-primary:focus {
            box-shadow: 0 0 0 0.25rem rgba(52, 152, 219, 0.5);
        }


        
        h3 {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        label {
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 8px;
        }
        
        .register-link {
            text-align: center;
            margin-top: 25px;
        }
        
        .register-link a {
            color: var(--accent-color);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .register-link a:hover {
            text-decoration: underline;
        }
        
        .alert {
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 25px;
            border-left: 4px solid #dc3545;
        }
        
        .forget-password {
            text-align: right;
            margin-top: -15px;
            margin-bottom: 20px;
        }
        
        .forget-password a {
            color: var(--accent-color);
            font-size: 14px;
            text-decoration: none;
        }
        
        .forget-password a:hover {
            text-decoration: underline;
        }
        
        .form-divider {
            display: flex;
            align-items: center;
            margin: 25px 0;
        }
        
        .form-divider hr {
            flex: 1;
            border-top: 1px solid #e0e0e0;
        }
        
        .form-divider span {
            padding: 0 15px;
            color: #95a5a6;
            font-size: 14px;
        }
        
        .social-login {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .social-login button {
            padding: 10px 15px;
            border-radius: 5px;
            border: 1px solid #e0e0e0;
            background-color: #fff;
            transition: all 0.3s ease;
        }
        
        .social-login button:hover {
            background-color: #f5f5f5;
            transform: translateY(-2px);
        }
        
        .social-login i {
            font-size: 18px;
        }
        
        .social-login .google i {
            color: #db4437;
        }
        
        .social-login .facebook i {
            color: #4267B2;
        }
    </style>
</head>
<body>
    <!-- Header container (for future use) -->
<?php
require "./src/header.php";
?>

    <!-- Main content container -->
    <div class="main-container">
        <div class="wrapper">
            <h3 class="text-center">Welcome Back</h3>
            
            <?php
            // Display any error message
            if (!empty($error_msg)) {
                echo '<div class="alert alert-danger"><i class="fas fa-exclamation-circle mr-2"></i>' . $error_msg . '</div>';
            }

            // Display success/error messages
            echo display_message();
            ?>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="form-group">
                    <label for="username">Username</label>
                    <i class="fas fa-user input-icon"></i>
                    <input type="text" id="username" name="username" class="form-control"
                        value="<?php echo $username; ?>" placeholder="Enter your username">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" id="password" name="password" class="form-control"
                        placeholder="Enter your password">
                </div>

                <div class="forget-password">
                    <a href="#">Forgot password?</a>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-sign-in-alt mr-2"></i> Sign In
                    </button>
                </div>
            </form>

            <div class="form-divider">
                <hr>
                <span>OR</span>
                <hr>
            </div>

            <div class="social-login">
                <button class="google">
                    <i class="fab fa-google"></i>
                </button>
                <button class="facebook">
                    <i class="fab fa-facebook-f"></i>
                </button>
            </div>

            <div class="register-link">
                <p>Don't have an account? <a href="register.php">Register here</a></p>
            </div>
        </div>
    </div>

    <!-- Footer container -->
<?php
require "./src/footer.php";
?>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>

</html>