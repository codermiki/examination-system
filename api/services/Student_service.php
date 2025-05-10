<?php
require_once "config/db.config.php";

class Student_service
{
    public static function assignStudent($course_id, $student_ids)
    {
        global $conn;

        try {
            $sql = "INSERT INTO assigned_students (student_id, course_id) VALUES (:student_id, :course_id)";

            $assignStmt = $conn->prepare($sql);

            foreach ($student_ids as $student) {
                $checkUserStmt = $conn->prepare("SELECT * FROM users WHERE user_id = ? AND role = 'Student'");
                $checkUserStmt->execute([$student_id]);
                $student = $checkUserStmt->fetch(PDO::FETCH_ASSOC);

                // 2. If student doesn't exist, insert new student
                if (!$student) {
                    $addUserStmt = $conn->prepare("INSERT INTO users (user_id, name, email, password, role) VALUES (?, ?, ?, ?, 'Student')");
                    $addUserStmt->execute([$student_id, $name, $email, $password]);
                }


                // Optional: check if already assigned
                $checkAssignedStmt = $conn->prepare("SELECT * FROM assigned_students WHERE student_id = :student_id AND course_id = :course_id");
                $checkAssignedStmt->execute([
                    ':student_id' => $student["student_id"],
                    ':course_id' => $course_id
                ]);

                if ($checkAssignedStmt->rowCount() === 0) {
                    $assignStmt->execute([
                        ':student_id' => $student["student_id"],
                        ':course_id' => $course_id
                    ]);
                }
            }

            return ['message' => 'Students successfully assigned to the course.'];
        } catch (PDOException $e) {
            return ['error' => 'Database error: ' . $e->getMessage()];
        }
    }
    public static function updateAssignedStudent($course_id, $student_id, $status)
    {
        global $conn;

        try {
            $sql = "UPDATE assigned_students SET course_id = :course_id, status = :status WHERE student_id = :student_id";

            $stmt = $conn->prepare($sql);

            // Optional: check if already assigned
            $checkStmt = $conn->prepare("SELECT * FROM assigned_students WHERE student_id = :student_id");
            $checkStmt->execute([
                ':student_id' => $student_id
            ]);

            if ($checkStmt->rowCount() > 0) {
                $stmt->execute([
                    ':course_id' => $course_id,
                    ':status' => $status,
                    ':student_id' => $student_id
                ]);
            }


            return ['message' => 'Student Update successfully.'];
        } catch (PDOException $e) {
            return ['error' => 'Database error: ' . $e->getMessage()];
        }
    }
    public static function unassignStudent($student_id, $course_id)
    {
        global $conn;

        try {
            $sql = "DELETE FROM assigned_students WHERE student_id = :student_id AND course_id = :course_id";

            $stmt = $conn->prepare($sql);

            // Optional: check if already assigned
            $checkStmt = $conn->prepare("SELECT * FROM assigned_students WHERE student_id = :student_id AND course_id = :course_id");
            $checkStmt->execute([
                ':student_id' => $student_id,
                ':course_id' => $course_id
            ]);

            if ($checkStmt->rowCount() > 0) {
                $stmt->execute([
                    ':student_id' => $student_id,
                    ':course_id' => $course_id
                ]);
            }


            return ['message' => 'Student unassigned successfully.'];
        } catch (PDOException $e) {
            return ['error' => 'Database error: ' . $e->getMessage()];
        }
    }
}
