<?php
require_once '../includes/auth_check.php';
require_once '../includes/staff_model.php';

$model = new StaffModel();

// --- FIXED: previously getCourses() → now getAllCourseNames() ---
$courses = $model->getAllCourseNames();

// filters (updated to use 'course')
$filters = [
    'course'    => $_GET['course'] ?? '',
    'is_active' => $_GET['is_active'] ?? '',
    'search'    => $_GET['search'] ?? ''
];

$staffMembers = $model->getStaff($filters);
$isAdmin = ($_SESSION['role'] === 'Admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Staff Management</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
/* Your original UI – unchanged */
body {
  background: linear-gradient(135deg, #212e68ff 0%, #9e69d3ff 100%);
  background-attachment: fixed;
  min-height: 100vh;
  margin: 0;
  padding: 40px;
  font-family: "Poppins", sans-serif;
}
.container {
  background: linear-gradient(135deg, #6c8fd6ff, #4b6cb7);
  border-radius: 15px;
  padding: 30px;
  box-shadow: 0 10px 40px rgba(0,0,0,0.4);
  color: white;
}
.table {
  background: rgba(52, 75, 120, 0.8);
  color: #fff;
  border-radius: 10px;
}
.table thead { background-color: rgba(0,0,0,0.4); }
.status-badge.active { background:#2ecc71; }
.status-badge.inactive { background:#e74c3c; }
.btn-logout {
  background:#ef4444;
  color:#ffffff;}
</style>
</head>

<body>
<div class="container">

  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Staff Directory</h1>
    <div>
      <?php if ($isAdmin): ?>
        <a href="staff_create.php" class="btn btn-success">Add New Staff</a>
      <?php endif; ?>
      <a href="dashboard.php" class="btn btn-outline-warning me-2">← Back to Dashboard</a>
              <a href="logout.php" class="btn btn-logout">Logout</a>
    </div>
  </div>

  <!-- FILTER SECTION -->
  <form method="GET" class="row g-3 mb-4">

    <div class="col-md-3">
      <input type="text" name="search" class="form-control"
             placeholder="Search by name/email"
             value="<?= htmlspecialchars($filters['search']); ?>">
    </div>

    <!-- FIXED: course dropdown -->
    <div class="col-md-3">
      <select name="course" class="form-select">
        <option value="">All Courses</option>

        <?php foreach ($courses as $c): ?>
          <option value="<?= htmlspecialchars($c); ?>"
            <?= $filters['course'] === $c ? 'selected' : ''; ?>>
            <?= htmlspecialchars($c); ?>
          </option>
        <?php endforeach; ?>

      </select>
    </div>

    <div class="col-md-2">
      <select name="is_active" class="form-select">
        <option value="">All Status</option>
        <option value="1" <?= $filters['is_active'] === '1' ? 'selected' : ''; ?>>Active</option>
        <option value="0" <?= $filters['is_active'] === '0' ? 'selected' : ''; ?>>Inactive</option>
      </select>
    </div>

    <div class="col-md-4">
      <button type="submit" class="btn btn-primary">Apply</button>
      <a href="staff_list.php" class="btn btn-secondary">Clear</a>
    </div>
 
  </form>
  

  <!-- STAFF TABLE -->
  <div class="table-responsive">
    <table class="table table-bordered align-middle text-center">
      <thead>
        <tr>
          <th>ID</th>
          <th>Name</th>
          <th>Email</th>
          <th>Course</th>
          <th>Hire Date</th>
          <?php if ($isAdmin): ?><th>Salary</th><?php endif; ?>
          <th>Status</th>
          <?php if ($isAdmin): ?><th>Actions</th><?php endif; ?>
        </tr>
      </thead>

      <tbody>
        <?php if (empty($staffMembers)): ?>
          <tr>
            <td colspan="<?= $isAdmin ? 8 : 6; ?>">No staff found</td>
          </tr>
        <?php else: ?>

        <?php foreach ($staffMembers as $staff): ?>
        <tr>
          <td><?= $staff['StaffID']; ?></td>
          <td><?= htmlspecialchars($staff['FirstName'] . ' ' . $staff['LastName']); ?></td>
          <td><?= htmlspecialchars($staff['Email']); ?></td>
          <td><?= htmlspecialchars($staff['CourseName']); ?></td>
          <td><?= htmlspecialchars(date('M d, Y', strtotime($staff['HireDate']))); ?></td>

          <?php if ($isAdmin): ?>
            <td><?= $staff['Salary'] ? number_format($staff['Salary'],2)." DKK" : 'N/A'; ?></td>
          <?php endif; ?>

          <td>
            <span class="status-badge <?= $staff['IsActive'] ? 'active' : 'inactive'; ?>">
              <?= $staff['IsActive'] ? 'Active' : 'Inactive'; ?>
            </span>
          </td>

          <?php if ($isAdmin): ?>
            <td>
              <a href="staff_edit.php?id=<?= $staff['StaffID']; ?>" class="btn btn-warning btn-sm">Edit</a>
              <a href="staff_delete.php?id=<?= $staff['StaffID']; ?>" class="btn btn-danger btn-sm">Delete</a>
            </td>
          <?php endif; ?>

        </tr>
        <?php endforeach; ?>

        <?php endif; ?>
      </tbody>

    </table>
  </div>

  <div class="summary mt-3">
    Total Staff Members: <?= count($staffMembers); ?>
  </div>

</div>
</body>
</html>
