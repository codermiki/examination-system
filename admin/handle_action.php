<?php
// handle_action.php
// This script handles AJAX requests from the sidebar to load content dynamically

include_once '../config.php';

// Start the session if it hasn't been started already
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Basic security check: Ensure the user is logged in and has a role
if (!isset($_SESSION['email']) || !isset($_SESSION['role'])) {
    echo '<p>Access denied. Please log in.</p>';
    exit();
}

// Check if the 'action' parameter is set in the GET request
if (isset($_GET['action'])) {
    $action = $_GET['action']; // Get the requested action
    $role = $_SESSION['role']; // Get the user's role from the session

    // Use a switch statement to handle different actions based on the user's role
    switch ($role) {
        case 'admin':
            // Handle actions specific to the admin role
            switch ($action) {
                case 'dashboard':
                    include "dashboard.php";
                    break;
                case 'admin_add_student':
                    // Include the file for adding a student
                    include "add_student.php";
                    break;
                case 'admin_manage_student':
                    // Include the file for managing students
                    include "manage_student.php";
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
            // Handle actions specific to the instructor role
            switch ($action) {
                 case 'dashboard':
                    // Include or generate instructor dashboard content
                    echo '<h2>Instructor Dashboard</h2><p>Instructor dashboard content goes here.</p>';
                    break;
                case 'instructor_create_exam':
                    // Include the file that contains the "Create Exam" form/logic
                    include "create_exam.php";
                    break;
                case 'instructor_import_exam':
                    // Include the file for importing exams
                     echo '<h2>Import Exam</h2><p>Import exam form goes here.</p>'; // Placeholder
                    break;
                case 'instructor_manage_exam':
                    // Include the file for managing exams
                     echo '<h2>Manage Exams</h2><p>Manage exams interface goes here.</p>'; // Placeholder
                    break;
                case 'instructor_view_exam':
                    // Include the file for viewing exams
                     echo '<h2>View Exam</h2><p>View exam details goes here.</p>'; // Placeholder
                    break;
                case 'instructor_exam_report':
                    // Include the file for exam reports
                     echo '<h2>Exam Report</h2><p>Exam report content goes here.</p>'; // Placeholder
                    break;
                case 'instructor_feedbacks':
                    // Include the file for instructor feedbacks
                     echo '<h2>Instructor Feedbacks</h2><p>Instructor feedbacks go here.</p>'; // Placeholder
                    break;
                // Add cases for other instructor actions
                default:
                    // Action not found for instructor
                    echo '<p>Instructor action not found.</p>';
                    break;
            }
            break;

        case 'student':
            // Handle actions specific to the student role
            switch ($action) {
                 case 'dashboard':
                    // Include or generate student dashboard content
                    echo '<h2>Student Dashboard</h2><p>Student dashboard content goes here.</p>';
                    break;
                case 'student_upcoming_exams':
                    // Include or generate content for the list of upcoming exams
                    echo '<h2>Upcoming Exams</h2><p>List of upcoming exams goes here.</p>'; // Placeholder
                    break;
                 case 'student_exam_schedule':
                    // Include or generate content for the exam schedule
                    echo '<h2>Exam Schedule</h2><p>Exam schedule list goes here.</p>'; // Placeholder
                    break;
                 case 'student_taken_exams':
                    // Include or generate content for the list of taken exams
                    echo '<h2>Taken Exams</h2><p>List of taken exams goes here.</p>'; // Placeholder
                    break;
                 case 'student_add_feedback':
                    // Include or generate content for adding feedback
                    echo '<h2>Add Feedback</h2><p>Feedback form goes here.</p>'; // Placeholder
                    break;
                // Add cases for other student actions
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
