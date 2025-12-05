<?php
// Start session only if it's not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load authentication + database helpers
require_once __DIR__ . '/../includes/auth_check.php';  // Ensures user is logged in
require_once __DIR__ . '/../includes/auth_model.php';  // Provides role checking
require_once __DIR__ . '/../includes/db.php';          // Database connection

// User must be logged in; otherwise redirect to login page
if (!isset($_SESSION['userID'])) {
    header("Location: ../login.php");
    exit;
}

// Only Admin or Staff can update enrollment records
requireRole(['Admin', 'Staff']);

$pdo = getDB(); // Get shared database connection

// -------------------------------
// Read form data from POST request
// -------------------------------
$enrollID = $_POST['EnrollmentID'] ?? '';   // Enrollment record ID
$student  = $_POST['StudentID'] ?? '';      // Student ID
$course   = $_POST['CourseID'] ?? '';       // Course ID
$date     = $_POST['EnrollmentDate'] ?? ''; // Enrollment date
$status   = $_POST['Status'] ?? '';         // Enrollment status
$grade    = $_POST['Grade'] ?? null;        // Final grade (optional)

// ---------------------------------------------
// Basic validation: required fields must be filled
// ---------------------------------------------
if (!$enrollID || !$student || !$course || !$date || !$status) {
    $_SESSION['error_message'] = "Please complete all required fields.";
    header("Location: edit_enrollment.php?id=" . $enrollID);
    exit;
}

// If grade field was left empty, convert it to NULL
if ($grade === "") {
    $grade = null;
}

try {
    // ----------------------------------------------------
    // Prepare SQL UPDATE query to modify enrollment record
    // ----------------------------------------------------
    $sql = "UPDATE enrollment
            SET StudentID = :stu,
                CourseID = :course,
                EnrollmentDate = :dt,
                Status = :st,
                FinalGrade = :gr
            WHERE EnrollmentID = :id";

    $stmt = $pdo->prepare($sql);

    // Bind values and execute update query
    $stmt->execute([
        ':stu'    => $student,
        ':course' => $course,
        ':dt'     => $date,
        ':st'     => $status,
        ':gr'     => $grade,
        ':id'     => $enrollID
    ]);

    // Store a success message and redirect back to the list page
    $_SESSION['success_message'] = "Enrollment updated successfully!";
    header("Location: list_enrollment.php");
    exit;

} catch (Exception $e) {

    // If an error occurs, show an error message and return user to edit page
    $_SESSION['error_message'] = "Error updating enrollment: " . $e->getMessage();
    header("Location: edit_enrollment.php?id=" . $enrollID);
    exit;
}
