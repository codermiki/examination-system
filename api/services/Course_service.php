<?php
require_once "config/db.config.php";

class Course_service
{
    public static function addCourse($courses)
    {
        global $conn;

        if (empty($courses)) {
            return ['error' => 'Invalid input. courses are required.'];
        }

        try {
            $stmt = $conn->prepare("INSERT INTO courses (course_id,course_name) VALUES (:course_id,:course_name)");

            foreach ($courses as $course) {
                // Check if already assigned (prevent duplicates)
                $checkStmt = $conn->prepare("SELECT * FROM courses WHERE course_id = :course_id");
                $checkStmt->execute([
                    ':course_id' => $course["course_id"]
                ]);

                if ($checkStmt->rowCount() === 0) {
                    $stmt->execute([
                        ':course_id' => $course["course_id"],
                        ':course_name' => $course["course_name"]
                    ]);
                }
                // Else: skip duplicate entry
            }

            return ['message' => 'Courses assigned successfully.'];

        } catch (PDOException $e) {
            return ['error' => "Failed to assign Courses {$e}"];
        }
    }

    public static function deleteCourse($course_id)
    {
        global $conn;

        if (!$course_id) {
            return ['error' => 'Invalid input. courses are required.'];
        }

        try {
            // Check if there
            $checkStmt = $conn->prepare("SELECT * FROM courses WHERE course_id =  :course_id");
            $checkStmt->execute([
                ':course_id' => $course_id
            ]);

            if ($checkStmt->rowCount() > 0) {
                $addStmt = $conn->prepare("DELETE FROM courses WHERE course_id = :course_id");

                $addStmt->execute([
                    ':course_id' => $course_id
                ]);
            } else {
                return ['message' => 'Course not found.'];
            }

            return ['message' => 'Course deleted successfully.'];

        } catch (PDOException $e) {
            return ['error' => "Failed to delete Course"];
        }
    }
}
