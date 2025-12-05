<?php
session_start();
require_once '../includes/auth_check.php';
requireRole(['Admin', 'Staff']);

require_once '../includes/course_model.php';

$id = (int)($_GET['id'] ?? 0);
$course = getCourseById($id);

if (!$course) {
    header("Location: course_list.php");
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>View Course</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    body { background:#f5f7fa; padding:30px; font-family:'Poppins',sans-serif; }
    .container { background:#fff; padding:25px; border-radius:12px; box-shadow:0 5px 20px rgba(0,0,0,0.1); }
    .title { font-size:28px; font-weight:600; }
    .badge-status { font-size:14px; padding:6px 10px; }
</style>
</head>
<body>

<div class="container">

    <h2 class="title mb-3">
        📘 <?= htmlspecialchars($course['CourseName']) ?>
    </h2>

    <p class="text-muted"><?= htmlspecialchars($course['Description'] ?: 'No description provided.') ?></p>

    <table class="table table-bordered mt-3">
        <tr>
            <th width="220">Course Code</th>
            <td><?= htmlspecialchars($course['CourseCode']); ?></td>
        </tr>
        <tr>
            <th>Credits</th>
            <td><?= $course['Credits']; ?></td>
        </tr>
        <tr>
            <th>Fee (DKK)</th>
            <td><?= number_format($course['Fee'], 2); ?></td>
        </tr>
        <tr>
            <th>Start Date</th>
            <td><?= $course['StartDate'] ? date('M j, Y', strtotime($course['StartDate'])) : '—'; ?></td>
        </tr>
        <tr>
            <th>Status</th>
            <td>
                <?php if ($course['IsActive']): ?>
                    <span class="badge bg-success badge-status">Active</span>
                <?php else: ?>
                    <span class="badge bg-secondary badge-status">Inactive</span>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <th>Instructor</th>
            <td>
                <?= ($course['FirstName'])
                        ? htmlspecialchars($course['FirstName'].' '.$course['LastName'])
                        : '<span class="text-muted">Not assigned</span>' ?>
            </td>
        </tr>
    </table>

    <div class="d-flex justify-content-between mt-4">
        <a href="course_list.php" class="btn btn-secondary">⬅ Back</a>
        <a href="course_edit.php?id=<?= $course['CourseID'] ?>" class="btn btn-primary">✏️ Edit</a>
    </div>

</div>

</body>
</html>
