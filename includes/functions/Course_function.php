<?php
include __DIR__ . "/../db/db.config.php";
class Course_function
{
    public static function fetchCourses()
    {
        global $conn;

        $sql = "SELECT * FROM courses";
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