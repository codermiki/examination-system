<?php
include __DIR__ . "/../db/db.config.php";
class Exam_function
{
    public static function activeExams()
    {
        global $conn;

        $sql = "SELECT exam_id, exam_title FROM exams WHERE status = 'Active'";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($result)) {
            return $result;
        } else {
            return [];
        }
    }
    public static function scheduledExams()
    {
        global $conn;

        $sql = "SELECT 
                    e.exam_id,
                    c.course_name,
                    e.exam_title,
                    s.scheduled_date,
                    e.duration_minutes,
                    e.status
                FROM 
                    exams e
                JOIN 
                    courses c ON e.course_id = c.course_id
                INNER JOIN 
                    exam_schedules s ON e.exam_id = s.exam_id;
                ";

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