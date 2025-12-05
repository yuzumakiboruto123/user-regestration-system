<?php
// Start the session for the logged-in staff user
session_start();

// Load role/auth protection
require_once '../includes/auth_check.php';
// Load database connection helper
require_once '../includes/db.php';

// Allow only Staff to access this page
requireRole(['Staff']);

// Read logged-in username from session
$username = $_SESSION['username'] ?? '';

// Create database connection
$pdo = getDB();

// ---------------------------------------------------------
// STEP 1: Fetch staff record linked with this username
// ---------------------------------------------------------
$staffSql = "
    SELECT s.StaffID, s.CourseName
    FROM staff s
    INNER JOIN user u ON s.UserID = u.UserID
    WHERE u.Username = :uname
";
$staffStmt = $pdo->prepare($staffSql);
$staffStmt->execute([':uname' => $username]);
$staffRow = $staffStmt->fetch(PDO::FETCH_ASSOC);

// If the staff profile is missing
if (!$staffRow) {
    $courses = [];      // No courses to display
    $courseCount = 0;   // No count
    $staffCourseName = null; // No course name
} else {

    // Extract staff's CourseName from staff table
    $staffCourseName = $staffRow['CourseName'];

    // ---------------------------------------------------------
    // STEP 2: Fetch all matching courses for this CourseName
    // ---------------------------------------------------------
    $courseSql = "
        SELECT 
            c.CourseID,
            c.CourseName,
            c.CourseCode,
            c.Description,
            c.Credits,
            c.Fee,
            c.StartDate,
            c.IsActive
        FROM course c
        WHERE c.CourseName = :cname
        ORDER BY c.StartDate DESC, c.CourseID DESC
    ";

    $courseStmt = $pdo->prepare($courseSql);
    $courseStmt->execute([':cname' => $staffCourseName]);
    $courses = $courseStmt->fetchAll(PDO::FETCH_ASSOC);

    // Count assigned courses
    $courseCount = count($courses);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"> <!-- Page character encoding -->
<title>My Courses</title> <!-- Browser title -->

<!-- Load Bootstrap for layout -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
  /* Full-page gradient background */
  body {
    background: linear-gradient(135deg, #141E30, #243B55);
    color: #fff;
    font-family: "Poppins", sans-serif;
    min-height: 100vh;
  }

  /* Container with glassmorphism styling */
  .container-box {
    max-width: 1100px;
    margin: 40px auto;
    background: rgba(255,255,255,0.08);
    border-radius: 18px;
    padding: 30px 32px 28px;
    box-shadow: 0 18px 40px rgba(0,0,0,0.35);
    backdrop-filter: blur(10px);
  }

  /* Page titles */
  .page-title {
    font-size: 28px;
    font-weight: 700;
    text-align: center;
    margin-bottom: 6px;
  }

  .page-subtitle {
    text-align: center;
    font-size: 14px;
    color: #e5e7eb;
    margin-bottom: 20px;
  }

  /* Stats bar */
  .stats-card {
    background: rgba(15,23,42,0.8);
    border-radius: 12px;
    padding: 16px 18px;
    margin-bottom: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .stats-title {
    font-size: 14px;
    color: #cbd5f5;
  }

  .stats-number {
    font-size: 22px;
    font-weight: 700;
    color: #22c55e;
  }

  /* Table design */
  table {
    color: #fff;
    margin-top: 10px;
    font-size: 14px;
  }

  thead {
    background: #1f2937;
  }

  thead th {
    font-weight: 600;
    text-transform: uppercase;
    font-size: 12px;
    letter-spacing: .04em;
  }

  tbody tr:nth-child(even) { background: rgba(15,23,42,0.6); }
  tbody tr:nth-child(odd)  { background: rgba(15,23,42,0.9); }

  tbody tr:hover { background: rgba(59,130,246,0.3); }

  /* Status badge */
  .badge-active { background-color: #16a34a; }
  .badge-inactive { background-color: #6b7280; }

  /* Back button style */
  a.btn-back {
    margin-top: 18px;
    display: inline-block;
    background: #3b82f6;
    color: #fff;
    padding: 10px 22px;
    border-radius: 999px;
    text-decoration: none;
    font-weight: 600;
    font-size: 14px;
  }
  a.btn-back:hover { background: #1d4ed8; }

  /* Info alert styling */
  .alert-info {
    background-color: rgba(59,130,246,0.2);
    color: #e5f0ff;
    border-color: rgba(59,130,246,0.6);
  }
</style>
</head>

<body>

<div class="container-box"> <!-- Main page wrapper -->

  <!-- Page title/header -->
  <h2 class="page-title">📘 My Courses</h2>
  <p class="page-subtitle">
    Below are the courses assigned to you based on the <strong>CourseName</strong> in your staff profile.
  </p>

  <!-- Display the stats summary -->
  <div class="stats-card">
    <div>
      <div class="stats-title">Total Assigned Courses</div>
      <div class="stats-number"><?= (int)$courseCount ?></div>
    </div>

    <div style="text-align:right; font-size:13px; color:#9ca3af;">
      <?php if ($staffCourseName): ?>
        <div>Course name in your staff profile:</div>
        <strong><?= htmlspecialchars($staffCourseName) ?></strong>
      <?php else: ?>
        <div>No course name set in your staff profile.</div>
      <?php endif; ?>
    </div>
  </div>

  <?php if (!$staffRow): ?>
    <!-- Case: No staff profile exists -->
    <div class="alert alert-info text-center">
      No staff profile found for your account. Please contact the administrator.
    </div>

  <?php elseif (empty($courses)): ?>
    <!-- Case: No matching courses found -->
    <div class="alert alert-info text-center">
      No courses found that match your assigned CourseName.
    </div>

  <?php else: ?>
    <!-- Table of assigned courses -->
    <table class="table table-borderless table-hover align-middle">
      <thead>
        <tr>
          <th>#</th>
          <th>Course Name</th>
          <th>Code</th>
          <th>Description</th>
          <th>Credits</th>
          <th>Fee</th>
          <th>Start Date</th>
          <th>Status</th>
        </tr>
      </thead>

      <tbody>
        <?php $i = 1; ?>
        <?php foreach ($courses as $c): ?>
          <tr>
            <!-- Row number -->
            <td><?= $i++ ?></td>

            <!-- Basic course info -->
            <td><?= htmlspecialchars($c['CourseName']) ?></td>
            <td><?= htmlspecialchars($c['CourseCode']) ?></td>

            <td>
              <?php 
                // Shorten description preview
                $desc = $c['Description'] ?? '';
                $short = mb_strlen($desc) > 80 ? mb_substr($desc, 0, 80) . '…' : $desc;
                echo htmlspecialchars($short);
              ?>
            </td>

            <!-- Course credits -->
            <td><?= (int)$c['Credits'] ?></td>

            <!-- Course fee -->
            <td>$<?= number_format((float)$c['Fee'], 2) ?></td>

            <!-- Start date -->
            <td><?= htmlspecialchars($c['StartDate']) ?></td>

            <!-- Course active status -->
            <td>
              <?php if ((int)$c['IsActive'] === 1): ?>
                <span class="badge badge-active">Active</span>
              <?php else: ?>
                <span class="badge badge-inactive">Inactive</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>

  <!-- Back to staff dashboard -->
  <div class="text-center">
    <a href="dashboard.php" class="btn-back">⬅ Back to Staff Dashboard</a>
  </div>

</div>

</body>
</html>
