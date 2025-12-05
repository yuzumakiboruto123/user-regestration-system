<?php
/**
 * CREATE ENROLLMENT PAGE
 * -----------------------
 * This file displays a form to add a new enrollment
 * and handles insertion + validation.
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/auth_model.php';

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// -----------------------------
// ACCESS CONTROL CHECK
// -----------------------------
if (!isset($_SESSION['userID'])) {
    header("Location: ../login.php");
    exit;
}

// Only Admin or Staff can add enrollment
requireRole(['Admin', 'Staff']);

$pdo = getDB();

// ====================================================
// PROCESS THE FORM (POST REQUEST)
// ====================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Read values safely
    $stu   = $_POST['StudentID'] ?? '';
    $crs   = $_POST['CourseID'] ?? '';
    $dt    = $_POST['EnrollmentDate'] ?? '';
    $st    = $_POST['Status'] ?? '';
    $grade = $_POST['Grade'] ?? null;

    // -----------------------------
    // VALIDATION: Required fields
    // -----------------------------
    if ($stu === '' || $crs === '' || $dt === '' || $st === '') {
        $_SESSION['error_message'] = "Please fill all required fields.";
        header("Location: create_enrollment.php");
        exit;
    }

    // -----------------------------
    // VALIDATION: Date cannot be in future
    // -----------------------------
    if (strtotime($dt) > time()) {
        $_SESSION['error_message'] = "Enrollment date cannot be in the future.";
        header("Location: create_enrollment.php");
        exit;
    }

    // -----------------------------
    // VALIDATION: Clean empty grade
    // -----------------------------
    if ($grade === "") {
        $grade = null; // convert empty string to NULL
    }

    // -----------------------------
    // VALIDATION: Grade format
    // Accepts:
    //   - Letter grades: A, A-, B+, B, C+, C, D, F
    //   - Numeric: 0–100
    // -----------------------------
    if ($grade !== null) {

        $validLetters = ['A','A-','B+','B','C+','C','D','F'];

        $isLetter  = in_array($grade, $validLetters);
        $isNumber  = is_numeric($grade) && $grade >= 0 && $grade <= 100;

        if (!$isLetter && !$isNumber) {
            $_SESSION['error_message'] =
                "Invalid grade. Use A, B+, C, F or a number between 0–100.";
            header("Location: create_enrollment.php");
            exit;
        }
    }

    // -----------------------------
    // INSERT INTO DATABASE
    // -----------------------------
    try {

        $sql = "INSERT INTO enrollment
                (StudentID, CourseID, EnrollmentDate, Status, FinalGrade)
                VALUES (:stu, :crs, :dt, :st, :gr)";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ':stu' => $stu,
            ':crs' => $crs,
            ':dt'  => $dt,
            ':st'  => $st,
            ':gr'  => $grade
        ]);

        $_SESSION['success_message'] = "Enrollment added successfully!";
        header("Location: list_enrollment.php");
        exit;

    } catch (Exception $e) {
// HANDLE DUPLICATE ENROLLMENT ERROR
if ($e->getCode() == 23000) {
    $_SESSION['error_message'] = "This student is already enrolled in this course.";
} else {
    $_SESSION['error_message'] = "Something went wrong while adding the enrollment.";
}

header("Location: create_enrollment.php");
exit;

    }
}

// ====================================================
// LOAD DROPDOWN DATA FOR FORM
// ====================================================

// Logged-in user (display)
$user = [
    'name' => $_SESSION['username'] ?? "Unknown",
    'role' => $_SESSION['role'] ?? "Unknown"
];

// Load all students — we will show FullName + ID to avoid confusion
$students = $pdo->query("
    SELECT StudentID, FirstName, LastName
    FROM student
    ORDER BY FirstName, LastName
")->fetchAll();

// Load courses
$courses = $pdo->query("
    SELECT CourseID, CourseCode, CourseName
    FROM course
    ORDER BY CourseName
")->fetchAll();

// Enrollment status options
$statuses = ['registered','in-progress','completed','failed','dropped'];

// Today’s date
$today = date('Y-m-d');

?>
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add Enrollment</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<style>
/* Your CSS kept exactly same to avoid styling conflicts */
:root {
  --primary: #2563eb;
  --primary-dark: #1d4ed8;
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

.page {
  width: 100%;
  max-width: 900px;
  background: var(--surface);
  border-radius: 16px;
  padding: 32px 32px 28px;
  box-shadow: 0 20px 50px rgba(0,0,0,0.15);
}

.header-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 18px;
}

.page-title {
  font-size: 36px;
  font-weight: 800;
  margin: 0;
}

.page-sub {
  font-size: 18px;
  color: var(--muted);
  margin-top: 6px;
}

.user-box { text-align: right; font-size: 16px; }
.user-role { font-size: 14px; color: var(--muted); }

.flash { padding: 14px 16px; border-radius: 10px; margin-bottom: 20px; }
.flash-success { background: #e7f6ee; border: 2px solid #34d399; }
.flash-error { background: #fde2e1; border: 2px solid #f87171; }

.field { margin-bottom: 18px; }

label { font-size: 18px; font-weight: 600; margin-bottom: 6px; display: block; }

.input, .select {
  width: 100%;
  padding: 12px 14px;
  font-size: 18px;
  border-radius: 8px;
  border: 2px solid var(--border);
}

.btn {
  padding: 12px 20px;
  font-size: 18px;
  border-radius: 8px;
  font-weight: 600;
  border: none;
  cursor: pointer;
}

.btn-primary { background: var(--primary); color: white; }
.btn-secondary { background: #e5e7eb; color: #111; }

.actions-row {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
}
</style>
</head>
<body>

<div class="page">

  <!-- Header with user info -->
  <div class="header-bar">
    <div>
      <h1 class="page-title">Add Enrollment</h1>
      <div class="page-sub">Select student, course, date and status.</div>
    </div>
    <div class="user-box">
      <strong><?= htmlspecialchars($user['name']) ?></strong><br>
      <span class="user-role"><?= htmlspecialchars($user['role']) ?></span><br>
      <a href="list_enrollment.php" class="btn-secondary" style="padding:6px 12px;">Back</a>
    </div>
  </div>

  <!-- Flash messages -->
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

  <!-- Auto-hide flash -->
  <script>
  setTimeout(() => {
      document.querySelectorAll('.flash').forEach(el => {
          el.style.transition = "opacity 0.8s";
          el.style.opacity = "0";
          setTimeout(() => el.remove(), 800);
      });
  }, 6000);
  </script>

  <!-- ADD ENROLLMENT FORM -->
  <form method="post">

    <!-- STUDENT DROPDOWN WITH NAME + ID -->
    <div class="field">
      <label>Student</label>
      <select class="select" name="StudentID" required>
        <option value="">Select a student…</option>

        <?php foreach ($students as $stu): ?>
          <?php
            $fullName = htmlspecialchars($stu['FirstName'] . ' ' . $stu['LastName']);
            $id = (int)$stu['StudentID'];
          ?>
          <option value="<?= $id ?>">
            <?= $fullName ?> (ID: <?= $id ?>)
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <!-- COURSE -->
    <div class="field">
      <label>Course</label>
      <select class="select" name="CourseID" required>
        <option value="">Select a course…</option>
        <?php foreach ($courses as $c): ?>
          <option value="<?= (int)$c['CourseID'] ?>">
            <?= htmlspecialchars($c['CourseCode'] . ' — ' . $c['CourseName']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <!-- DATE -->
    <div class="field">
      <label>Enrollment Date</label>
      <input class="input" type="date" name="EnrollmentDate" value="<?= $today ?>" required>
    </div>

    <!-- STATUS -->
    <div class="field">
      <label>Status</label>
      <select class="select" name="Status" required>
        <?php foreach ($statuses as $s): ?>
          <option value="<?= $s ?>" <?= $s === 'registered' ? 'selected' : '' ?>>
            <?= ucfirst($s) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <!-- GRADE -->
    <div class="field">
      <label>Final Grade (optional)</label>
      <input class="input" type="text" name="Grade" placeholder="A, B+, C, F or 0–100">
    </div>

    <!-- ACTIONS -->
    <div class="actions-row">
      <a href="list_enrollment.php" class="btn btn-secondary">Cancel</a>
      <button type="submit" class="btn btn-primary">Save Enrollment</button>
    </div>

  </form>
</div>

</body>
</html>
