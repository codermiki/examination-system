<?php
include_once __DIR__ . "/../../includes/functions/Dashboard_function.php";

$data = Dashboard_function::fetchDashboardData();

?>

<div class="container admin-dashboard">
    <h1>Welcome to Softexam Admin Panel</h1>
    <div class="cards">
        <div class="card">
            <h2 id="students">
                <?= htmlspecialchars($data['student']) ?>
            </h2>
            <p>Total Students</p>
        </div>
        <div class="card">
            <h2 id="instructors">
                <?= htmlspecialchars($data['instructor']) ?>
            </h2>
            <p>Total Instructors</p>
        </div>
        <div class="card">
            <h2 id="courses">
                <?= htmlspecialchars($data['admin']) ?>
            </h2>
            <p>Total Admins</p>
        </div>
        <div class="card">
            <h2 id="courses">
                <?= htmlspecialchars($data['total_courses']) ?>
            </h2>
            <p>Total Courses</p>
        </div>
        <div class="card">
            <h2 id="exams">
                <?= htmlspecialchars($data['upcoming_exams']) ?>
            </h2>
            <p>Upcoming Exams</p>
        </div>
    </div>
</div>