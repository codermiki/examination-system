<?php
include_once '../config.php';

?>

<aside class="inner__left_panel">
    <!-- admin roles -->
    <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'Admin') { ?>
        <a href="./">Dashboards</a>
        <div class="role__container">
            <p>MANAGE STUDENTS</p>
            <div class="drop__down">
                <div class="non-collapse ">
                    <div class="v__line"></div>
                    <div class="action">
                        <a href="index.php?page=assign_student">Assign
                            Student</a>
                        <a href="index.php?page=manage_student">Manage Student</a>
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
                        <a href="index.php?page=assign_instructor">Assign
                            Instructor</a>
                        <a href="index.php?page=manage_instructor">Manage Instructor</a>
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
                        <a href="index.php?page=manage_course">Manage Course</a>
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
                        <a href="index.php?page=schedule_exam">Schedule Exam</a>
                        <a href="index.php?page=manage_schedule">Manage Schedule</a>
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
                        <a href="index.php?page=feed_backs">All Feedbacks</a>
                    </div>
                </div>
            </div>
        </div>
    <?php } ?>

    <!-- Instructor roles -->
    <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'Instructor') { ?>
        <a href="./">Dashboards</a>
        <div class="role__container">
            <p>CREATE EXAM</p>
            <div class="drop__down">
                <!-- <button class="collapsebtn" onclick="toggleCollapse(this);" type="button">Exam</button> -->
                <div class="collapse">
                    <div class="v__line"></div>
                    <div class="action">
                        <!-- <a href="/softexam/instructor/index.php?page=manage_exam">Manage Exam</a> -->
                        <a href="index.php?page=create_exam">Create Exam</a>
                        <a href="index.php?page=import_exam">Import Exam</a>
                        <!-- <a href="index.php?page=view_exam">View Exam</a>
                        <a href="index.php?page=edit_exam">Edit Exam</a> -->
                    </div>
                </div>
            </div>
        </div>


        <div class="role__container">
            <p>MANAGE EXAM</p>
            <div class="drop__down">
                <div class="collapse">
                    <div class="v__line"></div>
                    <div class="action">
                        <a href="index.php?page=view_exam">Manage Exam</a>
                        <!-- <a href="index.php?page=edit_exam">Edit Exam</a>
                        <a href="index.php?page=delete_exam">Delete Exam</a> -->
                    </div>
                </div>
            </div>
        </div>

        <!-- <div class="role__container">
            <p>MANAGE Questions</p>
            <div class="drop__down">
                <div class="collapse">
                    <div class="v__line"></div>
                    <div class="action">
                        <a href="index.php?page=manage_questions" >Manage Questions</a>
                    </div>
                </div>
            </div>
        </div> -->

        <div class="role__container">
            <p>REPORTS</p>
            <div class="drop__down">
                <div class="non-collapse ">
                    <div class="v__line"></div>
                    <div class="action">
                        <a href="index.php?page=exam_report">Exam Report</a>
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
                        <a href="index.php?page=feedbacks">Feedbacks</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="role__container">
            <p>Profile</p>
            <div class="drop__down">
                <div class="non-collapse ">
                    <div class="v__line"></div>
                    <div class="action">
                        <a href="index.php?page=update_password">Update Password</a>
                    </div>
                </div>
            </div>
        </div>
    <?php } ?>
</aside>