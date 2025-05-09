<?php
// includes/instructor/feedbacks.php

// This file displays a list of student feedbacks for the instructor.

// Include necessary configuration or database files
include_once __DIR__ . '/../../config.php';
include_once __DIR__ . '/../../includes/db/db.config.php'; // Assuming this file sets up the $pdo database connection

// Start the session if it hasn't been started already
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Security check: Ensure the user is logged in and is an instructor
if (!isset($_SESSION['email']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'instructor' || !isset($_SESSION['user_id'])) {
    // Redirect to login or show access denied message
    header('Location: ../../login.php'); // Adjust the path as needed
    exit();
}

$message = ''; // Variable to store feedback messages
$feedbacks = []; // Array to hold feedbacks fetched from DB

$instructorId = $_SESSION['user_id']; // Get the logged-in instructor's user_id
// $instructorId = 2; // Uncomment for testing with a fixed instructor ID if needed

// --- Start: PHP Logic for Fetching Feedbacks ---

try {
    // Fetch feedbacks submitted by students for exams/courses
    // that belong to this instructor.
    // Joins feedbacks with users (for student name), courses (for course name),
    // and exams (to link feedbacks to exams owned by the instructor).
    $sql = "SELECT
                f.feedback_id,
                f.student_id,
                f.course_id,
                f.exam_id,
                f.message,
                f.rating,
                f.created_at,
                u.first_name,
                u.last_name,
                c.course_name,
                e.title AS exam_title
            FROM feedbacks f
            JOIN users u ON f.student_id = u.user_id
            JOIN courses c ON f.course_id = c.course_id
            JOIN exams e ON f.exam_id = e.exam_id
            WHERE e.instructor_id = :instructor_id -- Filter feedbacks for exams created by this instructor
            ORDER BY f.created_at DESC"; 
            // -- Order by newest feedback first

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':instructor_id', $instructorId, PDO::PARAM_INT);
    $stmt->execute();
    $feedbacks = $stmt->fetchAll(PDO::FETCH_ASSOC); // $feedbacks will be set if feedbacks are found

} catch (PDOException $e) {
    // Log error and display a user-friendly message
    error_log("Error fetching instructor's feedbacks: " . $e->getMessage()); // Log the detailed error
    $message = '<p class="error">Error loading feedbacks. Please try again later.</p>';
}
// --- End: PHP Logic for Fetching Feedbacks ---

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Feedbacks</title>
    <style>
        /* General Container Styling (Consistent with other instructor pages) */
        .page-container {
            background-color: #f9f9f9;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            max-width: 1000px; /* Slightly wider for feedback table */
            margin: 30px auto;
            font-family: sans-serif;
            color: #333;
        }

        .page-container h1, .page-container h2 {
             color: #0056b3;
             text-align: center;
             margin-bottom: 25px;
        }

        .page-container h2 {
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }

        /* Message Styling */
        .message {
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-size: 1em;
            line-height: 1.4;
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

        /* Feedbacks Table Styling (Similar to other tables) */
        .feedbacks-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
            background-color: #fff;
            border-radius: 5px;
            overflow: hidden;
        }

        .feedbacks-table th, .feedbacks-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
            vertical-align: top; /* Align content to top in cells */
        }

        .feedbacks-table th {
            background-color: #f2f2f2;
            font-weight: bold;
            color: #555;
        }

        .feedbacks-table tbody tr:hover {
            background-color: #f9f9f9;
        }

        .feedbacks-table td {
            color: #333;
        }

        /* Styling for rating column */
        .feedbacks-table td:nth-child(6) { /* Assuming rating is the 6th column */
            font-weight: bold;
            text-align: center; /* Center the rating */
        }

        /* Styling for feedback message - allow wrapping */
        .feedbacks-table td:nth-child(5) { /* Assuming message is the 5th column */
            white-space: pre-wrap; /* Preserve whitespace and wrap text */
            word-wrap: break-word; /* Break long words */
        }

    </style>
</head>
<body>

    <?php // include_once '../includes/layout/InstructorSidebar.php'; // Example ?>

    <main>
        <div class="page-container">

            <h1>Student Feedbacks</h1>

            <?php
            // Display feedback message if any
            if (!empty($message)) {
                echo '<div class="message ' . (strpos($message, 'Error') !== false ? 'error' : 'success') . '">' . $message . '</div>';
            }
            ?>


            <?php if (!empty($feedbacks)): ?>
                <table class="feedbacks-table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Course</th>
                            <th>Exam</th>
                            <th>Date</th>
                            <th>Message</th>
                            <th>Rating (1-5)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($feedbacks as $feedback): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($feedback['first_name'] . ' ' . $feedback['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($feedback['course_name']); ?></td>
                                <td><?php echo htmlspecialchars($feedback['exam_title']); ?></td>
                                <td><?php echo htmlspecialchars(date('M d, Y H:i', strtotime($feedback['created_at']))); ?></td>
                                <td><?php echo nl2br(htmlspecialchars($feedback['message'])); ?></td>
                                <td><?php echo htmlspecialchars($feedback['rating']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No feedbacks have been submitted for your exams yet.</p>
            <?php endif; ?>

        </div>
    </main>

    <?php // include_once '../includes/layout/footer.php'; // Example ?>

</body>
</html>
