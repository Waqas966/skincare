<?php
// Include config file
require_once "../config.php";

// Check if user is logged in and is a patient
require_patient();


$_SESSION["user_role"] = "patient"; // or "admin" or "patient"


// Get patient data
$user_id = $_SESSION['user_id'];

// Check if appointment ID is provided
if (!isset($_GET['appointment_id']) || empty($_GET['appointment_id'])) {
    $_SESSION['error'] = "Invalid appointment selection.";
    header("Location: appointments.php");
    exit;
}

$appointment_id = $_GET['appointment_id'];

// Verify the appointment belongs to this patient and is completed
$verify_sql = "SELECT a.*, u.first_name, u.last_name, s.specialization 
               FROM appointments a 
               LEFT JOIN users u ON a.specialist_id = u.id 
               LEFT JOIN specialists s ON u.id = s.user_id
               WHERE a.id = ? AND a.patient_id = ? AND a.status = 'completed'";
$verify_stmt = mysqli_prepare($conn, $verify_sql);
mysqli_stmt_bind_param($verify_stmt, "ii", $appointment_id, $user_id);
mysqli_stmt_execute($verify_stmt);
$verify_result = mysqli_stmt_get_result($verify_stmt);

if (mysqli_num_rows($verify_result) == 0) {
    $_SESSION['error'] = "You can only review completed appointments.";
    header("Location: appointments.php");
    exit;
}

$appointment = mysqli_fetch_assoc($verify_result);

// Check if review already exists
$check_review_sql = "SELECT * FROM reviews WHERE appointment_id = ?";
$check_review_stmt = mysqli_prepare($conn, $check_review_sql);
mysqli_stmt_bind_param($check_review_stmt, "i", $appointment_id);
mysqli_stmt_execute($check_review_stmt);
$existing_review = mysqli_stmt_get_result($check_review_stmt);
$review_exists = mysqli_num_rows($existing_review) > 0;
$review_data = $review_exists ? mysqli_fetch_assoc($existing_review) : null;

// Handle delete review
if (isset($_POST['delete_review']) && $_POST['delete_review'] == 1) {
    // Delete the review
    $delete_sql = "DELETE FROM reviews WHERE appointment_id = ? AND patient_id = ?";
    $delete_stmt = mysqli_prepare($conn, $delete_sql);
    mysqli_stmt_bind_param($delete_stmt, "ii", $appointment_id, $user_id);

    if (mysqli_stmt_execute($delete_stmt)) {
        $_SESSION['success'] = "Your review has been deleted successfully.";
        header("Location: appointments.php");
        exit;
    } else {
        $error_message = "Error deleting review. Please try again.";
    }
}

// Handle form submission for adding/updating review
if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['delete_review'])) {
    $rating = $_POST['rating'];
    $review_text = $_POST['review_text'];
    $specialist_id = $appointment['specialist_id'];

    // Validate input
    if ($rating < 1 || $rating > 5) {
        $error_message = "Rating must be between 1 and 5.";
    } else {
        if ($review_exists) {
            // Update existing review
            $sql = "UPDATE reviews SET rating = ?, review_text = ? WHERE appointment_id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "isi", $rating, $review_text, $appointment_id);
        } else {
            // Insert new review
            $sql = "INSERT INTO reviews (appointment_id, patient_id, specialist_id, rating, review_text) VALUES (?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "iiiis", $appointment_id, $user_id, $specialist_id, $rating, $review_text);
        }

        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success'] = "Your review has been submitted successfully.";
            header("Location: appointments.php");
            exit;
        } else {
            $error_message = "Error submitting review. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Derma Elixir Studio</title>
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
            width: 260px;
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

        /* Star rating */
        .rating-container {
            display: flex;
            flex-direction: row-reverse;
            justify-content: flex-end;
            padding: 10px 0;
        }

        .rating-container input {
            display: none;
        }

        .rating-container label {
            font-size: 30px;
            color: #ddd;
            cursor: pointer;
            padding: 0 5px;
        }

        .rating-container label:hover,
        .rating-container label:hover~label,
        .rating-container input:checked~label {
            color: #FFD700;
        }

        /* Cards */
        .card {
            border-radius: 8px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            transition: transform 0.3s;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .card-header {
            border-radius: 8px 8px 0 0 !important;
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
   
         
        <!-- Dashboard Sidebar  -->
     <?php
        require "../src/sidebar.php";
        ?>


        <!-- Page Content  -->
        <div id="content">
            <nav class="navbar navbar-expand-lg navbar-light bg-light mb-4">
                <div class="container-fluid">
                    <div>
                        <h4 class="mb-0">Review Appointment</h4>
                    </div>
                </div>
            </nav>

            <div class="container-fluid">
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-lg-8 mx-auto">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="fas fa-star mr-2"></i> Rate Your Experience</h5>
                            </div>
                            <div class="card-body">
                                <!-- Appointment details -->
                                <div class="mb-4">
                                    <h6 class="text-muted">Appointment Details</h6>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p><strong>Date:</strong>
                                                <?php
                                                $date = new DateTime($appointment['appointment_date']);
                                                echo $date->format('M d, Y');
                                                ?>
                                            </p>
                                            <p><strong>Time:</strong>
                                                <?php
                                                $time = new DateTime($appointment['appointment_time']);
                                                echo $time->format('h:i A');
                                                ?>
                                            </p>
                                        </div>
                                        <div class="col-md-6">
                                            <p><strong>Specialist:</strong> Dr.
                                                <?php echo htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name']); ?>
                                            </p>
                                            <p><strong>Specialization:</strong>
                                                <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $appointment['specialization'])) ?? 'N/A'); ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <hr>

                                <!-- Review form -->
                                <form method="POST" action="">
                                    <div class="form-group">
                                        <label>Your Rating:</label>
                                        <div class="rating-container">
                                            <input type="radio" id="star5" name="rating" value="5" <?php echo $review_exists && $review_data['rating'] == 5 ? 'checked' : ''; ?>>
                                            <label for="star5" title="Excellent"><i class="fas fa-star"></i></label>

                                            <input type="radio" id="star4" name="rating" value="4" <?php echo $review_exists && $review_data['rating'] == 4 ? 'checked' : ''; ?>>
                                            <label for="star4" title="Very Good"><i class="fas fa-star"></i></label>

                                            <input type="radio" id="star3" name="rating" value="3" <?php echo $review_exists && $review_data['rating'] == 3 ? 'checked' : ''; ?>>
                                            <label for="star3" title="Good"><i class="fas fa-star"></i></label>

                                            <input type="radio" id="star2" name="rating" value="2" <?php echo $review_exists && $review_data['rating'] == 2 ? 'checked' : ''; ?>>
                                            <label for="star2" title="Poor"><i class="fas fa-star"></i></label>

                                            <input type="radio" id="star1" name="rating" value="1" <?php echo $review_exists && $review_data['rating'] == 1 ? 'checked' : ''; ?>>
                                            <label for="star1" title="Very Poor"><i class="fas fa-star"></i></label>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="review_text">Your Review:</label>
                                        <textarea class="form-control" id="review_text" name="review_text" rows="5"
                                            placeholder="Share your experience with the specialist..."><?php echo $review_exists ? htmlspecialchars($review_data['review_text']) : ''; ?></textarea>
                                    </div>

                                    <div class="text-center mt-4">
                                        <a href="appointments.php" class="btn btn-secondary mr-2">
                                            <i class="fas fa-arrow-left mr-1"></i> Back to Appointments
                                        </a>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-paper-plane mr-1"></i>
                                            <?php echo $review_exists ? 'Update Review' : 'Submit Review'; ?>
                                        </button>

                                        <?php if ($review_exists): ?>
                                            <!-- Delete Review Button (with confirmation modal) -->
                                            <button type="button" class="btn btn-danger ml-2" data-toggle="modal"
                                                data-target="#deleteReviewModal">
                                                <i class="fas fa-trash-alt mr-1"></i> Delete Review
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Review Confirmation Modal -->
    <?php if ($review_exists): ?>
        <div class="modal fade" id="deleteReviewModal" tabindex="-1" role="dialog" aria-labelledby="deleteReviewModalLabel"
            aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title" id="deleteReviewModalLabel">Confirm Delete</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to delete your review? This action cannot be undone.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <form method="POST" action="">
                            <input type="hidden" name="delete_review" value="1">
                            <button type="submit" class="btn btn-danger">Delete Review</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- jQuery and Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        $(document).ready(function () {
            $('#sidebarCollapse').on('click', function () {
                $('#sidebar').toggleClass('active');
            });
        });
    </script>
</body>

</html>