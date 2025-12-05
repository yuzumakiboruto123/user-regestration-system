<?php
// ===============================================
// edit_student.php
// Edit existing student + linked user account
// ===============================================

// -----------------------------------------------
// Include auth_check.php to ensure the user is
// logged in and has the correct role.
// Only Admin + Staff can access this page.
// -----------------------------------------------
require_once '../includes/auth_check.php';

// -----------------------------------------------
// Include the database connection helper.
// getDB() returns a shared PDO connection.
// -----------------------------------------------
require_once '../includes/db.php';

// -----------------------------------------------
// Restrict this page to Admin and Staff roles.
// Students or Public users cannot access here.
// -----------------------------------------------
requireRole(['Admin', 'Staff']);

// -----------------------------------------------
// Create PDO database connection using our helper
// -----------------------------------------------
$pdo = getDB();

// -----------------------------------------------
// Start session to access username (for header)
// and to possibly use flash messages if needed.
// -----------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// -----------------------------------------------
// Variables to store success or error message
// to be displayed above the form.
// -----------------------------------------------
$message      = '';
$message_type = '';   // "success" or "error"

// -----------------------------------------------
// Read logged-in username for the header display
// -----------------------------------------------
$currentUsername = $_SESSION['username'] ?? 'User';

// -----------------------------------------------
// Helper: load student + user data for a given
// StudentID. Returns associative array or false.
// -----------------------------------------------
function loadStudentWithUser(PDO $pdo, int $studentId)
{
    // SQL joins student + user using UserID
    $sql = "
        SELECT 
            s.StudentID,
            s.UserID,
            s.FirstName,
            s.LastName,
            s.DateOfBirth,
            s.Email      AS StudentEmail,
            s.Age,
            s.GPA,
            s.IsActive   AS StudentIsActive,
            u.Username,
            u.Email      AS UserEmail,
            u.IsActive   AS UserIsActive
        FROM student s
        JOIN user u ON s.UserID = u.UserID
        WHERE s.StudentID = :sid
        LIMIT 1
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':sid' => $studentId]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: false;
}

// -----------------------------------------------
// Get StudentID from GET or POST.
// When first opening, it comes from ?id=... (GET).
// On form submit, we keep it in a hidden field.
// -----------------------------------------------
$studentId = 0;
if (isset($_GET['id'])) {
    $studentId = (int)$_GET['id'];       // from query string
} elseif (isset($_POST['student_id'])) {
    $studentId = (int)$_POST['student_id']; // from hidden field
}

// -----------------------------------------------
// If no valid StudentID is provided, we cannot
// edit anything, so we stop with a simple error.
// -----------------------------------------------
if ($studentId <= 0) {
    die('Invalid or missing Student ID.');
}

// -----------------------------------------------
// We will store current database values here so
// that we can pre-fill the form fields.
// -----------------------------------------------
$dbRow = loadStudentWithUser($pdo, $studentId);

if (!$dbRow) {
    // If we cannot find the student, exit early.
    die('Student record not found.');
}

// -----------------------------------------------
// Initialize form fields with DB values by default.
// If POST fails validation, we will override these
// with submitted values to keep user input.
// -----------------------------------------------
$form_username      = $dbRow['Username'];
$form_first_name    = $dbRow['FirstName'];
$form_last_name     = $dbRow['LastName'];
$form_email         = $dbRow['UserEmail'];   // same as StudentEmail, but from user table
$form_date_of_birth = $dbRow['DateOfBirth'];
$form_age           = $dbRow['Age'];
$form_gpa           = $dbRow['GPA'];
$form_is_active     = $dbRow['UserIsActive']; // we keep user+student IsActive in sync

// ==================================================
// HANDLE FORM SUBMISSION (POST)
// ==================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // -------------------------------------------
    // Read hidden IDs from form (for safety)
    // -------------------------------------------
    $studentId = (int)($_POST['student_id'] ?? 0); // StudentID to update
    $userId    = (int)($_POST['user_id'] ?? 0);    // Linked UserID

    // -------------------------------------------
    // Read and trim posted values.
    // Password is optional here (change only if set).
    // -------------------------------------------
    $username      = trim($_POST['username'] ?? '');
    $newPassword   = trim($_POST['password'] ?? '');    // optional
    $first_name    = trim($_POST['first_name'] ?? '');
    $last_name     = trim($_POST['last_name'] ?? '');
    $date_of_birth = trim($_POST['date_of_birth'] ?? '');
    $age           = trim($_POST['age'] ?? '');
    $gpa           = trim($_POST['gpa'] ?? '');
    $is_active     = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;

    // -------------------------------------------
    // We keep email read-only in the form,
    // but still read its value for consistency.
    // -------------------------------------------
    $email = trim($_POST['email'] ?? $dbRow['UserEmail']);

    // -------------------------------------------
    // Start validation of fields.
    // If something is wrong, we collect errors
    // in an array and show them later.
    // -------------------------------------------
    $errors = [];

    // Validate username (simple non-empty, 4–50 chars)
    if ($username === '') {
        $errors[] = 'Username is required.';
    } elseif (strlen($username) < 4 || strlen($username) > 50) {
        $errors[] = 'Username must be between 4 and 50 characters.';
    }

    // First name required
    if ($first_name === '') {
        $errors[] = 'First name is required.';
    }

    // Last name required
    if ($last_name === '') {
        $errors[] = 'Last name is required.';
    }

    // Date of birth required
    if ($date_of_birth === '') {
        $errors[] = 'Date of birth is required.';
    }

    // Age required and must be integer
    if ($age === '' || !ctype_digit($age)) {
        $errors[] = 'Age is required and must be a whole number.';
    }

    // GPA optional: if provided, must be numeric
    $gpaValue = null;
    if ($gpa !== '') {
        if (!is_numeric($gpa)) {
            $errors[] = 'GPA must be a numeric value.';
        } else {
            $gpaValue = (float)$gpa;  // convert to float for DB
        }
    }

    // -------------------------------------------
    // If there are no basic validation errors,
    // we continue with database checks & updates.
    // -------------------------------------------
    if (empty($errors)) {

        try {
            // -----------------------------------
            // Check if another user (different ID)
            // already uses this username.
            // -----------------------------------
            $checkSql = "SELECT COUNT(*) FROM user WHERE Username = :uname AND UserID <> :uid";
            $checkStmt = $pdo->prepare($checkSql);
            $checkStmt->execute([
                ':uname' => $username,
                ':uid'   => $userId
            ]);
            $usernameTaken = (int)$checkStmt->fetchColumn() > 0;

            if ($usernameTaken) {
                $errors[] = 'Error: Another account already uses this username.';
            }

            // -----------------------------------
            // If still no errors, we perform the
            // actual updates inside a transaction.
            // -----------------------------------
            if (empty($errors)) {

                // begin atomic transaction
                $pdo->beginTransaction();

                // -------------------------------
                // UPDATE user table.
                // If newPassword is not empty,
                // we also update PasswordHash.
                // -------------------------------
                if ($newPassword !== '') {
                    // Update with password change
                    $updateUserSql = "
                        UPDATE user
                        SET Username   = :uname,
                            IsActive   = :active,
                            PasswordHash = :phash
                        WHERE UserID   = :uid
                    ";
                    $stmtUser = $pdo->prepare($updateUserSql);
                    $stmtUser->execute([
                        ':uname' => $username,
                        ':active'=> $is_active,
                        ':phash' => password_hash($newPassword, PASSWORD_DEFAULT),
                        ':uid'   => $userId
                    ]);
                } else {
                    // Update without changing password
                    $updateUserSql = "
                        UPDATE user
                        SET Username = :uname,
                            IsActive = :active
                        WHERE UserID = :uid
                    ";
                    $stmtUser = $pdo->prepare($updateUserSql);
                    $stmtUser->execute([
                        ':uname' => $username,
                        ':active'=> $is_active,
                        ':uid'   => $userId
                    ]);
                }

                // -------------------------------
                // UPDATE student table.
                // Email stays same (read-only).
                // GPA may be NULL if not provided.
                // IsActive mirrors user table.
                // -------------------------------
                $updateStudentSql = "
                    UPDATE student
                    SET FirstName   = :fname,
                        LastName    = :lname,
                        DateOfBirth = :dob,
                        Age         = :age,
                        GPA         = :gpa,
                        IsActive    = :sactive
                    WHERE StudentID = :sid
                ";

                $stmtStudent = $pdo->prepare($updateStudentSql);
                $stmtStudent->execute([
                    ':fname'   => $first_name,
                    ':lname'   => $last_name,
                    ':dob'     => $date_of_birth,
                    ':age'     => (int)$age,
                    ':gpa'     => $gpaValue,
                    ':sactive' => $is_active,
                    ':sid'     => $studentId
                ]);

                // commit transaction if both updates succeed
                $pdo->commit();

                // Success message
                $message      = 'Student and linked user account updated successfully!';
                $message_type = 'success';

                // Reload fresh data from DB to re-populate form
                $dbRow = loadStudentWithUser($pdo, $studentId);

                $form_username      = $dbRow['Username'];
                $form_first_name    = $dbRow['FirstName'];
                $form_last_name     = $dbRow['LastName'];
                $form_email         = $dbRow['UserEmail'];
                $form_date_of_birth = $dbRow['DateOfBirth'];
                $form_age           = $dbRow['Age'];
                $form_gpa           = $dbRow['GPA'];
                $form_is_active     = $dbRow['UserIsActive'];

            } else {
                // If username conflict or other logical error
                $message      = implode('<br>', $errors);
                $message_type = 'error';
            }

        } catch (PDOException $e) {

            // Roll back transaction if something went wrong
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $message      = 'Database Error: ' . htmlspecialchars($e->getMessage());
            $message_type = 'error';
        }

    } else {
        // There were validation errors before hitting DB
        $message      = implode('<br>', $errors);
        $message_type = 'error';

        // Refill form with submitted values so user does not lose input
        $form_username      = $username;
        $form_first_name    = $first_name;
        $form_last_name     = $last_name;
        $form_email         = $email;
        $form_date_of_birth = $date_of_birth;
        $form_age           = $age;
        $form_gpa           = $gpa;
        $form_is_active     = $is_active;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <!-- Make layout responsive on mobile -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Browser tab title -->
    <title>Edit Student - Student Management System</title>
    <!-- External stylesheet from your Student Management UI -->
    <link rel="stylesheet" href="style.css">

    <style>
        /* Simple centered card for edit form (works with your base style.css) */
        body {
            /* keep any global styles from style.css, but ensure nice padding */
            padding: 30px;
        }
        .edit-container {
            max-width: 800px;             /* limit form width */
            margin: 0 auto;               /* center horizontally */
            background: #ffffff;          /* white card */
            border-radius: 16px;          /* rounded corners */
            padding: 30px 25px;           /* inner spacing */
            box-shadow: 0 10px 30px rgba(0,0,0,0.12); /* soft shadow */
        }
        .edit-title {
            margin: 0 0 10px;
            font-size: 24px;
            font-weight: 700;
            color: #333;
            text-align: center;
        }
        .edit-subtitle {
            margin: 0 0 20px;
            font-size: 14px;
            color: #6b7280;
            text-align: center;
        }
        .form-row {
            display: flex;
            gap: 15px;
            margin-bottom: 16px;
        }
        .form-group {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .form-group label {
            font-size: 14px;
            margin-bottom: 4px;
            color: #374151;
        }
        .form-group input,
        .form-group select {
            padding: 8px 10px;
            border-radius: 8px;
            border: 1px solid #d1d5db;
            font-size: 14px;
        }
        .form-group input[readonly] {
            background: #f3f4f6;          /* light grey for read-only email */
            color: #6b7280;
        }
        .alert {
            padding: 10px 14px;
            border-radius: 10px;
            font-size: 14px;
            margin-bottom: 16px;
        }
        .alert-success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #15803d;
        }
        .alert-error {
            background: #fee2e2;
            color: #b91c1c;
            border: 1px solid #b91c1c;
        }
        .btn-row {
            margin-top: 24px;
            display: flex;
            justify-content: center;
            gap: 15px;
        }
        .btn {
            padding: 9px 18px;
            border-radius: 999px;
            border: none;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .btn-save {
            background: #16a34a;
            color: white;
        }
        .btn-cancel {
            background: #ef4444;
            color: white;
        }
        .btn-back {
            background: #4b5563;
            color: white;
        }
    </style>
</head>

<body>

    <!-- Optional simple header with system title + welcome + logout -->
    <div style="max-width: 800px; margin: 0 auto 20px; text-align: center;">
        <h1 style="margin-bottom: 4px;">📚 Student Management System</h1>
        <p style="margin: 0; color:#6b7280;">Developed by Mithil - Future Billionaire Team</p>
        <div style="margin-top: 10px; display: flex; justify-content: center; align-items: center; gap: 10px;">
            <span style="background: rgba(99,102,241,0.08); padding: 6px 16px; border-radius: 999px; font-size: 0.9em;">
                👤 Welcome, <strong><?php echo htmlspecialchars($currentUsername); ?></strong>
            </span>
            <a href="../logout.php"
               style="background:#ef4444; color:#fff; padding:6px 14px; border-radius:999px; text-decoration:none; font-size:0.85em;">
               🚪 Logout
            </a>
        </div>
    </div>

    <!-- Centered edit form card -->
    <div class="edit-container">

        <h2 class="edit-title">✏️ Edit Student</h2>
        <p class="edit-subtitle">Update login and personal details for this student.</p>

        <!-- Show message if available -->
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type === 'success' ? 'success' : 'error'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- EDIT FORM -->
        <form method="POST" action="">
            <!-- Hidden fields to keep IDs across postbacks -->
            <input type="hidden" name="student_id" value="<?php echo (int)$dbRow['StudentID']; ?>">
            <input type="hidden" name="user_id"    value="<?php echo (int)$dbRow['UserID']; ?>">

            <!-- ==========================
                 LOGIN ACCOUNT INFORMATION
                 ========================== -->
            <h3 style="margin: 18px 0 12px; color:#4B6EF5;">Login Account Information</h3>

            <div class="form-row">
                <div class="form-group">
                    <label for="username">Username *</label>
                    <input type="text"
                           id="username"
                           name="username"
                           placeholder="e.g., john_doe21"
                           value="<?php echo htmlspecialchars($form_username); ?>"
                           required>
                </div>
                <div class="form-group">
                    <label for="password">Password (leave blank to keep current)</label>
                    <input type="password"
                           id="password"
                           name="password"
                           placeholder="Enter new password only if you want to change it">
                </div>
            </div>

            <!-- ==========================
                 PERSONAL INFORMATION
                 ========================== -->
            <h3 style="margin: 24px 0 12px; color:#4B6EF5;">Personal Information</h3>

            <div class="form-row">
                <div class="form-group">
                    <label for="first_name">First Name *</label>
                    <input type="text"
                           id="first_name"
                           name="first_name"
                           placeholder="e.g., Michael"
                           value="<?php echo htmlspecialchars($form_first_name); ?>"
                           required>
                </div>
                <div class="form-group">
                    <label for="last_name">Last Name *</label>
                    <input type="text"
                           id="last_name"
                           name="last_name"
                           placeholder="e.g., Johnson"
                           value="<?php echo htmlspecialchars($form_last_name); ?>"
                           required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="email">Email (read-only)</label>
                    <input type="email"
                           id="email"
                           name="email"
                           value="<?php echo htmlspecialchars($form_email); ?>"
                           readonly>
                </div>
                <div class="form-group">
                    <label for="date_of_birth">Date of Birth *</label>
                    <input type="date"
                           id="date_of_birth"
                           name="date_of_birth"
                           value="<?php echo htmlspecialchars($form_date_of_birth); ?>"
                           required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="age">Age *</label>
                    <input type="number"
                           id="age"
                           name="age"
                           min="1"
                           placeholder="e.g., 19"
                           value="<?php echo htmlspecialchars($form_age); ?>"
                           required>
                </div>
                <div class="form-group">
                    <label for="gpa">GPA (optional)</label>
                    <input type="number"
                           id="gpa"
                           name="gpa"
                           step="0.01"
                           min="0"
                           max="4"
                           placeholder="e.g., 3.75"
                           value="<?php echo htmlspecialchars($form_gpa); ?>">
                </div>
            </div>

            <!-- ==========================
                 ACCOUNT STATUS
                 ========================== -->
            <h3 style="margin: 24px 0 12px; color:#4B6EF5;">Account Status</h3>

            <div class="form-row">
                <div class="form-group" style="max-width: 220px;">
                    <label for="is_active">Account Status *</label>
                    <select id="is_active" name="is_active" required>
                        <option value="1" <?php echo ((int)$form_is_active === 1) ? 'selected' : ''; ?>>Active</option>
                        <option value="0" <?php echo ((int)$form_is_active === 0) ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
            </div>

            <!-- ==========================
                 FORM BUTTONS
                 ========================== -->
            <div class="btn-row">
                <button type="submit" class="btn btn-save">💾 Update Student</button>
                <a href="student_list.php" class="btn btn-back">⬅ Back to Student List</a>
                <a href="student_list.php" class="btn btn-cancel">❌ Cancel</a>
            </div>

        </form>
    </div>

</body>
</html>
