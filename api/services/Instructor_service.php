<?php
require_once "config/db.config.php";

class Instructor_service
{
    public static function assignInstructor($instructor, $course_id)
    {
        global $conn;

        // Proper input validation
        if (!$instructor || !$course_id) {
            return ['error' => 'Instructor ID and Course ID are required'];
        }

        try {
            // Check if instructor exists
            $checkUserStmt = $conn->prepare("SELECT * FROM users WHERE user_id = :user_id AND role = 'Instructor'");
            $checkUserStmt->execute([
                ':user_id' => $instructor['user_id']
            ]);

            $isExist = $checkUserStmt->fetch(PDO::FETCH_ASSOC);

            // If instructor doesn't exist, insert new instructor
            if (!$isExist) {
                $addUserStmt = $conn->prepare("INSERT INTO users (user_id, name, email, password, role) VALUES (:user_id, :name, :email, :password, 'Instructor')");
                $addUserStmt->execute([
                    ':user_id' => $instructor['user_id'],
                    ':name' => $instructor['name'],
                    ':email' => $instructor['email'],
                    ':password' => $instructor['email']  // Consider hashing in real use
                ]);
            }

            // Check if already assigned
            $checkAssignedStmt = $conn->prepare("SELECT * FROM assigned_instructors WHERE instructor_id = :instructor_id AND course_id = :course_id");
            $checkAssignedStmt->execute([
                ':instructor_id' => $instructor["user_id"],
                ':course_id' => $course_id
            ]);

            if ($checkAssignedStmt->rowCount() === 0) {
                $assignStmt = $conn->prepare("INSERT INTO assigned_instructors (instructor_id, course_id) VALUES (:instructor_id, :course_id)");
                $assignStmt->execute([
                    ':instructor_id' => $instructor["user_id"],
                    ':course_id' => $course_id
                ]);
            }

            return ['message' => 'Instructor successfully assigned'];
        } catch (PDOException $e) {
            return ['error' => "Failed to assign instructor"];
        }
    }


    public static function updateAssignedInstructor($course_id, $instructor_id, $status)
    {
        global $conn;

        try {
            $sql = "UPDATE assigned_instructors SET course_id = :course_id, status = :status WHERE instructor_id = :instructor_id";

            $stmt = $conn->prepare($sql);

            // Optional: check if already assigned
            $checkStmt = $conn->prepare("SELECT * FROM assigned_instructors WHERE instructor_id = :instructor_id");
            $checkStmt->execute([
                ':instructor_id' => $instructor_id
            ]);

            if ($checkStmt->rowCount() > 0) {
                $stmt->execute([
                    ':course_id' => $course_id,
                    ':status' => $status,
                    ':instructor_id' => $instructor_id
                ]);
                return ['message' => 'Instructor Update successfully.'];
            } else {
                return ['error' => 'Instructor Not Found.'];
            }
        } catch (PDOException $e) {
            return ['error' => 'Failed to Update Instructor.'];
        }
    }

    public static function unassignInstructor($instructor_id, $course_id)
    {
        global $conn;

        try {
            $sql = "DELETE FROM assigned_instructors WHERE instructor_id = :instructor_id AND course_id = :course_id";

            // check if there assigned
            $checkStmt = $conn->prepare("SELECT * FROM assigned_instructors WHERE instructor_id = :instructor_id AND course_id = :course_id");
            $checkStmt->execute([
                ':instructor_id' => $instructor_id,
                ':course_id' => $course_id
            ]);

            if ($checkStmt->rowCount() > 0) {
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    ':instructor_id' => $instructor_id,
                    ':course_id' => $course_id
                ]);
            }
            return ['message' => 'Instructor unassigned successfully.'];
        } catch (PDOException $e) {
            return ['error' => "Instructor unassigned Failed {$e}"];
        }
    }

}
