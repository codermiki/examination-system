<?php
// import session configuration
include_once '../../config.php';

if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $role = $_SESSION['role'];

    switch ($role) {
        case 'admin':
            switch ($action) {
                case 'dashboard':
                    include "dashboard.php";
                    break;

                case 'admin_add_student':
                    include "add_students.php";
                    break;
                case 'first_login_update_password':
                    include "first_login_update_password.php";
                    break;
                case 'admin_manage_student':
                    include "manage_student.php";
                    break;

                case 'admin_assign_instructor':
                    echo '<h2>Assign Instructor</h2><p>Assign instructor form goes here.</p>';
                    break;

                case 'admin_manage_instructor':
                    echo '<h2>Manage Instructors</h2><p>Manage instructors interface goes here.</p>';
                    break;

                case 'admin_add_course':
                    echo '<h2>Add Course</h2><p>Add course form goes here.</p>';
                    break;

                case 'admin_manage_course':
                    echo '<h2>Manage Courses</h2><p>Manage courses interface goes here.</p>';
                    break;

                case 'admin_schedule_exam':
                    // Include the file for scheduling exams (admin)
                    echo '<h2>Schedule Exam</h2><p>Schedule exam form goes here (Admin view).</p>';
                    break;

                case 'admin_manage_schedule':
                    // Include the file for managing exam schedules (admin)
                    echo '<h2>Manage Schedule</h2><p>Manage exam schedules interface goes here (Admin view).</p>';
                    break;

                case 'admin_all_feedbacks':
                    // Include the file for viewing all feedbacks (admin)
                    echo '<h2>All Feedbacks</h2><p>List of all feedbacks goes here (Admin view).</p>';
                    break;

                default:
                    // Action not found for admin
                    echo '<p>Admin action not found.</p>';
                    break;
            }
            break;

        case 'instructor':
            switch ($action) {
                case 'instructor_create_exam':
                    include "create_exam.php";
                    break;
                case 'instructor_update_password';
                    include "update_password.php";
                    break;
                case 'instructor_import_exam':
                    include "import_exam.php";
                    break;
                case 'first_login_update_password':
                    include "first_login_update_password.php";
                    break;

                case 'instructor_create_exam_submit':
                    include "process_create_exam.php";
                    break;

                case 'instructor_view_exam':
                    include "view_exam.php";
                    break;

                case 'instructor_edit_exam':
                    include "edit_exam.php";
                    break;

                case 'instructor_edit_exam_submit':
                    include "process_edit_exam.php";
                    break;

                case 'instructor_manage_questions':
                    include "manage_questions.php";
                    break;

                case 'instructor_exam_report':
                    echo '<h2>Exam Report</h2><p>Exam report content goes here.</p>';
                    break;

                case 'instructor_feedbacks':
                    echo '<h2>Instructor Feedbacks</h2><p>Instructor feedbacks go here.</p>';
                    break;

                default:
                    echo '<p>page not found.</p>';
                    break;
            }
            break;
            case 'student':
            switch ($action) {
                case 'dashboard':
                    include "student_dashboard.php";
                    break;

                case 'student_upcoming_exams':
                    include "student_upcoming_exams.php";
                    break;
                case 'first_login_update_password':
                    include "first_login_update_password.php";
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
            // Role not recognized
            echo '<p>page not found.</p>';
            break;
    }
}