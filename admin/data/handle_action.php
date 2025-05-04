<?php
// import session configuration
include_once '../config.php';

if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $role = $_SESSION['role'];

    switch ($role) {
        case 'admin':
            switch ($action) {
                case 'dashboard':
                    include "dashboard.php";
                    break;

                case 'assign_student':
                    include "./ui/assign_student.php";
                    break;

                case 'manage_student':
                    include "./ui/manage_student.php";
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


        default:
            // Role not recognized
            echo '<p>page not found.</p>';
            break;
    }
}