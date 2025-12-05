<?php
session_start(); // Start session for staff user

require_once '../includes/auth_check.php'; // Auth role checker
require_once '../includes/db.php';         // Database connection

requireRole(['Staff']); // Only staff can access this page

$username = $_SESSION['username']; // Logged-in staff username
$pdo = getDB(); // Get DB instance

/* ---------------------------------------------------
   STEP 1: Fetch StaffID and assigned CourseID
   by matching username from User + Staff tables
   --------------------------------------------------- */
$stmt = $pdo->prepare("
    SELECT StaffID, CourseID 
    FROM staff 
    INNER JOIN user ON staff.UserID = user.UserID 
    WHERE user.Username = ?
");
$stmt->execute([$username]);
$staff = $stmt->fetch(PDO::FETCH_ASSOC);

$staffID  = $staff['StaffID'] ?? 0;  
$courseID = $staff['CourseID'] ?? 0;

/* ---------------------------------------------------
   If staff has no assigned course, skip fetching students
   --------------------------------------------------- */
if ($courseID == 0) {
    $students = [];
} else {

    /* ---------------------------------------------------
       STEP 2: Fetch enrolled students for this course
       FIXED JOIN → StudentID = Student.StudentID
       --------------------------------------------------- */
    $query = "
        SELECT 
            s.StudentID,
            CONCAT(s.FirstName, ' ', s.LastName) AS StudentName, 
            s.Email,
            c.CourseName
        FROM enrollment e
        INNER JOIN student s ON e.StudentID = s.StudentID
        INNER JOIN course c  ON e.CourseID  = c.CourseID
        WHERE e.CourseID = ?
        ORDER BY s.FirstName
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute([$courseID]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Enrolled Students</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
  body {
    background: linear-gradient(135deg, #141E30, #243B55);
    color: #fff;
    font-family: "Poppins", sans-serif;
  }
  .container {
    background: rgba(255,255,255,0.1);
    border-radius: 15px;
    padding: 30px;
    margin-top: 40px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.3);
  }
  table { color: #fff; margin-top: 20px; }
  th { background: #4b6cb7; }
  a.btn-back {
    margin-top: 15px;
    display: inline-block;
    background: #3498db;
    color: #fff;
    padding: 10px 20px;
    border-radius: 8px;
    text-decoration: none;
  }
  a.btn-back:hover { background: #21618c; }
</style>
</head>

<body>
<div class="container">
  <h2 class="text-center mb-4">🎓 Enrolled Students</h2>

  <?php if ($courseID == 0): ?>
      <div class="alert alert-warning text-center">
          ❗ You are not assigned to any course yet.
      </div>

  <?php elseif (empty($students)): ?>
      <div class="alert alert-info text-center">
          No students enrolled in your course yet.
      </div>

  <?php else: ?>
  <table class="table table-striped table-hover">
    <thead class="table-dark">
      <tr>
        <th>Student ID</th>
        <th>Student Name</th>
        <th>Email</th>
        <th>Course</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($students as $s): ?>
      <tr>
        <td><?= $s['StudentID'] ?></td>
        <td><?= htmlspecialchars($s['StudentName']) ?></td>
        <td><?= htmlspecialchars($s['Email']) ?></td>
        <td><?= htmlspecialchars($s['CourseName']) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>

  <div class="text-center">
    <a href="dashboard.php" class="btn-back">⬅ Back to Dashboard</a>
  </div>
</div>
</body>
</html>
