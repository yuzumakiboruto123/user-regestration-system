<?php
//------------------------------------------------------------
// INCLUDE ESSENTIAL SYSTEM HELPERS
//------------------------------------------------------------

// db.php → provides getDB() which returns a PDO connection to DB
require_once 'db.php';

// audit.php → provides logAction() used for admin/user activity logs
require_once 'audit.php';


//------------------------------------------------------------
// FUNCTION: getUserByUsernameAndRole()
// PURPOSE:  Fetch a single user row where BOTH username and role match
// USED IN:  Login form when verifying credentials for a specific role
//------------------------------------------------------------
function getUserByUsernameAndRole($username, $role) {

    // get PDO instance
    $pdo = getDB();

    // prepare statement — ensures SQL injection safety
    $stmt = $pdo->prepare("
        SELECT *
        FROM user
        WHERE Username = ? AND Role = ?
        LIMIT 1
    ");

    // execute with provided values
    $stmt->execute([$username, $role]);

    // return single row (or false)
    return $stmt->fetch(PDO::FETCH_ASSOC);
}



//------------------------------------------------------------
// FUNCTION: getUserByUsername()
// PURPOSE:  Fetch user by username WITHOUT role filtering
// USED IN:  Password reset, logs, username checks
//------------------------------------------------------------
function getUserByUsername($username) {

    $pdo = getDB();

    $stmt = $pdo->prepare("SELECT * FROM user WHERE Username = ? LIMIT 1");
    $stmt->execute([$username]);

    return $stmt->fetch(PDO::FETCH_ASSOC);
}



//------------------------------------------------------------
// FUNCTION: verifyUser()
// PURPOSE:  Validate login credentials:
//           1) Check username + role match
//           2) Ensure user is active
//           3) Password verification
//           4) Update login stats
//           5) Log successful login
//------------------------------------------------------------
function verifyUser($username, $passwordPlain, $role) {

    $pdo = getDB();

    // fetch user row only if active AND role matches
    $stmt = $pdo->prepare("
        SELECT *
        FROM user
        WHERE Username = ? AND Role = ? AND IsActive = 1
    ");

    $stmt->execute([$username, $role]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // verify password using password_verify()
    if ($user && password_verify($passwordPlain, $user['PasswordHash'])) {

        // update login statistics
        $pdo->prepare("
            UPDATE user 
            SET LoginCount = LoginCount + 1,
                LastLogin = NOW()
            WHERE UserID = ?
        ")->execute([$user['UserID']]);

        // log successful login events
        logAction($user['UserID'], "Login Successful", "User", $user['UserID'], "Correct credentials");

        return $user; // return full user row to login controller
    }

    // login failed
    return false;
}



//------------------------------------------------------------
// FUNCTION: createPasswordResetToken()
// PURPOSE:
//   1) Find user by (username OR email OR staff email OR student email)
//   2) Remove old pending reset requests
//   3) Create NEW reset token valid for 30 minutes
//   4) Store token in passwordreset table
// RETURNS: token string OR false if user not found
//------------------------------------------------------------
function createPasswordResetToken($usernameOrEmail) {

    try {
        $pdo = getDB();

        // find user by multiple possible identifiers
        $stmt = $pdo->prepare("
            SELECT u.UserID
            FROM user u
            LEFT JOIN student s ON u.UserID = s.UserID
            LEFT JOIN staff st ON u.UserID = st.UserID
            WHERE u.Username = :ue OR s.Email = :ue OR st.Email = :ue
            LIMIT 1
        ");

        $stmt->execute([':ue' => $usernameOrEmail]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // if user not found — exit early
        if (!$user) return false;

        // generate secure random 64-character hex token
        $token     = bin2hex(random_bytes(32));

        // calculate expiration timestamp (30 mins from now)
        $expiresAt = date('Y-m-d H:i:s', strtotime('+30 minutes'));

        // delete any existing pending token for same user
        $pdo->prepare("
            DELETE FROM passwordreset
            WHERE UserID = :uid AND Status = 'Pending'
        ")->execute(['uid' => $user['UserID']]);

        // insert fresh token
        $insert = $pdo->prepare("
            INSERT INTO passwordreset (UserID, Token, ExpiresAt, Status)
            VALUES (:uid, :token, :exp, 'Pending')
        ");

        $insert->execute([
            ':uid'   => $user['UserID'],
            ':token' => $token,
            ':exp'   => $expiresAt
        ]);

        return $token; // return token so UI can show reset link

    } catch (PDOException $e) {
        // silently fail for security
        return false;
    }
}



//------------------------------------------------------------
// FUNCTION: verifyPasswordResetToken()
// PURPOSE:
//   1) Expire all outdated pending tokens
//   2) Get reset token details IF STILL valid & pending
//------------------------------------------------------------
function verifyPasswordResetToken($token) {

    $pdo = getDB();

    // expire old tokens automatically
    $pdo->prepare("
        UPDATE passwordreset 
        SET Status = 'Expired'
        WHERE ExpiresAt < NOW() AND Status = 'Pending'
    ")->execute();

    // fetch valid token
    $stmt = $pdo->prepare("
        SELECT pr.*, u.Username
        FROM passwordreset pr
        JOIN user u ON pr.UserID = u.UserID
        WHERE pr.Token = :token AND pr.Status = 'Pending'
    ");

    $stmt->execute([':token' => $token]);

    return $stmt->fetch(PDO::FETCH_ASSOC);
}



//------------------------------------------------------------
// FUNCTION: resetPassword()
// PURPOSE:
//   1) Validate token
//   2) Update password for matching user
//   3) Mark reset token as USED
//------------------------------------------------------------
function resetPassword($token, $newPassword) {

    $pdo = getDB();

    // verify token again for safety
    $tokenData = verifyPasswordResetToken($token);

    if (!$tokenData) return false;

    // update password hash
    $pdo->prepare("
        UPDATE user 
        SET PasswordHash = :pw 
        WHERE UserID = :uid
    ")->execute([
        ':pw'  => password_hash($newPassword, PASSWORD_DEFAULT),
        ':uid' => $tokenData['UserID']
    ]);

    // mark token as used
    $pdo->prepare("
        UPDATE passwordreset
        SET Status = 'Used', UsedAt = NOW()
        WHERE Token = :t
    ")->execute([':t' => $token]);

    return true;
}



//------------------------------------------------------------
// FUNCTION: getPendingUsers()
// PURPOSE:  Fetch all users who are NOT ACTIVE (IsActive = 0)
// USED IN: approve_user.php
//------------------------------------------------------------
function getPendingUsers() {

    $pdo = getDB();

    $stmt = $pdo->query("
        SELECT UserID, Username, Role, CreatedDate
        FROM user
        WHERE IsActive = 0
        ORDER BY CreatedDate DESC
    ");

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}



//------------------------------------------------------------
// FUNCTION: activateUser()
// PURPOSE:
//   1) Activate "user" table record
//   2) Activate matching staff/student record (if exists)
//   3) Log the approval event
// NOTES:
//   - Uses transaction so ALL updates happen together
//------------------------------------------------------------
function activateUser($userId) {

    $pdo = getDB();

    try {
        $pdo->beginTransaction();

        // set active status
        $pdo->prepare("UPDATE user    SET IsActive = 1 WHERE UserID = ?")->execute([$userId]);
        $pdo->prepare("UPDATE staff   SET IsActive = 1 WHERE UserID = ?")->execute([$userId]);
        $pdo->prepare("UPDATE student SET IsActive = 1 WHERE UserID = ?")->execute([$userId]);

        $pdo->commit();

        // log admin approval
        logAction(null, "User approved", "User", $userId, "Approved by Admin");

        return true;

    } catch (PDOException $e) {
        $pdo->rollBack();
        return false;
    }
}



//------------------------------------------------------------
// FUNCTION: deactivateUser()
// PURPOSE:
//   1) Disable user + staff + student records
//   2) Log deactivation event
//   3) Use transaction for atomic update
//------------------------------------------------------------
function deactivateUser($userId) {

    $pdo = getDB();

    try {
        $pdo->beginTransaction();

        $pdo->prepare("UPDATE user    SET IsActive = 0 WHERE UserID = ?")->execute([$userId]);
        $pdo->prepare("UPDATE staff   SET IsActive = 0 WHERE UserID = ?")->execute([$userId]);
        $pdo->prepare("UPDATE student SET IsActive = 0 WHERE UserID = ?")->execute([$userId]);

        $pdo->commit();

        logAction(null, "User deactivated", "User", $userId, "Disabled by Admin");

        return true;

    } catch (PDOException $e) {
        $pdo->rollBack();
        return false;
    }
}



//------------------------------------------------------------
// FUNCTION: countPendingUsers()
// PURPOSE:  Count number of users waiting for admin approval
//------------------------------------------------------------
function countPendingUsers() {

    $pdo = getDB();

    return $pdo->query("SELECT COUNT(*) FROM user WHERE IsActive = 0")->fetchColumn();
}



//------------------------------------------------------------
// FUNCTION: createUser()
// PURPOSE:
//   ● Insert new user into database
//   ● Hash password
//   ● Ensure column names match EXACTLY with your DB
// RETURNS:
//   new UserID
//------------------------------------------------------------
function createUser($username, $password, $role, $email = null) {

    global $pdo; // uses global PDO from connecting script

    // hash password safely
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    // insert new record into user table
    $stmt = $pdo->prepare("
        INSERT INTO `user` 
        (Username, Email, PasswordHash, Role, ProfileImage, LoginCount, LastLogin, IsActive, CreatedDate)
        VALUES (?, ?, ?, ?, NULL, 0, NULL, 1, NOW())
    ");

    $stmt->execute([
        $username,
        $email,
        $passwordHash,
        $role
    ]);

    // return new user ID
    return $pdo->lastInsertId();
}

?>
