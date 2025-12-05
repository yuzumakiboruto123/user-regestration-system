<?php
/**
 * DELETE ENROLLMENT – BACKEND ACTION
 * ----------------------------------
 * This script handles deleting an enrollment record.
 * It:
 *   - Checks if the user is logged in
 *   - Confirms the user is Admin or Staff
 *   - Accepts ID from POST or GET
 *   - Prevents deleting records that already have a final grade
 *   - Deletes the enrollment safely
 */

require_once __DIR__ . '/../includes/db.php';         // Database connection
require_once __DIR__ . '/../includes/auth_check.php'; // Login check helper
require_once __DIR__ . '/../includes/auth_model.php'; // Role checking functions

// Start session if needed
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Make sure the user is logged in
if (!isset($_SESSION['userID'])) {
    header("Location: ../login.php");
    exit;
}

// Only Admin or Staff can delete enrollment records
requireRole(['Admin', 'Staff']);

$pdo = getDB(); // Connect to database

// Allow both POST and GET for safety
// (Sometimes deletes come from a form, sometimes from a link)
$enrollmentId = 0;

// ID coming from POST form
if (isset($_POST['id'])) {
    $enrollmentId = (int)$_POST['id'];
}

// ID coming from POST under alternate name
if (isset($_POST['EnrollmentID'])) {
    $enrollmentId = (int)$_POST['EnrollmentID'];
}

// ID coming from a GET link
if (isset($_GET['id'])) {
    $enrollmentId = (int)$_GET['id'];
}

// If ID is not valid
if ($enrollmentId <= 0) {
    $_SESSION['error_message'] = "Invalid enrollment ID.";
    header("Location: list_enrollment.php");
    exit;
}

// Load the record to confirm it exists
$stmt = $pdo->prepare("SELECT FinalGrade FROM enrollment WHERE EnrollmentID = :id");
$stmt->execute([':id' => $enrollmentId]);
$row = $stmt->fetch();

// If record does not exist
if (!$row) {
    $_SESSION['error_message'] = "Enrollment record not found.";
    header("Location: list_enrollment.php");
    exit;
}

// Prevent deleting enrollment that already has a grade
// (This protects academic record integrity)
if ($row['FinalGrade'] !== null) {
    $_SESSION['error_message'] = "Cannot delete enrollment: it already has a final grade.";
    header("Location: list_enrollment.php");
    exit;
}

// Perform safe delete
$stmt = $pdo->prepare("DELETE FROM enrollment WHERE EnrollmentID = :id");
$stmt->execute([':id' => $enrollmentId]);

// Success message
$_SESSION['success_message'] = "Enrollment deleted successfully.";

// Redirect back to list
header("Location: list_enrollment.php");
exit;
