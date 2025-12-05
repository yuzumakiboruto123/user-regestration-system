<?php
require_once __DIR__ . '/db.php';

/*
|--------------------------------------------------------------------------
| GET COURSES (FILTERS + SEARCH + STAFF JOIN + PAGINATION)
|--------------------------------------------------------------------------
*/
function getCourses($filters = [], $limit = 20, $offset = 0) {
    $pdo = getDB();

    $sql = "
        SELECT 
            c.*,
            CONCAT(s.FirstName, ' ', s.LastName) AS StaffName
        FROM Course c
        LEFT JOIN Staff s ON c.StaffID = s.StaffID
        WHERE 1 = 1
    ";

    $params = [];

    // Filter: Active / Inactive
    if ($filters['status'] !== '' && isset($filters['status'])) {
        $sql .= " AND c.IsActive = :status";
        $params[':status'] = $filters['status'];
    }

    // Filter: Credits
    if (!empty($filters['credits'])) {
        $sql .= " AND c.Credits = :credits";
        $params[':credits'] = $filters['credits'];
    }

    // Search: name, code, description, instructor
    if (!empty($filters['search'])) {
        $sql .= "
            AND (
                c.CourseName LIKE :search
                OR c.CourseCode LIKE :search
                OR c.Description LIKE :search
                OR CONCAT(s.FirstName, ' ', s.LastName) LIKE :search
            )
        ";
        $params[':search'] = "%" . $filters['search'] . "%";
    }

    $sql .= " ORDER BY c.CourseID DESC LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);

    // Bind dynamic params
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }

    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/*
|--------------------------------------------------------------------------
| TOTAL COUNT FOR PAGINATION
|--------------------------------------------------------------------------
*/
function getCoursesCount($filters = []) {
    $pdo = getDB();

    $sql = "
        SELECT COUNT(*)
        FROM Course c
        LEFT JOIN Staff s ON c.StaffID = s.StaffID
        WHERE 1 = 1
    ";

    $params = [];

    if ($filters['status'] !== '' && isset($filters['status'])) {
        $sql .= " AND c.IsActive = :status";
        $params[':status'] = $filters['status'];
    }

    if (!empty($filters['credits'])) {
        $sql .= " AND c.Credits = :credits";
        $params[':credits'] = $filters['credits'];
    }

    if (!empty($filters['search'])) {
        $sql .= "
            AND (
                c.CourseName LIKE :search
                OR c.CourseCode LIKE :search
                OR c.Description LIKE :search
                OR CONCAT(s.FirstName, ' ', s.LastName) LIKE :search
            )
        ";
        $params[':search'] = "%" . $filters['search'] . "%";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchColumn();
}

/*
|--------------------------------------------------------------------------
| GET A SINGLE COURSE
|--------------------------------------------------------------------------
*/
function getCourseById($id) {
    $pdo = getDB();
    $stmt = $pdo->prepare("
        SELECT 
            c.*,
            s.FirstName,
            s.LastName
        FROM Course c
        LEFT JOIN Staff s ON c.StaffID = s.StaffID
        WHERE c.CourseID = :id
    ");
    $stmt->execute([':id' => $id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/*
|--------------------------------------------------------------------------
| CREATE COURSE
|--------------------------------------------------------------------------
*/
function createCourse($data) {
    $pdo = getDB();
    try {
        $stmt = $pdo->prepare("
            INSERT INTO Course
            (StaffID, CourseName, CourseCode, Description, Credits, Fee, StartDate, IsActive)
            VALUES (:StaffID, :CourseName, :CourseCode, :Description, :Credits, :Fee, :StartDate, 1)
        ");
        $stmt->execute($data);
        return $pdo->lastInsertId();
    } catch (PDOException $e) {

        if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
            return "Course code already exists.";
        }

        return $e->getMessage();
    }
}

/*
|--------------------------------------------------------------------------
| UPDATE COURSE
|--------------------------------------------------------------------------
*/
function updateCourse($id, $data) {
    $pdo = getDB();

    try {
        $data[':id'] = $id;

        $stmt = $pdo->prepare("
            UPDATE Course SET
                StaffID = :StaffID,
                CourseName = :CourseName,
                CourseCode = :CourseCode,
                Description = :Description,
                Credits = :Credits,
                Fee = :Fee,
                StartDate = :StartDate
            WHERE CourseID = :id
        ");

        $stmt->execute($data);
        return true;

    } catch (PDOException $e) {

        if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
            return "Course code already exists.";
        }

        return $e->getMessage();
    }
}

/*
|--------------------------------------------------------------------------
| ACTIVATE / DEACTIVATE COURSE
|--------------------------------------------------------------------------
*/
function deactivateCourse($id) {
    $pdo = getDB();
    return $pdo->prepare("UPDATE Course SET IsActive = 0 WHERE CourseID = ?")
               ->execute([$id]);
}

function activateCourse($id) {
    $pdo = getDB();
    return $pdo->prepare("UPDATE Course SET IsActive = 1 WHERE CourseID = ?")
               ->execute([$id]);
}
?>
