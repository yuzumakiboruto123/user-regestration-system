<?php
require_once 'db.php';

class StaffModel {
    private $db;

    public function __construct() {
        $this->db = getDB();
    }

    /* FETCH STAFF WITH FILTERS */
    public function getStaff($filters = []) {
        $sql = "SELECT s.*, u.Username 
                FROM Staff s 
                JOIN User u ON s.UserID = u.UserID 
                WHERE 1=1";
        $params = [];

        if (!empty($filters['course'])) {
            $sql .= " AND s.CourseName = ?";
            $params[] = $filters['course'];
        }

        if (!empty($filters['is_active'])) {
            $sql .= " AND s.IsActive = ?";
            $params[] = $filters['is_active'];
        }

        if (!empty($filters['search'])) {
            $s = "%".$filters['search']."%";
            $sql .= " AND (s.FirstName LIKE ? OR s.LastName LIKE ? OR s.Email LIKE ?)";
            $params[] = $s;
            $params[] = $s;
            $params[] = $s;
        }

        $sql .= " ORDER BY s.LastName, s.FirstName";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* GET STAFF BY ID */
    public function getStaffById($id) {
        $sql = "SELECT s.*, u.Username, u.Role
                FROM Staff s
                JOIN User u ON s.UserID = u.UserID
                WHERE s.StaffID = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /* CREATE STAFF */
    public function createStaff($data) {
        $sql = "INSERT INTO Staff (
                    UserID, FirstName, LastName, Email,
                    CourseID, CourseName, Salary, IsActive, HireDate
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $data['UserID'],
            $data['FirstName'],
            $data['LastName'],
            $data['Email'],
            $data['CourseID'],
            $data['CourseName'],
            $data['Salary'],
            $data['IsActive'],
            $data['HireDate']
        ]);

        return $this->db->lastInsertId();
    }

    /* UPDATE STAFF */
    public function updateStaff($id, $data) {
        $sql = "UPDATE Staff SET
                    FirstName=?, LastName=?, Email=?,
                    CourseID=?, CourseName=?, Salary=?,
                    IsActive=?, HireDate=?
                WHERE StaffID=?";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['FirstName'],
            $data['LastName'],
            $data['Email'],
            $data['CourseID'],
            $data['CourseName'],
            $data['Salary'],
            $data['IsActive'],
            $data['HireDate'],
            $id
        ]);
    }

    /* NEW — GET ALL COURSE NAMES */
    public function getAllCourseNames() {
        $sql = "SELECT CourseName FROM Course ORDER BY CourseName ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /* FIX — BACKWARD COMPATIBILITY FOR staff_list.php */
    public function getCourses() {
        return $this->getAllCourseNames();
    }
}
