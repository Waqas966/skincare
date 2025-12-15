<?php
// Include config file
require_once "../config.php";

// Check if user is admin
require_admin();

$_SESSION["user_role"] = "admin"; // or "admin" 


// Process edit/delete actions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action']) && isset($_POST['appointment_id'])) {
        $appointment_id = intval($_POST['appointment_id']);

        if ($_POST['action'] == 'delete') {
            // Delete appointment
            if (delete_appointment($appointment_id)) {
                $_SESSION['success_msg'] = "Appointment deleted successfully.";
            } else {
                $_SESSION['error_msg'] = "Failed to delete appointment.";
            }
        } elseif ($_POST['action'] == 'update_status' && isset($_POST['new_status'])) {
            // Update appointment status
            $new_status = $_POST['new_status'];
            if (update_appointment_status($appointment_id, $new_status)) {
                $_SESSION['success_msg'] = "Appointment status updated successfully.";
            } else {
                $_SESSION['error_msg'] = "Failed to update appointment status.";
            }
        }

        // Redirect to refresh the page
        header("Location: all_appointments.php");
        exit;
    }
}

// Filter variables
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$specialist_filter = isset($_GET['specialist']) ? intval($_GET['specialist']) : 0;
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Get all appointments with filters
$appointments = get_all_appointments($status_filter, $specialist_filter, $date_from, $date_to);

// Get all specialists for filter dropdown
$specialists = get_all_specialists();

// Helper functions
function get_all_appointments($status = 'all', $specialist_id = 0, $date_from = '', $date_to = '')
{
    global $conn;

    $sql = "SELECT a.*, 
                 p.first_name AS patient_first_name, p.last_name AS patient_last_name, 
                 s.first_name AS specialist_first_name, s.last_name AS specialist_last_name, 
                 spec.specialization 
          FROM appointments a 
          JOIN users p ON a.patient_id = p.id 
          LEFT JOIN users s ON a.specialist_id = s.id 
          LEFT JOIN specialists spec ON s.id = spec.user_id";

    $conditions = [];
    $params = [];
    $types = "";

    // Add filters
    if ($status != 'all') {
        $conditions[] = "a.status = ?";
        $params[] = $status;
        $types .= "s";
    }

    if ($specialist_id > 0) {
        $conditions[] = "a.specialist_id = ?";
        $params[] = $specialist_id;
        $types .= "i";
    }

    if (!empty($date_from)) {
        $conditions[] = "a.appointment_date >= ?";
        $params[] = $date_from;
        $types .= "s";
    }

    if (!empty($date_to)) {
        $conditions[] = "a.appointment_date <= ?";
        $params[] = $date_to;
        $types .= "s";
    }

    // Build the WHERE clause
    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }

    // Add order by
    $sql .= " ORDER BY a.appointment_date ASC, a.appointment_time ASC";

    $stmt = $conn->prepare($sql);

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

function get_all_specialists()
{
    global $conn;
    $sql = "SELECT u.id, u.first_name, u.last_name, s.specialization 
            FROM users u 
            JOIN specialists s ON u.id = s.user_id 
            WHERE u.user_type = 'specialist' AND u.approval_status = 'approved'
            ORDER BY u.first_name ASC";
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

function delete_appointment($appointment_id)
{
    global $conn;

    $sql = "DELETE FROM appointments WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $appointment_id);
    return $stmt->execute() && $stmt->affected_rows > 0;
}

function update_appointment_status($appointment_id, $new_status)
{
    global $conn;

    $sql = "UPDATE appointments SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $new_status, $appointment_id);
    return $stmt->execute() && $stmt->affected_rows > 0;
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
            return 'Cosmetic Procedure';
        case 'hair':
            return 'Hair Treatment';
        default:
            return ucfirst($specialization);
    }
}

// Function to get status badge class
function get_status_badge_class($status)
{
    switch ($status) {
        case 'pending':
            return 'badge-warning';
        case 'confirmed':
            return 'badge-primary';
        case 'completed':
            return 'badge-success';
        case 'cancelled':
            return 'badge-danger';
        default:
            return 'badge-secondary';
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
    <!-- Datepicker CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css">
    <style>
        :root {
            --primary-color: #4a90e2;
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

        /* Filter card */
        .filter-card {
            margin-bottom: 20px;
        }

        /* Status badges */
        .status-badge {
            font-size: 85%;
            padding: 0.35em 0.65em;
        }

        .btn-primary {
    background-color: var(--primary-color);
    color: white;
    border: 2px solid var(--primary-color);
}

.btn-primary:hover {
    background-color: transparent;
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
                    <h4 class="mb-0">All Appointments</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb bg-transparent p-0 mb-0">
                            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                            <li class="breadcrumb-item active" aria-current="page">All Appointments</li>
                        </ol>
                    </nav>
                </div>
            </div>
            

            <!-- Display success/error messages -->
            <?php if (isset($_SESSION['success_msg'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['success_msg'];
                        unset($_SESSION['success_msg']); ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_msg'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['error_msg'];
                        unset($_SESSION['error_msg']); ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
            <?php endif; ?>

            <!-- Filters -->
            <div class="card filter-card">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-filter mr-2"></i>Filter Appointments</h5>
                </div>
                <div class="card-body">
                    <form method="get" action="all_appointments.php" class="row">
                        <!-- Status Filter -->
                        <div class="form-group col-md-3">
                            <label for="status">Status</label>
                            <select class="form-control" id="status" name="status">
                                <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Statuses</option>
                                <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="confirmed" <?php echo $status_filter == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                        
                        <!-- Specialist Filter -->
                        <div class="form-group col-md-3">
                            <label for="specialist">Specialist</label>
                            <select class="form-control" id="specialist" name="specialist">
                                <option value="0">All Specialists</option>
                                <?php foreach ($specialists as $specialist): ?>
                                        <option value="<?php echo $specialist['id']; ?>" <?php echo $specialist_filter == $specialist['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($specialist['first_name'] . ' ' . $specialist['last_name']); ?> 
                                            (<?php echo format_specialization($specialist['specialization']); ?>)
                                        </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Date From Filter -->
                        <div class="form-group col-md-3">
                            <label for="date_from">Date From</label>
                            <input type="text" class="form-control datepicker" id="date_from" name="date_from" 
                                   value="<?php echo htmlspecialchars($date_from); ?>" placeholder="YYYY-MM-DD">
                        </div>
                        
                        <!-- Date To Filter -->
                        <div class="form-group col-md-3">
                            <label for="date_to">Date To</label>
                            <input type="text" class="form-control datepicker" id="date_to" name="date_to" 
                                   value="<?php echo htmlspecialchars($date_to); ?>" placeholder="YYYY-MM-DD">
                        </div>
                        
                        <div class="col-12 mt-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search mr-2"></i>Apply Filters
                            </button>
                            <a href="all_appointments.php" class="btn btn-secondary ml-2">
                                <i class="fas fa-redo mr-2"></i>Reset Filters
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Appointments Table -->
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-calendar-check mr-2"></i>Appointments</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($appointments)): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle mr-2"></i> No appointments found with the selected filters.
                            </div>
                    <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Patient</th>
                                            <th>Specialist</th>
                                            <th>Date & Time</th>
                                            <th>Treatment Type</th>
                                            <th>Status</th>
                                            <th>Created At</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        
                                        <?php 
                                         $counter = 1;
                                        foreach ($appointments as $appointment): ?>
                                                <tr>
                                                    <td><?php echo $counter++; ?></td>
                                                    <td><?php echo htmlspecialchars($appointment['patient_first_name'] . ' ' . $appointment['patient_last_name']); ?></td>
                                                    <td>
                                                        <?php if ($appointment['specialist_id']): ?>
                                                                <?php echo htmlspecialchars($appointment['specialist_first_name'] . ' ' . $appointment['specialist_last_name']); ?>
                                                        <?php else: ?>
                                                                <span class="text-muted">Not Assigned</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($appointment['appointment_date'] . ' at ' . substr($appointment['appointment_time'], 0, 5)); ?></td>
                                                    <td><?php echo htmlspecialchars($appointment['treatment_type']); ?></td>
                                                    <td>
                                                        <span class="badge status-badge <?php echo get_status_badge_class($appointment['status']); ?>">
                                                            <?php echo ucfirst($appointment['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo date('Y-m-d H:i', strtotime($appointment['created_at'])); ?></td>
                                                    <td>
                                                        <div class="btn-group">
                                                            <button type="button" class="btn btn-sm btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                                Actions
                                                            </button>
                                                            <div class="dropdown-menu">
                                                                <?php if ($appointment['status'] != 'pending'): ?>
                                                                        <form method="post" class="dropdown-item-form">
                                                                            <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                                                            <input type="hidden" name="action" value="update_status">
                                                                            <input type="hidden" name="new_status" value="pending">
                                                                            <button type="submit" class="dropdown-item text-warning">
                                                                                <i class="fas fa-clock mr-2"></i>Set as Pending
                                                                            </button>
                                                                        </form>
                                                                <?php endif; ?>
                                                                <?php if ($appointment['status'] != 'confirmed'): ?>
                                                                        <form method="post" class="dropdown-item-form">
                                                                            <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                                                            <input type="hidden" name="action" value="update_status">
                                                                            <input type="hidden" name="new_status" value="confirmed">
                                                                            <button type="submit" class="dropdown-item text-primary">
                                                                                <i class="fas fa-check mr-2"></i>Set as Confirmed
                                                                            </button>
                                                                        </form>
                                                                <?php endif; ?>
                                                                <?php if ($appointment['status'] != 'completed'): ?>
                                                                        <form method="post" class="dropdown-item-form">
                                                                            <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                                                            <input type="hidden" name="action" value="update_status">
                                                                            <input type="hidden" name="new_status" value="completed">
                                                                            <button type="submit" class="dropdown-item text-success">
                                                                                <i class="fas fa-check-double mr-2"></i>Set as Completed
                                                                            </button>
                                                                        </form>
                                                                <?php endif; ?>
                                                                <?php if ($appointment['status'] != 'cancelled'): ?>
                                                                        <form method="post" class="dropdown-item-form">
                                                                            <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                                                            <input type="hidden" name="action" value="update_status">
                                                                            <input type="hidden" name="new_status" value="cancelled">
                                                                            <button type="submit" class="dropdown-item text-danger">
                                                                                <i class="fas fa-times mr-2"></i>Set as Cancelled
                                                                            </button>
                                                                        </form>
                                                                <?php endif; ?>
                        
                                                                <form method="post" class="dropdown-item-form" onsubmit="return confirm('Are you sure you want to delete this appointment? This action cannot be undone.')">
                                                                    <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                                                    <input type="hidden" name="action" value="delete">
                                                                    <button type="submit" class="dropdown-item text-danger">
                                                                        <i class="fas fa-trash mr-2"></i>Delete
                                                                    </button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Datepicker JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>

    <script>
        $(document).ready(function () {
            // Initialize datepicker
            $('.datepicker').datepicker({
                format: 'yyyy-mm-dd',
                autoclose: true,
                todayHighlight: true
            });

            // Auto-dismiss alerts after 5 seconds
            window.setTimeout(function() {
                $(".alert").fadeTo(500, 0).slideUp(500, function(){
                    $(this).remove(); 
                });
            }, 5000);

            // Style dropdown item forms to make them work correctly
            $('.dropdown-item-form').on('click', function(e) {
                e.stopPropagation();
            });
        });
    </script>
</body>

</html>