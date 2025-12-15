<?php
// Include config file
require_once "../config.php";

// Check if user is admin
require_admin();

// Define variables and initialize with empty values
$first_name = $last_name = $username = $password = $confirm_password = $email = $mobile = $cnic = $state = $city = $specialization = $experience = "";
$errors = [];

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate first name
    if (empty(trim($_POST["first_name"]))) {
        $errors["first_name"] = "Please enter first name.";
    } else {
        $first_name = trim($_POST["first_name"]);
    }

    // Validate last name
    if (empty(trim($_POST["last_name"]))) {
        $errors["last_name"] = "Please enter last name.";
    } else {
        $last_name = trim($_POST["last_name"]);
    }

    // Validate username
    if (empty(trim($_POST["username"]))) {
        $errors["username"] = "Please enter a username.";
    } else {
        // Check if username exists
        $sql = "SELECT id FROM users WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $param_username);
        $param_username = trim($_POST["username"]);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $errors["username"] = "This username is already taken.";
        } else {
            $username = trim($_POST["username"]);
        }
        $stmt->close();
    }

    // Validate password
    if (empty(trim($_POST["password"]))) {
        $errors["password"] = "Please enter a password.";
    } elseif (strlen(trim($_POST["password"])) < 6) {
        $errors["password"] = "Password must have at least 6 characters.";
    } else {
        $password = trim($_POST["password"]);
    }

    // Validate confirm password
    if (empty(trim($_POST["confirm_password"]))) {
        $errors["confirm_password"] = "Please confirm password.";
    } else {
        $confirm_password = trim($_POST["confirm_password"]);
        if ($password != $confirm_password) {
            $errors["confirm_password"] = "Passwords did not match.";
        }
    }

    // Validate email
    if (empty(trim($_POST["email"]))) {
        $errors["email"] = "Please enter an email.";
    } elseif (!filter_var(trim($_POST["email"]), FILTER_VALIDATE_EMAIL)) {
        $errors["email"] = "Please enter a valid email address.";
    } else {
        // Check if email exists
        $sql = "SELECT id FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $param_email);
        $param_email = trim($_POST["email"]);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $errors["email"] = "This email is already registered.";
        } else {
            $email = trim($_POST["email"]);
        }
        $stmt->close();
    }

    // Validate mobile
    if (empty(trim($_POST["mobile"]))) {
        $errors["mobile"] = "Please enter a mobile number.";
    } else {
        $mobile = trim($_POST["mobile"]);
    }

    // Validate CNIC
    if (empty(trim($_POST["cnic"]))) {
        $errors["cnic"] = "Please enter CNIC.";
    } else {
        $cnic = trim($_POST["cnic"]);
    }

    // Validate state
    if (empty(trim($_POST["state"]))) {
        $errors["state"] = "Please enter state.";
    } else {
        $state = trim($_POST["state"]);
    }

    // Validate city
    if (empty(trim($_POST["city"]))) {
        $errors["city"] = "Please enter city.";
    } else {
        $city = trim($_POST["city"]);
    }

    // Validate specialization
    if (empty(trim($_POST["specialization"]))) {
        $errors["specialization"] = "Please select a specialization.";
    } else {
        $specialization = trim($_POST["specialization"]);
    }

    // Validate experience
    if (empty(trim($_POST["experience"]))) {
        $errors["experience"] = "Please enter years of experience.";
    } elseif (!is_numeric(trim($_POST["experience"])) || intval(trim($_POST["experience"])) < 0) {
        $errors["experience"] = "Experience must be a positive number.";
    } else {
        $experience = intval(trim($_POST["experience"]));
    }

    // Check if there are no errors
    if (empty($errors)) {
        // Start transaction
        $conn->begin_transaction();

        try {
            // Insert into users table
            $sql = "INSERT INTO users (first_name, last_name, username, password, email, mobile, cnic, state, city, user_type, approval_status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'specialist', 'approved')";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssssss", $param_first_name, $param_last_name, $param_username, $param_password, $param_email, $param_mobile, $param_cnic, $param_state, $param_city);

            $param_first_name = $first_name;
            $param_last_name = $last_name;
            $param_username = $username;
            $param_password = password_hash($password, PASSWORD_DEFAULT); // Hash the password
            $param_email = $email;
            $param_mobile = $mobile;
            $param_cnic = $cnic;
            $param_state = $state;
            $param_city = $city;

            // Execute the statement
            $stmt->execute();

            // Get the last inserted user ID
            $user_id = $conn->insert_id;

            // Insert into specialists table
            $sql = "INSERT INTO specialists (user_id, experience, specialization) VALUES (?, ?, ?)";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iis", $param_user_id, $param_experience, $param_specialization);

            $param_user_id = $user_id;
            $param_experience = $experience;
            $param_specialization = $specialization;

            // Execute the statement
            $stmt->execute();

            // Commit transaction
            $conn->commit();

            // Set success message and redirect to manage specialists page
            $_SESSION['success_msg'] = "Specialist added successfully!";
            header("location: manage_specialists.php");
            exit();

        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $_SESSION['error_msg'] = "Error adding specialist: " . $e->getMessage();
        }
    }
}

// Function to format specialization for display
function format_specialization($specialization)
{
    switch ($specialization) {
        case 'skin_care':
            return 'Skin Care';
        case 'laser':
            return 'Laser Treatment';
        case 'cosmetic':
            return 'Cosmetic Treatment';
        case 'hair':
            return 'Hair Treatment';
        default:
            return ucfirst($specialization);
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Specialist - Derma Elixir Studio</title>
    <!-- Basic favicon -->
    <link rel="icon" href="../images/favicon.svg" sizes="32x32">
    <!-- SVG favicon -->
    <link rel="icon" href="../images/favicon.svg" type="image/svg+xml">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
            --success-color: #2ecc71;
            --danger-color: #e74c3c;
            --light-bg: #f8f9fa;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            padding-bottom: 20px;
        }

        /* Sidebar styling */
        #sidebar {
            min-height: 100vh;
            width: 300px;
            background-color: var(--secondary-color);
            color: white;
            transition: all 0.3s;
            box-shadow: 3px 0 10px rgba(0, 0, 0, 0.1);
            z-index: 999;
        }

          .dashboard-header {
            background-color: white;
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.05);
        }

        #sidebar .sidebar-header {
            padding: 20px;
            background-color: rgba(0, 0, 0, 0.1);
        }

        #sidebar ul.components {
            padding: 20px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        #sidebar ul p {
            color: #fff;
            padding: 10px;
        }

        #sidebar ul li a {
            padding: 10px 20px;
            font-size: 1.1em;
            display: block;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.3s;
        }

        #sidebar ul li a:hover {
            color: #fff;
            background: rgba(255, 255, 255, 0.1);
        }

        #sidebar ul li.active>a {
            color: #fff;
            background: var(--primary-color);
        }

        #sidebar .sidebar-footer {
            position: absolute;
            bottom: 0;
            width: 100%;
            padding: 15px;
            background: rgba(0, 0, 0, 0.2);
            text-align: center;
        }

        /* Main content area */
        #content {
            width: 100%;
            min-height: 100vh;
            transition: all 0.3s;
            padding: 20px;
            background: var(--light-color)
        }

        /* Form styling */
        .form-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.05);
            padding: 30px;
        }

        .form-section {
            margin-bottom: 25px;
        }

        .form-section-title {
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        /* Badge styles */
        .badge-specialization {
            font-size: 85%;
            padding: 0.35em 0.65em;
            font-weight: 500;
        }

        .badge-skin_care {
            background-color: #a29bfe;
            color: white;
        }

        .badge-laser {
            background-color: #ff7675;
            color: white;
        }

        .badge-cosmetic {
            background-color: #74b9ff;
            color: white;
        }

        .badge-hair {
            background-color: #55efc4;
            color: white;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            #sidebar {
                margin-left: -250px;
            }

            #sidebar.active {
                margin-left: 0;
            }

            #content {
                width: 100%;
            }

            #sidebarCollapse span {
                display: none;
            }
        }
    </style>
</head>

<body>

    <div class="wrapper d-flex align-items-stretch">
        <!-- Dashboard Sidebar -->
        <?php require "../src/sidebar.php"; ?>

        <!-- Page Content -->
        <div id="content">
            <div class="dashboard-header d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-0">Add New Specialist</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb bg-transparent p-0 mb-0">
                            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="manage_specialists.php">Manage Specialists</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Add Specialist</li>
                        </ol>
                    </nav>
                </div>
            </div>

            <!-- Display error message if any -->
            <?php if (isset($_SESSION['error_msg'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['error_msg'];
                    unset($_SESSION['error_msg']); ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>

            <!-- Add Specialist Form -->
            <div class="form-container">
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">

                    <!-- Personal Information Section -->
                    <div class="form-section">
                        <h5 class="form-section-title">
                            <i class="fas fa-user-circle mr-2"></i>Personal Information
                        </h5>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>First Name</label>
                                    <input type="text" name="first_name"
                                        class="form-control <?php echo (!empty($errors["first_name"])) ? 'is-invalid' : ''; ?>"
                                        value="<?php echo $first_name; ?>">
                                    <div class="invalid-feedback"><?php echo $errors["first_name"] ?? ''; ?></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Last Name</label>
                                    <input type="text" name="last_name"
                                        class="form-control <?php echo (!empty($errors["last_name"])) ? 'is-invalid' : ''; ?>"
                                        value="<?php echo $last_name; ?>">
                                    <div class="invalid-feedback"><?php echo $errors["last_name"] ?? ''; ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Email</label>
                                    <input type="email" name="email"
                                        class="form-control <?php echo (!empty($errors["email"])) ? 'is-invalid' : ''; ?>"
                                        value="<?php echo $email; ?>">
                                    <div class="invalid-feedback"><?php echo $errors["email"] ?? ''; ?></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Mobile Number</label>
                                    <input type="text" name="mobile"
                                        class="form-control <?php echo (!empty($errors["mobile"])) ? 'is-invalid' : ''; ?>"
                                        value="<?php echo $mobile; ?>">
                                    <div class="invalid-feedback"><?php echo $errors["mobile"] ?? ''; ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>CNIC (ID Number)</label>
                                    <input type="text" name="cnic"
                                        class="form-control <?php echo (!empty($errors["cnic"])) ? 'is-invalid' : ''; ?>"
                                        value="<?php echo $cnic; ?>" placeholder="e.g. 12345-1234567-1">
                                    <div class="invalid-feedback"><?php echo $errors["cnic"] ?? ''; ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Location Information Section -->
                    <div class="form-section">
                        <h5 class="form-section-title">
                            <i class="fas fa-map-marker-alt mr-2"></i>Location Information
                        </h5>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>State/Province</label>
                                    <input type="text" name="state"
                                        class="form-control <?php echo (!empty($errors["state"])) ? 'is-invalid' : ''; ?>"
                                        value="<?php echo $state; ?>">
                                    <div class="invalid-feedback"><?php echo $errors["state"] ?? ''; ?></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>City</label>
                                    <input type="text" name="city"
                                        class="form-control <?php echo (!empty($errors["city"])) ? 'is-invalid' : ''; ?>"
                                        value="<?php echo $city; ?>">
                                    <div class="invalid-feedback"><?php echo $errors["city"] ?? ''; ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Professional Information Section -->
                    <div class="form-section">
                        <h5 class="form-section-title">
                            <i class="fas fa-user-md mr-2"></i>Professional Information
                        </h5>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Specialization</label>
                                    <select name="specialization"
                                        class="form-control <?php echo (!empty($errors["specialization"])) ? 'is-invalid' : ''; ?>">
                                        <option value="" selected disabled>Select Specialization</option>
                                        <option value="skin_care" <?php echo ($specialization == "skin_care") ? 'selected' : ''; ?>>Skin Care</option>
                                        <option value="laser" <?php echo ($specialization == "laser") ? 'selected' : ''; ?>>Laser Treatment</option>
                                        <option value="cosmetic" <?php echo ($specialization == "cosmetic") ? 'selected' : ''; ?>>Cosmetic Treatment</option>
                                        <option value="hair" <?php echo ($specialization == "hair") ? 'selected' : ''; ?>>
                                            Hair Treatment</option>
                                    </select>
                                    <div class="invalid-feedback"><?php echo $errors["specialization"] ?? ''; ?></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Years of Experience</label>
                                    <input type="number" name="experience"
                                        class="form-control <?php echo (!empty($errors["experience"])) ? 'is-invalid' : ''; ?>"
                                        value="<?php echo $experience; ?>" min="0">
                                    <div class="invalid-feedback"><?php echo $errors["experience"] ?? ''; ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Account Information Section -->
                    <div class="form-section">
                        <h5 class="form-section-title">
                            <i class="fas fa-lock mr-2"></i>Account Information
                        </h5>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Username</label>
                                    <input type="text" name="username"
                                        class="form-control <?php echo (!empty($errors["username"])) ? 'is-invalid' : ''; ?>"
                                        value="<?php echo $username; ?>">
                                    <div class="invalid-feedback"><?php echo $errors["username"] ?? ''; ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Password</label>
                                    <input type="password" name="password"
                                        class="form-control <?php echo (!empty($errors["password"])) ? 'is-invalid' : ''; ?>">
                                    <div class="invalid-feedback"><?php echo $errors["password"] ?? ''; ?></div>
                                    <small class="form-text text-muted">Password must be at least 6 characters
                                        long.</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Confirm Password</label>
                                    <input type="password" name="confirm_password"
                                        class="form-control <?php echo (!empty($errors["confirm_password"])) ? 'is-invalid' : ''; ?>">
                                    <div class="invalid-feedback"><?php echo $errors["confirm_password"] ?? ''; ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Buttons -->
                    <div class="form-group d-flex justify-content-between">
                        <a href="manage_specialists.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left mr-2"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-user-plus mr-2"></i>Add Specialist
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- jQuery and Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        $(document).ready(function () {
            // Auto-dismiss alerts after 5 seconds
            window.setTimeout(function () {
                $(".alert").fadeTo(500, 0).slideUp(500, function () {
                    $(this).remove();
                });
            }, 5000);
        });
    </script>
</body>

</html>