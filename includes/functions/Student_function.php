<?php
include __DIR__ . "/../db/db.config.php";
class Student_function
{
    public static function fetchAssignedStudents()
    {
        global $conn;

        $sql = "SELECT 
                    u.user_id,
                    u.name,
                    c.course_id,
                    c.course_name,
                    u.email,
                    a.status 
                FROM assigned_students a
                JOIN users u ON a.student_id = u.user_id
                JOIN courses c ON a.course_id = c.course_id
                WHERE u.role = 'Student';";

        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($results)) {
            return $results;
        } else {
            return [];
        }
    }
}