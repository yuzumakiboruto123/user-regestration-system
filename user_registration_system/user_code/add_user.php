<?php
session_start();

/* ================================================================
   LOAD REQUIRED SYSTEM FILES
   ---------------------------------------------------------------
   - auth_check.php  → Ensures a logged-in user + correct role
   - user_model.php  → Contains user creation logic + DB actions
   - validation.php  → Contains validation functions (name/email/role)
================================================================ */
require_once '../includes/auth_check.php';
require_once '../includes/user_model.php';
require_once '../includes/validation.php';

/* ================================================================
   ACCESS CONTROL: ONLY ADMIN CAN CREATE USERS
   ---------------------------------------------------------------
   - If a non-admin tries to access this page → they will be blocked
================================================================ */
requireRole(['Admin']);

/* ================================================================
   MESSAGE VARIABLE
   ---------------------------------------------------------------
   - Used to show success/error messages at the top
================================================================ */
$message = "";

/* ================================================================
   LOAD COURSE LIST FOR STAFF DROPDOWN
   ---------------------------------------------------------------
   - Staff creation requires selecting a course
================================================================ */
$pdo = getDB();
$courseList = $pdo->query("
    SELECT CourseName 
    FROM course 
    ORDER BY CourseName ASC
")->fetchAll(PDO::FETCH_ASSOC);

/* ================================================================
   FUNCTION: emailExists()
   ---------------------------------------------------------------
   - Checks if email is already used (Prevent duplicates)
================================================================ */
function emailExists($email) {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM user WHERE Email = ?");
    $stmt->execute([$email]);
    return $stmt->fetchColumn() > 0; 
}

/* ================================================================
   FORM SUBMISSION HANDLING: RUNS ONLY WHEN POST METHOD USED
   ---------------------------------------------------------------
   - This means the admin submitted "Create User"
================================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Read basic required fields
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $role     = $_POST['role'] ?? '';

    // Will store validation errors
    $errors = [];

    // Will store extra values for admin/staff/student
    $extra  = [];

    /* ============================================================
       VALIDATION — COMMON FIELDS
       ============================================================ */

    // Validate username format
    if (!validateUsername($username)) {
        $errors[] = "Username must be 4–20 characters.";
    }

    // Validate role is Admin/Staff/Student
    if (!validateRole($role)) {
        $errors[] = "Invalid role.";
    }

    // Validate password strength
    if (!validatePassword($password)) {
        $errors[] = "Password must be strong (upper, lower, number).";
    }

    /* ============================================================
       ADMIN-SPECIFIC FIELDS
       ------------------------------------------------------------
       Only executed if admin selected "Admin" role in dropdown
    ============================================================ */
    if ($role === 'Admin') {

        $adminEmail = trim($_POST['admin_email'] ?? '');
        $adminPin   = trim($_POST['admin_pin'] ?? '');

        // Email validation (must be from school)
        if (!validateEmail($adminEmail)) {
            $errors[] = "Admin email must end with @school.edu.";
        }

        // Check duplicate email
        if (emailExists($adminEmail)) {
            $errors[] = "Email already registered.";
        }

        // Admin PIN must be exactly 6 digits numeric
        if (!preg_match('/^[0-9]{6}$/', $adminPin)) {
            $errors[] = "Admin PIN must be exactly 6 digits.";
        }

        // Store admin fields for createUser()
        $extra['email']     = $adminEmail;
        $extra['admin_pin'] = $adminPin;
    }

    /* ============================================================
       STAFF-SPECIFIC FIELDS
       ------------------------------------------------------------
       Only executed if admin selected "Staff" role
    ============================================================ */
    if ($role === 'Staff') {

        // Gather staff form fields
        $firstName  = trim($_POST['first_name'] ?? '');
        $lastName   = trim($_POST['last_name'] ?? '');
        $email      = trim($_POST['email'] ?? '');
        $email      = filter_var($email, FILTER_SANITIZE_EMAIL);
        $courseName = trim($_POST['CourseName'] ?? '');
        $salary     = trim($_POST['salary'] ?? '');
        $hireDate   = trim($_POST['hire_date'] ?? '');

        // All staff fields are mandatory
        if (
            $firstName === '' || $lastName === '' || $email === '' ||
            $courseName === '' || $salary === '' || $hireDate === ''
        ) {
            $errors[] = "All staff fields are required.";
        }

        // Validate names
        if (!validateName($firstName)) $errors[] = "Invalid first name.";
        if (!validateName($lastName))  $errors[] = "Invalid last name.";

        // Validate staff email
        if (!validateEmail($email)) $errors[] = "Staff email invalid.";
        if (emailExists($email))     $errors[] = "Email already registered.";

        // Validate courseName uses letters/spaces
        if (!validateDepartment($courseName)) {
            $errors[] = "Invalid department.";
        }

        // Validate salary is numeric
        if (!validateSalary($salary)) $errors[] = "Invalid salary.";

        // Validate hire date is proper date
        if (!validateHireDate($hireDate)) $errors[] = "Invalid hire date.";

        // Store staff fields
        $extra = [
            'first_name' => $firstName,
            'last_name'  => $lastName,
            'email'      => $email,
            'courseName' => $courseName,
            'salary'     => floatval($salary),
            'hire_date'  => $hireDate
        ];
    }

    /* ============================================================
       STUDENT-SPECIFIC FIELDS
       ------------------------------------------------------------
       Only executed if admin selected "Student"
    ============================================================ */
    if ($role === 'Student') {

        // Gather student form data
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName  = trim($_POST['last_last'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $email     = filter_var($email, FILTER_SANITIZE_EMAIL);
        $dob       = trim($_POST['dob'] ?? '');
        $age       = trim($_POST['age'] ?? '');
        $gpa       = trim($_POST['gpa'] ?? '');

        // Required student fields except GPA
        if (
            $firstName === '' || $lastName === '' ||
            $email === '' || $dob === '' || $age === ''
        ) {
            $errors[] = "All student fields except GPA must be filled.";
        }

        // Validate name fields
        if (!validateName($firstName)) $errors[] = "Invalid first name.";
        if (!validateName($lastName))  $errors[] = "Invalid last name.";

        // Validate email
        if (!validateEmail($email)) $errors[] = "Student email invalid.";
        if (emailExists($email))     $errors[] = "Email already registered.";

        // Validate DOB, age, and GPA
        if (!validateDOB($dob)) $errors[] = "Invalid DOB.";
        if (!validateAge($age)) $errors[] = "Invalid age.";
        if (!validateGPA($gpa)) $errors[] = "Invalid GPA (0.0 - 4.0).";

        // Store student fields
        $extra = [
            'first_name' => $firstName,
            'last_name'  => $lastName,
            'email'      => $email,
            'dob'        => $dob,
            'age'        => intval($age),
            'gpa'        => ($gpa === '' ? null : floatval($gpa))
        ];
    }

    /* ============================================================
       DISPLAY VALIDATION ERRORS
       ------------------------------------------------------------
       - If any errors exist, show them and stop execution.
    ============================================================ */
    if (!empty($errors)) {
        $message = "<div class='alert alert-danger'>
                        <strong>Error:</strong><br>" .
                        implode("<br>", $errors) .
                   "</div>";
    }

    /* ============================================================
       NO ERRORS → REDIRECT TO verify_pin_action.php
       ------------------------------------------------------------
       Why?
       Because before creating a user, admin must enter PIN
       for security confirmation.
    ============================================================ */
    else {

        // Build an auto-submitted hidden form
        echo "<form id='redirectPIN' method='POST' action='verify_pin_action.php'>";

        echo "<input type='hidden' name='action' value='create'>";
        echo "<input type='hidden' name='username' value='".htmlspecialchars($username)."'>";
        echo "<input type='hidden' name='password' value='".htmlspecialchars($password)."'>";
        echo "<input type='hidden' name='role' value='".htmlspecialchars($role)."'>";

        // Add all extra values (admin/staff/student)
        foreach ($extra as $key => $val) {
            echo "<input type='hidden' name='extra[$key]' value='".htmlspecialchars($val)."'>";
        }

        echo "</form>";

        // Immediately submit
        echo "<script>document.getElementById('redirectPIN').submit();</script>";
        exit;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Add User</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
/* Page styling (same) */
body {
    background: linear-gradient(135deg,#212e68,#9e69d3);
    padding: 40px;
    min-height: 100vh;
    font-family: 'Poppins',sans-serif;
}

.form-box {
    max-width: 650px;
    margin: auto;
    background: linear-gradient(135deg,#6c8fd6,#4b6cb7);
    padding: 30px;
    border-radius: 15px;
    color: white;
}
</style>
</head>

<body>

<div class="form-box">

<h1 class="text-center text-warning">Add New User</h1>

<!-- Show validation messages -->
<?= $message ?>

<form method="POST">

    <!-- USERNAME FIELD -->
    <label>Username *</label>
    <input type="text" name="username" class="form-control mb-2"
           placeholder="e.g., john_doe21"
           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">

    <!-- PASSWORD FIELD -->
    <label>Password *</label>
    <input type="password" name="password" class="form-control mb-2"
           autocomplete="new-password"
           placeholder="Min 8 chars: Upper, lower & number">

    <!-- ROLE DROPDOWN -->
    <label>Role *</label>
    <select name="role" id="roleSelect" class="form-select mb-2">
        <option value="">-- Select Role --</option>
        <option value="Admin"   <?= ($_POST['role'] ?? '') === "Admin" ? "selected" : "" ?>>Admin</option>
        <option value="Staff"   <?= ($_POST['role'] ?? '') === "Staff" ? "selected" : "" ?>>Staff</option>
        <option value="Student" <?= ($_POST['role'] ?? '') === "Student" ? "selected" : "" ?>>Student</option>
    </select>


<!-- ================================================================
     ADMIN FIELDS SECTION (initially hidden)
================================================================ -->
<div id="adminSection" style="display:none;">

    <label>Admin Email</label>
    <input type="email" name="admin_email" class="form-control mb-2"
           placeholder="e.g., admin@school.edu"
           value="<?= htmlspecialchars($_POST['admin_email'] ?? '') ?>">

    <label>Admin PIN (6 digits)</label>
    <input type="text" name="admin_pin" maxlength="6"
           class="form-control mb-2"
           placeholder="Enter 6-digit Admin PIN"
           value="<?= htmlspecialchars($_POST['admin_pin'] ?? '') ?>">

</div>


<!-- ================================================================
     COMMON FIELDS (SHARED BY STAFF + STUDENT)
================================================================ -->
<div id="commonProfileSection" style="display:none;">

    <label>First Name</label>
    <input type="text" name="first_name" class="form-control mb-2"
           placeholder="e.g., Michael"
           value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>">

    <label>Last Name</label>
    <input type="text" name="last_name" class="form-control mb-2"
           placeholder="e.g., Johnson"
           value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>">

    <label>Email</label>
    <input type="email" name="email" class="form-control mb-2"
           placeholder="e.g., user@school.edu"
           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">

</div>


<!-- ================================================================
     STAFF FIELDS
================================================================ -->
<div id="staffSection" style="display:none;">

    <label>Course</label>
    <select name="CourseName" class="form-select mb-2">
        <option value="">-- Select Course --</option>

        <?php foreach ($courseList as $c): ?>
            <option value="<?= htmlspecialchars($c['CourseName']) ?>"
                <?= (($_POST['CourseName'] ?? '') === $c['CourseName']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($c['CourseName']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label>Salary</label>
    <input type="number" step="0.01" name="salary" class="form-control mb-2"
           placeholder="e.g., 50000"
           value="<?= htmlspecialchars($_POST['salary'] ?? '') ?>">

    <label>Hire Date</label>
    <input type="date" name="hire_date" class="form-control mb-2"
           value="<?= htmlspecialchars($_POST['hire_date'] ?? '') ?>">

</div>


<!-- ================================================================
     STUDENT FIELDS
================================================================ -->
<div id="studentSection" style="display:none;">

    <label>Date of Birth</label>
    <input type="date" name="dob" class="form-control mb-2"
           value="<?= htmlspecialchars($_POST['dob'] ?? '') ?>">

    <label>Age</label>
    <input type="number" name="age" class="form-control mb-2"
           placeholder="e.g., 19"
           value="<?= htmlspecialchars($_POST['age'] ?? '') ?>">

    <label>GPA (optional)</label>
    <input type="number" step="0.01" name="gpa" class="form-control mb-2"
           placeholder="e.g., 3.75"
           value="<?= htmlspecialchars($_POST['gpa'] ?? '') ?>">

</div>


<!-- ================================================================
     BUTTON AREA
================================================================ -->
<div class="d-flex justify-content-center gap-4 mt-4">

    <button type="submit" class="btn btn-success px-5 py-2">
        Create User
    </button>

    <a href="manage_user.php" class="btn btn-warning px-5 py-2">
        ← Back
    </a>

</div>

</form>
</div>

<!-- ================================================================
     JAVASCRIPT — HANDLE ROLE FIELD SHOW/HIDE SECTIONS
================================================================ -->
<script>
const roleSelect = document.getElementById('roleSelect');
const adminSection = document.getElementById('adminSection');
const commonSection = document.getElementById('commonProfileSection');
const staffSection = document.getElementById('staffSection');
const studentSection = document.getElementById('studentSection');

/*
   updateSections()
   ------------------------------------------------------------
   Shows/hides fields based on selected role:
   - Admin → admin fields ONLY
   - Staff → common + staff
   - Student → common + student
*/
function updateSections() {
    // reset all sections (hide them)
    adminSection.style.display = 'none';
    commonSection.style.display = 'none';
    staffSection.style.display = 'none';
    studentSection.style.display = 'none';

    const val = roleSelect.value;

    // turn on correct sections
    if (val === 'Admin') adminSection.style.display = 'block';

    if (val === 'Staff') {
        commonSection.style.display = 'block';
        staffSection.style.display  = 'block';
    }

    if (val === 'Student') {
        commonSection.style.display = 'block';
        studentSection.style.display = 'block';
    }
}

// Run on dropdown change
roleSelect.addEventListener('change', updateSections);

// Run on initial page load (so selected role loads correctly)
updateSections();
</script>

</body>
</html>
