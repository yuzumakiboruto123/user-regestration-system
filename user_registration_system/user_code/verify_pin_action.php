<?php
session_start(); // Start session for admin authentication

// Load required helpers (auth check, DB, models, validation, audit)
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/user_model.php';
require_once __DIR__ . '/../includes/validation.php';
require_once __DIR__ . '/../includes/audit.php';


// Only Admin users can verify PIN
requireRole(['Admin']);

$pdo = getDB(); // Database connection
$adminID = $_SESSION['userID']; // Logged-in admin ID
$message = ""; // Message placeholder for feedback

// --------------------------------------------------------------
// FETCH LOGGED-IN ADMIN PIN HASH
// --------------------------------------------------------------
$stmt = $pdo->prepare("SELECT AdminPINHash FROM admin WHERE UserID = ?");
$stmt->execute([$adminID]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

// Stop if admin record missing
if (!$admin) {
    die("Admin record not found for logged-in user.");
}

// --------------------------------------------------------------
// WHEN FORM SUBMITTED
// --------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') { // Handle form submit

    // PIN entered by acting admin
    $pin = trim($_POST['admin_pin'] ?? '');

    // Determine requested action (create/update/delete)
    $action = $_POST['action'] ?? '';

    // Fields for creating a user
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $role     = $_POST['role'] ?? '';
    $extra    = $_POST['extra'] ?? [];

    // Fields for updating/deleting a user
    $targetUser  = $_POST['target_user'] ?? null;
    $isActive    = $_POST['is_active'] ?? 0;
    $newAdminPin = $_POST['new_admin_pin'] ?? null;

    // --------------------------------------------------------------
    // VALIDATE ADMIN PIN FORMAT
    // --------------------------------------------------------------
    if (!preg_match('/^[0-9]{6}$/', $pin)) { // Must be 6 digits
        $message = "<div class='alert alert-danger'>❌ PIN must be exactly 6 digits.</div>";
    }
    elseif (!password_verify($pin, $admin['AdminPINHash'])) { // PIN incorrect
        $message = "<div class='alert alert-danger'>❌ Incorrect Admin PIN.</div>";
        logAction($adminID, "PIN Failed", "Admin", null, "Incorrect PIN at verify_pin_action");
    }
    else {

        // --------------------------------------------------------------
        // PIN VERIFIED → EXECUTE REQUESTED ACTION
        // --------------------------------------------------------------

        if ($action === "create") { // Handle create user request

            // createUser() internally handles admin table insert for admin role
            $result = createUser($username, $password, $role, $extra);

            if ($result === "exists") {
                $message = "<div class='alert alert-danger'>⚠ Username already exists!</div>";
            } elseif ($result) {
                $message = "<div class='alert alert-success'>✅ User created successfully!</div>";
            } else {
                $message = "<div class='alert alert-danger'>❌ Failed to create user.</div>";
            }
        }

        elseif ($action === "update") { // Handle user update

            // Update user table (username, password, role, active status)
            $ok = updateUser($targetUser, $username, $password, $role, $isActive);

            // ============================
            // STAFF UPDATE
            // ============================
            if ($role === "Staff" && isset($_POST['extra'])) {
                updateStaff($targetUser, $_POST['extra']);
            }

            // ============================
            // STUDENT UPDATE
            // ============================
            if ($role === "Student" && isset($_POST['extra'])) {
                updateStudent($targetUser, $_POST['extra']);
            }

            // ============================
            // ADMIN UPDATE
            // ============================
            if ($role === "Admin" && !empty($newAdminPin)) {

                if (preg_match('/^[0-9]{6}$/', $newAdminPin)) {

                    // Hash new PIN
                    $newPinHash = password_hash($newAdminPin, PASSWORD_DEFAULT);

                    // Update admin table
                    $pdo->prepare("
                        UPDATE admin 
                        SET AdminPINHash = ?, FailedPinAttempts = 0,
                            PinLastChanged = NOW(), PinLockUntil = NULL
                        WHERE UserID = ?
                    ")->execute([$newPinHash, $targetUser]);

                    $message .= "<div class='alert alert-info'>🔐 Admin PIN updated successfully.</div>";

                } else {
                    $message .= "<div class='alert alert-warning'>⚠ Invalid new PIN, not updated.</div>";
                }
            }

            // ============================
            // SUCCESS / FAIL MESSAGE
            // ============================
            if ($ok) {
                $message = "<div class='alert alert-success'>✅ User updated successfully!</div>";
            } else {
                $message = "<div class='alert alert-danger'>❌ Failed to update user.</div>";
            }
        }

        elseif ($action === "delete") { // Handle user deletion

            $ok = deleteUser($targetUser);

            if ($ok) {
                $message = "<div class='alert alert-success'>🗑 User deleted successfully!</div>";
            } else {
                $message = "<div class='alert alert-danger'>❌ Failed to delete user.</div>";
            }
        }

        // Log action taken by admin after PIN verification
        logAction($adminID, "Admin Action: $action", "Admin", $targetUser ?: null, "Executed via verify_pin_action.php");
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Admin PIN Verification</title> <!-- Page title -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"> <!-- Bootstrap -->
</head>

<body style="background:#243B55; padding:40px; font-family:Poppins,sans-serif;"> <!-- Page styling -->

<div class="card p-4" style="max-width:450px; margin:auto;"> <!-- Centered card -->

    <h3 class="text-center mb-3">Enter Admin PIN</h3> <!-- Title -->

    <?= $message ?> <!-- Display feedback -->

    <form method="POST"> <!-- Form for PIN entry -->

        <!-- Forward all previous POST fields (hidden) -->
        <?php foreach ($_POST as $k => $v): ?>
            <?php 
            if (is_array($v)) { // Handle nested arrays
                foreach ($v as $k2 => $v2) {
                    echo "<input type='hidden' name='{$k}[{$k2}]' value='".htmlspecialchars($v2)."'>";
                }
            } else { // Handle normal values
                echo "<input type='hidden' name='".htmlspecialchars($k)."' value='".htmlspecialchars($v)."'>";
            }
            ?>
        <?php endforeach; ?>

        <label>Admin PIN</label> <!-- PIN input label -->
        <input type="password" maxlength="6" name="admin_pin" class="form-control" placeholder="******" required> <!-- PIN field -->

        <button class="btn btn-warning w-100 mt-3">Verify PIN</button> <!-- Submit button -->
    </form>

    <a href="manage_user.php" class="btn btn-secondary w-100 mt-3">← Back</a> <!-- Back button -->

</div>

</body>
</html>
