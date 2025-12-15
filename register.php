<?php
// Include config file
require_once "config.php";

// Define variables and initialize with empty values
$first_name = $last_name = $username = $password = $confirm_password = "";
$email = $mobile = $cnic = $state = $city = $user_type = "";
$certificate = $experience = $specialization = "";

$errors = array();

// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate first name
    if (empty(trim($_POST["first_name"]))) {
        $errors[] = "Please enter first name.";
    } else {
        $first_name = trim($_POST["first_name"]);
    }
    
    // Validate last name
    if (empty(trim($_POST["last_name"]))) {
        $errors[] = "Please enter last name.";
    } else {
        $last_name = trim($_POST["last_name"]);
    }
    
    // Validate username
    if (empty(trim($_POST["username"]))) {
        $errors[] = "Please enter a username.";
    } else {
        // Prepare a select statement
        $sql = "SELECT id FROM users WHERE username = ?";
        
        if ($stmt = mysqli_prepare($conn, $sql)) {
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "s", $param_username);
            
            // Set parameters
            $param_username = trim($_POST["username"]);
            
            // Attempt to execute the prepared statement
            if (mysqli_stmt_execute($stmt)) {
                /* store result */
                mysqli_stmt_store_result($stmt);
                
                if (mysqli_stmt_num_rows($stmt) == 1) {
                    $errors[] = "This username is already taken.";
                } else {
                    $username = trim($_POST["username"]);
                }
            } else {
                $errors[] = "Oops! Something went wrong. Please try again later.";
            }
            
            // Close statement
            mysqli_stmt_close($stmt);
        }
    }
    
    // Validate password
    if (empty(trim($_POST["password"]))) {
        $errors[] = "Please enter a password.";     
    } elseif (strlen(trim($_POST["password"])) < 6) {
        $errors[] = "Password must have at least 6 characters.";
    } else {
        $password = trim($_POST["password"]);
    }
    
    // Validate confirm password
    if (empty(trim($_POST["confirm_password"]))) {
        $errors[] = "Please confirm password.";     
    } else {
        $confirm_password = trim($_POST["confirm_password"]);
        if (empty($errors) && ($password != $confirm_password)) {
            $errors[] = "Password did not match.";
        }
    }
    
    // Validate email
    if (empty(trim($_POST["email"]))) {
        $errors[] = "Please enter an email.";
    } elseif (!filter_var(trim($_POST["email"]), FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    } else {
        // Check if email exists
        $sql = "SELECT id FROM users WHERE email = ?";
        
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $param_email);
            $param_email = trim($_POST["email"]);
            
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);
                
                if (mysqli_stmt_num_rows($stmt) == 1) {
                    $errors[] = "This email is already taken.";
                } else {
                    $email = trim($_POST["email"]);
                }
            } else {
                $errors[] = "Oops! Something went wrong. Please try again later.";
            }
            
            mysqli_stmt_close($stmt);
        }
    }
    
    // Validate mobile
    if (empty(trim($_POST["mobile"]))) {
        $errors[] = "Please enter a mobile number.";
    } elseif (!preg_match('/^\+?[0-9]{10,15}$/', str_replace(' ', '', trim($_POST["mobile"])))) {
        $errors[] = "Please enter a valid mobile number.";
    } else {
        $mobile = trim($_POST["mobile"]);
    }
    // Validate CNIC
    if (empty(trim($_POST["cnic"]))) {
        $errors[] = "Please enter CNIC number.";
    } elseif (!preg_match('/^\d{5}-\d{7}-\d{1}$/', trim($_POST["cnic"]))) {
        $errors[] = "Please enter a valid CNIC (format: 12345-1234567-1).";
    } else {
        $cnic = trim($_POST["cnic"]);
    }
    
    // Validate state
    if (empty(trim($_POST["state"]))) {
        $errors[] = "Please enter state.";
    } else {
        $state = trim($_POST["state"]);
    }
    
    // Validate city
    if (empty(trim($_POST["city"]))) {
        $errors[] = "Please enter city.";
    } else {
        $city = trim($_POST["city"]);
    }
    
    // Validate user type
    if (empty(trim($_POST["user_type"]))) {
        $errors[] = "Please select user type.";
    } else {
        $user_type = trim($_POST["user_type"]);
        
        // Additional validation based on user type
        if ($user_type == "patient") {
            // Handle certificate upload
            if (empty($_FILES["certificate"]["name"])) {
                $errors[] = "Please upload a certificate.";
            } else {
                $target_dir = "uploads/certificates/";
                $file_extension = pathinfo($_FILES["certificate"]["name"], PATHINFO_EXTENSION);
                $new_filename = uniqid() . "." . $file_extension;
                $target_file = $target_dir . $new_filename;
                
                // Check file type
                $allowed_types = array("jpg", "jpeg", "png", "pdf");
                if (!in_array(strtolower($file_extension), $allowed_types)) {
                    $errors[] = "Only JPG, JPEG, PNG, and PDF files are allowed.";
                }
                
                // Check file size (5MB max)
                if ($_FILES["certificate"]["size"] > 5000000) {
                    $errors[] = "File is too large. Maximum size is 5MB.";
                }
                
                // Upload file if no errors
                if (move_uploaded_file($_FILES["certificate"]["tmp_name"], $target_file)) {
                    $certificate = $new_filename;
                } else {
                    $errors[] = "There was an error uploading your file.";
                }
            }
        } elseif ($user_type == "specialist") {
            // Validate experience
            if (empty(trim($_POST["experience"]))) {
                $errors[] = "Please enter experience in years.";
            } elseif (!is_numeric(trim($_POST["experience"]))) {
                $errors[] = "Experience must be a number.";
            } else {
                $experience = (int)trim($_POST["experience"]);
            }
            
            // Validate specialization
            if (empty(trim($_POST["specialization"]))) {
                $errors[] = "Please select a specialization.";
            } else {
                $specialization = trim($_POST["specialization"]);
                
                // Check if specialization is valid
                $allowed_specializations = array("skin_care", "laser_specialist", "cosmetic_specialist", "hair_specialist");
                if (!in_array($specialization, $allowed_specializations)) {
                    $errors[] = "Invalid specialization selected.";
                }
            }
        }
    }
    
    // Check if there are no errors
    if (empty($errors)) {
        // Register the user
        $result = register_user($first_name, $last_name, $username, $password, $email, $mobile, $cnic, $state, $city, $user_type, $certificate, $experience, $specialization);
        
        if ($result['success']) {
            // Set success message and redirect to login page
            $_SESSION['success_msg'] = $result['message'];
            header("location: login.php");
            exit;
        } else {
            $errors[] = $result['message'];
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
        }
        .wrapper {
            width: 100%;
            max-width: 900px;
            margin: 30px auto;
            padding: 30px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }
        
        .form-group {
            margin-bottom: 20px;
            position: relative;
        }
        
        .form-control {
            border-radius: 5px;
            height: auto;
            padding: 10px 15px;
            padding-left: 40px;
            border: 1px solid #e0e0e0;
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
            font-size: 16px;
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

        
        .section-title {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--secondary-color);
        }
        
        .specialization-group, .certificate-group {
            display: none;
            padding: 15px;
            background-color: rgba(46, 204, 113, 0.05);
            border-radius: 8px;
            margin-top: 10px;
            border-left: 4px solid var(--secondary-color);
        }
        
        .nav-tabs {
            border-bottom: 2px solid var(--primary-color);
            margin-bottom: 25px;
        }
        
        .nav-tabs .nav-link {
            border: none;
            color: var(--text-color);
            font-weight: 600;
            padding: 10px 20px;
        }
        
        .nav-tabs .nav-link.active {
            color: var(--primary-color);
            background-color: transparent;
            border-bottom: 3px solid var(--primary-color);
        }
        
        .form-section {
            display: none;
            animation: fadeIn 0.5s;
        }
        
        .form-section.active {
            display: block;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
     
        .form-navigation {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }
        
        .form-control-file {
            padding: 8px 0;
            padding-left: 40px;
        }
        
        .login-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .login-link a {
            color: var(--accent-color);
            text-decoration: none;
            font-weight: 600;
        }
        
        .login-link a:hover {
            text-decoration: underline;
        }
        
        .progress-container {
            margin-bottom: 25px;
        }
        
        .progress {
            height: 8px;
            background-color: #e0e0e0;
        }
        
        .progress-bar {
            background-color: var(--secondary-color);
        }

        /* Special handling for file input fields */
        .file-input-container {
            position: relative;
        }

        .file-input-container .input-icon {
            top: 10px;
        }
    </style>
</head>

<?php
require "./src/header.php";
?>
<body>
    <div class="main-container">
    <div class="wrapper">
        <h3 class="text-center mb-3">Create Your Account</h3>
        <p class="text-center mb-4">Join our community for better dermatological care</p>
        
        <?php
        if (!empty($errors)) {
            echo '<div class="alert alert-danger">';
            foreach ($errors as $error) {
                echo '<p class="mb-0"><i class="fas fa-exclamation-circle mr-2"></i>' . $error . '</p>';
            }
            echo '</div>';
        }
        ?>

        <div class="progress-container">
            <div class="progress">
                <div class="progress-bar" role="progressbar" style="width: 0%;" id="progress-bar"></div>
            </div>
        </div>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post"
            enctype="multipart/form-data">
            <ul class="nav nav-tabs" id="formTabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="personal-tab" data-toggle="tab" href="#personal" role="tab">Personal
                        Info</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="account-tab" data-toggle="tab" href="#account" role="tab">Account</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="profile-tab" data-toggle="tab" href="#profile" role="tab">Profile Type</a>
                </li>
            </ul>

            <div class="tab-content">
                <!-- Personal Information Section -->
                <div class="tab-pane fade show active form-section" id="personal" role="tabpanel">
                    <h4 class="section-title">Personal Information</h4>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="first_name">First Name</label>
                                <i class="fas fa-user input-icon"></i>
                                <input type="text" id="first_name" name="first_name" class="form-control"
                                    value="<?php echo $first_name; ?>" placeholder="Enter your first name">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="last_name">Last Name</label>
                                <i class="fas fa-user input-icon"></i>
                                <input type="text" id="last_name" name="last_name" class="form-control"
                                    value="<?php echo $last_name; ?>" placeholder="Enter your last name">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="mobile">Mobile Number</label>
                                <i class="fas fa-mobile-alt input-icon"></i>
                                <input type="text" id="mobile" name="mobile" class="form-control" value="<?php echo $mobile; ?>"
                                    placeholder="Enter your mobile number">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="cnic">CNIC</label>
                                <i class="fas fa-id-card input-icon"></i>
                                <input type="text" id="cnic" name="cnic" class="form-control" value="<?php echo $cnic; ?>"
                                    placeholder="Enter your CNIC">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="state">State</label>
                                <i class="fas fa-map-marker-alt input-icon"></i>
                                <input type="text" id="state" name="state" class="form-control" value="<?php echo $state; ?>"
                                    placeholder="Enter your state">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="city">City</label>
                                <i class="fas fa-city input-icon"></i>
                                <input type="text" id="city" name="city" class="form-control" value="<?php echo $city; ?>"
                                    placeholder="Enter your city">
                            </div>
                        </div>
                    </div>

                    <div class="form-navigation">
                        <div></div>
                        <button type="button" class="btn btn-primary next-btn" data-next="account">Next <i
                                class="fas fa-arrow-right ml-2"></i></button>
                    </div>
                </div>

                <!-- Account Information Section -->
                <div class="tab-pane fade form-section" id="account" role="tabpanel">
                    <h4 class="section-title">Account Information</h4>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="username">Username</label>
                                <i class="fas fa-user-circle input-icon"></i>
                                <input type="text" id="username" name="username" class="form-control" value="<?php echo $username; ?>"
                                    placeholder="Choose a username">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="email">Email</label>
                                <i class="fas fa-envelope input-icon"></i>
                                <input type="email" id="email" name="email" class="form-control" value="<?php echo $email; ?>"
                                    placeholder="Enter your email">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="password">Password</label>
                                <i class="fas fa-lock input-icon"></i>
                                <input type="password" id="password" name="password" class="form-control"
                                    placeholder="Create a password">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="confirm_password">Confirm Password</label>
                                <i class="fas fa-lock input-icon"></i>
                                <input type="password" id="confirm_password" name="confirm_password" class="form-control"
                                    placeholder="Confirm your password">
                            </div>
                        </div>
                    </div>

                    <div class="form-navigation">
                        <button type="button" class="btn btn-secondary prev-btn" data-prev="personal"><i
                                class="fas fa-arrow-left mr-2"></i> Previous</button>
                        <button type="button" class="btn btn-primary next-btn" data-next="profile">Next <i
                                class="fas fa-arrow-right ml-2"></i></button>
                    </div>
                </div>

                <!-- Profile Type Section -->
                <div class="tab-pane fade form-section" id="profile" role="tabpanel">
                    <h4 class="section-title">Profile Type</h4>
                    <div class="form-group">
                        <label for="user-type">I am registering as</label>
                        <i class="fas fa-user-tag input-icon"></i>
                        <select name="user_type" class="form-control" id="user-type">
                            <option value="">Select User Type</option>
                            <option value="patient" <?php if ($user_type == "patient")
                                echo "selected"; ?>>Patient
                            </option>
                            <option value="specialist" <?php if ($user_type == "specialist")
                                echo "selected"; ?>>
                                Specialist</option>
                        </select>
                    </div>

                    <!-- Patient-specific field -->
                    <div id="certificate-group" class="form-group certificate-group file-input-container">
                        <label for="certificate">Medical Certificate</label>
                        <i class="fas fa-file-medical input-icon"></i>
                        <input type="file" id="certificate" name="certificate" class="form-control-file">
                        <small class="form-text text-muted"><i class="fas fa-info-circle mr-2"></i>Upload a medical
                            certificate. Only JPG, JPEG, PNG, and PDF files are allowed (max 5MB).</small>
                    </div>

                    <!-- Specialist-specific fields -->
                    <div id="specialization-group" class="specialization-group">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="experience">Experience (in years)</label>
                                    <i class="fas fa-briefcase input-icon"></i>
                                    <input type="number" id="experience" name="experience" class="form-control"
                                        value="<?php echo $experience; ?>" placeholder="Years of experience">
                                </div>
                            </div>
                             <div class="col-md-6">
                                <div class="form-group">
                                    <label for="specialization">Specialization</label>
                                    <i class="fas fa-stethoscope input-icon"></i>
                                    <select id="specialization" name="specialization" class="form-control">
                                        <option value="">Select Specialization</option>
                                        <option value="skin_care" <?php if ($specialization == "skin_care")
                                            echo "selected"; ?>>Skin Care</option>
                                        <option value="laser_specialist" <?php if ($specialization == "laser_specialist")
                                            echo "selected"; ?>>
                                            Laser</option>
                                        <option value="cosmetic_specialist" <?php if ($specialization == "cosmetic_specialist")
                                            echo "selected"; ?>>Cosmetic</option>
                                        <option value="hair_specialist" <?php if ($specialization == "hair_specialist")
                                            echo "selected"; ?>>Hair
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-navigation">
                        <button type="button" class="btn btn-secondary prev-btn" data-prev="account"><i
                                class="fas fa-arrow-left mr-2"></i> Previous</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-user-plus mr-2"></i>
                            Register</button>
                    </div>
                </div>
            </div>
        </form>

        <div class="login-link">
            <p>Already have an account? <a href="login.php">Sign in here</a></p>
        </div>
    </div>
</div>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <script>
        $(document).ready(function () {
            // Show/hide fields based on user type
            $('#user-type').change(function () {
                var selectedType = $(this).val();

                if (selectedType === 'patient') {
                    $('#certificate-group').show();
                    $('#specialization-group').hide();
                } else if (selectedType === 'specialist') {
                    $('#certificate-group').hide();
                    $('#specialization-group').show();
                } else {
                    $('#certificate-group').hide();
                    $('#specialization-group').hide();
                }
            });

            // Trigger the change event on page load
            $('#user-type').trigger('change');

            // Multi-step form navigation
            $('.next-btn').click(function () {
                var nextTab = $(this).data('next');
                $('#' + nextTab + '-tab').tab('show');
                updateProgress();
            });

            $('.prev-btn').click(function () {
                var prevTab = $(this).data('prev');
                $('#' + prevTab + '-tab').tab('show');
                updateProgress();
            });

            // Update progress bar
            function updateProgress() {
                var activeTab = $('.nav-link.active').attr('id');
                var progress = 0;

                if (activeTab === 'personal-tab') {
                    progress = 0;
                } else if (activeTab === 'account-tab') {
                    progress = 50;
                } else if (activeTab === 'profile-tab') {
                    progress = 100;
                }

                $('#progress-bar').css('width', progress + '%');
            }

            // Initialize progress bar
            updateProgress();

            // Make tabs clickable
            $('#formTabs a').click(function (e) {
                e.preventDefault();
                $(this).tab('show');
                updateProgress();
            });
        });
    </script>


<?php
require "./src/footer.php";
?>
</body>

</html>