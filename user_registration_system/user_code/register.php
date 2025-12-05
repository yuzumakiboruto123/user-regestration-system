<?php
session_start(); // Start session for form handling

// Load DB + validation helpers + audit log
require_once '../includes/db.php'; // Database connection
require_once '../includes/validation.php'; // Input validation functions
require_once '../includes/audit.php'; // Audit logging (optional)

$pdo = getDB(); // Get database instance
$message = ""; // Placeholder for success/error messages

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') { // Check if form is submitted

    // Basic fields from user input
    $username   = trim($_POST['username'] ?? ''); // Username
    $email      = trim($_POST['email'] ?? ''); // Email
    $password   = $_POST['password'] ?? ''; // Password
    $password2  = $_POST['confirm_password'] ?? ''; // Confirm password
    $role       = $_POST['role'] ?? 'Student'; // Role (Student/Staff)

    // Common extra fields
    $firstName  = trim($_POST['first_name'] ?? ''); // First name
    $lastName   = trim($_POST['last_name'] ?? ''); // Last name

    // Student-only fields
    $dob        = trim($_POST['dob'] ?? ''); // Date of birth
    $age        = trim($_POST['age'] ?? ''); // Age

    $errors = []; // Collect validation errors

    // ---- VALIDATION SECTION ---- //

    if (!validateUsername($username)) { // Validate username
        $errors[] = "Username must be 4–20 characters, letters/numbers/underscore only.";
    }

    if (!validateEmail($email)) { // Validate email format
        $errors[] = "Email must be valid and end with @school.edu.";
    }

    if (!validatePassword($password)) { // Check password strength
        $errors[] = "Password must be at least 6 characters.";
    }

    if ($password !== $password2) { // Verify password match
        $errors[] = "Password and confirm password do not match.";
    }

    if (!validateName($firstName)) { // Validate first name
        $errors[] = "First name must contain only letters and spaces (2–40 characters).";
    }

    if (!validateName($lastName)) { // Validate last name
        $errors[] = "Last name must contain only letters and spaces (2–40 characters).";
    }

    if ($role !== 'Staff' && $role !== 'Student') { // Validate allowed roles
        $errors[] = "Invalid role selected.";
    }

    // Student-specific validation
    if ($role === 'Student') {
        if (!validateDOB($dob)) { // Validate DOB
            $errors[] = "Date of birth must be a valid past date.";
        }
        if (!validateAge($age)) { // Validate age range
            $errors[] = "Age must be between 5 and 100.";
        }
    }

    // Check if username already exists
    $check = $pdo->prepare("SELECT COUNT(*) FROM user WHERE Username = ?");
    $check->execute([$username]);
    if ($check->fetchColumn() > 0) { // Username duplication check
        $errors[] = "This username is already taken. Please choose another.";
    }

    // If validation errors exist, show them
    if (!empty($errors)) {
        $message = "<div class='error-msg'><strong>⚠ Please fix the following:</strong><br>" .
            implode("<br>", array_map('htmlspecialchars', $errors)) . "</div>";
    } else {

        try {
            $pdo->beginTransaction(); // Begin transaction for atomic insert

            // Insert into user table (pending approval)
            $stmt = $pdo->prepare("
                INSERT INTO user (Username, PasswordHash, Role, Email, IsActive, CreatedDate)
                VALUES (?, ?, ?, ?, 0, NOW())
            ");

            $stmt->execute([
                $username,
                password_hash($password, PASSWORD_DEFAULT), // Hash password
                $role,
                $email
            ]);

            $userId = $pdo->lastInsertId(); // Get new UserID

            // Insert basic profile based on role
            if ($role === 'Staff') {

                // Create default staff profile
                $insertStaff = $pdo->prepare("
                    INSERT INTO staff (UserID, FirstName, LastName, Email, Department, Salary, HireDate, IsActive)
                    VALUES (?, ?, ?, ?, 'General', 0.00, CURDATE(), 0)
                ");

                $insertStaff->execute([
                    $userId,
                    $firstName,
                    $lastName,
                    $email
                ]);

            } elseif ($role === 'Student') {

                // Create student profile
                $insertStudent = $pdo->prepare("
                    INSERT INTO student (UserID, FirstName, LastName, DateOfBirth, Email, Age, GPA, IsActive)
                    VALUES (?, ?, ?, ?, ?, ?, 0.00, 0)
                ");

                $insertStudent->execute([
                    $userId,
                    $firstName,
                    $lastName,
                    $dob,
                    $email,
                    $age
                ]);
            }

            $pdo->commit(); // Commit full transaction

            // Log audit activity (optional)
            logAction(null, "Self registration", "User", $userId, "Username: $username (Role: $role, Pending)");

            // Success message
            $message = "<div class='success-msg'>
                ✅ Registration successful! Your account is <strong>pending admin approval</strong>.<br>
                You will be able to log in once an admin activates your account.
            </div>";

            $_POST = []; // Clear form

        } catch (PDOException $e) {
            $pdo->rollBack(); // Rollback on failure
            $message = "<div class='error-msg'>❌ Something went wrong while registering. Please try again.</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"> <!-- Page charset -->
<title>Register | School Management</title> <!-- Page title -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"> <!-- Bootstrap -->

<style>
/* Background gradient */
body{
  background:linear-gradient(135deg,#4b6cb7,#182848);
  font-family:Poppins,sans-serif;
  display:flex;justify-content:center;align-items:center;
  min-height:100vh;margin:0;padding:20px;
}

/* Registration box styling */
.register-box{
  width:480px;max-width:95%;background:#fff;color:#333;
  border-radius:18px;padding:30px 28px;
  box-shadow:0 8px 26px rgba(0,0,0,0.25);
}

h2{text-align:center;color:#182848;margin-bottom:5px;}
.subtitle{text-align:center;color:#555;margin-bottom:20px;font-size:14px;}

/* Labels + Inputs */
label{font-size:14px;margin-top:10px;color:#444;}
input,select{
  width:100%;padding:10px;border-radius:8px;border:1px solid #bbb;
  margin-top:5px;outline:none;
}

/* Submit button */
button{
  width:100%;margin-top:18px;padding:11px;border:none;
  border-radius:8px;background:linear-gradient(135deg,#4b6cb7,#182848);
  color:#fff;font-weight:600;cursor:pointer;transition:0.2s;
}
button:hover{transform:scale(1.03);}

/* Success + error messages */
.success-msg{
  background:#e9fcef;color:#27ae60;padding:10px;border-radius:8px;
  margin-bottom:10px;font-size:14px;
}
.error-msg{
  background:#fdecea;color:#c0392b;padding:10px;border-radius:8px;
  margin-bottom:10px;font-size:14px;
}

/* Extra fields per role */
.role-extra{display:none;}

/* Back link */
.back-login{text-align:center;margin-top:15px;font-size:14px;}
.back-login a{text-decoration:none;color:#4b6cb7;font-weight:600;}
.back-login a:hover{text-decoration:underline;}
</style>
</head>

<body>

<div class="register-box"> <!-- Registration form container -->

    <h2>📝 User Registration</h2> <!-- Page header -->
    <p class="subtitle">Register as Student or Staff account. An admin must approve before you can log in.</p>

    <?= $message ?> <!-- Show form messages -->

    <form method="POST" autocomplete="off"> <!-- Form start -->

        <!-- Username -->
        <label>Username</label>
        <input type="text" name="username" required
               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">

        <!-- Email -->
        <label>School Email</label>
        <input type="email" name="email" required placeholder="example@school.edu"
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">

        <!-- Password -->
        <label>Password</label>
        <input type="password" name="password" required>

        <!-- Confirm Password -->
        <label>Confirm Password</label>
        <input type="password" name="confirm_password" required>

        <!-- Role selection -->
        <label>Role</label>
        <select name="role" id="roleSelect" required>
            <option value="Student" <?= (($_POST['role'] ?? '')==='Student')?'selected':''; ?>>Student</option>
            <option value="Staff"   <?= (($_POST['role'] ?? '')==='Staff')?'selected':''; ?>>Staff</option>
        </select>

        <!-- Common fields -->
        <label>First Name</label>
        <input type="text" name="first_name"
               value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>">

        <label>Last Name</label>
        <input type="text" name="last_name"
               value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>">

        <!-- Student-only section -->
        <div id="studentFields" class="role-extra">
            <label>Date of Birth</label>
            <input type="date" name="dob"
                   value="<?= htmlspecialchars($_POST['dob'] ?? '') ?>">

            <label>Age</label>
            <input type="number" name="age" min="5" max="100"
                   value="<?= htmlspecialchars($_POST['age'] ?? '') ?>">
        </div>

        <!-- Staff-only info -->
        <div id="staffFields" class="role-extra">
            <small class="text-muted">Department and salary can be updated later by Admin.</small>
        </div>

        <button type="submit">Register</button> <!-- Submit button -->

    </form>

    <div class="back-login">
        Already registered? <a href="login.php">Log in here</a> <!-- Login link -->
    </div>
</div>

<script>
// Show/hide fields based on selected role
const roleSelect    = document.getElementById('roleSelect'); // Role dropdown
const studentFields = document.getElementById('studentFields'); // Student fields
const staffFields   = document.getElementById('staffFields'); // Staff fields

// Update field visibility
function updateRoleFields() {
    const role = roleSelect.value;
    if (role === 'Student') {
        studentFields.style.display = 'block'; // Show student fields
        staffFields.style.display   = 'none'; // Hide staff fields
    } else if (role === 'Staff') {
        studentFields.style.display = 'none';
        staffFields.style.display   = 'block'; // Show staff fields
    } else {
        studentFields.style.display = 'none';
        staffFields.style.display   = 'none';
    }
}

updateRoleFields(); // Set initial visibility
roleSelect.addEventListener('change', updateRoleFields); // Update on change
</script>

</body>
</html>
