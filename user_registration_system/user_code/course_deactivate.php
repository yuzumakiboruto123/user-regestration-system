<?php
session_start();
require_once '../includes/auth_check.php';
requireRole(['Admin']);

require_once '../includes/course_model.php';

$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    deactivateCourse($id);
}

header("Location: course_list.php?msg=updated");
exit;
