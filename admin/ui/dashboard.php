<?php
include_once __DIR__ . "/../../includes/functions/Dashboard_function.php";

$data = Dashboard_function::fetchDashboardData();

?>

<div class="container admin-dashboard">
    <h1>Welcome to Softexam Admin Panel</h1>
    <div class="cards">
        <a href="index.php?page=manage_student">
            <div class="card">
                <h2 id="students">
                    <?= htmlspecialchars($data['Student']) ?>
                </h2>
                <p>Total Students</p>
            </div>
        </a>

        <a href="index.php?page=manage_instructor">
            <div class="card">
                <h2 id="instructors">
                    <?= htmlspecialchars($data['Instructor']) ?>
                </h2>
                <p>Total Instructors</p>
            </div>
        </a>

        <a href="index.php?page=manage_course">
            <div class="card">
                <h2 id="courses">
                    <?= htmlspecialchars($data['total_courses']) ?>
                </h2>
                <p>Total Courses</p>
            </div>
        </a>

        <a href="index.php?page=manage_schedule">
            <div class="card">
                <h2 id="exams">
                    <?= htmlspecialchars($data['upcoming_exams']) ?>
                </h2>
                <p>Upcoming Exams</p>
            </div>
        </a>
    </div>
</div>