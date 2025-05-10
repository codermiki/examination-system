<?php
require_once "config/db.config.php";

class Instructor_service
{
    public static function assignInstructor($instructor, $course_id)
    {
        global $conn;

        if (!$instructor || !$course_id) {
            return ['error' => 'Instructor ID and Course ID are required'];
        }

        try {
            $assignStmt = $conn->prepare("INSERT INTO assigned_instructors (instructor_id, course_id) VALUES (:instructor_id, :course_id)");


            $checkUserStmt = $conn->prepare("SELECT * FROM users WHERE user_id = :user_id AND role = 'Instructor'");
            $checkUserStmt->execute([
                ':user_id' => $instructor['user_id']
            ]);

            $isExist = $checkUserStmt->fetch(PDO::FETCH_ASSOC);

            //If instructor doesn't exist, insert new instructor
            if (!$isExist) {
                $addUserStmt = $conn->prepare("INSERT INTO users (user_id, name, email, password, role) VALUES (:user_id, :name, :email, :password, 'Instructor')");
                $addUserStmt->execute([
                    ':user_id' => $instructor['user_id'],
                    ':name' => $instructor['name'],
                    ':email' => $instructor['email'],
                    ':password' => $instructor['email']
                ]);
            }

            //  check if already assigned
            $checkAssignedStmt = $conn->prepare("SELECT * FROM assigned_instructors WHERE instructor_id = :instructor_id AND course_id = :course_id");

            $checkAssignedStmt->execute([
                ':student_id' => $instructor["user_id"],
                ':course_id' => $course_id
            ]);

            if ($checkAssignedStmt->rowCount() === 0) {
                $assignStmt->execute([
                    ':student_id' => $instructor["user_id"],
                    ':course_id' => $course_id
                ]);
            }
            return ['message' => 'Students successfully assigned'];
        } catch (PDOException $e) {
            return ['error' => "Failed to Assign Student"];
        }
    }

}
