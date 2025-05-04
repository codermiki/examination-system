<?php
require_once "config/db.config.php";

class Course_service
{
    public static function addCourse($year, $semester, $course_ids)
    {
        global $conn;

        if (!$year || !$semester || empty($course_ids)) {
            return ['error' => 'Invalid input. Year, semester, and courses are required.'];
        }

        try {
            $stmt = $conn->prepare("INSERT INTO assigned_courses (course_id, year, semester) VALUES (:course_id, :year, :semester)");

            foreach ($course_ids as $course_id) {
                // Optional: Check if already assigned (prevent duplicates)
                $checkStmt = $conn->prepare("SELECT * FROM assigned_courses WHERE course_id = :course_id AND year = :year AND semester = :semester");
                $checkStmt->execute([
                    ':course_id' => $course_id,
                    ':year' => $year,
                    ':semester' => $semester
                ]);

                if ($checkStmt->rowCount() === 0) {
                    $stmt->execute([
                        ':course_id' => $course_id,
                        ':year' => $year,
                        ':semester' => $semester
                    ]);
                }
                // Else: skip duplicate entry
            }

            return ['message' => 'Courses assigned successfully.'];

        } catch (PDOException $e) {
            return ['error' => 'Database error: ' . $e->getMessage()];
        }
    }
    public static function assignInstructor($instructor_id, $course_id)
    {
        global $conn;

        if (!$instructor_id || !$course_id) {
            return ['error' => 'Instructor ID and Course ID are required'];
        }

        $sql = "INSERT INTO assigned_instructors (instructor_id, course_id) VALUES (:instructor_id, :course_id)";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':instructor_id', $instructor_id);
        $stmt->bindParam(':course_id', $course_id);

        if ($stmt->execute()) {
            return ['message' => 'Instructor assigned successfully'];
        } else {
            return ['error' => 'Failed to assign instructor'];
        }
    }


}
