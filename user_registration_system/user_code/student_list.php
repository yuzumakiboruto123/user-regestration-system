<?php
// ======================================================================
//  student_list.php – Student Management (Colorful Theme: Purple + Blue)
//  Fully rewritten with:
//     ✔ Same layout structure as list_enrollment.php
//     ✔ Light-purple & dark-blue gradients
//     ✔ White card container
//     ✔ Blue/Green/Red buttons
//     ✔ Clean table
//     ✔ EXTREMELY DETAILED INLINE COMMENTS (top–bottom)
// ======================================================================

// -----------------------------
// 1) Include authentication + DB
// -----------------------------
require_once '../includes/auth_check.php';     // ensures logged-in user
require_once '../includes/db.php';             // PDO connection helper

// restrict access to admin & staff only
requireRole(['Admin','Staff']);

// open DB connection
$pdo = getDB();

// -----------------------------
// 2) Start session for flash messages
// -----------------------------
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// store logged-in user info for header UI
$username = $_SESSION['username'] ?? 'User';
$role     = $_SESSION['role'] ?? '';


// ======================================================================
// 3) READ FILTER INPUTS
// ======================================================================

// search keyword for student name / email
$keyword = trim($_GET['q'] ?? '');

// status filter: active or inactive
$statusFilter = trim($_GET['status'] ?? '');

// recent toggle – show students created in last X days
$showRecent = isset($_GET['recent']) && $_GET['recent'] === '1';

// define recency window (days)
$recentDays = 30;


// ======================================================================
// 4) PAGINATION CONFIG
// ======================================================================
$perPage = 8;                                         // students listed per page
$page    = max(1, (int)($_GET['page'] ?? 1));         // current page
$offset  = ($page - 1) * $perPage;                    // starting row for LIMIT/OFFSET


// ======================================================================
// 5) BUILD SQL WHERE CLAUSE DYNAMICALLY
// ======================================================================
$where  = " WHERE 1=1 ";      // base condition so AND can append easily
$params = [];                 // PDO bind parameters

// keyword search (first name / last name / email)
if ($keyword !== '') {
    $where .= " AND (
        s.FirstName LIKE :kw
        OR s.LastName LIKE :kw
        OR s.Email    LIKE :kw
    )";
    $params[':kw'] = "%$keyword%";
}

// status filter mapped to IsActive (1/0)
if ($statusFilter === 'active') {
    $where .= " AND s.IsActive = 1 ";
} elseif ($statusFilter === 'inactive') {
    $where .= " AND s.IsActive = 0 ";
}

// recent students filter (using CreatedDate in user table)
if ($showRecent) {
    $where .= " AND u.CreatedDate >= DATE_SUB(CURDATE(), INTERVAL :days DAY) ";
    $params[':days'] = $recentDays;
}


// ======================================================================
// 6) COUNT TOTAL ROWS (for pagination)
// ======================================================================
$countSql = "
    SELECT COUNT(*)
    FROM student s
    JOIN user u ON s.UserID = u.UserID
    $where
";
$stmt = $pdo->prepare($countSql);
$stmt->execute($params);
$totalRows  = (int)$stmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows / $perPage));

// ensure page is not out of bounds
if ($page > $totalPages) {
    $page   = $totalPages;
    $offset = ($page - 1) * $perPage;
}


// ======================================================================
// 7) FETCH PAGINATED STUDENT RECORDS
// ======================================================================
$sql = "
    SELECT
        s.StudentID,
        s.UserID,
        s.FirstName,
        s.LastName,
        s.DateOfBirth,
        s.Email,
        s.Age,
        s.GPA,
        s.IsActive,
        u.CreatedDate
    FROM student s
    JOIN user u ON s.UserID = u.UserID
    $where
    ORDER BY u.CreatedDate DESC, s.StudentID DESC
    LIMIT :limit OFFSET :offset
";

$stmt = $pdo->prepare($sql);

// bind filter params
foreach ($params as $key => $val) {
    if ($key === ':days') {
        $stmt->bindValue($key, (int)$val, PDO::PARAM_INT);
    } else {
        $stmt->bindValue($key, $val);
    }
}

// bind pagination params
$stmt->bindValue(':limit',  (int)$perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', (int)$offset,  PDO::PARAM_INT);

// execute + fetch results
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);


// ======================================================================
// 8) SUPPORT FUNCTIONS (status badges, pagination links)
// ======================================================================

// create colored status badge
function student_status_badge(int $active): string {
    // active = green, inactive = red
    $label = $active ? "Active" : "Inactive";
    $color = $active ? "#22c55e" : "#ef4444";
    return "<span style='
        padding:5px 14px;
        border-radius:999px;
        border:1px solid {$color};
        color:{$color};
        background-color:{$color}15;
        font-size:13px;
    '>$label</span>";
}

// build pagination link with preserved filters
function student_page_link(int $page, string $keyword, string $status, bool $recent): string {
    $q = ['page'=>$page];
    if ($keyword !== '') $q['q'] = $keyword;
    if ($status  !== '') $q['status'] = $status;
    if ($recent)         $q['recent'] = '1';
    return 'student_list.php?' . http_build_query($q);
}


// ======================================================================
// 9) READ AND CLEAR FLASH MESSAGES
// ======================================================================
$success = $_SESSION['success_message'] ?? '';
$error   = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Student Management</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<!-- =====================================================================
     10) PAGE STYLING (B2: purple/blue gradient + white card + colorful buttons)
     ===================================================================== -->
<style>
/* entire page background */
body {
  margin:0;
  padding:30px;
  background: linear-gradient(135deg, #4c1d95, #1e3a8a); /* purple ➜ dark blue */
  font-family:system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
  color:#111;
}

/* core container */
.page {
  max-width:1250px;
  margin:0 auto;
  background:#ffffff;
  border-radius:20px;
  padding:35px 40px 30px;
  box-shadow:0 25px 50px rgba(0,0,0,0.25);
}

/* -------- HEADER BLOCK -------- */
.header-top {
  display:flex;
  justify-content:space-between;
  align-items:flex-start;
  margin-bottom:25px;
}
.title-block h1 {
  margin:0;
  font-size:36px;
  font-weight:800;
  color:#1e3a8a; /* dark blue */
}
.title-block p {
  margin:6px 0 0;
  color:#6b7280;
  font-size:15px;
}

/* user info & logout */
.user-block {
  text-align:right;
  font-size:14px;
}
.user-block strong {
  display:block;
  font-size:16px;
  color:#4c1d95; /* purple */
}
.user-role {
  color:#6b7280;
  font-size:13px;
  margin-bottom:8px;
}

/* buttons */
.btn {
  display:inline-block;
  font-size:14px;
  padding:8px 16px;
  border-radius:999px;
  text-decoration:none;
  font-weight:600;
}
.btn-dashboard {
  background:#e0e7ff;  /* light blue */
  color:#1e3a8a;
}
.btn-logout {
  background:#ef4444;
  color:#ffffff;
}

/* flash messages */
.flash {
  padding:10px 14px;
  border-radius:10px;
  font-size:14px;
  margin-bottom:14px;
}
.flash-success {
  background:#dcfce7;
  border:1px solid #15803d;
  color:#166534;
}
.flash-error {
  background:#fee2e2;
  border:1px solid #b91c1c;
  color:#b91c1c;
}

/* pill filter buttons */
.pill-row {
  display:flex;
  gap:12px;
  margin-bottom:15px;
}
.pill {
  padding:6px 16px;
  border-radius:999px;
  border:1px solid #d1d5db;
  font-size:13px;
  background:#f3f4f6;
  text-decoration:none;
  color:#1e3a8a;
}
.pill.active {
  background:#4c1d95;
  color:#ffffff;
  border-color:#4c1d95;
}

/* search + filter row */
.toolbar {
  display:grid;
  grid-template-columns: minmax(0, 3fr) minmax(0, 2fr) auto;
  gap:10px;
  align-items:center;
  margin-bottom:20px;
}

.search-box {
  display:flex;
  gap:8px;
}
.input {
  flex:1;
  padding:10px 12px;
  border-radius:12px;
  border:1px solid #d1d5db;
  font-size:14px;
}

.btn-blue {
  background:#2563eb;
  color:#ffffff;
}
.btn-gray {
  background:#e5e7eb;
  color:#111827;
}

.filter-box {
  display:flex;
  gap:8px;
}

.select {
  padding:10px 12px;
  border-radius:12px;
  border:1px solid #d1d5db;
  font-size:14px;
}

.btn-add {
  background:#4c1d95;
  color:#ffffff;
}

/* ---------- TABLE ---------- */
table {
  width:100%;
  border-collapse:collapse;
  font-size:14px;
  margin-top:10px;
}
thead {
  background:#ede9fe; /* light purple */
}
th, td {
  padding:12px 10px;
  text-align:left;
}
th {
  color:#4c1d95;
  font-weight:700;
}
tbody tr:nth-child(even) {
  background:#f3f4f6; /* light grey */
}
tbody tr:hover td {
  background:#e0e7ff; /* hover light blue */
}

/* action buttons */
.actions {
  display:flex;
  justify-content:flex-end;
  gap:8px;
}
.btn-edit {
  background:#22c55e;  /* green */
  color:#ffffff;
}
.btn-view {
  background:#2563eb; /* blue */
  color:#ffffff;
}
.btn-delete {
  background:#ef4444; /* red */
  color:#ffffff;
}

/* pagination */
.pagination {
  margin-top:18px;
  display:flex;
  justify-content:center;
  gap:6px;
}
.page-link {
  padding:6px 12px;
  border-radius:999px;
  border:1px solid #d1d5db;
  background:#ffffff;
  text-decoration:none;
  font-size:13px;
  color:#4c1d95;
}
.page-link.active {
  background:#4c1d95;
  color:#ffffff;
  border-color:#4c1d95;
}
</style>
</head>
<body>

<div class="page">

  <!-- ========================== HEADER ========================== -->
  <div class="header-top">
    <div class="title-block">
      <h1>Student Management</h1>
      <p>Search, filter and paginate student records (8 per page).</p>
    </div>

    <div class="user-block">
      <strong><?= htmlspecialchars($username) ?></strong>
      <span class="user-role"><?= htmlspecialchars($role) ?></span>

      <div>
        <a href="dashboard.php" class="btn btn-dashboard">Dashboard</a>
        <a href="logout.php" class="btn btn-logout">Logout</a>
      </div>
    </div>
  </div>


  <!-- ========================== MESSAGES ========================== -->
  <?php if ($success): ?>
    <div class="flash flash-success"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="flash flash-error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>


  <!-- ========================== FILTER PILLS ========================== -->
  <div class="pill-row">
    <a href="student_list.php<?= ($keyword || $statusFilter) ? '?' . http_build_query(['q'=>$keyword,'status'=>$statusFilter]) : '' ?>"
       class="pill <?= !$showRecent ? 'active' : '' ?>">
       All students
    </a>

    <a href="student_list.php?<?= htmlspecialchars(http_build_query(array_filter([
        'q'      => $keyword,
        'status' => $statusFilter,
        'recent' => '1'
      ]))) ?>"
      class="pill <?= $showRecent ? 'active' : '' ?>">
      Recent students (last <?= $recentDays ?> days)
    </a>
  </div>


  <!-- ========================== TOOLBAR (SEARCH + FILTER + ADD) ========================== -->
  <div class="toolbar">

    <!-- SEARCH BAR -->
    <form method="get" class="search-box">
      <input class="input" type="text" name="q" placeholder="Search student name or email…"
             value="<?= htmlspecialchars($keyword) ?>">

      <?php if ($showRecent): ?>
        <input type="hidden" name="recent" value="1">
      <?php endif; ?>

      <?php if ($statusFilter !== ''): ?>
        <input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter) ?>">
      <?php endif; ?>

      <button class="btn btn-blue" type="submit">Search</button>
      <a href="student_list.php" class="btn btn-gray">Clear</a>
    </form>

    <!-- STATUS FILTER -->
    <form method="get" class="filter-box">
      <select class="select" name="status">
        <option value="">All statuses</option>
        <option value="active"   <?= $statusFilter==='active'?'selected':'' ?>>Active</option>
        <option value="inactive" <?= $statusFilter==='inactive'?'selected':'' ?>>Inactive</option>
      </select>

      <?php if ($keyword !== ''): ?>
        <input type="hidden" name="q" value="<?= htmlspecialchars($keyword) ?>">
      <?php endif; ?>

      <?php if ($showRecent): ?>
        <input type="hidden" name="recent" value="1">
      <?php endif; ?>

      <button class="btn btn-blue" type="submit">Filter</button>
      <a href="student_list.php" class="btn btn-gray">Reset</a>
    </form>

    <!-- ADD NEW STUDENT -->
    <div style="text-align:right;">
      <a href="add_student.php" class="btn btn-add">+ Add Student</a>
    </div>

  </div>


  <!-- ========================== STUDENTS TABLE ========================== -->
  <table>
    <thead>
      <tr>
        <th>#</th>
        <th>Student</th>
        <th>Email</th>
        <th>Date of Birth</th>
        <th>Age</th>
        <th>GPA</th>
        <th>Status</th>
        <th style="text-align:right;">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$rows): ?>
        <tr><td colspan="8" style="text-align:center;padding:20px;">No students found.</td></tr>
      <?php else: ?>
        <?php $i = $offset + 1; ?>
        <?php foreach ($rows as $s): ?>
          <tr>
            <td><?= $i++ ?></td>
            <td><?= htmlspecialchars($s['FirstName']." ".$s['LastName']) ?></td>
            <td><?= htmlspecialchars($s['Email']) ?></td>
            <td><?= htmlspecialchars($s['DateOfBirth']) ?></td>
            <td><?= $s['Age'] === null ? "—" : (int)$s['Age'] ?></td>
            <td><?= $s['GPA'] === null ? "—" : number_format((float)$s['GPA'], 2) ?></td>
            <td><?= student_status_badge((int)$s['IsActive']) ?></td>

            <td>
              <div class="actions">

                <!-- EDIT -->
                <a href="edit_student.php?id=<?= (int)$s['StudentID'] ?>"
                   class="btn btn-edit">
                   Edit
                </a>

                <!-- DELETE -->
                <a href="delete_student.php?id=<?= (int)$s['StudentID'] ?>"
                   class="btn btn-delete">
                  
                   Delete
                </a>

              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>


  <!-- ========================== PAGINATION ========================== -->
  <?php if ($totalPages > 1): ?>
    <div class="pagination">
      <?php if ($page > 1): ?>
        <a class="page-link"
           href="<?= htmlspecialchars(student_page_link($page-1,$keyword,$statusFilter,$showRecent)) ?>">
           Prev
        </a>
      <?php endif; ?>

      <?php for ($p=1;$p<=$totalPages;$p++): ?>
        <a class="page-link <?= $p===$page?'active':'' ?>"
           href="<?= htmlspecialchars(student_page_link($p,$keyword,$statusFilter,$showRecent)) ?>">
           <?= $p ?>
        </a>
      <?php endfor; ?>

      <?php if ($page < $totalPages): ?>
        <a class="page-link"
           href="<?= htmlspecialchars(student_page_link($page+1,$keyword,$statusFilter,$showRecent)) ?>">
           Next
        </a>
      <?php endif; ?>
    </div>
  <?php endif; ?>

</div>

</body>
</html>
