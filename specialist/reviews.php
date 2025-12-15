<?php
// Include config file
require_once "../config.php";

// Check if user is logged in and is a specialist
require_specialist();



$_SESSION["user_role"] = "specialist"; // or "admin" or "patient"

// Get specialist data
$user_id = $_SESSION['user_id'];

// Get all reviews for this specialist
$reviews_sql = "SELECT r.*, a.appointment_date, a.appointment_time, a.treatment_type, 
                u.first_name, u.last_name 
                FROM reviews r 
                JOIN appointments a ON r.appointment_id = a.id
                JOIN users u ON r.patient_id = u.id
                WHERE r.specialist_id = ?
                ORDER BY r.created_at DESC";

$reviews_stmt = mysqli_prepare($conn, $reviews_sql);
mysqli_stmt_bind_param($reviews_stmt, "i", $user_id);
mysqli_stmt_execute($reviews_stmt);
$reviews_result = mysqli_stmt_get_result($reviews_stmt);

// Calculate average rating
$avg_rating_sql = "SELECT AVG(rating) as average_rating, COUNT(*) as total_reviews 
                   FROM reviews 
                   WHERE specialist_id = ?";
$avg_rating_stmt = mysqli_prepare($conn, $avg_rating_sql);
mysqli_stmt_bind_param($avg_rating_stmt, "i", $user_id);
mysqli_stmt_execute($avg_rating_stmt);
$avg_rating_result = mysqli_stmt_get_result($avg_rating_stmt);
$rating_data = mysqli_fetch_assoc($avg_rating_result);

$average_rating = $rating_data['average_rating'] ?? 0;
$total_reviews = $rating_data['total_reviews'] ?? 0;

// Get rating distribution
$rating_dist_sql = "SELECT rating, COUNT(*) as count 
                    FROM reviews 
                    WHERE specialist_id = ? 
                    GROUP BY rating 
                    ORDER BY rating DESC";
$rating_dist_stmt = mysqli_prepare($conn, $rating_dist_sql);
mysqli_stmt_bind_param($rating_dist_stmt, "i", $user_id);
mysqli_stmt_execute($rating_dist_stmt);
$rating_dist_result = mysqli_stmt_get_result($rating_dist_stmt);

$rating_distribution = array_fill(1, 5, 0); // Initialize with 0 for each star rating (1-5)
while ($row = mysqli_fetch_assoc($rating_dist_result)) {
    $rating_distribution[$row['rating']] = $row['count'];
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

         /* Header */
        .dashboard-header {
            background-color: white;
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.05);
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

        /* Star rating display */
        .stars-container {
            color: #FFD700;
            font-size: 1.2rem;
            display: inline-block;
        }

        .rating-distribution .progress {
            height: 10px;
            margin-bottom: 10px;
        }

        .rating-distribution .progress-bar {
            background-color: #FFD700;
        }

        .review-item {
            border-left: 4px solid var(--primary-color);
            padding-left: 15px;
            margin-bottom: 20px;
        }

        .review-date {
            color: #6c757d;
            font-size: 0.9rem;
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
                <div class="dashboard-header d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-0">Reviews</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb bg-transparent p-0 mb-0">
                            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Reviews</li>
                        </ol>
                    </nav>
                </div>
            </div>
            

            <div class="container-fluid">
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success" role="alert">
                        <?php echo $_SESSION['success'];
                        unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo $_SESSION['error'];
                        unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <!-- Summary Card -->
                    <div class="col-lg-4 mb-4">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="fas fa-chart-bar mr-2"></i> Review Summary</h5>
                            </div>
                            <div class="card-body">
                                <div class="text-center mb-3">
                                    <h2 class="mb-0"><?php echo number_format($average_rating, 1); ?> / 5</h2>
                                    <div class="stars-container mb-2">
                                        <?php
                                        $full_stars = floor($average_rating);
                                        $half_star = $average_rating - $full_stars >= 0.5;
                                        $empty_stars = 5 - $full_stars - ($half_star ? 1 : 0);

                                        for ($i = 0; $i < $full_stars; $i++) {
                                            echo '<i class="fas fa-star"></i>';
                                        }
                                        if ($half_star) {
                                            echo '<i class="fas fa-star-half-alt"></i>';
                                        }
                                        for ($i = 0; $i < $empty_stars; $i++) {
                                            echo '<i class="far fa-star"></i>';
                                        }
                                        ?>
                                    </div>
                                    <p class="text-muted">Based on <?php echo $total_reviews; ?> reviews</p>
                                </div>

                                <div class="rating-distribution">
                                    <?php for ($i = 5; $i >= 1; $i--): ?>
                                        <div class="d-flex align-items-center mb-2">
                                            <div class="mr-2" style="width: 35px;">
                                                <?php echo $i; ?> <i class="fas fa-star text-warning small"></i>
                                            </div>
                                            <div class="progress flex-grow-1">
                                                <?php
                                                $percentage = $total_reviews > 0 ? ($rating_distribution[$i] / $total_reviews) * 100 : 0;
                                                ?>
                                                <div class="progress-bar" role="progressbar"
                                                    style="width: <?php echo $percentage; ?>%"
                                                    aria-valuenow="<?php echo $percentage; ?>" aria-valuemin="0"
                                                    aria-valuemax="100">
                                                </div>
                                            </div>
                                            <div class="ml-2" style="width: 35px;">
                                                <?php echo $rating_distribution[$i]; ?>
                                            </div>
                                        </div>
                                    <?php endfor; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Reviews List -->
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="fas fa-comments mr-2"></i> Patient Reviews</h5>
                            </div>
                            <div class="card-body">
                                <?php if (mysqli_num_rows($reviews_result) > 0): ?>
                                    <?php while ($review = mysqli_fetch_assoc($reviews_result)): ?>
                                        <div class="review-item">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <h5 class="mb-0">
                                                    <?php echo htmlspecialchars($review['first_name'] . ' ' . $review['last_name']); ?>
                                                </h5>
                                                <div class="stars-container">
                                                    <?php
                                                    for ($i = 1; $i <= 5; $i++) {
                                                        if ($i <= $review['rating']) {
                                                            echo '<i class="fas fa-star"></i>';
                                                        } else {
                                                            echo '<i class="far fa-star"></i>';
                                                        }
                                                    }
                                                    ?>
                                                </div>
                                            </div>
                                            <div class="mb-2">
                                                <span
                                                    class="badge badge-primary"><?php echo htmlspecialchars($review['treatment_type']); ?></span>
                                                <span class="review-date ml-2">
                                                    <?php
                                                    $date = new DateTime($review['appointment_date']);
                                                    echo $date->format('M d, Y');
                                                    ?>
                                                </span>
                                            </div>
                                            <p><?php echo htmlspecialchars($review['review_text'] ?? 'No comment provided.'); ?>
                                            </p>
                                        </div>
                                        <?php if (mysqli_data_seek($reviews_result, mysqli_num_rows($reviews_result)) === FALSE): ?>
                                            <hr>
                                        <?php endif; ?>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <div class="text-center py-5">
                                        <i class="fas fa-comment-slash text-muted fa-4x mb-3"></i>
                                        <h5>No reviews yet</h5>
                                        <p class="text-muted">When patients leave reviews, they will appear here.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

 <!-- jQuery, Popper.js, and Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.min.js"></script>

    <script>
        $(document).ready(function () {
            $('#sidebarCollapse').on('click', function () {
                $('#sidebar').toggleClass('active');
            });
        });
    </script>
</body>

</html>