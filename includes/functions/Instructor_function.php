<?php
include __DIR__ . "/../db/db.config.php";
class Instructor_function
{
    public static function fetchInstructors()
    {
        global $conn;

        $sql = "SELECT 
                    u.user_id,
                    u.name,
                    c.course_id,
                    c.course_name,
                    u.email,
                    a.status 
                FROM assigned_instructors a
                JOIN users u ON a.instructor_id = u.user_id
                JOIN courses c ON a.course_id = c.course_id
                WHERE u.role = 'Instructor';";

        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($result)) {
            return $result;
        } else {
            return [];
        }
    }
}