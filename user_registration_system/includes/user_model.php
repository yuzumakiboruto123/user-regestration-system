<?php
// ============================================================================
// USER MODEL FILE (HIGHLY COMMENTED VERSION)
// Handles: User CRUD, Admin/Staff/Student creation, pagination, updates,
//          deletion, filters, and all user-related database work.
// ============================================================================


// ------------------------------------------------------------
// INCLUDE REQUIRED SYSTEM FILES
// ------------------------------------------------------------

// db.php → provides getDB() to return global PDO connection
require_once 'db.php';

// audit.php → provides logAction() to save admin/system logs
require_once 'audit.php';


// ------------------------------------------------------------
// GET GLOBAL PDO CONNECTION
// ------------------------------------------------------------
// We load it once and share through $pdo or via getDB() inside functions.
// ------------------------------------------------------------
$pdo = getDB();


// ============================================================================
// FUNCTION: getUsers()
// PURPOSE : Fetch ALL users (NO pagination) with filters + sorting.
// INPUTS  : $search (string), $role (string), $sort (column name), $order (ASC/DESC)
// RETURNS : Array of all matched users
// ============================================================================
function getUsers($search = "", $role = "", $sort = "UserID", $order = "ASC") {

    global $pdo; // use global PDO instance

    // Only allow sorting by specific columns for security
    $allowedSort = ["UserID", "Username", "Role", "CreatedDate", "Email"];
    if (!in_array($sort, $allowedSort)) {
        $sort = "UserID"; // fallback default
    }

    // Validate order (ASC/DESC)
    $order = strtoupper($order) === "DESC" ? "DESC" : "ASC";

    // Base query
    $sql = "SELECT * FROM user WHERE 1 ";
    $params = []; // holds values for prepared statement

    // If search text exists, filter by Username LIKE '%search%'
    if ($search !== "") {
        $sql .= " AND Username LIKE ? ";
        $params[] = "%$search%";
    }

    // If role filter exists (Admin/Staff/Student)
    if ($role !== "") {
        $sql .= " AND Role = ? ";
        $params[] = $role;
    }

    // Add ORDER BY
    $sql .= " ORDER BY $sort $order";

    // Prepare + run query
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    // Return all users
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}



// ============================================================================
// FUNCTION: countAllUsers()
// PURPOSE : Counts number of users matching filters
// INPUTS  : $search, $role
// RETURNS : integer count
// ============================================================================
function countAllUsers($search = "", $role = "") {

    $pdo = getDB(); // fresh PDO instance

    $sql = "SELECT COUNT(*) FROM user WHERE 1 ";
    $params = [];

    // username search
    if ($search !== "") {
        $sql .= " AND Username LIKE ? ";
        $params[] = "%$search%";
    }

    // role filter
    if ($role !== "") {
        $sql .= " AND Role = ? ";
        $params[] = $role;
    }

    // run query
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    // return numeric count
    return (int)$stmt->fetchColumn();
}



// ============================================================================
// FUNCTION: getUsersPaginated()
// PURPOSE : Fetch users with full pagination support
// INPUTS  : search, role, sort, order, page number, items per page
// RETURNS : array containing:
//   - users
//   - total
//   - page
//   - perPage
//   - totalPages
// ============================================================================
function getUsersPaginated($search, $role, $sort, $order, $page, $perPage) {

    $pdo = getDB(); // new DB connection

    // Validate allowed sorting fields
    $allowedSort = ["UserID", "Username", "Email", "Role", "CreatedDate"];
    if (!in_array($sort, $allowedSort)) {
        $sort = "UserID";
    }

    // Validate order direction
    $order = strtoupper($order) === "DESC" ? "DESC" : "ASC";

    // Ensure valid numbers for pagination
    $page    = max(1, (int)$page);
    $perPage = max(1, (int)$perPage);
    $offset  = ($page - 1) * $perPage;

    // Build WHERE clause dynamically
    $where = " WHERE 1 ";
    $params = [];

    if ($search !== "") {
        $where .= " AND Username LIKE ? ";
        $params[] = "%$search%";
    }

    if ($role !== "") {
        $where .= " AND Role = ? ";
        $params[] = $role;
    }

    // -----------------------------------------
    // Step 1: Count total matched rows
    // -----------------------------------------
    $sqlCount = "SELECT COUNT(*) FROM user $where";
    $stm = $pdo->prepare($sqlCount);
    $stm->execute($params);
    $total = (int)$stm->fetchColumn();

    // -----------------------------------------
    // Step 2: Fetch actual paginated results
    // -----------------------------------------
    $sql = "
        SELECT *
        FROM user
        $where
        ORDER BY $sort $order
        LIMIT ? OFFSET ?
    ";

    $stm = $pdo->prepare($sql);

    // Bind dynamic parameters first (search/role)
    $pos = 1;
    foreach ($params as $p) {
        $stm->bindValue($pos++, $p);
    }

    // Now bind limit + offset
    $stm->bindValue($pos++, (int)$perPage, PDO::PARAM_INT);
    $stm->bindValue($pos++, (int)$offset, PDO::PARAM_INT);

    // Execute paginated query
    $stm->execute();

    // Fetch rows
    $users = $stm->fetchAll(PDO::FETCH_ASSOC);

    // Calculate total pages
    $totalPages = max(1, ceil($total / $perPage));

    // Return pagination structure
    return [
        "users"      => $users,
        "total"      => $total,
        "page"       => $page,
        "perPage"    => $perPage,
        "totalPages" => $totalPages
    ];
}



// ============================================================================
// FUNCTION: getUserById()
// PURPOSE : Fetch single user row by UserID
// ============================================================================
function getUserById($id) {

    global $pdo;

    $stmt = $pdo->prepare("SELECT * FROM user WHERE UserID = ?");
    $stmt->execute([$id]);

    return $stmt->fetch(PDO::FETCH_ASSOC);
}



// ============================================================================
// FUNCTION: createUser()
// PURPOSE : Create FULL user + admin/staff/student records
// NOTES   : Uses transactions so all inserts succeed or fail together
// ============================================================================
function createUser($username, $passwordPlain, $role, $extra = []) {

    $pdo = getDB();

    try {
        // ------------------------------------------------------------
        // Step 1: Check if username already exists (prevent duplicates)
        // ------------------------------------------------------------
        $check = $pdo->prepare("SELECT COUNT(*) FROM user WHERE Username = ?");
        $check->execute([$username]);

        if ($check->fetchColumn() > 0) {
            return "exists";
        }

        // Begin transaction
        $pdo->beginTransaction();

        // ------------------------------------------------------------
        // Step 2: Insert into main user table
        // ------------------------------------------------------------
        $email = $extra['email'] ?? ($username . "@school.edu");
        $passwordHash = password_hash($passwordPlain, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("
            INSERT INTO user (Username, PasswordHash, Role, Email, IsActive)
            VALUES (?, ?, ?, ?, 1)
        ");

        $stmt->execute([
            $username,
            $passwordHash,
            $role,
            $email
        ]);

        // get newly created user id
        $userId = (int)$pdo->lastInsertId();

        // ------------------------------------------------------------
        // Step 3: Role-Specific Inserts
        // ------------------------------------------------------------

        // ========== ADMIN EXTRA FIELDS ==========
        if ($role === "Admin") {

            $adminPinPlain = $extra['admin_pin'] ?? null;

            if (!$adminPinPlain) {
                throw new Exception("Admin PIN required");
            }

            $adminPinHash = password_hash($adminPinPlain, PASSWORD_DEFAULT);

            $stmtAdmin = $pdo->prepare("
                INSERT INTO admin
                    (UserID, Username, AdminPINHash, AdminPasswordHash, AdminLevel,
                     Email, LastLogin, CreatedAt, FailedPinAttempts, PinLastChanged, PinLockUntil)
                VALUES
                    (:uid, :uname, :pinHash, :pwdHash, 'Admin',
                     :email, NULL, NOW(), 0, NULL, NULL)
            ");

            $stmtAdmin->execute([
                ':uid'     => $userId,
                ':uname'   => $username,
                ':pinHash' => $adminPinHash,
                ':pwdHash' => $passwordHash,
                ':email'   => $email
            ]);
        }

        // ========== STAFF EXTRA FIELDS ==========
        if ($role === "Staff") {

            $stmtStaff = $pdo->prepare("
                INSERT INTO staff (UserID, FirstName, LastName, Email, CourseName, Salary, HireDate, IsActive)
                VALUES (?, ?, ?, ?, ?, ?, ?, 1)
            ");

            $stmtStaff->execute([
                $userId,
                $extra['first_name'] ?? ucfirst($username),
                $extra['last_name']  ?? "Staff",
                $email,
                $extra['courseName'] ?? null,
                $extra['salary']     ?? null,
                $extra['hire_date']  ?? date("Y-m-d")
            ]);
        }

        // ========== STUDENT EXTRA FIELDS ==========
        if ($role === "Student") {

            $stmtStudent = $pdo->prepare("
                INSERT INTO student (UserID, FirstName, LastName, DateOfBirth, Email, Age, GPA, IsActive)
                VALUES (?, ?, ?, ?, ?, ?, ?, 1)
            ");

            $stmtStudent->execute([
                $userId,
                $extra['first_name'] ?? ucfirst($username),
                $extra['last_name']  ?? "Student",
                $extra['dob']        ?? date("Y-m-d"),
                $email,
                $extra['age'] ?? null,
                $extra['gpa'] ?? null
            ]);
        }

        // ------------------------------------------------------------
        // Step 4: Finish Transaction + Log
        // ------------------------------------------------------------
        $pdo->commit();

        logAction(null, "Created User", "User", $userId, "Username: $username Role: $role");

        return $userId;

    } catch (Exception $e) {

        // rollback if error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        return false;
    }
}



// ============================================================================
// FUNCTION: updateUser()
// PURPOSE : Update username, role, active state, and optional new password
// ============================================================================
function updateUser($id, $username, $password, $role, $isActive) {

    global $pdo;

    // If password provided → update password field too
    if ($password !== "") {

        $stmt = $pdo->prepare("
            UPDATE user
            SET Username = ?, PasswordHash = ?, Role = ?, IsActive = ?
            WHERE UserID = ?
        ");

        return $stmt->execute([
            $username,
            password_hash($password, PASSWORD_DEFAULT),
            $role,
            $isActive,
            $id
        ]);
    }

    // No password change
    $stmt = $pdo->prepare("
        UPDATE user
        SET Username = ?, Role = ?, IsActive = ?
        WHERE UserID = ?
    ");

    return $stmt->execute([
        $username,
        $role,
        $isActive,
        $id
    ]);
}



// ============================================================================
// FUNCTION: updateStaff()
// PURPOSE : Update only staff-specific fields
// ============================================================================
function updateStaff($id, $extra) {

    global $pdo;

    $stmt = $pdo->prepare("
        UPDATE staff
        SET CourseName = ?, Salary = ?
        WHERE UserID = ?
    ");

    return $stmt->execute([
        $extra['courseName'] ?? null,
        $extra['salary']     ?? null,
        $id
    ]);
}



// ============================================================================
// FUNCTION: updateStudent()
// PURPOSE : Update only student-specific fields (such as GPA)
// ============================================================================
function updateStudent($id, $extra) {

    global $pdo;

    $stmt = $pdo->prepare("
        UPDATE student
        SET GPA = ?
        WHERE UserID = ?
    ");

    return $stmt->execute([
        $extra['gpa'] ?? null,
        $id
    ]);
}



// ============================================================================
// FUNCTION: updateAdminExtra()
// PURPOSE : Update only admin PIN (if provided)
// ============================================================================
function updateAdminExtra($id, $extra) {

    global $pdo;

    // No pin update requested → skip
    if (empty($extra['new_admin_pin'])) {
        return true;
    }

    $newPinHash = password_hash($extra['new_admin_pin'], PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("
        UPDATE admin
        SET AdminPINHash = ?, FailedPinAttempts = 0,
            PinLastChanged = NOW(), PinLockUntil = NULL
        WHERE UserID = ?
    ");

    return $stmt->execute([
        $newPinHash,
        $id
    ]);
}
function calculateAndUpdateGPA($studentID) {
    $pdo = getDB();

    // Fetch all final grades + credits
    $sql = "
        SELECT e.FinalGrade, c.Credits
        FROM enrollment e
        INNER JOIN course c ON e.CourseID = c.CourseID
        WHERE e.StudentID = ?
        AND e.FinalGrade IS NOT NULL
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$studentID]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($rows)) {
        // No grades yet → set GPA to NULL
        $pdo->prepare("UPDATE student SET GPA = NULL WHERE StudentID = ?")
            ->execute([$studentID]);
        return null;
    }

    $totalGradePoints = 0;
    $totalCredits = 0;

    foreach ($rows as $r) {
        $grade = floatval($r['FinalGrade']);
        $credits = floatval($r['Credits']);

        // Convert grade to GPA
        $gradePoints = $grade / 25;  // Option A (your choice)

        $totalGradePoints += ($gradePoints * $credits);
        $totalCredits     += $credits;
    }

    // GPA formula
    $gpa = $totalGradePoints / $totalCredits;

    // Round to 2 decimals
    $gpa = round($gpa, 2);

    // Save GPA to student table
    $update = $pdo->prepare("UPDATE student SET GPA = ? WHERE StudentID = ?");
    $update->execute([$gpa, $studentID]);

    return $gpa; // Return calculated GPA
}




// ============================================================================
// FUNCTION: deleteUser()
// PURPOSE : Delete user + admin/staff/student rows in transaction
// ============================================================================
function deleteUser($id) {

    $pdo = getDB();

    try {
        // Start transaction for safe delete
        $pdo->beginTransaction();

        // Delete dependent records first to avoid constraints
        $pdo->prepare("DELETE FROM admin   WHERE UserID = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM staff   WHERE UserID = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM student WHERE UserID = ?")->execute([$id]);

        // Delete main user row
        $stmtUser = $pdo->prepare("DELETE FROM user WHERE UserID = ?");
        $stmtUser->execute([$id]);

        // Commit delete
        $pdo->commit();

        // Return true if at least 1 row deleted
        return ($stmtUser->rowCount() > 0);

    } catch (PDOException $e) {

        // Roll back if something fails
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        return false;
    }
}
