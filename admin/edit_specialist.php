<?php
// Include config file
require_once "../config.php";

// Check if user is admin
require_admin();

$_SESSION["user_role"] = "admin"; // or "admin" 

// Check if id parameter is set
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: manage_specialists.php");
    exit;
}

$user_id = intval($_GET['id']);

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Extract form data
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $mobile = trim($_POST['mobile']);
    $city = trim($_POST['city']);
    $state = trim($_POST['state']);
    $specialization = trim($_POST['specialization']);
    $experience = intval($_POST['experience']);

    // Validate input
    $errors = [];

    if (empty($first_name)) {
        $errors[] = "First name is required";
    }

    if (empty($last_name)) {
        $errors[] = "Last name is required";
    }

    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }

    if (empty($mobile)) {
        $errors[] = "Mobile number is required";
    }

    // If no errors, update the specialist
    if (empty($errors)) {
        // Start transaction
        $conn->begin_transaction();

        try {
            // Update users table
            $sql1 = "UPDATE users SET 
                    first_name = ?, 
                    last_name = ?, 
                    email = ?, 
                    mobile = ?,
                    city = ?,
                    state = ?
                    WHERE id = ? AND user_type = 'specialist'";

            $stmt1 = $conn->prepare($sql1);
            $stmt1->bind_param("ssssssi", $first_name, $last_name, $email, $mobile, $city, $state, $user_id);
            $stmt1->execute();

            // Update specialists table
            $sql2 = "UPDATE specialists SET 
                    specialization = ?, 
                    experience = ?
                    WHERE user_id = ?";

            $stmt2 = $conn->prepare($sql2);
            $stmt2->bind_param("sii", $specialization, $experience, $user_id);
            $stmt2->execute();

            // Commit transaction
            $conn->commit();

            $_SESSION['success_msg'] = "Specialist updated successfully";
            header("Location: manage_specialists.php");
            exit;

        } catch (Exception $e) {
            // Roll back transaction on error
            $conn->rollback();
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

// Get specialist data
$specialist = null;
$sql = "SELECT u.*, s.experience, s.specialization 
        FROM users u 
        JOIN specialists s ON u.id = s.user_id 
        WHERE u.id = ? AND u.user_type = 'specialist'";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error_msg'] = "Specialist not found";
    header("Location: manage_specialists.php");
    exit;
}

$specialist = $result->fetch_assoc();

// Function to format specialization for display
function format_specialization($specialization)
{
    switch ($specialization) {
        case 'skin_care':
            return 'Skin Care';
        case 'laser':
            return 'Laser Treatment';
        case 'cosmetic':
            return 'Cosmetic Procedure';
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
    <title>Admin Dashboard - Edit Specialist</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        :root {
            --primary-color: #4a90e2;
            --secondary-color: #2c3e50;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --light-bg: #f8f9fa;
            --border-color: #dee2e6;
        }

        body {
            font-family: 'Arial', sans-serif;
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

        /* Main content area */
        #content {
            width: 100%;
            min-height: 100vh;
            transition: all 0.3s;
            padding: 20px;
        }

        /* Classic form styling */
        .form-card {
            background: white;
            border: 1px solid var(--border-color);
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 25px;
        }

        .form-card h4 {
            color: var(--primary-color);
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .form-group label {
            font-weight: bold;
            color: #333;
        }

        .form-control {
            border: 1px solid #ced4da;
            border-radius: 3px;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(51, 102, 153, 0.25);
        }

        .form-control[readonly] {
            background-color: #f8f9fa;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: #264d73;
            border-color: #264d73;
        }

        .required-field::after {
            content: "*";
            color: red;
            margin-left: 4px;
        }

        /* Dashboard header styling */
        .dashboard-header {
            margin-bottom: 20px;
        }

        .breadcrumb {
            background-color: transparent;
            padding: 0;
            margin-bottom: 0;
        }

        .breadcrumb-item+.breadcrumb-item::before {
            content: ">";
        }

        /* Alert styling */
        .alert {
            border-radius: 3px;
        }

        /* Form section styling */
        .form-section {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .form-section-title {
            font-size: 1.1rem;
            color: #555;
            margin-bottom: 15px;
        }

        /* Button styling */
        .form-actions {
            padding-top: 15px;
            margin-top: 20px;
            border-top: 1px solid #eee;
        }
    </style>
</head>

<body>

    <div class="wrapper d-flex align-items-stretch">
 

         <!-- Dashboard Sidebar  -->
     <?php
        require "../src/sidebar.php";
        ?>

        <!-- Page Content  -->
        <div id="content">
            <div class="dashboard-header d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-0">Edit Specialist</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb bg-transparent p-0 mb-0">
                            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="manage_specialists.php">Manage Specialists</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Edit Specialist</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <a href="manage_specialists.php" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left mr-1"></i> Back to List
                    </a>
                </div>
            </div>

            <!-- Display errors if any -->
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>Error!</strong>
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>

            <!-- Edit Specialist Form -->
            <div class="form-card mt-4">
                <h4>Specialist Information</h4>
                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?id=' . $user_id; ?>">

                    <!-- Personal Information Section -->
                    <div class="form-section">
                        <div class="form-section-title">Personal Information</div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="first_name" class="required-field">First Name</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name"
                                        value="<?php echo htmlspecialchars($specialist['first_name']); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="last_name" class="required-field">Last Name</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name"
                                        value="<?php echo htmlspecialchars($specialist['last_name']); ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="email" class="required-field">Email</label>
                                    <input type="email" class="form-control" id="email" name="email"
                                        value="<?php echo htmlspecialchars($specialist['email']); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="mobile" class="required-field">Mobile Number</label>
                                    <input type="text" class="form-control" id="mobile" name="mobile"
                                        value="<?php echo htmlspecialchars($specialist['mobile']); ?>" required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Account Information Section -->
                    <div class="form-section">
                        <div class="form-section-title">Account Information</div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="username">Username</label>
                                    <input type="text" class="form-control" id="username"
                                        value="<?php echo htmlspecialchars($specialist['username']); ?>" readonly>
                                    <small class="form-text text-muted">Username cannot be changed</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="cnic">CNIC</label>
                                    <input type="text" class="form-control" id="cnic"
                                        value="<?php echo htmlspecialchars($specialist['cnic']); ?>" readonly>
                                    <small class="form-text text-muted">CNIC cannot be changed</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Location Information Section -->
                    <div class="form-section">
                        <div class="form-section-title">Location Information</div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="city" class="required-field">City</label>
                                    <input type="text" class="form-control" id="city" name="city"
                                        value="<?php echo htmlspecialchars($specialist['city']); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="state" class="required-field">State/Province</label>
                                    <input type="text" class="form-control" id="state" name="state"
                                        value="<?php echo htmlspecialchars($specialist['state']); ?>" required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Professional Information Section -->
                    <div class="form-section">
                        <div class="form-section-title">Professional Information</div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="specialization" class="required-field">Specialization</label>
                                    <select class="form-control" id="specialization" name="specialization" required>
                                        <option value="skin_care" <?php echo ($specialist['specialization'] == 'skin_care') ? 'selected' : ''; ?>>Skin Care
                                        </option>
                                        <option value="laser" <?php echo ($specialist['specialization'] == 'laser') ? 'selected' : ''; ?>>Laser Treatment</option>
                                        <option value="cosmetic" <?php echo ($specialist['specialization'] == 'cosmetic') ? 'selected' : ''; ?>>Cosmetic Procedure</option>
                                        <option value="hair" <?php echo ($specialist['specialization'] == 'hair') ? 'selected' : ''; ?>>Hair Treatment</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="experience" class="required-field">Years of Experience</label>
                                    <input type="number" class="form-control" id="experience" name="experience"
                                        value="<?php echo htmlspecialchars($specialist['experience']); ?>" min="0"
                                        required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-1"></i> Save Changes
                        </button>
                        <a href="manage_specialists.php" class="btn btn-secondary ml-2">
                            <i class="fas fa-times mr-1"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- jQuery and Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>