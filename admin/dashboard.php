<?php
// Include config file
require_once "../config.php";

// Check if user is admin
require_admin();


$_SESSION["user_role"] = "admin"; // or "admin" 

// Process approval/rejection actions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action']) && isset($_POST['user_id'])) {
        $user_id = intval($_POST['user_id']);

        if ($_POST['action'] == 'approve') {
            if (approve_patient($user_id)) {
                $_SESSION['success_msg'] = "Patient registration approved successfully.";
            } else {
                $_SESSION['error_msg'] = "Failed to approve patient registration.";
            }
        } elseif ($_POST['action'] == 'reject') {
            if (reject_patient($user_id)) {
                $_SESSION['success_msg'] = "Patient registration rejected.";
            } else {
                $_SESSION['error_msg'] = "Failed to reject patient registration.";
            }
        }

        // Redirect to refresh the page
        header("Location: dashboard.php");
        exit;
    }
}

// Get all pending patient registrations
$pending_patients = get_pending_patients();


// include "header.php";
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
                    <h4 class="mb-0">Admin Dashboard</h4>
                    <div class="ml-auto">
                        <div class="dropdown">
                            <i class="fa fa-user-shield"></i> Admin
                        </div>
                    </div>
                </div>
            </nav>





            <!-- Dashboard Stats -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="stat-card text-center">
                        <div class="icon mb-3">
                            <i class="fas fa-user-clock"></i>
                        </div>
                        <h3><?php echo mysqli_num_rows($pending_patients); ?></h3>
                        <p class="text-muted mb-0">Pending Patients</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card text-center">
                        <div class="icon mb-3">
                            <i class="fas fa-user-check"></i>
                        </div>
                        <?php
                        $approved_count = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM users WHERE user_type = 'patient' AND approval_status = 'approved'"));
                        ?>
                        <h3><?php echo $approved_count; ?></h3>
                        <p class="text-muted mb-0">Approved Patients</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card text-center">
                        <div class="icon mb-3">
                            <i class="fas fa-user-times"></i>
                        </div>
                        <?php
                        $rejected_count = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM users WHERE user_type = 'patient' AND approval_status = 'rejected'"));
                        ?>
                        <h3><?php echo $rejected_count; ?></h3>
                        <p class="text-muted mb-0">Rejected Patients</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card text-center">
                        <div class="icon mb-3">
                            <i class="fas fa-users"></i>
                        </div>
                        <?php
                        $total_count = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM users WHERE user_type = 'patient'"));
                        ?>
                        <h3><?php echo $total_count; ?></h3>
                        <p class="text-muted mb-0">Total Patients</p>
                    </div>
                </div>
            </div>


            <!-- Doctor/Specialist Stats -->
            <div class="row mb-4">

                <!-- Total Specialists Card -->
                <div class="col-md-3">
                    <div class="stat-card text-center">
                        <div class="icon mb-3">
                            <i class="fas fa-user-md"></i>
                        </div>
                        <?php
                        $total_specialists = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM users WHERE user_type = 'specialist'"));
                        ?>
                        <h3><?php echo $total_specialists; ?></h3>
                        <p class="text-muted mb-0">Total Specialists</p>
                    </div>
                </div>

                <!-- Pending Appointments Card -->
                <div class="col-md-3">
                    <div class="stat-card text-center">
                        <div class="icon mb-3">
                            <i class="fas fa-hourglass-half "></i>
                        </div>
                        <?php
                        $pending_appointments = mysqli_num_rows(mysqli_query(
                            $conn,
                            "SELECT id FROM appointments WHERE status = 'pending'"
                        ));
                        ?>
                        <h3><?php echo $pending_appointments; ?></h3>
                        <p class="text-muted mb-0">Pending Appointments</p>
                    </div>
                </div>

                <!-- Confirmed Appointments Card -->
                <div class="col-md-3">
                    <div class="stat-card text-center">
                        <div class="icon mb-3">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <?php
                        $confirmed_appointments = mysqli_num_rows(mysqli_query(
                            $conn,
                            "SELECT id FROM appointments WHERE status = 'confirmed'"
                        ));
                        ?>
                        <h3><?php echo $confirmed_appointments; ?></h3>
                        <p class="text-muted mb-0">Confirmed Appointments</p>
                    </div>
                </div>

                <!-- Completed Appointments Card -->
                <div class="col-md-3">
                    <div class="stat-card text-center">
                        <div class="icon mb-3">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <?php
                        $completed_appointments = mysqli_num_rows(mysqli_query(
                            $conn,
                            "SELECT id FROM appointments WHERE status = 'completed'"
                        ));
                        ?>
                        <h3><?php echo $completed_appointments; ?></h3>
                        <p class="text-muted mb-0">Completed Appointments</p>
                    </div>
                </div>
</div>

                <div class="container-fluid">
                    <h2 class="mb-4"><i class="fas fa-user-plus mr-2"></i>Manage Patient Registrations</h2>
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

                    <ul class="nav nav-tabs mb-4">
                        <li class="nav-item">
                            <a class="nav-link active" href="#pending" data-toggle="tab">
                                <i class="fas fa-hourglass-half mr-1"></i> Pending
                                <span
                                    class="badge badge-primary ml-1"><?php echo mysqli_num_rows($pending_patients); ?></span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#approved" data-toggle="tab">
                                <i class="fas fa-check-circle mr-1"></i> Approved
                                <span class="badge badge-success ml-1"><?php echo $approved_count; ?></span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#rejected" data-toggle="tab">
                                <i class="fas fa-times-circle mr-1"></i> Rejected
                                <span class="badge badge-danger ml-1"><?php echo $rejected_count; ?></span>
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <div id="pending" class="tab-pane fade show active">
                            <div class="card">
                                <div class="card-header bg-white">
                                    <h4 class="mb-0">Pending Patient Registrations</h4>
                                </div>
                                <div class="card-body">
                                    <?php if (mysqli_num_rows($pending_patients) > 0): ?>
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Name</th>
                                                        <th>Email</th>
                                                        <th>Mobile</th>
                                                        <th>CNIC</th>
                                                        <th>Location</th>
                                                        <th>Certificate</th>
                                                        <th>Registration Date</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php while ($patient = mysqli_fetch_assoc($pending_patients)): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?>
                                                            </td>
                                                            <td><?php echo htmlspecialchars($patient['email']); ?></td>
                                                            <td><?php echo htmlspecialchars($patient['mobile']); ?></td>
                                                            <td><?php echo htmlspecialchars($patient['cnic']); ?></td>
                                                            <td><?php echo htmlspecialchars($patient['city'] . ', ' . $patient['state']); ?>
                                                            </td>
                                                            <td>
                                                                <?php if (!empty($patient['certificate'])): ?>
                                                                    <a href="../uploads/certificates/<?php echo htmlspecialchars($patient['certificate']); ?>"
                                                                        onclick="viewCertificate('../uploads/certificates/<?php echo htmlspecialchars($patient['certificate']); ?>'); return false;"
                                                                        target="blank" class="btn btn-sm btn-info">
                                                                        <i class="fas fa-file-medical mr-1"></i> View
                                                                    </a>
                                                                <?php else: ?>
                                                                    <span class="badge badge-secondary">No certificate</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td><?php echo date('M d, Y', strtotime($patient['created_at'])); ?>
                                                            </td>
                                                            <td>
                                                                <form method="post" class="d-inline">
                                                                    <input type="hidden" name="user_id"
                                                                        value="<?php echo $patient['id']; ?>">
                                                                    <input type="hidden" name="action" value="approve">
                                                                    <button type="submit" class="btn btn-success btn-sm"
                                                                        onclick="return confirm('Are you sure you want to approve this registration?')">
                                                                        <i class="fas fa-check mr-1"></i> Approve
                                                                    </button>
                                                                </form>
                                                                <form method="post" class="d-inline ml-1">
                                                                    <input type="hidden" name="user_id"
                                                                        value="<?php echo $patient['id']; ?>">
                                                                    <input type="hidden" name="action" value="reject">
                                                                    <button type="submit" class="btn btn-danger btn-sm"
                                                                        onclick="return confirm('Are you sure you want to reject this registration?')">
                                                                        <i class="fas fa-times mr-1"></i> Reject
                                                                    </button>
                                                                </form>
                                                            </td>
                                                        </tr>
                                                    <?php endwhile; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle mr-2"></i> No pending patient registrations found.
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div id="approved" class="tab-pane fade">
                            <div class="card">
                                <div class="card-header bg-white">
                                    <h4 class="mb-0">Approved Patients</h4>
                                </div>
                                <div class="card-body">
                                    <?php
                                    $approved_patients = mysqli_query($conn, "SELECT u.*, p.certificate 
                                                     FROM users u 
                                                     LEFT JOIN patients p ON u.id = p.user_id 
                                                     WHERE u.user_type = 'patient' AND u.approval_status = 'approved'");

                                    if (mysqli_num_rows($approved_patients) > 0): ?>
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Name</th>
                                                        <th>Email</th>
                                                        <th>Mobile</th>
                                                        <th>CNIC</th>
                                                        <th>Location</th>
                                                        <th>Certificate</th>
                                                        <th>Registration Date</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php while ($patient = mysqli_fetch_assoc($approved_patients)): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?>
                                                            </td>
                                                            <td><?php echo htmlspecialchars($patient['email']); ?></td>
                                                            <td><?php echo htmlspecialchars($patient['mobile']); ?></td>
                                                            <td><?php echo htmlspecialchars($patient['cnic']); ?></td>
                                                            <td><?php echo htmlspecialchars($patient['city'] . ', ' . $patient['state']); ?>
                                                            </td>
                                                            <td>
                                                                <?php if (!empty($patient['certificate'])): ?>
                                                                    <a href="../uploads/certificates/<?php echo htmlspecialchars($patient['certificate']); ?>"
                                                                        onclick=" viewCertificate('../uploads/certificates/<?php echo htmlspecialchars($patient['certificate']); ?>');
                                                            return false;" class="btn btn-sm btn-info">
                                                                        <i class="fas fa-file-medical mr-1"></i> View
                                                                    </a>
                                                                <?php else: ?>
                                                                    <span class="badge badge-secondary">No certificate</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td><?php echo date('M d, Y', strtotime($patient['created_at'])); ?>
                                                            </td>
                                                        </tr>
                                                    <?php endwhile; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle mr-2"></i> No approved patients found.
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div id="rejected" class="tab-pane fade">
                            <div class="card">
                                <div class="card-header bg-white">
                                    <h4 class="mb-0">Rejected Patients</h4>
                                </div>
                                <div class="card-body">
                                    <?php
                                    $rejected_patients = mysqli_query($conn, "SELECT u.*, p.certificate 
                                                     FROM users u 
                                                     LEFT JOIN patients p ON u.id = p.user_id 
                                                     WHERE u.user_type = 'patient' AND u.approval_status = 'rejected'");

                                    if (mysqli_num_rows($rejected_patients) > 0): ?>
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Name</th>
                                                        <th>Email</th>
                                                        <th>Mobile</th>
                                                        <th>CNIC</th>
                                                        <th>Location</th>
                                                        <th>Certificate</th>
                                                        <th>Registration Date</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php while ($patient = mysqli_fetch_assoc($rejected_patients)): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?>
                                                            </td>
                                                            <td><?php echo htmlspecialchars($patient['email']); ?></td>
                                                            <td><?php echo htmlspecialchars($patient['mobile']); ?></td>
                                                            <td><?php echo htmlspecialchars($patient['cnic']); ?></td>
                                                            <td><?php echo htmlspecialchars($patient['city'] . ', ' . $patient['state']); ?>
                                                            </td>
                                                            <td>
                                                                <?php if (!empty($patient['certificate'])): ?>
                                                                    <a href="../uploads/certificates/<?php echo htmlspecialchars($patient['certificate']); ?>"
                                                                        onclick=" viewCertificate('../uploads/certificates/<?php echo htmlspecialchars($patient['certificate']); ?>');
                                                                                                                return false;"
                                                                        class="btn btn-sm btn-info">
                                                                        <i class="fas fa-file-medical mr-1"></i> View
                                                                    </a>
                                                                <?php else: ?>
                                                                    <span class="badge badge-secondary">No certificate</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td><?php echo date('M d, Y', strtotime($patient['created_at'])); ?>
                                                            </td>
                                                        </tr>
                                                    <?php endwhile; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle mr-2"></i> No rejected patients found.
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

        <!-- Certificate viewer modal -->
        <?php
        // Include certificate viewer
        include "certificate_viewer.php";


        ?>

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

<?php
// No need for footer.php as we've included everything in this file
// include "footer.php";
?>