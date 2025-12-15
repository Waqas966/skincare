<?php
// Get current page filename to highlight active menu item
$current_page = basename($_SERVER['PHP_SELF']);

// Determine user role - assumes you have a role stored in your session
// You might need to adjust this based on your actual authentication system
$user_role = isset($_SESSION["user_role"]) ? $_SESSION["user_role"] : "";

// Logo path - you can adjust this as needed
$logo_path = "../images/Picture1.png";
?>

<!-- Sidebar Navigation -->
<nav id="sidebar">
<div class="sidebar-header">
    <img src="<?php echo $logo_path; ?>" alt="Derma Specialist Logo" class="img-fluid mb-2"
        style="max-width: 100%; display: block; margin: 0 auto; filter: brightness(1.3) contrast(1.15);">
</div>

    <ul class="list-unstyled components">
        <?php if ($user_role == "admin") { ?>
            <!-- Admin Menu Items -->
            <li class="<?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
                <a href="dashboard.php"><i class="fas fa-tachometer-alt mr-2"></i> Dashboard</a>
            </li>
             <li class="<?php echo ($current_page == 'manage_patients.php') ? 'active' : ''; ?>">
                <a href="manage_patients.php"><i class="fas fa-user-injured mr-2"></i> Manage Patients</a>
            </li>
            <li class="<?php echo ($current_page == 'manage_specialists.php') ? 'active' : ''; ?>">
                <a href="manage_specialists.php"><i class="fas fa-user-md mr-2"></i> Manage Specialists</a>
            </li>
            <li class="<?php echo ($current_page == 'all_appointments.php') ? 'active' : ''; ?>">
                <a href="all_appointments.php"><i class="fas fa-calendar-alt mr-2"></i> All Appointments</a>
            </li>
            <li class="<?php echo ($current_page == 'Patient_history.php') ? 'active' : ''; ?>">
                <a href="Patient_history.php"><i class="fas fa-history mr-2"></i> Patients History</a>
            </li>
            <li class="<?php echo ($current_page == 'reports.php') ? 'active' : ''; ?>">
                <a href="reports.php"><i class="fas fa-chart-bar mr-2"></i> Reports</a>
            </li>
        <?php } elseif ($user_role == "specialist") { ?>
            <!-- Specialist Menu Items -->
            <li class="<?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
                <a href="dashboard.php"><i class="fas fa-tachometer-alt mr-2"></i> Dashboard</a>
            </li>
            <li class="<?php echo ($current_page == 'manage_appointments.php') ? 'active' : ''; ?>">
                <a href="manage_appointments.php"><i class="fas fa-calendar-check mr-2"></i> Manage Appointments</a>
            </li>
            <li class="<?php echo ($current_page == 'My_patients.php') ? 'active' : ''; ?>">
                <a href="My_patients.php"><i class="fas fa-user-injured mr-2"></i> My Patients</a>
            </li>
            <li class="<?php echo ($current_page == 'patient_records.php') ? 'active' : ''; ?>">
                <a href="patient_records.php"><i class="fas fa-notes-medical mr-2"></i> Patient Records</a>
            </li>
            <li class="<?php echo ($current_page == 'Patient_history.php') ? 'active' : ''; ?>">
                <a href="Patient_history.php"><i class="fas fa-history mr-2"></i> Patients History</a>
            </li>
             <li class="<?php echo ($current_page == 'reviews.php') ? 'active' : ''; ?>">
                <a href="reviews.php"><i class="fas fa-star mr-2"></i> Patients Reviews</a>
            </li>    
        <?php } elseif ($user_role == "patient") { ?>
            <!-- Patient Menu Items -->
            <li class="<?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
                <a href="dashboard.php"><i class="fas fa-tachometer-alt mr-2"></i> Dashboard</a>
            </li>
            <li class="<?php echo ($current_page == 'book_appointment.php') ? 'active' : ''; ?>">
                <a href="book_appointment.php"><i class="fas fa-calendar-plus mr-2"></i> Book Appointment</a>
            </li>
            <li class="<?php echo ($current_page == 'appointments.php') ? 'active' : ''; ?>">
                <a href="appointments.php"><i class="fas fa-calendar-check mr-2"></i> My Appointments</a>
            </li>
            <li class="<?php echo ($current_page == 'treatment_history.php') ? 'active' : ''; ?>">
                <a href="Report.php"><i class="fas fa-book-medical mr-2"></i></i>Report</a>
            </li>
        <?php } ?>

        <!-- Logout is common for all roles -->
        <li>
            <a href="../logout.php"><i class="fas fa-sign-out-alt mr-2"></i> Logout</a>
        </li>
    </ul>
</nav>