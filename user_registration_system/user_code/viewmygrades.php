<?php
session_start(); // Start session to access logged-in user data and maintain session state

require_once '../includes/auth_check.php'; // Include login/auth validation helper (ensures user is logged in)
require_once '../includes/db.php';         // Include database connection helper function
require_once '../includes/user_model.php'; // Include user model (for calculateAndUpdateGPA function)

requireRole(['Student']); // Role-based access control → only students can view grades

$pdo    = getDB();              // Fetch shared PDO database instance
$userID = $_SESSION['userID'];  // Get logged-in UserID from session

// -------------------------------------------------------
// STEP 1: Fetch the student record linked to this UserID
// -------------------------------------------------------
// The student table stores StudentID linked to UserID.
// We need StudentID to fetch grades + calculate GPA.
$q = $pdo->prepare("SELECT StudentID FROM student WHERE UserID = ?");
$q->execute([$userID]); // Execute with logged-in user's ID
$student = $q->fetch(PDO::FETCH_ASSOC); // Fetch row as associative array

// If student account doesn't exist → something is wrong → stop execution
if (!$student) {
    die("Student not found");
}

$studentID = $student['StudentID']; // Extract StudentID for further operations

// -------------------------------------------------------
// STEP 2: Recalculate GPA dynamically from enrollment table
// -------------------------------------------------------
// The function calculateAndUpdateGPA():
//   - Reads all FinalGrade entries for this student
//   - Converts them to grade points
//   - Computes weighted GPA using credits
//   - Updates GPA in student table
//   - Returns the calculated result
$gpa = calculateAndUpdateGPA($studentID);

// -------------------------------------------------------
// STEP 3: Retrieve detailed grade breakdown for all enrolled courses
// -------------------------------------------------------
// We join enrollment table with course table to fetch:
//
// - CourseName
// - CourseCode
// - Credits
// - FinalGrade
// - Enrollment Status (Completed, In Progress, Pending)
//
// Sorted alphabetically by course name.
//
$sql = "
    SELECT 
        c.CourseName, 
        c.CourseCode, 
        c.Credits,
        e.FinalGrade, 
        e.Status
    FROM enrollment e
    INNER JOIN course c ON e.CourseID = c.CourseID
    WHERE e.StudentID = ?
    ORDER BY c.CourseName ASC
";

$stmt = $pdo->prepare($sql);  // Prepare SQL query
$stmt->execute([$studentID]); // Execute using StudentID
$grades = $stmt->fetchAll(PDO::FETCH_ASSOC); // Fetch all grade rows
?>
<!DOCTYPE html>
<html>
<head>
<title>My Grades</title> <!-- Browser tab title -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"> <!-- Bootstrap import -->

<style>
/* -------------------------------------------------------
   PAGE DESIGN & STYLING
   ------------------------------------------------------- */

/* Body background: gradient + padding + Poppins font */
body{
    background: linear-gradient(135deg,#212e68,#9e69d3);
    min-height:100vh;
    padding:40px;
    font-family:Poppins,sans-serif;
}

/* White card box for content */
.box{
    max-width:850px;
    margin:auto;
    background:#fff;
    padding:25px;
    border-radius:12px;
}

/* Box for each grade item */
.grade-card{
    background:#f4f6f9;
    border-left:5px solid #9b59b6; /* Purple highlight */
    padding:15px;
    margin-bottom:15px;
    border-radius:10px;
}

/* GPA display container */
.gpa-box{
    background:#4b6cb7;       /* Blue gradient */
    padding:18px;
    border-radius:10px;
    color:#fff;
    margin-bottom:20px;
    text-align:center;
}
</style>
</head>

<body>

<div class="box"> <!-- Main content container -->

<h2 class="text-center"> My Grades</h2> <!-- Page heading -->

<!-- -------------------------------------------------------
     GPA Display Section
     ------------------------------------------------------- -->
<div class="gpa-box">
    <h4>Your GPA: <?= $gpa !== null ? htmlspecialchars($gpa) : "N/A" ?></h4>
</div>

<!-- -------------------------------------------------------
     CASE 1: Student has no grades yet
     ------------------------------------------------------- -->
<?php if (empty($grades)): ?>
    <p>No grades available yet.</p>

<!-- -------------------------------------------------------
     CASE 2: Student has at least one grade → loop through them
     ------------------------------------------------------- -->
<?php else: ?>
    <?php foreach ($grades as $g): ?>
        <div class="grade-card">

            <!-- Course name + code -->
            <h4>
                <?= htmlspecialchars($g['CourseName']) ?> 
                (<?= htmlspecialchars($g['CourseCode']) ?>)
            </h4>

            <!-- Course credit value -->
            <p><strong>Credits:</strong> <?= htmlspecialchars($g['Credits']) ?></p>

            <!-- Enrollment status (Completed, In Progress, Pending) -->
            <p><strong>Status:</strong> <?= htmlspecialchars($g['Status']) ?></p>

            <!-- Final grade (if available) -->
            <p>
                <strong>Final Grade:</strong> 
                <?= $g['FinalGrade'] !== null ? htmlspecialchars($g['FinalGrade']) : "Not Graded" ?>
            </p>

        </div>
    <?php endforeach; ?>
<?php endif; ?>

<!-- Back to dashboard button -->
<a href="dashboard.php" class="btn btn-primary mt-3">⬅ Back to Dashboard</a>

</div>

</body>
</html>
