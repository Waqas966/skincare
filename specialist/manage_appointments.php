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

// Default filter values
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$date_filter = isset($_GET['date']) ? $_GET['date'] : '';
$search_query = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Build the query conditions based on filters
$where_conditions = ["a.specialist_id = $specialist_id"];  // This ensures only this specialist's appointments are shown

if ($status_filter !== 'all') {
    $status_filter = mysqli_real_escape_string($conn, $status_filter);
    $where_conditions[] = "a.status = '$status_filter'";
}

if (!empty($date_filter)) {
    $date_filter = mysqli_real_escape_string($conn, $date_filter);
    $where_conditions[] = "a.appointment_date = '$date_filter'";
}

if (!empty($search_query)) {
    $search_query = mysqli_real_escape_string($conn, $search_query);
    $where_conditions[] = "(u.first_name LIKE '%$search_query%' 
                          OR u.last_name LIKE '%$search_query%' 
                          OR u.email LIKE '%$search_query%' 
                          OR u.mobile LIKE '%$search_query%'
                          OR a.treatment_type LIKE '%$search_query%')";
}

$where_clause = implode(' AND ', $where_conditions);

// Get total records count for pagination
$total_records_query = "SELECT COUNT(*) as count 
                      FROM appointments a 
                      JOIN users u ON a.patient_id = u.id 
                      WHERE $where_clause";

$total_records_result = mysqli_query($conn, $total_records_query);
$total_records = mysqli_fetch_assoc($total_records_result)['count'];
$total_pages = ceil($total_records / $per_page);

// Get appointments based on filters with pagination
$appointments_query = "SELECT a.*, u.first_name, u.last_name, u.email, u.mobile 
                     FROM appointments a 
                     JOIN users u ON a.patient_id = u.id 
                     WHERE $where_clause 
                     ORDER BY a.appointment_date DESC, a.appointment_time DESC 
                     LIMIT $offset, $per_page";

$appointments = mysqli_query($conn, $appointments_query);

// Process appointment status updates
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action']) && $_POST['action'] == 'update_status' && isset($_POST['appointment_id']) && isset($_POST['status'])) {
        $appointment_id = intval($_POST['appointment_id']);
        $status = mysqli_real_escape_string($conn, $_POST['status']);

        if (in_array($status, ['confirmed', 'completed', 'cancelled'])) {
            if (mysqli_query($conn, "UPDATE appointments SET status = '$status' WHERE id = $appointment_id AND specialist_id = $specialist_id")) {
                $_SESSION['success_msg'] = "Appointment status updated successfully.";
            } else {
                $_SESSION['error_msg'] = "Failed to update appointment status: " . mysqli_error($conn);
            }
        }

        // Redirect to refresh the page with the same filters
        $redirect_url = "manage_appointments.php";
        $query_params = [];

        if ($status_filter !== 'all')
            $query_params[] = "status=$status_filter";
        if (!empty($date_filter))
            $query_params[] = "date=$date_filter";
        if (!empty($search_query))
            $query_params[] = "search=$search_query";
        if ($page > 1)
            $query_params[] = "page=$page";

        if (!empty($query_params)) {
            $redirect_url .= '?' . implode('&', $query_params);
        }

        header("Location: $redirect_url");
        exit;
    }

    // Process bulk actions
    if (isset($_POST['action']) && $_POST['action'] == 'bulk_action' && isset($_POST['bulk_action']) && isset($_POST['appointment_ids'])) {
        $bulk_action = mysqli_real_escape_string($conn, $_POST['bulk_action']);
        $appointment_ids = $_POST['appointment_ids'];

        if (!empty($appointment_ids) && in_array($bulk_action, ['confirm', 'complete', 'cancel'])) {
            $status_map = [
                'confirm' => 'confirmed',
                'complete' => 'completed',
                'cancel' => 'cancelled'
            ];

            $status = $status_map[$bulk_action];
            $ids = implode(',', array_map('intval', $appointment_ids));

            $update_query = "UPDATE appointments SET status = '$status' 
                           WHERE id IN ($ids) AND specialist_id = $specialist_id";

            if (mysqli_query($conn, $update_query)) {
                $count = mysqli_affected_rows($conn);
                $_SESSION['success_msg'] = "$count appointments were updated successfully.";
            } else {
                $_SESSION['error_msg'] = "Failed to update appointments: " . mysqli_error($conn);
            }
        } else {
            $_SESSION['error_msg'] = "Invalid bulk action or no appointments selected.";
        }

        // Redirect to refresh the page with the same filters
        header("Location: manage_appointments.php?status=$status_filter&date=$date_filter&search=$search_query&page=$page");
        exit;
    }
}

// Get list of distinct appointment dates for the filter dropdown
$dates_query = "SELECT DISTINCT appointment_date FROM appointments 
               WHERE specialist_id = $specialist_id 
               ORDER BY appointment_date DESC 
               LIMIT 30";
$dates_result = mysqli_query($conn, $dates_query);
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
    <!-- DatePicker CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css">
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

        /* Button styling */
        .btn-success {
            background-color: var(--success-color);
            border-color: var(--success-color);
        }

        .btn-danger {
            background-color: var(--danger-color);
            border-color: var(--danger-color);
        }

        /* Card styling */
        .card {
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }

        .card-header {
            background-color: var(--light-bg);
            border-bottom: 1px solid rgba(0, 0, 0, 0.125);
            padding: 15px 20px;
        }

        /* Filter section */
        .filter-section {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.05);
        }

        /* Status badges */
        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.85em;
            font-weight: 500;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-confirmed {
            background-color: #cce5ff;
            color: #004085;
        }

        .status-completed {
            background-color: #d4edda;
            color: #155724;
        }

        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }

        /* Pagination styling */
        .pagination .page-item.active .page-link {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .pagination .page-link {
            color: var(--primary-color);
        }

        /* Notes section */
        .appointment-notes {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
            font-style: italic;
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
        <!-- Sidebar  -->
           <!-- Dashboard Sidebar  -->
     <?php
        require "../src/sidebar.php";
        ?>


        <!-- Page Content  -->
        <div id="content">
                   <div class="dashboard-header d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-0">Manage Appointments</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb bg-transparent p-0 mb-0">
                            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Manage Appointments</li>
                        </ol>
                    </nav>
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

                <!-- Filter Section -->
                <div class="filter-section">
                    <form method="get" class="row align-items-end">
                        <div class="col-md-3 form-group">
                            <label for="status">Status Filter:</label>
                            <select name="status" id="status" class="form-control">
                                <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Statuses</option>
                                <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="confirmed" <?php echo $status_filter == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>

                        <div class="col-md-3 form-group">
                            <label for="date">Date Filter:</label>
                            <select name="date" id="date" class="form-control">
                                <option value="">All Dates</option>
                                <?php 
                                while ($date_row = mysqli_fetch_assoc($dates_result)) {
                                    $date_value = $date_row['appointment_date'];
                                    $formatted_date = date('M d, Y', strtotime($date_value));
                                    $selected = ($date_filter == $date_value) ? 'selected' : '';
                                    echo "<option value=\"$date_value\" $selected>$formatted_date</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="col-md-4 form-group">
                            <label for="search">Search:</label>
                            <input type="text" name="search" id="search" class="form-control" placeholder="Search name, email, phone, treatment..." value="<?php echo htmlspecialchars($search_query); ?>">
                        </div>

                        <div class="col-md-2 form-group">
                            <button type="submit" class="btn btn-primary btn-block">Apply Filters</button>
                        </div>
                    </form>
                </div>

                <!-- Bulk Actions -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="post" id="bulk-action-form">
                            <input type="hidden" name="action" value="bulk_action">
                            <div class="row align-items-center">
                                <div class="col-md-4">
                                    <select name="bulk_action" class="form-control">
                                        <option value="">Select Bulk Action</option>
                                        <option value="confirm">Confirm Selected</option>
                                        <option value="complete">Complete Selected</option>
                                        <option value="cancel">Cancel Selected</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-secondary" onclick="return confirm('Are you sure you want to perform this action on the selected appointments?')">Apply</button>
                                </div>
                                <div class="col-md-6 text-right">
                                    <span class="text-muted">
                                        Showing <?php echo min($total_records, ($page-1)*$per_page+1); ?> to 
                                        <?php echo min($total_records, $page*$per_page); ?> of 
                                        <?php echo $total_records; ?> appointments
                                    </span>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Appointments Table -->
                <div class="card">
                    <div class="card-body p-0">
                        <?php if (mysqli_num_rows($appointments) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th width="30px">
                                                <div class="custom-control custom-checkbox">
                                                    <input type="checkbox" class="custom-control-input" id="select-all">
                                                    <label class="custom-control-label" for="select-all"></label>
                                                </div>
                                            </th>
                                            <th>Date & Time</th>
                                            <th>Patient Details</th>
                                            <th>Treatment</th>
                                            <th>Status</th>
                                            <th>Notes</th>
                                            <th width="180px">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($appointment = mysqli_fetch_assoc($appointments)): ?>
                                            <tr>
                                                <td>
                                                    <div class="custom-control custom-checkbox">
                                                        <input type="checkbox" class="custom-control-input appointment-checkbox" 
                                                               id="appointment-<?php echo $appointment['id']; ?>" 
                                                               name="appointment_ids[]" 
                                                               value="<?php echo $appointment['id']; ?>" 
                                                               form="bulk-action-form">
                                                        <label class="custom-control-label" for="appointment-<?php echo $appointment['id']; ?>"></label>
                                                    </div>
                                                </td>
                                                <td>
                                                    <strong><?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?></strong><br>
                                                    <?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?>
                                                </td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name']); ?></strong><br>
                                                    <small>
                                                        <a href="tel:<?php echo htmlspecialchars($appointment['mobile']); ?>" class="text-decoration-none">
                                                            <i class="fas fa-phone-alt mr-1"></i> <?php echo htmlspecialchars($appointment['mobile']); ?>
                                                        </a><br>
                                                        <a href="mailto:<?php echo htmlspecialchars($appointment['email']); ?>" class="text-decoration-none">
                                                            <i class="fas fa-envelope mr-1"></i> <?php echo htmlspecialchars($appointment['email']); ?>
                                                        </a>
                                                    </small>
                                                </td>
                                                <td>
                                                    <?php echo htmlspecialchars($appointment['treatment_type']); ?>
                                                </td>
                                                <td>
                                                    <?php 
                                                    $status_class = '';
                                                    switch($appointment['status']) {
                                                        case 'pending':
                                                            $status_class = 'status-pending';
                                                            break;
                                                        case 'confirmed':
                                                            $status_class = 'status-confirmed';
                                                            break;
                                                        case 'completed':
                                                            $status_class = 'status-completed';
                                                            break;
                                                        case 'cancelled':
                                                            $status_class = 'status-cancelled';
                                                            break;
                                                    }
                                                    ?>
                                                    <span class="status-badge <?php echo $status_class; ?>">
                                                        <?php echo ucfirst($appointment['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if (!empty($appointment['description'])): ?>
                                                        <div class="appointment-notes">
                                                            <?php echo nl2br(htmlspecialchars($appointment['description'])); ?>
                                                        </div>
                                                    <?php else: ?>
                                                        <small class="text-muted">No notes</small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group">
                                                        <button type="button" class="btn btn-sm btn-info dropdown-toggle" data-toggle="dropdown">
                                                            Actions
                                                        </button>
                                                        <div class="dropdown-menu">
                                                            <?php if ($appointment['status'] == 'pending'): ?>
                                                                <form method="post" class="dropdown-item-form">
                                                                    <input type="hidden" name="action" value="update_status">
                                                                    <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                                                    <input type="hidden" name="status" value="confirmed">
                                                                    <button type="submit" class="dropdown-item">
                                                                        <i class="fas fa-check text-primary mr-1"></i> Confirm
                                                                    </button>
                                                                </form>
                                                            <?php endif; ?>
                                                            
                                                            <?php if ($appointment['status'] == 'confirmed'): ?>
                                                                <form method="post" class="dropdown-item-form">
                                                                    <input type="hidden" name="action" value="update_status">
                                                                    <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                                                    <input type="hidden" name="status" value="completed">
                                                                    <button type="submit" class="dropdown-item">
                                                                        <i class="fas fa-check-double text-success mr-1"></i> Complete
                                                                    </button>
                                                                </form>
                                                            <?php endif; ?>
                                                            
                                                            <?php if ($appointment['status'] == 'pending' || $appointment['status'] == 'confirmed'): ?>
                                                                <form method="post" class="dropdown-item-form">
                                                                    <input type="hidden" name="action" value="update_status">
                                                                    <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                                                    <input type="hidden" name="status" value="cancelled">
                                                                    <button type="submit" class="dropdown-item" onclick="return confirm('Are you sure you want to cancel this appointment?')">
                                                                        <i class="fas fa-times text-danger mr-1"></i> Cancel
                                                                    </button>
                                                                </form>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info m-3">
                                <i class="fas fa-info-circle mr-2"></i> No appointments found with the current filters.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="d-flex justify-content-center mt-4">
                        <nav aria-label="Page navigation">
                            <ul class="pagination">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?status=<?php echo $status_filter; ?>&date=<?php echo $date_filter; ?>&search=<?php echo $search_query; ?>&page=<?php echo $page-1; ?>">
                                            Previous
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php
                                $start_page = max(1, $page - 2);
                                $end_page = min($total_pages, $page + 2);
                                
                                for ($i = $start_page; $i <= $end_page; $i++):
                                ?>
                                    <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                        <a class="page-link" href="?status=<?php echo $status_filter; ?>&date=<?php echo $date_filter; ?>&search=<?php echo $search_query; ?>&page=<?php echo $i; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?status=<?php echo $status_filter; ?>&date=<?php echo $date_filter; ?>&search=<?php echo $search_query; ?>&page=<?php echo $page+1; ?>">
                                            Next
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>




<!-- JavaScript Dependencies -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>

<script>
    $(document).ready(function() {
        // Toggle Sidebar
        $('#sidebarCollapse').on('click', function() {
            $('#sidebar').toggleClass('active');
            $('#content').toggleClass('active');
        });
        
        // Select All Checkbox
        $('#select-all').change(function() {
            $('.appointment-checkbox').prop('checked', $(this).prop('checked'));
        });
        
        // Update Select All when individual checkboxes change
        $('.appointment-checkbox').change(function() {
            if ($('.appointment-checkbox:checked').length === $('.appointment-checkbox').length) {
                $('#select-all').prop('checked', true);
            } else {
                $('#select-all').prop('checked', false);
            }
        });
        
       
        
        // DatePicker
        $('.datepicker').datepicker({
            format: 'yyyy-mm-dd',
            autoclose: true,
            todayHighlight: true
        });
        
        // Disable bulk action form submission if no action selected
        $('#bulk-action-form').submit(function(e) {
            if ($('select[name="bulk_action"]').val() === '') {
                e.preventDefault();
                alert('Please select a bulk action.');
                return false;
            }
            
            if ($('.appointment-checkbox:checked').length === 0) {
                e.preventDefault();
                alert('Please select at least one appointment.');
                return false;
            }
        });
    });
</script>

</body>
</html>