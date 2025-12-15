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

// Get all unique patients for this specialist
$patients_query = mysqli_query($conn, "SELECT DISTINCT u.id, u.first_name, u.last_name, u.email, u.mobile, 
                              (SELECT COUNT(*) FROM appointments WHERE patient_id = u.id AND specialist_id = $specialist_id) as appointment_count,
                              (SELECT MAX(appointment_date) FROM appointments WHERE patient_id = u.id AND specialist_id = $specialist_id) as last_visit
                              FROM users u 
                              JOIN appointments a ON u.id = a.patient_id 
                              WHERE a.specialist_id = $specialist_id 
                              ORDER BY last_visit DESC");

// Search functionality
$search_query = "";
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['search']) && !empty($_GET['search'])) {
    $search_query = mysqli_real_escape_string($conn, $_GET['search']);
    $patients_query = mysqli_query($conn, "SELECT DISTINCT u.id, u.first_name, u.last_name, u.email, u.mobile, 
                                (SELECT COUNT(*) FROM appointments WHERE patient_id = u.id AND specialist_id = $specialist_id) as appointment_count,
                                (SELECT MAX(appointment_date) FROM appointments WHERE patient_id = u.id AND specialist_id = $specialist_id) as last_visit
                                FROM users u 
                                JOIN appointments a ON u.id = a.patient_id 
                                WHERE a.specialist_id = $specialist_id 
                                AND (u.first_name LIKE '%$search_query%' OR u.last_name LIKE '%$search_query%' OR u.email LIKE '%$search_query%' OR u.mobile LIKE '%$search_query%')
                                ORDER BY last_visit DESC");
}

// Handle adding patient notes
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['patient_id']) && isset($_POST['notes'])) {
    $patient_id = intval($_POST['patient_id']);
    $notes = mysqli_real_escape_string($conn, $_POST['notes']);
    $date = date('Y-m-d H:i:s');

    // Check if notes already exist for this patient
    $check_notes = mysqli_query($conn, "SELECT id FROM patient_notes WHERE patient_id = $patient_id AND specialist_id = $specialist_id");

    if (mysqli_num_rows($check_notes) > 0) {
        // Update existing notes
        $note_id = mysqli_fetch_assoc($check_notes)['id'];
        if (mysqli_query($conn, "UPDATE patient_notes SET notes = '$notes', updated_at = '$date' WHERE id = $note_id")) {
            $_SESSION['success_msg'] = "Patient notes updated successfully.";
        } else {
            $_SESSION['error_msg'] = "Failed to update patient notes.";
        }
    } else {
        // Create new notes
        if (mysqli_query($conn, "INSERT INTO patient_notes (patient_id, specialist_id, notes, created_at, updated_at) VALUES ($patient_id, $specialist_id, '$notes', '$date', '$date')")) {
            $_SESSION['success_msg'] = "Patient notes added successfully.";
        } else {
            $_SESSION['error_msg'] = "Failed to add patient notes.";
        }
    }

    // Redirect to refresh the page
    header("Location: patients.php");
    exit;
}

// Get patient details if viewing a specific patient
$patient_details = null;
$patient_appointments = null;
$patient_notes = null;

if (isset($_GET['patient_id']) && !empty($_GET['patient_id'])) {
    $patient_id = intval($_GET['patient_id']);

    // Get patient information
    $patient_details_query = mysqli_query($conn, "SELECT u.*, 
                                  (SELECT COUNT(*) FROM appointments WHERE patient_id = u.id AND specialist_id = $specialist_id) as total_appointments,
                                  (SELECT COUNT(*) FROM appointments WHERE patient_id = u.id AND specialist_id = $specialist_id AND status = 'completed') as completed_appointments,
                                  (SELECT MAX(appointment_date) FROM appointments WHERE patient_id = u.id AND specialist_id = $specialist_id) as last_visit_date
                                  FROM users u 
                                  WHERE u.id = $patient_id");

    if (mysqli_num_rows($patient_details_query) > 0) {
        $patient_details = mysqli_fetch_assoc($patient_details_query);

        // Get patient appointments
        $patient_appointments = mysqli_query($conn, "SELECT a.*, 
                                  (SELECT notes FROM appointment_notes WHERE appointment_id = a.id LIMIT 1) as appointment_notes
                                  FROM appointments a
                                  WHERE a.patient_id = $patient_id AND a.specialist_id = $specialist_id
                                  ORDER BY a.appointment_date DESC, a.appointment_time DESC");

        // Get patient notes
        $patient_notes_query = mysqli_query($conn, "SELECT * FROM patient_notes WHERE patient_id = $patient_id AND specialist_id = $specialist_id LIMIT 1");
        if (mysqli_num_rows($patient_notes_query) > 0) {
            $patient_notes = mysqli_fetch_assoc($patient_notes_query);
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

        /* Dashboard cards */
        .patient-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s;
            margin-bottom: 20px;
            overflow: hidden;
        }

        .patient-card:hover {
            transform: translateY(-5px);
        }

        .patient-card .card-header {
            background-color: var(--primary-color);
            color: white;
            font-weight: 500;
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

        /* Header */
        .dashboard-header {
            background-color: white;
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.05);
        }

        /* Patient profile */
        .patient-profile {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }

        .patient-profile .avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background-color: var(--light-bg);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 48px;
            color: var(--primary-color);
        }

        .patient-info-card {
            background-color: var(--light-bg);
            border-left: 4px solid var(--primary-color);
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 5px;
        }

        .appointment-card {
            border-left: 5px solid;
            margin-bottom: 15px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .appointment-pending {
            border-left-color: #ffc107;
        }

        .appointment-confirmed {
            border-left-color: #007bff;
        }

        .appointment-completed {
            border-left-color: #28a745;
        }

        .appointment-cancelled {
            border-left-color: #dc3545;
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
                    <h4 class="mb-0">My Patients</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb bg-transparent p-0 mb-0">
                            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                            <li class="breadcrumb-item active" aria-current="page">My Patients</li>
                        </ol>
                    </nav>
                </div>
            </div>

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

            <?php if (!$patient_details): ?>
                <!-- Patients List View -->
                <div class="card mb-4">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h4 class="mb-0"><i class="fas fa-user-injured mr-2"></i>My Patients</h4>
                        <form class="form-inline" method="GET">
                            <div class="input-group">
                                <input type="text" name="search" class="form-control" placeholder="Search patients..."
                                    value="<?php echo htmlspecialchars($search_query); ?>">
                                <div class="input-group-append">
                                    <button class="btn btn-outline-primary" type="submit">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="card-body">
                        <?php if (mysqli_num_rows($patients_query) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Patient Name</th>
                                            <th>Contact Information</th>
                                            <th>Last Visit</th>
                                            <th>Total Appointments</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($patient = mysqli_fetch_assoc($patients_query)): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar-sm bg-light rounded-circle text-center mr-3 d-flex align-items-center justify-content-center"
                                                            style="width: 40px; height: 40px;">
                                                            <i class="fas fa-user text-primary"></i>
                                                        </div>
                                                        <div>
                                                            <h6 class="mb-0">
                                                                <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?>
                                                            </h6>
                                                            <?php
                                                            if (!empty($patient['date_of_birth'])) {
                                                                $dob = new DateTime($patient['date_of_birth']);
                                                                $now = new DateTime();
                                                                $age = $now->diff($dob)->y;
                                                                echo '<small class="text-muted">' . $age . ' years old</small>';
                                                            }
                                                            ?>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <i class="fas fa-phone-alt text-muted mr-1"></i>
                                                    <?php echo htmlspecialchars($patient['mobile']); ?><br>
                                                    <i class="fas fa-envelope text-muted mr-1"></i>
                                                    <?php echo htmlspecialchars($patient['email']); ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    if (!empty($patient['last_visit'])) {
                                                        echo date('M d, Y', strtotime($patient['last_visit']));

                                                        // Calculate days since last visit
                                                        $last_visit = new DateTime($patient['last_visit']);
                                                        $today = new DateTime('today');
                                                        $days_since = $today->diff($last_visit)->days;

                                                        echo "<br><small class='text-muted'>$days_since days ago</small>";
                                                    } else {
                                                        echo "N/A";
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <span
                                                        class="badge badge-pill badge-info"><?php echo $patient['appointment_count']; ?></span>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle mr-2"></i> No patients found.
                                <?php if (!empty($search_query)): ?>
                                    <a href="My_patients.php" class="alert-link">Clear search</a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <!-- Single Patient View -->
                <div class="mb-3">
                    <a href="My_patients.php" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left mr-1"></i> Back to Patients List
                    </a>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <!-- Patient Profile Card -->
                        <div class="patient-profile text-center">
                            <div class="avatar">
                                <i class="fas fa-user"></i>
                            </div>
                            <h4><?php echo htmlspecialchars($patient_details['first_name'] . ' ' . $patient_details['last_name']); ?>
                            </h4>
                            <p class="text-muted mb-3">
                                <?php
                                if (!empty($patient_details['date_of_birth'])) {
                                    $dob = new DateTime($patient_details['date_of_birth']);
                                    $now = new DateTime();
                                    $age = $now->diff($dob)->y;
                                    echo $age . ' years old';
                                }
                                ?>
                            </p>

                            <hr>

                            <div class="patient-info text-left">
                                <h5>Contact Information</h5>
                                <div class="patient-info-card">
                                    <p><i class="fas fa-phone-alt mr-2"></i>
                                        <?php echo htmlspecialchars($patient_details['mobile']); ?></p>
                                    <p class="mb-0"><i class="fas fa-envelope mr-2"></i>
                                        <?php echo htmlspecialchars($patient_details['email']); ?></p>
                                </div>

                                <h5>Treatment History</h5>
                                <div class="patient-info-card">
                                    <p><strong>Total Appointments:</strong>
                                        <?php echo $patient_details['total_appointments']; ?></p>
                                    <p><strong>Completed Treatments:</strong>
                                        <?php echo $patient_details['completed_appointments']; ?></p>
                                    <p class="mb-0"><strong>Last Visit:</strong>
                                        <?php
                                        if (!empty($patient_details['last_visit_date'])) {
                                            echo date('M d, Y', strtotime($patient_details['last_visit_date']));
                                        } else {
                                            echo 'N/A';
                                        }
                                        ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-8">
                        <!-- Patient Notes -->
                        <div class="card mb-4">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="fas fa-sticky-note mr-2"></i> Patient Notes</h5>
                            </div>
                            <div class="card-body">
                                <form method="post">
                                    <input type="hidden" name="patient_id" value="<?php echo $patient_details['id']; ?>">
                                    <div class="form-group">
                                        <textarea name="notes" class="form-control" rows="5"
                                            placeholder="Add notes about this patient's condition, treatments, and follow-up recommendations..."><?php echo isset($patient_notes['notes']) ? htmlspecialchars($patient_notes['notes']) : ''; ?></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save mr-1"></i> Save Notes
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Appointment History -->
                        <div class="card">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="fas fa-history mr-2"></i> Appointment History</h5>
                            </div>
                            <div class="card-body">
                                <?php if (mysqli_num_rows($patient_appointments) > 0): ?>
                                    <?php while ($appointment = mysqli_fetch_assoc($patient_appointments)): ?>
                                        <?php
                                        $status_class = '';
                                        switch ($appointment['status']) {
                                            case 'pending':
                                                $status_class = 'appointment-pending';
                                                break;
                                            case 'confirmed':
                                                $status_class = 'appointment-confirmed';
                                                break;
                                            case 'completed':
                                                $status_class = 'appointment-completed';
                                                break;
                                            case 'cancelled':
                                                $status_class = 'appointment-cancelled';
                                                break;
                                        }
                                        ?>
                                        <div class="card appointment-card <?php echo $status_class; ?>">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between">
                                                    <div>
                                                        <h6 class="mb-1">
                                                            <?php echo htmlspecialchars($appointment['treatment_type']); ?></h6>
                                                        <p class="mb-1 text-muted">
                                                            <i class="far fa-calendar-alt mr-1"></i>
                                                            <?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?>
                                                            at
                                                            <?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?>
                                                        </p>
                                                        <?php if (!empty($appointment['appointment_notes'])): ?>
                                                            <div class="mt-2">
                                                                <strong>Notes:</strong>
                                                                <p class="mb-0">
                                                                    <?php echo nl2br(htmlspecialchars($appointment['appointment_notes'])); ?>
                                                                </p>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div>
                                                        <?php
                                                        $badge_class = '';
                                                        switch ($appointment['status']) {
                                                            case 'pending':
                                                                $badge_class = 'badge-warning';
                                                                break;
                                                            case 'confirmed':
                                                                $badge_class = 'badge-primary';
                                                                break;
                                                            case 'completed':
                                                                $badge_class = 'badge-success';
                                                                break;
                                                            case 'cancelled':
                                                                $badge_class = 'badge-danger';
                                                                break;
                                                        }
                                                        ?>
                                                        <span class="badge <?php echo $badge_class; ?>">
                                                            <?php echo ucfirst($appointment['status']); ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle mr-2"></i> No appointment history available.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
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