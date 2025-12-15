<?php
// Start output buffering immediately at the very beginning
ob_start();

// Enable full error reporting and logging
ini_set('display_errors', 0); // Turn off output of errors to browser
error_reporting(E_ALL);

// Log file for detailed debugging
$log_file = '../logs/pdf_generation_' . date('Y-m-d') . '.log';
function logMessage($message)
{
    global $log_file;
    $dir = dirname($log_file);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    file_put_contents($log_file, date('[Y-m-d H:i:s] ') . $message . PHP_EOL, FILE_APPEND);
}

try {
    logMessage("Script started");

    // Check if TCPDF library exists
    $tcpdf_path = '../vendor/tecnickcom/tcpdf/tcpdf.php';
    if (!file_exists($tcpdf_path)) {
        throw new Exception("TCPDF library not found at: $tcpdf_path");
    }
    logMessage("TCPDF library found");

    require_once '../vendor/autoload.php';
    logMessage("Autoloader included");

    // Include database connection
    $config_path = "../config.php";
    if (!file_exists($config_path)) {
        throw new Exception("Config file not found at: $config_path");
    }
    require_once $config_path;
    logMessage("Database config included");

    // Verify database connection
    if (!isset($conn) || !$conn) {
        throw new Exception("Database connection failed");
    }
    logMessage("Database connection verified");

    // Check if the request method is POST and record_id is set
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Request method is not POST: ' . $_SERVER['REQUEST_METHOD']);
    }

    if (!isset($_POST['record_id'])) {
        throw new Exception('Missing record ID in POST data');
    }

    $record_id = $_POST['record_id'];
    logMessage("Received record_id: $record_id");

    // Validate record_id
    if (!filter_var($record_id, FILTER_VALIDATE_INT)) {
        throw new Exception('Invalid record ID format');
    }

    
    // Fetch patient and record information
    $sql = "SELECT pr.id as record_id, pr.patient_id, pr.specialist_id, pr.appointment_id, 
           pr.diagnosis, pr.notes, pr.created_at, 
           u1.first_name, u1.last_name, u1.email, u1.mobile as phone, u1.cnic,
           u1.state, u1.city, 
           a.treatment_type, a.appointment_date,
           u2.first_name as specialist_first_name, u2.last_name as specialist_last_name,
           s.specialization
    FROM patient_records pr 
    JOIN users u1 ON pr.patient_id = u1.id 
    JOIN specialists s ON pr.specialist_id = s.id
    JOIN users u2 ON s.user_id = u2.id
    JOIN appointments a ON pr.appointment_id = a.id
    WHERE pr.id = ?";

    if (!$stmt = mysqli_prepare($conn, $sql)) {
        throw new Exception('Failed to prepare statement: ' . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($stmt, "i", $record_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (!$result) {
        throw new Exception('Query execution failed: ' . mysqli_error($conn));
    }

    if (!$row = mysqli_fetch_assoc($result)) {
        throw new Exception('Record not found for ID: ' . $record_id);
    }

    logMessage("Patient record data fetched successfully");

    // Include TCPDF library
    require_once $tcpdf_path;
    logMessage("TCPDF library loaded");

    // Create custom PDF class with header and footer
    class MYPDF extends TCPDF
    {
        protected $header_title = 'DERMACARE PATIENT MEDICAL RECORD';
        protected $footer_text = 'DermaCare Management System - Confidential Medical Record';
        protected $logo_path;

        public function setLogoPath($path)
        {
            $this->logo_path = $path;
        }

        // Page header
        public function Header()
        {
            // Logo
            if (file_exists($this->logo_path)) {
                $this->Image($this->logo_path, 15, 10, 30, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
            }

            // Set font
            $this->SetFont('helvetica', 'B', 16);

            // Title
            $this->SetY(15);
            $this->SetX(50);
            $this->Cell(0, 10, $this->header_title, 0, false, 'L');

            // Subtitle
            $this->SetFont('helvetica', 'I', 9);
            $this->SetY(20);
            $this->SetX(50);
            $this->Cell(0, 10, 'Specialized Skin Care Management', 0, false, 'L');

            // Draw line
            $this->SetY(30);
            $this->SetDrawColor(0, 102, 153);
            $this->SetLineWidth(0.5);
            $this->Line(15, 30, 195, 30);
        }

        // Page footer
        public function Footer()
        {
            // Position at 15 mm from bottom
            $this->SetY(-15);
            // Set font
            $this->SetFont('helvetica', 'I', 8);
            // Draw line
            $this->SetDrawColor(0, 102, 153);
            $this->SetLineWidth(0.5);
            $this->Line(15, $this->GetY() - 2, 195, $this->GetY() - 2);
            // Footer text
            $this->Cell(0, 10, $this->footer_text . ' | Page ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, false, 'C');
        }
    }

    // Create new PDF document
    $pdf = new MYPDF('P', 'mm', 'A4', true, 'UTF-8', false);

    // Set logo path
    $logo_path = 'C:\xampp\htdocs\skincare\images\logo.png';
    $pdf->setLogoPath($logo_path);
    logMessage("Logo path set: $logo_path");

    $pdf->SetCreator('Derma Elixir Management System');
    $pdf->SetAuthor('Derma Elixir Studio ');
    $pdf->SetTitle('Patient Medical Record');
    $pdf->SetSubject('Medical Record for ' . $row['first_name'] . ' ' . $row['last_name']);
    $pdf->SetKeywords('DermaCare, Medical Record, Patient, Dermatology');

    // Set document properties
    $pdf->SetMargins(15, 40, 15);
    $pdf->SetAutoPageBreak(TRUE, 20);
    $pdf->SetHeaderMargin(5);
    $pdf->SetFooterMargin(10);

    $pdf->AddPage();
    logMessage("PDF page added");

    // Define colors
    $header_bg_color = array(0, 102, 153); // Blue
    $header_text_color = array(255, 255, 255); // White
    $alternate_row_color = array(240, 247, 250); // Light blue
    $border_color = array(0, 102, 153); // Blue

    // Set up for drawing rounded borders
    $pdf->SetLineStyle(array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => $border_color));

    // Patient Information Section
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->SetFillColor($header_bg_color[0], $header_bg_color[1], $header_bg_color[2]);
    $pdf->SetTextColor($header_text_color[0], $header_text_color[1], $header_text_color[2]);

    // Draw a nice rounded rectangle for section header
    $pdf->RoundedRect(15, $pdf->GetY(), 180, 8, 2, '1001', 'F');
    $pdf->Cell(180, 8, 'PATIENT INFORMATION', 0, 1, 'L', 0);
    $pdf->Ln(2);

    // Reset text color for content
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('helvetica', '', 10);

    // Calculate age if date of birth is available
    $age = '';
    if (!empty($row['date_of_birth'])) {
        $dob = new DateTime($row['date_of_birth']);
        $now = new DateTime();
        $age = $dob->diff($now)->y . ' years';
    }

    // Create a 2-column layout for patient details
    $pdf->SetFillColor($alternate_row_color[0], $alternate_row_color[1], $alternate_row_color[2]);

    // Left Column
    $pdf->SetXY(15, $pdf->GetY());
    $pdf->Cell(85, 7, 'Full Name:', 1, 0, 'L', 1);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(85, 7, $row['first_name'] . ' ' . $row['last_name'], 1, 1, 'L', 0);
    $pdf->SetFont('helvetica', '', 10);

    $pdf->SetX(15);
    $pdf->Cell(85, 7, 'Patient ID:', 1, 0, 'L', 1);
    $pdf->Cell(85, 7, $row['patient_id'], 1, 1, 'L', 0);

    $pdf->SetX(15);
    $pdf->Cell(85, 7, 'CNIC:', 1, 0, 'L', 1);
    $pdf->Cell(85, 7, $row['cnic'] ?? 'N/A', 1, 1, 'L', 0);

    $pdf->SetX(15);
    $pdf->Cell(85, 7, 'Gender:', 1, 0, 'L', 1);
    $pdf->Cell(85, 7, $row['gender'] ?? 'N/A', 1, 1, 'L', 0);

    $pdf->SetX(15);
    $pdf->Cell(85, 7, 'Date of Birth / Age:', 1, 0, 'L', 1);
    $dob_display = '';
    if (!empty($row['date_of_birth'])) {
        $dob_display = date('d M, Y', strtotime($row['date_of_birth'])) . ' (' . $age . ')';
    } else {
        $dob_display = 'N/A';
    }
    $pdf->Cell(85, 7, $dob_display, 1, 1, 'L', 0);

    $pdf->SetX(15);
    $pdf->Cell(85, 7, 'Contact Number:', 1, 0, 'L', 1);
    $pdf->Cell(85, 7, $row['phone'] ?? 'N/A', 1, 1, 'L', 0);

    $pdf->SetX(15);
    $pdf->Cell(85, 7, 'Email:', 1, 0, 'L', 1);
    $pdf->Cell(85, 7, $row['email'] ?? 'N/A', 1, 1, 'L', 0);

    $pdf->SetX(15);
    $pdf->Cell(85, 7, 'Address:', 1, 0, 'L', 1);
    $address = trim(($row['city'] ?? '') . ', ' . ($row['state'] ?? ''), ', ');
    $address = empty($address) ? 'N/A' : $address;
    $pdf->Cell(85, 7, $address, 1, 1, 'L', 0);

    $pdf->Ln(5);

    // Medical Record Information
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->SetFillColor($header_bg_color[0], $header_bg_color[1], $header_bg_color[2]);
    $pdf->SetTextColor($header_text_color[0], $header_text_color[1], $header_text_color[2]);

    $pdf->RoundedRect(15, $pdf->GetY(), 180, 8, 2, '1001', 'F');
    $pdf->Cell(180, 8, 'MEDICAL RECORD DETAILS', 0, 1, 'L', 0);
    $pdf->Ln(2);

    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('helvetica', '', 10);

    $created_date = !empty($row['created_at']) ? date('d M, Y', strtotime($row['created_at'])) : 'N/A';
    $appointment_date = !empty($row['appointment_date']) ? date('d M, Y', strtotime($row['appointment_date'])) : 'N/A';

    $pdf->SetFillColor($alternate_row_color[0], $alternate_row_color[1], $alternate_row_color[2]);

    $pdf->SetX(15);
    $pdf->Cell(85, 7, 'Record ID:', 1, 0, 'L', 1);
    $pdf->Cell(85, 7, $row['record_id'], 1, 1, 'L', 0);

    $pdf->SetX(15);
    $pdf->Cell(85, 7, 'Record Date:', 1, 0, 'L', 1);
    $pdf->Cell(85, 7, $created_date, 1, 1, 'L', 0);

    $pdf->SetX(15);
    $pdf->Cell(85, 7, 'Appointment Date:', 1, 0, 'L', 1);
    $pdf->Cell(85, 7, $appointment_date, 1, 1, 'L', 0);

    $pdf->SetX(15);
    $pdf->Cell(85, 7, 'Treatment Type:', 1, 0, 'L', 1);
    $pdf->Cell(85, 7, $row['treatment_type'] ?? 'N/A', 1, 1, 'L', 0);

    $pdf->SetX(15);
    $pdf->Cell(85, 7, 'Specialist:', 1, 0, 'L', 1);
    $specialist_name = ($row['specialist_first_name'] ?? '') . ' ' . ($row['specialist_last_name'] ?? '');
    $specialist_name = trim($specialist_name) ? $specialist_name : 'N/A';
    $specialist_info = $specialist_name;
    if (!empty($row['specialization'])) {
        $specialist_info .= ' (' . $row['specialization'] . ')';
    }
    $pdf->Cell(85, 7, $specialist_info, 1, 1, 'L', 0);

    $pdf->Ln(3);

    // Diagnosis and Notes section with better formatting
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->SetX(15);
    $pdf->Cell(180, 7, 'Diagnosis:', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 10);

    $pdf->SetX(15);
    $pdf->RoundedRect(15, $pdf->GetY(), 180, 25, 2, '1111');
    $pdf->MultiCell(180, 25, $row['diagnosis'] ?? 'N/A', 0, 'L', 0, 1, 15, $pdf->GetY());

    $pdf->Ln(3);

    if (!empty($row['notes'])) {
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->SetX(15);
        $pdf->Cell(180, 7, 'Clinical Notes:', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 10);

        $pdf->SetX(15);
        $pdf->RoundedRect(15, $pdf->GetY(), 180, 25, 2, '1111');
        $pdf->MultiCell(180, 25, $row['notes'], 0, 'L', 0, 1, 15, $pdf->GetY());

        $pdf->Ln(3);
    }

    // Medications Section
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->SetFillColor($header_bg_color[0], $header_bg_color[1], $header_bg_color[2]);
    $pdf->SetTextColor($header_text_color[0], $header_text_color[1], $header_text_color[2]);

    $pdf->RoundedRect(15, $pdf->GetY(), 180, 8, 2, '1001', 'F');
    $pdf->Cell(180, 8, 'PRESCRIBED MEDICATIONS', 0, 1, 'L', 0);
    $pdf->Ln(2);

    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('helvetica', '', 10);

    $med_sql = "SELECT medication_name, dosage, instructions FROM medications WHERE record_id = ?";
    if ($med_stmt = mysqli_prepare($conn, $med_sql)) {
        mysqli_stmt_bind_param($med_stmt, "i", $record_id);
        mysqli_stmt_execute($med_stmt);
        $med_result = mysqli_stmt_get_result($med_stmt);

        if (mysqli_num_rows($med_result) > 0) {
            // Table header
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->SetFillColor($header_bg_color[0], $header_bg_color[1], $header_bg_color[2]);
            $pdf->SetTextColor($header_text_color[0], $header_text_color[1], $header_text_color[2]);

            $pdf->SetX(15);
            $pdf->Cell(60, 7, 'Medication', 1, 0, 'C', 1);
            $pdf->Cell(50, 7, 'Dosage', 1, 0, 'C', 1);
            $pdf->Cell(70, 7, 'Instructions', 1, 1, 'C', 1);

            // Reset text color for data
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetFont('helvetica', '', 10);

            $pdf->SetFillColor($alternate_row_color[0], $alternate_row_color[1], $alternate_row_color[2]);
            $fill = 0;
            while ($med_row = mysqli_fetch_assoc($med_result)) {
                $pdf->SetX(15);
                $pdf->Cell(60, 7, $med_row['medication_name'] ?? 'N/A', 1, 0, 'L', $fill);
                $pdf->Cell(50, 7, $med_row['dosage'] ?? 'N/A', 1, 0, 'L', $fill);
                $pdf->Cell(70, 7, $med_row['instructions'] ?? 'N/A', 1, 1, 'L', $fill);
                $fill = !$fill;
            }
        } else {
            $pdf->SetX(15);
            $pdf->Cell(180, 7, 'No medications prescribed for this record.', 1, 1, 'C');
        }
        mysqli_stmt_close($med_stmt);
    }
    logMessage("Medications added to PDF");

    $pdf->Ln(5);

    // Treatments Section
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->SetFillColor($header_bg_color[0], $header_bg_color[1], $header_bg_color[2]);
    $pdf->SetTextColor($header_text_color[0], $header_text_color[1], $header_text_color[2]);

    $pdf->RoundedRect(15, $pdf->GetY(), 180, 8, 2, '1001', 'F');
    $pdf->Cell(180, 8, 'RECOMMENDED TREATMENTS', 0, 1, 'L', 0);
    $pdf->Ln(2);

    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('helvetica', '', 10);

    $treat_sql = "SELECT treatment_name, description FROM treatments WHERE record_id = ?";
    if ($treat_stmt = mysqli_prepare($conn, $treat_sql)) {
        mysqli_stmt_bind_param($treat_stmt, "i", $record_id);
        mysqli_stmt_execute($treat_stmt);
        $treat_result = mysqli_stmt_get_result($treat_stmt);

        if (mysqli_num_rows($treat_result) > 0) {
            // Table header
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->SetFillColor($header_bg_color[0], $header_bg_color[1], $header_bg_color[2]);
            $pdf->SetTextColor($header_text_color[0], $header_text_color[1], $header_text_color[2]);

            $pdf->SetX(15);
            $pdf->Cell(60, 7, 'Treatment', 1, 0, 'C', 1);
            $pdf->Cell(120, 7, 'Description', 1, 1, 'C', 1);

            // Reset text color for data
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetFont('helvetica', '', 10);

            $pdf->SetFillColor($alternate_row_color[0], $alternate_row_color[1], $alternate_row_color[2]);
            $fill = 0;
            while ($treat_row = mysqli_fetch_assoc($treat_result)) {
                $pdf->SetX(15);
                $pdf->Cell(60, 7, $treat_row['treatment_name'] ?? 'N/A', 1, 0, 'L', $fill);
                $pdf->Cell(120, 7, $treat_row['description'] ?? 'N/A', 1, 1, 'L', $fill);
                $fill = !$fill;
            }
        } else {
            $pdf->SetX(15);
            $pdf->Cell(180, 7, 'No treatments recommended for this record.', 1, 1, 'C');
        }
        mysqli_stmt_close($treat_stmt);
    }
    logMessage("Treatments added to PDF");

    $pdf->Ln(5);

    // Lab Tests Section
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->SetFillColor($header_bg_color[0], $header_bg_color[1], $header_bg_color[2]);
    $pdf->SetTextColor($header_text_color[0], $header_text_color[1], $header_text_color[2]);

    $pdf->RoundedRect(15, $pdf->GetY(), 180, 8, 2, '1001', 'F');
    $pdf->Cell(180, 8, 'LAB TEST RESULTS', 0, 1, 'L', 0);
    $pdf->Ln(2);

    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('helvetica', '', 10);

    $lab_sql = "SELECT test_name, results FROM lab_tests WHERE record_id = ?";
    if ($lab_stmt = mysqli_prepare($conn, $lab_sql)) {
        mysqli_stmt_bind_param($lab_stmt, "i", $record_id);
        mysqli_stmt_execute($lab_stmt);
        $lab_result = mysqli_stmt_get_result($lab_stmt);

        if (mysqli_num_rows($lab_result) > 0) {
            // Table header
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->SetFillColor($header_bg_color[0], $header_bg_color[1], $header_bg_color[2]);
            $pdf->SetTextColor($header_text_color[0], $header_text_color[1], $header_text_color[2]);

            $pdf->SetX(15);
            $pdf->Cell(90, 7, 'Test Name', 1, 0, 'C', 1);
            $pdf->Cell(90, 7, 'Result', 1, 1, 'C', 1);

            // Reset text color for data
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetFont('helvetica', '', 10);

            $pdf->SetFillColor($alternate_row_color[0], $alternate_row_color[1], $alternate_row_color[2]);
            $fill = 0;
            while ($lab_row = mysqli_fetch_assoc($lab_result)) {
                $pdf->SetX(15);
                $pdf->Cell(90, 7, $lab_row['test_name'] ?? 'N/A', 1, 0, 'L', $fill);
                // Check both possible column names
                $result_value = isset($lab_row['results']) ? $lab_row['results'] : ($lab_row['result'] ?? 'N/A');
                $pdf->Cell(90, 7, $result_value, 1, 1, 'L', $fill);
                $fill = !$fill;
            }
        } else {
            $pdf->SetX(15);
            $pdf->Cell(180, 7, 'No lab tests recorded for this record.', 1, 1, 'C');
        }
        mysqli_stmt_close($lab_stmt);
    }
    logMessage("Lab tests added to PDF");

    // Add signature section
    $pdf->Ln(10);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetTextColor($text_color[0], $text_color[1], $text_color[2]);
    $pdf->SetX(15);
    $pdf->Cell(85, 7, 'Specialist Signature: _______________________', 0, 0, 'L');
    $pdf->Cell(85, 7, 'Date: _______________________', 0, 1, 'L');

    // Add disclaimer/confidentiality notice
    $pdf->Ln(10);
    $pdf->SetFont('helvetica', 'I', 8);
    $pdf->SetTextColor(100, 100, 100);
    $pdf->MultiCell(180, 4, 'CONFIDENTIALITY NOTICE: This document contains protected health information that is confidential and privileged. If you are not the intended recipient, please contact DermaCare Management System immediately. Unauthorized use, disclosure, copying, or distribution is strictly prohibited and may be unlawful.', 0, 'L', 0);

    // Use absolute path for the directory
    $upload_dir = dirname(__DIR__) . '/Records/';
    logMessage("Using absolute path for directory: $upload_dir");

    // Make sure the directory exists
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            throw new Exception("Failed to create directory: $upload_dir");
        }
        logMessage("Created directory: $upload_dir");
    }

    // Check directory permissions
    if (!is_writable($upload_dir)) {
        throw new Exception("Directory not writable: $upload_dir");
    }
    logMessage("Directory is writable: $upload_dir");

    // Additional debugging
    $absolute_path = realpath($upload_dir) ?: "Directory doesn't exist";
    logMessage("Absolute path to directory: $absolute_path");
    logMessage("Current script path: " . __DIR__);

    if (function_exists('posix_getpwuid') && function_exists('posix_geteuid')) {
        $process_user = posix_getpwuid(posix_geteuid());
        logMessage("Process running as user: " . $process_user['name']);
    } else {
        logMessage("Cannot determine process user (posix functions not available)");
    }

    // Save the file
    $filename = 'patient_record_' . $record_id . '_' . time() . '.pdf';
    $filepath = $upload_dir . $filename;

    // Empty output buffer before PDF generation to avoid any interference
    ob_clean();

    // Attempt to generate PDF
    try {
        logMessage("Attempting to save PDF to: $filepath");
        ob_start(); // Capture any HTML error output from TCPDF
        $pdf->Output($filepath, 'F'); // Save to file
        $tcpdf_output = ob_get_clean();

        // Check if there was an error message in the output
        if ($tcpdf_output && strpos($tcpdf_output, 'TCPDF ERROR') !== false) {
            throw new Exception("TCPDF error: " . strip_tags($tcpdf_output));
        }
    } catch (Exception $pdfException) {
        logMessage("PDF generation error: " . $pdfException->getMessage());
        throw new Exception("PDF engine error: " . $pdfException->getMessage());
    }

    // Verify the file was created
    if (!file_exists($filepath)) {
        throw new Exception("PDF file was not created at: $filepath");
    }

    $filesize = filesize($filepath);
    logMessage("PDF file created successfully. Size: $filesize bytes");

    // Get any output that might have been generated before our JSON
    $output_before_json = ob_get_clean();

    // Log any unexpected output
    if (!empty($output_before_json)) {
        logMessage("WARNING: Output before JSON: " . $output_before_json);
    }

    // Now set the JSON header
    header('Content-Type: application/json');

    // Start fresh output buffer
    ob_start();

    // Return success response with correct path relative to web root
    echo json_encode([
        'success' => true,
        'file_url' => '../Records/' . $filename, // Adjust this path to be correct from web perspective
        'filename' => $filename
    ]);

    // Flush the buffer with just our JSON
    ob_end_flush();
    logMessage("Success response sent");

} catch (Exception $e) {
    logMessage("ERROR: " . $e->getMessage());

    // Collect PHP errors and any other output
    $php_errors = ob_get_clean();
    if (!empty($php_errors)) {
        logMessage("Unexpected output before error: " . $php_errors);
    }

    // Set JSON header
    header('Content-Type: application/json');

    // Send error response
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug_info' => 'Check logs for details'
    ]);
}
?>