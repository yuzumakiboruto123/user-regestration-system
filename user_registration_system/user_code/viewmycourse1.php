<?php
session_start(); // Start session for student

require_once '../includes/auth_check.php'; // Role/permission guard
require_once '../includes/db.php';         // DB connection helper

requireRole(['Student']); // Only allow Student role to access this page

$pdo = getDB(); // Get PDO instance
$userID = $_SESSION['userID']; // Logged-in user's ID

// Get StudentID linked to this user
$q = $pdo->prepare("SELECT StudentID FROM student WHERE UserID = ?");
$q->execute([$userID]);
$student = $q->fetch(PDO::FETCH_ASSOC);

// If no student record found → stop
if (!$student) {
    die("Student profile not found");
}

$studentID = $student['StudentID']; // Extract StudentID

// ------------------------------------------------------------
// Fetch all courses the student is enrolled in
// Using Enrollment table + join with Course table
// ------------------------------------------------------------
$sql = "
    SELECT 
        c.CourseName, 
        c.CourseCode, 
        c.Credits, 
        c.Description, 
        c.StartDate,
        e.EnrollmentDate, 
        e.Status
    FROM enrollment e
    INNER JOIN course c ON e.CourseID = c.CourseID
    WHERE e.StudentID = ?
    ORDER BY c.CourseName ASC
";

$stmt = $pdo->prepare($sql); // Prepare query
$stmt->execute([$studentID]); // Run with student ID
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC); // Fetch list of enrolled courses
?>
<!DOCTYPE html>
<html>
<head>
<title>My Courses</title> <!-- Page title -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"> <!-- Bootstrap -->

<style>
/* Page background styling */
body{
    background: linear-gradient(135deg,#212e68,#9e69d3);
    min-height:100vh;
    padding:40px;
    font-family:Poppins,sans-serif;
}

/* White container box */
.box{
    max-width:800px;
    margin:auto;
    background:#fff;
    border-radius:12px;
    padding:25px;
}

/* Title style */
h2{ 
    text-align:center; 
    margin-bottom:25px; 
}

/* Individual course display */
.course-box{
    padding:15px;
    border-radius:10px;
    margin-bottom:15px;
    background:#f4f6f9;
    border-left:5px solid #4b6cb7;
}
</style>
</head>

<body>

<div class="box"> <!-- Main container -->

<h2>My Enrolled Courses</h2> <!-- Page heading -->

<?php if (empty($courses)): ?> <!-- Case: No courses -->
    <p>No courses enrolled yet.</p>

<?php else: ?> <!-- Loop enrolled courses -->
    <?php foreach ($courses as $c): ?>
        <div class="course-box"> <!-- Each course card -->
            <h4>
                <?= htmlspecialchars($c['CourseName']) ?> 
                (<?= htmlspecialchars($c['CourseCode']) ?>)
            </h4>

            <p><strong>Credits:</strong> <?= $c['Credits'] ?></p>
            <p><strong>Status:</strong> <?= htmlspecialchars($c['Status']) ?></p>
            <p><strong>Start Date:</strong> <?= htmlspecialchars($c['StartDate']) ?></p>
            <p><strong>Enrolled On:</strong> <?= htmlspecialchars($c['EnrollmentDate']) ?></p>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<!-- Back to dashboard -->
<a href="dashboard.php" class="btn btn-primary mt-3">⬅ Back to Dashboard</a>

</div>

</body>
</html>
