<?php
// Include config file
require_once "../config.php";

// Check if user is admin
require_admin();

// Check if a valid user ID was provided
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $user_id = intval($_GET['id']);

    // Start transaction
    $conn->begin_transaction();

    try {
        // First, delete from patients table (child record)
        $sql = "DELETE FROM patients WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();

        // Then, delete from users table (parent record)
        $sql = "DELETE FROM users WHERE id = ? AND user_type = 'patient'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();

        // Check if any rows were affected to confirm deletion
        if ($stmt->affected_rows > 0) {
            // Commit transaction
            $conn->commit();
            $_SESSION['success_msg'] = "Patient record has been deleted successfully.";
        } else {
            // No rows affected, rollback
            $conn->rollback();
            $_SESSION['error_msg'] = "No patient found with ID $user_id.";
        }
        $stmt->close();

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $_SESSION['error_msg'] = "Error deleting patient: " . $e->getMessage();
    }

} else {
    $_SESSION['error_msg'] = "Invalid patient ID provided.";
}

// Redirect back to manage_patients.php
header("Location: manage_patients.php");
exit;
?>