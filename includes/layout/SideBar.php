<?php
include_once '../config.php';
?>


<aside class="inner__left_panel">
    <a href="#">Dashboards</a>

    <!-- admin role start -->
    <?php
    if ($_SESSION['role'] == 3) {
        ?>
        <div class="role__container">
            <p>MANAGE STUDENTS</p>
            <div class="drop__down">
                <button onclick="collapse();" type="button">Course</button>
                <div class="collapse">
                    <div class="v__line"></div>
                    <div class="action">
                        <a href="#">Add Student</a>
                        <a href="#">Manage Student</a>
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
                        <a href="#">Assign Instructor</a>
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
                        <a href="#">Add Course</a>
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
                        <a href="#">Schedule Exam</a>
                        <a href="#">Manage Schedule</a>
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
                        <a href="#">All Feedbacks</a>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    ?>
    <!-- admin role end -->

    <!-- instructor role start -->
    <?php
    if ($_SESSION['role'] == 2) {
        ?>

        <div class="role__container">
            <p>MANAGE EXAM</p>
            <div class="drop__down">
                <button onclick="collapse();" type="button">Exam</button>
                <div class="collapse">
                    <div class="v__line"></div>
                    <div class="action">
                        <a href="#">Add Exam</a>
                        <a href="#">Manage Exam</a>
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
                        <a href="#">Exam Report</a>
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
                        <a href="#">Feedbacks</a>
                    </div>
                </div>
            </div>
        </div>

        <?php
    }
    ?>
    <!-- instructor role end -->


    <!-- student role start -->
    <?php
    if ($_SESSION['role'] == 1) {
        ?>

        <div class="role__container">
            <p>AVAILABLE EXAMS</p>
            <div class="drop__down">
                <button onclick="collapse();" type="button">Upcoming Exams</button>
                <div class="collapse">
                    <div class="v__line"></div>
                    <div class="action">
                        <a href="#">exam 1</a>
                        <a href="#">Exam Schedule</a>
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
                        <a href="#">exam 1</a>
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
                        <a href="#">Add Feedback</a>
                    </div>
                </div>
            </div>
        </div>

        <?php
    }
    ?>

    <!-- student role end -->
</aside>

<script>
    function collapse() {
        const el = document.querySelector(".collapse");
        el.classList.toggle("action-collapsed");
    }
</script>