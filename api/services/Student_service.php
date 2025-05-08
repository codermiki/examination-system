<?php
require_once "config/db.config.php";

class Student_service
{
    public static function assignStudent($course_id, $student_ids)
    {
        global $conn;

        try {
            $sql = "INSERT INTO assigned_students (student_id,name, gender,course_id, year, semester, email) VALUES (:student_id,:name, :gender,  :course_id, :year, :semester, :email)";

            $stmt = $conn->prepare($sql);

            foreach ($student_ids as $student) {
                // Optional: check if already assigned
                $checkStmt = $conn->prepare("SELECT * FROM assigned_students WHERE student_id = :student_id AND course_id = :course_id");
                $checkStmt->execute([
                    ':student_id' => $student["student_id"],
                    ':course_id' => $course_id
                ]);

                if ($checkStmt->rowCount() === 0) {
                    $stmt->execute([
                        ':student_id' => $student["student_id"],
                        ':name' => $student["name"],
                        ':gender' => $student["gender"],
                        ':course_id' => $course_id,
                        ':year' => $student["year"],
                        ':semester' => $student["semester"],
                        ':email' => $student["email"]
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
