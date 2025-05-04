<?php
// import session configuration
include_once '../config.php';


// Check if the 'action' parameter is set in the GET request
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $role = $_SESSION['role'];

    // Use a switch statement to handle different actions based on the user's role
    switch ($role) {
        case 'instructor':

            switch ($action) {
                case 'instructor_create_exam':
                    include "create_exam.php";
                    break;

                case 'inst_update_password':
                    include "update_password.php";
                    break;

                case 'instructor_import_exam':
                    include "import_exam.php";
                    break;

                case 'instructor_manage_exam':
                    include "manage_exam.php";
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
    }
}
