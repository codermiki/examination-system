<?php
include __DIR__ . "/../db/db.config.php";

class Dashboard_function
{
    public static function fetchDashboardData()
    {
        global $conn;

        $data = [
            'Admin' => 0,
            'Student' => 0,
            'Instructor' => 0,
            'total_courses' => 0,
            'upcoming_exams' => 0
        ];

        // 1. Count Admins from users table
        $sql_admins = "SELECT COUNT(*) AS admin_count FROM users WHERE role = 'Admin';";
        $stmt_admins = $conn->prepare($sql_admins);
        $stmt_admins->execute();
        $data['Admin'] = (int) $stmt_admins->fetchColumn();

        // 2. Count assigned students
        $sql_students = "SELECT COUNT(DISTINCT student_id) AS student_count FROM assigned_students;";
        $stmt_students = $conn->prepare($sql_students);
        $stmt_students->execute();
        $data['Student'] = (int) $stmt_students->fetchColumn();

        // 3. Count assigned instructors
        $sql_instructors = "SELECT COUNT(DISTINCT instructor_id) AS instructor_count FROM assigned_instructors;";
        $stmt_instructors = $conn->prepare($sql_instructors);
        $stmt_instructors->execute();
        $data['Instructor'] = (int) $stmt_instructors->fetchColumn();

        // 4. Count total courses
        $sql_courses = "SELECT COUNT(*) AS total_courses FROM courses;";
        $stmt_courses = $conn->prepare($sql_courses);
        $stmt_courses->execute();
        $data['total_courses'] = (int) $stmt_courses->fetchColumn();

        // 5. Count upcoming exams
        $sql_exams = "SELECT COUNT(*) AS upcoming_exams FROM exam_schedules WHERE scheduled_date >= CURDATE();";
        $stmt_exams = $conn->prepare($sql_exams);
        $stmt_exams->execute();
        $data['upcoming_exams'] = (int) $stmt_exams->fetchColumn();

        return $data;
    }

}
