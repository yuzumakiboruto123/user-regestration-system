<?php
require_once '../includes/validation.php'; // Load validation helpers

session_start(); // Start session
require_once '../includes/auth_check.php'; // Access control checker
require_once '../includes/user_model.php'; // User model functions
requireRole(['Admin']); // Allow only Admin to view this page

// Pagination setup
$perPage = 10; // Number of users per page
$page    = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Current page number
if ($page < 1) $page = 1; // Prevent invalid page values

// Filters and sorting
$search = $_GET['search'] ?? ""; // Search filter
$role   = $_GET['role']   ?? ""; // Role filter
$sort   = $_GET['sort']   ?? "UserID"; // Sorting column
$order  = $_GET['order']  ?? "ASC"; // Sorting direction

// Fetch paginated results
$pagination = getUsersPaginated($search, $role, $sort, $order, $page, $perPage); // Get users with filters

// Extract pagination values
$users      = $pagination['users']; // Current page user list
$totalUsers = $pagination['total']; // Total matching users
$totalPages = $pagination['totalPages']; // Total number of pages

// Toggle sorting order for clickable column headers
$toggleOrder = ($order === "ASC") ? "DESC" : "ASC"; // Flip sort order
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"> <!-- UTF-8 encoding -->
<title>User Directory</title> <!-- Page title -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"> <!-- Bootstrap -->

<style>
/* Page background */
body {
  background: linear-gradient(135deg, #212e68ff 0%, #9e69d3ff 100%);
  background-attachment: fixed;
  padding: 40px;
  min-height: 100vh;
  font-family: "Poppins", sans-serif;
}

/* Main container card */
.container-box {
  background: linear-gradient(135deg, #6c8fd6ff, #4b6cb7);
  border-radius: 15px;
  padding: 30px;
  color: white;
  box-shadow: 0 10px 40px rgba(0,0,0,0.4);
}

/* Heading */
h1 {
  color: #f1c40f;
  font-weight: 600;
  margin: 0;
}

/* Table style */
.table {
  background: rgba(52,75,120,0.8);
  color: #fff;
  border-radius: 10px;
  overflow: hidden;
}
.table thead {
  background: rgba(0,0,0,0.35);
}
.table-bordered > :not(caption) > * {
  border-color: rgba(255,255,255,0.25);
}

/* Status badges */
.badge-success {
  background: #2ecc71;
}
.badge-secondary {
  background: #e74c3c;
}

/* Buttons styling */
.btn-add {
  background: #2ecc71;
  border: none;
}
.btn-add:hover {
  background: #27ae60;
}
.btn-back {
  border: 2px solid #f1c40f;
  color: #f1c40f;
}
.btn-back:hover {
  background: #f1c40f;
  color: #000;
}
.btn-logout {
  background:#ef4444;
  color:#ffffff;
}

/* Pagination styles */
.pagination .page-link {
  background: rgba(255,255,255,0.2);
  color: white;
  border: none;
}
.pagination .page-link:hover {
  background: rgba(255,255,255,0.4);
}

/* Small width inputs */
.filter-input {
  max-width: 180px;
}
</style>
</head>
<body>

<div class="container-box"> <!-- Main content box -->

  <!-- Top heading and buttons -->
  <div class="d-flex justify-content-between align-items-center mb-4">
      <h1>USER DIRECTORY</h1> <!-- Title -->

      <div>
        <a href="add_user.php" class="btn btn-add me-2">+ Add User</a> <!-- Add user -->
        <a href="dashboard.php" class="btn btn-back">← Back to Dashboard</a> <!-- Back -->
        <a href="logout.php" class="btn btn-logout">Logout</a> <!-- Logout -->
      </div>
  </div>

  <!-- Filters -->
  <form method="GET" class="row g-3 mb-4"> <!-- Filter form -->

      <!-- Username search -->
      <div class="col-md-3">
        <input type="text"
               class="form-control filter-input"
               name="search"
               placeholder="Search username..."
               value="<?= htmlspecialchars($search) ?>"> <!-- Show current value -->
      </div>

      <!-- Role filter -->
      <div class="col-md-3">
        <select name="role" class="form-select filter-input">
            <option value="">All Roles</option>
            <option value="Admin"   <?= $role=="Admin"   ? "selected" : "" ?>>Admin</option>
            <option value="Staff"   <?= $role=="Staff"   ? "selected" : "" ?>>Staff</option>
            <option value="Student" <?= $role=="Student" ? "selected" : "" ?>>Student</option>
        </select>
      </div>

      <!-- Sort order -->
      <div class="col-md-2">
         <select name="order" class="form-select filter-input">
            <option value="ASC"  <?= $order=="ASC"  ? "selected" : "" ?>>Ascending</option>
            <option value="DESC" <?= $order=="DESC" ? "selected" : "" ?>>Descending</option>
         </select>
      </div>

      <!-- Apply / Clear -->
      <div class="col-md-4">
        <button class="btn btn-primary">Apply</button> <!-- Apply filters -->
        <a href="manage_user.php" class="btn btn-secondary">Clear</a> <!-- Clear filters -->
      </div>
  </form>

  <!-- User table -->
  <div class="table-responsive"> <!-- Responsive wrapper -->
    <table class="table table-bordered text-center align-middle"> <!-- User table -->
        <thead>
            <tr>
                <!-- Sort by UserID -->
                <th>
                  <a href="?sort=UserID&order=<?= $toggleOrder ?>&search=<?= urlencode($search) ?>&role=<?= urlencode($role) ?>">
                    ID
                  </a>
                </th>

                <!-- Sort by Username -->
                <th>
                  <a href="?sort=Username&order=<?= $toggleOrder ?>&search=<?= urlencode($search) ?>&role=<?= urlencode($role) ?>">
                    Username
                  </a>
                </th>

                <!-- Email (static) -->
                <th>Email</th>

                <!-- Sort by Role -->
                <th>
                  <a href="?sort=Role&order=<?= $toggleOrder ?>&search=<?= urlencode($search) ?>&role=<?= urlencode($role) ?>">
                    Role
                  </a>
                </th>

                <th>Login Count</th> <!-- Login number -->
                <th>Last Login</th> <!-- Last login date -->
                <th>Status</th> <!-- Active/Inactive -->
                <th width="180">Actions</th> <!-- Edit/Delete -->
            </tr>
        </thead>
        <tbody>

        <?php if (empty($users)): ?>
            <tr><td colspan="8">No users found</td></tr> <!-- No results -->
        <?php else: ?>

            <!-- Loop users -->
            <?php foreach ($users as $u): ?>
            <tr>
                <td><?= (int)$u['UserID'] ?></td> <!-- User ID -->
                <td><?= htmlspecialchars($u['Username']) ?></td> <!-- Username -->
                <td><?= htmlspecialchars($u['Email']) ?></td> <!-- Email -->
                <td><?= htmlspecialchars($u['Role']) ?></td> <!-- Role -->
                <td><?= (int)$u['LoginCount'] ?></td> <!-- Login count -->
                <td><?= htmlspecialchars($u['LastLogin']) ?></td> <!-- Last login -->

                <!-- Status badge -->
                <td>
                    <?php if ($u['IsActive']): ?>
                        <span class="badge badge-success">Active</span> <!-- Active status -->
                    <?php else: ?>
                        <span class="badge badge-secondary">Inactive</span> <!-- Inactive status -->
                    <?php endif; ?>
                </td>

                <!-- Edit/Delete buttons -->
                <td>
                    <a href="edit_user.php?id=<?= (int)$u['UserID'] ?>" class="btn btn-warning btn-sm">Edit</a>
                    <a href="delete_user.php?id=<?= (int)$u['UserID'] ?>" class="btn btn-danger btn-sm">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>

        <?php endif; ?>

        </tbody>
    </table>
  </div>

  <!-- Pagination -->
  <nav>
    <ul class="pagination justify-content-center mt-3">

        <!-- Previous page -->
        <?php if ($page > 1): ?>
            <li class="page-item">
               <a class="page-link"
                  href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>&role=<?= urlencode($role) ?>&sort=<?= urlencode($sort) ?>&order=<?= urlencode($order) ?>">
                 Previous
               </a>
            </li>
        <?php endif; ?>

        <!-- Page numbers -->
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <li class="page-item <?= ($page == $i ? 'active' : '') ?>">
               <a class="page-link"
                  href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&role=<?= urlencode($role) ?>&sort=<?= urlencode($sort) ?>&order=<?= urlencode($order) ?>">
                 <?= $i ?>
               </a>
            </li>
        <?php endfor; ?>

        <!-- Next page -->
        <?php if ($page < $totalPages): ?>
            <li class="page-item">
               <a class="page-link"
                  href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>&role=<?= urlencode($role) ?>&sort=<?= urlencode($sort) ?>&order=<?= urlencode($order) ?>">
                 Next
               </a>
            </li>
        <?php endif; ?>

    </ul>
  </nav>

  <!-- Total users footer -->
  <div class="text-end mt-3" style="color:#f1c40f; font-weight:bold;">
      Total Users: <?= (int)$totalUsers ?> <!-- Total count -->
  </div>

</div>

</body>
</html>
