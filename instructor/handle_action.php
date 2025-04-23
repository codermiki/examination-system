<?php

// This script handles AJAX requests from the sidebar to load content dynamically

include_once '../config.php';


if (session_status() == PHP_SESSION_NONE) {
    session_start();
}


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
                    
                    echo '<h2>Admin Dashboard</h2><p>Admin dashboard content goes here.</p>';
                    break;
                case 'admin_add_student':
                   
                    include "add_student.php";
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
                    
                     echo '<h2>Schedule Exam</h2><p>Schedule exam form goes here (Admin view).</p>'; 
                    break;
                case 'admin_manage_schedule':
                    
                     echo '<h2>Manage Schedule</h2><p>Manage exam schedules interface goes here (Admin view).</p>'; 
                    break;
                case 'admin_all_feedbacks':
                    
                     echo '<h2>All Feedbacks</h2><p>List of all feedbacks goes here (Admin view).</p>'; 
                    break;
                default:
                    
                    echo '<p>Admin action not found.</p>';
                    break;
            }
            break;

        case 'instructor':
            
            switch ($action) {
                 case 'dashboard':
                    
                    include "dashboard.php";
                    break;
                case 'instructor_create_exam':
                    
                    include "create_exam.php";
                    break;
                case 'instructor_import_exam':
                    include "import_exam.php";
                    break;
                case 'instructor_manage_exam':
                    include "manage_exam.php";
                    break;
                case 'instructor_view_exam':
                    include "process_create_exam.php";
                    break;
                    case 'instructor_create_exam_submit':
                    include "view_exam.php";
                    break;
                case 'instructor_edit_exam':
                    include "edit_exam.php";
                    break;
                case 'instructor_exam_report':
                    
                     echo '<h2>Exam Report</h2><p>Exam report content goes here.</p>'; 
                    break;
                case 'instructor_feedbacks':
                    
                     echo '<h2>Instructor Feedbacks</h2><p>Instructor feedbacks go here.</p>'; 
                    break;
                
                default:
                    
                    echo '<p>Instructor action not found.</p>';
                    break;
            }
            break;

        case 'student':
            
            switch ($action) {
                 case 'dashboard':
                    
                    echo '<h2>Student Dashboard</h2><p>Student dashboard content goes here.</p>';
                    break;
                case 'student_upcoming_exams':
                    
                    echo '<h2>Upcoming Exams</h2><p>List of upcoming exams goes here.</p>'; 
                    break;
                 case 'student_exam_schedule':
                    
                    echo '<h2>Exam Schedule</h2><p>Exam schedule list goes here.</p>'; 
                    break;
                 case 'student_taken_exams':
                    
                    echo '<h2>Taken Exams</h2><p>List of taken exams goes here.</p>'; 
                    break;
                 case 'student_add_feedback':
                    
                    echo '<h2>Add Feedback</h2><p>Feedback form goes here.</p>'; 
                    break;
                
                default:
                    
                    echo '<p>Student action not found.</p>';
                    break;
            }
            break;

        default:
            
            echo '<p>Invalid user role.</p>';
            break;
    }
} else {
    
    echo '<h2>Welcome!</h2><p>Select an option from the sidebar.</p>';
}
?>
