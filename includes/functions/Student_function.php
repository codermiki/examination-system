<?php
include __DIR__ . "/../db/db.config.php";
class Student_function
{
    public static function assignStudent()
    {
        global $pdo;
        try {
            // Sample input
            $student_id = 'S005';
            $name = 'John Doe';
            $email = 'johndoe@example.com';
            $password = password_hash('default123', PASSWORD_DEFAULT);
            $course_id = 'C003';

            // 1. Check if student exists
            $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ? AND role = 'Student'");
            $stmt->execute([$student_id]);
            $student = $stmt->fetch(PDO::FETCH_ASSOC);

            // 2. If student doesn't exist, insert new student
            if (!$student) {
                $stmt = $pdo->prepare("INSERT INTO users (user_id, name, email, password, role) VALUES (?, ?, ?, ?, 'Student')");
                $stmt->execute([$student_id, $name, $email, $password]);
            }

            // 3. Check if student is already assigned to the course
            $stmt = $pdo->prepare("SELECT * FROM assigned_students WHERE student_id = ? AND course_id = ?");
            $stmt->execute([$student_id, $course_id]);

            if ($stmt->rowCount() === 0) {
                // 4. Assign the student to the course
                $stmt = $pdo->prepare("INSERT INTO assigned_students (student_id, course_id) VALUES (?, ?)");
                $stmt->execute([$student_id, $course_id]);
                return "Student assigned successfully.";
            } else {
                return "Student is already assigned to this course.";
            }
        } catch (PDOException $e) {
            return "Failed to Assign Student";
        }
    }
}