<?php
// list_enrollment.php – Enrollment Management (UI like screenshot) //

require_once '../includes/auth_check.php';  // login + role
require_once '../includes/db.php';          // getDB()
requireRole(['Admin','Staff']);             // only admin + staff

// db connection //
$pdo = getDB();

// start session for messages //
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// logged-in user info //
$username = $_SESSION['username'] ?? 'User';
$role     = $_SESSION['role'] ?? '';

// ------------- FILTERS ------------- //
$keyword   = trim($_GET['q'] ?? '');        // search keyword
$status    = trim($_GET['status'] ?? '');   // status filter
$showRecent = isset($_GET['recent']) && $_GET['recent'] === '1'; // recent toggle
$recentDays = 30;                           // last X days

// ------------- PAGINATION ------------- //
$perPage = 8;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

// ------------- WHERE CLAUSE BUILD ------------- //
$where  = " WHERE 1=1 ";
$params = [];

// keyword filter: student name, email, course name, code //
if ($keyword !== '') {
    $where .= " AND (
        s.FirstName LIKE :kw
        OR s.LastName LIKE :kw
        OR s.Email LIKE :kw
        OR c.CourseName LIKE :kw
        OR c.CourseCode LIKE :kw
    )";
    $params[':kw'] = "%$keyword%";
}

// status filter //
if ($status !== '') {
    $where .= " AND e.Status = :st ";
    $params[':st'] = $status;
}

// recent filter: EnrollmentDate last X days //
if ($showRecent) {
    $where .= " AND e.EnrollmentDate >= DATE_SUB(CURDATE(), INTERVAL :days DAY) ";
    $params[':days'] = $recentDays;
}

// ------------- STEP 1: COUNT ROWS ------------- //
$countSql = "
    SELECT COUNT(*)
    FROM enrollment e
    JOIN student s ON e.StudentID = s.StudentID
    JOIN course  c ON e.CourseID  = c.CourseID
    $where
";
$stmt = $pdo->prepare($countSql);
$stmt->execute($params);
$totalRows  = (int)$stmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows / $perPage));
if ($page > $totalPages) {
    $page   = $totalPages;
    $offset = ($page - 1) * $perPage;
}

// ------------- STEP 2: FETCH PAGE DATA ------------- //
$sql = "
    SELECT 
        e.EnrollmentID,
        e.EnrollmentDate,
        e.Status,
        e.FinalGrade,
        CONCAT(s.FirstName,' ',s.LastName) AS StudentName,
        s.Email AS StudentEmail,
        c.CourseName,
        c.CourseCode
    FROM enrollment e
    JOIN student s ON e.StudentID = s.StudentID
    JOIN course  c ON e.CourseID  = c.CourseID
    $where
    ORDER BY e.EnrollmentDate DESC, e.EnrollmentID DESC
    LIMIT :limit OFFSET :offset
";

$stmt = $pdo->prepare($sql);

// bind filters //
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
// bind limit/offset as integers //
$stmt->bindValue(':limit',  (int)$perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', (int)$offset,  PDO::PARAM_INT);

$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ------------- SUPPORT DATA ------------- //
$statuses = ['registered','in-progress','completed','failed','dropped'];

// status badge HTML //
function status_badge(string $s): string {
    $colors = [
        'completed'   => '#16a34a',
        'registered'  => '#2563eb',
        'in-progress' => '#f59e0b',
        'failed'      => '#dc2626',
        'dropped'     => '#dc2626',
    ];
    $c = $colors[$s] ?? '#6b7280';
    return "<span style='
        padding:4px 14px;
        border-radius:999px;
        border:1px solid {$c};
        color:{$c};
        background-color:{$c}10;
        font-size:14px;
    '>".ucfirst($s)."</span>";
}

// pagination link builder //
function page_link(int $page, string $keyword, string $status, bool $recent): string {
    $q = ['page' => $page];
    if ($keyword !== '') $q['q'] = $keyword;
    if ($status  !== '') $q['status'] = $status;
    if ($recent)         $q['recent'] = '1';
    return 'list_enrollment.php?' . http_build_query($q);
}

// messages //
$success = $_SESSION['success_message'] ?? '';
$error   = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Enrollment Management</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<style>
body {
  margin:0;
  padding:30px;
  background:#f3f4f6;
  font-family:system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
}
.page {
  max-width:1200px;
  margin:0 auto;
  background:#ffffff;
  border-radius:18px;
  padding:32px 36px 28px;
  box-shadow:0 20px 45px rgba(0,0,0,0.12);
}

/* header */
.header-top {
  display:flex;
  justify-content:space-between;
  align-items:flex-start;
  margin-bottom:24px;
}
.title-block h1 {
  margin:0;
  font-size:34px;
  font-weight:800;
}
.title-block p {
  margin:6px 0 0;
  color:#6b7280;
  font-size:15px;
}
.user-block {
  text-align:right;
  font-size:14px;
}
.user-block strong {
  display:block;
}
.user-role {
  color:#6b7280;
  font-size:13px;
}
.btn {
  display:inline-block;
  font-size:14px;
  padding:8px 16px;
  border-radius:999px;
  border:none;
  cursor:pointer;
  text-decoration:none;
  font-weight:600;
}
.btn-dashboard {
  background:#e5e7eb;
  color:#111827;
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
  color:#166534;
  border:1px solid #15803d;
}
.flash-error {
  background:#fee2e2;
  color:#b91c1c;
  border:1px solid #b91c1c;
}

/* pill filter row */
.pill-row {
  display:flex;
  gap:10px;
  margin-bottom:14px;
}
.pill {
  padding:6px 14px;
  border-radius:999px;
  border:1px solid #d1d5db;
  font-size:13px;
  text-decoration:none;
  color:#111827;
  background:#f9fafb;
}
.pill.active {
  background:#111827;
  color:#ffffff;
  border-color:#111827;
}

/* toolbar */
.toolbar {
  display:grid;
  grid-template-columns: minmax(0, 3fr) minmax(0, 2fr) auto;
  gap:10px;
  align-items:center;
  margin-bottom:16px;
}
.search-box {
  display:flex;
  gap:8px;
}
.input {
  flex:1;
  padding:9px 10px;
  border-radius:999px;
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
  padding:9px 12px;
  border-radius:999px;
  border:1px solid #d1d5db;
  font-size:14px;
}
.btn-add {
  background:#2563eb;
  color:#ffffff;
}

/* table */
table {
  width:100%;
  border-collapse:collapse;
  font-size:14px;
}
thead {
  background:#f3f4f6;
}
th, td {
  padding:12px 10px;
  text-align:left;
}
th {
  font-weight:600;
  font-size:13px;
  color:#4b5563;
}
tbody tr:nth-child(even) {
  background:#f9fafb;
}
tbody tr:hover td {
  background:#eef2ff;
}

/* actions */
.actions {
  display:flex;
  justify-content:flex-end;
  gap:8px;
}
.btn-edit {
  background:#e5e7eb;
  color:#111827;
}
.btn-delete {
  background:#ef4444;
  color:#ffffff;
}

/* pagination */
.pagination {
  margin-top:16px;
  display:flex;
  justify-content:center;
  gap:6px;
}
.page-link {
  min-width:28px;
  padding:6px 10px;
  text-align:center;
  border-radius:999px;
  border:1px solid #d1d5db;
  text-decoration:none;
  font-size:13px;
  color:#111827;
  background:#ffffff;
}
.page-link.active {
  background:#2563eb;
  color:#ffffff;
  border-color:#2563eb;
}
</style>
</head>
<body>
<div class="page">

  <!-- top header -->
  <div class="header-top">
    <div class="title-block">
      <h1>Enrollment Management</h1>
      <p>Search, filter and paginate enrollment records (8 per page).</p>
    </div>
    <div class="user-block">
      <strong><?= htmlspecialchars($username) ?></strong>
      <span class="user-role"><?= htmlspecialchars($role) ?></span>
      <div style="margin-top:8px;">
        <a href="dashboard.php" class="btn btn-dashboard">Dashboard</a>
        <a href="logout.php" class="btn btn-logout">Logout</a>
      </div>
    </div>
  </div>

  <!-- messages -->
  <?php if ($success): ?>
    <div class="flash flash-success"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="flash flash-error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <!-- All / Recent pills -->
  <div class="pill-row">
    <a href="list_enrollment.php<?= ($keyword || $status) ? '?' . http_build_query(['q'=>$keyword,'status'=>$status]) : '' ?>"
       class="pill <?= !$showRecent ? 'active' : '' ?>">All enrollments</a>

    <a href="list_enrollment.php?<?= http_build_query(array_filter([
          'q'      => $keyword,
          'status' => $status,
          'recent' => '1'
        ])) ?>"
       class="pill <?= $showRecent ? 'active' : '' ?>">Recent enrollments (last <?= $recentDays ?> days)</a>
  </div>

  <!-- toolbar -->
  <div class="toolbar">

    <!-- search -->
    <form method="get" class="search-box">
      <input class="input" type="text" name="q" placeholder="Search student or course…"
             value="<?= htmlspecialchars($keyword) ?>">
      <?php if ($showRecent): ?>
        <input type="hidden" name="recent" value="1">
      <?php endif; ?>
      <?php if ($status !== ''): ?>
        <input type="hidden" name="status" value="<?= htmlspecialchars($status) ?>">
      <?php endif; ?>
      <button class="btn btn-blue" type="submit">Search</button>
      <a href="list_enrollment.php" class="btn btn-gray">Clear</a>
    </form>

    <!-- status filter -->
    <form method="get" class="filter-box">
      <select class="select" name="status">
        <option value="">All statuses</option>
        <?php foreach ($statuses as $s): ?>
          <option value="<?= $s ?>" <?= $s === $status ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
        <?php endforeach; ?>
      </select>
      <?php if ($keyword !== ''): ?>
        <input type="hidden" name="q" value="<?= htmlspecialchars($keyword) ?>">
      <?php endif; ?>
      <?php if ($showRecent): ?>
        <input type="hidden" name="recent" value="1">
      <?php endif; ?>
      <button class="btn btn-blue" type="submit">Filter</button>
      <a href="list_enrollment.php" class="btn btn-gray">Reset</a>
    </form>

    <!-- add button -->
    <div style="text-align:right;">
      <a href="create_enrollment.php" class="btn btn-add">+ Add Enrollment</a>
    </div>
  </div>

  <!-- table -->
  <table>
    <thead>
      <tr>
        <th>#</th>
        <th>Student</th>
        <th>Course</th>
        <th>Date</th>
        <th>Status</th>
        <th>Grade</th>
        <th style="text-align:right;">Actions</th>
      </tr>
    </thead>
    <tbody>
    <?php if (!$rows): ?>
      <tr><td colspan="7" style="text-align:center;padding:20px;">No records found.</td></tr>
    <?php else: ?>
      <?php $i = $offset + 1; ?>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= $i++ ?></td>
          <td><?= htmlspecialchars($r['StudentName']) ?></td>
          <td><?= htmlspecialchars($r['CourseCode'].' — '.$r['CourseName']) ?></td>
          <td><?= htmlspecialchars($r['EnrollmentDate']) ?></td>
          <td><?= status_badge($r['Status']) ?></td>
          <td><?= $r['FinalGrade'] === null ? '—' : htmlspecialchars($r['FinalGrade']) ?></td>
          <td>
            <div class="actions">
              <a href="edit_enrollment.php?id=<?= (int)$r['EnrollmentID'] ?>" class="btn btn-edit">Edit</a>
              <a href="delete_enrollment.php?id=<?= (int)$r['EnrollmentID'] ?>"class="btn btn-delete"> Delete</a>
                 
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
  </table>

  <!-- pagination -->
  <?php if ($totalPages > 1): ?>
    <div class="pagination">
      <?php if ($page > 1): ?>
        <a class="page-link" href="<?= htmlspecialchars(page_link($page-1, $keyword, $status, $showRecent)) ?>">Prev</a>
      <?php endif; ?>

      <?php for ($p = 1; $p <= $totalPages; $p++): ?>
        <a class="page-link <?= $p === $page ? 'active' : '' ?>"
           href="<?= htmlspecialchars(page_link($p, $keyword, $status, $showRecent)) ?>">
           <?= $p ?>
        </a>
      <?php endfor; ?>

      <?php if ($page < $totalPages): ?>
        <a class="page-link" href="<?= htmlspecialchars(page_link($page+1, $keyword, $status, $showRecent)) ?>">Next</a>
      <?php endif; ?>
    </div>
  <?php endif; ?>

</div>
</body>
</html>
