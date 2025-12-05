<?php
// =====================================================================
// STAFF DELETE PAGE — ADMIN-ONLY
// This page displays all staff information in disabled inputs and
// asks the admin to confirm permanent deletion.
// It uses StaffModel and loads the staff by ID.
// =====================================================================

// Load authentication + role enforcement
require_once '../includes/auth_check.php';
require_once '../includes/staff_model.php';

// Ensure only Admin can access this page
requireRole(['Admin']);

// Create StaffModel instance
$staffModel = new StaffModel();

// Initialize error + success message arrays
$errors = [];
$success = "";

// =====================================================================
// VALIDATE STAFF ID FROM URL (?id=###)
// =====================================================================
$staffId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Invalid or missing ID → redirect safely
if ($staffId <= 0) {
    header("Location: staff_list.php");
    exit();
}

// =====================================================================
// FETCH STAFF INFORMATION
// If no matching staff is found → redirect to list
// =====================================================================
$staff = $staffModel->getStaffById($staffId);

if (!$staff) {
    $_SESSION['error_message'] = "Staff member not found!";
    header("Location: staff_list.php");
    exit();
}

// =====================================================================
// HANDLE ACTUAL DELETE ACTION (POST REQUEST ONLY)
// =====================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get database instance
        $pdo = getDB();

        // Delete staff record from Staff table
        $stmt = $pdo->prepare("DELETE FROM Staff WHERE StaffID = ?");
        $stmt->execute([$staffId]);

        // Success message — page stays here
        $success = "✅ Staff member deleted permanently!";

    } catch (PDOException $e) {
        // Capture DB errors
        $errors[] = "Database error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Delete Staff Member</title>

<!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
/* =====================================================================
   PAGE BACKGROUND — MATCHES YOUR UI (ORANGE/PINK GRADIENT)
   ===================================================================== */
body {
    background: linear-gradient(135deg, #ff6a00 0%, #ee0979 100%);
    background-attachment: fixed;
    min-height: 100vh;
    padding: 20px;
}

/* =====================================================================
   MAIN WHITE PANEL STYLING — SAME AS YOUR UI SCREENSHOT
   ===================================================================== */
.container {
    background: #fff;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    padding: 30px;
    max-width: 900px;
    margin: 40px auto;
}

/* Back button */
.btn-secondary {
    background-color: #555;
    border: none;
}
.btn-secondary:hover {
    background-color: #333;
}

/* Confirm delete button */
.btn-danger {
    background-color: #e74c3c;
    border: none;
}
.btn-danger:hover {
    background-color: #c0392b;
}

/* Readonly field styling */
input[readonly] {
    background: #f3f3f3 !important;
    color: #555 !important;
}
</style>

</head>
<body>

<div class="container">

    <!-- ==========================================================
         HEADER AREA — PAGE TITLE + BACK BUTTON (TOP RIGHT)
         ========================================================== -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="text-dark">Delete Staff Member</h1>
        <a href="staff_list.php" class="btn btn-secondary">← Back to List</a>
    </div>

    <!-- ==========================================================
         SUCCESS MESSAGE (GREEN BOX) — PAGE STAYS HERE
         ========================================================== -->
    <?php if ($success): ?>
        <div class="alert alert-success text-center">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <!-- ==========================================================
         ERROR MESSAGES (RED BOX)
         ========================================================== -->
    <?php if ($errors): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $e): ?>
                <div><?= htmlspecialchars($e) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- ==========================================================
         DELETE CONFIRMATION FORM — READONLY STAFF INFO
         ========================================================== -->
    <form method="POST">

        <!-- ROW 1: First Name + Last Name -->
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label text-dark">First Name</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($staff['FirstName']); ?>" readonly>
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label text-dark">Last Name</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($staff['LastName']); ?>" readonly>
            </div>
        </div>

        <!-- ROW 2: Email + CourseName (DEPARTMENT FIX APPLIED) -->
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label text-dark">Email</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($staff['Email']); ?>" readonly>
            </div>

            <div class="col-md-6 mb-3">
                <!-- FIX: Replaced Department → CourseName -->
                <label class="form-label text-dark">Course</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($staff['CourseName']); ?>" readonly>
            </div>
        </div>

        <!-- ROW 3: Hire Date + Salary + Status -->
        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label text-dark">Hire Date</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($staff['HireDate']); ?>" readonly>
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label text-dark">Salary (DKK)</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($staff['Salary']); ?>" readonly>
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label text-dark">Status</label>
                <input type="text" class="form-control" value="<?= $staff['IsActive'] ? 'Active' : 'Inactive'; ?>" readonly>
            </div>
        </div>

        <!-- WARNING BOX -->
        <div class="alert alert-danger mt-4 text-center">
            ⚠️ Are you sure you want to <strong>permanently delete</strong> this staff member?
        </div>

        <!-- ACTION BUTTONS -->
        <div class="d-flex justify-content-between">
            <a href="staff_list.php" class="btn btn-outline-secondary">Cancel</a>
            <button type="submit" class="btn btn-danger">Confirm Delete</button>
        </div>

    </form>
</div>

</body>
</html>
