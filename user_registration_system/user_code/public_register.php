<?php
require_once '../includes/db.php'; // Load database connection
require_once '../includes/auth_model.php'; // Load authentication model

$message = ''; // Message placeholder for success/error output

if ($_SERVER['REQUEST_METHOD'] === 'POST') { // Handle form submission
    $username = trim($_POST['username']); // Get username
    $password = $_POST['password']; // Get password
    $firstName = trim($_POST['first_name']); // Get first name
    $lastName = trim($_POST['last_name']); // Get last name
    $email = trim($_POST['email']); // Get email
    $dob = $_POST['dob']; // Get date of birth
    $age = (date('Y') - date('Y', strtotime($dob))); // Calculate age

    try {
        $pdo = getDB(); // Connect to database
        $pdo->beginTransaction(); // Start transaction

        // Create user account (inactive until admin approval)
        $stmtUser = $pdo->prepare("
            INSERT INTO User (Username, PasswordHash, Role, IsActive)
            VALUES (?, ?, 'Student', 0)
        ");
        $stmtUser->execute([$username, password_hash($password, PASSWORD_DEFAULT)]); // Insert user
        $userId = $pdo->lastInsertId(); // Get newly created UserID

        // Create student profile linked to user
        $stmtStudent = $pdo->prepare("
            INSERT INTO Student (UserID, FirstName, LastName, DateOfBirth, Email, Age, GPA, IsActive)
            VALUES (?, ?, ?, ?, ?, ?, NULL, 1)
        ");
        $stmtStudent->execute([$userId, $firstName, $lastName, $dob, $email, $age]); // Insert student data

        $pdo->commit(); // Commit transaction
        $message = "<p class='success-msg'>✅ Registration successful! Your account is pending admin approval.</p>"; // Success message
    } catch (Exception $e) {
        $pdo->rollBack(); // Rollback on failure
        $message = "<p class='error-msg'>⚠️ Registration failed: " . htmlspecialchars($e->getMessage()) . "</p>"; // Error message
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"> <!-- Page encoding -->
  <title>Student Registration</title> <!-- Page title -->

  <style>
    /* Page background and layout */
    body {
      background: linear-gradient(135deg, #182848, #4b6cb7);
      font-family: "Poppins", sans-serif;
      display: flex;
      align-items: center;
      justify-content: center;
      height: 100vh;
      color: #333;
      margin: 0;
    }

    /* Registration container */
    .auth-container {
      background: #fff;
      border-radius: 20px;
      box-shadow: 0 10px 25px rgba(0,0,0,0.25);
      width: 420px;
      max-width: 90%;
      padding: 40px 30px;
      text-align: center;
      animation: fadeIn 0.6s ease;
    }

    /* Fade-in animation */
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }

    /* Title style */
    h2 {
      margin-bottom: 20px;
      color: #182848;
      font-size: 22px;
      font-weight: 600;
    }

    /* Labels */
    label {
      display: block;
      text-align: left;
      color: #555;
      font-size: 14px;
      margin-top: 10px;
    }

    /* Input fields */
    input {
      width: 100%;
      padding: 10px;
      border: 1px solid #ccc;
      border-radius: 8px;
      margin-top: 5px;
      outline: none;
      transition: border 0.3s ease;
    }

    /* Input focus effect */
    input:focus {
      border-color: #4b6cb7;
      box-shadow: 0 0 4px rgba(75,108,183,0.4);
    }

    /* Submit button */
    button {
      margin-top: 20px;
      width: 100%;
      padding: 10px;
      background: linear-gradient(135deg, #4b6cb7, #182848);
      color: white;
      font-weight: 600;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      transition: transform 0.2s, background 0.3s;
    }

    /* Button hover effect */
    button:hover {
      transform: translateY(-2px);
      background: linear-gradient(135deg, #5d7ad2, #223b7a);
    }

    /* Success message */
    .success-msg {
      color: #2ecc71;
      font-weight: 600;
      margin-top: 10px;
    }

    /* Error message */
    .error-msg {
      color: #e74c3c;
      font-weight: 600;
      margin-top: 10px;
    }

    /* Links */
    a {
      display: inline-block;
      margin-top: 15px;
      text-decoration: none;
      color: #4b6cb7;
      font-weight: 500;
      transition: color 0.2s;
    }

    a:hover { color: #182848; } /* Link hover effect */
  </style>
</head>

<body>

  <div class="auth-container"> <!-- Form container -->

    <h2> Student Registration</h2> <!-- Form title -->

    <?php echo $message; ?> <!-- Show success/error messages -->

    <form method="POST"> <!-- Registration form -->

      <label>Username</label>
      <input type="text" name="username" placeholder="Enter username" required> <!-- Username -->

      <label>Password</label>
      <input type="password" name="password" placeholder="Enter password" required> <!-- Password -->

      <label>First Name</label>
      <input type="text" name="first_name" placeholder="Enter first name" required> <!-- First Name -->

      <label>Last Name</label>
      <input type="text" name="last_name" placeholder="Enter last name" required> <!-- Last Name -->

      <label>Email</label>
      <input type="email" name="email" placeholder="Enter email address" required> <!-- Email -->

      <label>Date of Birth</label>
      <input type="date" name="dob" required> <!-- DOB -->

      <button type="submit">Register</button> <!-- Submit button -->

    </form>

    <a href="login.php">⬅ Back to Login</a> <!-- Back link -->

  </div>

</body>
</html>
