<?php
// handle_action.php
// This script handles AJAX requests from the sidebar to load content dynamically
// and also handles form submissions from dynamically loaded forms.

include_once '../config.php';
include_once '../includes/db/db.config.php';

// Start the session if it hasn't been started already
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['email']) || !isset($_SESSION['role']) || !isset($_SESSION['user_id'])) {
    echo '<p>Access denied. Please log in.</p>';
    exit();
}

// Check if the 'action' parameter is set in the GET request
if (isset($_GET['action'])) {
    $action = $_GET['action']; // Get the requested action
    $role = $_SESSION['role']; // Get the user's role from the session
    $userId = $_SESSION['user_id']; // Get the logged-in user's user_id (can be student or instructor)


    // Use a switch statement to handle different actions based on the user's role
    switch ($role) {
        case 'admin':
            // Handle actions specific to the admin role
            switch ($action) {
                case 'dashboard':
                    // Include or generate admin dashboard content
                    echo '<h2>Admin Dashboard</h2><p>Admin dashboard content goes here.</p>';
                    break;
                case 'admin_add_student':
                    // Include the file for adding a student
                    include '../includes/admin/add_student.php';
                    break;
                case 'admin_manage_student':
                    // Include the file for managing students
                    include '../includes/admin/manage_student.php';
                    break;
                case 'admin_assign_instructor':
                    // Include the file for assigning instructors
                    echo '<h2>Assign Instructor</h2><p>Assign instructor form goes here.</p>'; // Placeholder
                    break;
                case 'admin_manage_instructor':
                    // Include the file for managing instructors
                    echo '<h2>Manage Instructors</h2><p>Manage instructors interface goes here.</p>'; // Placeholder
                    break;
                case 'admin_add_course':
                     // Include the file for adding a course
                    echo '<h2>Add Course</h2><p>Add course form goes here.</p>'; // Placeholder
                    break;
                case 'admin_manage_course':
                    // Include the file for managing courses
                    echo '<h2>Manage Courses</h2><p>Manage courses interface goes here.</p>'; // Placeholder
                    break;
                case 'admin_schedule_exam':
                    // Include the file for scheduling exams (admin)
                     echo '<h2>Schedule Exam</h2><p>Schedule exam form goes here (Admin view).</p>'; // Placeholder
                    break;
                case 'admin_manage_schedule':
                    // Include the file for managing exam schedules (admin)
                     echo '<h2>Manage Schedule</h2><p>Manage exam schedules interface goes here (Admin view).</p>'; // Placeholder
                    break;
                case 'admin_all_feedbacks':
                    // Include the file for viewing all feedbacks (admin)
                     echo '<h2>All Feedbacks</h2><p>List of all feedbacks goes here (Admin view).</p>'; // Placeholder
                    break;
                default:
                    // Action not found for admin
                    echo '<p>Admin action not found.</p>';
                    break;
            }
            break;

        case 'instructor':
             // Use instructorId for instructor-specific actions
            $instructorId = $userId;
            // Handle actions specific to the instructor role
            switch ($action) {
                 case 'dashboard':
                    // Include or generate instructor dashboard content
                    echo '<h2>Instructor Dashboard</h2><p>Instructor dashboard content goes here.</p>';
                    break;
                case 'instructor_create_exam':
                    // Include the file that contains the "Create Exam" form HTML
                    include '../includes/instructor/create_exam.php';
                    break;
                case 'instructor_import_exam':
                    // Include the file for importing exams
                    include '../includes/instructor/import_exam.php';
                    break;
                case 'instructor_manage_exam':
                    // Include the file for managing exams
                    include '../includes/instructor/manage_exam.php';
                    break;
                case 'instructor_view_exam':
                    // Include the file for viewing exams
                    include '../includes/instructor/view_exam.php';
                    break;
                 case 'instructor_edit_exam':
                    // Include the file for editing exams (displays the form)
                    include '../includes/instructor/edit_exam.php';
                    break;
                 case 'instructor_manage_questions':
                    // Include the file for managing questions
                    include '../includes/instructor/manage_questions.php';
                    break;
                 // You will need to create create_question.php and edit_question.php
                 case 'instructor_create_question':
                     echo '<h2>Create Question</h2><p>Create question form goes here.</p>'; // Placeholder
                     // include '../includes/instructor/create_question.php';
                     break;
                 case 'instructor_edit_question':
                     echo '<h2>Edit Question</h2><p>Edit question form goes here.</p>'; // Placeholder
                     // include '../includes/instructor/edit_question.php';
                     break;


                // --- Start: Handle Instructor Form Submissions ---
                case 'instructor_create_exam_submit':
                    // Include the file that processes the create form submission
                    // Pass $pdo and $instructorId to the included file
                    $pdo = $pdo; // Assuming $pdo is available globally or from config.php
                    $instructorId = $userId;
                    include '../includes/instructor/process_create_exam.php';
                    break;

                case 'instructor_edit_exam_submit':
                     // Include the file that processes the edit form submission
                    // Pass $pdo and $instructorId to the included file
                    $pdo = $pdo;
                    $instructorId = $userId;
                    include '../includes/instructor/process_edit_exam.php'; // Include the new file
                    break;
                // Add cases for other instructor form submissions (e.g., process_create_question.php)
                // --- End: Handle Instructor Form Submissions ---


                default:
                    // Action not found for instructor
                    echo '<p>Instructor action not found.</p>';
                    break;
            }
            break;

        case 'student':
             // Use studentId for student-specific actions
            $studentId = $userId;
            // Handle actions specific to the student role
            switch ($action) {
                 case 'dashboard':
                    // Include the student dashboard file
                    include '../includes/student/student_dashboard.php';
                    break;
                case 'student_upcoming_exams':
                    // Include the upcoming exams/schedule file
                    include '../includes/student/student_upcoming_exams.php';
                    break;
                 case 'student_exam_schedule':
                     // This action might also load student_upcoming_exams.php
                     // or a dedicated schedule view if different.
                     // For now, let's include the same file.
                    include '../includes/student/student_upcoming_exams.php';
                    break;
                 case 'student_take_exam':
                     // Include the take exam placeholder file
                    include '../includes/student/student_take_exam.php';
                    break;
                 case 'student_taken_exams':
                     // Include the taken exams list file
                    include '../includes/student/student_taken_exams.php';
                    break;
                 case 'student_view_result':
                     // Include the view result file
                    include '../includes/student/student_view_result.php';
                    break;
                 case 'student_add_feedback':
                     // Include the add feedback file
                    include '../includes/student/student_add_feedback.php';
                    break;

                // --- Start: Handle Student Form Submissions ---
                 case 'student_submit_exam':
                     // Include the file that processes exam submission
                     echo '<h2>Submit Exam</h2><p>Exam submission processing logic goes here.</p>'; // Placeholder
                     // include '../includes/student/process_submit_exam.php'; // You'll need to create this file
                     break;
                 case 'student_add_feedback_submit':
                     // Include the file that processes feedback submission
                     // Pass $pdo and $studentId to the included file
                     $pdo = $pdo; // Assuming $pdo is available globally or from config.php
                     $studentId = $userId;
                     include '../includes/student/process_add_feedback.php'; // You'll need to create this file
                     break;
                 // Add cases for other student form submissions
                 // --- End: Handle Student Form Submissions ---

                default:
                    // Action not found for student
                    echo '<p>Student action not found.</p>';
                    break;
            }
            break;

        default:
            // Role not recognized
            echo '<p>Invalid user role.</p>';
            break;
    }
} else {
    // Default content to display if no action is specified
    echo '<h2>Welcome!</h2><p>Select an option from the sidebar.</p>';
}
?>
