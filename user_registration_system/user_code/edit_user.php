<?php
session_start(); // Start session for admin access tracking

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/user_model.php';
require_once __DIR__ . '/../includes/validation.php';
require_once __DIR__ . '/../includes/audit.php';


requireRole(['Admin']); // Only admin can open Edit User page

$pdo = getDB(); // Get PDO instance
$id = intval($_GET['id'] ?? 0); // Read target user ID from URL

// Log page opening
logAction($_SESSION['userID'], "OPEN_EDIT_USER", "user", $id, "Admin opened Edit Page");

// Fetch user record from DB
$user = getUserById($id);
if (!$user) die("User not found.");

$message = ""; // Message placeholder

$staffData   = null; // Staff extra data
$studentData = null; // Student extra data

$currentDbRole = $user['Role']; // Current DB-stored role of the account

// Load staff information if the user is Staff
if ($currentDbRole === 'Staff') {
    $stmt = $pdo->prepare("SELECT * FROM staff WHERE UserID = ?");
    $stmt->execute([$id]);
    $staffData = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Load student information if the user is Student
if ($currentDbRole === 'Student') {
    $stmt = $pdo->prepare("SELECT * FROM student WHERE UserID = ?");
    $stmt->execute([$id]);
    $studentData = $stmt->fetch(PDO::FETCH_ASSOC);
}

// =======================================================
// LOAD COURSES FOR DROPDOWN
// =======================================================
$courseList = $pdo->query("SELECT CourseName FROM course ORDER BY CourseName ASC")->fetchAll(PDO::FETCH_ASSOC);

// ------------------------------------------------------------
// Handle form submission (VALIDATION ONLY — NO UPDATE HERE)
// ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $role     = $_POST['role'] ?? '';
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    // STAFF: UPDATED FIELD
    $staffCourseName = trim($_POST['CourseName'] ?? '');
    $staffSalary     = trim($_POST['salary'] ?? '');

    $studentGpa = trim($_POST['gpa'] ?? '');

    $newAdminPin = trim($_POST['new_admin_pin'] ?? '');

    $errors = []; // Collect validation errors

    // Validate username
    if (!validateUsername($username)) {
        $errors[] = "Username must be 4–20 characters.";
    }

    // Validate role
    if (!validateRole($role)) {
        $errors[] = "Invalid role.";
    }

    // Validate status
    if (!validateStatus($isActive)) {
        $errors[] = "Invalid status.";
    }

    // Validate password only if provided
    if ($password !== '' && !validatePassword($password)) {
        $errors[] = "Password must be 8+ chars (upper, lower, number).";
    }

    // Staff extra validation
    if (($currentDbRole === 'Staff' || $role === 'Staff')) {

        if ($staffCourseName !== '' && !validateDepartment($staffCourseName)) {
            $errors[] = "Course name must contain letters/spaces.";
        }

        if ($staffSalary !== '' && !validateSalary($staffSalary)) {
            $errors[] = "Salary must be numeric.";
        }
    }

    // Student extra validation
    if (($currentDbRole === 'Student' || $role === 'Student')) {

        if ($studentGpa !== '' && !validateGPA($studentGpa)) {
            $errors[] = "GPA must be 0.0–4.0.";
        }
    }

    // If there are errors → stop and show messages
    if (!empty($errors)) {

        logAction($_SESSION['userID'], "FAILED_UPDATE_USER", "user", $id, "Validation failed");

        $message = "<div class='alert alert-danger'><strong>Fix the following:</strong><br>" .
            implode("<br>", array_map('htmlspecialchars', $errors)) .
            "</div>";
    }

    // If validation OK → forward data to PIN verification
    else {

        // Build a hidden form to forward values securely
        echo "<form id='redirectPIN' method='POST' action='verify_pin_action.php'>";

        echo "<input type='hidden' name='action' value='update'>";
        echo "<input type='hidden' name='target_user' value='".htmlspecialchars($id)."'>";
        echo "<input type='hidden' name='username' value='".htmlspecialchars($username)."'>";
        echo "<input type='hidden' name='password' value='".htmlspecialchars($password)."'>";
        echo "<input type='hidden' name='role' value='".htmlspecialchars($role)."'>";
        echo "<input type='hidden' name='is_active' value='".htmlspecialchars($isActive)."'>";

        // Staff extra fields
        if ($role === 'Staff') {
            echo "<input type='hidden' name='extra[courseName]' value='".htmlspecialchars($staffCourseName)."'>";
            echo "<input type='hidden' name='extra[salary]' value='".htmlspecialchars($staffSalary)."'>";
        }

        // Student extra fields
        if ($role === 'Student') {
            echo "<input type='hidden' name='extra[gpa]' value='".htmlspecialchars($studentGpa)."'>";
        }

        // Admin PIN update
        if ($role === 'Admin') {
            echo "<input type='hidden' name='new_admin_pin' value='".htmlspecialchars($newAdminPin)."'>";
        }

        echo "</form>";

        // Auto-submit the form to PIN verification page
        echo "<script>document.getElementById('redirectPIN').submit();</script>";
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit User</title>

<!-- Bootstrap -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
/* Page background */
body {
    background: linear-gradient(135deg, #212e68, #9e69d3);
    min-height: 100vh;
    padding: 40px;
    font-family: "Poppins", sans-serif;
}

/* Main container box */
.box {
    max-width: 650px;
    margin: auto;
    background: linear-gradient(135deg, #6c8fd6, #4b6cb7);
    padding: 30px;
    border-radius: 15px;
    color: white;
}

/* Title */
h1 {
    color: #f1c40f;
}

/* Input field styling */
.form-control, .form-select {
    border-radius: 10px;
}

/* Save button */
.btn-save {
    background-color: #2ecc71;
    border-radius: 10px;
}
</style>
</head>

<body>

<div class="box">

<h1>Edit User</h1>

<?= $message ?> <!-- Validation messages -->

<form method="POST">

    <!-- Username -->
    <div class="mb-3">
        <label class="form-label">Username *</label>
        <input type="text" name="username" class="form-control"
               value="<?= htmlspecialchars($_POST['username'] ?? $user['Username']) ?>" required>
    </div>

    <!-- Email (read-only) -->
    <div class="mb-3">
        <label class="form-label">Email (read only)</label>
        <input type="email" class="form-control" value="<?= htmlspecialchars($user['Email']) ?>" disabled>
    </div>

    <!-- Role selector -->
    <div class="mb-3">
        <?php $selectedRole = $_POST['role'] ?? $user['Role']; ?>
        <label class="form-label">Role *</label>
        <select name="role" class="form-select">
            <option value="Admin" <?= $selectedRole==='Admin'?'selected':'' ?>>Admin</option>
            <option value="Staff" <?= $selectedRole==='Staff'?'selected':'' ?>>Staff</option>
            <option value="Student" <?= $selectedRole==='Student'?'selected':'' ?>>Student</option>
        </select>
    </div>

    <!-- Active toggle -->
    <div class="form-check mb-3">
        <?php $isActiveChecked = isset($_POST['is_active']) ? true : (bool)$user['IsActive']; ?>
        <input class="form-check-input" type="checkbox" name="is_active" <?= $isActiveChecked?'checked':'' ?>>
        <label class="form-check-label">Active user</label>
    </div>

    <!-- New password -->
    <div class="mb-3">
        <label class="form-label">New Password (optional)</label>
        <input type="password" name="password" class="form-control">
    </div>

    <!-- New admin PIN -->
    <?php if ($currentDbRole === 'Admin'): ?>
        <div class="mb-3">
            <label class="form-label">New Admin PIN (optional)</label>
            <input type="password" maxlength="6" name="new_admin_pin" class="form-control"
                   placeholder="6-digit PIN">
        </div>
    <?php endif; ?>

    <!-- Staff fields -->
    <?php if ($currentDbRole === 'Staff'): ?>
        <?php
        $courseValue = $_POST['CourseName'] ?? ($staffData['CourseName'] ?? '');
        $salaryValue = $_POST['salary'] ?? ($staffData['Salary'] ?? '');
        ?>

        <div class="mb-3">
            <label class="form-label">Course</label>
            <select name="CourseName" class="form-select">
                <option value="">-- Select Course --</option>

                <?php foreach ($courseList as $c): ?>
                    <option value="<?= htmlspecialchars($c['CourseName']) ?>"
                        <?= ($courseValue === $c['CourseName']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['CourseName']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Salary</label>
            <input type="number" step="0.01" name="salary" class="form-control"
                   value="<?= htmlspecialchars($salaryValue) ?>">
        </div>
    <?php endif; ?>

    <!-- Student fields -->
    <?php if ($currentDbRole === 'Student'): ?>
        <?php $gpaValue = $_POST['gpa'] ?? ($studentData['GPA'] ?? ''); ?>
        <div class="mb-3">
            <label class="form-label">GPA</label>
            <input type="number" step="0.01" name="gpa" class="form-control"
                   value="<?= htmlspecialchars($gpaValue) ?>">
        </div>
    <?php endif; ?>

    <button type="submit" class="btn btn-save w-100 mt-3">Save Changes</button>

    <div class="text-center mt-3">
        <a href="manage_user.php" class="back-btn">← Back</a>
    </div>

</form>

</div>

</body>
</html>
