<?php
session_start(); // Start session for authentication tracking

require_once '../includes/auth_check.php'; // Ensure user is logged in
require_once '../includes/auth_model.php'; // Load role checking functions
require_once '../includes/db.php';          // Database connection helper

requireRole(['Admin','Staff','Student']); // Only logged-in users with allowed roles can access

// Auto-logout: if user is inactive for 10 minutes (600 seconds)
if (isset($_SESSION['last_activity']) && time() - $_SESSION['last_activity'] > 600) {
    session_unset(); // Clear session variables
    session_destroy(); // Destroy session
    header("Location: login.php?expired=1"); // Redirect to login with expiry message
    exit;
}
$_SESSION['last_activity'] = time(); // Update last activity timestamp

// Read session variables
$username = $_SESSION['username'];
$role     = $_SESSION['role'];
$userID   = $_SESSION['userID'];

$pdo = getDB(); // Get database connection object

// Default profile avatar image
$defaultAvatar = "https://cdn-icons-png.flaticon.com/512/847/847969.png";
$profileImg = $defaultAvatar;

// Fetch profile image for Admin
if ($role === 'Admin') {
    $stmt = $pdo->prepare("SELECT ProfileImage FROM user WHERE UserID=?");
    $stmt->execute([$userID]);
    $r = $stmt->fetch(PDO::FETCH_ASSOC);

    // Replace with actual image if exists
    if (!empty($r['ProfileImage'])) {
        $profileImg = (strpos($r['ProfileImage'],'http')===0)
            ? $r['ProfileImage']
            : "../uploads/profile/".$r['ProfileImage'];
    }
}

// Fetch profile image for Staff
elseif ($role === 'Staff') {
    $stmt = $pdo->prepare("SELECT ProfileImage FROM staff WHERE UserID=?");
    $stmt->execute([$userID]);
    $r = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!empty($r['ProfileImage'])) {
        $profileImg = (strpos($r['ProfileImage'],'http')===0)
            ? $r['ProfileImage']
            : "../uploads/profile/".$r['ProfileImage'];
    }
}

// Fetch profile image for Student
else {
    $stmt = $pdo->prepare("SELECT ProfileImage FROM student WHERE UserID=?");
    $stmt->execute([$userID]);
    $r = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!empty($r['ProfileImage'])) {
        $profileImg = (strpos($r['ProfileImage'],'http')===0)
            ? $r['ProfileImage']
            : "../uploads/profile/".$r['ProfileImage'];
    }
}

// Count pending users (admin-only widget)
$pendingCount = ($role==='Admin') ? countPendingUsers() : 0;

// Initialize dashboard numbers to 0
$totalStudents = $totalStaff = $totalCourses = $totalEnrollment = 0;

// Admin statistics
if ($role === 'Admin') {
    $totalStudents   = $pdo->query("SELECT COUNT(*) FROM student")->fetchColumn();
    $totalStaff      = $pdo->query("SELECT COUNT(*) FROM staff")->fetchColumn();
    $totalCourses    = $pdo->query("SELECT COUNT(*) FROM course")->fetchColumn();
    $totalEnrollment = $pdo->query("SELECT COUNT(*) FROM enrollment")->fetchColumn();
}

// Staff statistics
$myCourses = $myStudents = 0;

if ($role==='Staff') {
    $q = $pdo->prepare("SELECT StaffID FROM staff WHERE UserID=?");
    $q->execute([$userID]);
    $s = $q->fetch(PDO::FETCH_ASSOC);

    if ($s) {
        $staffID = $s['StaffID'];

        // Count courses assigned to this staff
        $c1 = $pdo->prepare("SELECT COUNT(*) FROM course WHERE StaffID=?");
        $c1->execute([$staffID]);
        $myCourses = $c1->fetchColumn();

        // Count unique students for staff's courses
        $c2 = $pdo->prepare("
            SELECT COUNT(DISTINCT e.StudentID)
            FROM enrollment e
            JOIN course c ON e.CourseID=c.CourseID
            WHERE c.StaffID=?
        ");
        $c2->execute([$staffID]);
        $myStudents = $c2->fetchColumn();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">

<!-- Page title -->
<title><?php echo ucfirst($role); ?> Dashboard</title>

<!-- External CSS -->
<link rel="stylesheet" href="../assets/css/auth-style.css">

<style>
/* Background, layout, global UI */
body {
  font-family:Poppins,sans-serif;
  background:linear-gradient(135deg,#141E30,#243B55);
  margin:0;
  color:white;
  min-height:100vh;
  display:flex;
  flex-direction:column;
}

/* Header bar */
header {
  background:linear-gradient(90deg,#182848,#4b6cb7);
  padding:20px 40px;
  display:flex;
  justify-content:space-between;
  align-items:center;
}

/* Navigation links */
nav a {
  color:white;
  text-decoration:none;
  margin-left:20px;
}

/* Logout button UI */
.logout-btn {
  background:#e74c3c;
  padding:8px 16px;
  border-radius:20px;
}

/* Profile picture style */
.profile-icon {
  width:42px;
  height:42px;
  border-radius:50%;
  object-fit:cover;
  border:2px solid white;
}

/* Content area */
main {
  flex:1;
  padding:40px;
  text-align:center;
}

/* Dashboard card container */
.dashboard-cards {
  display:flex;
  justify-content:center;
  flex-wrap:wrap;
  gap:25px;
  margin-bottom:30px;
}

/* Individual card UI */
.card {
  width:220px;
  padding:25px;
  border-radius:15px;
  cursor:pointer;
  color:white;
  box-shadow:0 8px 20px rgba(0,0,0,0.3);
}

/* Card color variations */
.blue {background:linear-gradient(135deg,#3498db,#2980b9);}
.green {background:linear-gradient(135deg,#2ecc71,#27ae60);}
.purple {background:linear-gradient(135deg,#9b59b6,#8e44ad);}
.red {background:linear-gradient(135deg,#e74c3c,#c0392b);}

/* Admin control buttons */
.btn-row a {
  background:#3498db;
  padding:10px 20px;
  border-radius:8px;
  color:white;
  margin:8px;
  display:inline-block;
  text-decoration:none;
}

/* Footer */
footer {
  padding:15px;
  background:rgba(0,0,0,0.3);
  text-align:center;
}
</style>
</head>

<body>

<!-- Header bar showing username, avatar, logout -->
<header>

  <h1>Welcome, <?php echo htmlspecialchars($username); ?> (<?php echo $role; ?>)</h1>

  <nav>

    <!-- Admin view: pending users -->
    <?php if ($role==='Admin'): ?>
      <a href="approve_user.php">Pending Users <span class="badge-pending"><?= $pendingCount ?></span></a>
    <?php endif; ?>

    <!-- Profile link with icon -->
    <a href="my_profile.php">
      <img src="<?= $profileImg ?>" class="profile-icon">
    </a>

    <!-- Logout -->
    <a class="logout-btn" href="logout.php">Logout</a>
  </nav>
</header>

<!-- Main dashboard area -->
<main>

<?php if ($role==='Admin'): ?>

<!-- Admin dashboard cards -->
<div class="dashboard-cards">

  <div class="card blue" onclick="location.href='list.php?type=students'">
    <h3>Students</h3>
    <p><?= $totalStudents ?></p>
  </div>

  <div class="card green" onclick="location.href='list.php?type=staff'">
    <h3>Staff</h3>
    <p><?= $totalStaff ?></p>
  </div>

  <div class="card purple" onclick="location.href='list.php?type=courses'">
    <h3>Courses</h3>
    <p><?= $totalCourses ?></p>
  </div>

  <div class="card red" onclick="location.href='list.php?type=enrollments'">
    <h3>Enrollments</h3>
    <p><?= $totalEnrollment ?></p>
  </div>

</div>

<!-- Admin control buttons -->
<h2>Admin Controls</h2>

<div class="btn-row">
  <a href="approve_user.php">Approve Users</a>
  <a href="manage_user.php">Manage Users</a>
  <a href="course_list.php">Manage Courses</a>
  <a href="staff_list.php">Manage Staff</a>
  <a href="student_list.php">Manage Students</a>
  <a href="list_enrollment.php">Manage Enrollment</a>
</div>

<?php endif; ?>


<!-- Staff dashboard -->
<?php if ($role==='Staff'): ?>

<h2>Staff Dashboard</h2>

<?php
// ================================================
// Step 1: Get staff record from DB
// ================================================
$staffID = 0;
$assignedCourseID = null;

$st = $pdo->prepare("SELECT StaffID, CourseID FROM staff WHERE UserID = ?");
$st->execute([$userID]);
$staffRow = $st->fetch(PDO::FETCH_ASSOC);

if ($staffRow) {
    $staffID = (int)$staffRow['StaffID'];
    $assignedCourseID = $staffRow['CourseID']; // May be null if not assigned
}

// ================================================
// Step 2: Count staff courses (0 or 1)
// ================================================
$myCourses = 0;

if (!empty($assignedCourseID)) {
    $myCourses = 1; // Staff teaches exactly one assigned course
}

// ================================================
// Step 3: Count number of students in this course
// ================================================
$myStudents = 0;

if (!empty($assignedCourseID)) {

    $q2 = $pdo->prepare("
        SELECT COUNT(DISTINCT StudentID)
        FROM enrollment
        WHERE CourseID = ?
    ");
    $q2->execute([$assignedCourseID]);
    $myStudents = (int)$q2->fetchColumn();
}
?>

<!-- Staff cards -->
<div class="dashboard-cards">

  <div class="card blue" onclick="location.href='viewmycourse.php'">
    <h3>My Courses</h3>
    <p><?= $myCourses ?></p>
  </div>

  <div class="card green" onclick="location.href='viewenrolledstudent.php'">
    <h3>My Students</h3>
    <p><?= $myStudents ?></p>
  </div>

</div>

<!-- Staff action buttons -->
<div class="btn-row">
  <a href="viewmycourse.php">View Assigned Courses</a>
  <a href="viewenrolledstudent.php">View Enrolled Students</a>
  <a href="my_profile.php">My Profile</a>
</div>

<?php endif; ?>


<!-- Student dashboard -->
<?php if ($role==='Student'): ?>

<h2>Student Dashboard</h2>

<div class="btn-row">
  <a href="viewmycourse1.php">View My Courses</a>
  <a href="viewmygrades.php">View My Grades</a>
  <a href="my_profile.php">My Profile</a>
</div>

<?php endif; ?>

</main>

<!-- Footer -->
<footer>
  © <?= date('Y') ?> FUTURE BILLIONAIRE PVT LTD
</footer>

</body>
</html>
