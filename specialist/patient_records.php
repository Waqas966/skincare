<?php

require_once '../vendor/autoload.php';
require_once('../vendor/tecnickcom/tcpdf/tcpdf.php');

require_once "../config.php";

// Check if user is logged in and is a specialist
require_specialist();

$_SESSION["user_role"] = "specialist"; // or "admin" or "patient"
// Get specialist data

// Get specialist information
$specialist_id = $_SESSION["user_id"];
$specialist_info_query = mysqli_query($conn, "SELECT u.*, s.experience, s.specialization 
                          FROM users u 
                          JOIN specialists s ON u.id = s.user_id 
                          WHERE u.id = $specialist_id");
$specialist_info = mysqli_fetch_assoc($specialist_info_query);


$user_id = $_SESSION['user_id'];
$specialist_id = null;

// Get specialist ID from specialists table
$sql = "SELECT id FROM specialists WHERE user_id = ?";
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_store_result($stmt);
        if (mysqli_stmt_num_rows($stmt) == 1) {
            mysqli_stmt_bind_result($stmt, $specialist_id);
            mysqli_stmt_fetch($stmt);
        }
    }
    mysqli_stmt_close($stmt);
}

// Process form submission for adding or updating patient records
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["action"])) {
        // Add new record
        if ($_POST["action"] == "add") {
            $appointment_id = trim($_POST["appointment_id"]);
            $patient_id = trim($_POST["patient_id"]);
            $diagnosis = trim($_POST["diagnosis"]);
            $notes = trim($_POST["notes"]);

            // Insert record
            $sql = "INSERT INTO patient_records (patient_id, specialist_id, appointment_id, diagnosis, notes) VALUES (?, ?, ?, ?, ?)";
            if ($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "iiiss", $patient_id, $specialist_id, $appointment_id, $diagnosis, $notes);

                if (mysqli_stmt_execute($stmt)) {
                    $record_id = mysqli_insert_id($conn);

                    // Add medications if provided
                    if (!empty($_POST["medications"])) {
                        foreach ($_POST["medications"] as $key => $value) {
                            $medication_name = trim($_POST["medications"][$key]);
                            $dosage = trim($_POST["dosages"][$key]);
                            $instructions = trim($_POST["med_instructions"][$key]);

                            if (!empty($medication_name)) {
                                $sql = "INSERT INTO medications (record_id, medication_name, dosage, instructions) VALUES (?, ?, ?, ?)";
                                if ($med_stmt = mysqli_prepare($conn, $sql)) {
                                    mysqli_stmt_bind_param($med_stmt, "isss", $record_id, $medication_name, $dosage, $instructions);
                                    mysqli_stmt_execute($med_stmt);
                                    mysqli_stmt_close($med_stmt);
                                }
                            }
                        }
                    }

                    // Add treatments if provided
                    if (!empty($_POST["treatments"])) {
                        foreach ($_POST["treatments"] as $key => $value) {
                            $treatment_name = trim($_POST["treatments"][$key]);
                            $treatment_desc = trim($_POST["treatment_descriptions"][$key]);

                            if (!empty($treatment_name)) {
                                $sql = "INSERT INTO treatments (record_id, treatment_name, description) VALUES (?, ?, ?)";
                                if ($treat_stmt = mysqli_prepare($conn, $sql)) {
                                    mysqli_stmt_bind_param($treat_stmt, "iss", $record_id, $treatment_name, $treatment_desc);
                                    mysqli_stmt_execute($treat_stmt);
                                    mysqli_stmt_close($treat_stmt);
                                }
                            }
                        }
                    }

                    // Add lab tests if provided
                    if (!empty($_POST["tests"])) {
                        foreach ($_POST["tests"] as $key => $value) {
                            $test_name = trim($_POST["tests"][$key]);
                            $results = trim($_POST["results"][$key]);
                            $test_date = trim($_POST["test_dates"][$key]);

                            if (!empty($test_name)) {
                                $sql = "INSERT INTO lab_tests (record_id, test_name, results, test_date) VALUES (?, ?, ?, ?)";
                                if ($test_stmt = mysqli_prepare($conn, $sql)) {
                                    mysqli_stmt_bind_param($test_stmt, "isss", $record_id, $test_name, $results, $test_date);
                                    mysqli_stmt_execute($test_stmt);
                                    mysqli_stmt_close($test_stmt);
                                }
                            }
                        }
                    }

                    // Update appointment status to completed
                    $sql = "UPDATE appointments SET status = 'completed' WHERE id = ?";
                    if ($update_stmt = mysqli_prepare($conn, $sql)) {
                        mysqli_stmt_bind_param($update_stmt, "i", $appointment_id);
                        mysqli_stmt_execute($update_stmt);
                        mysqli_stmt_close($update_stmt);
                    }

                    $success_msg = "Patient record created successfully.";
                } else {
                    $error_msg = "Error creating record. Please try again.";
                }
                mysqli_stmt_close($stmt);
            }
        }
        // Update existing record
        elseif ($_POST["action"] == "update") {
            $record_id = trim($_POST["record_id"]);
            $diagnosis = trim($_POST["diagnosis"]);
            $notes = trim($_POST["notes"]);

            // Update record
            $sql = "UPDATE patient_records SET diagnosis = ?, notes = ? WHERE id = ? AND specialist_id = ?";
            if ($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "ssii", $diagnosis, $notes, $record_id, $specialist_id);

                if (mysqli_stmt_execute($stmt)) {
                    // Clear existing medications, treatments, and tests to avoid duplicates
                    $sql = "DELETE FROM medications WHERE record_id = ?";
                    if ($del_stmt = mysqli_prepare($conn, $sql)) {
                        mysqli_stmt_bind_param($del_stmt, "i", $record_id);
                        mysqli_stmt_execute($del_stmt);
                        mysqli_stmt_close($del_stmt);
                    }

                    $sql = "DELETE FROM treatments WHERE record_id = ?";
                    if ($del_stmt = mysqli_prepare($conn, $sql)) {
                        mysqli_stmt_bind_param($del_stmt, "i", $record_id);
                        mysqli_stmt_execute($del_stmt);
                        mysqli_stmt_close($del_stmt);
                    }

                    $sql = "DELETE FROM lab_tests WHERE record_id = ?";
                    if ($del_stmt = mysqli_prepare($conn, $sql)) {
                        mysqli_stmt_bind_param($del_stmt, "i", $record_id);
                        mysqli_stmt_execute($del_stmt);
                        mysqli_stmt_close($del_stmt);
                    }

                    // Add medications if provided
                    if (!empty($_POST["medications"])) {
                        foreach ($_POST["medications"] as $key => $value) {
                            $medication_name = trim($_POST["medications"][$key]);
                            $dosage = trim($_POST["dosages"][$key]);
                            $instructions = trim($_POST["med_instructions"][$key]);

                            if (!empty($medication_name)) {
                                $sql = "INSERT INTO medications (record_id, medication_name, dosage, instructions) VALUES (?, ?, ?, ?)";
                                if ($med_stmt = mysqli_prepare($conn, $sql)) {
                                    mysqli_stmt_bind_param($med_stmt, "isss", $record_id, $medication_name, $dosage, $instructions);
                                    mysqli_stmt_execute($med_stmt);
                                    mysqli_stmt_close($med_stmt);
                                }
                            }
                        }
                    }

                    // Add treatments if provided
                    if (!empty($_POST["treatments"])) {
                        foreach ($_POST["treatments"] as $key => $value) {
                            $treatment_name = trim($_POST["treatments"][$key]);
                            $treatment_desc = trim($_POST["treatment_descriptions"][$key]);

                            if (!empty($treatment_name)) {
                                $sql = "INSERT INTO treatments (record_id, treatment_name, description) VALUES (?, ?, ?)";
                                if ($treat_stmt = mysqli_prepare($conn, $sql)) {
                                    mysqli_stmt_bind_param($treat_stmt, "iss", $record_id, $treatment_name, $treatment_desc);
                                    mysqli_stmt_execute($treat_stmt);
                                    mysqli_stmt_close($treat_stmt);
                                }
                            }
                        }
                    }

                    // Add lab tests if provided
                    if (!empty($_POST["tests"])) {
                        foreach ($_POST["tests"] as $key => $value) {
                            $test_name = trim($_POST["tests"][$key]);
                            $results = trim($_POST["results"][$key]);
                            $test_date = trim($_POST["test_dates"][$key]);

                            if (!empty($test_name)) {
                                $sql = "INSERT INTO lab_tests (record_id, test_name, results, test_date) VALUES (?, ?, ?, ?)";
                                if ($test_stmt = mysqli_prepare($conn, $sql)) {
                                    mysqli_stmt_bind_param($test_stmt, "isss", $record_id, $test_name, $results, $test_date);
                                    mysqli_stmt_execute($test_stmt);
                                    mysqli_stmt_close($test_stmt);
                                }
                            }
                        }
                    }

                    $success_msg = "Patient record updated successfully.";
                } else {
                    $error_msg = "Error updating record. Please try again.";
                }
                mysqli_stmt_close($stmt);
            }
        }
        // Delete record
        elseif ($_POST["action"] == "delete") {
            $record_id = trim($_POST["record_id"]);
            
            // First, delete related data from child tables
            $tables = ["medications", "treatments", "lab_tests"];
            
            foreach ($tables as $table) {
                $sql = "DELETE FROM $table WHERE record_id = ?";
                if ($del_stmt = mysqli_prepare($conn, $sql)) {
                    mysqli_stmt_bind_param($del_stmt, "i", $record_id);
                    mysqli_stmt_execute($del_stmt);
                    mysqli_stmt_close($del_stmt);
                }
            }
            
            // Then delete the record itself
            $sql = "DELETE FROM patient_records WHERE id = ? AND specialist_id = ?";
            if ($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "ii", $record_id, $specialist_id);
                
                if (mysqli_stmt_execute($stmt)) {
                    $success_msg = "Patient record deleted successfully.";
                } else {
                    $error_msg = "Error deleting record. Please try again.";
                }
                mysqli_stmt_close($stmt);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Records - Derma Elixir Studio</title>
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

        /* Cards */
        .card {
             background: white;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s;
            margin-bottom: 20px;
            overflow: hidden;
        }

        .card-header {
            border-radius: 8px 8px 0 0 !important;
            padding: 0.75rem 1.25rem;
            font-weight: 600;
        }

        /* Form styling */
        .form-group label {
            font-weight: 600;
            color: var(--secondary-color);
        }

        .btn-add-field {
            background-color: #f8f9fa;
            border: 1px dashed #ced4da;
            color: #6c757d;
            transition: all 0.3s;
        }

        .btn-add-field:hover {
            background-color: #e9ecef;
            color: #495057;
        }

        .record-item {
            background-color: #fff;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .record-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .detail-table {
            margin-bottom: 0;
        }

        .detail-table td {
            padding: 5px 0;
        }

        .detail-table td:first-child {
            font-weight: 600;
            color: var(--secondary-color);
            padding-right: 15px;
            width: 150px;
        }

        /* Responsive adjustments */
        @media (max-width: 992px) {
            #sidebar {
                margin-left: -280px;
            }

            #sidebar.active {
                margin-left: 0;
            }

            #content {
                width: 100%;
                margin-left: 0;
            }

            #content.active {
                margin-left: 280px;
                width: calc(100% - 280px);
            }
        }

        /* Modal styling */
        .modal-dialog.modal-lg {
            max-width: 80%;
        }

        .modal-content {
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border: none;
        }

        .modal-header {
            background-color: var(--primary-color);
            color: white;
            border-radius: 10px 10px 0 0;
            padding: 1rem 1.5rem;
        }

        .modal-body {
            padding: 1.5rem;
        }

        .dynamic-form-row {
            background-color: #f9f9f9;
            padding: 12px;
            margin-bottom: 12px;
            border-radius: 6px;
            border-left: 3px solid var(--primary-color);
        }

        .btn-remove-field {
            color: white;
            background-color: var(--danger-color);
            border: none;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 36px;
        }

        .action-buttons {
            display: flex;
            gap: 5px;
        }

        .action-buttons .btn {
            border-radius: 4px;
            font-size: 0.85rem;
            padding: 0.375rem 0.75rem;
        }

        
        .nav-tabs .nav-link {
            border-radius: 6px 6px 0 0;
            font-weight: 500;
        }

        .nav-tabs .nav-link.active {
            background-color: #f9f9f9;
            border-color: #dee2e6 #dee2e6 #f9f9f9;
        }

        .tab-content {
            background-color: #f9f9f9;
            border: 1px solid #dee2e6;
            border-top: none;
            padding: 1rem;
            border-radius: 0 0 6px 6px;
        }

        /* Confirmation Dialog */
        .swal2-popup {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            border-radius: 12px;
        }

        .dashboard-header {
            border-bottom: 1px solid #e9ecef;
            margin-bottom: 20px;
        }

        .table-bordered {
            border-radius: 6px;
            overflow: hidden;
        }

        .table-bordered th {
            background-color: #f9f9f9;
        }
    </style>
</head>

<body>
    <div class="wrapper d-flex align-items-stretch">
        <!-- Dashboard Sidebar  -->
        <?php require "../src/sidebar.php"; ?>

        <!-- Page Content  -->
        <div id="content">
                   <div class="dashboard-header d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-0">Patient Records</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb bg-transparent p-0 mb-0">
                            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Patient Records</li>
                        </ol>
                    </nav>
                </div>
            </div>

            <?php if (isset($success_msg)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle mr-2"></i> <?php echo $success_msg; ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>

            <?php if (isset($error_msg)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle mr-2"></i> <?php echo $error_msg; ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>

            <!-- Appointments awaiting records -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-calendar-check mr-2"></i>Confirmed Appointments</h5>
                </div>
                <div class="card-body">
                    <?php
                    // Get confirmed appointments for the specialist
                    $sql = "SELECT a.id as appointment_id, a.appointment_date, a.appointment_time, a.treatment_type, 
                           a.description, u.id as patient_id, u.first_name, u.last_name, u.mobile 
                           FROM appointments a 
                           INNER JOIN users u ON a.patient_id = u.id 
                           WHERE a.specialist_id = ? AND a.status = 'confirmed' 
                           AND NOT EXISTS (SELECT 1 FROM patient_records pr WHERE pr.appointment_id = a.id) 
                           ORDER BY a.appointment_date ASC, a.appointment_time ASC";

                    if ($stmt = mysqli_prepare($conn, $sql)) {
                        mysqli_stmt_bind_param($stmt, "i", $user_id);
                        if (mysqli_stmt_execute($stmt)) {
                            $result = mysqli_stmt_get_result($stmt);

                            if (mysqli_num_rows($result) > 0) {
                                while ($row = mysqli_fetch_assoc($result)) {
                                    ?>
                                    <div class="record-item">
                                        <div class="row">
                                            <div class="col-md-9">
                                                <h5><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></h5>
                                                <table class="detail-table">
                                                    <tr>
                                                        <td>Appointment Date:</td>
                                                        <td><?php echo date('d M, Y', strtotime($row['appointment_date'])); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td>Time:</td>
                                                        <td><?php echo date('h:i A', strtotime($row['appointment_time'])); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td>Type:</td>
                                                        <td><?php echo htmlspecialchars($row['treatment_type']); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td>Mobile:</td>
                                                        <td><?php echo htmlspecialchars($row['mobile']); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td>Description:</td>
                                                        <td><?php echo htmlspecialchars($row['description']); ?></td>
                                                    </tr>
                                                </table>
                                            </div>
                                            <div class="col-md-3 d-flex align-items-center justify-content-end">
                                                <button class="btn btn-success" data-toggle="modal"
                                                    data-target="#addRecordModal<?php echo $row['appointment_id']; ?>">
                                                    <i class="fas fa-plus-circle mr-1"></i> Add Record
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Add Record Modal -->
                                    <div class="modal fade" id="addRecordModal<?php echo $row['appointment_id']; ?>" tabindex="-1"
                                        role="dialog" aria-labelledby="addRecordModalLabel<?php echo $row['appointment_id']; ?>"
                                        aria-hidden="true">
                                        <div class="modal-dialog modal-lg" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title"
                                                        id="addRecordModalLabel<?php echo $row['appointment_id']; ?>">Add Patient Record
                                                    </h5>
                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                        <span aria-hidden="true">&times;</span>
                                                    </button>
                                                </div>
                                                <form method="post">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="action" value="add">
                                                        <input type="hidden" name="appointment_id"
                                                            value="<?php echo $row['appointment_id']; ?>">
                                                        <input type="hidden" name="patient_id"
                                                            value="<?php echo $row['patient_id']; ?>">

                                                        <div class="form-group">
                                                            <label
                                                                for="diagnosis<?php echo $row['appointment_id']; ?>">Diagnosis</label>
                                                            <textarea class="form-control"
                                                                id="diagnosis<?php echo $row['appointment_id']; ?>" name="diagnosis"
                                                                rows="3" required></textarea>
                                                        </div>

                                                        <div class="form-group">
                                                            <label for="notes<?php echo $row['appointment_id']; ?>">Notes</label>
                                                            <textarea class="form-control"
                                                                id="notes<?php echo $row['appointment_id']; ?>" name="notes"
                                                                rows="3"></textarea>
                                                        </div>

                                                        <ul class="nav nav-tabs" id="recordTabs<?php echo $row['appointment_id']; ?>"
                                                            role="tablist">
                                                            <li class="nav-item">
                                                                <a class="nav-link active"
                                                                    id="medications-tab<?php echo $row['appointment_id']; ?>"
                                                                    data-toggle="tab"
                                                                    href="#medications<?php echo $row['appointment_id']; ?>"
                                                                    role="tab">Medications</a>
                                                            </li>
                                                            <li class="nav-item">
                                                                <a class="nav-link"
                                                                    id="treatments-tab<?php echo $row['appointment_id']; ?>"
                                                                    data-toggle="tab"
                                                                    href="#treatments<?php echo $row['appointment_id']; ?>"
                                                                    role="tab">Treatments</a>
                                                            </li>
                                                            <li class="nav-item">
                                                                <a class="nav-link" id="tests-tab<?php echo $row['appointment_id']; ?>"
                                                                    data-toggle="tab" href="#tests<?php echo $row['appointment_id']; ?>"
                                                                    role="tab">Lab Tests</a>
                                                            </li>
                                                        </ul>

                                                        <div class="tab-content pt-3"
                                                            id="recordTabContent<?php echo $row['appointment_id']; ?>">
                                                            <!-- Medications Tab -->
                                                            <div class="tab-pane fade show active"
                                                                id="medications<?php echo $row['appointment_id']; ?>" role="tabpanel">
                                                                <div id="medications-container<?php echo $row['appointment_id']; ?>">
                                                                    <div class="dynamic-form-row">
                                                                        <div class="form-row">
                                                                            <div class="form-group col-md-4">
                                                                                <label>Medication Name</label>
                                                                                <input type="text" class="form-control"
                                                                                    name="medications[]" placeholder="Medication name">
                                                                            </div>
                                                                            <div class="form-group col-md-3">
                                                                                <label>Dosage</label>
                                                                                <input type="text" class="form-control" name="dosages[]"
                                                                                    placeholder="Dosage">
                                                                            </div>
                                                                            <div class="form-group col-md-5">
                                                                                <label>Instructions</label>
                                                                                <input type="text" class="form-control"
                                                                                    name="med_instructions[]"
                                                                                    placeholder="Instructions">
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <button type="button" class="btn btn-add-field mt-2"
                                                                    onclick="addMedication(<?php echo $row['appointment_id']; ?>)">
                                                                    <i class="fas fa-plus"></i> Add Medication
                                                                </button>
                                                            </div>

                                                            <!-- Treatments Tab -->
                                                            <div class="tab-pane fade"
                                                                id="treatments<?php echo $row['appointment_id']; ?>" role="tabpanel">
                                                                <div id="treatments-container<?php echo $row['appointment_id']; ?>">
                                                                    <div class="dynamic-form-row">
                                                                        <div class="form-row">
                                                                            <div class="form-group col-md-5">
                                                                                <label>Treatment Name</label>
                                                                                <input type="text" class="form-control"
                                                                                    name="treatments[]" placeholder="Treatment name">
                                                                            </div>
                                                                            <div class="form-group col-md-7">
                                                                                <label>Description</label>
                                                                                <input type="text" class="form-control"
                                                                                    name="treatment_descriptions[]"
                                                                                    placeholder="Description">
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <button type="button" class="btn btn-add-field mt-2"
                                                                    onclick="addTreatment(<?php echo $row['appointment_id']; ?>)">
                                                                    <i class="fas fa-plus"></i> Add Treatment
                                                                </button>
                                                            </div>

                                                            <!-- Lab Tests Tab -->
                                                            <div class="tab-pane fade" id="tests<?php echo $row['appointment_id']; ?>"
                                                                role="tabpanel">
                                                                <div id="tests-container<?php echo $row['appointment_id']; ?>">
                                                                    <div class="dynamic-form-row">
                                                                        <div class="form-row">
                                                                            <div class="form-group col-md-4">
                                                                                <label>Test Name</label>
                                                                                <input type="text" class="form-control"
                                                                                    name="tests[]" placeholder="Test name">
                                                                            </div>
                                                                            <div class="form-group col-md-4">
                                                                                <label>Results</label>
                                                                                <input type="text" class="form-control"
                                                                                    name="results[]" placeholder="Results">
                                                                            </div>
                                                                            <div class="form-group col-md-4">
                                                                                <label>Test Date</label>
                                                                                <input type="date" class="form-control"
                                                                                    name="test_dates[]">
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <button type="button" class="btn btn-add-field mt-2"
                                                                    onclick="addTest(<?php echo $row['appointment_id']; ?>)">
                                                                    <i class="fas fa-plus"></i> Add Lab Test
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary"
                                                            data-dismiss="modal">Close</button>
                                                        <button type="submit" class="btn btn-primary">Save Record</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    <?php
                                }
                            } else {
                                echo '<div class="alert alert-info"><i class="fas fa-info-circle mr-2"></i>No confirmed appointments awaiting records.</div>';
                            }
                        }
                        mysqli_stmt_close($stmt);
                    }
                    ?>
                </div>
            </div>

            <!-- Patient Records -->
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-file-medical mr-2"></i>Patient Records History</h5>
                </div>
                <div class="card-body">
                    <?php
                    // Get patient records for the specialist
                    $sql = "SELECT pr.id as record_id, pr.created_at, pr.diagnosis, pr.notes, 
                           u.id as patient_id, u.first_name, u.last_name, 
                           a.appointment_date, a.treatment_type, a.status
                           FROM patient_records pr 
                           INNER JOIN users u ON pr.patient_id = u.id 
                           LEFT JOIN appointments a ON pr.appointment_id = a.id 
                           WHERE pr.specialist_id = ? 
                           ORDER BY pr.created_at DESC";

                    if ($stmt = mysqli_prepare($conn, $sql)) {
                        mysqli_stmt_bind_param($stmt, "i", $specialist_id);
                        if (mysqli_stmt_execute($stmt)) {
                            $result = mysqli_stmt_get_result($stmt);

                            if (mysqli_num_rows($result) > 0) {
                                while ($row = mysqli_fetch_assoc($result)) {
                                    ?>
                                    <div class="record-item">
                                        <div class="row">
                                            <div class="col-md-9">
                                                <h5 class="fas fa-user-check text-capitalize fw-semibold mb-2 text-muted"> <?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></h5>
                                                <p class="text-muted">
                                                    <small>
                                                        <i class="far fa-calendar-alt mr-1"></i>
                                                        <?php echo date('d M, Y', strtotime($row['created_at'])); ?> |
                                                        <i class="fas fa-stethoscope mr-1"></i>
                                                        <?php echo htmlspecialchars($row['treatment_type']); ?>  |
                                                       <i class="fas fa-check-circle text-success mr-1"></i>
                                                        <?php echo htmlspecialchars($row['status']); ?>
                                                    </small>
                                                </p>
                                            
                                            </div>
                                            <div class="col-md-3 d-flex align-items-start justify-content-end">
                                                <div class="action-buttons">
                                                    <button class="btn btn-sm btn-primary" data-toggle="modal"
                                                        data-target="#viewRecordModal<?php echo $row['record_id']; ?>">
                                                        <i class="fas fa-eye"></i> View
                                                    </button>
                                                    <button class="btn btn-sm btn-warning" data-toggle="modal"
                                                        data-target="#editRecordModal<?php echo $row['record_id']; ?>">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </button>
                                                    <button class="btn btn-sm btn-danger" onclick="confirmDelete(<?php echo $row['record_id']; ?>)">
                                                        <i class="fas fa-trash-alt"></i> Delete
                                                    </button>
                            
    <button type="button" class="btn btn-sm btn-primary" onclick="generatePDF(<?php echo $row['record_id']; ?>)">
                                                            <i class="fas fa-file-pdf mr-1"></i>PDF
                                                        </button>
                                                       
                                                    
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- View Record Modal -->
                                    <div class="modal fade" id="viewRecordModal<?php echo $row['record_id']; ?>" tabindex="-1"
                                        role="dialog" aria-labelledby="viewRecordModalLabel<?php echo $row['record_id']; ?>"
                                        aria-hidden="true">
                                        <div class="modal-dialog modal-lg" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="viewRecordModalLabel<?php echo $row['record_id']; ?>">
                                                        Patient Record: <?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?>
                                                    </h5>
                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                        <span aria-hidden="true">&times;</span>
                                                    </button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="row mb-3">
                                                        <div class="col-md-6">
                                                            <p><strong>Date:</strong> <?php echo date('d M, Y', strtotime($row['created_at'])); ?></p>
                                                            <p><strong>Treatment Type:</strong> <?php echo htmlspecialchars($row['treatment_type']); ?></p>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <p><strong>Patient ID:</strong> <?php echo $row['patient_id']; ?></p>
                                                            <p><strong>Record ID:</strong> <?php echo $row['record_id']; ?></p>
                                                        </div>
                                                    </div>

                                                    <div class="card mb-3">
                                                        <div class="card-header bg-light">
                                                            <h6 class="mb-0">Diagnosis</h6>
                                                        </div>
                                                        <div class="card-body">
                                                            <p><?php echo htmlspecialchars($row['diagnosis']); ?></p>
                                                        </div>
                                                    </div>

                                                    <?php if (!empty($row['notes'])): ?>
                                                    <div class="card mb-3">
                                                        <div class="card-header bg-light">
                                                            <h6 class="mb-0">Notes</h6>
                                                        </div>
                                                        <div class="card-body">
                                                            <p><?php echo htmlspecialchars($row['notes']); ?></p>
                                                        </div>
                                                    </div>
                                                    <?php endif; ?>

                                                    <ul class="nav nav-tabs" id="viewRecordTabs<?php echo $row['record_id']; ?>" role="tablist">
                                                        <li class="nav-item">
                                                            <a class="nav-link active" id="view-medications-tab<?php echo $row['record_id']; ?>"
                                                                data-toggle="tab" href="#view-medications<?php echo $row['record_id']; ?>"
                                                                role="tab">Medications</a>
                                                        </li>
                                                        <li class="nav-item">
                                                            <a class="nav-link" id="view-treatments-tab<?php echo $row['record_id']; ?>"
                                                                data-toggle="tab" href="#view-treatments<?php echo $row['record_id']; ?>"
                                                                role="tab">Treatments</a>
                                                        </li>
                                                        <li class="nav-item">
                                                            <a class="nav-link" id="view-tests-tab<?php echo $row['record_id']; ?>"
                                                                data-toggle="tab" href="#view-tests<?php echo $row['record_id']; ?>"
                                                                role="tab">Lab Tests</a>
                                                        </li>
                                                    </ul>

                                                    <div class="tab-content pt-3" id="viewRecordTabContent<?php echo $row['record_id']; ?>">
                                                        <!-- Medications Tab -->
                                                        <div class="tab-pane fade show active" id="view-medications<?php echo $row['record_id']; ?>"
                                                            role="tabpanel">
                                                            <?php
                                                            $med_sql = "SELECT medication_name, dosage, instructions FROM medications WHERE record_id = ?";
                                                            if ($med_stmt = mysqli_prepare($conn, $med_sql)) {
                                                                mysqli_stmt_bind_param($med_stmt, "i", $row['record_id']);
                                                                if (mysqli_stmt_execute($med_stmt)) {
                                                                    $med_result = mysqli_stmt_get_result($med_stmt);
                                                                    if (mysqli_num_rows($med_result) > 0) {
                                                                        echo '<table class="table table-bordered">';
                                                                        echo '<thead><tr><th>Medication</th><th>Dosage</th><th>Instructions</th></tr></thead>';
                                                                        echo '<tbody>';
                                                                        while ($med_row = mysqli_fetch_assoc($med_result)) {
                                                                            echo '<tr>';
                                                                            echo '<td>' . htmlspecialchars($med_row['medication_name']) . '</td>';
                                                                            echo '<td>' . htmlspecialchars($med_row['dosage']) . '</td>';
                                                                            echo '<td>' . htmlspecialchars($med_row['instructions']) . '</td>';
                                                                            echo '</tr>';
                                                                        }
                                                                        echo '</tbody></table>';
                                                                    } else {
                                                                        echo '<div class="alert alert-info">No medications prescribed.</div>';
                                                                    }
                                                                }
                                                                mysqli_stmt_close($med_stmt);
                                                            }
                                                            ?>
                                                        </div>

                                                        <!-- Treatments Tab -->
                                                        <div class="tab-pane fade" id="view-treatments<?php echo $row['record_id']; ?>"
                                                            role="tabpanel">
                                                            <?php
                                                            $treat_sql = "SELECT treatment_name, description FROM treatments WHERE record_id = ?";
                                                            if ($treat_stmt = mysqli_prepare($conn, $treat_sql)) {
                                                                mysqli_stmt_bind_param($treat_stmt, "i", $row['record_id']);
                                                                if (mysqli_stmt_execute($treat_stmt)) {
                                                                    $treat_result = mysqli_stmt_get_result($treat_stmt);
                                                                    if (mysqli_num_rows($treat_result) > 0) {
                                                                        echo '<table class="table table-bordered">';
                                                                        echo '<thead><tr><th>Treatment</th><th>Description</th></tr></thead>';
                                                                        echo '<tbody>';
                                                                        while ($treat_row = mysqli_fetch_assoc($treat_result)) {
                                                                            echo '<tr>';
                                                                            echo '<td>' . htmlspecialchars($treat_row['treatment_name']) . '</td>';
                                                                            echo '<td>' . htmlspecialchars($treat_row['description']) . '</td>';
                                                                            echo '</tr>';
                                                                        }
                                                                        echo '</tbody></table>';
                                                                    } else {
                                                                        echo '<div class="alert alert-info">No treatments recorded.</div>';
                                                                    }
                                                                }
                                                                mysqli_stmt_close($treat_stmt);
                                                            }
                                                            ?>
                                                        </div>

                                                        <!-- Lab Tests Tab -->
                                                        <div class="tab-pane fade" id="view-tests<?php echo $row['record_id']; ?>"
                                                            role="tabpanel">
                                                            <?php
                                                            $test_sql = "SELECT test_name, results, test_date FROM lab_tests WHERE record_id = ?";
                                                            if ($test_stmt = mysqli_prepare($conn, $test_sql)) {
                                                                mysqli_stmt_bind_param($test_stmt, "i", $row['record_id']);
                                                                if (mysqli_stmt_execute($test_stmt)) {
                                                                    $test_result = mysqli_stmt_get_result($test_stmt);
                                                                    if (mysqli_num_rows($test_result) > 0) {
                                                                        echo '<table class="table table-bordered">';
                                                                        echo '<thead><tr><th>Test</th><th>Results</th><th>Date</th></tr></thead>';
                                                                        echo '<tbody>';
                                                                        while ($test_row = mysqli_fetch_assoc($test_result)) {
                                                                            echo '<tr>';
                                                                            echo '<td>' . htmlspecialchars($test_row['test_name']) . '</td>';
                                                                            echo '<td>' . htmlspecialchars($test_row['results']) . '</td>';
                                                                            echo '<td>' . date('d M, Y', strtotime($test_row['test_date'])) . '</td>';
                                                                            echo '</tr>';
                                                                        }
                                                                        echo '</tbody></table>';
                                                                    } else {
                                                                        echo '<div class="alert alert-info">No lab tests recorded.</div>';
                                                                    }
                                                                }
                                                                mysqli_stmt_close($test_stmt);
                                                            }
                                                            ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Edit Record Modal -->
                                    <div class="modal fade" id="editRecordModal<?php echo $row['record_id']; ?>" tabindex="-1"
                                        role="dialog" aria-labelledby="editRecordModalLabel<?php echo $row['record_id']; ?>"
                                        aria-hidden="true">
                                        <?php include "edit_record_modal.php"; ?>
                                    </div>
                                    <?php

                                }
                            } else {
                                echo '<div class="alert alert-info"><i class="fas fa-info-circle mr-2"></i>No patient records found.</div>';
                            }
                        }
                        mysqli_stmt_close($stmt);
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Record Form (Hidden) -->
    <form id="deleteRecordForm" method="post" style="display: none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" id="delete_record_id" name="record_id" value="">
    </form>

    <!-- jQuery and Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function() {
            $('#sidebarCollapse').on('click', function() {
                $('#sidebar, #content').toggleClass('active');
            });
        });

        // Function to add a new medication field
        function addMedication(appointmentId) {
            const container = document.getElementById('medications-container' + appointmentId);
            const newRow = document.createElement('div');
            newRow.className = 'dynamic-form-row';
            newRow.innerHTML = `
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>Medication Name</label>
                        <input type="text" class="form-control" name="medications[]" placeholder="Medication name">
                    </div>
                    <div class="form-group col-md-3">
                        <label>Dosage</label>
                        <input type="text" class="form-control" name="dosages[]" placeholder="Dosage">
                    </div>
                    <div class="form-group col-md-4">
                        <label>Instructions</label>
                        <input type="text" class="form-control" name="med_instructions[]" placeholder="Instructions">
                    </div>
                    <div class="form-group col-md-1 d-flex align-items-end">
                        <button type="button" class="btn btn-remove-field" onclick="removeFormRow(this)">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            `;
            container.appendChild(newRow);
        }

        // Function to add a new treatment field
        function addTreatment(appointmentId) {
            const container = document.getElementById('treatments-container' + appointmentId);
            const newRow = document.createElement('div');
            newRow.className = 'dynamic-form-row';
            newRow.innerHTML = `
                <div class="form-row">
                    <div class="form-group col-md-5">
                        <label>Treatment Name</label>
                        <input type="text" class="form-control" name="treatments[]" placeholder="Treatment name">
                    </div>
                    <div class="form-group col-md-6">
                        <label>Description</label>
                        <input type="text" class="form-control" name="treatment_descriptions[]" placeholder="Description">
                    </div>
                    <div class="form-group col-md-1 d-flex align-items-end">
                        <button type="button" class="btn btn-remove-field" onclick="removeFormRow(this)">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            `;
            container.appendChild(newRow);
        }

        // Function to add a new lab test field
        function addTest(appointmentId) {
            const container = document.getElementById('tests-container' + appointmentId);
            const newRow = document.createElement('div');
            newRow.className = 'dynamic-form-row';
            newRow.innerHTML = `
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>Test Name</label>
                        <input type="text" class="form-control" name="tests[]" placeholder="Test name">
                    </div>
                    <div class="form-group col-md-3">
                        <label>Results</label>
                        <input type="text" class="form-control" name="results[]" placeholder="Results">
                    </div>
                    <div class="form-group col-md-4">
                        <label>Test Date</label>
                        <input type="date" class="form-control" name="test_dates[]">
                    </div>
                    <div class="form-group col-md-1 d-flex align-items-end">
                        <button type="button" class="btn btn-remove-field" onclick="removeFormRow(this)">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            `;
            container.appendChild(newRow);
        }

        // Function to remove a form row
        function removeFormRow(button) {
            const row = button.closest('.dynamic-form-row');
            row.parentNode.removeChild(row);
        }

        // Function to confirm record deletion
        function confirmDelete(recordId) {
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('delete_record_id').value = recordId;
                    document.getElementById('deleteRecordForm').submit();
                }
            });
        }

  // Improved PDF generation function with better error handling
function generatePDF(recordId) {
    // Validate input
    if (!recordId || isNaN(recordId)) {
        Swal.fire({
            icon: 'error',
            title: 'Invalid Record',
            text: 'The record ID appears to be invalid.'
        });
        return;
    }

    // Show loading indicator
    Swal.fire({
        title: 'Generating PDF...',
        html: 'Please wait while we prepare the patient history.',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    // Make AJAX request
    $.ajax({
        url: 'generate_patient_pdf.php',
        type: 'POST',
        data: { record_id: recordId },
        dataType: 'json',
        timeout: 80000, // 60 seconds timeout
        success: function(result) {
            Swal.close();
            console.log("Server response:", result);

            if (result && result.success) {
                // Create a temporary link to download the file
                const link = document.createElement('a');
                link.href = result.file_url;
                link.download = result.filename;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);

                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: 'Patient history PDF has been generated successfully.',
                    confirmButtonColor: '#3085d6'
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    html: 'Server error: ' + (result && result.message ? result.message : 'Unknown error occurred.'),
                    footer: '<a href="javascript:void(0)" onclick="showTechDetails()">View technical details</a>',
                    confirmButtonColor: '#3085d6'
                });

                window.technicalDetails = JSON.stringify(result, null, 2);
            }
        },
        error: function(xhr, status, error) {
            Swal.close();

            console.error("AJAX Error:", status, error);
            console.error("Status Code:", xhr.status);
            console.error("Response Text:", xhr.responseText);

            let errorMessage = 'An error occurred while generating the PDF.';
            let technicalInfo = '';

            if (xhr.responseText && xhr.responseText.trim().startsWith('<')) {
                // Response is HTML (error page)
                technicalInfo = xhr.responseText;
            } else {
                try {
                    const jsonResponse = JSON.parse(xhr.responseText);
                    if (jsonResponse && jsonResponse.message) {
                        errorMessage = jsonResponse.message;
                    }
                    technicalInfo = JSON.stringify(jsonResponse, null, 2);
                } catch (e) {
                    technicalInfo = xhr.responseText || 'No response text available';
                }
            }

            window.technicalDetails = 'Status: ' + status + '\nError: ' + error +
                                       '\nStatus Code: ' + xhr.status +
                                       '\nResponse:\n' + technicalInfo;

            Swal.fire({
                icon: 'error',
                title: 'Error',
                html: errorMessage,
                footer: '<a href="javascript:void(0)" onclick="showTechDetails()">View technical details</a>',
                confirmButtonColor: '#3085d6'
            });
        }
    });
}

// Function to show technical details
function showTechDetails() {
    Swal.fire({
        title: 'Technical Details',
        html: '<pre style="text-align: left; max-height: 400px; overflow-y: auto;">' +
              (window.technicalDetails || 'No details available').replace(/</g, '&lt;').replace(/>/g, '&gt;') +
              '</pre>',
        width: 800,
        confirmButtonColor: '#3085d6'
    });
}

    </script>
</body>

</html>