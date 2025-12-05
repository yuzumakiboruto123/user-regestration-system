<?php
session_start(); // Start session for user authentication

require_once '../includes/auth_model.php'; // Load authentication model
require_once '../includes/audit.php'; // Load audit logging functions
require_once '../includes/db.php'; // Load database connection

$error = ''; // Store login error message

if ($_SERVER['REQUEST_METHOD'] === 'POST') { // Handle POST login request

    $username  = trim($_POST['username']); // Get username input
    $password  = $_POST['password']; // Get password input
    $role      = trim($_POST['role']); // Get selected role
    $admin_pin = $_POST['admin_pin'] ?? null; // Get admin PIN if provided

    $user = getUserByUsername($username); // Fetch user record
    $pdo  = getDB(); // Get database instance

    // Log every login attempt to database
    function logLoginAttempt($pdo, $userID, $success) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO loginattempts (UserID, IsSuccessful, AttemptTime)
                VALUES (:u, :s, NOW())
            ");
            $stmt->execute([':u' => $userID, ':s' => $success ? 1 : 0]);
        } catch (PDOException $e) { } // Ignore errors silently
    }

    if (!$user) { // Check if user exists
        $error = "❌ User not found!";
        logLoginAttempt($pdo, null, false); // Log failed attempt
        goto endLogin; // Skip to end
    }

    if ($user['IsActive'] == 0) { // Check if account is inactive
        $error = "⚠️ Account pending admin approval.";
        logLoginAttempt($pdo, $user['UserID'], false); // Log attempt
        goto endLogin;
    }

    if ($user['Role'] !== $role) { // Ensure correct role selection
        $error = "❌ Role mismatch!";
        logLoginAttempt($pdo, $user['UserID'], false); // Log attempt
        goto endLogin;
    }

    if ($role === "Admin") { // Additional admin validation

        if (empty($admin_pin) || !preg_match("/^[0-9]{6}$/", $admin_pin)) { // Validate PIN format
            $error = "❌ Admin PIN must be exactly 6 digits!";
            logLoginAttempt($pdo, $user['UserID'], false); // Log attempt
            goto endLogin;
        }

        $stmt = $pdo->prepare("SELECT AdminPINHash FROM admin WHERE UserID = ?"); // Get admin PIN data
        $stmt->execute([$user['UserID']]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$admin) { // Check if admin record exists
            $error = "❌ Admin record not found!";
            goto endLogin;
        }

        if (!password_verify($admin_pin, $admin['AdminPINHash'])) { // Validate admin PIN hash
            $error = "❌ Incorrect Admin PIN!";
            goto endLogin;
        }
    }

    $result = verifyUser($username, $password, $role); // Verify password and role

    if ($result && is_array($result)) { // Successful login

        $_SESSION['userID']   = $result['UserID']; // Set session user ID
        $_SESSION['role']     = $result['Role']; // Set session role
        $_SESSION['username'] = $result['Username']; // Set session username

        session_regenerate_id(true); // Prevent session fixation

        header("Location: dashboard.php"); // Redirect after login
        exit(); // Stop further execution
    }

    $error = "❌ Invalid username or password!"; // Generic login error
}

endLogin: // Label for goto jumps
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"> <!-- Page encoding -->
  <title>Login | School Management</title> <!-- Page title -->
  <link rel="stylesheet" href="../assets/css/auth-style.css"> <!-- External CSS -->

  <style>
    /* Inline styles for login UI */
    body {
      background: linear-gradient(135deg, #4b6cb7, #182848);
      font-family: "Poppins", sans-serif;
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
    }
    .auth-container {
      background: #fff;
      padding: 40px 30px;
      border-radius: 16px;
      width: 400px;
      text-align: center;
      box-shadow: 0 8px 24px rgba(0,0,0,0.2);
    }
    label {
      text-align: left;
      display: block;
      font-size: 14px;
      margin-top: 10px;
      color: #555;
    }
    input, select {
      width: 100%;
      padding: 10px;
      margin-top: 5px;
      border-radius: 8px;
      border: 1px solid #ccc;
    }
    button {
      width: 100%;
      padding: 10px;
      background: linear-gradient(135deg, #4b6cb7, #182848);
      border: none;
      border-radius: 8px;
      margin-top: 20px;
      color: #fff;
      font-weight: 600;
      cursor: pointer;
    }
    .error-msg { 
      color: #e74c3c; 
      margin-top: 10px; 
      font-weight: 500; 
    }
  </style>
</head>
<body>

<div class="auth-container"> <!-- Login form container -->
  <h2>Welcome Back 👋</h2> <!-- Header text -->

  <?php if ($error): ?>
      <p class="error-msg"><?= $error ?></p> <!-- Display login errors -->
  <?php endif; ?>

  <form method="POST"> <!-- Login form -->

    <label>Username</label>
    <input type="text" name="username" required> <!-- Username input -->

    <label>Password</label>
    <input type="password" name="password" required> <!-- Password input -->

    <label>Select Role</label>
    <select name="role" id="role-select" required> <!-- Role dropdown -->
      <option value="">-- Choose Role --</option>
      <option value="Admin">Admin</option>
      <option value="Staff">Staff</option>
      <option value="Student">Student</option>
    </select>

    <div id="adminPinField" style="display:none;"> <!-- Admin PIN field -->
      <label>Admin PIN</label>
      <input type="password"
             name="admin_pin"
             maxlength="6"
             pattern="\d{6}"
             placeholder="Enter 6-digit Admin PIN">
      <small style="font-size:12px; color:#888;">Admin PIN must be exactly 6 digits.</small>
    </div>

    <button type="submit">Login</button> <!-- Submit button -->

  </form>

  <p><a href="reset_request.php">Forgot Password?</a></p> <!-- Reset link -->
  <p>Don't have an account? <a href="register.php">Register here</a></p> <!-- Registration link -->

</div>

<script>
// Toggle admin PIN field on role change
document.addEventListener("DOMContentLoaded", () => {
    const role = document.getElementById("role-select");
    const pin  = document.getElementById("adminPinField");

    role.addEventListener("change", () => {
        pin.style.display = role.value === "Admin" ? "block" : "none"; // Show only for Admin
    });
});
</script>

</body>
</html>
