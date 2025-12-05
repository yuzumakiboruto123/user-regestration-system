<?php
/* 
    Secure page access: Allow only Admin + Staff
*/
require_once '../includes/auth_check.php';
require_once '../includes/db.php';
requireRole(['Admin', 'Staff']);

/* Database connection */
$pdo = getDB();

/* Messages */
$message = '';
$message_type = '';

/* Handle form on POST */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username      = trim($_POST['username'] ?? '');
    $password      = trim($_POST['password'] ?? '');
    $first_name    = trim($_POST['first_name'] ?? '');
    $last_name     = trim($_POST['last_name'] ?? '');
    $email         = trim($_POST['email'] ?? '');
    $date_of_birth = trim($_POST['date_of_birth'] ?? '');
    $age           = trim($_POST['age'] ?? '');
    $gpa           = trim($_POST['gpa'] ?? '');
    $status_active = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;

    /* Validation */
    $errors = [];

    if ($username === '') $errors[] = "Username is required.";
    if ($password === '') $errors[] = "Password is required.";
    if ($first_name === '') $errors[] = "First Name is required.";
    if ($last_name === '') $errors[] = "Last Name is required.";
    if ($email === '') $errors[] = "Email is required.";
    if ($date_of_birth === '') $errors[] = "Date of Birth is required.";
    if ($age === '' || !ctype_digit($age)) $errors[] = "Age must be a number.";
    if ($gpa !== '' && !is_numeric($gpa)) $errors[] = "GPA must be numeric.";

    if (empty($errors)) {
        try {

            /* Check duplicates */
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM user WHERE Username = :u");
            $stmt->execute([':u' => $username]);
            if ($stmt->fetchColumn() > 0) $errors[] = "Username already exists.";

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM user WHERE Email = :e");
            $stmt->execute([':e' => $email]);
            if ($stmt->fetchColumn() > 0) $errors[] = "Email already exists.";

            if (empty($errors)) {
                $pdo->beginTransaction();

                /* Insert into user table */
                $sqlUser = "
                    INSERT INTO user (Username, Email, PasswordHash, Role, IsActive)
                    VALUES (:u, :e, :p, 'Student', :a)
                ";
                $stmt = $pdo->prepare($sqlUser);
                $stmt->execute([
                    ':u' => $username,
                    ':e' => $email,
                    ':p' => password_hash($password, PASSWORD_DEFAULT),
                    ':a' => $status_active
                ]);

                $newUserId = $pdo->lastInsertId();

                /* Insert into student table */
                $sqlStudent = "
                    INSERT INTO student (
                        UserID, FirstName, LastName, DateOfBirth, Email,
                        Age, GPA, ProfileImage, AvatarImage, IsActive
                    )
                    VALUES (
                        :uid, :fn, :ln, :dob, :email, :age, :gpa,
                        NULL, NULL, :active
                    )
                ";
                $stmt = $pdo->prepare($sqlStudent);
                $stmt->execute([
                    ':uid'    => $newUserId,
                    ':fn'     => $first_name,
                    ':ln'     => $last_name,
                    ':dob'    => $date_of_birth,
                    ':email'  => $email,
                    ':age'    => $age,
                    ':gpa'    => $gpa !== '' ? $gpa : null,
                    ':active' => $status_active
                ]);

                $pdo->commit();

                $message = "Student created successfully!";
                $message_type = "success";
                $_POST = [];

            } else {
                $message = implode("<br>", $errors);
                $message_type = "error";
            }

        } catch (Exception $ex) {

            if ($pdo->inTransaction()) $pdo->rollBack();
            $message = "Database Error: " . $ex->getMessage();
            $message_type = "error";
        }
    } else {
        $message = implode("<br>", $errors);
        $message_type = "error";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Add Student</title>

    <style>
        body {
            background:#f3f4f6;
            font-family:system-ui, Arial, sans-serif;
            padding:40px;
        }

        .form-box {
            max-width:650px;
            margin:0 auto;
            background:white;
            padding:35px 40px;
            border-radius:18px;
            box-shadow:0 15px 35px rgba(0,0,0,0.10);
        }

        h2 {
            text-align:center;
            color:#3446eb;
            font-size:28px;
            margin-bottom:25px;
            font-weight:800;
        }

        .msg {
            padding:10px 15px;
            border-radius:10px;
            margin-bottom:20px;
        }
        .msg-success { background:#d1fae5; color:#065f46; border:1px solid #10b981; }
        .msg-error   { background:#fee2e2; color:#b91c1c; border:1px solid #ef4444; }

        label {
            display:block;
            margin-bottom:6px;
            font-weight:600;
        }

        input, select {
            width:100%;
            padding:10px;
            border-radius:12px;
            border:1px solid #cbd5e1;
            font-size:15px;
            margin-bottom:18px;
        }

        .row {
            display:flex;
            gap:18px;
        }

        .row .col {
            flex:1;
        }

        .btn-area {
            display:flex;
            justify-content:center;
            gap:18px;
            margin-top:25px;
        }

        .btn {
            padding:10px 22px;
            border-radius:14px;
            text-decoration:none;
            font-weight:700;
            font-size:15px;
            cursor:pointer;
            border:none;
        }

        .btn-green { background:#10b981; color:white; }
        .btn-red   { background:#ef4444; color:white; }
        .btn-blue  { background:#3b82f6; color:white; }

        .btn:hover { opacity:0.85; }
    </style>
</head>

<body>

<div class="form-box">

    <h2>Add New Student</h2>

    <?php if ($message): ?>
        <div class="msg msg-<?php echo $message_type; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <!-- FORM START -->
    <form method="POST">

        <!-- USERNAME + PASSWORD -->
        <div class="row">
            <div class="col">
                <label>Username *</label>
                <input type="text" name="username" placeholder="e.g., john_doe21"
                       value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
            </div>
            <div class="col">
                <label>Password *</label>
                <input type="password" name="password"
                       placeholder="Min 8 chars with upper, lower, number" required>
            </div>
        </div>

        <!-- FIRST + LAST NAME -->
        <div class="row">
            <div class="col">
                <label>First Name *</label>
                <input type="text" name="first_name" placeholder="e.g., Michael"
                       value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>" required>
            </div>
            <div class="col">
                <label>Last Name *</label>
                <input type="text" name="last_name" placeholder="e.g., Johnson"
                       value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>" required>
            </div>
        </div>

        <!-- EMAIL + DOB -->
        <div class="row">
            <div class="col">
                <label>Email *</label>
                <input type="email" name="email" placeholder="e.g., user@school.edu"
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
            </div>
            <div class="col">
                <label>Date of Birth *</label>
                <input type="date" name="date_of_birth"
                       value="<?php echo htmlspecialchars($_POST['date_of_birth'] ?? ''); ?>" required>
            </div>
        </div>

        <!-- AGE + GPA -->
        <div class="row">
            <div class="col">
                <label>Age *</label>
                <input type="number" name="age" placeholder="e.g., 19"
                       value="<?php echo htmlspecialchars($_POST['age'] ?? ''); ?>" required>
            </div>
            <div class="col">
                <label>GPA (optional)</label>
                <input type="number" step="0.01" min="0" max="4"
                       name="gpa" placeholder="e.g., 3.75"
                       value="<?php echo htmlspecialchars($_POST['gpa'] ?? ''); ?>">
            </div>
        </div>

        <!-- STATUS -->
        <label>Account Status *</label>
        <select name="is_active">
            <option value="1">Active</option>
            <option value="0">Inactive</option>
        </select>

        <!-- BUTTONS -->
        <div class="btn-area">
            <button type="submit" class="btn btn-green">Create Student</button>
            <a href="student_list.php" class="btn btn-red">Cancel</a>
            <a href="student_list.php" class="btn btn-blue"> Back </a>
        </div>

    </form>
    <!-- FORM END -->

</div>

</body>
</html>
