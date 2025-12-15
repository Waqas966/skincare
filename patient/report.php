<?php
// Include config file
require_once "../config.php";

// Check if user is logged in and is a patient
require_patient();

// Get patient data
$user_id = $_SESSION['user_id'];

// Set success message from redirect
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $success_message = "Appointment cancelled successfully.";
}

// Fetch patient's records and associated PDFs
$reports_sql = "SELECT pr.id as record_id, pr.diagnosis, pr.created_at, 
                 a.appointment_date,
                u.first_name as specialist_first_name, u.last_name as specialist_last_name 
                FROM patient_records pr 
                JOIN appointments a ON pr.appointment_id = a.id
                JOIN specialists s ON pr.specialist_id = s.id
                JOIN users u ON s.user_id = u.id
                WHERE pr.patient_id = ?
                ORDER BY pr.created_at DESC";

$reports = [];
if ($stmt = mysqli_prepare($conn, $reports_sql)) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($result)) {
        // Check if PDF exists for this record
        $pdf_pattern = "../Records/patient_record_" . $row['record_id'] . "_*.pdf";
        $matching_files = glob($pdf_pattern);
        
        // Add PDF file path if it exists
        if (!empty($matching_files)) {
            $row['pdf_file'] = $matching_files[0]; // Get the first matching PDF
        }
        
        $reports[] = $row;
    }
    mysqli_stmt_close($stmt);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Reports - Derma Elixir Studio</title>
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

        /* Status badges */
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
            text-transform: uppercase;
        }

        /* Card styling for reports */
        .report-card {
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            margin-bottom: 20px;
            border: none;
        }

        .report-card:hover {
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
            transform: translateY(-2px);
        }

        .report-card .card-header {
            background-color: var(--secondary-color);
            color: white;
            font-weight: bold;
            border: none;
            padding: 15px 20px;
        }

        .report-card .card-body {
            padding: 20px;
        }

        .report-meta {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 15px;
        }

   
        .btn-download {
            background-color: var(--success-color);
            color: white;
            border: none;
            font-weight: 500;
            transition: all 0.3s;
        }

        .btn-download:hover {
            background-color: #27ae60;
            color: white;
        }

        .report-placeholder {
            text-align: center;
            padding: 50px 20px;
            background-color: #f8f9fa;
            border-radius: 8px;
            margin-top: 20px;
        }

        /* Modal styling */
        .pdf-modal .modal-content {
            border-radius: 8px;
            overflow: hidden;
        }

        .pdf-modal .modal-header {
            background-color: var(--secondary-color);
            color: white;
        }

        .pdf-modal .modal-body {
            padding: 0;
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
                    <h4 class="mb-0">Medical Reports</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb bg-transparent p-0 mb-0">
                            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Medical Reports</li>
                        </ol>
                    </nav>
                </div>
            </div>

            <div class="container-fluid">
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success" role="alert">
                        <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <?php if (empty($reports)): ?>
                        <div class="col-12">
                            <div class="report-placeholder">
                                <i class="fas fa-file-medical-alt fa-4x mb-3 text-muted"></i>
                                <h4>No Medical Reports Available</h4>
                                <p class="text-muted">Your medical reports will appear here after your doctor creates them.</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($reports as $report): ?>
                            <div class="col-lg-6 col-md-12">
                                <div class="card report-card">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <span>Medical Report #<?php echo $report['record_id']; ?></span>
                                        <span class="badge badge-light">
                                            <?php echo date('d M, Y', strtotime($report['created_at'])); ?>
                                        </span>
                                    </div>
                                    <div class="card-body">
                                        <div class="report-meta">
                                            <div> <i class="fas fa-user-md"></i> | <strong>Specialist Name :</strong> Dr. <?php echo htmlspecialchars($report['specialist_first_name'] . ' ' . $report['specialist_last_name']); ?></div>
                                            <div><i class="fas fa-calendar-alt"></i> | <strong>Appointment Date:</strong> <?php echo date('d M, Y', strtotime($report['appointment_date'])); ?></div>
                                        </div>
                                        <div class="diagnosis-preview mb-3">
                                            <strong>Diagnosis:</strong>
                                            <p class="text-muted mb-0">
                                                <?php echo substr(htmlspecialchars($report['diagnosis']), 0, 100) . (strlen($report['diagnosis']) > 100 ? '...' : ''); ?>
                                            </p>
                                        </div>
                                        <div class="text-right">
                                            <?php if (isset($report['pdf_file'])): ?>
                                                <a href="<?php echo $report['pdf_file']; ?>" download class="btn btn-download ml-2">
                                                    <i class="fas fa-download mr-1"></i> Download
                                                </a>
                                            <?php else: ?>
                                                <button class="btn btn-secondary" disabled>
                                                    <i class="fas fa-file-pdf mr-1"></i> PDF Not Available
                                                </button>
                                                <button type="button" class="btn btn-primary generate-pdf ml-2" data-record-id="<?php echo $report['record_id']; ?>">
                                                    <i class="fas fa-file-medical mr-1"></i> Generate PDF
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery and Bootstrap JS -->
     <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        
        $(document).ready(function () {
            // Sidebar toggle
            $('#sidebarCollapse').on('click', function () {
                $('#sidebar').toggleClass('active');
            });

            // Generate PDF button handler
            $('.generate-pdf').on('click', function() {
                const recordId = $(this).data('record-id');
                const btn = $(this);
                
                // Change button text and disable it
                btn.html('<i class="fas fa-spinner fa-spin mr-1"></i> Generating...').prop('disabled', true);
                
                // Send AJAX request to generate PDF
                $.ajax({
                    url: 'generate_patient_pdf.php',
                    type: 'POST',
                    data: {
                        record_id: recordId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Show success message
                            $('<div class="alert alert-success alert-dismissible fade show" role="alert">' +
                              'PDF generated successfully! <button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
                              '<span aria-hidden="true">&times;</span></button></div>').insertBefore('.row').delay(3000).fadeOut();
                              
                            // Reload the page to show the new PDF
                            setTimeout(function() {
                                location.reload();
                            }, 2000);
                        } else {
                            // Show error message
                            $('<div class="alert alert-danger alert-dismissible fade show" role="alert">' +
                              'Failed to generate PDF: ' + response.message + ' <button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
                              '<span aria-hidden="true">&times;</span></button></div>').insertBefore('.row');
                              
                            // Reset button
                            btn.html('<i class="fas fa-file-medical mr-1"></i> Generate PDF').prop('disabled', false);
                        }
                    },
                    error: function() {
                        // Show connection error
                        $('<div class="alert alert-danger alert-dismissible fade show" role="alert">' +
                          'Connection error while generating PDF. Please try again. <button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
                          '<span aria-hidden="true">&times;</span></button></div>').insertBefore('.row');
                          
                        // Reset button
                        btn.html('<i class="fas fa-file-medical mr-1"></i> Generate PDF').prop('disabled', false);
                    }
                });
            });
        });
        
    </script>
</body>

</html>