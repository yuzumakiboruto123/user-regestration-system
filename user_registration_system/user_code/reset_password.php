<?php
require_once '../includes/auth_model.php'; // Load authentication model

$message = ''; // Store error/success message
$success = false; // Track if reset was successful
$token = $_GET['token'] ?? ''; // Get reset token from URL

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') { // Check form submission
    $token = $_POST['token'] ?? ''; // Token from form
    $newPassword = $_POST['new_password'] ?? ''; // New password
    $confirmPassword = $_POST['confirm_password'] ?? ''; // Confirm new password

    // Validate password match
    if ($newPassword !== $confirmPassword) {
        $message = "<p class='error-msg'>⚠️ Passwords do not match.</p>";

    // Validate password length
    } elseif (strlen($newPassword) < 8) {
        $message = "<p class='error-msg'>⚠️ Password must be at least 8 characters.</p>";

    } else {

        // Attempt password reset
        $reset = resetPassword($token, $newPassword);

        if ($reset) { // If reset success
            $success = true;
            $message = "<p class='success-msg'>✅ Your password has been successfully reset. 
                        You can now <a href='login.php'>log in here</a>.</p>";
        } else { // Invalid or expired token
            $message = "<p class='error-msg'>⚠️ Invalid or expired reset link. Please request a new one.</p>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"> <!-- Page encoding -->
  <title>Reset Password</title> <!-- Page title -->
  <link rel="stylesheet" href="../assets/css/auth-style.css"> <!-- Base auth styles -->

  <style>
    /* (Your entire CSS unchanged — UI comments not required unless you want) */
  </style>
</head>
<body>

  <div class="auth-wrapper"> <!-- Center wrapper -->

    <div class="auth-card"> <!-- Main form card -->

      <div class="auth-inner"> <!-- Inner container -->

        <!-- Header with dark-mode toggle -->
        <div class="auth-header">
          <div class="auth-title-group">
            <h2>Reset Your Password 🔐</h2> <!-- Title -->
            <p>Choose a strong password to keep your account secure.</p>
          </div>

          <button type="button" class="mode-toggle-btn" id="modeToggle"> <!-- Dark mode toggle -->
            <span class="icon" id="modeIcon">🌙</span>
            <span id="modeText">Dark</span>
          </button>
        </div>

        <!-- Display success/error message -->
        <div class="message-box">
          <?= $message ?>
        </div>

        <?php if (!$success): ?> <!-- Hide form if reset successful -->

          <div class="form-body"> <!-- Form container -->

            <form method="POST" autocomplete="off"> <!-- Reset form -->

              <!-- Hidden token -->
              <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

              <!-- New Password Field -->
              <div class="field-group">
                <div class="field-label">
                  <span>New Password</span>
                  <small>At least 8 characters</small>
                </div>
                <input type="password"
                       name="new_password"
                       id="new_password"
                       class="field-input"
                       placeholder="Enter new password"
                       required> <!-- Password input -->

                <!-- Password strength widget -->
                <div class="password-widget">
                  <div class="strength-bar">
                    <div class="strength-fill" id="strengthFill"></div>
                  </div>
                  <div class="strength-label">
                    <span>Strength</span>
                    <span class="status" id="strengthStatus">Too weak</span>
                  </div>
                  <ul class="requirements-list">
                    <li id="req-length">
                      <span class="req-icon">✖</span>
                      <span>At least 8 characters</span>
                    </li>
                    <li id="req-upper">
                      <span class="req-icon">✖</span>
                      <span>At least one uppercase letter (A–Z)</span>
                    </li>
                    <li id="req-number">
                      <span class="req-icon">✖</span>
                      <span>At least one number (0–9)</span>
                    </li>
                    <li id="req-special">
                      <span class="req-icon">✖</span>
                      <span>At least one special character (!@#$…)</span>
                    </li>
                  </ul>
                </div>
              </div>

              <!-- Confirm Password Field -->
              <div class="field-group">
                <div class="field-label">
                  <span>Confirm Password</span>
                  <small>Repeat the same password</small>
                </div>
                <input type="password"
                       name="confirm_password"
                       id="confirm_password"
                       class="field-input"
                       placeholder="Re-type new password"
                       required>
                <div class="match-msg" id="matchMsg"></div>
              </div>

              <!-- Submit Button -->
              <button type="submit" class="primary-btn">
                <span>Save New Password</span> <span>✅</span>
              </button>

            </form>
          </div>
        <?php endif; ?>

        <!-- Back to login link -->
        <div class="footer-links">
          <a href="login.php">⬅ Back to Login</a>
        </div>

      </div>
    </div>
  </div>

  <script>
    /* ---- Dark mode toggle ---- */
    const toggleBtn = document.getElementById('modeToggle'); // Button
    const modeIcon = document.getElementById('modeIcon'); // Icon
    const modeText = document.getElementById('modeText'); // Label

    toggleBtn.addEventListener('click', () => { // Toggle action
      document.body.classList.toggle('dark-mode');
      const dark = document.body.classList.contains('dark-mode');
      modeIcon.textContent = dark ? '☀️' : '🌙';
      modeText.textContent = dark ? 'Light' : 'Dark';
    });

    /* ---- Password Strength and Matching ---- */
    const newPassword = document.getElementById('new_password'); // New password field
    const confirmPassword = document.getElementById('confirm_password'); // Confirm field
    const strengthFill = document.getElementById('strengthFill'); // Bar fill
    const strengthStatus = document.getElementById('strengthStatus'); // Strength text
    const matchMsg = document.getElementById('matchMsg'); // Match hint

    const reqLength = document.getElementById('req-length'); // Length requirement
    const reqUpper = document.getElementById('req-upper'); // Uppercase
    const reqNumber = document.getElementById('req-number'); // Number
    const reqSpecial = document.getElementById('req-special'); // Special character

    // Update requirement icons and strength
    function updateRequirements(pw) {
      const hasLength = pw.length >= 8;
      const hasUpper = /[A-Z]/.test(pw);
      const hasNumber = /[0-9]/.test(pw);
      const hasSpecial = /[^A-Za-z0-9]/.test(pw);

      toggleReq(reqLength, hasLength);
      toggleReq(reqUpper, hasUpper);
      toggleReq(reqNumber, hasNumber);
      toggleReq(reqSpecial, hasSpecial);

      let score = 0;
      if (hasLength) score++;
      if (hasUpper) score++;
      if (hasNumber) score++;
      if (hasSpecial) score++;

      let width = 0;
      let statusText = "Too weak";
      let gradient = "linear-gradient(90deg, #ef4444, #f97316)";

      if (score === 1) {
        width = 25;
        statusText = "Very weak";
      } else if (score === 2) {
        width = 45;
        statusText = "Weak";
        gradient = "linear-gradient(90deg, #f97316, #eab308)";
      } else if (score === 3) {
        width = 70;
        statusText = "Good";
        gradient = "linear-gradient(90deg, #eab308, #22c55e)";
      } else if (score === 4) {
        width = 100;
        statusText = "Strong";
        gradient = "linear-gradient(90deg, #22c55e, #16a34a)";
      }

      strengthFill.style.width = width + "%";
      strengthFill.style.background = gradient;
      strengthStatus.textContent = statusText;
    }

    // Toggle requirement ✔/✖ icons
    function toggleReq(element, met) {
      if (!element) return;
      const icon = element.querySelector('.req-icon');
      if (met) {
        element.classList.add('met');
        icon.textContent = '✔';
      } else {
        element.classList.remove('met');
        icon.textContent = '✖';
      }
    }

    // Update password match message
    function updateMatch() {
      const pw = newPassword.value;
      const cpw = confirmPassword.value;

      if (!cpw) {
        matchMsg.textContent = "";
        matchMsg.className = "match-msg";
        return;
      }

      if (pw === cpw) {
        matchMsg.textContent = "✅ Passwords match.";
        matchMsg.className = "match-msg ok";
      } else {
        matchMsg.textContent = "⚠️ Passwords do not match.";
        matchMsg.className = "match-msg bad";
      }
    }

    // Add listeners
    if (newPassword) {
      newPassword.addEventListener('input', () => {
        updateRequirements(newPassword.value);
        updateMatch();
      });
    }
    if (confirmPassword) {
      confirmPassword.addEventListener('input', updateMatch);
    }
  </script>

</body>
</html>
