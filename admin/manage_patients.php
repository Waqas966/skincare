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
            // Update user approval status
            $sql = "UPDATE users SET approval_status = 'approved' WHERE id = ? AND user_type = 'patient'";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $user_id);

            if ($stmt->execute()) {
                $_SESSION['success_msg'] = "Patient registration approved successfully.";
            } else {
                $_SESSION['error_msg'] = "Failed to approve patient registration: " . $conn->error;
            }
            $stmt->close();
        } elseif ($_POST['action'] == 'reject') {
            // Update user approval status
            $sql = "UPDATE users SET approval_status = 'rejected' WHERE id = ? AND user_type = 'patient'";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $user_id);

            if ($stmt->execute()) {
                $_SESSION['success_msg'] = "Patient registration rejected.";
            } else {
                $_SESSION['error_msg'] = "Failed to reject patient registration: " . $conn->error;
            }
            $stmt->close();
        }

        // Redirect to refresh the page
        header("Location: manage_patients.php");
        exit;
    }
}

// Function to get all patients with their approval status
function get_all_patients($conn, $status = null)
{
    $patients = array();

    $sql = "SELECT u.id, u.first_name, u.last_name, u.email, u.mobile, u.cnic, u.state, u.city, 
            u.created_at, u.approval_status, p.certificate 
            FROM users u 
            JOIN patients p ON u.id = p.user_id 
            WHERE u.user_type = 'patient'";

    if ($status !== null) {
        $sql .= " AND u.approval_status = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $status);
    } else {
        $stmt = $conn->prepare($sql);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $patients[] = $row;
    }

    $stmt->close();
    return $patients;
}

// Get patients by status
$pending_patients = get_all_patients($conn, 'pending');
$approved_patients = get_all_patients($conn, 'approved');
$rejected_patients = get_all_patients($conn, 'rejected');
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
            width: 250px;
            background-color: var(--secondary-color);
            color: white;
            transition: all 0.3s;
            box-shadow: 3px 0 10px rgba(0, 0, 0, 0.1);
            z-index: 999;
            position: fixed;
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
            width: calc(100% - 250px);
            min-height: 100vh;
            transition: all 0.3s;
            padding: 20px;
            margin-left: 250px;
        }

        /* Header */
        .dashboard-header {
            background-color: white;
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.05);
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

        /* Certificate preview */
        .certificate-preview {
            max-width: 100px;
            cursor: pointer;
        }

        /* Modal for certificate */
        .modal-certificate img {
            max-width: 100%;
        }

        /* Status badges */
        .badge-pending {
            background-color: #f39c12;
            color: white;
        }

        .badge-approved {
            background-color: var(--success-color);
            color: white;
        }

        .badge-rejected {
            background-color: var(--danger-color);
            color: white;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            #sidebar {
                margin-left: -250px;
                position: absolute;
            }

            #sidebar.active {
                margin-left: 0;
            }

            #content {
                width: 100%;
                margin-left: 0;
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
                    <h4 class="mb-0">Patients Managements</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb bg-transparent p-0 mb-0">
                             <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Manage Patients</li>
                           
                        </ol>
                    </nav>
                </div>
            </div>


      

            <!-- Display Messages -->
            <?php if (isset($_SESSION['success_msg'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php
                    echo $_SESSION['success_msg'];
                    unset($_SESSION['success_msg']);
                    ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_msg'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php
                    echo $_SESSION['error_msg'];
                    unset($_SESSION['error_msg']);
                    ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>

            <!-- Patients Management -->
            <div class="card">
                <div class="card-body">
                    <!-- Nav tabs -->
                    <ul class="nav nav-tabs" id="patientTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="pending-tab" data-toggle="tab" href="#pending" role="tab"
                                aria-controls="pending" aria-selected="true">
                                Pending
                                <span
                                    class="badge badge-pill badge-pending"><?php echo count($pending_patients); ?></span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="approved-tab" data-toggle="tab" href="#approved" role="tab"
                                aria-controls="approved" aria-selected="false">
                                Approved
                                <span
                                    class="badge badge-pill badge-approved"><?php echo count($approved_patients); ?></span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="rejected-tab" data-toggle="tab" href="#rejected" role="tab"
                                aria-controls="rejected" aria-selected="false">
                                Rejected
                                <span
                                    class="badge badge-pill badge-rejected"><?php echo count($rejected_patients); ?></span>
                            </a>
                        </li>
                    </ul>

                    <!-- Tab panes -->
                    <div class="tab-content">
    <!-- Pending Patients Tab -->
    <div class="tab-pane fade show active" id="pending" role="tabpanel" aria-labelledby="pending-tab">
        <div class="table-responsive mt-3">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Mobile</th>
                        <th>CNIC</th>
                        <th>Location</th>
                        <th>Certificate</th>
                        <th>Registered On</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($pending_patients)): ?>
                        <tr>
                            <td colspan="9" class="text-center">No pending patient registrations</td>
                        </tr>
                    <?php else: ?>
                        <?php $counter = 1; ?>
                        <?php foreach ($pending_patients as $patient): ?>
                            <tr>
                                <td><?php echo $counter++; ?></td> <!-- Sequential Numbering -->
                                <td><?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($patient['email']); ?></td>
                                <td><?php echo htmlspecialchars($patient['mobile']); ?></td>
                                <td><?php echo htmlspecialchars($patient['cnic']); ?></td>
                                <td><?php echo htmlspecialchars($patient['city'] . ', ' . $patient['state']); ?></td>
                                <td>
                                    <?php if ($patient['certificate']): ?>
                                        <img src="../uploads/certificates/<?php echo $patient['certificate']; ?>" 
                                             alt="Certificate" class="certificate-preview" 
                                             data-toggle="modal" data-target="#certificateModal<?php echo $patient['id']; ?>">
                                    <?php else: ?>
                                        Not uploaded
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($patient['created_at'])); ?></td>
                                <td>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="user_id" value="<?php echo $patient['id']; ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <button type="submit" class="btn btn-sm btn-success">
                                            <i class="fas fa-check"></i> Approve
                                        </button>
                                    </form>
                                    <form method="POST" class="d-inline ml-1">
                                        <input type="hidden" name="user_id" value="<?php echo $patient['id']; ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="fas fa-times"></i> Reject
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Approved Patients Tab -->
    <div class="tab-pane fade" id="approved" role="tabpanel" aria-labelledby="approved-tab">
        <div class="table-responsive mt-3">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Mobile</th>
                        <th>CNIC</th>
                        <th>Location</th>
                        <th>Certificate</th>
                        <th>Registered On</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($approved_patients)): ?>
                        <tr>
                            <td colspan="9" class="text-center">No approved patients</td>
                        </tr>
                    <?php else: ?>
                        <?php $counter = 1; ?>
                        <?php foreach ($approved_patients as $patient): ?>
                            <tr>
                                <td><?php echo $counter++; ?></td>
                                <td><?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($patient['email']); ?></td>
                                <td><?php echo htmlspecialchars($patient['mobile']); ?></td>
                                <td><?php echo htmlspecialchars($patient['cnic']); ?></td>
                                <td><?php echo htmlspecialchars($patient['city'] . ', ' . $patient['state']); ?></td>
                                <td>
                                    <?php if ($patient['certificate']): ?>
                                        <img src="../uploads/certificates/<?php echo $patient['certificate']; ?>" 
                                             alt="Certificate" class="certificate-preview" 
                                             data-toggle="modal" data-target="#certificateModal<?php echo $patient['id']; ?>">
                                    <?php else: ?>
                                        Not uploaded
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($patient['created_at'])); ?></td>
                                <td>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="user_id" value="<?php echo $patient['id']; ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="fas fa-ban"></i> Revoke
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Rejected Patients Tab -->
    <div class="tab-pane fade" id="rejected" role="tabpanel" aria-labelledby="rejected-tab">
        <div class="table-responsive mt-3">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Mobile</th>
                        <th>CNIC</th>
                        <th>Location</th>
                        <th>Certificate</th>
                        <th>Registered On</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rejected_patients)): ?>
                        <tr>
                            <td colspan="9" class="text-center">No rejected patients</td>
                        </tr>
                    <?php else: ?>
                        <?php $counter = 1; ?>
                        <?php foreach ($rejected_patients as $patient): ?>
                            <tr>
                                <td><?php echo $counter++; ?></td>
                                <td><?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($patient['email']); ?></td>
                                <td><?php echo htmlspecialchars($patient['mobile']); ?></td>
                                <td><?php echo htmlspecialchars($patient['cnic']); ?></td>
                                <td><?php echo htmlspecialchars($patient['city'] . ', ' . $patient['state']); ?></td>
                                <td>
                                    <?php if ($patient['certificate']): ?>
                                        <img src="../uploads/certificates/<?php echo $patient['certificate']; ?>" 
                                             alt="Certificate" class="certificate-preview" 
                                             data-toggle="modal" data-target="#certificateModal<?php echo $patient['id']; ?>">
                                    <?php else: ?>
                                        Not uploaded
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($patient['created_at'])); ?></td>
                                <td>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="user_id" value="<?php echo $patient['id']; ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <button type="submit" class="btn btn-sm btn-success">
                                            <i class="fas fa-check"></i> Approve
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

                </div>
            </div>
        </div>
    </div>

    <!-- jQuery and Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.min.js"></script>

    <script>
        $(document).ready(function () {
            // Sidebar toggle for mobile view
            $('#sidebarCollapse').on('click', function () {
                $('#sidebar').toggleClass('active');
            });

            // Close alerts after 5 seconds
            setTimeout(function () {
                $(".alert").alert('close');
            }, 5000);
        });
    </script>
</body>

</html>