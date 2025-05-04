<?php
include_once '../config.php';

// Start the session if it hasn't been started already
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>

<aside class="inner__left_panel">

    <?php
    if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin') {
        ?>
        <div class="role__container">
            <p>MANAGE STUDENTS</p>
            <div class="drop__down">
                <button onclick="collapse();" type="button">Students</button>
                <div id="collapse" class="collapse-wrapper">
                    <div class="collapse">
                        <div class="v__line"></div>
                        <div class="action">
                            <a id="assign_student_toggler" onclick="showAssignStudent(event)" href="#">Add Student</a>
                            <a href="#">Manage Student</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="role__container">
            <p>MANAGE INSTRUCTOR</p>
            <div class="drop__down">
                <div class="non-collapse ">
                    <div class="v__line"></div>
                    <div class="action">
                        <a id="assign_instructor_toggler" onclick="showAssignInstructor(event)"
                            href="#assign_instructor">Assign
                            Instructor</a>
                        <a href="#">Manage Instructor</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="role__container">
            <p>MANAGE COURSES</p>
            <div class="drop__down">
                <div class="non-collapse ">
                    <div class="v__line"></div>
                    <div class="action">
                        <a href="index.php?page=add_course">Add Course</a>
                        <a href="#">Manage Course</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="role__container">
            <p>MANAGE EXAM</p>
            <div class="drop__down">
                <div class="non-collapse ">
                    <div class="v__line"></div>
                    <div class="action">
                        <a href="#" class="sidebar-link" data-content="admin_schedule_exam">Schedule Exam</a>
                        <a href="#" class="sidebar-link" data-content="admin_manage_schedule">Manage Schedule</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="role__container">
            <p>FEEDBACKS</p>
            <div class="drop__down">
                <div class="non-collapse ">
                    <div class="v__line"></div>
                    <div class="action">
                        <a href="#" class="sidebar-link" data-content="admin_all_feedbacks">All Feedbacks</a>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    ?>
    <?php
    if (isset($_SESSION['role']) && $_SESSION['role'] == 'instructor') {
        ?>
        <a href="/">Dashboards</a>

        <div class="role__container">
            <p>MANAGE EXAM</p>
            <div class="drop__down">
                <button class="collapsebtn" onclick="toggleCollapse(this);" type="button">Exam</button>
                <div class="collapse">
                    <div class="v__line"></div>
                    <div class="action">
                        <!-- <a href="/softexam/instructor/index.php?page=manage_exam">Manage Exam</a> -->
                        <a href="#" class="sidebar-link" data-content="instructor_create_exam">Create Exam</a>
                        <a href="#" class="sidebar-link" data-content="instructor_import_exam">Import Exam</a>
                        <a href="#" class="sidebar-link" data-content="instructor_manage_exam">Manage Exam</a>
                        <a href="#" class="sidebar-link" data-content="instructor_manage_questions">Manage Questions</a>
                        <a href="#" class="sidebar-link" data-content="instructor_view_exam">View Exam</a>
                        <a href="#" class="sidebar-link" data-content="instructor_edit_exam">Edit Exam</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="role__container">
            <p>REPORTS</p>
            <div class="drop__down">
                <div class="non-collapse ">
                    <div class="v__line"></div>
                    <div class="action">
                        <a href="#" class="sidebar-link" data-content="instructor_exam_report">Exam Report</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="role__container">
            <p>FEEDBACKS</p>
            <div class="drop__down">
                <div class="non-collapse ">
                    <div class="v__line"></div>
                    <div class="action">
                        <a href="#" class="sidebar-link" data-content="instructor_feedbacks">Feedbacks</a>
                    </div>
                </div>
            </div>
        </div>

        <?php
    }
    ?>
    <?php
    if (isset($_SESSION['role']) && $_SESSION['role'] == 'student') {
        ?>

        <div class="role__container">
            <p>AVAILABLE EXAMS</p>
            <div class="drop__down">
                <button onclick="toggleCollapse(this);" type="button">Upcoming Exams</button>
                <div class="collapse">
                    <div class="v__line"></div>
                    <div class="action">
                        <a href="#" class="sidebar-link" data-content="student_upcoming_exams">Upcoming Exams List</a>
                        <a href="#" class="sidebar-link" data-content="student_exam_schedule">Exam Schedule</a>
                        <a href="#" class="sidebar-link" data-content="student_take_exam">Take Exam</a>
                        <a href="#" class="sidebar-link" data-content="student_taken_exams">Taken Exams</a>
                        <a href="#" class="sidebar-link" data-content="student_view_result">View Result</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="role__container">
            <p>TAKEN EXAMS</p>
            <div class="drop__down">
                <div class="non-collapse ">
                    <div class="v__line"></div>
                    <div class="action">
                        <a href="#" class="sidebar-link" data-content="student_taken_exams">Taken Exams List</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="role__container">
            <p>FEEDBACKS</p>
            <div class="drop__down">
                <div class="non-collapse ">
                    <div class="v__line"></div>
                    <div class="action">
                        <a href="#" class="sidebar-link" data-content="student_add_feedback">Add Feedback</a>
                    </div>
                </div>
            </div>
        </div>

        <?php
    }
    ?>

</aside>

<script>
    function collapse() {
        const el = document.querySelector("#collapse");
        el.classList.toggle("collapsed");
    }
</script>