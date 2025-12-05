<?php
session_start();

require_once "../includes/auth_check.php";
requireRole(['Admin']); // only admin can edit

require_once "../includes/db.php";
$pdo = getDB();

// ----------------------------
// Load Course
// ----------------------------
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: course_list.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM Course WHERE CourseID = ?");
$stmt->execute([$id]);
$course = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$course) {
    header("Location: course_list.php?msg=notfound");
    exit;
}

// ----------------------------
// Load Staff for Dropdown
// ----------------------------
$staffStmt = $pdo->query("SELECT StaffID, FirstName, LastName FROM Staff WHERE IsActive = 1 ORDER BY FirstName");
$staff = $staffStmt->fetchAll(PDO::FETCH_ASSOC);

// ----------------------------
// Handle Edit Submission
// ----------------------------
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $course_name  = trim($_POST['course_name']);
    $course_code  = strtoupper(trim($_POST['course_code']));
    $description  = trim($_POST['description']);
    $credits      = (int)$_POST['credits'];
    $fee          = (float)$_POST['fee'];
    $start_date   = $_POST['start_date'];
    $staff_id     = !empty($_POST['staff_id']) ? (int)$_POST['staff_id'] : null;

    // Validation
    if (strlen($course_name) < 3) $errors[] = "Course name must be at least 3 characters.";
    if (!preg_match('/^[A-Z0-9\-]{2,20}$/', $course_code)) $errors[] = "Invalid course code.";
    if ($credits < 1 || $credits > 10) $errors[] = "Credits must be between 1 and 10.";
    if ($fee < 0.01) $errors[] = "Fee must be greater than 0.";
    if ($start_date < date('Y-m-d')) $errors[] = "Start date cannot be in the past.";

    // Check duplicate course code (excluding current course)
    if ($course_code !== $course['CourseCode']) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM Course WHERE CourseCode = ? AND CourseID != ?");
        $stmt->execute([$course_code, $id]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "Course code already exists.";
        }
    }

    // Update course
    if (empty($errors)) {
        $stmt = $pdo->prepare("
            UPDATE Course SET
                StaffID = ?, CourseName = ?, CourseCode = ?, Description = ?,
                Credits = ?, Fee = ?, StartDate = ?
            WHERE CourseID = ?
        ");

        $stmt->execute([
            $staff_id,
            $course_name,
            $course_code,
            $description,
            $credits,
            $fee,
            $start_date,
            $id
        ]);

        header("Location: course_list.php?msg=updated");
        exit;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Course</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: linear-gradient(135deg, #667eea, #764ba2);
            min-height: 100vh;
            padding: 30px;
            font-family: "Poppins", sans-serif;
        }
        .container-box {
            background: white;
            padding: 30px;
            border-radius: 15px;
            max-width: 900px;
            margin: auto;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .header-bar {
            background: #2c3e50;
            color: white;
            padding: 14px;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 25px;
        }
    </style>
</head>
<body>

<div class="container-box">

    <div class="header-bar">
        <h2>Edit Course</h2>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <strong>Please fix:</strong>
            <ul>
                <?php foreach ($errors as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>


    <form method="POST">

        <div class="row mb-3">
            <div class="col-md-6">
                <label>Course Name *</label>
                <input type="text" name="course_name" class="form-control"
                       value="<?= htmlspecialchars($course['CourseName']) ?>" required>
            </div>

            <div class="col-md-6">
                <label>Course Code *</label>
                <input type="text" name="course_code" class="form-control"
                       value="<?= htmlspecialchars($course['CourseCode']) ?>" required>
            </div>
        </div>


        <div class="row mb-3">
            <div class="col-md-4">
                <label>Credits *</label>
                <select name="credits" class="form-control">
                    <?php for ($i=1; $i<=10; $i++): ?>
                        <option value="<?= $i ?>" <?= ($course['Credits'] == $i ? "selected" : "") ?>>
                            <?= $i ?> Credits
                        </option>
                    <?php endfor; ?>
                </select>
            </div>

            <div class="col-md-4">
                <label>Fee (DKK) *</label>
                <input type="number" name="fee" step="0.01" class="form-control"
                       value="<?= htmlspecialchars($course['Fee']) ?>" required>
            </div>

            <div class="col-md-4">
                <label>Start Date *</label>
                <input type="date" name="start_date" class="form-control"
                       value="<?= htmlspecialchars($course['StartDate']) ?>" required>
            </div>
        </div>


        <div class="mb-3">
            <label>Instructor</label>
            <select name="staff_id" class="form-control">
                <option value="">Select Instructor</option>

                <?php foreach ($staff as $s): ?>
                    <option value="<?= $s['StaffID'] ?>"
                        <?= ($s['StaffID'] == $course['StaffID']) ? "selected" : "" ?>>
                        <?= htmlspecialchars($s['FirstName'] . ' ' . $s['LastName']) ?>
                    </option>
                <?php endforeach; ?>

            </select>
        </div>


        <div class="mb-3">
            <label>Description</label>
            <textarea name="description" class="form-control" rows="4"><?= 
                htmlspecialchars($course['Description']) ?></textarea>
        </div>

        <div class="d-flex justify-content-between">
            <a href="course_list.php" class="btn btn-secondary">← Cancel</a>
            <button class="btn btn-primary">Save Changes</button>
        </div>

    </form>

</div>

</body>
</html>
