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

    public static function scheduledExamsPerStudent($user_id)
    {
        global $conn;

        $sql = "SELECT 
                e.exam_id,
                e.exam_title,
                c.course_name,
                es.scheduled_date,
                e.duration_minutes
            FROM assigned_students AS a
            JOIN courses AS c ON a.course_id = c.course_id
            JOIN exams AS e ON e.course_id = c.course_id
            JOIN exam_schedules AS es ON es.exam_id = e.exam_id
            WHERE a.student_id = :user_id
              AND a.status = 'Active'
              AND e.status = 'Active'
              AND DATE_ADD(es.scheduled_date, INTERVAL e.duration_minutes MINUTE) > NOW()
            ORDER BY es.scheduled_date ASC;";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':user_id' => $user_id
        ]);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result ?: [];
    }

    public static function takenExamsPerStudent($user_id)
    {
        global $conn;

        $sql = "SELECT 
                    e.exam_id,
                    c.course_name,
                    e.exam_title,
                    e.total_marks,
                    ses.taken_on,
                    ses.score,
                    e.duration_minutes
                FROM student_exam_status AS ses
                JOIN exams AS e ON ses.exam_id = e.exam_id
                JOIN courses AS c ON e.course_id = c.course_id
                WHERE ses.student_id = :user_id 
                AND ses.has_taken = TRUE;
                ";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':user_id' => $user_id
        ]);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($result)) {
            return $result;
        } else {
            return [];
        }
    }

    public static function examStart($exam_id)
    {
        global $conn;

        $sql = "SELECT scheduled_date FROM exam_schedules WHERE exam_id = :exam_id;";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':exam_id' => $exam_id
        ]);
        $examStart = $stmt->fetchColumn();

        if ($examStart) {
            return $examStart;
        } else {
            return null;
        }
    }

    public static function examDuration($exam_id)
    {
        global $conn;

        $sql = "SELECT duration_minutes FROM exams WHERE exam_id = :exam_id";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':exam_id' => $exam_id
        ]);
        $duration_minutes = $stmt->fetchColumn();

        if ($duration_minutes) {
            return $duration_minutes;
        } else {
            return null;
        }
    }
}