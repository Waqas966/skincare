<?php
// Include config file
require_once "../config.php";

// Check if user is logged in and is a patient
require_patient();


$_SESSION["user_role"] = "patient"; // or "admin" or "patient"


// Get patient data
$user_id = $_SESSION['user_id'];

// Get all patient appointments with pagination
$limit = 10; // Number of records per page
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$start = ($page - 1) * $limit;

// Get total number of appointments
$count_sql = "SELECT COUNT(*) as total FROM appointments WHERE patient_id = ?";
$count_stmt = mysqli_prepare($conn, $count_sql);
mysqli_stmt_bind_param($count_stmt, "i", $user_id);
mysqli_stmt_execute($count_stmt);
$count_result = mysqli_stmt_get_result($count_stmt);
$total_records = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_records / $limit);

// Get appointments for current page
$sql = "SELECT a.*, u.first_name, u.last_name, s.specialization 
        FROM appointments a 
        LEFT JOIN users u ON a.specialist_id = u.id 
        LEFT JOIN specialists s ON u.id = s.user_id
        WHERE a.patient_id = ? 
        ORDER BY a.appointment_date DESC, a.appointment_time DESC
        LIMIT ?, ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "iii", $user_id, $start, $limit);
mysqli_stmt_execute($stmt);
$appointments = mysqli_stmt_get_result($stmt);

// Handle appointment cancellation
if (isset($_POST['cancel_appointment']) && isset($_POST['appointment_id'])) {
    $appointment_id = $_POST['appointment_id'];

    // Verify the appointment belongs to this patient
    $verify_sql = "SELECT * FROM appointments WHERE id = ? AND patient_id = ?";
    $verify_stmt = mysqli_prepare($conn, $verify_sql);
    mysqli_stmt_bind_param($verify_stmt, "ii", $appointment_id, $user_id);
    mysqli_stmt_execute($verify_stmt);
    $verify_result = mysqli_stmt_get_result($verify_stmt);

    if (mysqli_num_rows($verify_result) > 0) {
        $appointment = mysqli_fetch_assoc($verify_result);

        // Only allow cancellation of pending or confirmed appointments
        if ($appointment['status'] == 'pending' || $appointment['status'] == 'confirmed') {
            $update_sql = "UPDATE appointments SET status = 'cancelled' WHERE id = ?";
            $update_stmt = mysqli_prepare($conn, $update_sql);
            mysqli_stmt_bind_param($update_stmt, "i", $appointment_id);

            if (mysqli_stmt_execute($update_stmt)) {
                $success_message = "Appointment cancelled successfully.";
                // Redirect to avoid form resubmission
                header("Location: appointments.php?success=1");
                exit;
            } else {
                $error_message = "Error cancelling appointment. Please try again.";
            }
        } else {
            $error_message = "Only pending or confirmed appointments can be cancelled.";
        }
    } else {
        $error_message = "Invalid appointment.";
    }
}

// Set success message from redirect
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $success_message = "Appointment cancelled successfully.";
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

        /* Table styling */
        .table {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.05);
        }

        .table th {
            background-color: var(--light-bg);
            border-top: none;
        }

        /* Status badges */
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-pending {
            background-color: #ffeeba;
            color: #856404;
        }

        .status-confirmed {
            background-color: #d4edda;
            color: #155724;
        }

        .status-completed {
            background-color: #cce5ff;
            color: #004085;
        }

        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }

        /* Buttons */
        .btn-success {
            background-color: var(--primary-color);
            color: white;
            border: 2px solid var(--primary-color);
        }

        .btn-success:hover {
            background-color: transparent;
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
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
                    <h4 class="mb-0">My Appointments</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb bg-transparent p-0 mb-0">
                            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                            <li class="breadcrumb-item active" aria-current="page">My Appointments</li>
                        </ol>
                    </nav>
                </div>
            </div>

            <div class="container-fluid">
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success" role="alert">
                        <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>

                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-calendar-check mr-2"></i> All Appointments</h5>
                    </div>
                    <div class="card-body">
                        <!-- Filter options -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <form method="GET" action="appointments.php" class="form-inline">
                                    <select name="status" class="form-control mr-2">
                                        <option value="">All Statuses</option>
                                        <option value="pending" <?php echo isset($_GET['status']) && $_GET['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="confirmed" <?php echo isset($_GET['status']) && $_GET['status'] == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                        <option value="completed" <?php echo isset($_GET['status']) && $_GET['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                        <option value="cancelled" <?php echo isset($_GET['status']) && $_GET['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>
                                    <button type="submit" class="btn btn-outline-primary">Filter</button>
                                </form>
                            </div>
                            <div class="col-md-6 text-right">
                                <a href="book_appointment.php" class="btn btn-success">
                                    <i class="fas fa-calendar-plus mr-1"></i> Book New Appointment
                                </a>
                            </div>
                        </div>

                        <!-- Appointments table -->
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Date & Time</th>
                                        <th>Specialist</th>
                                        <th>Specialization</th>
                                        <th>Status</th>
                                        <th>Description</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (mysqli_num_rows($appointments) > 0): ?>
                                        <?php while ($appointment = mysqli_fetch_assoc($appointments)): ?>
                                            <tr>
                                                <td>
                                                    <?php
                                                    $date = new DateTime($appointment['appointment_date']);
                                                    echo $date->format('M d, Y');
                                                    ?>
                                                    <br>
                                                    <small>
                                                        <?php
                                                        $time = new DateTime($appointment['appointment_time']);
                                                        echo $time->format('h:i A');
                                                        ?>
                                                    </small>
                                                </td>
                                                <td>Dr.
                                                    <?php echo htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name']); ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($appointment['specialization'] ?? 'N/A'); ?>
                                                </td>
                                                <td>
                                                    <span class="status-badge status-<?php echo $appointment['status']; ?>">
                                                        <?php echo ucfirst($appointment['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($appointment['description']); ?></td>
                                                <td>
                                                    <?php if ($appointment['status'] == 'pending' || $appointment['status'] == 'confirmed'): ?>
                                                        <form method="POST" action="appointments.php"
                                                            onsubmit="return confirm('Are you sure you want to cancel this appointment?')">
                                                            <input type="hidden" name="appointment_id"
                                                                value="<?php echo $appointment['id']; ?>">
                                                            <button type="submit" name="cancel_appointment"
                                                                class="btn btn-sm btn-danger">
                                                                <i class="fas fa-times-circle mr-1"></i> Cancel
                                                            </button>
                                                        </form>
                                                        <!-- In your appointments.php file, replace the "Details" button with these lines in the actions column -->
                                                    <?php elseif ($appointment['status'] == 'completed'): ?>
                                                        <?php
                                                        // Check if review exists
                                                        $check_review_sql = "SELECT * FROM reviews WHERE appointment_id = ?";
                                                        $check_review_stmt = mysqli_prepare($conn, $check_review_sql);
                                                        mysqli_stmt_bind_param($check_review_stmt, "i", $appointment['id']);
                                                        mysqli_stmt_execute($check_review_stmt);
                                                        $review_exists = mysqli_num_rows(mysqli_stmt_get_result($check_review_stmt)) > 0;
                                                        ?>

                                                        <?php if ($review_exists): ?>
                                                            <div class="btn-group" role="group">
                                                                <a href="review.php?appointment_id=<?php echo $appointment['id']; ?>"
                                                                    class="btn btn-sm btn-warning">
                                                                    <i class="fas fa-edit mr-1"></i>
                                                                </a>
                                                                <a href="review.php?appointment_id=<?php echo $appointment['id']; ?>"
                                                                    class="btn btn-sm btn-danger">
                                                                    <i class="fas fa-trash-alt mr-1"></i>
                                                                </a>
                                                            </div>
                                                        <?php else: ?>
                                                            <div class="btn-group" role="group">
                                                                <a href="review.php?appointment_id=<?php echo $appointment['id']; ?>"
                                                                    class="btn btn-sm btn-success">
                                                                    <i class="fas fa-star mr-1"></i>
                                                                </a>
                                                            </div>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center">No appointments found.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <nav aria-label="Appointments pagination">
                                <ul class="pagination justify-content-center">
                                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $page - 1; ?>"
                                            tabindex="-1">Previous</a>
                                    </li>
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a>
                                    </li>
                                </ul>
                            </nav>
                        <?php endif; ?>
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
        });
    </script>
</body>

</html>