<?php
// Include config file
require_once "../config.php";

// Check if user is admin
require_admin();


$_SESSION["user_role"] = "admin"; // or "admin" 

// Process edit/delete actions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action']) && isset($_POST['user_id'])) {
        $user_id = intval($_POST['user_id']);

        if ($_POST['action'] == 'delete') {
            if (delete_specialist($user_id)) {
                $_SESSION['success_msg'] = "Specialist deleted successfully.";
            } else {
                $_SESSION['error_msg'] = "Failed to delete specialist.";
            }
            
            // Redirect to refresh the page
            header("Location: manage_specialists.php");
            exit;
        }
    }
}

// Get all specialists 
$specialists = get_all_specialists();

// Helper functions (if not already defined in config.php)
function get_all_specialists() {
    global $conn;
    $sql = "SELECT u.*, s.experience, s.specialization 
            FROM users u 
            JOIN specialists s ON u.id = s.user_id 
            WHERE u.user_type = 'specialist'
            ORDER BY u.first_name ASC";
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

function delete_specialist($user_id) {
    global $conn;
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Delete from specialists table first (child)
        $sql1 = "DELETE FROM specialists WHERE user_id = ?";
        $stmt1 = $conn->prepare($sql1);
        $stmt1->bind_param("i", $user_id);
        $stmt1->execute();
        
        // Then delete from users table (parent)
        $sql2 = "DELETE FROM users WHERE id = ? AND user_type = 'specialist'";
        $stmt2 = $conn->prepare($sql2);
        $stmt2->bind_param("i", $user_id);
        $stmt2->execute();
        
        // Commit transaction
        $conn->commit();
        return true;
    } catch (Exception $e) {
        // Roll back transaction on error
        $conn->rollback();
        return false;
    }
}

// Function to format specialization for display
function format_specialization($specialization) {
    switch ($specialization) {
        case 'skin_care':
            return 'Skin Care';
        case 'laser':
            return 'Laser Treatment';
        case 'cosmetic':
            return 'Cosmetic Treatment';
        case 'hair':
            return 'Hair Treatment';
        default:
            return ucfirst($specialization);
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

        /* Badge styles */
        .badge-specialization {
            font-size: 85%;
            padding: 0.35em 0.65em;
            font-weight: 500;
        }

        .badge-skin_care {
            background-color: #a29bfe;
            color: white;
        }

        .badge-laser {
            background-color: #ff7675;
            color: white;
        }

        .badge-cosmetic {
            background-color: #74b9ff;
            color: white;
        }

        .badge-hair {
            background-color: #55efc4;
            color: white;
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
                    <h4 class="mb-0">Specialist Management</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb bg-transparent p-0 mb-0">
                            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Manage Specialists</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <button class="btn btn-primary" onclick="location.href='add_specialist.php'">
                        <i class="fas fa-plus-circle mr-1"></i> Add New Specialist
                    </button>
                </div>
            </div>

            <!-- Display success/error messages -->
            <?php if (isset($_SESSION['success_msg'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['success_msg']; unset($_SESSION['success_msg']); ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_msg'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['error_msg']; unset($_SESSION['error_msg']); ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>

            <!-- Specialists Management Table -->
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-user-md mr-2"></i>All Specialists</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($specialists)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle mr-2"></i> No specialists found.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>Mobile</th>
                                        <th>Specialization</th>
                                        <th>Experience</th>
                                        <th>Location</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($specialists as $specialist): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($specialist['first_name'] . ' ' . $specialist['last_name']); ?></td>
                                            <td><?php echo htmlspecialchars($specialist['username']); ?></td>
                                            <td><?php echo htmlspecialchars($specialist['email']); ?></td>
                                            <td><?php echo htmlspecialchars($specialist['mobile']); ?></td>
                                            <td>
                                                <span class="badge badge-specialization badge-<?php echo $specialist['specialization']; ?>">
                                                    <?php echo format_specialization($specialist['specialization']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo $specialist['experience']; ?> years</td>
                                            <td><?php echo htmlspecialchars($specialist['city'] . ', ' . $specialist['state']); ?></td>
                                            <td>
                                                <a href="edit_specialist.php?id=<?php echo $specialist['id']; ?>" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form method="post" class="d-inline">
                                                    <input type="hidden" name="user_id" value="<?php echo $specialist['id']; ?>">
                                                    <input type="hidden" name="action" value="delete">
                                                    <button type="submit" class="btn btn-sm btn-danger"
                                                            onclick="return confirm('Are you sure you want to delete this specialist? This action cannot be undone.')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
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

    <!-- jQuery and Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        $(document).ready(function () {
            // Auto-dismiss alerts after 5 seconds
            window.setTimeout(function() {
                $(".alert").fadeTo(500, 0).slideUp(500, function(){
                    $(this).remove(); 
                });
            }, 5000);
        });
    </script>
</body>

</html>