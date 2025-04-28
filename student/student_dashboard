<?php
// includes/student/student_dashboard.php

// This is the default dashboard page for students.

// Include necessary configuration or database files
include_once '../config.php';
include_once '../includes/db/db.config.php';

// Start the session if it hasn't been started already
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Security check: Ensure the user is logged in and is a student
if (!isset($_SESSION['email']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'student' || !isset($_SESSION['user_id'])) {
    echo '<p>Access denied. You must be a logged-in student to view this page.</p>';
    exit();
}

$studentId = $_SESSION['user_id']; // Get the logged-in student's user_id
$studentName = $_SESSION['name'] ?? 'Student'; // Get student name from session

// --- Start: PHP Logic for Dashboard Data (Optional) ---
// You can fetch some summary data here, e.g.,
// - Number of upcoming exams
// - Number of taken exams
// - Recent results

$upcomingExamsCount = 0;
$takenExamsCount = 0;

try {
    // Count upcoming exams (example: exams scheduled in the future for courses the student is enrolled in)
    // This requires joining student_courses (if you have one) or checking exam_schedule against student's courses
    // For now, a simplified count of all scheduled exams
    $stmt = $pdo->query("SELECT COUNT(*) FROM exam_schedule WHERE exam_date >= CURDATE()");
    $upcomingExamsCount = $stmt->fetchColumn();

    // Count taken exams for this student
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM student_exams WHERE student_id = :student_id AND submitted_at IS NOT NULL");
    $stmt->bindParam(':student_id', $studentId, PDO::PARAM_INT);
    $stmt->execute();
    $takenExamsCount = $stmt->fetchColumn();

} catch (PDOException $e) {
    error_log("Error fetching student dashboard data: " . $e->getMessage());
    // Handle error gracefully
}

// --- End: PHP Logic for Dashboard Data ---

?>

<style>
    /* Basic styling for the student dashboard */
    .student-dashboard-container {
        background-color: #f9f9f9;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        max-width: 800px;
        margin: 20px auto;
        text-align: center;
    }

    .student-dashboard-container h2 {
        color: #333;
        margin-bottom: 15px;
    }

    .dashboard-stats {
        margin-top: 20px;
        display: flex;
        justify-content: space-around;
        flex-wrap: wrap;
    }

    .stat-box {
        background-color: #fff;
        padding: 15px;
        border-radius: 8px;
        border: 1px solid #ddd;
        margin: 10px;
        flex: 1;
        min-width: 180px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    }

    .stat-box h3 {
        color: #007bff;
        margin-top: 0;
        margin-bottom: 5px;
    }

    .stat-box p {
        font-size: 1.2em;
        font-weight: bold;
        color: #555;
    }
</style>

<div class="student-dashboard-container">
    <h2>Welcome, <?php echo htmlspecialchars($studentName); ?>!</h2>
    <p>This is your student dashboard. Use the sidebar to navigate.</p>

    <div class="dashboard-stats">
        <div class="stat-box">
            <h3>Upcoming Exams</h3>
            <p><?php echo htmlspecialchars($upcomingExamsCount); ?></p>
        </div>
        <div class="stat-box">
            <h3>Exams Taken</h3>
            <p><?php echo htmlspecialchars($takenExamsCount); ?></p>
        </div>
        </div>

    <p style="margin-top: 20px;">Select an option from the left sidebar to view your upcoming exams, taken exams, or add feedback.</p>
</div>

<?php
// No JavaScript needed for this basic dashboard.
?>
