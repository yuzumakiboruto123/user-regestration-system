<?php
session_start(); // Start session for admin access

require_once '../includes/auth_check.php'; // Authentication check
require_once '../includes/auth_model.php'; // Load user activation functions

requireRole(['Admin']); // Only Admin can access this page

$message = ''; // Message placeholder for alerts

// If form submitted (approve or deactivate)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Approve user action
    if (isset($_POST['approve'])) {
        $id = intval($_POST['approve']); // Get approved user ID
        activateUser($id); // Activate user in DB
        $message = "<div class='alert alert-success'>✅ User ID $id approved successfully.</div>";
    }

    // Deactivate user action
    elseif (isset($_POST['deactivate'])) {
        $id = intval($_POST['deactivate']); // Get deactivated user ID
        deactivateUser($id); // Deactivate user in DB
        $message = "<div class='alert alert-danger'>⚠️ User ID $id deactivated.</div>";
    }
}

// Fetch all pending users for approval list
$pendingUsers = getPendingUsers();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Approve Users</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
/* Outer gradient background */
body {
    background: linear-gradient(135deg, #212e68 0%, #9e69d3 100%);
    background-attachment: fixed;
    min-height: 100vh;
    padding: 40px;
    font-family: "Poppins", sans-serif;
}

/* Glass-style container box */
.container-box {
    background: linear-gradient(135deg, #6c8fd6, #4b6cb7);
    border-radius: 15px;
    padding: 30px;
    color: white;
    box-shadow: 0 10px 40px rgba(0,0,0,0.4);
}

/* Page title styling */
h1 {
    color: #f1c40f;
    font-weight: 600;
}

/* Table styling */
.table {
    background: rgba(52, 75, 120, 0.85);
    color: white;
    border-radius: 10px;
    overflow: hidden;
}

/* Table header styling */
.table thead {
    background: rgba(0,0,0,0.4);
    color: #fff;
}

/* Border styling */
.table-bordered > :not(caption) > * {
    border-color: rgba(255,255,255,0.2);
}

a.btn-sm {
    margin: 2px;
}

/* Approve button style */
.btn-approve {
    background-color: #2ecc71;
    color: white;
}
.btn-approve:hover {
    background-color: #27ae60;
}

/* Deactivate button style */
.btn-deactivate {
    background-color: #e74c3c;
    color: white;
}
.btn-deactivate:hover {
    background-color: #c0392b;
}

/* Back button */
.back-btn {
    margin-top: 20px;
    display: inline-block;
}
</style>
</head>

<body>

<div class="container-box mx-auto" style="max-width: 900px;">

    <!-- Header with back button -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Pending User Approvals</h1>
        <a href="dashboard.php" class="btn btn-outline-warning">← Back to Dashboard</a>
    </div>

    <!-- Display status messages -->
    <?= $message ?>

    <!-- Show pending users list -->
    <?php if (count($pendingUsers) > 0): ?>
    <div class="table-responsive">
        <table class="table table-bordered text-center align-middle">
            <thead>
                <tr>
                    <th>UserID</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Created Date</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($pendingUsers as $user): ?>
                <tr>
                    <td><?= htmlspecialchars($user['UserID']); ?></td>
                    <td><?= htmlspecialchars($user['Username']); ?></td>
                    <td><?= htmlspecialchars($user['Role']); ?></td>
                    <td><?= htmlspecialchars($user['CreatedDate']); ?></td>

                    <td>
                        <!-- Approve button -->
                        <form method="POST" style="display:inline;">
                            <button name="approve" value="<?= $user['UserID']; ?>" class="btn btn-approve btn-sm">Approve</button>
                        </form>

                        <!-- Deactivate button -->
                        <form method="POST" style="display:inline;">
                            <button name="deactivate" value="<?= $user['UserID']; ?>" class="btn btn-deactivate btn-sm">Deactivate</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>

        </table>
    </div>

    <?php else: ?>
        <!-- No users message -->
        <p class="text-white mt-3">No pending users to approve.</p>
    <?php endif; ?>

</div>

</body>
</html>
