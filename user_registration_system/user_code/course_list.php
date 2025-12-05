<?php
session_start();

// -----------------------------
// ACCESS CONTROL
// -----------------------------
require_once '../includes/auth_check.php';
requireRole(['Admin', 'Staff']); // change if you want only Admin

// -----------------------------
// MODELS / DB
// -----------------------------
require_once '../includes/course_model.php'; // uses getDB() inside

// -----------------------------
// READ FILTERS + PAGINATION
// -----------------------------
$search        = trim($_GET['search'] ?? '');
$statusFilter  = $_GET['status'] ?? 'all';   // all | active | inactive
$page          = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page          = max(1, $page);
$limit         = 20;
$offset        = ($page - 1) * $limit;

// Build filters array for course_model.php
$filters = [
    'search' => $search,
    'status' => ''
];

// Map UI status filter → DB IsActive
if ($statusFilter === 'active') {
    $filters['status'] = 1;
} elseif ($statusFilter === 'inactive') {
    $filters['status'] = 0;
}

// Fetch data
$courses = getCourses($filters, $limit, $offset);
$total   = getCoursesCount($filters);
$pages   = max(1, ceil($total / $limit));

// -----------------------------
// SUCCESS / ERROR MESSAGES
// -----------------------------
$message      = null;
$message_type = 'success';

// Old-style ?msg=created/updated/deleted
if (isset($_GET['msg'])) {
    switch ($_GET['msg']) {
        case 'created':
            $message = "New course created successfully!";
            break;
        case 'updated':
            $message = "Course updated successfully!";
            break;
        case 'deleted':
            $message = "Course deleted successfully!";
            break;
    }
}

// Optional session-based message (if you ever set it)
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'] ?? 'success';
    unset($_SESSION['message'], $_SESSION['message_type']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Course Management</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body {
            background: #f8f9fa;
            font-family: "Segoe UI", system-ui, -apple-system, BlinkMacSystemFont, "Poppins", sans-serif;
        }

        /* Top navbar */
        .navbar-brand {
            font-weight: 600;
        }

        /* Card + table look */
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }

        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            border-radius: 15px 15px 0 0 !important;
            padding: 18px 22px;
        }

        .table th {
            background: #2c3e50;
            color: #fff;
            border-bottom: 0;
        }

        .table-hover tbody tr:hover {
            background-color: rgba(0, 123, 255, 0.05);
        }

        .status-badge {
            font-size: 0.8rem;
        }

        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 8px;
            min-width: 150px;
        }

        .action-buttons .btn {
            padding: 7px 11px;
            font-size: 13px;
            border-radius: 8px;
            border: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            transition: all 0.25s ease;
        }

        .action-buttons .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .btn-view {
            background: linear-gradient(135deg, #17a2b8, #138496);
            color: #fff;
        }

        .btn-edit {
            background: linear-gradient(135deg, #ffc107, #e0a800);
            color: #000;
        }

        .btn-activate {
            background: linear-gradient(135deg, #28a745, #218838);
            color: #fff;
        }

        .btn-deactivate {
            background: linear-gradient(135deg, #6c757d, #5a6268);
            color: #fff;
        }

        .btn-delete {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: #fff;
        }

        @media (max-width: 768px) {
            .action-buttons {
                flex-direction: row;
                flex-wrap: wrap;
            }
            .action-buttons .btn span.text {
                display: none;
            }
        }
    </style>
</head>
<body>

<!-- TOP NAVBAR (like screenshot) -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">
            <i class="fas fa-graduation-cap me-2"></i>Course Management System
        </a>
        <div class="navbar-nav ms-auto">
            <a class="nav-link" href="dashboard.php">
                <i class="fas fa-tachometer-alt me-1"></i>Dashboard
            </a>
            <a class="nav-link" href="course_create.php">
                <i class="fas fa-plus me-1"></i>Add Course
            </a>
            <?php if (!empty($_SESSION['username'])): ?>
                <span class="navbar-text text-white mx-2">
                    <i class="fas fa-user me-1"></i><?= htmlspecialchars($_SESSION['username']) ?>
                </span>
            <?php endif; ?>
            <a class="nav-link" href="../logout.php">
                <i class="fas fa-sign-out-alt me-1"></i>Logout
            </a>
        </div>
    </div>
</nav>

<div class="container mt-4">

    <!-- Page title + Add button -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
            <i class="fas fa-book-open me-2"></i>Course Management
        </h1>
        <a href="course_create.php" class="btn btn-success btn-lg">
            <i class="fas fa-plus"></i> Add New Course
        </a>
    </div>

    <!-- Global message -->
    <?php if ($message): ?>
        <div class="alert alert-<?= $message_type ?> alert-dismissible fade show">
            <i class="fas fa-<?= $message_type === 'success' ? 'check-circle' : 'exclamation-triangle' ?> me-2"></i>
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Search & Filter card -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-search me-2"></i>Search &amp; Filter Courses
            </h5>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">
                        <i class="fas fa-search me-1"></i>Search Courses
                    </label>
                    <div class="input-group">
                        <input type="text"
                               name="search"
                               class="form-control"
                               placeholder="Search by course name or code..."
                               value="<?= htmlspecialchars($search) ?>">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Search
                        </button>
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">
                        <i class="fas fa-filter me-1"></i>Filter by Status
                    </label>
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="all"     <?= $statusFilter === 'all' ? 'selected' : '' ?>>All Courses</option>
                        <option value="active"  <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active Only</option>
                        <option value="inactive"<?= $statusFilter === 'inactive' ? 'selected' : '' ?>>Inactive Only</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label fw-semibold">&nbsp;</label>
                    <a href="course_list.php" class="btn btn-secondary w-100">
                        <i class="fas fa-rotate-left"></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Courses table -->
    <?php if (empty($courses)): ?>
        <div class="alert alert-info text-center">
            <h5><i class="fas fa-info-circle me-2"></i>No Courses Found</h5>
            <p class="mb-3">
                <?php if ($search !== '' || $statusFilter !== 'all'): ?>
                    Try changing your search or filters.
                <?php else: ?>
                    There are no courses in the system yet.
                <?php endif; ?>
            </p>
            <a href="course_create.php" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i>Add First Course
            </a>
        </div>
    <?php else: ?>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Course Name</th>
                            <th>Course Code</th>
                            <th>Credits</th>
                            <th>Fee (DKK)</th>
                            <th>Start Date</th>
                            <th>Status</th>
                            <th>Instructor</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($courses as $c): ?>
                            <tr>
                                <td><?= (int)$c['CourseID']; ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($c['CourseName']); ?></strong>
                                    <?php if (!empty($c['Description'])): ?>
                                        <br><small class="text-muted">
                                            <?= htmlspecialchars($c['Description']); ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td><code><?= htmlspecialchars($c['CourseCode']); ?></code></td>
                                <td>
                                    <span class="badge bg-info">
                                        <?= (int)$c['Credits']; ?> Credits
                                    </span>
                                </td>
                                <td><strong><?= number_format((float)$c['Fee'], 2); ?></strong></td>
                                <td>
                                    <?php
                                    if (!empty($c['StartDate'])) {
                                        echo date('M j, Y', strtotime($c['StartDate']));
                                    } else {
                                        echo '—';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php if (!empty($c['IsActive'])): ?>
                                        <span class="badge bg-success status-badge">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary status-badge">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($c['StaffName'])): ?>
                                        <?= htmlspecialchars($c['StaffName']); ?>
                                    <?php else: ?>
                                        <span class="text-muted">Not assigned</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <!-- View course (needs course_view.php) -->
                                        <a href="course_view.php?id=<?= (int)$c['CourseID']; ?>"
                                           class="btn btn-view" title="View course">
                                            <i class="fas fa-eye"></i>
                                            <span class="text">View</span>
                                        </a>

                                        <!-- Edit -->
                                        <a href="course_edit.php?id=<?= (int)$c['CourseID']; ?>"
                                           class="btn btn-edit" title="Edit course">
                                            <i class="fas fa-edit"></i>
                                            <span class="text">Edit</span>
                                        </a>

                                        <!-- Activate / Deactivate -->
                                        <?php if (!empty($c['IsActive'])): ?>
                                            <a href="course_deactivate.php?id=<?= (int)$c['CourseID']; ?>"
                                               class="btn btn-deactivate"
                                               onclick="return confirm('Deactivate this course?');"
                                               title="Deactivate course">
                                                <i class="fas fa-pause"></i>
                                                <span class="text">Deactivate</span>
                                            </a>
                                        <?php else: ?>
                                            <a href="course_activate.php?id=<?= (int)$c['CourseID']; ?>"
                                               class="btn btn-activate"
                                               onclick="return confirm('Activate this course?');"
                                               title="Activate course">
                                                <i class="fas fa-play"></i>
                                                <span class="text">Activate</span>
                                            </a>
                                        <?php endif; ?>

                                        <!-- Delete -->
                                        <a href="course_delete.php?id=<?= (int)$c['CourseID']; ?>"
                                           class="btn btn-delete"
                                           onclick="return confirm('Are you sure you want to delete this course permanently?');"
                                           title="Delete course">
                                            <i class="fas fa-trash"></i>
                                            <span class="text">Delete</span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($pages > 1): ?>
                    <nav class="mt-3">
                        <ul class="pagination justify-content-center">
                            <?php for ($i = 1; $i <= $pages; $i++): ?>
                                <li class="page-item <?= $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link"
                                       href="?page=<?= $i; ?>&search=<?= urlencode($search); ?>&status=<?= urlencode($statusFilter); ?>">
                                        <?= $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                <?php endif; ?>

            </div>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
