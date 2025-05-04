<?php
require_once "config/db.config.php";

class Student_service
{
    public static function assignStudent($course_id, $student_ids)
    {
        global $conn;

        try {
            $stmt = $conn->prepare("INSERT INTO assigned_students (student_id, course_id) 
                                    VALUES (:student_id, :course_id)");

            foreach ($student_ids as $student_id) {
                // Optional: check if already assigned
                $checkStmt = $conn->prepare("SELECT * FROM assigned_students WHERE student_id = :student_id AND course_id = :course_id");
                $checkStmt->execute([
                    ':student_id' => $student_id,
                    ':course_id' => $course_id
                ]);

                if ($checkStmt->rowCount() === 0) {
                    $stmt->execute([
                        ':student_id' => $student_id,
                        ':course_id' => $course_id
                    ]);
                }
            }

            return ['message' => 'Students successfully assigned to the course.'];
        } catch (PDOException $e) {
            return ['error' => 'Database error: ' . $e->getMessage()];
        }
    }
}
