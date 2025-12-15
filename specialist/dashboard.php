<?php
// Include config file
require_once "../config.php";

// Check if user is logged in and is a specialist
require_specialist();


$_SESSION["user_role"] = "specialist"; // or "admin" or "patient"


// Get specialist information
$specialist_id = $_SESSION["user_id"];
$specialist_info_query = mysqli_query($conn, "SELECT u.*, s.experience, s.specialization 
                          FROM users u 
                          JOIN specialists s ON u.id = s.user_id 
                          WHERE u.id = $specialist_id");
$specialist_info = mysqli_fetch_assoc($specialist_info_query);

// Get upcoming appointments
$upcoming_appointments = mysqli_query($conn, "SELECT a.*, u.first_name, u.last_name, u.email, u.mobile 
                          FROM appointments a 
                          JOIN users u ON a.patient_id = u.id 
                          WHERE a.specialist_id = $specialist_id 
                          AND a.status IN ('pending', 'confirmed') 
                          AND a.appointment_date >= CURDATE() 
                          ORDER BY a.appointment_date ASC, a.appointment_time ASC 
                          LIMIT 10");

// Get today's appointments
$today_appointments = mysqli_query($conn, "SELECT a.*, u.first_name, u.last_name, u.email, u.mobile 
                       FROM appointments a 
                       JOIN users u ON a.patient_id = u.id 
                       WHERE a.specialist_id = $specialist_id 
                       AND a.status IN ('pending', 'confirmed') 
                       AND a.appointment_date = CURDATE() 
                       ORDER BY a.appointment_time ASC");

// Get appointment statistics
$total_appointments = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(*) as count FROM appointments WHERE specialist_id = $specialist_id"))[0];
$pending_appointments = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(*) as count FROM appointments WHERE specialist_id = $specialist_id AND status = 'pending'"))[0];
$completed_appointments = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(*) as count FROM appointments WHERE specialist_id = $specialist_id AND status = 'completed'"))[0];
$cancelled_appointments = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(*) as count FROM appointments WHERE specialist_id = $specialist_id AND status = 'cancelled'"))[0];

// Process appointment status updates
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['appointment_id']) && isset($_POST['status'])) {
    $appointment_id = intval($_POST['appointment_id']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    
    if (in_array($status, ['confirmed', 'completed', 'cancelled'])) {
        if (mysqli_query($conn, "UPDATE appointments SET status = '$status' WHERE id = $appointment_id AND specialist_id = $specialist_id")) {
            $_SESSION['success_msg'] = "Appointment status updated successfully.";
        } else {
            $_SESSION['error_msg'] = "Failed to update appointment status.";
        }
    }
    
    // Redirect to refresh the page
    header("Location: dashboard.php");
    exit;
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
            width:300px;
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
        .stat-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.05);
            padding: 20px;
            margin-bottom: 20px;
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card .icon {
            font-size: 48px;
            color: var(--primary-color);
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

        /* Tab styling */
        .nav-tabs .nav-link {
            border-radius: 8px 8px 0 0;
            font-weight: 500;
        }

        .nav-tabs .nav-link.active {
            background-color: var(--light-bg);
            color: var(--primary-color);
            border-bottom: 2px solid var(--primary-color);
        }

        /* Button styling */
        .btn-success {
            background-color: var(--success-color);
            border-color: var(--success-color);
        }

        .btn-danger {
            background-color: var(--danger-color);
            border-color: var(--danger-color);
        }

        /* Header */
        .dashboard-header {
            background-color: white;
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.05);
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
        
        /* Specialization labels */
        .specialization {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8em;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .skin_care {
            background-color: #e6f7ff;
            color: #0099cc;
        }
        
        .laser {
            background-color: #fff0f5;
            color: #ff3366;
        }
        
        .cosmetic {
            background-color: #f0fff0;
            color: #33cc33;
        }
        
        .hair {
            background-color: #fff8dc;
            color: #cc9900;
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
            <nav class="navbar navbar-expand-lg navbar-light bg-light dashboard-header">
                <div class="container-fluid">
                     <h4 class="mb-0">Specialist Dashboard</h4>
                    <div class="ml-auto">
                        <div class="dropdown">
                                <i class="fas fa-user-md mr-2"></i> 
                                <?php echo htmlspecialchars($specialist_info['first_name'] . ' ' . $specialist_info['last_name']); ?>
                        </div>
                    </div>
                </div>
            </nav>


            <!-- Welcome Section -->
            <div class="card mb-4">
                <div class="card-body">
                    <h2 class="card-title">Welcome, Dr. <?php echo htmlspecialchars($specialist_info['first_name'] . ' ' . $specialist_info['last_name']); ?></h2>
                    <p class="card-text">
                        <span class="specialization <?php echo $specialist_info['specialization']; ?>">
                            <?php echo ucwords(str_replace('_', ' ', $specialist_info['specialization'])); ?> Specialist
                        </span>
                        • <?php echo $specialist_info['experience']; ?> years of experience
                    </p>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i> You have <strong><?php echo $pending_appointments; ?></strong> pending appointments that need your attention.
                    </div>
                </div>
            </div>

            <!-- Dashboard Stats -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="stat-card text-center">
                        <div class="icon mb-3">
                            <i class="fas fa-calendar-day"></i>
                        </div>
                        <?php 
                        $today_count = mysqli_num_rows($today_appointments);
                        ?>
                        <h3><?php echo $today_count; ?></h3>
                        <p class="text-muted mb-0">Today's Appointments</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card text-center">
                        <div class="icon mb-3">
                            <i class="fas fa-hourglass-half"></i>
                        </div>
                        <h3><?php echo $pending_appointments; ?></h3>
                        <p class="text-muted mb-0">Pending Appointments</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card text-center">
                        <div class="icon mb-3">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <h3><?php echo $completed_appointments; ?></h3>
                        <p class="text-muted mb-0">Completed Treatments</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card text-center">
                        <div class="icon mb-3">
                            <i class="fas fa-users"></i>
                        </div>
                        <?php
                        $patient_count = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(DISTINCT patient_id) as count FROM appointments WHERE specialist_id = $specialist_id"))[0];
                        ?>
                        <h3><?php echo $patient_count; ?></h3>
                        <p class="text-muted mb-0">Total Patients</p>
                    </div>
                </div>
            </div>

            <div class="container-fluid">
                <?php
                // Display success/error messages
                if (isset($_SESSION['success_msg'])) {
                    echo '<div class="alert alert-success">' . $_SESSION['success_msg'] . '</div>';
                    unset($_SESSION['success_msg']);
                }

                if (isset($_SESSION['error_msg'])) {
                    echo '<div class="alert alert-danger">' . $_SESSION['error_msg'] . '</div>';
                    unset($_SESSION['error_msg']);
                }
                ?>

                <!-- Today's Appointments -->
                <div class="card mb-4">
                    <div class="card-header bg-white">
                        <h4 class="mb-0"><i class="fas fa-calendar-day mr-2"></i>Today's Appointments</h4>
                    </div>
                    <div class="card-body">
                        <?php if (mysqli_num_rows($today_appointments) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>Time</th>
                                            <th>Patient Name</th>
                                            <th>Contact</th>
                                            <th>Treatment</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($appointment = mysqli_fetch_assoc($today_appointments)): ?>
                                            <tr>
                                                <td><?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?></td>
                                                <td><?php echo htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name']); ?></td>
                                                <td>
                                                    <a href="tel:<?php echo htmlspecialchars($appointment['mobile']); ?>" class="text-decoration-none">
                                                        <i class="fas fa-phone-alt mr-1"></i> <?php echo htmlspecialchars($appointment['mobile']); ?>
                                                    </a><br>
                                                    <a href="mailto:<?php echo htmlspecialchars($appointment['email']); ?>" class="text-decoration-none">
                                                        <i class="fas fa-envelope mr-1"></i> <?php echo htmlspecialchars($appointment['email']); ?>
                                                    </a>
                                                </td>
                                                <td><?php echo htmlspecialchars($appointment['treatment_type']); ?></td>
                                                <td>
                                                    <?php 
                                                    $status_class = '';
                                                    switch($appointment['status']) {
                                                        case 'pending':
                                                            $status_class = 'badge-warning';
                                                            break;
                                                        case 'confirmed':
                                                            $status_class = 'badge-primary';
                                                            break;
                                                        case 'completed':
                                                            $status_class = 'badge-success';
                                                            break;
                                                        case 'cancelled':
                                                            $status_class = 'badge-danger';
                                                            break;
                                                    }
                                                    ?>
                                                    <span class="badge <?php echo $status_class; ?>">
                                                        <?php echo ucfirst($appointment['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($appointment['status'] == 'pending'): ?>
                                                        <form method="post" class="d-inline">
                                                            <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                                            <input type="hidden" name="status" value="confirmed">
                                                            <button type="submit" class="btn btn-primary btn-sm">
                                                                <i class="fas fa-check mr-1"></i> Confirm
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($appointment['status'] == 'confirmed'): ?>
                                                        <form method="post" class="d-inline">
                                                            <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                                            <input type="hidden" name="status" value="completed">
                                                            <button type="submit" class="btn btn-success btn-sm">
                                                                <i class="fas fa-check-double mr-1"></i> Complete
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($appointment['status'] == 'pending' || $appointment['status'] == 'confirmed'): ?>
                                                        <form method="post" class="d-inline ml-1">
                                                            <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                                            <input type="hidden" name="status" value="cancelled">
                                                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to cancel this appointment?')">
                                                                <i class="fas fa-times mr-1"></i> Cancel
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                   
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle mr-2"></i> You have no appointments scheduled for today.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Upcoming Appointments -->
                <div class="card mb-4">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h4 class="mb-0"><i class="fas fa-calendar-alt mr-2"></i>Upcoming Appointments</h4>
                    </div>
                    <div class="card-body">
                        <?php if (mysqli_num_rows($upcoming_appointments) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>Date & Time</th>
                                            <th>Patient Name</th>
                                            <th>Treatment</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($appointment = mysqli_fetch_assoc($upcoming_appointments)): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?></strong><br>
                                                    <?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name']); ?></td>
                                                <td><?php echo htmlspecialchars($appointment['treatment_type']); ?></td>
                                                <td>
                                                    <?php 
                                                    $status_class = '';
                                                    switch($appointment['status']) {
                                                        case 'pending':
                                                            $status_class = 'badge-warning';
                                                            break;
                                                        case 'confirmed':
                                                            $status_class = 'badge-primary';
                                                            break;
                                                    }
                                                    ?>
                                                    <span class="badge <?php echo $status_class; ?>">
                                                        <?php echo ucfirst($appointment['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($appointment['status'] == 'pending'): ?>
                                                        <form method="post" class="d-inline">
                                                            <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                                            <input type="hidden" name="status" value="confirmed">
                                                            <button type="submit" class="btn btn-primary btn-sm">
                                                                <i class="fas fa-check mr-1"></i> Confirm
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($appointment['status'] == 'pending' || $appointment['status'] == 'confirmed'): ?>
                                                        <form method="post" class="d-inline ml-1">
                                                            <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                                            <input type="hidden" name="status" value="cancelled">
                                                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to cancel this appointment?')">
                                                                <i class="fas fa-times mr-1"></i> Cancel
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                    
                                                    
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle mr-2"></i> You have no upcoming appointments.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Links -->
    
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

            // Initialize tooltips
            $('[data-toggle="tooltip"]').tooltip();
        });
    </script>

</body>
</html>