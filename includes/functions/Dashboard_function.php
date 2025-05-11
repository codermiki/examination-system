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

        // 1. Count users by role
        $sql_users = "SELECT role, COUNT(*) AS user_count FROM users GROUP BY role;";
        $stmt_users = $conn->prepare($sql_users);
        $stmt_users->execute();
        $user_results = $stmt_users->fetchAll(PDO::FETCH_ASSOC);

        foreach ($user_results as $row) {
            $role = $row['role'];
            if (isset($data[$role])) {
                $data[$role] = (int) $row['user_count'];
            }
        }

        // 2. Count total courses
        $sql_courses = "SELECT COUNT(*) AS total_courses FROM courses;";
        $stmt_courses = $conn->prepare($sql_courses);
        $stmt_courses->execute();
        $row_courses = $stmt_courses->fetch(PDO::FETCH_ASSOC);

        $data['total_courses'] = (int) $row_courses['total_courses'];

        // 3. Count upcoming exams
        $sql_exams = "SELECT COUNT(*) AS upcoming_exams FROM exam_schedules WHERE scheduled_date >= CURDATE();";
        $stmt_exams = $conn->prepare($sql_exams);
        $stmt_exams->execute();
        $row_exams = $stmt_exams->fetch(PDO::FETCH_ASSOC);

        $data['upcoming_exams'] = (int) $row_exams['upcoming_exams'];

        return $data;
    }
}
