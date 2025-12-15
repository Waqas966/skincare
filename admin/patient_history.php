<?php
// Include config file
require_once "../config.php";

require_admin();


$_SESSION["user_role"] = "admin"; // or "admin" 

// Get all patients for the dropdown
function getAllPatients()
{
    global $conn;
    $sql = "SELECT u.id, u.first_name, u.last_name, u.email, u.mobile 
            FROM users u 
            WHERE u.user_type = 'patient' AND u.approval_status = 'approved'
            ORDER BY u.first_name, u.last_name";
    return mysqli_query($conn, $sql);
}

// Search patient by email
function searchPatientByEmail($email)
{
    global $conn;
    $sql = "SELECT u.id, u.first_name, u.last_name, u.email, u.mobile 
            FROM users u 
            WHERE u.user_type = 'patient' AND u.approval_status = 'approved' AND u.email = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($result);
}

// Get patient complete history
function getPatientHistory($patient_id)
{
    global $conn;

    // Patient basic info
    $patient_info = [];
    $sql = "SELECT u.*, p.certificate 
            FROM users u 
            LEFT JOIN patients p ON u.id = p.user_id 
            WHERE u.id = ? AND u.user_type = 'patient'";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $patient_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $patient_info = mysqli_fetch_assoc($result);

    // Appointments with specialist info
    $appointments = [];
    $sql = "SELECT a.*, 
                   s_user.first_name as specialist_fname, 
                   s_user.last_name as specialist_lname,
                   sp.specialization
            FROM appointments a
            LEFT JOIN users s_user ON a.specialist_id = s_user.id
            LEFT JOIN specialists sp ON s_user.id = sp.user_id
            WHERE a.patient_id = ?
            ORDER BY a.appointment_date DESC, a.appointment_time DESC";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $patient_id);
    mysqli_stmt_execute($stmt);
    $appointments = mysqli_stmt_get_result($stmt);

    // Patient records with treatments, medications, and lab tests
    $records = [];
    $sql = "SELECT pr.*, 
                   s_user.first_name as specialist_fname, 
                   s_user.last_name as specialist_lname,
                   sp.specialization,
                   a.appointment_date, a.treatment_type
            FROM patient_records pr
            LEFT JOIN users s_user ON pr.specialist_id = s_user.id
            LEFT JOIN specialists sp ON s_user.id = sp.user_id
            LEFT JOIN appointments a ON pr.appointment_id = a.id
            WHERE pr.patient_id = ?
            ORDER BY pr.created_at DESC";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $patient_id);
    mysqli_stmt_execute($stmt);
    $records_result = mysqli_stmt_get_result($stmt);

    while ($record = mysqli_fetch_assoc($records_result)) {
        // Get treatments for this record
        $treatments_sql = "SELECT * FROM treatments WHERE record_id = ?";
        $treatments_stmt = mysqli_prepare($conn, $treatments_sql);
        mysqli_stmt_bind_param($treatments_stmt, "i", $record['id']);
        mysqli_stmt_execute($treatments_stmt);
        $treatments_result = mysqli_stmt_get_result($treatments_stmt);
        $record['treatments'] = [];
        while ($treatment = mysqli_fetch_assoc($treatments_result)) {
            $record['treatments'][] = $treatment;
        }

        // Get medications for this record
        $medications_sql = "SELECT * FROM medications WHERE record_id = ?";
        $medications_stmt = mysqli_prepare($conn, $medications_sql);
        mysqli_stmt_bind_param($medications_stmt, "i", $record['id']);
        mysqli_stmt_execute($medications_stmt);
        $medications_result = mysqli_stmt_get_result($medications_stmt);
        $record['medications'] = [];
        while ($medication = mysqli_fetch_assoc($medications_result)) {
            $record['medications'][] = $medication;
        }

        // Get lab tests for this record
        $lab_tests_sql = "SELECT * FROM lab_tests WHERE record_id = ?";
        $lab_tests_stmt = mysqli_prepare($conn, $lab_tests_sql);
        mysqli_stmt_bind_param($lab_tests_stmt, "i", $record['id']);
        mysqli_stmt_execute($lab_tests_stmt);
        $lab_tests_result = mysqli_stmt_get_result($lab_tests_stmt);
        $record['lab_tests'] = [];
        while ($lab_test = mysqli_fetch_assoc($lab_tests_result)) {
            $record['lab_tests'][] = $lab_test;
        }

        $records[] = $record;
    }

    // Get reviews
    $reviews = [];
    $sql = "SELECT r.*, 
                   s_user.first_name as specialist_fname, 
                   s_user.last_name as specialist_lname,
                   a.appointment_date, a.treatment_type
            FROM reviews r
            LEFT JOIN users s_user ON r.specialist_id = s_user.id
            LEFT JOIN appointments a ON r.appointment_id = a.id
            WHERE r.patient_id = ?
            ORDER BY r.created_at DESC";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $patient_id);
    mysqli_stmt_execute($stmt);
    $reviews = mysqli_stmt_get_result($stmt);

    return [
        'patient_info' => $patient_info,
        'appointments' => $appointments,
        'records' => $records,
        'reviews' => $reviews
    ];
}

// Handle search
$selected_patient_id = 0;
$search_email = isset($_GET['search_email']) ? trim($_GET['search_email']) : '';
$search_message = '';
$patient_history = null;

// If dropdown selection is used
if (isset($_GET['patient_id']) && $_GET['patient_id'] > 0) {
    $selected_patient_id = (int) $_GET['patient_id'];
}

// If email search is used
if (!empty($search_email)) {
    $patient = searchPatientByEmail($search_email);
    if ($patient) {
        $selected_patient_id = $patient['id'];
        $search_message = "Patient found: " . $patient['first_name'] . " " . $patient['last_name'];
    } else {
        $search_message = "No patient found with email: " . htmlspecialchars($search_email);
    }
}

if ($selected_patient_id > 0) {
    $patient_history = getPatientHistory($selected_patient_id);
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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

        .patient-card {
            border-left: 4px solid #007bff;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .status-badge {
            font-size: 0.8em;
        }

        .record-card {
            border-left: 4px solid #28a745;
            margin-bottom: 20px;
        }

        .appointment-card {
            border-left: 4px solid #ffc107;
        }

        .review-card {
            border-left: 4px solid #17a2b8;
        }

        .info-section {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
        }

        .rating-stars {
            color: #ffc107;
        }

        .search-tabs {
            border-bottom: 1px solid #dee2e6;
        }

        .search-tabs .nav-link {
            border: none;
            border-bottom: 2px solid transparent;
        }

        .search-tabs .nav-link.active {
            border-bottom-color: #007bff;
            background: none;
        }

        @media print {
            .no-print {
                display: none !important;
            }

            .container-fluid {
                margin: 0;
                padding: 0;
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
                    <h4 class="mb-0">Patient History</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb bg-transparent p-0 mb-0">
                            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Patient History</li>
                        </ol>
                    </nav>
                </div>
            </div>


            <!-- Patient Selection/Search -->
            <div class="card mb-4 no-print">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-search"></i> Find Patient</h5>

                    <!-- Search Tabs -->
                    <ul class="nav nav-tabs search-tabs mb-3" id="searchTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="email-tab" data-bs-toggle="tab"
                                data-bs-target="#email-search" type="button" role="tab">
                                <i class="fas fa-envelope"></i> Search by Email
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="dropdown-tab" data-bs-toggle="tab"
                                data-bs-target="#dropdown-search" type="button" role="tab">
                                <i class="fas fa-list"></i> Select from List
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="searchTabContent">
                        <!-- Email Search Tab -->
                        <div class="tab-pane fade show active" id="email-search" role="tabpanel">
                            <form method="GET" class="row g-3">
                                <div class="col-md-8">
                                    <input type="email" name="search_email" class="form-control"
                                        placeholder="Enter patient email address..."
                                        value="<?php echo htmlspecialchars($search_email); ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search"></i> Search by Email
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Dropdown Search Tab -->
                        <div class="tab-pane fade" id="dropdown-search" role="tabpanel">
                            <form method="GET" class="row g-3">
                                <div class="col-md-8">
                                    <select name="patient_id" class="form-select" required>
                                        <option value="">Choose a patient...</option>
                                        <?php
                                        $patients = getAllPatients();
                                        while ($patient = mysqli_fetch_assoc($patients)) {
                                            $selected = ($patient['id'] == $selected_patient_id) ? 'selected' : '';
                                            echo "<option value='{$patient['id']}' {$selected}>";
                                            echo "{$patient['first_name']} {$patient['last_name']} - {$patient['email']}";
                                            echo "</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search"></i> View History
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Search Message -->
                    <?php if (!empty($search_message)): ?>
                        <div
                            class="alert <?php echo strpos($search_message, 'found:') !== false ? 'alert-success' : 'alert-warning'; ?> mt-3">
                            <i
                                class="fas <?php echo strpos($search_message, 'found:') !== false ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?>"></i>
                            <?php echo $search_message; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($patient_history && $patient_history['patient_info']): ?>
                <?php $patient = $patient_history['patient_info']; ?>

                <!-- Patient Basic Information -->
                <div class="card patient-card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="fas fa-user"></i> Patient Information</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Name:</strong>
                                    <?php echo $patient['first_name'] . ' ' . $patient['last_name']; ?></p>
                                <p><strong>Email:</strong> <?php echo $patient['email']; ?></p>
                                <p><strong>Mobile:</strong> <?php echo $patient['mobile']; ?></p>
                                <p><strong>CNIC:</strong> <?php echo $patient['cnic']; ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>State:</strong> <?php echo $patient['state']; ?></p>
                                <p><strong>City:</strong> <?php echo $patient['city']; ?></p>
                                <p><strong>Registered:</strong>
                                    <?php echo date('M d, Y', strtotime($patient['created_at'])); ?></p>
                                <p><strong>Status:</strong>
                                    <span
                                        class="badge bg-success status-badge"><?php echo ucfirst($patient['approval_status']); ?></span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Patient Records -->
                <?php if (!empty($patient_history['records'])): ?>
                    <div class="card record-card mb-4">
                        <div class="card-header bg-success text-white">
                            <h4 class="mb-0"><i class="fas fa-file-medical"></i> Medical Records</h4>
                        </div>
                        <div class="card-body">
                            <?php foreach ($patient_history['records'] as $record): ?>
                                <div class="info-section mb-4">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <h5 class="text-primary">Record #<?php echo $record['id']; ?></h5>
                                            <p><strong>Date:</strong>
                                                <?php echo date('M d, Y', strtotime($record['created_at'])); ?></p>
                                            <p><strong>Specialist:</strong>
                                                Dr. <?php echo $record['specialist_fname'] . ' ' . $record['specialist_lname']; ?>
                                                <small
                                                    class="text-muted">(<?php echo ucfirst(str_replace('_', ' ', $record['specialization'])); ?>)</small>
                                            </p>
                                            <p><strong>Treatment Type:</strong> <?php echo $record['treatment_type']; ?></p>
                                        </div>
                                        <div class="col-md-4 text-end">
                                            <small class="text-muted">Appointment:
                                                <?php echo date('M d, Y', strtotime($record['appointment_date'])); ?></small>
                                        </div>
                                    </div>

                                    <?php if ($record['diagnosis']): ?>
                                        <div class="mt-3">
                                            <h6><i class="fas fa-stethoscope"></i> Diagnosis:</h6>
                                            <p class="border-start border-3 border-info ps-3"><?php echo $record['diagnosis']; ?></p>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($record['notes']): ?>
                                        <div class="mt-3">
                                            <h6><i class="fas fa-sticky-note"></i> Notes:</h6>
                                            <p class="border-start border-3 border-warning ps-3"><?php echo $record['notes']; ?></p>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Treatments -->
                                    <?php if (!empty($record['treatments'])): ?>
                                        <div class="mt-3">
                                            <h6><i class="fas fa-procedures"></i> Treatments:</h6>
                                            <div class="row">
                                                <?php foreach ($record['treatments'] as $treatment): ?>
                                                    <div class="col-md-6 mb-2">
                                                        <div class="border rounded p-2 bg-light">
                                                            <strong><?php echo $treatment['treatment_name']; ?></strong>
                                                            <?php if ($treatment['description']): ?>
                                                                <br><small><?php echo $treatment['description']; ?></small>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Medications -->
                                    <?php if (!empty($record['medications'])): ?>
                                        <div class="mt-3">
                                            <h6><i class="fas fa-pills"></i> Medications:</h6>
                                            <div class="row">
                                                <?php foreach ($record['medications'] as $medication): ?>
                                                    <div class="col-md-6 mb-2">
                                                        <div class="border rounded p-2 bg-light">
                                                            <strong><?php echo $medication['medication_name']; ?></strong>
                                                            <?php if ($medication['dosage']): ?>
                                                                <br><small><strong>Dosage:</strong> <?php echo $medication['dosage']; ?></small>
                                                            <?php endif; ?>
                                                            <?php if ($medication['instructions']): ?>
                                                                <br><small><strong>Instructions:</strong>
                                                                    <?php echo $medication['instructions']; ?></small>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Lab Tests -->
                                    <?php if (!empty($record['lab_tests'])): ?>
                                        <div class="mt-3">
                                            <h6><i class="fas fa-vial"></i> Lab Tests:</h6>
                                            <div class="row">
                                                <?php foreach ($record['lab_tests'] as $lab_test): ?>
                                                    <div class="col-md-6 mb-2">
                                                        <div class="border rounded p-2 bg-light">
                                                            <strong><?php echo $lab_test['test_name']; ?></strong>
                                                            <?php if ($lab_test['results']): ?>
                                                                <br><small><strong>Results:</strong> <?php echo $lab_test['results']; ?></small>
                                                            <?php endif; ?>
                                                            <?php if ($lab_test['test_date']): ?>
                                                                <br><small><strong>Date:</strong>
                                                                    <?php echo date('M d, Y', strtotime($lab_test['test_date'])); ?></small>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <hr>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Appointments History -->
                <div class="card appointment-card mb-4">
                    <div class="card-header bg-warning text-dark">
                        <h4 class="mb-0"><i class="fas fa-calendar-alt"></i> Appointments History</h4>
                    </div>
                    <div class="card-body">
                        <?php if (mysqli_num_rows($patient_history['appointments']) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Time</th>
                                            <th>Specialist</th>
                                            <th>Treatment Type</th>
                                            <th>Status</th>
                                            <th>Description</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($appointment = mysqli_fetch_assoc($patient_history['appointments'])): ?>
                                            <tr>
                                                <td><?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?></td>
                                                <td><?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?></td>
                                                <td>
                                                    Dr.
                                                    <?php echo $appointment['specialist_fname'] . ' ' . $appointment['specialist_lname']; ?>
                                                    <?php if ($appointment['specialization']): ?>
                                                        <br><small
                                                            class="text-muted"><?php echo ucfirst(str_replace('_', ' ', $appointment['specialization'])); ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo $appointment['treatment_type']; ?></td>
                                                <td>
                                                    <?php
                                                    $status_class = '';
                                                    switch ($appointment['status']) {
                                                        case 'completed':
                                                            $status_class = 'bg-success';
                                                            break;
                                                        case 'confirmed':
                                                            $status_class = 'bg-info';
                                                            break;
                                                        case 'pending':
                                                            $status_class = 'bg-warning';
                                                            break;
                                                        case 'cancelled':
                                                            $status_class = 'bg-danger';
                                                            break;
                                                    }
                                                    ?>
                                                    <span class="badge <?php echo $status_class; ?> status-badge">
                                                        <?php echo ucfirst($appointment['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo $appointment['description'] ?: '-'; ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">No appointments found.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Reviews -->
                <?php if (mysqli_num_rows($patient_history['reviews']) > 0): ?>
                    <div class="card review-card mb-4">
                        <div class="card-header bg-info text-white">
                            <h4 class="mb-0"><i class="fas fa-star"></i> Patient Reviews</h4>
                        </div>
                        <div class="card-body">
                            <?php while ($review = mysqli_fetch_assoc($patient_history['reviews'])): ?>
                                <div class="info-section mb-3">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6>Dr. <?php echo $review['specialist_fname'] . ' ' . $review['specialist_lname']; ?>
                                            </h6>
                                            <div class="rating-stars mb-2">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="fas fa-star<?php echo $i <= $review['rating'] ? '' : '-o'; ?>"></i>
                                                <?php endfor; ?>
                                                <span class="ms-2">(<?php echo $review['rating']; ?>/5)</span>
                                            </div>
                                            <p><?php echo $review['review_text']; ?></p>
                                        </div>
                                        <small class="text-muted">
                                            <?php echo date('M d, Y', strtotime($review['created_at'])); ?>
                                        </small>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                <?php endif; ?>


            <?php elseif ($selected_patient_id > 0): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i> No patient found with the selected ID.
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
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