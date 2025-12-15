<?php
// Include config file
require_once "../config.php";

// Check if user is admin
require_admin();


$_SESSION["user_role"] = "admin"; // or "admin" 
// Set default date range (last 30 days)
$end_date = date('Y-m-d');
$start_date = date('Y-m-d', strtotime('-30 days'));

// Handle date filter if submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['filter_date'])) {
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
}


// Get filtered appointment statistics
$appointment_stats_sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
FROM appointments 
WHERE appointment_date BETWEEN ? AND ?";

$appointment_stats_stmt = mysqli_prepare($conn, $appointment_stats_sql);
mysqli_stmt_bind_param($appointment_stats_stmt, "ss", $start_date, $end_date);
mysqli_stmt_execute($appointment_stats_stmt);
$appointment_stats = mysqli_fetch_assoc(mysqli_stmt_get_result($appointment_stats_stmt));

// Get appointment types statistics
$appointment_types_sql = "SELECT 
    treatment_type,
    COUNT(*) as count
FROM appointments 
WHERE appointment_date BETWEEN ? AND ?
GROUP BY treatment_type
ORDER BY count DESC";

$appointment_types_stmt = mysqli_prepare($conn, $appointment_types_sql);
mysqli_stmt_bind_param($appointment_types_stmt, "ss", $start_date, $end_date);
mysqli_stmt_execute($appointment_types_stmt);
$appointment_types_result = mysqli_stmt_get_result($appointment_types_stmt);
$appointment_types = [];
while ($row = mysqli_fetch_assoc($appointment_types_result)) {
    $appointment_types[] = $row;
}

// Get specialist performance (rating averages)
$specialist_ratings_sql = "SELECT 
    u.id,
    CONCAT(u.first_name, ' ', u.last_name) as specialist_name,
    s.specialization,
    AVG(r.rating) as avg_rating,
    COUNT(r.id) as review_count
FROM reviews r
JOIN users u ON r.specialist_id = u.id
JOIN specialists s ON u.id = s.user_id
JOIN appointments a ON r.appointment_id = a.id
WHERE a.appointment_date BETWEEN ? AND ?
GROUP BY u.id
ORDER BY avg_rating DESC";

$specialist_ratings_stmt = mysqli_prepare($conn, $specialist_ratings_sql);
mysqli_stmt_bind_param($specialist_ratings_stmt, "ss", $start_date, $end_date);
mysqli_stmt_execute($specialist_ratings_stmt);
$specialist_ratings_result = mysqli_stmt_get_result($specialist_ratings_stmt);
$specialist_ratings = [];
while ($row = mysqli_fetch_assoc($specialist_ratings_result)) {
    $specialist_ratings[] = $row;
}

// Get recent reviews
$recent_reviews_sql = "SELECT 
    r.id, 
    r.rating, 
    r.review_text, 
    r.created_at,
    CONCAT(p.first_name, ' ', p.last_name) as patient_name,
    CONCAT(s.first_name, ' ', s.last_name) as specialist_name,
    sp.specialization
FROM reviews r
JOIN users p ON r.patient_id = p.id
JOIN users s ON r.specialist_id = s.id
JOIN specialists sp ON s.id = sp.user_id
JOIN appointments a ON r.appointment_id = a.id
WHERE a.appointment_date BETWEEN ? AND ?
ORDER BY r.created_at DESC
LIMIT 10";

$recent_reviews_stmt = mysqli_prepare($conn, $recent_reviews_sql);
mysqli_stmt_bind_param($recent_reviews_stmt, "ss", $start_date, $end_date);
mysqli_stmt_execute($recent_reviews_stmt);
$recent_reviews_result = mysqli_stmt_get_result($recent_reviews_stmt);
$recent_reviews = [];
while ($row = mysqli_fetch_assoc($recent_reviews_result)) {
    $recent_reviews[] = $row;
}

// Get appointments by date (for chart)
$appointments_by_date_sql = "SELECT 
    appointment_date,
    COUNT(*) as appointment_count
FROM appointments
WHERE appointment_date BETWEEN ? AND ?
GROUP BY appointment_date
ORDER BY appointment_date";

$appointments_by_date_stmt = mysqli_prepare($conn, $appointments_by_date_sql);
mysqli_stmt_bind_param($appointments_by_date_stmt, "ss", $start_date, $end_date);
mysqli_stmt_execute($appointments_by_date_stmt);
$appointments_by_date_result = mysqli_stmt_get_result($appointments_by_date_stmt);
$appointments_by_date = [];
while ($row = mysqli_fetch_assoc($appointments_by_date_result)) {
    $appointments_by_date[] = $row;
}

// Convert the appointment data to a format suitable for Chart.js
$chart_labels = [];
$chart_data = [];
foreach ($appointments_by_date as $data) {
    $chart_labels[] = date('M d', strtotime($data['appointment_date']));
    $chart_data[] = $data['appointment_count'];
}

// Convert specialization names for display
function formatSpecialization($spec)
{
    return ucwords(str_replace('_', ' ', $spec));
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Reports - Derma Elixir Studio</title>
    <!-- Basic favicon -->
    <link rel="icon" href="../images/favicon.svg" sizes="32x32">
    <!-- SVG favicon -->
    <link rel="icon" href="../images/favicon.svg" type="image/svg+xml">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
            --success-color: #2ecc71;
            --danger-color: #e74c3c;
            --warning-color: #f39c12;
            --info-color: #3498db;
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

        /* Dashboard cards */
        .stats-card {
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            transition: transform 0.3s;
        }

        .stats-card:hover {
            transform: translateY(-5px);
        }

        .stats-icon {
            font-size: 2rem;
            opacity: 0.8;
        }

        .stats-card .card-body {
            padding: 1.5rem;
        }

        .stats-number {
            font-size: 2rem;
            font-weight: 600;
        }

        /* Report cards */
        .report-card {
            border-radius: 8px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .report-card .card-header {
            border-radius: 8px 8px 0 0 !important;
            font-weight: 600;
        }

        /* Star rating display */
        .stars-display {
            color: #FFD700;
            font-size: 1rem;
        }

        .reviews-list {
            max-height: 400px;
            overflow-y: auto;
        }

        /* Filter form */
        .filter-form {
            background-color: #fff;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
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
                        <h4 class="mb-0"><i class="fas fa-chart-line mr-2"></i> Admin Reports Dashboard</h4>
                    </div>
                </div>
            </nav>

            <div class="container-fluid">
                <!-- Date Range Filter -->
                <div class="filter-form">
                    <form method="POST" action="" class="row align-items-end">
                        <div class="col-md-4">
                            <label for="start_date">Start Date:</label>
                            <input type="date" class="form-control" id="start_date" name="start_date"
                                value="<?php echo $start_date; ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="end_date">End Date:</label>
                            <input type="date" class="form-control" id="end_date" name="end_date"
                                value="<?php echo $end_date; ?>">
                        </div>
                        <div class="col-md-4">
                            <button type="submit" name="filter_date" class="btn btn-primary btn-block">
                                <i class="fas fa-filter mr-1"></i> Apply Filter
                            </button>
                        </div>
                    </form>
                </div>

                
              

                <!-- Main Report Row -->
                <div class="row">
                    <!-- Appointment Stats -->
                    <div class="col-lg-8">
                        <div class="report-card card">
                            <div class="card-header bg-primary text-white">
                                <i class="fas fa-chart-bar mr-2"></i> Appointment Activity
                            </div>
                            <div class="card-body">
                                <canvas id="appointmentChart" height="220"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Appointment Status Summary -->
                    <div class="col-lg-4">
                        <div class="report-card card">
                            <div class="card-header bg-success text-white">
                                <i class="fas fa-tasks mr-2"></i> Appointment Status
                            </div>
                            <div class="card-body">
                                <div class="list-group">
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        Pending
                                        <span
                                            class="badge badge-warning badge-pill"><?php echo $appointment_stats['pending']; ?></span>
                                    </div>
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        Confirmed
                                        <span
                                            class="badge badge-info badge-pill"><?php echo $appointment_stats['confirmed']; ?></span>
                                    </div>
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        Completed
                                        <span
                                            class="badge badge-success badge-pill"><?php echo $appointment_stats['completed']; ?></span>
                                    </div>
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        Cancelled
                                        <span
                                            class="badge badge-danger badge-pill"><?php echo $appointment_stats['cancelled']; ?></span>
                                    </div>
                                    <div
                                        class="list-group-item d-flex justify-content-between align-items-center font-weight-bold bg-light">
                                        Total
                                        <span
                                            class="badge badge-primary badge-pill"><?php echo $appointment_stats['total']; ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Second Report Row -->
                <div class="row">
                    <!-- Specialist Ratings -->
                    <div class="col-lg-6">
                        <div class="report-card card">
                            <div class="card-header bg-info text-white">
                                <i class="fas fa-star mr-2"></i> Specialist Performance
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Specialist</th>
                                                <th>Specialization</th>
                                                <th>Rating</th>
                                                <th>Reviews</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($specialist_ratings)): ?>
                                                <tr>
                                                    <td colspan="4" class="text-center">No specialist ratings available for
                                                        this period.</td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($specialist_ratings as $rating): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($rating['specialist_name']); ?></td>
                                                        <td><?php echo formatSpecialization($rating['specialization']); ?></td>
                                                        <td>
                                                            <div class="stars-display">
                                                                <?php
                                                                $avg_rounded = round($rating['avg_rating']);
                                                                for ($i = 1; $i <= 5; $i++) {
                                                                    echo $i <= $avg_rounded ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
                                                                }
                                                                echo " (" . number_format($rating['avg_rating'], 1) . ")";
                                                                ?>
                                                            </div>
                                                        </td>
                                                        <td><?php echo $rating['review_count']; ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Treatment Types -->
                    <div class="col-lg-6">
                        <div class="report-card card">
                            <div class="card-header bg-warning text-white">
                                <i class="fas fa-procedures mr-2"></i> Popular Treatment Types
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Treatment Type</th>
                                                <th>Appointments</th>
                                                <th>Percentage</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($appointment_types)): ?>
                                                <tr>
                                                    <td colspan="3" class="text-center">No appointments available for this
                                                        period.</td>
                                                </tr>
                                            <?php else: ?>
                                                <?php
                                                $total_count = array_sum(array_column($appointment_types, 'count'));
                                                foreach ($appointment_types as $type): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($type['treatment_type']); ?></td>
                                                        <td><?php echo $type['count']; ?></td>
                                                        <td>
                                                            <?php
                                                            $percentage = ($type['count'] / $total_count) * 100;
                                                            echo number_format($percentage, 1) . '%';
                                                            ?>
                                                            <div class="progress mt-1" style="height: 4px;">
                                                                <div class="progress-bar bg-info" role="progressbar"
                                                                    style="width: <?php echo $percentage; ?>%"
                                                                    aria-valuenow="<?php echo $percentage; ?>" aria-valuemin="0"
                                                                    aria-valuemax="100"></div>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Reviews Row -->
                <div class="row">
                    <div class="col-12">
                        <div class="report-card card">
                            <div class="card-header bg-danger text-white">
                                <i class="fas fa-comments mr-2"></i> Recent Reviews
                            </div>
                            <div class="card-body reviews-list">
                                <?php if (empty($recent_reviews)): ?>
                                    <div class="text-center py-3">
                                        <p class="mb-0">No reviews available for this period.</p>
                                    </div>
                                <?php else: ?>
                                    <div class="list-group">
                                        <?php foreach ($recent_reviews as $review): ?>
                                            <div class="list-group-item">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <h6 class="mb-1">
                                                            Patient: <?php echo htmlspecialchars($review['patient_name']); ?>
                                                            <span class="text-muted font-weight-normal">→</span>
                                                            Dr. <?php echo htmlspecialchars($review['specialist_name']); ?>
                                                            <span
                                                                class="badge badge-info"><?php echo formatSpecialization($review['specialization']); ?></span>
                                                        </h6>
                                                    </div>
                                                    <small class="text-muted">
                                                        <?php echo date('M d, Y', strtotime($review['created_at'])); ?>
                                                    </small>
                                                </div>
                                                <p class="mb-1 mt-2 stars-display">
                                                    <?php
                                                    for ($i = 1; $i <= 5; $i++) {
                                                        echo $i <= $review['rating'] ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
                                                    }
                                                    ?>
                                                </p>
                                                <p class="mb-1"><?php echo htmlspecialchars($review['review_text']); ?></p>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery and Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        $(document).ready(function () {
            $('#sidebarCollapse').on('click', function () {
                $('#sidebar').toggleClass('active');
            });

            // Appointment Chart
            var ctx = document.getElementById('appointmentChart').getContext('2d');
            var appointmentChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($chart_labels); ?>,
                    datasets: [{
                        label: 'Appointments',
                        data: <?php echo json_encode($chart_data); ?>,
                        backgroundColor: 'rgba(52, 152, 219, 0.2)',
                        borderColor: 'rgba(52, 152, 219, 1)',
                        borderWidth: 2,
                        pointBackgroundColor: 'rgba(52, 152, 219, 1)',
                        pointRadius: 4,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    },
                    plugins: {
                        title: {
                            display: true,
                            text: 'Appointments by Date',
                            font: {
                                size: 16
                            }
                        },
                        legend: {
                            position: 'top',
                        }
                    }
                }
            });
        });
    </script>
</body>

</html>