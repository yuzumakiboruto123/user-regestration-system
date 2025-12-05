<?php
session_start();

require_once "../includes/auth_check.php";
requireRole(['Admin']); // Only admin can delete

require_once "../includes/db.php";
$pdo = getDB();

// -------------------------------------------
// VALIDATE COURSE ID
// -------------------------------------------
$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    header("Location: course_list.php?msg=invalid");
    exit;
}

// -------------------------------------------
// LOAD COURSE DETAILS
// -------------------------------------------
$stmt = $pdo->prepare("
    SELECT c.*, s.FirstName, s.LastName
    FROM Course c
    LEFT JOIN Staff s ON c.StaffID = s.StaffID
    WHERE c.CourseID = ?
");
$stmt->execute([$id]);
$course = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$course) {
    header("Location: course_list.php?msg=notfound");
    exit;
}

// -------------------------------------------
// DELETE — PERMANENT DELETE
// -------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {
        $pdo->beginTransaction();

        // 1️⃣ Delete enrollments linked to this course
        $pdo->prepare("DELETE FROM Enrollment WHERE CourseID = ?")->execute([$id]);

        // 2️⃣ Delete the course itself
        $pdo->prepare("DELETE FROM Course WHERE CourseID = ?")->execute([$id]);

        $pdo->commit();

        header("Location: course_list.php?msg=deleted");
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        die("Delete failed: " . $e->getMessage());
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Delete Course</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: linear-gradient(135deg, #ff6a00, #ee0979);
            min-height: 100vh;
            padding: 25px;
            font-family: "Poppins", sans-serif;
        }
        .container-box {
            background: white;
            padding: 30px;
            border-radius: 15px;
            max-width: 850px;
            margin: auto;
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
        }
        .header-bar {
            background: #e74c3c;
            padding: 14px;
            color: white;
            text-align: center;
            border-radius: 10px;
            margin-bottom: 25px;
        }
    </style>
</head>

<body>

<div class="container-box">

    <div class="header-bar">
        <h2>Delete Course</h2>
    </div>

    

    <div class="mb-3">
        <label class="form-label">Course Name</label>
        <input class="form-control" value="<?= htmlspecialchars($course['CourseName']) ?>" readonly>
    </div>

    <div class="row mb-3">
        <div class="col-md-6">
            <label class="form-label">Course Code</label>
            <input class="form-control" value="<?= htmlspecialchars($course['CourseCode']) ?>" readonly>
        </div>
        <div class="col-md-6">
            <label class="form-label">Credits</label>
            <input class="form-control" value="<?= htmlspecialchars($course['Credits']) ?>" readonly>
        </div>
    </div>

    <div class="d-flex justify-content-between mt-4">
        <a href="course_list.php" class="btn btn-secondary">Cancel</a>

        <form method="POST" >
            <button class="btn btn-danger">Delete Permanently</button>
        </form>
    </div>

</div>

</body>
</html>
