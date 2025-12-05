<?php
// This page is responsible for displaying the EDIT FORM for an enrollment record.
// Staff/Admin can update the student's enrollment details such as student, course, date, status and final grade.

require_once __DIR__ . '/../includes/auth_check.php';   // Checks login status
require_once __DIR__ . '/../includes/auth_model.php';   // Provides role checking
require_once __DIR__ . '/../includes/db.php';           // Database connection helper

// Ensure session is active before using session variables
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If user not logged in, redirect to login
if (!isset($_SESSION['userID'])) {
    header("Location: ../login.php");
    exit;
}

// Allow only Admin and Staff to access this page
requireRole(['Admin', 'Staff']);

// Build simple user info array for display
$user = [
    'name' => $_SESSION['username'] ?? 'Unknown',  // logged-in username
    'role' => $_SESSION['role'] ?? 'Unknown'       // logged-in user role
];

// Read Enrollment ID from URL query (edit_enrollment.php?id=...)
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// If ID is invalid, send user back to the enrollment list
if ($id <= 0) {
    $_SESSION['error_message'] = 'Invalid enrollment ID.';
    header('Location: list_enrollment.php');
    exit;
}

$pdo = getDB(); // connect to database

// Fetch the enrollment record we want to edit
$stmt = $pdo->prepare("
    SELECT e.EnrollmentID, e.StudentID, e.CourseID, e.EnrollmentDate,
           e.Status, e.FinalGrade
    FROM enrollment e
    WHERE e.EnrollmentID = :id
");
$stmt->execute([':id' => $id]);
$enroll = $stmt->fetch();

// If the enrollment does not exist, show error
if (!$enroll) {
    $_SESSION['error_message'] = 'Enrollment record not found.';
    header('Location: list_enrollment.php');
    exit;
}

// Get student list for dropdown selection
$students = $pdo->query("
    SELECT StudentID, CONCAT(FirstName, ' ', LastName) AS FullName
    FROM student
    ORDER BY FirstName, LastName
")->fetchAll();

// Get course list for dropdown selection
$courses = $pdo->query("
    SELECT CourseID, CourseCode, CourseName
    FROM course
    ORDER BY CourseName
")->fetchAll();

// Predefined allowed status options for enrollment
$statuses = ['registered','in-progress','completed','failed','dropped'];
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Enrollment</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<style>
/* Page uses clean centered layout with white card styling */
:root {
  --primary: #2563eb;
  --primary-dark: #1d4ed8;
  --danger: #dc2626;
  --bg: #f1f5f9;
  --surface: #ffffff;
  --border: #d1d5db;
  --text: #111827;
  --muted: #6b7280;
}

body {
  margin: 0;
  background: var(--bg);
  font-family: system-ui, sans-serif;
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
  padding: 30px;
}

/* Main white card container */
.page {
  width: 100%;
  max-width: 900px;
  background: var(--surface);
  border-radius: 16px;
  padding: 32px 32px 28px;
  box-shadow: 0 20px 50px rgba(0,0,0,0.15);
}

/* Header: Title + user info */
.header-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 18px;
}

.page-title {
  font-size: 36px;
  font-weight: 800;
}

.page-sub {
  font-size: 18px;
  color: var(--muted);
  margin-top: 6px;
}

/* User section (top-right) */
.user-box {
  text-align: right;
  font-size: 16px;
}

.user-role {
  font-size: 14px;
  color: var(--muted);
}

/* Button above form */
.btn-top {
  margin-top: 8px;
  padding: 8px 14px;
  border-radius: 8px;
  background: #f9fafb;
  border: 1px solid var(--border);
  text-decoration: none;
  color: var(--text);
}
.btn-top:hover { background: #e5e7eb; }

/* Flash message styling for success and errors */
.flash {
  padding: 14px 16px;
  border-radius: 10px;
  font-size: 18px;
  margin-bottom: 20px;
}
.flash-success {
  background: #e7f6ee;
  border: 2px solid #34d399;
}
.flash-error {
  background: #fde2e1;
  border: 2px solid #f87171;
}

/* Form fields */
.field {
  margin-bottom: 18px;
}
label {
  font-size: 18px;
  font-weight: 600;
  margin-bottom: 6px;
}
.input, .select {
  width: 100%;
  padding: 12px 14px;
  font-size: 18px;
  border: 2px solid var(--border);
  border-radius: 8px;
}
.input:focus,
.select:focus {
  border-color: var(--primary);
  box-shadow: 0 0 0 2px rgba(37,99,235,0.18);
}

/* Buttons inside form */
.btn {
  padding: 12px 20px;
  font-size: 18px;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  text-decoration: none;
}
.btn-primary { background: var(--primary); color: #fff; }
.btn-primary:hover { background: var(--primary-dark); }
.btn-secondary { background: #e5e7eb; color: #111; }
.btn-secondary:hover { background: #d4d4d8; }

.actions-row {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
}

/* Small helper text below inputs */
.helper {
  font-size: 14px;
  color: var(--muted);
}
</style>
</head>
<body>
<div class="page">

  <!-- Main header: title + logged-in user info -->
  <div class="header-bar">
    <div>
      <h1 class="page-title">Edit Enrollment</h1>
      <div class="page-sub">
        Update the student, course, date, status or grade for this enrollment.
      </div>
    </div>

    <div class="user-box">
      <strong><?= htmlspecialchars($user['name']) ?></strong><br>
      <span class="user-role"><?= htmlspecialchars($user['role']) ?></span><br>
      <a href="list_enrollment.php" class="btn-top">Back to list</a>
    </div>
  </div>

  <!-- Display flash messages if available -->
  <?php
  if (!empty($_SESSION['error_message'])) {
      echo '<div class="flash flash-error">'.htmlspecialchars($_SESSION['error_message']).'</div>';
      unset($_SESSION['error_message']);
  }
  if (!empty($_SESSION['success_message'])) {
      echo '<div class="flash flash-success">'.htmlspecialchars($_SESSION['success_message']).'</div>';
      unset($_SESSION['success_message']);
  }
  ?>

<script>
// Automatically fade out flash messages after 6 seconds
setTimeout(() => {
    document.querySelectorAll('.flash').forEach(el => {
        el.style.transition = "opacity 0.8s";
        el.style.opacity = "0";
        setTimeout(() => el.remove(), 800);
    });
}, 6000);
</script>

  <!-- Form that sends data to update_status.php -->
  <form method="post" action="update_status.php">

    <!-- Hidden ID field (required to know which record to update) -->
    <input type="hidden" name="EnrollmentID" value="<?= (int)$enroll['EnrollmentID'] ?>">

    <!-- Student dropdown -->
    <div class="field">
      <label for="student">Student</label>
      <select class="select" id="student" name="StudentID" required>
        <option value="">Select a student…</option>
        <?php foreach ($students as $stu): ?>
          <option value="<?= (int)$stu['StudentID'] ?>"
            <?= $stu['StudentID'] == $enroll['StudentID'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($stu['FullName']) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <div class="helper">Choose which student this enrollment belongs to.</div>
    </div>

    <!-- Course dropdown -->
    <div class="field">
      <label for="course">Course</label>
      <select class="select" id="course" name="CourseID" required>
        <option value="">Select a course…</option>
        <?php foreach ($courses as $c): ?>
          <option value="<?= (int)$c['CourseID'] ?>"
            <?= $c['CourseID'] == $enroll['CourseID'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($c['CourseCode'] . ' — ' . $c['CourseName']) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <div class="helper">Select the course the student is enrolled in.</div>
    </div>

    <!-- Enrollment date -->
    <div class="field">
      <label for="date">Enrollment date</label>
      <input class="input" type="date" id="date" name="EnrollmentDate"
             value="<?= htmlspecialchars($enroll['EnrollmentDate']) ?>" required>
      <div class="helper">The date when the student enrolled.</div>
    </div>

    <!-- Status dropdown -->
    <div class="field">
      <label for="status">Status</label>
      <select class="select" id="status" name="Status" required>
        <?php foreach ($statuses as $s): ?>
          <option value="<?= $s ?>" <?= $s === $enroll['Status'] ? 'selected' : '' ?>>
            <?= ucfirst($s) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <div class="helper">Registered, in-progress, completed, failed or dropped.</div>
    </div>

    <!-- Final grade (optional) -->
    <div class="field">
      <label for="grade">Final grade (optional)</label>
      <input class="input"
             type="text"
             id="grade"
             name="Grade"
             value="<?= htmlspecialchars($enroll['FinalGrade'] ?? '') ?>"
             placeholder="e.g. 85.50">
      <div class="helper">Leave blank if the course is not completed yet.</div>
    </div>

    <!-- Buttons -->
    <div class="actions-row">
      <a href="list_enrollment.php" class="btn btn-secondary">Cancel</a>
      <button type="submit" class="btn btn-primary">Save changes</button>
    </div>

  </form>

</div>
</body>
</html>
