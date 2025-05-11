<?php
include __DIR__ . "/../db/db.config.php";
class Feedback_function
{
    public static function fetchFeedbacks()
    {
        global $conn;

        $sql = "SELECT 
                    u.name,
                    e.exam_title,
                    c.course_name,
                    f.feedback_text,
                    f.rate,
                    f.created_at 
                FROM feedbacks f
                JOIN users u ON f.student_id = u.user_id
                JOIN exams e ON f.exam_id = e.exam_id
                JOIN courses c ON e.course_id = c.course_id
                WHERE u.role = 'Instructor'
                ORDER BY f.created_at DESC";
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