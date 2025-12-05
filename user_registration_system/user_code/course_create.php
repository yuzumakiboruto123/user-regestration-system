<?php
session_start();

// ---------------------------
// AUTH CHECK (your system)
// ---------------------------
require_once "../includes/auth_check.php";
requireRole(['Admin']); // only Admin can add courses

// ---------------------------
// DB CONNECTION (your system)
// ---------------------------
require_once "../includes/db.php";
$pdo = getDB();

// ---------------------------
// LOAD ACTIVE STAFF
// ---------------------------
$staff = [];
try {
    $stmt = $pdo->query("SELECT StaffID, FirstName, LastName FROM Staff WHERE IsActive = 1 ORDER BY FirstName");
    $staff = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $staff = [];
}

// ---------------------------
// FORM VALIDATION
// ---------------------------
$errors = [];
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $course_name  = trim($_POST['course_name'] ?? "");
    $course_code  = strtoupper(trim($_POST['course_code'] ?? ""));
    $description  = trim($_POST['description'] ?? "");
    $credits      = (int)($_POST['credits'] ?? 0);
    $fee          = (float)($_POST['fee'] ?? 0);
    $start_date   = $_POST['start_date'] ?? "";
    $staff_id     = !empty($_POST['staff_id']) ? (int)$_POST['staff_id'] : null;

    // --- VALIDATION ---
    if ($course_name === "" || strlen($course_name) < 3) {
        $errors[] = "Course name must be at least 3 characters long.";
    }

    if (!preg_match('/^[A-Z0-9\-]{2,20}$/', $course_code)) {
        $errors[] = "Course code must be 2–20 characters (A–Z, 0–9, - only).";
    }

    if ($credits < 1 || $credits > 10) {
        $errors[] = "Credits must be between 1 and 10.";
    }

    if ($fee < 0.01 || $fee > 10000) {
        $errors[] = "Fee must be between 0.01 and 10,000.";
    }

    if (empty($start_date)) {
        $errors[] = "Start date is required.";
    } elseif ($start_date < date('Y-m-d')) {
        $errors[] = "Start date cannot be in the past.";
    }

    // --- CHECK DUPLICATE CODE ---
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM Course WHERE CourseCode = ?");
    $stmt->execute([$course_code]);
    if ($stmt->fetchColumn() > 0) {
        $errors[] = "Course code already exists.";
    }

    // --- INSERT IF VALID ---
    if (empty($errors)) {

        $stmt = $pdo->prepare("
            INSERT INTO Course 
            (StaffID, CourseName, CourseCode, Description, Credits, Fee, StartDate, IsActive)
            VALUES (?, ?, ?, ?, ?, ?, ?, 1)
        ");

        $ok = $stmt->execute([
            $staff_id,
            $course_name,
            $course_code,
            $description,
            $credits,
            $fee,
            $start_date
        ]);

        if ($ok) {
            header("Location: course_list.php?msg=created");
            exit;
        } else {
            $errors[] = "Failed to create course. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Create Course</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Match UI from your screenshot -->
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 30px;
            font-family: "Poppins", sans-serif;
        }
        .container-box {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            max-width: 850px;
            margin: auto;
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
        <h2><i class="fas fa-plus-circle"></i> Create New Course</h2>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <strong>Please fix the following:</strong>
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
                <label class="form-label">Course Name *</label>
                <input type="text" name="course_name" class="form-control"
                       value="<?= htmlspecialchars($_POST['course_name'] ?? '') ?>" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Course Code *</label>
                <input type="text" name="course_code" class="form-control"
                       value="<?= htmlspecialchars($_POST['course_code'] ?? '') ?>" required>
            </div>
        </div>


        <div class="row mb-3">
            <div class="col-md-4">
                <label class="form-label">Credits *</label>
                <select name="credits" class="form-control" required>
                    <option value="">Choose...</option>
                    <?php for ($i=1; $i<=10; $i++): ?>
                        <option value="<?= $i ?>"
                            <?= (($_POST['credits'] ?? '') == $i) ? 'selected' : '' ?>>
                            <?= $i ?> Credits
                        </option>
                    <?php endfor; ?>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Fee (DKK) *</label>
                <input type="number" name="fee" class="form-control" step="0.01"
                       value="<?= htmlspecialchars($_POST['fee'] ?? '') ?>" required>
            </div>

            <div class="col-md-4">
                <label class="form-label">Start Date *</label>
                <input type="date" name="start_date" class="form-control"
                       min="<?= date('Y-m-d') ?>"
                       value="<?= htmlspecialchars($_POST['start_date'] ?? '') ?>" required>
            </div>
        </div>


        <div class="mb-3">
            <label class="form-label">Instructor</label>
            <select name="staff_id" class="form-control">
                <option value="">Select Instructor</option>

                <?php foreach ($staff as $s): ?>
                    <option value="<?= $s['StaffID'] ?>"
                        <?= (($_POST['staff_id'] ?? '') == $s['StaffID']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($s['FirstName'] . ' ' . $s['LastName']) ?>
                    </option>
                <?php endforeach; ?>

            </select>
        </div>


        <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="4"><?= 
                htmlspecialchars($_POST['description'] ?? '') ?></textarea>
        </div>


        <div class="d-flex justify-content-between mt-4">
            <a href="course_list.php" class="btn btn-secondary">← Back</a>
            <button class="btn btn-success">Create Course</button>
        </div>

    </form>

</div>

</body>
</html>
