<?php
// includes/student/student_taken_exams.php

// This file displays a list of exams the student has taken and submitted.

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
$takenExams = []; // Array to hold taken exam data
$message = ''; // Variable for messages

// --- Start: PHP Logic for Fetching Taken Exams ---

try {
    // Fetch exams the student has submitted, joining with exams and courses tables
    $sql = "SELECT se.id as student_exam_id, se.submitted_at, se.score,
                   e.exam_id, e.title, e.total_marks,
                   c.course_name
            FROM student_exams se
            JOIN exams e ON se.exam_id = e.exam_id
            JOIN courses c ON e.course_id = c.course_id
            WHERE se.student_id = :student_id AND se.submitted_at IS NOT NULL
            ORDER BY se.submitted_at DESC"; // Order by submission date

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':student_id', $studentId, PDO::PARAM_INT);
    $stmt->execute();
    $takenExams = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error fetching taken exams: " . $e->getMessage());
    $message = '<p class="error">Error loading taken exams. Please try again later.</p>';
}

// --- End: PHP Logic for Fetching Taken Exams ---

?>

<style>
    /* Basic styling for the taken exams page */
    .taken-exams-container {
        background-color: #f9f9f9;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        max-width: 800px;
        margin: 20px auto;
    }

    .taken-exams-container h2 {
        text-align: center;
        color: #333;
        margin-bottom: 20px;
    }

    .taken-exams-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }

    .taken-exams-table th, .taken-exams-table td {
        border: 1px solid #ddd;
        padding: 10px;
        text-align: left;
    }

    .taken-exams-table th {
        background-color: #f2f2f2;
        font-weight: bold;
        color: #555;
    }

    .taken-exams-table tbody tr:nth-child(even) {
        background-color: #f9f9f9;
    }

    .taken-exams-table tbody tr:hover {
        background-color: #e9e9e9;
    }

    .result-link {
        display: inline-block;
        background-color: #007bff;
        color: white;
        padding: 5px 10px;
        border-radius: 4px;
        text-decoration: none;
        font-size: 0.9em;
    }

    .result-link:hover {
        background-color: #0056b3;
    }

    .message {
        margin-top: 15px;
        padding: 10px;
        border-radius: 4px;
    }

    .success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .error {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
</style>

<div class="taken-exams-container">
    <h2>Taken Exams</h2>

    <?php
    // Display feedback message if any
    if (!empty($message)) {
        echo '<div class="message ' . (strpos($message, 'Error') !== false ? 'error' : 'success') . '">' . $message . '</div>';
    }
    ?>

    <?php if (empty($takenExams)): ?>
        <p>You have not taken any exams yet.</p>
    <?php else: ?>
        <table class="taken-exams-table">
            <thead>
                <tr>
                    <th>Exam Title</th>
                    <th>Course</th>
                    <th>Submission Date</th>
                    <th>Score</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($takenExams as $exam): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($exam['title']); ?></td>
                        <td><?php echo htmlspecialchars($exam['course_name']); ?></td>
                        <td><?php echo htmlspecialchars($exam['submitted_at']); ?></td>
                        <td><?php echo htmlspecialchars($exam['score']); ?> / <?php echo htmlspecialchars($exam['total_marks']); ?></td>
                        <td>
                            <a href="#" class="result-link sidebar-link" data-content="student_view_result" data-student-exam-id="<?php echo $exam['student_exam_id']; ?>">View Result</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

</div>

<?php
// No JavaScript needed for this page.
?>
