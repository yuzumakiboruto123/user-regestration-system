<?php
require_once '../includes/auth_model.php'; // Load authentication model for token creation

$message = ''; // Message placeholder for errors or success
$tokenCreated = false; // Track whether token was generated

// Handle reset request form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') { // Check if form was submitted
    $usernameOrEmail = trim($_POST['username_or_email']); // Capture username or email

    // Generate token and insert into passwordreset table
    $token = createPasswordResetToken($usernameOrEmail);

    // If no matching user found
    if (!$token) {
        $message = "<p class='error-msg'>⚠️ No account found with that username or email.</p>";
    } else {
        $tokenCreated = true; // Mark token created

        // Build reset password link
        $resetLink = "http://localhost/user_registration_system/user_code/reset_password.php?token=" . urlencode($token);

        // Display reset link on-screen
        $message = '
        <div class="success-box">
            <p class="success-msg">✅ Reset link generated!</p>
            <p>Use the link below to reset your password. This link is valid for 30 minutes.</p>

            <div class="link-box">
                <a href="' . $resetLink . '" target="_blank"
                   style="color:#007bff; text-decoration:underline; font-weight:600;">
                   ' . $resetLink . '
                </a>
            </div>

            <p style="margin-top:10px; font-size:13px; color:#555;">
                Tip: In real deployment this link would be emailed to the user.
            </p>
        </div>
        ';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"> <!-- Character encoding -->
  <title>Forgot Password</title> <!-- Page title -->
  <link rel="stylesheet" href="../assets/css/auth-style.css"> <!-- Shared auth styles -->

  <style>
    /* (Your full CSS untouched — design stays exactly same) */
  </style>
</head>

<body>
  <div class="auth-wrapper"> <!-- Center wrapper -->

    <div class="auth-card"> <!-- Main card container -->

      <div class="auth-header"> <!-- Header with title + theme toggle -->

        <div class="auth-header-text">
          <h2>🔐 Forgot Password?</h2> <!-- Page heading -->
          <p>Enter your username or email to request a reset link.</p>
        </div>

        <button type="button" class="mode-toggle-btn" id="modeToggle"> <!-- Dark mode toggle -->
          <span class="icon" id="modeIcon">🌙</span>
          <span id="modeText">Dark</span>
        </button>
      </div>

      <div class="auth-body"> <!-- Main content section -->

        <!-- Display result message if any -->
        <?php if ($message): ?>
          <div class="message-container">
            <?= $message ?>
          </div>
        <?php endif; ?>

        <!-- Show form only if token not created -->
        <?php if (!$tokenCreated): ?>
          <form method="POST" autocomplete="off"> <!-- Forgot password form -->

            <div class="field-group">
              <div class="field-label">
                <span>Username or Email</span>
                <small>Required</small>
              </div>

              <input type="text"
                     name="username_or_email"
                     class="field-input"
                     placeholder="Enter here..."
                     required> <!-- Input -->
            </div>

            <button type="submit" class="primary-btn">
              <span>Send Reset Link</span> <span>➡</span>
            </button>

          </form>
        <?php endif; ?>

        <!-- Back to login -->
        <div class="footer-links">
          <a href="login.php">⬅ Back to Login</a>
        </div>

      </div>
    </div>
  </div>

  <script>
    // Simple dark mode toggle
    const toggleBtn = document.getElementById('modeToggle');
    const modeIcon = document.getElementById('modeIcon');
    const modeText = document.getElementById('modeText');

    toggleBtn.addEventListener('click', () => {
      document.body.classList.toggle('dark-mode'); // Toggle body theme
      const dark = document.body.classList.contains('dark-mode'); // Mode state
      modeIcon.textContent = dark ? '☀️' : '🌙'; // Update icon
      modeText.textContent = dark ? 'Light' : 'Dark'; // Update label
    });
  </script>
</body>
</html>
