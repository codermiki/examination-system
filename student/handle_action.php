<?php
// import session configuration
include_once '../config.php';

if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $role = $_SESSION['role'];
    $userId = $_SESSION['user_id'];

    switch ($role) {
        case 'student':
            $studentId = $userId;
            switch ($action) {
                case 'dashboard':
                    include "student_dashboard.php";
                    break;

                    case 'stud_update_password':
                    include "update_password.php";
                    break;

                case 'student_upcoming_exams':
                    include "student_upcoming_exams.php";
                    break;

                case 'student_exam_schedule':
                    include "student_upcoming_exams.php";
                    break;

                case 'student_take_exam':
                    include "student_take_exam.php";
                    break;

                case 'student_taken_exams':
                    include "student_taken_exams.php";
                    break;

                case 'student_view_result':
                    include "student_view_result.php";
                    break;

                case 'student_add_feedback':
                    include "student_add_feedback.php";
                    break;

                case 'student_submit_exam':
                    include "process_submit_exam.php";
                    break;

                case 'student_add_feedback_submit':
                    $studentId = $userId;
                    include "process_add_feedback.php";
                    break;

                default:
                    echo '<p>page not found.</p>';
                    break;
            }
            break;

        default:
            echo '<p>Invalid page request</p>';
            break;
    }
}