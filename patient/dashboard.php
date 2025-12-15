<?php
// Include config file
require_once "../config.php";

// Check if user is logged in and is a patient
require_patient();



$_SESSION["user_role"] = "patient"; // or "admin" or "patient"


// Get patient data
$user_id = $_SESSION['user_id'];
$sql = "SELECT u.*, p.certificate FROM users u 
        LEFT JOIN patients p ON u.id = p.user_id 
        WHERE u.id = ? AND u.user_type = 'patient'";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    header("Location: ../logout.php");
    exit;
}

$patient = mysqli_fetch_assoc($result);

// Get patient appointments
$sql = "SELECT a.*, u.first_name, u.last_name 
        FROM appointments a 
        LEFT JOIN users u ON a.specialist_id = u.id 
        WHERE a.patient_id = ? 
        ORDER BY a.appointment_date DESC, a.appointment_time DESC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$appointments = mysqli_stmt_get_result($stmt);
$total_appointments = mysqli_num_rows($appointments);

// Get counts for status-based statistics
$pending_count = 0;
$confirmed_count = 0;
$completed_count = 0;
$cancelled_count = 0;

$sql = "SELECT status, COUNT(*) as count FROM appointments WHERE patient_id = ? GROUP BY status";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$status_result = mysqli_stmt_get_result($stmt);

while ($row = mysqli_fetch_assoc($status_result)) {
    switch ($row['status']) {
        case 'pending':
            $pending_count = $row['count'];
            break;
        case 'confirmed':
            $confirmed_count = $row['count'];
            break;
        case 'completed':
            $completed_count = $row['count'];
            break;
        case 'cancelled':
            $cancelled_count = $row['count'];
            break;
    }
}

// Get upcoming appointments (limit to 5)
$sql = "SELECT a.*, u.first_name, u.last_name, s.specialization 
        FROM appointments a 
        LEFT JOIN users u ON a.specialist_id = u.id 
        LEFT JOIN specialists s ON u.id = s.user_id
        WHERE a.patient_id = ? AND (a.status = 'pending' OR a.status = 'confirmed') 
        AND a.appointment_date >= CURDATE()
        ORDER BY a.appointment_date ASC, a.appointment_time ASC
        LIMIT 5";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$upcoming_appointments = mysqli_stmt_get_result($stmt);
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

        /* Profile card */
        .profile-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }

        .profile-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
        }

        .profile-body {
            padding: 20px;
        }

        .profile-pic {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            margin-right: 20px;
        }

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

        /* Appointment card */
        .appointment-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 15px;
            padding: 15px;
            transition: transform 0.3s;
        }

        .appointment-card:hover {
            transform: translateY(-2px);
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
            <nav class="navbar navbar-expand-lg navbar-light bg-light dashboard-header">
                <div class="container-fluid">

                    <div class="ml-auto">
                        <div class="dropdown">
                            <i class="fas fa-user-circle mr-1"></i>
                            <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?>

                        </div>
                    </div>
                </div>
            </nav>

            <!-- Dashboard Stats -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="stat-card text-center">
                        <div class="icon mb-3">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <h3><?php echo $total_appointments; ?></h3>
                        <p class="text-muted mb-0">Total Appointments</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card text-center">
                        <div class="icon mb-3">
                            <i class="fas fa-hourglass-half"></i>
                        </div>
                        <h3><?php echo $pending_count; ?></h3>
                        <p class="text-muted mb-0">Pending Appointments</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card text-center">
                        <div class="icon mb-3">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <h3><?php echo $confirmed_count; ?></h3>
                        <p class="text-muted mb-0">Confirmed Appointments</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card text-center">
                        <div class="icon mb-3">
                            <i class="fas fa-clipboard-check"></i>
                        </div>
                        <h3><?php echo $completed_count; ?></h3>
                        <p class="text-muted mb-0">Completed Treatments</p>
                    </div>
                </div>
            </div>

            <div class="container-fluid">
                <div class="row">
                    <!-- Profile Summary Card -->
                    <div class="col-lg-4">
                        <div class="profile-card">
                            <div class="profile-header">
                                <h4 class="mb-0"><i class="fas fa-user-circle mr-2"></i>My Profile</h4>
                            </div>
                            <div class="profile-body">
                                <div class="d-flex align-items-center mb-4">
                                    <div class="profile-pic">
                                        <?php
                                        $initials = strtoupper(substr($patient['first_name'], 0, 1) . substr($patient['last_name'], 0, 1));
                                        echo $initials;
                                        ?>
                                    </div>
                                    <div>
                                        <h4><?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?>
                                        </h4>
                                        <p class="text-muted mb-0">
                                            <i class="fas fa-map-marker-alt mr-1"></i>
                                            <?php echo htmlspecialchars($patient['city'] . ', ' . $patient['state']); ?>
                                        </p>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <h6 class="text-muted">Email</h6>
                                    <p><i
                                            class="fas fa-envelope mr-2"></i><?php echo htmlspecialchars($patient['email']); ?>
                                    </p>
                                </div>

                                <div class="mb-3">
                                    <h6 class="text-muted">Mobile</h6>
                                    <p><i
                                            class="fas fa-phone mr-2"></i><?php echo htmlspecialchars($patient['mobile']); ?>
                                    </p>
                                </div>

                                <div class="mb-3">
                                    <h6 class="text-muted">CNIC</h6>
                                    <p><i
                                            class="fas fa-id-card mr-2"></i><?php echo htmlspecialchars($patient['cnic']); ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Upcoming Appointments and History -->
                    <div class="col-lg-8">
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="fas fa-calendar-check mr-2"></i>Upcoming Appointments</h5>
                            </div>
                            <div class="card-body">
                                <?php if (mysqli_num_rows($upcoming_appointments) > 0): ?>
                                    <?php while ($appointment = mysqli_fetch_assoc($upcoming_appointments)): ?>
                                        <div class="appointment-card">
                                            <div class="row">
                                                <div class="col-md-8">
                                                    <h5>
                                                        <?php echo htmlspecialchars($appointment['treatment_type']); ?>
                                                        <span class="status-badge status-<?php echo $appointment['status']; ?>">
                                                            <?php echo ucfirst($appointment['status']); ?>
                                                        </span>
                                                    </h5>
                                                    <p class="mb-1">
                                                        <i class="fas fa-calendar mr-2"></i>
                                                        <?php echo date('l, F d, Y', strtotime($appointment['appointment_date'])); ?>
                                                    </p>
                                                    <p class="mb-1">
                                                        <i class="fas fa-clock mr-2"></i>
                                                        <?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?>
                                                    </p>
                                                    <?php if (!empty($appointment['first_name'])): ?>
                                                        <p class="mb-1">
                                                            <i class="fas fa-user-md mr-2"></i>
                                                            Dr.
                                                            <?php echo htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name']); ?>
                                                            <?php if (isset($appointment['specialization'])): ?>
                                                                <span class="badge badge-info ml-1">
                                                                    <?php echo ucfirst(str_replace('_', ' ', $appointment['specialization'])); ?>
                                                                </span>
                                                            <?php endif; ?>
                                                        </p>
                                                    <?php else: ?>
                                                        <p class="text-muted mb-1">
                                                            <i class="fas fa-user-md mr-2"></i>
                                                            No specialist assigned yet
                                                        </p>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="col-md-4 text-right">
                                                    <?php if ($appointment['status'] == 'pending' || $appointment['status'] == 'confirmed'): ?>
                                                        <a href="cancel_appointment.php?id=<?php echo $appointment['id']; ?>"
                                                            onclick="return confirm('Are you sure you want to cancel this appointment?')"
                                                            class="btn btn-danger btn-sm">
                                                            <i class="fas fa-times mr-1"></i> Cancel
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                    <div class="text-center mt-3">
                                        <a href="appointments.php" class="btn btn-outline-primary">
                                            <i class="fas fa-list mr-1"></i> View All Appointments
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle mr-2"></i> You have no upcoming appointments.
                                        <a href="book_appointment.php" class="alert-link">Book an appointment now</a>.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="fas fa-clipboard-list mr-2"></i>Appointment History</h5>
                            </div>
                            <div class="card-body">
                                <ul class="nav nav-tabs mb-4" id="appointmentTabs" role="tablist">
                                    <li class="nav-item">
                                        <a class="nav-link active" id="all-tab" data-toggle="tab" href="#all">
                                            <i class="fas fa-list mr-1"></i> All
                                            <span
                                                class="badge badge-primary ml-1"><?php echo $total_appointments; ?></span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" id="pending-tab" data-toggle="tab" href="#pending">
                                            <i class="fas fa-hourglass-half mr-1"></i> Pending
                                            <span class="badge badge-warning ml-1"><?php echo $pending_count; ?></span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" id="confirmed-tab" data-toggle="tab" href="#confirmed">
                                            <i class="fas fa-check-circle mr-1"></i> Confirmed
                                            <span
                                                class="badge badge-success ml-1"><?php echo $confirmed_count; ?></span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" id="completed-tab" data-toggle="tab" href="#completed">
                                            <i class="fas fa-clipboard-check mr-1"></i> Completed
                                            <span class="badge badge-info ml-1"><?php echo $completed_count; ?></span>
                                        </a>
                                    </li>
                                </ul>

                                <div class="tab-content">
                                    <div class="tab-pane fade show active" id="all">
                                        <?php
                                        mysqli_data_seek($appointments, 0); // Reset the appointments result set
                                        
                                        if (mysqli_num_rows($appointments) > 0):
                                            ?>
                                            <div class="table-responsive">
                                                <table class="table table-bordered table-striped">
                                                    <thead>
                                                        <tr>
                                                            <th>Date & Time</th>
                                                            <th>Treatment</th>
                                                            <th>Specialist</th>
                                                            <th>Status</th>
                                                            <th>Actions</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php while ($appointment = mysqli_fetch_assoc($appointments)): ?>
                                                            <tr>
                                                                <td>
                                                                    <?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?>
                                                                    <br>
                                                                    <small class="text-muted">
                                                                        <?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?>
                                                                    </small>
                                                                </td>
                                                                <td><?php echo htmlspecialchars($appointment['treatment_type']); ?>
                                                                </td>
                                                                <td>
                                                                    <?php if (!empty($appointment['first_name'])): ?>
                                                                        Dr.
                                                                        <?php echo htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name']); ?>
                                                                    <?php else: ?>
                                                                        <span class="text-muted">Not assigned</span>
                                                                    <?php endif; ?>
                                                                </td>
                                                                <td>
                                                                    <?php
                                                                    $status_class = '';
                                                                    $status_icon = '';
                                                                    switch ($appointment['status']) {
                                                                        case 'pending':
                                                                            $status_class = 'warning';
                                                                            $status_icon = 'hourglass-half';
                                                                            break;
                                                                        case 'confirmed':
                                                                            $status_class = 'success';
                                                                            $status_icon = 'check-circle';
                                                                            break;
                                                                        case 'completed':
                                                                            $status_class = 'info';
                                                                            $status_icon = 'clipboard-check';
                                                                            break;
                                                                        case 'cancelled':
                                                                            $status_class = 'danger';
                                                                            $status_icon = 'times-circle';
                                                                            break;
                                                                    }
                                                                    ?>
                                                                    <span class="badge badge-<?php echo $status_class; ?>">
                                                                        <i class="fas fa-<?php echo $status_icon; ?> mr-1"></i>
                                                                        <?php echo ucfirst($appointment['status']); ?>
                                                                    </span>
                                                                </td>
                                                                <td>
                                                                    <a href="appointments.php?id=<?php echo $appointment['id']; ?>"
                                                                        class="btn btn-info btn-sm">
                                                                        <i class="fas fa-eye"></i>
                                                                    </a>

                                                                    <?php if ($appointment['status'] == 'pending' || $appointment['status'] == 'confirmed'): ?>
                                                                        <a href="cancel_appointment.php?id=<?php echo $appointment['id']; ?>"
                                                                            onclick="return confirm('Are you sure you want to cancel this appointment?')"
                                                                            class="btn btn-danger btn-sm">
                                                                            <i class="fas fa-times"></i>
                                                                        </a>
                                                                    <?php endif; ?>
                                                                </td>
                                                            </tr>
                                                        <?php endwhile; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        <?php else: ?>
                                            <div class="alert alert-info">
                                                <i class="fas fa-info-circle mr-2"></i> You have no appointment history.
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="tab-pane fade" id="pending">
                                        <?php
                                        // Filter pending appointments
                                        mysqli_data_seek($appointments, 0);
                                        $has_pending = false;
                                        ?>
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Date & Time</th>
                                                        <th>Treatment</th>
                                                        <th>Specialist</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    while ($appointment = mysqli_fetch_assoc($appointments)) {
                                                        if ($appointment['status'] == 'pending') {
                                                            $has_pending = true;
                                                            ?>
                                                            <tr>
                                                                <td>
                                                                    <?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?>
                                                                    <br>
                                                                    <small class="text-muted">
                                                                        <?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?>
                                                                    </small>
                                                                </td>
                                                                <td><?php echo htmlspecialchars($appointment['treatment_type']); ?>
                                                                </td>
                                                                <td>
                                                                    <?php if (!empty($appointment['first_name'])): ?>
                                                                        Dr.
                                                                        <?php echo htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name']); ?>
                                                                    <?php else: ?>
                                                                        <span class="text-muted">Not assigned</span>
                                                                    <?php endif; ?>
                                                                </td>
                                                                <td>
                                                                    <a href="appointments.php?id=<?php echo $appointment['id']; ?>"
                                                                        class="btn btn-info btn-sm">
                                                                        <i class="fas fa-eye"></i>
                                                                    </a>

                                                                    <a href="cancel_appointment.php?id=<?php echo $appointment['id']; ?>"
                                                                        onclick="return confirm('Are you sure you want to cancel this appointment?')"
                                                                        class="btn btn-danger btn-sm">
                                                                        <i class="fas fa-times"></i>
                                                                    </a>
                                                                </td>
                                                            </tr>
                                                            <?php
                                                        }
                                                    }

                                                    if (!$has_pending) {
                                                        echo '<tr><td colspan="4" class="text-center">No pending appointments found.</td></tr>';
                                                    }
                                                    ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <!-- Similar structure for confirmed tab -->
                                    <div class="tab-pane fade" id="confirmed">
                                        <?php
                                        // Filter confirmed appointments
                                        mysqli_data_seek($appointments, 0);
                                        $has_confirmed = false;
                                        ?>
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Date & Time</th>
                                                        <th>Treatment</th>
                                                        <th>Specialist</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    while ($appointment = mysqli_fetch_assoc($appointments)) {
                                                        if ($appointment['status'] == 'confirmed') {
                                                            $has_confirmed = true;
                                                            ?>
                                                            <tr>
                                                                <td>
                                                                    <?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?>
                                                                    <br>
                                                                    <small class="text-muted">
                                                                        <?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?>
                                                                    </small>
                                                                </td>
                                                                <td><?php echo htmlspecialchars($appointment['treatment_type']); ?>
                                                                </td>
                                                                <td>
                                                                    <?php if (!empty($appointment['first_name'])): ?>
                                                                        Dr.
                                                                        <?php echo htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name']); ?>
                                                                    <?php else: ?>
                                                                        <span class="text-muted">Not assigned</span>
                                                                    <?php endif; ?>
                                                                </td>
                                                                <td>
                                                                    <a href="appointments.php?id=<?php echo $appointment['id']; ?>"
                                                                        class="btn btn-info btn-sm">
                                                                        <i class="fas fa-eye"></i>
                                                                    </a>

                                                                    <a href="cancel_appointment.php?id=<?php echo $appointment['id']; ?>"
                                                                        onclick="return confirm('Are you sure you want to cancel this appointment?')"
                                                                        class="btn btn-danger btn-sm">
                                                                        <i class="fas fa-times"></i>
                                                                    </a>
                                                                </td>
                                                            </tr>
                                                            <?php
                                                        }
                                                    }

                                                    if (!$has_confirmed) {
                                                        echo '<tr><td colspan="4" class="text-center">No confirmed appointments found.</td></tr>';
                                                    }
                                                    ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <!-- Completed tab -->
                                    <div class="tab-pane fade" id="completed">
                                        <?php
                                        // Filter completed appointments
                                        mysqli_data_seek($appointments, 0);
                                        $has_completed = false;
                                        ?>
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Date & Time</th>
                                                        <th>Treatment</th>
                                                        <th>Specialist</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    while ($appointment = mysqli_fetch_assoc($appointments)) {
                                                        if ($appointment['status'] == 'completed') {
                                                            $has_completed = true;
                                                            ?>
                                                            <tr>
                                                                <td>
                                                                    <?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?>
                                                                    <br>
                                                                    <small class="text-muted">
                                                                        <?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?>
                                                                    </small>
                                                                </td>
                                                                <td><?php echo htmlspecialchars($appointment['treatment_type']); ?>
                                                                </td>
                                                                <td>
                                                                    <?php if (!empty($appointment['first_name'])): ?>
                                                                        Dr.
                                                                        <?php echo htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name']); ?>
                                                                    <?php else: ?>
                                                                        <span class="text-muted">Not assigned</span>
                                                                    <?php endif; ?>
                                                                </td>
                                                                <td>
                                                                    <a href="appointments.php?id=<?php echo $appointment['id']; ?>"
                                                                        class="btn btn-info btn-sm">
                                                                        <i class="fas fa-eye"></i>
                                                                    </a>
                                                                </td>
                                                            </tr>
                                                            <?php
                                                        }
                                                    }

                                                    if (!$has_completed) {
                                                        echo '<tr><td colspan="4" class="text-center">No completed appointments found.</td></tr>';
                                                    }
                                                    ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.min.js"></script>

    <!-- Custom JavaScript -->
    <script>
        $(document).ready(function () {
            // Toggle sidebar
            $('#sidebarCollapse').on('click', function () {
                $('#sidebar').toggleClass('active');
            });
        });
    </script>
</body>

</html>