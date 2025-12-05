<?php
/**********************************************
 *  delete_student.php
 *  --------------------
 *  Shows confirmation page → Deletes student
 *  Deletes from BOTH:
 *      - student table
 *      - user table (linked account)
 **********************************************/

/* Include login + role guard */
require_once '../includes/auth_check.php';

/* Include PDO database connection helper */
require_once '../includes/db.php';

/* Only Admin & Staff allowed */
requireRole(['Admin', 'Staff']);

/* Connect DB */
$pdo = getDB();

/* Store messages */
$message = '';
$message_type = '';

/* -----------------------------------------------------------
   1. Validate & fetch student details (via GET id)
----------------------------------------------------------- */
$studentID = $_GET['id'] ?? null;

if (!$studentID || !ctype_digit($studentID)) {
    die("Invalid student ID.");
}

/* Fetch student + linked user info */
$sql = "
    SELECT s.StudentID, s.UserID, s.FirstName, s.LastName, s.Email, s.IsActive
    FROM student s
    WHERE s.StudentID = :sid
";
$stmt = $pdo->prepare($sql);
$stmt->execute([':sid' => $studentID]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

/* If student does not exist */
if (!$student) {
    die("Student not found.");
}

/* -----------------------------------------------------------
   2. When delete button is pressed → delete in DB
----------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {

    try {
        $pdo->beginTransaction();

        /* Delete from student table */
        $stmt = $pdo->prepare("DELETE FROM student WHERE StudentID = :sid");
        $stmt->execute([':sid' => $student['StudentID']]);

        /* Delete linked User Account */
        $stmt2 = $pdo->prepare("DELETE FROM user WHERE UserID = :uid");
        $stmt2->execute([':uid' => $student['UserID']]);

        $pdo->commit();

        $message = "Student and linked user account deleted successfully!";
        $message_type = "success";

        /* After success hide form */
        $student = null;

    } catch (PDOException $e) {

        if ($pdo->inTransaction()) $pdo->rollBack();

        $message = "Database Error: " . htmlspecialchars($e->getMessage());
        $message_type = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Delete Student</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<!-- INLINE CSS FOR DELETE PAGE -->
<style>
body {
    margin: 0;
    padding: 30px;
    background: #f3f4f6;
    font-family: system-ui, sans-serif;
}

.page {
    max-width: 600px;
    margin: 0 auto;
    background: white;
    padding: 30px 35px;
    border-radius: 16px;
    box-shadow: 0 15px 35px rgba(0,0,0,0.10);
}

h1 {
    margin-top: 0;
    font-size: 28px;
    text-align: center;
    color: #b91c1c;
}

h3 {
    margin-bottom: 10px;
    color: #374151;
    font-size: 20px;
    font-weight: 600;
}

/* Flash Messages */
.alert {
    padding: 12px 14px;
    border-radius: 10px;
    margin-bottom: 18px;
    font-size: 15px;
}
.alert-success {
    background: #dcfce7;
    color: #166534;
    border: 1px solid #15803d;
}
.alert-error {
    background: #fee2e2;
    color: #b91c1c;
    border: 1px solid #b91c1c;
}
.alert-warning {
    background: #fef3c7;
    color: #92400e;
    border: 1px solid #fbbf24;
}

/* Form */
.form-box {
    margin-top: 20px;
}

.form-group {
    margin-bottom: 18px;
}

label {
    display: block;
    margin-bottom: 6px;
    color: #4b5563;
    font-weight: 600;
}

input {
    width: 100%;
    padding: 10px 12px;
    border-radius: 10px;
    border: 1px solid #d1d5db;
    font-size: 14px;
    background: #f9fafb;
}

input[readonly] {
    background: #e5e7eb;
}

/* Buttons */
.btn {
    padding: 10px 20px;
    border-radius: 10px;
    font-size: 15px;
    cursor: pointer;
    text-decoration: none;
    font-weight: 600;
}

.btn-delete {
    background: #dc2626;
    color: white;
    border: none;
}
.btn-delete:hover {
    background: #b91c1c;
}

.btn-cancel {
    background: #6b7280;
    color: white;
    text-decoration: none;
    margin-left: 8px;
}
.btn-cancel:hover {
    background: #4b5563;
}

</style>
</head>

<body>

<div class="page">

    <h1>⚠️ Delete Student</h1>

    <!-- Flash messages -->
    <?php if ($message): ?>
        <div class="alert alert-<?php echo htmlspecialchars($message_type); ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <?php if ($student): ?>
    
    <div class="alert alert-warning">
        You are about to <strong>permanently delete</strong> this student and their linked user account.
        <br>This action cannot be undone.
    </div>

    <h3>Student Information</h3>

    <form method="POST" class="form-box">

        <div class="form-group">
            <label>Full Name</label>
            <input type="text" value="<?= htmlspecialchars($student['FirstName'] . ' ' . $student['LastName']) ?>" readonly>
        </div>

        <div class="form-group">
            <label>Email (Read Only)</label>
            <input type="email" value="<?= htmlspecialchars($student['Email']) ?>" readonly>
        </div>

        <div class="form-group">
            <label>Account Status</label>
            <input type="text" value="<?= $student['IsActive'] ? 'Active' : 'Inactive' ?>" readonly>
        </div>

        <!-- Confirm delete -->
        <button type="submit" name="confirm_delete" class="btn btn-delete">
            ❌ Yes, Delete This Student
        </button>

        <a href="student_list.php" class="btn btn-cancel">Cancel</a>

    </form>

    <?php endif; ?>

</div>

</body>
</html>
