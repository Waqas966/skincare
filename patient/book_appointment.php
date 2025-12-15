<?php
// Include config file
require_once "../config.php";

// Check if user is logged in and is a patient
require_patient();

// Get patient data
$user_id = $_SESSION['user_id'];

$_SESSION["user_role"] = "patient"; // or "admin" or "patient"

// Get list of specialists
$sql = "SELECT u.id, u.first_name, u.last_name, s.specialization 
        FROM users u 
        JOIN specialists s ON u.id = s.user_id 
        WHERE u.user_type = 'specialist' 
        ORDER BY u.last_name, u.first_name";
$specialists = mysqli_query($conn, $sql);

// Get all specializations for filter
$specialization_sql = "SELECT DISTINCT specialization FROM specialists ORDER BY specialization";
$specializations = mysqli_query($conn, $specialization_sql);

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $specialist_id = $_POST['specialist_id'];
    $appointment_date = $_POST['appointment_date'];
    $appointment_time = $_POST['appointment_time'];
    $treatment_type = isset($_POST['treatment_type']) ? $_POST['treatment_type'] : ''; // Add check here
    $description = $_POST['description'];

    // Validate inputs
    $errors = [];

    if (empty($specialist_id)) {
        $errors[] = "Please select a specialist.";
    }

    if (empty($appointment_date)) {
        $errors[] = "Please select an appointment date.";
    } else {
        // Check if date is in the future
        $selected_date = new DateTime($appointment_date);
        $today = new DateTime();
        if ($selected_date < $today) {
            $errors[] = "Appointment date must be in the future.";
        }
    }

    if (empty($appointment_time)) {
        $errors[] = "Please select an appointment time.";
    }


    // If no errors, insert appointment
    if (empty($errors)) {
        $status = "pending"; // Default status for new appointments

        $sql = "INSERT INTO appointments (patient_id, specialist_id, appointment_date, appointment_time, treatment_type, description, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "iisssss", $user_id, $specialist_id, $appointment_date, $appointment_time, $treatment_type, $description, $status);

        if (mysqli_stmt_execute($stmt)) {
            $success = "Appointment requested successfully. The specialist will confirm your appointment soon.";
        } else {
            $errors[] = "Something went wrong. Please try again later.";
        }
    }
}

// Function to check if a time slot is available
function isTimeSlotAvailable($conn, $specialist_id, $date, $time)
{
    $sql = "SELECT COUNT(*) as count FROM appointments 
            WHERE specialist_id = ? AND appointment_date = ? AND appointment_time = ? 
            AND (status = 'pending' OR status = 'confirmed')";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "iss", $specialist_id, $date, $time);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);

    return $row['count'] == 0;
}

// Get available time slots for AJAX request
if (isset($_GET['get_available_slots']) && isset($_GET['specialist_id']) && isset($_GET['date'])) {
    $specialist_id = $_GET['specialist_id'];
    $date = $_GET['date'];

    // Define all possible time slots (9 AM to 5 PM)
    $time_slots = [
        '09:00:00' => '9:00 AM',

        '10:00:00' => '10:00 AM',

        '11:00:00' => '11:00 AM',

        '12:00:00' => '12:00 PM',

        '13:00:00' => '1:00 PM',

        '14:00:00' => '2:00 PM',

        '15:00:00' => '3:00 PM',

        '16:00:00' => '4:00 PM',

    ];

    $available_slots = [];

    foreach ($time_slots as $time_value => $time_label) {
        if (isTimeSlotAvailable($conn, $specialist_id, $date, $time_value)) {
            $available_slots[$time_value] = $time_label;
        }
    }

    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($available_slots);
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
    <!-- Datepicker CSS -->
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css">
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

        /* Card styling */
        .card {
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }

        .specialist-card {
            cursor: pointer;
            transition: all 0.3s;
            border: 2px solid transparent;
        }

        .specialist-card:hover {
            transform: translateY(-5px);
            border-color: var(--primary-color);
        }

        .specialist-card.selected {
            border-color: var(--success-color);
            background-color: rgba(46, 204, 113, 0.1);
        }

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
                    <h4 class="mb-0">Book Appointments</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb bg-transparent p-0 mb-0">
                            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                            <li class="breadcrumb-item active" aria-current="page">All Appointments</li>
                        </ol>
                    </nav>
                </div>
            </div>

            <div class="container-fluid">
                <?php if (isset($errors) && !empty($errors)): ?>
                    <div class="alert alert-danger" role="alert">
                        <strong>Please correct the following errors:</strong>
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if (isset($success)): ?>
                    <div class="alert alert-success" role="alert">
                        <?php echo $success; ?>
                        <p class="mb-0 mt-2">
                            <a href="appointments.php" class="alert-link">View all appointments</a>
                        </p>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="fas fa-filter mr-2"></i> Filter Specialists</h5>
                            </div>
                            <div class="card-body">
                                <form id="filterForm">
                                    <div class="form-group">
                                        <label for="specialization">Specialization</label>
                                        <select class="form-control" id="specialization" name="specialization">
                                            <option value="">All Specializations</option>
                                            <?php while ($row = mysqli_fetch_assoc($specializations)): ?>
                                                <option value="<?php echo $row['specialization']; ?>">
                                                    <?php echo $row['specialization']; ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="name">Search by Name</label>
                                        <input type="text" class="form-control" id="name" name="name"
                                            placeholder="Enter name...">
                                    </div>
                                    <button type="button" id="filterButton" class="btn btn-primary btn-block">
                                        <i class="fas fa-search mr-2"></i>Apply Filter
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="fas fa-user-md mr-2"></i> Available Specialists</h5>
                            </div>
                            <div class="card-body">
                                <div class="row" id="specialistsList">
                                    <?php while ($specialist = mysqli_fetch_assoc($specialists)): ?>
                                        <div class="col-md-6 mb-3 specialist-item"
                                            data-id="<?php echo $specialist['id']; ?>"
                                            data-name="<?php echo $specialist['first_name'] . ' ' . $specialist['last_name']; ?>"
                                            data-specialization="<?php echo $specialist['specialization']; ?>">
                                            <div class="card specialist-card h-100">
                                                <div class="card-body">
                                                    <h5 class="card-title">
                                                        <i class="fas fa-user-md text-primary mr-2"></i>
                                                        Dr.
                                                        <?php echo $specialist['first_name'] . ' ' . $specialist['last_name']; ?>
                                                    </h5>
                                                    <p class="card-text">
                                                        <span class="badge badge-info">
                                                            <?php echo $specialist['specialization']; ?>
                                                        </span>
                                                    </p>
                                                    <button class="btn btn-outline-primary btn-sm select-specialist"
                                                        data-id="<?php echo $specialist['id']; ?>"
                                                        data-name="Dr. <?php echo $specialist['first_name'] . ' ' . $specialist['last_name']; ?>">
                                                        <i class="fas fa-check mr-1"></i> Select
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mt-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-calendar-plus mr-2"></i> Schedule Appointment</h5>
                    </div>
                    <div class="card-body">
                        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                            <div class="form-group">
                                <label for="selected_specialist">Selected Specialist</label>
                                <input type="text" class="form-control" id="selected_specialist" readonly
                                    value="No specialist selected">
                                <input type="hidden" name="specialist_id" id="specialist_id">
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="appointment_date">Appointment Date</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control datepicker" id="appointment_date"
                                                name="appointment_date" placeholder="Select date" autocomplete="off">
                                            <div class="input-group-append">
                                                <span class="input-group-text"><i
                                                        class="fas fa-calendar-alt"></i></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="appointment_time">Appointment Time</label>
                                        <select class="form-control" id="appointment_time" name="appointment_time"
                                            disabled>
                                            <option value="">Select date first</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="treatment_type">Treatment for Appointment</label>
                                <select class="form-control" id="treatment_type" name="treatment_type">
                                    <option value="">Select Treatment type</option>
                                    <option value="New consultation">New consultation</option>
                                    <option value="Follow-up">Follow-up</option>
                                    <option value="Emergency">Emergency</option>
                                    <option value="Regular check-up">Regular check-up</option>
                                    <option value="Lab results discussion">Lab results discussion</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="description">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3"
                                    placeholder="Include any additional information or specific concerns..."></textarea>
                            </div>

                            <button type="submit" class="btn btn-success btn-lg btn-block">
                                <i class="fas fa-calendar-check mr-2"></i> Request Appointment
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Datepicker JS -->
    <script
        src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
    <script>
        $(document).ready(function () {
            // Sidebar toggle
            $('#sidebarCollapse').on('click', function () {
                $('#sidebar').toggleClass('active');
            });

            // Initialize datepicker
            $('.datepicker').datepicker({
                format: 'yyyy-mm-dd',
                startDate: '+1d',  // Can only select dates in the future
                autoclose: true
            });

            // Specialist selection
            $('.select-specialist').on('click', function () {
                const specialistId = $(this).data('id');
                const specialistName = $(this).data('name');

                // Update the form fields
                $('#selected_specialist').val(specialistName);
                $('#specialist_id').val(specialistId);

                // Highlight the selected specialist
                $('.specialist-card').removeClass('selected');
                $(this).closest('.specialist-card').addClass('selected');

                // Reset time slots when specialist changes
                $('#appointment_time').html('<option value="">Select date first</option>').prop('disabled', true);

                // If date is already selected, load available times for new specialist
                if ($('#appointment_date').val()) {
                    loadAvailableTimes();
                }
            });

            // When date changes, get available time slots
            $('#appointment_date').on('change', function () {
                loadAvailableTimes();
            });

            // Function to load available times
            function loadAvailableTimes() {
                const specialistId = $('#specialist_id').val();
                const date = $('#appointment_date').val();

                if (!specialistId || !date) {
                    return;
                }

                // AJAX request to get available slots
                $.ajax({
                    url: 'book_appointment.php',
                    type: 'GET',
                    data: {
                        get_available_slots: 1,
                        specialist_id: specialistId,
                        date: date
                    },
                    dataType: 'json',
                    success: function (data) {
                        const timeSelect = $('#appointment_time');
                        timeSelect.empty();

                        if (Object.keys(data).length === 0) {
                            timeSelect.append('<option value="">No available slots</option>');
                        } else {
                            timeSelect.append('<option value="">Select time</option>');
                            $.each(data, function (value, label) {
                                timeSelect.append('<option value="' + value + '">' + label + '</option>');
                            });
                            timeSelect.prop('disabled', false);
                        }
                    },
                    error: function () {
                        alert('Error fetching available time slots. Please try again.');
                    }
                });
            }

            // Filter functionality
            $('#filterButton').on('click', function () {
                const specialization = $('#specialization').val().toLowerCase();
                const name = $('#name').val().toLowerCase();

                $('.specialist-item').each(function () {
                    const itemSpecialization = $(this).data('specialization').toLowerCase();
                    const itemName = $(this).data('name').toLowerCase();

                    const matchesSpecialization = !specialization || itemSpecialization.includes(specialization);
                    const matchesName = !name || itemName.includes(name);

                    if (matchesSpecialization && matchesName) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            });
        });
    </script>
</body>

</html>